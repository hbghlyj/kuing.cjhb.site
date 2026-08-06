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

function emailpost_test_config(): array {
	return [
		'enabled' => true,
		'recipient_domain' => 'forum.example',
		'trusted_authserv_id' => 'mx.example',
		'require_dmarc' => true,
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
require_once libfile('function/forum');

emailpost_assert(DB::result_first('SELECT COUNT(*) FROM %t', ['forum_emailpost']) !== false, 'forum_emailpost schema is missing.');
DB::update('common_member', ['email' => 'admin@admin.com', 'emailstatus' => 1, 'freeze' => 0], 'uid=1');

$token = 'emailpost-fixture-'.bin2hex(random_bytes(6));
$rootId = '<'.$token.'-root@example.net>';
$replyId = '<'.$token.'-reply@example.net>';
$referenceId = '<'.$token.'-reference@example.net>';
$htmlId = '<'.$token.'-html@example.net>';
$attachmentId = '<'.$token.'-attachment@example.net>';
$base = "From: Admin <admin@admin.com>\r\nAuthentication-Results: mx.example; dkim=pass; dmarc=pass\r\n";
$boundary = 'boundary-'.$token;
$messages = [
	[
		'headers' => "To: forum+2@forum.example\r\n{$base}Message-ID: {$rootId}\r\nSubject: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n",
		'body' => 'Root=20email=20body=2E',
	],
	[
		'headers' => "{$base}Message-ID: {$replyId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Direct reply fixture body.',
	],
	[
		'headers' => "{$base}Message-ID: {$referenceId}\r\nReferences: <unrelated@example.net> {$rootId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'References fallback fixture body.',
	],
	[
		'headers' => "{$base}Message-ID: {$replyId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Duplicate message must not post.',
	],
	[
		'headers' => "To: forum+2@forum.example\r\n{$base}Message-ID: <{$token}-auto@example.net>\r\nAuto-Submitted: auto-replied\r\nSubject: {$token} automatic\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Automatic response body.',
	],
	[
		'headers' => "To: forum+2@forum.example\r\nFrom: Unknown <unknown@example.net>\r\nAuthentication-Results: mx.example; dmarc=pass\r\nMessage-ID: <{$token}-unknown@example.net>\r\nSubject: {$token} unknown\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Unknown sender body.',
	],
	[
		'headers' => "To: forum+2@forum.example\r\nFrom: Admin <admin@admin.com>\r\nAuthentication-Results: attacker.example; dmarc=pass\r\nMessage-ID: <{$token}-dmarc@example.net>\r\nSubject: {$token} dmarc\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Untrusted DMARC body.',
	],
	[
		'headers' => "{$base}Message-ID: {$htmlId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/html; charset=UTF-8\r\n",
		'body' => '<p>HTML fixture <strong>body</strong>.</p>',
	],
	[
		'headers' => "{$base}Message-ID: {$attachmentId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n",
		'body' => "--{$boundary}\r\n"
			."Content-Type: text/plain; charset=UTF-8\r\n\r\n"
			."Multipart body; the attachment must be ignored.\r\n"
			."--{$boundary}\r\n"
			."Content-Type: text/plain; charset=UTF-8\r\n"
			."Content-Disposition: attachment; filename=ignored.txt\r\n\r\n"
			."not an attachment upload\r\n"
			."--{$boundary}--\r\n",
	],
	[
		'headers' => "To: forum+2@forum.example\r\nFrom: Admin <admin@admin.com>\r\nSubject: {$token} missing id\r\nAuthentication-Results: mx.example; dmarc=pass\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'A message without a Message-ID still has a stable dedupe key.',
	],
];

$instance = new emailpost($config);
$process = static function(string $raw) use ($instance) {
	$reflection = new ReflectionMethod($instance, 'processMessage');
	$reflection->invoke($instance, $raw);
};
foreach($messages as $message) {
	$process($message['headers']."\r\n".$message['body']);
}

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
$missingIdHeaders = $messages[9]['headers'];
$missingId = '<missing-'.hash('sha256', $missingIdHeaders).'@forum.example>';
$missing = $rowFor($missingId);
emailpost_assert($missing && intval($missing['status']) === 1 && intval($missing['tid']) > 0, 'Message without Message-ID was not deterministically imported.');

echo "Email posting integration tests passed.\n";
