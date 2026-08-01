<?php

require_once __DIR__.'/bootstrap.php';
$discuzRoot = chat_init();
chat_require_write();

$messageTime = isset($_POST['message_time']) && is_string($_POST['message_time']) ? $_POST['message_time'] : '';
if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $messageTime)) {
	chat_json(400, ['error' => 'Invalid chat message key']);
}

$conn = chat_database($discuzRoot);
$uid = (int)$_G['uid'];
if($uid) {
	$types = 'si';
	$match = ['time = ? AND uid = ?', [$messageTime, $uid]];
} else {
	$sid = (string)($_G['sid'] ?? '');
	$types = 'ss';
	$match = ['time = ? AND uid = 0 AND sid = ?', [$messageTime, $sid]];
}
$stmt = $conn->prepare('SELECT message FROM chat WHERE '.$match[0]);
$stmt->bind_param($types, ...$match[1]);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row) {
	$conn->close();
	chat_json(403, ['error' => 'You cannot delete this chat message']);
}

$stmt = $conn->prepare('DELETE FROM chat WHERE '.$match[0]);
$stmt->bind_param($types, ...$match[1]);
if(!$stmt->execute()) {
	$stmt->close();
	$conn->close();
	chat_json(500, ['error' => 'Unable to delete chat message']);
}
$stmt->close();
$conn->close();

if(preg_match_all('/\/data\/attachment\/chat\/[A-Za-z0-9_\-\.\/]+/i', $row['message'], $matches)) {
	$allowedBase = realpath($discuzRoot.'data/attachment/chat');
	foreach($matches[0] as $relativeUrl) {
		$photoPath = realpath($discuzRoot.ltrim($relativeUrl, '/'));
		if($allowedBase && $photoPath && ($photoPath === $allowedBase || str_starts_with($photoPath, $allowedBase.DIRECTORY_SEPARATOR)) && is_file($photoPath)) {
			@unlink($photoPath);
		}
	}
}

require_once $discuzRoot.'vendor/autoload.php';
require_once __DIR__.'/config.php';
$pusher = new Pusher(APP_KEY, APP_SECRET, APP_ID, ['cluster' => 'eu', 'useTLS' => true]);
$pusher->trigger('Chat', 'chat_delete', ['message_time' => $messageTime], null, true);

chat_json(200, ['status' => 200]);
