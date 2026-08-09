<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'/vendor/autoload.php';
require_once DISCUZ_ROOT.'/chat/php/config.php';

function pusher_trigger_forum($event, array $payload, $tab_id = null) {
	global $_G;

	$tab_id = explode('.', (string)$tab_id);
	if(count($tab_id) === 3 && ctype_digit($tab_id[0]) && (int)$tab_id[0] === (int)$_G['uid'] && preg_match('/^[a-z0-9]{16,128}$/i', $tab_id[1]) && hash_equals(hash_hmac('sha256', $tab_id[0].'|'.$tab_id[1], $_G['config']['security']['authkey']), $tab_id[2])) {
		$payload['origin_tab_id'] = implode('.', $tab_id);
	}

	$pusher = new \Pusher(APP_KEY, APP_SECRET, APP_ID, [
		'cluster' => 'eu',
		'useTLS' => true
	]);
	$pusher->trigger('Chat', $event, $payload);
}
