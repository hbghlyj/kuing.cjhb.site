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
$emailConfig = emailpost_test_config();

// Keep parser/security coverage runnable without a configured local database.
$parser = new emailpost($emailConfig);
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

$plainRaw = "Hello headerless body.\n";
emailpost_assert($call('findBodyPart', $plainRaw, 'PLAIN') === "Hello headerless body.\n", 'Headerless plain-text body parsing failed.');
$multipartAlt = "Content-Type: multipart/alternative; boundary=XMAILBOUND\r\n\r\n"
	."--XMAILBOUND\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
	."Plain alternative body.\r\n"
	."--XMAILBOUND\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n"
	."<b>HTML alternative</b>\r\n"
	."--XMAILBOUND--\r\n";
emailpost_assert($call('findBodyPart', $multipartAlt, 'PLAIN') === "Plain alternative body.\r\n", 'Multipart/alternative plain extraction failed.');
emailpost_assert($call('findBodyPart', $multipartAlt, 'HTML') === "<b>HTML alternative</b>\r\n", 'Multipart/alternative HTML extraction failed.');
emailpost_assert($call('hasAlternativePart', $multipartAlt) === true, 'multipart/alternative was not detected.');
emailpost_assert($call('hasAlternativePart', $plainRaw) === false, 'Plain text was mis-detected as multipart/alternative.');
emailpost_assert($call('messageBody', $multipartAlt) === 'HTML alternative', 'multipart/alternative should prefer the HTML part converted to text.');
emailpost_assert($call('messageBody', "Content-Type: text/plain; charset=UTF-8\r\n\r\nplain body") === 'plain body', 'Plain text should be used when there is no alternative part.');
emailpost_assert($call('htmlToText', '<p>a <strong>b</strong><br>c</p>') === "a b\nc", 'htmlToText should preserve line breaks and strip tags.');
emailpost_assert($call('findBodyPart', "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\nUm9vdCBiYXNlNjQgYm9keS4=", 'PLAIN') === 'Root base64 body.', 'Base64 plain body decoding failed.');
emailpost_assert($call('findBodyPart', "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nquoted printable body =C3=A9", 'PLAIN') === 'quoted printable body é', 'Quoted-printable decoding failed.');
emailpost_assert($call('findBodyPart', '', 'PLAIN') === '', 'Empty body should decode to an empty string.');
$multipartAttach = "Content-Type: multipart/mixed; boundary=ATT\r\n\r\n"
	."--ATT\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
	."Body text.\r\n"
	."--ATT\r\nContent-Type: text/plain; charset=UTF-8\r\n"
	."Content-Disposition: attachment; filename=file.txt\r\n\r\n"
	."file content\r\n"
	."--ATT--\r\n";
$attachments = $call('findAttachments', $multipartAttach);
emailpost_assert(count($attachments) === 1 && $attachments[0]['name'] === 'file.txt' && $attachments[0]['data'] === "file content\r\n", 'Multipart attachment parsing failed.');
emailpost_assert($call('findAttachments', "Content-Type: text/plain\r\n\r\nplain only") === [], 'Plain text was mis-detected as an attachment.');
emailpost_assert($call('cleanFilename', '..\\..\\evil.txt') === 'evil.txt', 'Filename sanitization failed.');
$nestedAlternative = "Content-Type: multipart/mixed; boundary=OUTER\r\n\r\n"
	."--OUTER\r\nContent-Type: multipart/alternative; boundary=INNER\r\n\r\n"
	."--INNER\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
	."Nested plain.\r\n"
	."--INNER\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n"
	."<p>Nested <u>html</u>.</p>\r\n"
	."--INNER--\r\n"
	."--OUTER--\r\n";
