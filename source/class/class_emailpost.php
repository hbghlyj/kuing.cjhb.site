<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class emailpost_rejection extends RuntimeException {}

class emailpost {

	private const EMAIL_POST_STATUS = (1 << 4) | (1 << 9);
	private array $config;
	protected $mailbox;

	public static function run() {
		$default = require DISCUZ_ROOT.'config/config_emailpost_default.php';
		$localfile = DISCUZ_ROOT.'config/config_emailpost.php';
		$local = is_file($localfile) ? require $localfile : [];
		$config = array_merge($default, is_array($local) ? $local : []);
		if(empty($config['enabled'])) {
			return;
		}
		(new self($config))->consume();
	}

	public function __construct(array $config) {
		$this->config = $config;
	}

	protected function consume() {
		if(!function_exists('imap_open')) {
			throw new RuntimeException('The PHP IMAP extension is required for email posting.');
		}
		if(empty($this->config['mailbox']) || empty($this->config['username']) || empty($this->config['password'])) {
			throw new RuntimeException('Email posting mailbox credentials are incomplete.');
		}
		if(empty($this->config['recipient_domain'])) {
			throw new RuntimeException('Email posting recipient_domain is required.');
		}
		if(!empty($this->config['require_dmarc']) && empty($this->config['trusted_authserv_id'])) {
			throw new RuntimeException('trusted_authserv_id is required when DMARC validation is enabled.');
		}

		$lockfile = DISCUZ_ROOT.'data/sysdata/emailpost.lock';
		$lock = fopen($lockfile, 'c');
		if(!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
			return;
		}

		try {
			$this->mailbox = $this->imapOpen($this->config['mailbox'], $this->config['username'], $this->config['password']);
			if(!$this->mailbox) {
				throw new RuntimeException('Unable to open the email posting mailbox: '.imap_last_error());
			}
			$uids = $this->imapSearch($this->mailbox, 'UNSEEN', SE_UID) ?: [];
			$uids = array_slice($uids, 0, max(1, intval($this->config['max_messages'])));
			foreach($uids as $mailuid) {
				$this->consumeMessage(intval($mailuid));
			}
		} finally {
			if($this->mailbox) {
				$this->imapClose($this->mailbox, CL_EXPUNGE);
			}
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	protected function consumeMessage(int $mailuid) {
		$rawHeaders = $this->imapFetchHeader($this->mailbox, $mailuid, FT_UID) ?: '';
		$headers = $this->imapHeaderInfo($this->mailbox, $this->imapMsgNo($this->mailbox, $mailuid));
		$messageid = $this->firstMessageId($rawHeaders, 'Message-ID');
		if(!$messageid) {
			$messageid = '<missing-'.hash('sha256', $rawHeaders).'@'.strtolower($this->config['recipient_domain']).'>';
		}
		$messagekey = hash('sha256', $messageid);
		if(table_forum_emailpost::t()->fetch($messagekey)) {
			$this->finishMessage($mailuid);
			return;
		}

		$sender = $this->senderAddress($headers);
		$reserved = table_forum_emailpost::t()->reserve([
			'messagekey' => $messagekey,
			'mailuid' => $mailuid,
			'messageid' => cutstr($messageid, 255),
			'sender' => cutstr($sender, 255),
			'uid' => 0,
			'action' => 'thread',
			'dateline' => TIMESTAMP,
		]);
		if(!$reserved) {
			$this->finishMessage($mailuid);
			return;
		}

		try {
			$this->validateAutomatedHeaders($rawHeaders);
			$this->validateDmarc($rawHeaders);
			$member = $this->memberForSender($sender);
			table_forum_emailpost::t()->update($messagekey, ['uid' => $member['uid']]);

			$parent = $this->findParent($rawHeaders);
			if($parent) {
				$fid = intval($parent['fid']);
				$tid = intval($parent['tid']);
				$action = 'reply';
			} else {
				$fid = $this->forumIdFromRecipient($rawHeaders);
				$tid = 0;
				$action = 'thread';
			}

			$subject = dhtmlspecialchars(trim($this->decodeHeader($headers->subject ?? '')));
			$message = $this->messageBody($mailuid);
			if($message === '') {
				throw new emailpost_rejection('Email body is empty.');
			}
			if($action === 'thread' && $subject === '') {
				throw new emailpost_rejection('A subject is required for a new thread.');
			}

			table_forum_emailpost::t()->update($messagekey, ['action' => $action, 'fid' => $fid, 'tid' => $tid]);
			$result = $this->postAsMember($member, $fid, $tid, $subject, $message);
			table_forum_emailpost::t()->complete(
				$messagekey,
				$result['fid'],
				$result['tid'],
				$result['pid'],
				$parent['messagekey'] ?? ''
			);
			$this->finishMessage($mailuid);
			runlog('emailpost', 'Accepted '.$messageid.' as pid '.$result['pid']);
		} catch(emailpost_rejection $e) {
			table_forum_emailpost::t()->reject($messagekey, $e->getMessage());
			$this->finishMessage($mailuid);
			runlog('emailpost', 'Rejected '.$messageid.': '.$e->getMessage());
		} catch(Throwable $e) {
			table_forum_emailpost::t()->delete($messagekey);
			runlog('error', 'Email posting failed for '.$messageid.': '.$e->getMessage());
		}
	}

	protected function finishMessage(int $mailuid) {
		$this->imapSetFlagFull($this->mailbox, (string)$mailuid, '\\Seen', ST_UID);
		if(!empty($this->config['delete_after_posting'])) {
			$this->imapDelete($this->mailbox, (string)$mailuid, FT_UID);
		}
	}

	private function validateAutomatedHeaders(string $headers) {
		if(preg_match('/^Auto-Submitted:\s*(?!no\b)\S+/im', $headers)
			|| preg_match('/^Precedence:\s*(bulk|list|junk)\b/im', $headers)) {
			throw new emailpost_rejection('Automated and bulk email is not accepted.');
		}
	}

	private function validateDmarc(string $headers) {
		if(empty($this->config['require_dmarc'])) {
			return;
		}
		$unfolded = preg_replace("/\r?\n[\t ]+/", ' ', $headers);
		$authserv = preg_quote(strtolower(trim($this->config['trusted_authserv_id'])), '/');
		if(!preg_match('/^Authentication-Results:\s*'.$authserv.'\s*;[^\r\n]*\bdmarc=pass\b/im', strtolower($unfolded))) {
			throw new emailpost_rejection('DMARC did not pass at the trusted mail server.');
		}
	}

	private function memberForSender(string $sender) {
		if(!$sender || !filter_var($sender, FILTER_VALIDATE_EMAIL)) {
			throw new emailpost_rejection('The sender address is invalid.');
		}
		$member = table_common_member::t()->fetch_by_email(mb_strtolower($sender, 'UTF-8'), 1);
		if(!$member || empty($member['emailstatus'])) {
			throw new emailpost_rejection('The sender is not a verified forum member.');
		}
		if(!empty($member['freeze']) || in_array(intval($member['groupid']), [4, 5, 6], true)) {
			throw new emailpost_rejection('The member account cannot post.');
		}
		return $member;
	}

	private function findParent(string $headers) {
		$ids = $this->messageIdsForHeader($headers, 'In-Reply-To');
		foreach(array_reverse($ids) as $id) {
			if($parent = $this->acceptedMessage($id)) {
				return $parent;
			}
		}
		$ids = $this->messageIdsForHeader($headers, 'References');
		foreach(array_reverse($ids) as $id) {
			if($parent = $this->acceptedMessage($id)) {
				return $parent;
			}
		}
		return [];
	}

	private function acceptedMessage(string $messageid) {
		$row = table_forum_emailpost::t()->fetch_by_message_id($messageid);
		if($row && intval($row['status']) === 1 && !empty($row['tid'])) {
			return $row;
		}

		$domain = preg_quote(strtolower(trim($this->config['recipient_domain'])), '/');
		if(!preg_match('/^<post-(\d+)@'.$domain.'>$/i', $messageid, $match)) {
			return [];
		}
		require_once libfile('function/forum');
		$post = get_post_by_pid(intval($match[1]));
		if(!$post || intval($post['invisible']) !== 0) {
			return [];
		}
		$thread = table_forum_thread::t()->fetch($post['tid']);
		if(!$thread || intval($thread['displayorder']) < 0) {
			return [];
		}
		return [
			'messagekey' => hash('sha256', $messageid),
			'fid' => $post['fid'],
			'tid' => $post['tid'],
			'pid' => $post['pid'],
		];
	}

	private function forumIdFromRecipient(string $headers) {
		$domain = preg_quote(strtolower(trim($this->config['recipient_domain'])), '/');
		$recipients = implode(' ', array_merge(
			$this->headerValues($headers, 'To'),
			$this->headerValues($headers, 'Delivered-To'),
			$this->headerValues($headers, 'X-Original-To'),
			$this->headerValues($headers, 'Envelope-To')
		));
		preg_match_all('/\bforum\+(\d+)@'.$domain.'\b/i', strtolower($recipients), $matches);
		$fids = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
		if(count($fids) !== 1 || !$fids[0]) {
			throw new emailpost_rejection('Use exactly one forum+FID recipient for a new thread.');
		}
		return $fids[0];
	}

	private function postAsMember(array $member, int $fid, int $tid, string $subject, string $message) {
		global $_G;
		$app = C::app();
		$keys = ['member', 'group', 'forum', 'thread', 'forum_thread', 'uid', 'username', 'adminid', 'groupid', 'fid', 'tid'];
		$saved = [];
		foreach($keys as $key) {
			$saved[$key] = $app->var[$key] ?? null;
		}

		try {
			loadcache('usergroup_'.$member['groupid']);
			$group = $app->var['cache']['usergroup_'.$member['groupid']] ?? [];
			if($member['adminid'] > 0 && $member['groupid'] != $member['adminid']) {
				loadcache('admingroup_'.$member['adminid']);
				$group = array_merge($group, $app->var['cache']['admingroup_'.$member['adminid']] ?? []);
			}
			$app->var['member'] = $member;
			$app->var['group'] = $group;
			foreach(['uid', 'username', 'adminid', 'groupid'] as $key) {
				$app->var[$key] = $member[$key];
			}
			$app->var['forum'] = $app->var['thread'] = $app->var['forum_thread'] = [];
			$app->var['fid'] = $fid;
			$app->var['tid'] = $tid;

			$params = [
				'subject' => cutstr($subject, intval($app->var['setting']['maxsubjectsize'])),
				'message' => $message,
				'content' => '',
				'contentType' => 'text',
				'contentEditor' => 'default',
				'special' => 0,
				'extramessage' => '',
				'bbcodeoff' => 0,
				'smileyoff' => 0,
				'htmlon' => 0,
				'parseurloff' => 0,
				'isanonymous' => 0,
			];
			$fail = static function($key) {
				throw new emailpost_rejection((string)$key);
			};

			if($tid) {
				$model = new \forum\model_post($tid);
				if(empty($model->thread) || intval($model->thread['special']) !== 0) {
					throw new emailpost_rejection('Email replies support normal threads only.');
				}
				$this->assertForumAccess($model->forum, $group);
				if(!empty($model->thread['readperm']) && intval($model->thread['readperm']) > intval($group['readaccess'])
					&& intval($model->thread['authorid']) !== intval($member['uid']) && empty($model->forum['ismoderator'])) {
					throw new emailpost_rejection('thread_nopermission');
				}
				$this->assertReplyPermission($model->forum, $group);
				if(checklowerlimit('reply', 0, 1, $model->forum['fid'], 1) !== true) {
					throw new emailpost_rejection('credits_policy_lowerlimit');
				}
				$model->showmessage = $fail;
				$container = new discuz_container($model);
				$container->attach_before_method('newreply', ['class' => 'forum\\extend_thread_filter', 'method' => 'before_newreply']);
				if(!empty($group['allowat'])) {
					$container->attach_before_method('newreply', ['class' => 'forum\\extend_thread_allowat', 'method' => 'before_newreply']);
					$container->attach_after_method('newreply', ['class' => 'forum\\extend_thread_allowat', 'method' => 'after_newreply']);
				}
				$container->attach_after_method('newreply', ['class' => 'forum\\extend_thread_image', 'method' => 'after_newreply']);
				$container->attach_after_method('newreply', ['class' => 'forum\\extend_thread_filter', 'method' => 'after_newreply']);
				$params['timestamp'] = TIMESTAMP;
				$params['modstatus'] = [4 => 1, 9 => 1];
				$container->newreply($params);
				return ['fid' => $model->forum['fid'], 'tid' => $model->thread['tid'], 'pid' => $model->pid];
			}

			$model = new \forum\model_thread($fid);
			if(empty($model->forum['fid']) || $model->forum['type'] === 'group') {
				throw new emailpost_rejection('forum_nonexistence');
			}
			$this->assertForumAccess($model->forum, $group);
			$this->assertThreadPermission($model->forum, $group);
			if(checklowerlimit('post', 0, 1, $model->forum['fid'], 1) !== true) {
				throw new emailpost_rejection('credits_policy_lowerlimit');
			}
			$model->showmessage = $fail;
			$container = new discuz_container($model);
			$container->attach_before_method('newthread', ['class' => 'forum\\extend_thread_allowat', 'method' => 'before_newthread']);
			$container->attach_after_method('newthread', ['class' => 'forum\\extend_thread_allowat', 'method' => 'after_newthread']);
			$container->attach_after_method('newthread', ['class' => 'forum\\extend_thread_image', 'method' => 'after_newthread']);
			$params += [
				'typeid' => 0,
				'sortid' => 0,
				'publishdate' => TIMESTAMP,
				'save' => 0,
				'readperm' => 0,
				'price' => 0,
				'tags' => '',
				'pstatus' => self::EMAIL_POST_STATUS,
			];
			$container->newthread($params);
			return ['fid' => $model->forum['fid'], 'tid' => $model->tid, 'pid' => $model->pid];
		} finally {
			foreach($saved as $key => $value) {
				$app->var[$key] = $value;
			}
		}
	}

	private function assertThreadPermission(array $forum, array $group) {
		$allow = ($forum['allowpost'] ?? '') != -1 && (
			(empty($forum['postperm']) && !empty($group['allowpost']))
			|| (!empty($forum['postperm']) && forumperm($forum['postperm']))
			|| (($forum['allowpost'] ?? '') == 1 && !empty($group['allowpost']))
		);
		if(!$allow) {
			throw new emailpost_rejection('postperm_none_nopermission');
		}
	}

	private function assertForumAccess(array $forum, array $group) {
		if(!empty($forum['password']) || !empty($forum['formulaperm'])) {
			throw new emailpost_rejection('Password- and formula-protected forums do not accept email posts.');
		}
		if((!empty($forum['simple']) && (intval($forum['simple']) & 1)) || !empty($forum['redirect'])) {
			throw new emailpost_rejection('forum_disablepost');
		}
		if(empty($forum['allowview'])) {
			if(empty($forum['viewperm']) && empty($group['readaccess'])) {
				throw new emailpost_rejection('group_nopermission');
			}
			if(!empty($forum['viewperm']) && !forumperm($forum['viewperm'])) {
				throw new emailpost_rejection('viewperm_none_nopermission');
			}
		} elseif(intval($forum['allowview']) === -1) {
			throw new emailpost_rejection('forum_access_view_disallow');
		}
		if(periodscheck('postbanperiods', 0)) {
			throw new emailpost_rejection('period_nopermission');
		}
		$setting = getglobal('setting');
		if(in_array(intval(getglobal('adminid')), [0, -1], true) && !empty($setting['newbiespan'])
			&& (!getuserprofile('lastpost') || TIMESTAMP - getuserprofile('lastpost') < intval($setting['newbiespan']) * 60)
			&& TIMESTAMP - intval(getglobal('member/regdate')) < intval($setting['newbiespan']) * 60) {
			throw new emailpost_rejection('post_newbie_span');
		}
	}

	private function assertReplyPermission(array $forum, array $group) {
		$allow = ($forum['allowreply'] ?? '') != -1 && (
			(empty($forum['replyperm']) && !empty($group['allowreply']))
			|| (!empty($forum['replyperm']) && forumperm($forum['replyperm']))
			|| (($forum['allowreply'] ?? '') == 1 && !empty($group['allowreply']))
		);
		if(!$allow) {
			throw new emailpost_rejection('replyperm_none_nopermission');
		}
	}

	protected function messageBody(int $mailuid) {
		$structure = $this->imapFetchStructure($this->mailbox, $mailuid, FT_UID);
		$plain = $this->findBodyPart($mailuid, $structure, '', 'PLAIN');
		if($plain !== null) {
			return dhtmlspecialchars(trim($plain));
		}
		$html = $this->findBodyPart($mailuid, $structure, '', 'HTML');
		if($html === null) {
			return '';
		}
		require_once libfile('function/editor');
		return trim(html2bbcode($html));
	}

	private function findBodyPart(int $mailuid, $part, string $number, string $subtype) {
		if(!$part) {
			return null;
		}
		$isAttachment = !empty($part->disposition) && in_array(strtoupper($part->disposition), ['ATTACHMENT', 'INLINE'], true)
			&& (!empty($part->dparameters) || !empty($part->parameters));
		if(intval($part->type) === 0 && strtoupper($part->subtype ?? '') === $subtype && !$isAttachment) {
			$body = $number === ''
				? $this->imapBody($this->mailbox, $mailuid, FT_UID | FT_PEEK)
				: $this->imapFetchBody($this->mailbox, $mailuid, $number, FT_UID | FT_PEEK);
			$body = $this->decodeBody($body ?: '', intval($part->encoding));
			$charset = $this->partParameter($part, 'charset');
			return $charset && strcasecmp($charset, 'UTF-8') !== 0 ? diconv($body, $charset, 'UTF-8') : $body;
		}
		foreach($part->parts ?? [] as $index => $child) {
			$childNumber = $number === '' ? (string)($index + 1) : $number.'.'.($index + 1);
			if(($body = $this->findBodyPart($mailuid, $child, $childNumber, $subtype)) !== null) {
				return $body;
			}
		}
		return null;
	}

	private function decodeBody(string $body, int $encoding) {
		return match($encoding) {
			3 => base64_decode($body, true) ?: '',
			4 => quoted_printable_decode($body),
			default => $body,
		};
	}

	private function partParameter($part, string $name) {
		foreach(array_merge($part->parameters ?? [], $part->dparameters ?? []) as $parameter) {
			if(strcasecmp($parameter->attribute ?? '', $name) === 0) {
				return $parameter->value ?? '';
			}
		}
		return '';
	}

	private function senderAddress($headers) {
		$from = $headers->from[0] ?? null;
		return $from && !empty($from->mailbox) && !empty($from->host)
			? mb_strtolower($from->mailbox.'@'.$from->host, 'UTF-8')
			: '';
	}

	private function decodeHeader(string $value) {
		$result = '';
		foreach(imap_mime_header_decode($value) ?: [] as $part) {
			$text = $part->text ?? '';
			$charset = $part->charset ?? 'default';
			$result .= $charset && strcasecmp($charset, 'default') !== 0 && strcasecmp($charset, 'UTF-8') !== 0
				? diconv($text, $charset, 'UTF-8')
				: $text;
		}
		return $result;
	}

	private function firstMessageId(string $headers, string $name) {
		$ids = $this->messageIdsForHeader($headers, $name);
		return $ids[0] ?? '';
	}

	private function messageIdsForHeader(string $headers, string $name) {
		$values = $this->headerValues($headers, $name);
		if(!$values) {
			return [];
		}
		preg_match_all('/<[^<>\s]+>/', implode(' ', $values), $ids);
		return array_values(array_unique($ids[0] ?? []));
	}

	private function headerValues(string $headers, string $name) {
		$unfolded = preg_replace("/\r?\n[\t ]+/", ' ', $headers);
		preg_match_all('/^'.preg_quote($name, '/').':\s*([^\r\n]*)/im', $unfolded, $matches);
		return $matches[1] ?? [];
	}

	// Kept as a narrow boundary so integration tests can supply a fixture mailbox.
	protected function imapOpen(string $mailbox, string $username, string $password) {
		return imap_open($mailbox, $username, $password);
	}

	protected function imapSearch($mailbox, string $criteria, int $flags) {
		return imap_search($mailbox, $criteria, $flags);
	}

	protected function imapClose($mailbox, int $flags) {
		return imap_close($mailbox, $flags);
	}

	protected function imapFetchHeader($mailbox, int $uid, int $flags) {
		return imap_fetchheader($mailbox, $uid, $flags);
	}

	protected function imapHeaderInfo($mailbox, int $messageNumber) {
		return imap_headerinfo($mailbox, $messageNumber);
	}

	protected function imapMsgNo($mailbox, int $uid) {
		return imap_msgno($mailbox, $uid);
	}

	protected function imapSetFlagFull($mailbox, string $sequence, string $flag, int $options) {
		return imap_setflag_full($mailbox, $sequence, $flag, $options);
	}

	protected function imapDelete($mailbox, string $sequence, int $options) {
		return imap_delete($mailbox, $sequence, $options);
	}

	protected function imapFetchStructure($mailbox, int $uid, int $flags) {
		return imap_fetchstructure($mailbox, $uid, $flags);
	}

	protected function imapBody($mailbox, int $uid, int $flags) {
		return imap_body($mailbox, $uid, $flags);
	}

	protected function imapFetchBody($mailbox, int $uid, string $section, int $flags) {
		return imap_fetchbody($mailbox, $uid, $section, $flags);
	}
}
