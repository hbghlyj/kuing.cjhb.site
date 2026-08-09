<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function pusher_trigger_forum($event, array $payload, $socket_id = null) {
	require_once DISCUZ_ROOT.'/vendor/autoload.php';
	require_once DISCUZ_ROOT.'/chat/php/config.php';

	$socket_id = (string)$socket_id;
	if(!preg_match('/^\d+\.\d+$/', $socket_id)) {
		$socket_id = null;
	}

	$pusher = new \Pusher(APP_KEY, APP_SECRET, APP_ID, [
		'cluster' => 'eu',
		'useTLS' => true
	]);
	$pusher->trigger('Chat', $event, $payload, $socket_id);
}
