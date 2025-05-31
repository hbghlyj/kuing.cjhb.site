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

$published_time = $_POST['published_time'];

// Validate the timestamp format if necessary. MySQL is generally flexible with ISO 8601.

$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);

if ($conn->connect_error) {
    header("HTTP/1.0 500 Internal Server Error");
    error_log("Database connection failed: " . $conn->connect_error); // Log detailed error
    echo("Failed to connect to the database."); // User-friendly message
    exit;
}

// The 'time' column in your 'chat' table is TIMESTAMP.
// MySQL can typically parse ISO 8601 formatted strings for TIMESTAMP comparison.
$stmt = $conn->prepare("DELETE FROM chat WHERE time = ? AND uid = ?");
if (!$stmt) {
    header("HTTP/1.0 500 Internal Server Error");
    error_log("Prepare statement failed: (" . $conn->errno . ") " . $conn->error);
    echo("Error preparing database query.");
    $conn->close();
    exit;
}

// Assuming $_G['uid'] is set and represents the user ID of the message author.
$stmt->bind_param("is", $_G['uid'], $published_time);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("HTTP/1.0 200 OK");
        echo "Message deleted successfully.";
    } else {
        // This could be 200 OK with a specific message, or 404 if preferred.
        header("HTTP/1.0 200 OK"); // Or use 404 Not Found
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