emailpost_assert($call('hasAlternativePart', $nestedAlternative) === true, 'Nested multipart/alternative was not detected.');
emailpost_assert($call('hasAlternativePart', $multipartAttach) === false, 'multipart/mixed without an alternative was mis-detected.');
emailpost_assert($call('messageBody', $nestedAlternative) === 'Nested html.', 'Nested alternative should prefer the HTML part converted to text.');

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
C::t('common_usergroup')->update(1, ['allowpost' => 1, 'allowreply' => 1]);
C::t('common_usergroup_field')->update(1, ['allowpost' => 1, 'allowreply' => 1, 'disablepostctrl' => 1]);
require_once libfile('function/cache');
updatecache('usergroups');

$token = 'emailpost-fixture-'.bin2hex(random_bytes(6));
$rootId = '<'.$token.'-root@example.net>';
$replyId = '<'.$token.'-reply@example.net>';
$referenceId = '<'.$token.'-reference@example.net>';
$htmlId = '<'.$token.'-html@example.net>';
$attachmentId = '<'.$token.'-attachment@example.net>';
$altId = '<'.$token.'-alt@example.net>';
$ownReplyId = '<'.$token.'-ownreply@example.net>';
$base = "From: Admin <admin@admin.com>\r\nAuthentication-Results: mx.example; dkim=pass; dmarc=pass\r\n";
$boundary = 'boundary-'.$token;
$instance = new emailpost($emailConfig);
$process = static function(string $raw) use ($instance) {
	$reflection = new ReflectionMethod($instance, 'processMessage');
	$reflection->invoke($instance, $raw);
};
$rowFor = static fn(string $id) => table_forum_emailpost::t()->fetch($id);

// Messages that do not depend on the thread reply identity.
$standalone = [
	[
		'headers' => "To: forum+2@forum.example\r\n{$base}Message-ID: {$rootId}\r\nSubject: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n",
		'body' => 'Root=20"email"=20body=2E',
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
		'headers' => "To: forum+2@forum.example\r\nFrom: Admin <admin@admin.com>\r\nSubject: {$token} missing id\r\nAuthentication-Results: mx.example; dmarc=pass\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'A message without a Message-ID still has a stable dedupe key.',
	],
];
foreach($standalone as $message) {
	$process($message['headers']."\r\n".$message['body']);
}

$root = $rowFor($rootId);
emailpost_assert($root && intval($root['status']) === 1 && intval($root['fid']) === 2 && intval($root['tid']) > 0 && intval($root['pid']) > 0, 'New-thread email was not persisted as a post.');

emailpost_assert(emailpost::threadHasEmailCopy(intval($root['tid'])) === true, 'Email-created thread was not detected as having an email copy relationship.');
emailpost_assert(emailpost::threadHasEmailCopy(999999999) === false, 'Non-email thread was detected as having an email copy relationship.');
$copyNotice = emailpost::authorReplyNotice(['tid' => intval($root['tid'])], ['author' => 'Admin', 'message' => 'x']);
emailpost_assert(is_array($copyNotice) && ($copyNotice[0] ?? null) === ['emailpost', 'sendReplyCopy'] && intval($copyNotice[1][0]['tid']) === intval($root['tid']), 'Reply-copy notice was not built for an email-created thread.');
emailpost_assert(emailpost::authorReplyNotice(['tid' => 999999999], []) === [], 'Reply-copy notice was built for a non-email thread.');

