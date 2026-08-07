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

	private const EMAIL_POST_STATUS = (1 << 3) | (1 << 8);
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

	public static function threadHasEmailCopy(int $tid): bool {
		if($tid <= 0) {
			return false;
		}
		return (bool)DB::result_first('SELECT 1 FROM %t WHERE tid=%d AND action=%s AND status=1 LIMIT 1', ['forum_emailpost', $tid, 'thread']);
	}

	public static function authorReplyNotice(array $thread, array $reply): array {
		if(!self::threadHasEmailCopy(intval($thread['tid'] ?? 0))) {
			return [];
		}
		return [
			[self::class, 'sendReplyCopy'],
			[$thread, $reply],
		];
	}

	public static function sendReplyCopy(array $thread, array $reply): bool {
		$config = self::loadConfig();
		$domain = strtolower(trim($config['recipient_domain'] ?? ''));
		if(empty($config['enabled']) || $domain === '') {
			return false;
		}
		$tid = intval($thread['tid'] ?? 0);
		if($tid <= 0) {
			return false;
		}
		$thread = table_forum_thread::t()->fetch($tid);
		if(!$thread) {
			return false;
		}
		$member = table_common_member::t()->fetch(intval($thread['authorid']));
		$email = $member['email'] ?? '';
		if(!$member || empty($member['emailstatus']) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		global $_G;
		$fid = intval($thread['fid']);
		$subject = htmlspecialchars_decode(trim((string)$thread['subject']), ENT_QUOTES);
		$replier = trim((string)($reply['author'] ?? ''));
		$bodytext = trim((string)($reply['message'] ?? ''));
		$bodytext = preg_replace('/\[attach\]\d+\[\/attach\]/is', '', $bodytext);
		require_once libfile('function/discuzcode');
		$bodytext = discuzcode(
			$bodytext,
			0, 0, 0,
			1, 1, 1,
			0,
			0, 0,
			intval($reply['authorid'] ?? 0),
			1,
			intval($reply['pid'] ?? 0),
			0, 0, 0, 0
		);
		$copy = $bodytext
			.'<hr>'
			.'<p style="color:#888;">'.lang('forum/template', 'emailpost_reply_copy_email_footer').'</p>';
		require_once libfile('function/mail');
		$from = ($replier !== '' ? $replier : $_G['setting']['sitename']).' <forum+'.$fid.'@'.$domain.'>';
		$extraheaders = [
			'Message-ID: <thread-'.$tid.'@'.$domain.'>',
			'Reply-To: forum+'.$fid.'@'.$domain,
			'X-Emailpost-Reply-Copy: 1',
		];
		$original = trim((string)DB::result_first('SELECT messageid FROM %t WHERE tid=%d AND action=%s AND status=1 LIMIT 1', ['forum_emailpost', $tid, 'thread']));
		if($original !== '') {
			$extraheaders[] = 'In-Reply-To: '.$original;
			$extraheaders[] = 'References: '.$original;
		}
		return sendmail($email, 'Re: '.$subject, $copy, $from, $extraheaders);
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
		$messageid = cutstr($messageid, 255);
		if(table_forum_emailpost::t()->fetch($messageid)) {
			return;
		}

		$sender = $this->senderAddress($headers);
		$reserved = table_forum_emailpost::t()->reserve([
			'messageid' => $messageid,
			'mailuid' => 0,
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
			table_forum_emailpost::t()->update($messageid, ['uid' => $member['uid']]);

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
			if($action === 'reply' && preg_match('/^(re:\s*|回复[:：]\s*)/i', $subject)) {
				$subject = '';
			}
			$message = $this->messageBody($raw);
			if($message === '') {
				throw new emailpost_rejection('Email body is empty.');
			}
			if($action === 'thread' && $subject === '') {
				throw new emailpost_rejection('A subject is required for a new thread.');
			}

			table_forum_emailpost::t()->update($messageid, ['action' => $action, 'fid' => $fid, 'tid' => $tid]);
			$result = $this->postAsMember($member, $fid, $tid, $subject, $message, $raw);
			table_forum_emailpost::t()->complete(
				$messageid,
				$result['fid'],
				$result['tid'],
				$result['pid'],
				$parent['messageid'] ?? ''
			);
			runlog('emailpost', 'Accepted '.$messageid.' as pid '.$result['pid']);
		} catch(emailpost_rejection $e) {
			table_forum_emailpost::t()->reject($messageid, $e->getMessage());
			runlog('emailpost', 'Rejected '.$messageid.': '.$e->getMessage());
		} catch(Throwable $e) {
			table_forum_emailpost::t()->delete($messageid);
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
		$sender = mb_strtolower($sender, 'UTF-8');
		$member = table_common_member::t()->fetch_by_email($sender, 1);
		if(!$member) {
			$member = $this->memberByBoundEmail($sender);
		}
		if(!$member || empty($member['emailstatus'])) {
			throw new emailpost_rejection('The sender is not a verified forum member.');
		}
		if(!empty($member['freeze']) || in_array(intval($member['groupid']), [4, 5, 6], true)) {
			throw new emailpost_rejection('The member account cannot post.');
		}
		return $member;
	}

	private function memberByBoundEmail(string $sender) {
		$atype = $this->googleConnectAtype();
		if(!$atype) {
			return [];
		}
		$row = DB::fetch_first('SELECT uid FROM %t WHERE LOWER(bindname)=%s AND atype=%d', ['common_member_account', $sender, $atype]);
		if(!$row) {
			return [];
		}
		$member = table_common_member::t()->fetch(intval($row['uid']));
		return $member ? $member : [];
	}

	private function googleConnectAtype() {
		global $_G;
		if(!empty($_G['setting']['account_plugin_atypes']['googleconnect'])) {
			return intval($_G['setting']['account_plugin_atypes']['googleconnect']);
		}
		$row = DB::fetch_first('SELECT svalue FROM %t WHERE skey=%s', ['common_setting', 'account_plugin_atypes']);
		if(!$row) {
			return 0;
		}
		$map = @dunserialize($row['svalue']);
		return is_array($map) ? intval($map['googleconnect'] ?? 0) : 0;
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
		$domain = preg_quote(strtolower(trim($this->config['recipient_domain'])), '/');
		$tid = 0;
		if(preg_match('/^<thread-(\d+)@'.$domain.'>$/i', $messageid, $match)) {
			$tid = intval($match[1]);
		} else {
			$row = table_forum_emailpost::t()->fetch(cutstr($messageid, 255));
			if(!$row || intval($row['status']) !== 1 || intval($row['tid']) <= 0) {
				return [];
			}
			$tid = intval($row['tid']);
		}
		$thread = table_forum_thread::t()->fetch($tid);
		if(!$thread || intval($thread['displayorder']) < 0 || intval($thread['isgroup'])) {
			return [];
		}
		return [
			'messageid' => cutstr($messageid, 255),
			'fid' => intval($thread['fid']),
			'tid' => intval($thread['tid']),
			'pid' => 0,
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

	private function postAsMember(array $member, int $fid, int $tid, string $subject, string $message, string $raw = '') {
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
				$result = ['fid' => $model->forum['fid'], 'tid' => $model->thread['tid'], 'pid' => $model->pid];
				$cidToAid = $this->saveAttachments($member, $group, $result['fid'], $result['tid'], $result['pid'], $raw);
				$this->applyInlineAttachments($result['tid'], $result['pid'], $message, $cidToAid);
				return $result;
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
			$result = ['fid' => $model->forum['fid'], 'tid' => $model->tid, 'pid' => $model->pid];
			$cidToAid = $this->saveAttachments($member, $group, $result['fid'], $result['tid'], $result['pid'], $raw);
			$this->applyInlineAttachments($result['tid'], $result['pid'], $message, $cidToAid);
			return $result;
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
		if($plain !== null && !$this->hasAlternativePart($raw)) {
			return trim($plain);
		}
		$html = $this->findBodyPart($raw, 'HTML');
		if($html === null) {
			return $plain !== null ? trim($plain) : '';
		}
		return $this->htmlToText($html);
	}

	private function htmlToText(string $html): string {
		$text = preg_replace('/<br\s*\/?>/i', "\n", $html);
		$text = preg_replace('/<\/(p|div|li|tr|table|blockquote|h[1-6])>/i', "\n", $text);
		$text = preg_replace('/<img\b[^>]*\bsrc\s*=\s*["\']cid:([^"\'>]+)["\'][^>]*>/i', '[[cid:$1]]', $text);
		$text = preg_replace('/<[^>]+>/', '', $text);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = str_replace("\xC2\xA0", ' ', $text);
		$text = preg_replace('/[ \t]+(?=\r?\n)/', '', $text);
		$text = preg_replace('/\n{3,}/', "\n\n", $text);
		return trim($text);
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

	private function hasAlternativePart(string $raw): bool {
		[$headers, $body] = $this->splitMessage($raw);
		if(!preg_match("/\r?\n\r?\n/", $raw)) {
			$body = $headers;
			$headers = '';
		}
		$contentType = $this->headerValue($headers, 'Content-Type');
		$type = $contentType !== '' ? strtolower(trim(explode(';', $contentType, 2)[0])) : 'text/plain';
		if($type === 'multipart/alternative') {
			return true;
		}
		if(!str_starts_with($type, 'multipart/')) {
			return false;
		}
		$boundary = '';
		if(preg_match('/boundary\s*=\s*"?([^";\s]+)"?/i', $contentType, $matches)) {
			$boundary = $matches[1];
		}
		if($boundary === '' || $body === '') {
			return false;
		}
		$parts = preg_split('/--'.preg_quote($boundary, '/').'-{0,2}[ \t]*(?:\r\n|\r|\n|$)/', $body);
		foreach($parts as $part) {
			if(trim($part) === '') {
				continue;
			}
			if($this->hasAlternativePart($part)) {
				return true;
			}
		}
		return false;
	}

	private function findAttachments(string $raw, array $referencedCids = []): array {
		[$headers, $body] = $this->splitMessage($raw);
		if(!preg_match("/\r?\n\r?\n/", $raw)) {
			$body = $headers;
			$headers = '';
		}
		if(!$referencedCids) {
			$referencedCids = $this->referencedCids($raw);
		}
		$contentType = $this->headerValue($headers, 'Content-Type');
		$type = $contentType !== '' ? strtolower(trim(explode(';', $contentType, 2)[0])) : 'text/plain';
		$boundary = '';
		if(preg_match('/boundary\s*=\s*"?([^";\s]+)"?/i', $contentType, $matches)) {
			$boundary = $matches[1];
		}
		$filename = $this->partFilename($headers, $contentType);
		$disposition = strtolower($this->headerValue($headers, 'Content-Disposition'));
		$cid = $this->normalizeCid($this->headerValue($headers, 'Content-ID'));
		$isInlineImage = $cid !== '' && in_array($cid, $referencedCids, true);

		if($type !== '' && str_starts_with($type, 'multipart/')) {
			if($boundary === '' || $body === '') {
				return [];
			}
			$attachments = [];
			$parts = preg_split('/--'.preg_quote($boundary, '/').'-{0,2}[ \t]*(?:\r\n|\r|\n|$)/', $body);
			foreach($parts as $part) {
				if(trim($part) === '') {
					continue;
				}
				foreach($this->findAttachments($part, $referencedCids) as $found) {
					$attachments[] = $found;
				}
			}
			return $attachments;
		}

		if($filename === '' && !str_contains($disposition, 'attachment') && !$isInlineImage) {
			return [];
		}
		$encoding = strtolower(trim(explode(';', $this->headerValue($headers, 'Content-Transfer-Encoding'), 2)[0]));
		$data = match($encoding) {
			'base64' => base64_decode($body, true) ?: '',
			'quoted-printable' => quoted_printable_decode($body),
			default => $body,
		};
		if($data === '') {
			return [];
		}
		$name = $filename !== '' ? $filename : ($isInlineImage ? $this->inlineImageName($cid, $type) : 'attachment');
		$result = ['name' => $name, 'data' => $data];
		if($isInlineImage) {
			$result['cid'] = $cid;
		}
		return [$result];
	}

	private function normalizeCid(string $cid): string {
		return strtolower(trim(trim($cid), '<>'));
	}

	private function referencedCids(string $raw): array {
		$html = $this->findBodyPart($raw, 'HTML');
		if($html === null) {
			return [];
		}
		preg_match_all('/\bsrc\s*=\s*["\']cid:([^"\'>]+)["\']/i', $html, $matches);
		$cids = [];
		foreach($matches[1] ?? [] as $cid) {
			$normalized = $this->normalizeCid($cid);
			if($normalized !== '') {
				$cids[] = $normalized;
			}
		}
		return array_values(array_unique($cids));
	}

	private function inlineImageName(string $cid, string $type): string {
		$basename = $this->cleanFilename(preg_replace('/@.*$/', '', $cid));
		$basename = preg_replace('/\.[a-z0-9]{1,10}$/', '', $basename);
		$ext = preg_replace('/^image\//', '', $type);
		if(!in_array($ext, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp'], true)) {
			$ext = 'bin';
		}
		if($ext === 'jpeg') {
			$ext = 'jpg';
		}
		return ($basename !== '' ? $basename : 'attachment').'.'.$ext;
	}

	private function partFilename(string $headers, string $contentType): string {
		if(preg_match('/filename\s*=\s*"?([^";\s]+)"?/i', $contentType.'; '.$this->headerValue($headers, 'Content-Disposition'), $matches)) {
			return $matches[1];
		}
		return '';
	}

	private function cleanFilename(string $filename): string {
		$filename = basename(str_replace(['\\', '/'], '/', $filename));
		$filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
		$filename = trim($filename, " \t.");
		if($filename === '' || $filename === '.' || $filename === '..') {
			$filename = 'attachment';
		}
		return $filename;
	}

	private function saveAttachments(array $member, array $group, int $fid, int $tid, int $pid, string $raw): array {
		global $_G;
		if($tid <= 0 || $pid <= 0) {
			return [];
		}
		try {
			$attachments = $this->findAttachments($raw, $this->referencedCids($raw));
		} catch(Throwable $e) {
			runlog('emailpost', 'Attachment parsing failed: '.$e->getMessage());
			return [];
		}
		if(!$attachments) {
			return [];
		}

		$savedglobals = [];
		foreach(['group', 'forum', 'fid', 'uid', 'username', 'groupid', 'adminid', 'member'] as $key) {
			$savedglobals[$key] = $_G[$key] ?? null;
		}
		$_G['group'] = $group;
		$_G['forum'] = ['fid' => $fid];
		$_G['fid'] = $fid;
		$_G['uid'] = $member['uid'];
		$_G['username'] = $member['username'];
		$_G['groupid'] = $member['groupid'];
		$_G['adminid'] = $member['adminid'];
		$_G['member'] = $member;

		if(!(($group['allowpostattach'] ?? false) || ($group['allowpostimage'] ?? false))
			|| (intval($group['maxattachnum']) && intval($group['maxattachnum']) <= intval(getuserprofile('todayattachs')))) {
			foreach($savedglobals as $key => $value) {
				if($value === null) {
					unset($_G[$key]);
				} else {
					$_G[$key] = $value;
				}
			}
			return [];
		}

		$attachnew = [];
		$cidToAid = [];
		try {
			foreach($attachments as $attachment) {
				try {
					if(($aid = $this->saveAttachment($attachment, $group, $fid)) > 0) {
						$attachnew[$aid] = ['readperm' => 0, 'price' => 0, 'description' => ''];
						if(!empty($attachment['cid'])) {
							$cidToAid[$attachment['cid']] = $aid;
						}
					}
				} catch(Throwable $e) {
					runlog('emailpost', 'Attachment skipped: '.$e->getMessage());
				}
			}
			if($attachnew) {
				try {
					if(!function_exists('updateattach')) {
						require_once libfile('function/post');
					}
					if(!function_exists('dunlink')) {
						require_once libfile('function/forum');
					}
					updateattach(0, $tid, $pid, $attachnew, [], intval($member['uid']));
				} catch(Throwable $e) {
					runlog('emailpost', 'Attachment finalization failed: '.$e->getMessage());
				}
			}
		} finally {
			foreach($savedglobals as $key => $value) {
				if($value === null) {
					unset($_G[$key]);
				} else {
					$_G[$key] = $value;
				}
			}
		}
		return $cidToAid;
	}

	private function applyInlineAttachments(int $tid, int $pid, string $message, array $cidToAid) {
		$final = $message;
		$changed = false;
		if(preg_match_all('/\[\[cid:([^\]]+)\]\]/', $final, $markers)) {
			foreach(array_unique($markers[1]) as $markerCid) {
				$marker = '[[cid:'.$markerCid.']]';
				$aid = $cidToAid[$this->normalizeCid($markerCid)] ?? 0;
				$final = str_replace($marker, $aid ? '[attach]'.$aid.'[/attach]' : '', $final);
				$changed = true;
			}
		}
		if($changed && $final !== $message) {
			DB::update(getposttablebytid($tid), ['message' => $final], 'pid='.intval($pid));
		}
	}

	private function saveAttachment(array $attachment, array $group, int $fid): int {
		global $_G;
		$name = $this->cleanFilename($attachment['name']);
		$ext = discuz_upload::fileext($name);
		if($ext === '' || $ext === 'none') {
			return 0;
		}
		if($group['attachextensions'] && !preg_match('/(^|\s|,)'.preg_quote($ext, '/').'($|\s|,)/i', $group['attachextensions'])) {
			return 0;
		}
		$size = strlen($attachment['data']);
		if(!$size) {
			return 0;
		}
		if($group['maxattachsize'] && $size > $group['maxattachsize']) {
			return 0;
		}
		loadcache('attachtype');
		if($fid && isset($_G['cache']['attachtype'][$fid][$ext])) {
			$maxsize = $_G['cache']['attachtype'][$fid][$ext];
			if(!$maxsize || $size > $maxsize) {
				return 0;
			}
		} elseif(isset($_G['cache']['attachtype'][0][$ext])) {
			$maxsize = $_G['cache']['attachtype'][0][$ext];
			if(!$maxsize || $size > $maxsize) {
				return 0;
			}
		}
		if($group['maxsizeperday'] && intval(getuserprofile('todayattachsize')) + $size >= intval($group['maxsizeperday'])) {
			return 0;
		}

		$tmpfile = tempnam(sys_get_temp_dir(), 'du');
		if($tmpfile === false) {
			return 0;
		}
		if(@file_put_contents($tmpfile, $attachment['data']) === false) {
			@unlink($tmpfile);
			return 0;
		}
		$_ENV['DFILES'][$tmpfile] = ['tmp_name' => $tmpfile];

		$upload = new discuz_upload();
		$upload->ftpcmd = ftpperm($ext, $size) ? 1 : 0;
		if(!$upload->init(['tmp_name' => $tmpfile, 'name' => $name, 'size' => $size], 'forum') || !$upload->save()) {
			@unlink($tmpfile);
			unset($_ENV['DFILES'][$tmpfile]);
			return 0;
		}

		$uid = intval($_G['uid']);
		updatemembercount($uid, ['todayattachs' => 1, 'todayattachsize' => $upload->attach['size'], 'attachsize' => $upload->attach['size']]);
		$aid = getattachnewaid($uid);
		table_forum_attachment_unused::t()->insert([
			'aid' => $aid,
			'dateline' => TIMESTAMP,
			'filename' => $upload->attach['name'],
			'filesize' => $upload->attach['size'],
			'attachment' => $upload->attach['attachment'],
			'isimage' => $upload->attach['isimage'],
			'uid' => $uid,
			'thumb' => 0,
			'remote' => $upload->remote,
			'width' => $upload->attach['imageinfo'][0] ?? 0,
			'height' => $upload->attach['imageinfo'][1] ?? 0,
		]);
		return $aid;
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
