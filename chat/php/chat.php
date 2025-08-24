<?php
require '../../source/class/class_core.php';
$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();

require_once('./vendor/autoload.php');
require_once('Activity.php');
require_once('config.php');

$chat_info = $_POST['chat_info'];

$channel_name = 'Chat';

if( !isset($_POST['chat_info']) ){
  header("HTTP/1.0 400 Bad Request");
  echo('chat_info must be provided');
}
if(empty($_G['uid'])) {
  $_G['uid'] = 0;
  $_G['username'] = explode("\n",$_G['member']['username'])[0].' '.$_SERVER['REMOTE_ADDR'];
}

$options = array();
$options['displayName'] = $_G['username'];
$options['text'] = htmlspecialchars($chat_info['text']);
$options['image'] = 'https://' . $_SERVER['HTTP_HOST'] . substr(avatar($_G['uid'], 'small', 1), 1);
$activity = new Activity('chat-message', $options['text'], $options);

$pusher = new Pusher(APP_KEY,APP_SECRET,APP_ID,array(
  'cluster' => 'eu',
  'useTLS' => true
));
$data = $activity->getMessage();
$result = $pusher->trigger($channel_name, 'chat_message', $data, null, true);
if ($result['status'] == 200) {
  include '../../config/config_global.php';
  $conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);
  if ($conn->connect_error) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Connection failed: " . $conn->connect_error);
  }

  // Delete messages older than 2 days.
  $delete_sql = "DELETE FROM chat WHERE time < DATE_SUB(NOW(), INTERVAL 2 DAY)";
  if (!$conn->query($delete_sql)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Delete failed: " . $conn->error);
  }

  $stmt = $conn->prepare(
    "INSERT INTO chat (uid, author, message) VALUES (?, LEFT(?, 30), ?)"
  );
  if (!$stmt) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Prepare failed: " . $conn->error);
  }
  if (!$stmt->bind_param("iss", $_G['uid'], $_G['username'], $options['text'])) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Bind failed: " . $stmt->error);
  }
  if (!$stmt->execute()) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Execute failed: " . $stmt->error);
  }
  $stmt->close();
  $conn->close();
}
header('HTTP/1.1 ' . $result['status']);
echo $result['body'];
?>