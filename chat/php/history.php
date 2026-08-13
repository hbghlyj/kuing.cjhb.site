<?php

require_once __DIR__.'/bootstrap.php';
$discuzRoot = chat_init();
$conn = chat_database($discuzRoot);

$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$countResult = $conn->query('SELECT COUNT(*) AS total FROM chat');
$totalRows = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
$tablepre = $_G['config']['db'][1]['tablepre'];
$sql = "SELECT c.time AS message_time, UNIX_TIMESTAMP(c.time) AS published_ts, c.uid, c.author, c.message, c.sid, m.avatarstatus FROM chat c LEFT JOIN {$tablepre}common_member m ON m.uid = c.uid ORDER BY c.time DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while($row = $result->fetch_assoc()) {
	$rows[] = [
		'id' => $row['message_time'],
		'message_time' => $row['message_time'],
		'body' => $row['message'],
		'published' => gmdate('Y-m-d\TH:i:s\Z', (int)$row['published_ts']),
		'actor' => [
			'id' => (int)$row['uid'],
			'displayName' => $row['author'],
			'image' => !(int)$row['uid'] ? '/static/image/common/online_guest.svg' : (!empty($row['avatarstatus']) ? avatar($row['uid'], 'small', 1) : ''),
			'sessionId' => chat_session_token($row['sid']),
		],
	];
}
$stmt->close();
$conn->close();

chat_json(200, ['messages' => array_reverse($rows), 'total' => $totalRows, 'sessionId' => chat_session_token($_G['sid'] ?? '')]);
