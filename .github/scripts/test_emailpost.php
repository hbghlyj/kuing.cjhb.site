<?php

if(PHP_SAPI !== 'cli') {
	exit("This test must run from the command line.\n");
}

define('IN_DISCUZ', true);
define('DISCUZ_ROOT', dirname(__DIR__, 2).'/');
require DISCUZ_ROOT.'source/class/class_emailpost.php';

function emailpost_assert($condition, string $message): void {
	if(!$condition) {
		throw new RuntimeException($message);
	}
}

class emailpost_fixture_mailbox extends emailpost {
	private array $messages;
	public array $seen = [];
	public array $deleted = [];

	public function __construct(array $config, array $messages) {
		parent::__construct($config);
		$this->messages = $messages;
	}

	public function consumeFixtures(): void {
		$this->consume();
	}

	protected function imapOpen(string $mailbox, string $username, string $password) {
		return (object)['fixture' => true];
	}

	protected function imapSearch($mailbox, string $criteria, int $flags) {
		return array_keys($this->messages);
	}

	protected function imapClose($mailbox, int $flags) {
		return true;
	}

	protected function imapFetchHeader($mailbox, int $uid, int $flags) {
		return $this->messages[$uid]['headers'];
	}

	protected function imapHeaderInfo($mailbox, int $messageNumber) {
		$raw = $this->messages[$messageNumber]['headers'];
		preg_match('/^From:\s*.*?<([^>]+)>/im', $raw, $from);
		if(empty($from[1])) {
			preg_match('/^From:\s*([^\s\r\n]+)/im', $raw, $from);
		}
		[$mailboxPart, $host] = array_pad(explode('@', trim($from[1] ?? ''), 2), 2, '');
		preg_match('/^Subject:\s*(.*)$/im', $raw, $subject);
		return (object)[
			'from' => [(object)['mailbox' => $mailboxPart, 'host' => $host]],
			'subject' => trim($subject[1] ?? ''),
		];
	}

	protected function imapMsgNo($mailbox, int $uid) {
		return $uid;
	}

	protected function imapSetFlagFull($mailbox, string $sequence, string $flag, int $options) {
		$this->seen[] = intval($sequence);
		return true;
	}

	protected function imapDelete($mailbox, string $sequence, int $options) {
		$this->deleted[] = intval($sequence);
		return true;
	}

	protected function imapFetchStructure($mailbox, int $uid, int $flags) {
		return $this->messages[$uid]['structure'];
	}

	protected function imapBody($mailbox, int $uid, int $flags) {
		return $this->messages[$uid]['bodies'][''] ?? '';
	}

	protected function imapFetchBody($mailbox, int $uid, string $section, int $flags) {
		return $this->messages[$uid]['bodies'][$section] ?? '';
	}
}

function emailpost_test_config(): array {
	return [
		'enabled' => true,
		'mailbox' => '{fixture}INBOX',
		'username' => 'fixture',
		'password' => 'fixture',
		'recipient_domain' => 'forum.example',
		'trusted_authserv_id' => 'mx.example',
		'require_dmarc' => true,
		'max_messages' => 20,
	];
}
$config = emailpost_test_config();

// Keep parser/security coverage runnable without a configured local database.
$parser = new emailpost($config);
$call = static function($method, ...$arguments) use ($parser) {
	$reflection = new ReflectionMethod($parser, $method);
	return $reflection->invoke($parser, ...$arguments);
};
$headers = "To: Forum <forum+6@forum.example>\r\n"
	."Message-ID: <message-2@example.net>\r\n"
	."In-Reply-To: <message-1@example.net>\r\n"
	."References: <root@example.net>\r\n\t<message-1@example.net>\r\n"
	."Authentication-Results: mx.example; dkim=pass; dmarc=pass\r\n";
