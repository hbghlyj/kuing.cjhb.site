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

	public static function importRaw(string $raw, string $recipient = '') {
		$config = self::loadConfig();
		if(empty($config['enabled'])) {
			return;
		}
		(new self($config))->consumeRaw($raw, $recipient);
	}

	private static function loadConfig(): array {
		$default = require DISCUZ_ROOT.'config/config_emailpost_default.php';
		$localfile = DISCUZ_ROOT.'config/config_emailpost.php';
		$local = is_file($localfile) ? require $localfile : [];
		return array_merge($default, is_array($local) ? $local : []);
	}

	public static function config(): array {
		return self::loadConfig();
	}

	public function __construct(array $config) {
		$this->config = $config;
	}

	protected function consumeRaw(string $raw, string $recipient = '') {
		$lockfile = DISCUZ_ROOT.'data/sysdata/emailpost.lock';
		$lock = fopen($lockfile, 'c');
		if(!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
			return;
		}

		try {
			$this->processMessage($raw, $recipient);
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	protected function processMessage(string $raw, string $recipient = '') {
		[$headers, $body] = $this->splitMessage($raw);
		$messageid = $this->firstMessageId($headers, 'Message-ID');
		if(!$messageid) {
			$messageid = '<missing-'.hash('sha256', $headers).'@'.strtolower($this->config['recipient_domain']).'>';
		}
		$messagekey = hash('sha256', $messageid);
		if(table_forum_emailpost::t()->fetch($messagekey)) {
			return;
		}

		$sender = $this->senderAddress($headers);
		$reserved = table_forum_emailpost::t()->reserve([
			'messagekey' => $messagekey,
			'mailuid' => 0,
			'messageid' => cutstr($messageid, 255),
			'sender' => cutstr($sender, 255),
			'uid' => 0,
			'action' => 'thread',
			'dateline' => TIMESTAMP,
		]);
		if(!$reserved) {
			return;
		}

		try {
			$this->validateAutomatedHeaders($headers);
			$this->validateDmarc($headers);
			$member = $this->memberForSender($sender);
			table_forum_emailpost::t()->update($messagekey, ['uid' => $member['uid']]);

			$parent = $this->findParent($headers);
			if($parent) {
				$fid = intval($parent['fid']);
				$tid = intval($parent['tid']);
				$action = 'reply';
			} else {
				$fid = $this->forumIdFromRecipient($headers, $recipient);
				$tid = 0;
				$action = 'thread';
			}

			$subject = dhtmlspecialchars(trim($this->decodeHeader($this->headerValue($headers, 'Subject'))));
			$message = $this->messageBody($raw);
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
			runlog('emailpost', 'Accepted '.$messageid.' as pid '.$result['pid']);
		} catch(emailpost_rejection $e) {
			table_forum_emailpost::t()->reject($messagekey, $e->getMessage());
			runlog('emailpost', 'Rejected '.$messageid.': '.$e->getMessage());
		} catch(Throwable $e) {
			table_forum_emailpost::t()->delete($messagekey);
			runlog('error', 'Email posting failed for '.$messageid.': '.$e->getMessage());
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

	private function forumIdFromRecipient(string $headers, string $recipient = '') {
		$domain = preg_quote(strtolower(trim($this->config['recipient_domain'])), '/');
		$recipients = implode(' ', array_merge(
			$this->headerValues($headers, 'To'),
			$this->headerValues($headers, 'Delivered-To'),
			$this->headerValues($headers, 'X-Original-To'),
			$this->headerValues($headers, 'Envelope-To'),
			$recipient !== '' ? [$recipient] : []
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
		if(!empty($forum['password'])) {
			throw new emailpost_rejection('Password- and formula-protected forums do not accept email posts.');
		}
		$formula = is_string($forum['formulaperm'] ?? null) ? dunserialize($forum['formulaperm']) : [];
		if(!is_array($formula)) {
			$formula = [];
		}
		if(!empty($formula['medal']) || !empty($formula['users']) || !empty($formula['viewtype'])
			|| !empty(trim((string)($formula[0] ?? ''))) || !empty(trim((string)($formula[1] ?? '')))) {
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

	private function messageBody(string $raw) {
		$plain = $this->findBodyPart($raw, 'PLAIN');
		if($plain !== null) {
			return dhtmlspecialchars(trim($plain));
		}
		$html = $this->findBodyPart($raw, 'HTML');
		if($html === null) {
			return '';
		}
		require_once libfile('function/editor');
		return trim(html2bbcode($html));
	}

	private function findBodyPart(string $raw, string $subtype) {
		[$headers, $body] = $this->splitMessage($raw);
		if(!preg_match("/\r?\n\r?\n/", $raw)) {
			$body = $headers;
			$headers = '';
		}
		$contentType = $this->headerValue($headers, 'Content-Type');
		$type = $contentType !== '' ? strtolower(trim(explode(';', $contentType, 2)[0])) : 'text/plain';
		$boundary = '';
		if(preg_match('/boundary\s*=\s*"?([^";\s]+)"?/i', $contentType, $matches)) {
			$boundary = $matches[1];
		}
		$disposition = strtolower($this->headerValue($headers, 'Content-Disposition'));
		$filename = '';
		if(preg_match('/filename\s*=\s*"?([^";\s]+)"?/i', $contentType.'; '.$this->headerValue($headers, 'Content-Disposition'), $matches)) {
			$filename = $matches[1];
		}
		$isAttachment = str_contains($disposition, 'attachment') || $filename !== '';

		if($type !== '' && str_starts_with($type, 'multipart/')) {
			if($boundary === '' || $body === '') {
				return null;
			}
			$parts = preg_split('/--'.preg_quote($boundary, '/').'-{0,2}[ \t]*(?:\r\n|\r|\n|$)/', $body);
			foreach($parts as $part) {
				if(trim($part) === '') {
					continue;
				}
				if(($found = $this->findBodyPart($part, $subtype)) !== null) {
					return $found;
				}
			}
			return null;
		}

		if($isAttachment || $type !== 'text/'.strtolower($subtype)) {
			return null;
		}
		$encoding = strtolower(trim(explode(';', $this->headerValue($headers, 'Content-Transfer-Encoding'), 2)[0]));
		$text = match($encoding) {
			'base64' => base64_decode($body, true) ?: '',
			'quoted-printable' => quoted_printable_decode($body),
			default => $body,
		};
		if(preg_match('/charset\s*=\s*"?([^";\s]+)"?/i', $contentType, $matches)) {
			$charset = trim($matches[1]);
			if(strcasecmp($charset, 'UTF-8') !== 0) {
				$text = diconv($text, $charset, 'UTF-8');
			}
		}
		return $text;
	}

	private function splitMessage(string $raw): array {
		$parts = preg_split("/\r?\n\r?\n/", $raw, 2);
		return [$parts[0] ?? '', $parts[1] ?? ''];
	}

	private function senderAddress(string $headers) {
		$value = $this->headerValue($headers, 'From');
		if($value === '') {
			return '';
		}
		preg_match('/<([^<>@\s]+@[^<>@\s]+)>|\b([^<>@\s]+@[^<>@\s]+)\b/', $value, $matches);
		$address = trim($matches[1] !== '' ? $matches[1] : ($matches[2] ?? ''));
		return $address !== '' ? mb_strtolower($address, 'UTF-8') : '';
	}

	private function decodeHeader(string $value) {
		$decoded = mb_decode_mimeheader($value);
		return $decoded !== false ? $decoded : $value;
	}

	private function headerValue(string $headers, string $name) {
		$values = $this->headerValues($headers, $name);
		return $values[0] ?? '';
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
}
