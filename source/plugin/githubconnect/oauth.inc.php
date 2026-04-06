<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('GITHUBCONNECT_SCOPE', 'read:user user:email');

$settings = C::t('common_setting')->fetch_all_setting([
	'githubconnect_allow',
	'githubconnect_clientid',
	'githubconnect_clientsecret',
]);

if(empty($settings['githubconnect_allow']) || empty($settings['githubconnect_clientid']) || empty($settings['githubconnect_clientsecret'])) {
	showmessage(lang('plugin/githubconnect', 'githubconnect_not_configured'), $_G['siteurl']);
}

$callbackUrl = $_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=callback';
$op = in_array($_GET['op'], ['init', 'callback']) ? $_GET['op'] : 'init';
$atype = account_base::getAccountType('githubconnect');

if($atype === false) {
	showmessage(lang('plugin/githubconnect', 'githubconnect_account_type_missing'), $_G['siteurl']);
}

$request = static function($url, $method = 'GET', $headers = [], $data = null) {
	$baseHeaders = [
		'User-Agent: Discuz-GitHubConnect',
		'Accept: application/json',
	];
	$options = [
		'http' => [
			'method' => $method,
			'ignore_errors' => true,
			'timeout' => 15,
			'header' => implode("\r\n", array_merge($baseHeaders, $headers)),
		],
	];
	if($data !== null) {
		$options['http']['content'] = is_array($data) ? http_build_query($data) : $data;
	}
	$result = @file_get_contents($url, false, stream_context_create($options));
	if($result === false) {
		return [];
	}
	return (array)json_decode($result, true);
};

$normalizeReferer = static function($referer) {
	global $_G;
	$referer = trim((string)$referer);
	if(!$referer) {
		return $_G['siteurl'];
	}
	$parts = @parse_url($referer);
	$siteParts = @parse_url($_G['siteurl']);
	if(empty($parts['host']) || empty($siteParts['host']) || strcasecmp($parts['host'], $siteParts['host']) !== 0) {
		return $_G['siteurl'];
	}
	if(strpos($referer, 'mod=logging') !== false || strpos($referer, 'githubconnect:oauth') !== false) {
		return $_G['siteurl'];
	}
	return $referer;
};

$redirectToReferer = static function() use ($normalizeReferer) {
	global $_G;
	$referer = !empty($_G['cookie']['githubconnect_referer']) ? urldecode($_G['cookie']['githubconnect_referer']) : $_G['siteurl'];
	$referer = $normalizeReferer($referer);
	dsetcookie('githubconnect_state', '', -1);
	dsetcookie('githubconnect_referer', '', -1);
	dheader('Location: '.$referer, true, 302);
};

if($op === 'init') {
	$state = random(16);
	$referer = $normalizeReferer(!empty($_GET['referer']) ? $_GET['referer'] : dreferer());
	dsetcookie('githubconnect_state', $state, 600);
	dsetcookie('githubconnect_referer', rawurlencode($referer), 600);
	$query = [
		'client_id' => $settings['githubconnect_clientid'],
		'redirect_uri' => $callbackUrl,
		'scope' => GITHUBCONNECT_SCOPE,
		'state' => $state,
	];
	dheader('Location: https://github.com/login/oauth/authorize?'.http_build_query($query), true, 302);
}

if(empty($_GET['code']) || empty($_GET['state']) || $_GET['state'] !== $_G['cookie']['githubconnect_state']) {
	showmessage(lang('plugin/githubconnect', 'githubconnect_state_invalid'), $_G['siteurl']);
}

$token = $request(
	'https://github.com/login/oauth/access_token',
	'POST',
	['Content-Type: application/x-www-form-urlencoded'],
	[
		'client_id' => $settings['githubconnect_clientid'],
		'client_secret' => $settings['githubconnect_clientsecret'],
		'code' => $_GET['code'],
		'redirect_uri' => $callbackUrl,
		'state' => $_GET['state'],
	]
);

if(empty($token['access_token'])) {
	showmessage(lang('plugin/githubconnect', 'githubconnect_token_failed'), $_G['siteurl']);
}

$user = $request(
	'https://api.github.com/user',
	'GET',
	['Authorization: Bearer '.$token['access_token'], 'X-GitHub-Api-Version: 2022-11-28']
);

if(empty($user['id'])) {
	showmessage(lang('plugin/githubconnect', 'githubconnect_user_failed'), $_G['siteurl']);
}

$emails = $request(
	'https://api.github.com/user/emails',
	'GET',
	['Authorization: Bearer '.$token['access_token'], 'X-GitHub-Api-Version: 2022-11-28']
);

