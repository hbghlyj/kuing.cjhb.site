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
$referer = $referer && (strpos($referer, 'mod=register') === false) && (strpos($referer, 'mod=login') === false) ? $referer : 'index.php';

require_once 'vendor/autoload.php';
$client = new Google_Client(['client_id' => $_G['setting']['connectappid']]);
$payload = $client->verifyIdToken($_POST['credential']);
if ($payload) {
  $gmail = $payload['email'];
  $username = $payload['name'];
} else {
  showmessage('Invalid ID token', $referer);
}

if($op == 'callback') {
	global $_G;
	
	if(!($member = C::t('common_member')->fetch_by_email($gmail, 1))) {
		require_once libfile('function/misc');
		loaducenter();
		$uid = uc_user_register(addslashes($username), '', $gmail);
		if($uid <= 0) {
			if($uid == -1) {
				showmessage('profile_username_illegal');
			} elseif($uid == -2) {
				showmessage('profile_username_protect');
			} elseif($uid == -3) {
				showmessage('profile_username_duplicate');
			} elseif($uid == -4) {
				showmessage('profile_email_illegal');
			} elseif($uid == -5) {
				showmessage('profile_email_domain_illegal');
			} elseif($uid == -6) {
				showmessage('profile_email_duplicate');
			} else {
				showmessage('undefined_action');
			}
		}
		C::t('common_member')->insert_user($uid, $username, '', $gmail, $_G['clientip'], $_G['setting']['newusergroupid'], array('emailstatus'=>1), 0, $_G['remoteport']);
		C::t('common_member')->update($uid, array('conisbind' => '1'));
		$member = array(
			'uid' => $uid,
			'username' => $username,
			'adminid' => 0,
			'password' => '', // Password is unset for Google login
			'groupid' => $_G['setting']['newusergroupid']
		);
		require_once libfile('cache/userstats', 'function');
		build_cache_userstats();
		include_once libfile('function/stat');
		updatestat('register');
	} else {
		if(isset($member['_inarchive'])) {
			C::t('common_member_archive')->move_to_master($member['uid']);
		}
	}
	require_once libfile('function/member');
	$cookietime = 1296000;
	setloginstatus($member, $cookietime);
	$usergroups = $_G['cache']['usergroups'][$_G['groupid']]['grouptitle'];
	$param = array('username' => $_G['member']['username'], 'usergroup' => $_G['group']['grouptitle'], 'timeoffsetupdated' => '');

	C::t('common_member_status')->update($connect_member['uid'], array('lastip'=>$_G['clientip'], 'lastvisit'=>TIMESTAMP, 'lastactivity' => TIMESTAMP));
	showmessage('login_succeed', $referer, $param);
}