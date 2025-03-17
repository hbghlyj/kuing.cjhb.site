<?php
require '../../source/class/class_core.php';
$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();
if(empty($_G['uid'])) {
  header('HTTP/1.1 401 Unauthorized');
  exit('not_loggedin');
}
require_once('./vendor/autoload.php');
require_once('Activity.php');
require_once('config.php');

date_default_timezone_set('UTC');

$chat_info = $_POST['chat_info'];

$channel_name = 'Chat';

if( !isset($_POST['chat_info']) ){
  header("HTTP/1.0 400 Bad Request");
  echo('chat_info must be provided');
}

$options = array();
$options['displayName'] = $_G['username'];
$options['text'] = substr(htmlspecialchars($chat_info['text']), 0, 300);
$options['image']['url'] = 'https://' . $_SERVER['HTTP_HOST'] . substr(avatar($_G['uid'], 'small', 1), 1);



include '../../config/config_global.php';
$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CREATE TABLE chat (time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, uid mediumint NOT NULL, author CHAR(15) NOT NULL, message TEXT NOT NULL );
$stmt = $conn->prepare("INSERT INTO chat (uid,author,message) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $_G['uid'], $_G['username'], $options['text']);
$stmt->execute();
$stmt->close();
$conn->close();


$activity = new Activity('chat-message', $options['text'], $options);

$pusher = new Pusher(APP_KEY,APP_SECRET,APP_ID,array(
    'cluster' => 'eu',
    'useTLS' => true
  ));
$data = $activity->getMessage();
$response = $pusher->trigger($channel_name, 'chat_message', $data, null, true);

header('Cache-Control: no-cache, must-revalidate');
header('Content-type: application/json');

$result = array('activity' => $data, 'pusherResponse' => $response);
echo(json_encode($result));
?>