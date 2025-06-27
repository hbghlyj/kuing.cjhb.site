<?php
require '../../source/class/class_core.php';
$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();
include '../../config/config_global.php';
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

$sql = "SELECT DATE_FORMAT( CONVERT_TZ(`timestamp`, @@session.time_zone, '+00:00'), '%Y-%m-%dT%TZ') as ISO8601, uid, author, message FROM chat ORDER BY time DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log('Prepare failed: ' . $conn->error);
    echo json_encode(['error' => 'Database query failed']);
    $conn->close();
    exit;
}
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$rows = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rows[] = array(
            'body' => $row['message'],
            'published' => $row['ISO8601'],
            'actor' => array(
                'displayName' => $row['author'],
                'image' => avatar($row['uid'], 'small', 1)
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