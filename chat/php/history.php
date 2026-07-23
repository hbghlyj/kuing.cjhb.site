<?php
$discuzRoot = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
chdir($discuzRoot);
require $discuzRoot.'source/class/class_core.php';
$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();
include $discuzRoot.'config/config_global.php';
$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Get total count of messages
$count_sql = "SELECT COUNT(*) as total FROM chat";
$count_result = $conn->query($count_sql);
$total_rows = 0;
if ($count_result) {
    $total_rows = $count_result->fetch_assoc()['total'];
}

$tablepre = $_G['config']['db'][1]['tablepre'];
$sql = "SELECT UNIX_TIMESTAMP(c.time) AS published_ts, c.uid, c.author, c.message, m.avatarstatus FROM chat c LEFT JOIN {$tablepre}common_member m ON m.uid = c.uid ORDER BY c.time DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$rows = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rows[] = array(
            'body' => $row['message'],
            'published' => gmdate('Y-m-d\TH:i:s\Z', (int)$row['published_ts']),
            'actor' => array(
                'displayName' => $row['author'],
                'image' => !empty($row['avatarstatus']) ? avatar($row['uid'], 'small', 1) : ''
            )
        );
    }
}

// Return messages in chronological order for display
$response = array(
    'messages' => array_reverse($rows), // Reverse to show oldest first within the current batch
    'total' => (int)$total_rows
);

echo json_encode($response);

$conn->close();
?>