$email = '';
if(!empty($user['email'])) {
	$email = $user['email'];
}
if(is_array($emails)) {
	foreach($emails as $row) {
		if(!empty($row['email']) && !empty($row['verified']) && !empty($row['primary'])) {
			$email = $row['email'];
			break;
		}
	}
	if(!$email) {
		foreach($emails as $row) {
			if(!empty($row['email']) && !empty($row['verified'])) {
				$email = $row['email'];
				break;
			}
		}
	}
}

$githubId = (string)$user['id'];
$login = trim((string)$user['login']);
$displayName = trim((string)$user['name']);
$bindname = $displayName ?: $login;

$member = null;
$accountRow = C::t('common_member_account')->fetch_by_account($githubId, $atype);
if($accountRow) {
	$member = C::t('common_member')->fetch($accountRow['uid'], true);
	if($member && isset($member['_inarchive'])) {
		C::t('common_member_archive')->move_to_master($member['uid']);
		$member = C::t('common_member')->fetch($accountRow['uid'], true);
	}
}

if(empty($member) && $email) {
	$member = C::t('common_member')->fetch_by_email($email, 1);
	if($member) {
		C::t('common_member_account')->insert([
			'uid' => $member['uid'],
			'atype' => $atype,
			'account' => $githubId,
			'bindname' => $bindname,
		], false, true, true);
		if(isset($member['_inarchive'])) {
			C::t('common_member_archive')->move_to_master($member['uid']);
			$member = C::t('common_member')->fetch($member['uid'], true);
		}
	}
}

if($_G['uid']) {
	if($member && $member['uid'] != $_G['uid']) {
		showmessage(lang('plugin/githubconnect', 'githubconnect_bind_other_exists'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
	}
	if(!$accountRow) {
		C::t('common_member_account')->insert([
			'uid' => $_G['uid'],
			'atype' => $atype,
			'account' => $githubId,
			'bindname' => $bindname,
		], false, true, true);
	}
	showmessage(lang('plugin/githubconnect', 'githubconnect_bind_success'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
}

if(empty($member)) {
	$usernameBase = $login ?: preg_replace('/\s+/', '', $bindname);
	$usernameBase = preg_replace('/[^\w\x7f-\xff]+/u', '', $usernameBase);
	if(!$usernameBase) {
		$usernameBase = 'githubuser';
	}
	$username = substr($usernameBase, 0, 30);
	$password = random(16);

	loaducenter();
	$uid = 0;
	for($i = 0; $i < 6 && $uid <= 0; $i++) {
		$candidate = $i === 0 ? $username : substr($usernameBase, 0, 24).random(4, 1);
		$uid = uc_user_register(addslashes($candidate), $password, $email);
		if($uid > 0) {
			$username = $candidate;
			break;
		}
		if(!in_array($uid, [-3, -6], true)) {
			break;
		}
		if($uid === -6 && $email) {
			$member = C::t('common_member')->fetch_by_email($email, 1);
			if($member) {
				C::t('common_member_account')->insert([
					'uid' => $member['uid'],
					'atype' => $atype,
					'account' => $githubId,
					'bindname' => $bindname,
				], false, true, true);
				break;
			}
		}
	}

	if(empty($member) && $uid <= 0) {
		$msg = match($uid) {
			-1 => 'profile_username_illegal',
			-2 => 'profile_username_protect',
			-3 => 'profile_username_duplicate',
			-4 => 'profile_email_illegal',
			-5 => 'profile_email_domain_illegal',
			-6 => 'profile_email_duplicate',
			default => 'undefined_action',
		};
		showmessage($msg);
	}

	if(empty($member)) {
		C::t('common_member')->insert_user(
			$uid,
			$username,
			'',
			$email,
			$_G['clientip'],
			$_G['setting']['newusergroupid'],
			['emailstatus' => $email ? 1 : 0],
			0,
			$_G['remoteport']
		);
		C::t('common_member_account')->insert([
			'uid' => $uid,
			'atype' => $atype,
			'account' => $githubId,
			'bindname' => $bindname,
		], false, true, true);
		require_once libfile('cache/userstats', 'function');
		build_cache_userstats();
		$member = [
			'uid' => $uid,
			'username' => $username,
			'adminid' => 0,
			'password' => '',
			'groupid' => $_G['setting']['newusergroupid'],
		];
	}
}

require_once libfile('function/member');
setloginstatus($member, 2592000);
$redirectToReferer();