// Replies route only via the deterministic <thread-{tid}@domain> identity.
$threadId = '<thread-'.intval($root['tid']).'@forum.example>';
$replyFixtures = [
	[
		'headers' => "{$base}Message-ID: {$replyId}\r\nIn-Reply-To: {$threadId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Direct reply fixture body.',
	],
	[
		'headers' => "{$base}Message-ID: {$referenceId}\r\nReferences: <unrelated@example.net> {$threadId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'References fallback fixture body.',
	],
	[
		'headers' => "{$base}Message-ID: {$replyId}\r\nIn-Reply-To: {$threadId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Duplicate message must not post.',
	],
	[
		'headers' => "{$base}Message-ID: {$htmlId}\r\nIn-Reply-To: {$threadId}\r\nSubject: 回复：{$token} root\r\nContent-Type: text/html; charset=UTF-8\r\n",
		'body' => '<p>HTML fixture <strong>body</strong>.</p>',
	],
	[
		'headers' => "{$base}Message-ID: {$attachmentId}\r\nIn-Reply-To: {$threadId}\r\nSubject: Re: {$token} root\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n",
		'body' => "--{$boundary}\r\n"
			."Content-Type: text/plain; charset=UTF-8\r\n\r\n"
			."Multipart body with an attachment.\r\n"
			."--{$boundary}\r\n"
			."Content-Type: text/plain; charset=UTF-8\r\n"
			."Content-Disposition: attachment; filename=attachment.txt\r\n\r\n"
			."attached file content\r\n"
			."--{$boundary}--\r\n",
	],
	[
		'headers' => "{$base}Message-ID: {$altId}\r\nIn-Reply-To: {$threadId}\r\nSubject: Re: {$token} root\r\nContent-Type: multipart/alternative; boundary=ALT-{$token}\r\n",
		'body' => "--ALT-{$token}\r\n"
			."Content-Type: text/plain; charset=UTF-8\r\n\r\n"
			."Hard wrapped line one.\r\nline two.\r\n"
			."--ALT-{$token}\r\n"
			."Content-Type: text/html; charset=UTF-8\r\n\r\n"
			."<p>Alt <strong>HTML</strong> fixture.</p><p>Second line.</p>\r\n"
			."--ALT-{$token}--\r\n",
	],
	[
		'headers' => "{$base}Message-ID: {$ownReplyId}\r\nIn-Reply-To: {$rootId}\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n",
		'body' => 'Reply to own original email fixture body.',
	],
];
foreach($replyFixtures as $message) {
	$process($message['headers']."\r\n".$message['body']);
}

$reply = $rowFor($replyId);
$reference = $rowFor($referenceId);
$html = $rowFor($htmlId);
$attachment = $rowFor($attachmentId);
$alt = $rowFor($altId);
$ownReply = $rowFor($ownReplyId);
$parentid = $threadId;
emailpost_assert($reply && intval($reply['status']) === 1 && intval($reply['tid']) === intval($root['tid']) && $reply['parentid'] === $parentid, 'In-Reply-To to the thread identity did not create a mapped reply.');
emailpost_assert($reference && intval($reference['status']) === 1 && intval($reference['tid']) === intval($root['tid']) && $reference['parentid'] === $parentid, 'References fallback to the thread identity did not create a mapped reply.');
emailpost_assert($html && intval($html['status']) === 1 && intval($html['tid']) === intval($root['tid']), 'HTML-only email was not converted into a reply.');
emailpost_assert($attachment && intval($attachment['status']) === 1 && intval($attachment['tid']) === intval($root['tid']), 'Multipart email was not posted.');
emailpost_assert($alt && intval($alt['status']) === 1 && intval($alt['tid']) === intval($root['tid']), 'multipart/alternative email was not posted.');
emailpost_assert($ownReply && intval($ownReply['status']) === 1 && intval($ownReply['tid']) === intval($root['tid']) && $ownReply['parentid'] === $rootId, 'Reply to the original thread email Message-ID was not routed to the thread.');

// A reply referencing only an unknown Message-ID is not routed.
$orphanHeaders = "{$base}Message-ID: <{$token}-orphan@example.net>\r\nIn-Reply-To: <{$token}-never-sent@example.net>\r\nSubject: Re: {$token} root\r\nContent-Type: text/plain; charset=UTF-8\r\n";
$process($orphanHeaders."\r\nOrphaned reply body.");
$orphan = $rowFor('<'.$token.'-orphan@example.net>');
emailpost_assert($orphan && intval($orphan['status']) === -1, 'Reply referencing an unknown Message-ID must be rejected (no forum+FID recipient for a new thread).');

