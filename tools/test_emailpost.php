<?php

define('IN_DISCUZ', true);
define('DISCUZ_ROOT', dirname(__DIR__).'/');
require DISCUZ_ROOT.'source/class/class_emailpost.php';

$service = new emailpost([
	'recipient_domain' => 'forum.example',
	'trusted_authserv_id' => 'mx.example',
	'require_dmarc' => true,
]);

$call = static function($method, ...$arguments) use ($service) {
	$reflection = new ReflectionMethod($service, $method);
	return $reflection->invoke($service, ...$arguments);
};

$headers = "To: Forum <forum+6@forum.example>\r\n"
	."Message-ID: <message-2@example.net>\r\n"
	."In-Reply-To: <message-1@example.net>\r\n"
	."References: <root@example.net>\r\n\t<message-1@example.net>\r\n"
	."Authentication-Results: mx.example; dkim=pass; dmarc=pass\r\n";

assert($call('forumIdFromRecipient', $headers) === 6);
assert($call('messageIdsForHeader', $headers, 'Message-ID') === ['<message-2@example.net>']);
assert($call('messageIdsForHeader', $headers, 'References') === ['<root@example.net>', '<message-1@example.net>']);
$call('validateDmarc', $headers);

$badRoute = "To: thread+42@forum.example\r\nFrom: forum+9@forum.example\r\n";
try {
	$call('forumIdFromRecipient', $badRoute);
	throw new RuntimeException('thread+TID or From routing was accepted.');
} catch(emailpost_rejection) {
}

try {
	$call('validateDmarc', "Authentication-Results: attacker.example; dmarc=pass\r\n");
	throw new RuntimeException('Untrusted Authentication-Results was accepted.');
} catch(emailpost_rejection) {
}

echo "Email posting header tests passed.\n";
