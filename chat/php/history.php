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

// Old rows beyond limit 50 will be deleted as new ones come in.
$delete_sql = "DELETE chat FROM chat JOIN (SELECT time FROM chat ORDER BY time DESC LIMIT 50, 1) AS subquery ON chat.time < subquery.time";
$conn->query($delete_sql);
if ($conn->error) {
    die("Error: " . $conn->error);
}

$sql = "SELECT DATE_FORMAT(time, '%Y-%m-%dT%TZ') as ISO8601, uid, author, message FROM chat";
$result = $conn->query($sql);
$rows = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rows[] = array(
            'body' => $row['message'],
            'published' => $row['ISO8601'],
            'actor' => array(
                'displayName' => $row['author'],
                'image' => array(
                    'url' => avatar($row['uid'], 'small', 1),
                )
            )
        );
    }
    echo json_encode($rows);
} else {
    echo json_encode([]);
}
$conn->close();
?>