$post = get_post_by_pid(intval($root['pid']));
$htmlPost = get_post_by_pid(intval($html['pid']));
$attachmentPost = get_post_by_pid(intval($attachment['pid']));
$altPost = get_post_by_pid(intval($alt['pid']));
$ownReplyPost = get_post_by_pid(intval($ownReply['pid']));
emailpost_assert(str_contains($post['message'], 'Root "email" body.') && str_contains($post['message'], '"email"'), 'Quoted-printable plain-text body was not decoded or was double-escaped.');
$replyPost = get_post_by_pid(intval($reply['pid']));
$referencePost = get_post_by_pid(intval($reference['pid']));
emailpost_assert($replyPost['subject'] === '' && $referencePost['subject'] === '' && $htmlPost['subject'] === '', 'Reply subjects starting with "Re:" were not stored as empty.');
emailpost_assert(intval($post['status']) === 264, 'Email-posted thread was not marked with the via-email status bits.');
emailpost_assert(getstatus(intval($htmlPost['status']), 4) === 1 && getstatus(intval($htmlPost['status']), 9) === 1, 'Email reply was not marked with the via-email status bits.');
emailpost_assert(str_contains($htmlPost['message'], 'HTML fixture') && str_contains($htmlPost['message'], 'body'), 'HTML body was not converted.');
emailpost_assert(!empty($attachmentPost['message']) && str_contains($attachmentPost['message'], 'Multipart body with an attachment.'), 'Multipart email body was not extracted.');
emailpost_assert(str_contains($altPost['message'], 'Alt HTML fixture.') && str_contains($altPost['message'], 'Second line.') && !str_contains($altPost['message'], 'Hard wrapped'), 'multipart/alternative should post the HTML part converted to text instead of the hard-wrapped plain part.');
emailpost_assert(str_contains($ownReplyPost['message'], 'Reply to own original email fixture body.'), 'Own-reply post message was not stored.');
emailpost_assert(intval($attachmentPost['attachment']) > 0, 'Email attachment was not imported as a forum attachment.');
$emailAttachCount = table_forum_attachment_n::t()->count_by_id('tid:'.$root['tid'], 'pid', intval($attachment['pid']));
emailpost_assert(intval($emailAttachCount) === 1, 'Email attachment was not persisted in the attachment table.');
$emailAttachRows = table_forum_attachment_n::t()->fetch_all_by_id('tid:'.$root['tid'], 'pid', intval($attachment['pid']));
$emailAid = intval(array_key_first($emailAttachRows));
$emailMain = table_forum_attachment::t()->fetch($emailAid);
emailpost_assert($emailMain && intval($emailMain['tid']) === intval($root['tid']) && intval($emailMain['pid']) === intval($attachment['pid']) && intval($emailMain['tableid']) === getattachtableid(intval($root['tid'])), 'Email attachment was not linked to the post.');
$emailAttachFile = rtrim((string)getglobal('setting/attachdir'), '/').'/forum/'.($emailAttachRows[$emailAid]['attachment'] ?? '');
emailpost_assert(is_file($emailAttachFile), 'Email attachment file was not written to the forum attachment directory.');
emailpost_assert(!str_contains($attachmentPost['message'], '[attach]'), 'Email attachment imported an [attach] tag into the message.');
emailpost_assert(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE tid=%d', ['forum_post', $root['tid']])) === 7, 'Duplicate or rejected email created an unexpected post.');

foreach(['<'.$token.'-auto@example.net>', '<'.$token.'-unknown@example.net>', '<'.$token.'-dmarc@example.net>'] as $rejectedId) {
	$row = $rowFor($rejectedId);
	emailpost_assert($row && intval($row['status']) === -1, "Rejected message {$rejectedId} was not recorded as rejected.");
}
$missingIdHeaders = $standalone[4]['headers'];
$missingId = '<missing-'.hash('sha256', $missingIdHeaders).'@forum.example>';
$missing = $rowFor($missingId);
emailpost_assert($missing && intval($missing['status']) === 1 && intval($missing['tid']) > 0, 'Message without Message-ID was not deterministically imported.');

echo "Email posting integration tests passed.\n";