emailpost_assert($call('forumIdFromRecipient', $headers) === 6, 'forum+FID routing failed.');
emailpost_assert($call('messageIdsForHeader', $headers, 'References') === ['<root@example.net>', '<message-1@example.net>'], 'Folded References parsing failed.');
$call('validateDmarc', $headers);
try {
	$call('forumIdFromRecipient', "To: thread+42@forum.example\r\nFrom: forum+9@forum.example\r\n");
	throw new RuntimeException('thread+TID or From routing was accepted.');
} catch(emailpost_rejection) {
}
try {
	$call('validateDmarc', "Authentication-Results: attacker.example; dmarc=pass\r\n");
	throw new RuntimeException('Untrusted Authentication-Results was accepted.');
} catch(emailpost_rejection) {
}

if(!is_file(DISCUZ_ROOT.'config/config_global.php')) {
	echo "Email posting parser tests passed (database integration skipped: config/config_global.php is absent).\n";
	exit;
}

require DISCUZ_ROOT.'source/class/class_core.php';
$discuz = C::app();
$discuz->init();
$config = emailpost_test_config();

emailpost_assert(DB::result_first('SELECT COUNT(*) FROM %t', ['forum_emailpost']) !== false, 'forum_emailpost schema is missing.');
DB::update('common_member', ['email' => 'admin@admin.com', 'emailstatus' => 1, 'freeze' => 0], 'uid=1');
require_once libfile('function/forum');

$token = 'emailpost-fixture-'.bin2hex(random_bytes(6));
$rootId = '<'.$token.'-root@example.net>';
$replyId = '<'.$token.'-reply@example.net>';
$referenceId = '<'.$token.'-reference@example.net>';
$htmlId = '<'.$token.'-html@example.net>';
$attachmentId = '<'.$token.'-attachment@example.net>';
$missingIdHeaders = "To: forum+2@forum.example\r\nFrom: Admin <admin@admin.com>\r\nSubject: {$token} missing id\r\nAuthentication-Results: mx.example; dmarc=pass\r\n";
$base = "From: Admin <admin@admin.com>\r\nAuthentication-Results: mx.example; dmarc=pass\r\n";
$plain = static fn(int $encoding = 0) => (object)['type' => 0, 'subtype' => 'PLAIN', 'encoding' => $encoding];
$messages = [
	101 => [
		'headers' => "To: forum+2@forum.example\r\n{$base}Message-ID: {$rootId}\r\nSubject: {$token} root\r\n",
		'structure' => $plain(4),
		'bodies' => ['' => 'Root=20email=20body=2E'],
	],
	102 => [
		'headers' => "{$base}Message-ID: {$replyId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\n",
		'structure' => $plain(),
		'bodies' => ['' => 'Direct reply fixture body.'],
	],
	103 => [
		'headers' => "{$base}Message-ID: {$referenceId}\r\nReferences: <unrelated@example.net> {$rootId}\r\nSubject: Re: {$token} root\r\n",
		'structure' => $plain(),
		'bodies' => ['' => 'References fallback fixture body.'],
	],
	104 => [
		'headers' => "{$base}Message-ID: {$replyId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\n",
		'structure' => $plain(),
		'bodies' => ['' => 'Duplicate message must not post.'],
	],
	105 => [
		'headers' => "To: forum+2@forum.example\r\n{$base}Message-ID: <{$token}-auto@example.net>\r\nAuto-Submitted: auto-replied\r\nSubject: {$token} automatic\r\n",
		'structure' => $plain(),
		'bodies' => ['' => 'Automatic response body.'],
	],
	106 => [
		'headers' => "To: forum+2@forum.example\r\nFrom: Unknown <unknown@example.net>\r\nAuthentication-Results: mx.example; dmarc=pass\r\nMessage-ID: <{$token}-unknown@example.net>\r\nSubject: {$token} unknown\r\n",
		'structure' => $plain(),
		'bodies' => ['' => 'Unknown sender body.'],
	],
	107 => [
		'headers' => "To: forum+2@forum.example\r\nFrom: Admin <admin@admin.com>\r\nAuthentication-Results: attacker.example; dmarc=pass\r\nMessage-ID: <{$token}-dmarc@example.net>\r\nSubject: {$token} dmarc\r\n",
		'structure' => $plain(),
		'bodies' => ['' => 'Untrusted DMARC body.'],
	],
	108 => [
		'headers' => "{$base}Message-ID: {$htmlId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\n",
		'structure' => (object)['type' => 0, 'subtype' => 'HTML', 'encoding' => 0],
		'bodies' => ['' => '<p>HTML fixture <strong>body</strong>.</p>'],
	],
	109 => [
		'headers' => "{$base}Message-ID: {$attachmentId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\n",
		'structure' => (object)['type' => 1, 'subtype' => 'MIXED', 'parts' => [
			$plain(),
			(object)['type' => 0, 'subtype' => 'PLAIN', 'encoding' => 0, 'disposition' => 'ATTACHMENT', 'dparameters' => [(object)['attribute' => 'filename', 'value' => 'ignored.txt']]],
		]],
		'bodies' => ['1' => 'Multipart body; the attachment must be ignored.', '2' => 'not an attachment upload'],
	],
	110 => [
		'headers' => $missingIdHeaders,
		'structure' => $plain(),
		'bodies' => ['' => 'A message without a Message-ID still has a stable dedupe key.'],
	],
];

