<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      Google Connect Login
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$op = !empty($_GET['op']) ? $_GET['op'] : '';
if(!in_array($op, array('init', 'callback', 'change'))) {
	showmessage('undefined_action');
}
$referer = dreferer();

require_once 'vendor/autoload.php';
$client = new Google_Client(['client_id' => $_G['setting']['connectappid']]);
$payload = $client->verifyIdToken($_POST['credential']);
if ($payload) {
  $gmail = $payload['email'];
} else {
  showmessage('Invalid ID token', $referer);
}

if($op == 'callback') {
	global $_G;
	
	if(!($member = DB::fetch_first("SELECT * FROM %t WHERE email=%s", array('common_member',$gmail)))) {
		showmessage('No user found with this email: '.$gmail, $referer);
	} else {
		if(isset($member['_inarchive'])) {
			C::t('common_member_archive')->move_to_master($member['uid']);
		}
	}
	
	require_once libfile('function/member');
	$cookietime = 1296000;
	setloginstatus($member, $cookietime);
	loadcache('usergroups');
	$usergroups = $_G['cache']['usergroups'][$_G['groupid']]['grouptitle'];
	$param = array('username' => $_G['member']['username'], 'usergroup' => $_G['group']['grouptitle'], 'timeoffsetupdated' => '');

	C::t('common_member_status')->update($connect_member['uid'], array('lastip'=>$_G['clientip'], 'lastvisit'=>TIMESTAMP, 'lastactivity' => TIMESTAMP));
	showmessage('login_succeed', './', $param);
}