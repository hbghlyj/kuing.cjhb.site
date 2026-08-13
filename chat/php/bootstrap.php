<?php

function chat_init() {
	$root = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
	chdir($root);
	require_once $root.'source/class/class_core.php';
	$discuz = C::app();
	$discuz->init_cron = false;
	$discuz->init();
	return $root;
}

function chat_json($status, array $payload) {
	http_response_code($status);
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function chat_require_write($membersOnly = false) {
	global $_G;
	if($_SERVER['REQUEST_METHOD'] !== 'POST') {
		chat_json(405, ['error' => 'POST required']);
	}
	if($membersOnly && empty($_G['uid'])) {
		chat_json(403, ['error' => 'Authentication required']);
	}
	$formhash = isset($_POST['formhash']) && is_string($_POST['formhash']) ? $_POST['formhash'] : '';
	if(!hash_equals(FORMHASH, $formhash)) {
		chat_json(403, ['error' => 'Invalid formhash']);
	}
}

function chat_database($root) {
	include $root.'config/config_global.php';
	$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);
	if($conn->connect_error) {
		chat_json(500, ['error' => 'Database connection failed']);
	}
	$conn->set_charset('utf8mb4');
	return $conn;
}

function chat_session_token($sid) {
	global $_G;
	$sid = (string)$sid;
	if($sid === '') {
		return '';
	}
	$key = (string)($_G['config']['security']['authkey'] ?? '');
	return hash_hmac('sha256', $sid, $key);
}
