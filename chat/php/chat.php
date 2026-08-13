<?php

require_once __DIR__.'/bootstrap.php';
$discuzRoot = chat_init();
chat_require_write();

$chatInfo = isset($_POST['chat_info']) && is_array($_POST['chat_info']) ? $_POST['chat_info'] : [];
$text = isset($chatInfo['text']) && is_string($chatInfo['text']) ? trim($chatInfo['text']) : '';
if($text === '') {
	chat_json(400, ['error' => 'Chat text must be provided']);
}
if((function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text)) > 2000) {
	chat_json(413, ['error' => 'Chat text is too long']);
}

$message = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$uid = (int)$_G['uid'];
$sid = $uid ? '' : (string)($_G['sid'] ?? '');
$author = $_G['username'];
if(!$uid) {
	$location = ip::format_session_location($_G['session']['location'] ?? '', $_G['session']['city'] ?? null);
	$author = $location['compact'] ?: 'Guest';
	if($sid !== '') {
		$author .= ' #'.substr(md5($sid), 0, 4);
	}
}
$conn = chat_database($discuzRoot);

// Keep the short-lived history bounded before adding the new message.
$conn->query('DELETE FROM chat WHERE time < DATE_SUB(NOW(), INTERVAL 2 DAY)');
$timeResult = $conn->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') AS chat_time");
$chatTime = $timeResult ? $timeResult->fetch_assoc()['chat_time'] : null;
if(!$chatTime) {
	$conn->close();
	chat_json(500, ['error' => 'Unable to allocate chat message time']);
}
$stmt = $conn->prepare('INSERT INTO chat (time, uid, sid, author, message) VALUES (?, ?, ?, ?, ?)');
if(!$stmt) {
	$conn->close();
	chat_json(500, ['error' => 'Unable to save chat message']);
}
$saved = false;
for($attempt = 0; $attempt < 100; $attempt++) {
	$stmt->bind_param('sisss', $chatTime, $uid, $sid, $author, $message);
	if($stmt->execute()) {
		$saved = true;
		break;
	}
	if($stmt->errno !== 1062) {
		break;
	}
	$chatTime = date('Y-m-d H:i:s', strtotime($chatTime) + 1);
}
$stmt->close();
if(!$saved) {
	$conn->close();
	chat_json(500, ['error' => 'Unable to save chat message']);
}

require_once $discuzRoot.'vendor/autoload.php';
require_once __DIR__.'/Activity.php';
require_once __DIR__.'/config.php';

$options = [
	'displayName' => $author,
	'image' => !$uid ? '/static/image/common/online_guest.svg' : (!empty($_G['member']['avatarstatus']) ? avatar($uid, 'small', 1) : ''),
	'actorId' => (int)$_G['uid'],
	'messageTime' => $chatTime,
	'sessionId' => chat_session_token($sid),
];
$activity = new Activity('chat-message', $message, $options);
$data = $activity->getMessage();

$pusher = new Pusher(APP_KEY, APP_SECRET, APP_ID, ['cluster' => 'eu', 'useTLS' => true]);
$result = $pusher->trigger('Chat', 'chat_message', $data, null, true);
if((int)($result['status'] ?? 500) !== 200) {
	$stmt = $conn->prepare('DELETE FROM chat WHERE time = ?');
	if($stmt) {
		$stmt->bind_param('s', $chatTime);
		$stmt->execute();
		$stmt->close();
	}
	$conn->close();
	chat_json(502, ['error' => 'Unable to publish chat message']);
}

$conn->close();
chat_json(200, ['time' => $chatTime]);