$fixture = new emailpost_fixture_mailbox($config, $messages);
$fixture->consumeFixtures();
emailpost_assert(count($fixture->seen) === count($messages), 'Every fixture message must be marked seen.');

$rowFor = static fn(string $id) => table_forum_emailpost::t()->fetch_by_message_id($id);
$root = $rowFor($rootId);
$reply = $rowFor($replyId);
$reference = $rowFor($referenceId);
$html = $rowFor($htmlId);
$attachment = $rowFor($attachmentId);
emailpost_assert($root && intval($root['status']) === 1 && intval($root['fid']) === 2 && intval($root['tid']) > 0 && intval($root['pid']) > 0, 'New-thread email was not persisted as a post.');
emailpost_assert($reply && intval($reply['status']) === 1 && intval($reply['tid']) === intval($root['tid']) && $reply['parentkey'] === hash('sha256', $rootId), 'In-Reply-To did not create a mapped reply.');
emailpost_assert($reference && intval($reference['status']) === 1 && intval($reference['tid']) === intval($root['tid']) && $reference['parentkey'] === hash('sha256', $rootId), 'References fallback did not create a mapped reply.');
emailpost_assert($html && intval($html['status']) === 1 && intval($html['tid']) === intval($root['tid']), 'HTML-only email was not converted into a reply.');
emailpost_assert($attachment && intval($attachment['status']) === 1 && intval($attachment['tid']) === intval($root['tid']), 'Multipart email was not posted.');

$post = get_post_by_pid(intval($root['pid']));
$htmlPost = get_post_by_pid(intval($html['pid']));
$attachmentPost = get_post_by_pid(intval($attachment['pid']));
emailpost_assert(str_contains($post['message'], 'Root email body.'), 'Quoted-printable plain-text body was not decoded.');
emailpost_assert(str_contains($htmlPost['message'], 'HTML fixture') && str_contains($htmlPost['message'], 'body'), 'HTML body was not converted.');
emailpost_assert(empty($attachmentPost['attachment']), 'Email attachment was imported as a forum attachment.');
emailpost_assert(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE tid=%d', ['forum_post', $root['tid']])) === 5, 'Duplicate or rejected email created an unexpected post.');

foreach(['<'.$token.'-auto@example.net>', '<'.$token.'-unknown@example.net>', '<'.$token.'-dmarc@example.net>'] as $rejectedId) {
	$row = $rowFor($rejectedId);
	emailpost_assert($row && intval($row['status']) === -1, "Rejected message {$rejectedId} was not recorded as rejected.");
}
$missingId = '<missing-'.hash('sha256', $missingIdHeaders).'@forum.example>';
$missing = $rowFor($missingId);
emailpost_assert($missing && intval($missing['status']) === 1 && intval($missing['tid']) > 0, 'Message without Message-ID was not deterministically imported.');

echo "Email posting integration tests passed.\n";
