<?php
$discuzRoot = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
chdir($discuzRoot);
require $discuzRoot.'source/class/class_core.php';
require $discuzRoot.'config/config_global.php';

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
  $_G['username'] = explode("\n",$_G['member']['username'])[0].' '.$_SERVER['REMOTE_ADDR'];
}
$chat_author = cutstr($_G['username'], 30, '');

$published_time = $_POST['published_time'];
$published_timestamp = strtotime($published_time);
if($published_timestamp === false) {
    header("HTTP/1.0 400 Bad Request");
    echo('published_time is invalid');
    exit;
}

$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);

if ($conn->connect_error) {
    header("HTTP/1.0 500 Internal Server Error");
    error_log("Database connection failed: " . $conn->connect_error);
    echo("Failed to connect to the database.");
    exit;
}

$stmt = $conn->prepare("DELETE FROM chat WHERE UNIX_TIMESTAMP(time) = ? AND author = ?");
$stmt->bind_param("is", $published_timestamp, $chat_author);
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
