<?php
require '../../source/class/class_core.php';
require '../../config/config_global.php';

$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();

if (!isset($_POST['published_time'])) {
    header("HTTP/1.0 400 Bad Request");
    echo('published_time must be provided');
    exit;
}
if(empty($_G['uid'])) {
  $_G['uid'] = 0;
  $_G['username'] = $_G['member']['username'].' '.$_SERVER['REMOTE_ADDR'];
}

$published_time = $_POST['published_time'];

$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);

if ($conn->connect_error) {
    header("HTTP/1.0 500 Internal Server Error");
    error_log("Database connection failed: " . $conn->connect_error);
    echo("Failed to connect to the database.");
    exit;
}

$stmt = $conn->prepare("DELETE FROM chat WHERE time = STR_TO_DATE(?, '%Y-%m-%dT%TZ') AND author = ?");
$stmt->bind_param("ss", $published_time, $_G['username']);
if (!$stmt) {
    header("HTTP/1.0 500 Internal Server Error");
    error_log("Prepare statement failed: (" . $conn->errno . ") " . $conn->error);
    echo("Error preparing database query.");
    $conn->close();
    exit;
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("HTTP/1.0 200 OK");
        echo "Message deleted successfully.";
    } else {
        header("HTTP/1.0 403 Forbidden");
        echo "No message was deleted.";
    }
} else {
    header("HTTP/1.0 500 Internal Server Error");
    error_log("Execute statement failed: (" . $stmt->errno . ") " . $stmt->error);
    echo("Error deleting message from database.");
}

$stmt->close();
$conn->close();
?>