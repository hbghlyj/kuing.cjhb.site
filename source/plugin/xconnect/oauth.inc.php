<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('XCONNECT_PENDING_TTL', 600);

require_once DISCUZ_PLUGIN('xconnect').'/lib/XOAuth.php';

$settings = C::t('common_setting')->fetch_all_setting([
	'xconnect_allow',
	'xconnect_clientid',
	'xconnect_clientsecret',
]);

if(empty($settings['xconnect_allow']) || empty($settings['xconnect_clientid']) || empty($settings['xconnect_clientsecret'])) {
	showmessage(lang('plugin/xconnect', 'xconnect_not_configured'), $_G['siteurl']);
}

$callbackUrl = $_G['siteurl'].'plugin.php?id=xconnect:oauth&op=callback';
$op = in_array($_GET['op'], ['init', 'callback', 'resolve']) ? $_GET['op'] : 'init';
$atype = account_base::getAccountType('xconnect');

if($atype === false) {
	showmessage(lang('plugin/xconnect', 'xconnect_account_type_missing'), $_G['siteurl']);
}

$request = static function($url, $method = 'GET', $headers = [], $data = null) {
	$baseHeaders = [
		'User-Agent: Discuz-XConnect',
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
	if(strpos($referer, 'mod=logging') !== false || strpos($referer, 'xconnect:oauth') !== false) {
		return $_G['siteurl'];
	}
	return $referer;
};

$setPending = static function($data) {
	global $_G;
	$payload = authcode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'ENCODE', $_G['config']['security']['authkey']);
	dsetcookie('xconnect_pending', rawurlencode($payload), XCONNECT_PENDING_TTL);
};

$getPending = static function() {
	global $_G;
	if(empty($_G['cookie']['xconnect_pending'])) {
		return [];
	}
	$payload = authcode(urldecode($_G['cookie']['xconnect_pending']), 'DECODE', $_G['config']['security']['authkey']);
	if(!$payload) {
		return [];
	}
	$data = json_decode($payload, true);
	return is_array($data) ? $data : [];
};

$clearPending = static function() {
	dsetcookie('xconnect_pending', '', -1);
};

$setFlow = static function($state, $verifier) {
	global $_G;
	$payload = authcode(json_encode([
		'state' => $state,
		'verifier' => $verifier,
	], JSON_UNESCAPED_SLASHES), 'ENCODE', $_G['config']['security']['authkey']);
	dsetcookie('xconnect_flow', rawurlencode($payload), XCONNECT_PENDING_TTL);
};

$getFlow = static function() {
	global $_G;
	if(empty($_G['cookie']['xconnect_flow'])) {
		return [];
	}
	$payload = authcode(urldecode($_G['cookie']['xconnect_flow']), 'DECODE', $_G['config']['security']['authkey']);
	if(!$payload) {
		return [];
	}
	$data = json_decode($payload, true);
	return is_array($data) ? $data : [];
};

$pluginLang = static function($key, $default = null) {
	static $texts;
	global $_G;
	if($texts === null) {
		$texts = [];
		$langdirs = [];
		if(currentlang() === 'EN') {
			$langdirs[] = 'SC';
		}
		if(!empty($_G['i18n'])) {
			$langdirs[] = $_G['i18n'];
		}
		$langdirs[] = currentlang();
		foreach(array_unique($langdirs) as $langdir) {
			$loadfile = DISCUZ_PLUGIN('xconnect').'/i18n/'.$langdir.'/lang_plugin.php';
			if(!file_exists($loadfile)) {
				continue;
			}
			$scriptlang = [];
			$templatelang = [];
			include $loadfile;
			if(!empty($scriptlang['xconnect']) && is_array($scriptlang['xconnect'])) {
				$texts = array_merge($texts, $scriptlang['xconnect']);
			} elseif(!empty($templatelang['xconnect']) && is_array($templatelang['xconnect'])) {
				$texts = array_merge($texts, $templatelang['xconnect']);
			}
		}
	}
	return $texts[$key] ?? ($default !== null ? $default : 'xconnect:'.$key);
};

$redirectToReferer = static function() use ($normalizeReferer, $clearPending) {
	global $_G;
	$referer = !empty($_G['cookie']['xconnect_referer']) ? urldecode($_G['cookie']['xconnect_referer']) : $_G['siteurl'];
	$referer = $normalizeReferer($referer);
	dsetcookie('xconnect_flow', '', -1);
	dsetcookie('xconnect_referer', '', -1);
	$clearPending();
	dheader('Location: '.$referer, true, 302);
};

$fetchMemberFromArchiveAware = static function($uid) {
	$member = C::t('common_member')->fetch($uid, true);
	if($member && isset($member['_inarchive'])) {
		C::t('common_member_archive')->move_to_master($member['uid']);
		$member = C::t('common_member')->fetch($uid, true);
	}
	return $member;
};

$bindAccount = static function($uid, $xId, $bindname) use ($atype) {
	C::t('common_member_account')->insert([
		'uid' => $uid,
		'atype' => $atype,
		'account' => $xId,
		'bindname' => $bindname,
	], false, true, true);
};

$makeUsernameBase = static function($login, $bindname) {
	$usernameBase = $login ?: preg_replace('/\s+/', '', $bindname);
	$usernameBase = preg_replace('/[^\w\x7f-\xff]+/u', '', $usernameBase);
	return $usernameBase ?: 'xuser';
};

$resolveCreateMember = static function($data, $allowRename = false) use ($bindAccount, $fetchMemberFromArchiveAware, $makeUsernameBase) {
	global $_G;

	$usernameBase = $makeUsernameBase($data['login'], $data['bindname']);

	$username = substr($usernameBase, 0, 30);
	$password = random(16);

	loaducenter();
	$uid = 0;
	$attempts = $allowRename ? 6 : 1;
	for($i = 0; $i < $attempts && $uid <= 0; $i++) {
		$candidate = $i === 0 ? $username : substr($usernameBase, 0, 24).random(4, 1);
		$namecheck = native_user_checkname($candidate);
		$emailcheck = $data['email'] ? native_user_checkemail($data['email']) : 1;
		if($emailcheck === -6 && $data['email']) {
			$member = C::t('common_member')->fetch_by_email($data['email'], 1);
			if($member) {
				$member = $fetchMemberFromArchiveAware($member['uid']);
				$bindAccount($member['uid'], $data['xid'], $data['bindname']);
				return $member;
			}
		}
		if($namecheck < 0 || $emailcheck < 0) {
			$uid = $namecheck < 0 ? $namecheck : $emailcheck;
		} else {
			$uid = native_user_create($candidate, $password, $data['email'], $_G['clientip'], $_G['setting']['newusergroupid'], ['emailstatus' => $data['email'] ? 1 : 0], 0, $_G['remoteport']);
			$username = $candidate;
			break;
		}
		if($uid !== -3 || !$allowRename) {
			break;
		}
	}

	if($uid <= 0) {
		return $uid;
	}

	$bindAccount($uid, $data['xid'], $data['bindname']);
	require_once libfile('cache/userstats', 'function');
	build_cache_userstats();

	return [
		'uid' => $uid,
		'username' => $username,
		'adminid' => 0,
		'password' => '',
		'groupid' => $_G['setting']['newusergroupid'],
	];
};

$renderResolvePage = static function($data) use ($pluginLang) {
	global $_G;
	if(!is_array($data)) {
		showmessage($pluginLang('xconnect_resolve_expired'), $_G['siteurl']);
	}
	$navtitle = $pluginLang('xconnect_resolve_title');
	include template('common/header');
	$bindUrl = htmlspecialchars($_G['siteurl'].'plugin.php?id=xconnect:oauth&op=resolve&action=bind', ENT_QUOTES);
	$createUrl = htmlspecialchars($_G['siteurl'].'plugin.php?id=xconnect:oauth&op=resolve&action=create', ENT_QUOTES);
	$loginHint = dhtmlspecialchars($data['login']);
	echo '<div id="ct" class="wp cl"><div class="mn"><div class="bm"><div class="bm_h"><h1>'.$pluginLang('xconnect_resolve_title').'</h1></div><div class="bm_c">';
	echo '<p>'.$pluginLang('xconnect_resolve_message').'</p>';
	if($loginHint) {
		echo '<p class="xg1">'.$pluginLang('xconnect_resolve_login').': '.$loginHint.'</p>';
	}
	echo '<p><a class="pn" href="'.$bindUrl.'"><span>'.$pluginLang('xconnect_resolve_bind').'</span></a> ';
	echo '<a class="pn" href="'.$createUrl.'"><span>'.$pluginLang('xconnect_resolve_create').'</span></a></p>';
	echo '</div></div></div></div>';
	include template('common/footer');
	exit;
};

if($op === 'init') {
	$flow = XOAuth::createFlow();
	$referer = $normalizeReferer(!empty($_GET['referer']) ? $_GET['referer'] : dreferer());
	$setFlow($flow['state'], $flow['verifier']);
	dsetcookie('xconnect_referer', rawurlencode($referer), 600);
	dheader('Location: '.XOAuth::authorizationUrl($settings['xconnect_clientid'], $callbackUrl, $flow), true, 302);
}

if($op === 'resolve') {
	$pending = $getPending();
	if(!is_array($pending) || empty($pending['xid'])) {
		showmessage($pluginLang('xconnect_resolve_expired'), $_G['siteurl']);
	}

	$accountRow = C::t('common_member_account')->fetch_by_account($pending['xid'], $atype);
	if($accountRow) {
		$member = $fetchMemberFromArchiveAware($accountRow['uid']);
		require_once libfile('function/member');
		setloginstatus($member, 2592000);
		$redirectToReferer();
	}

	$action = in_array($_GET['action'], ['bind', 'create']) ? $_GET['action'] : '';
	if($action === 'bind') {
		if(!$_G['uid']) {
			$loginUrl = $_G['siteurl'].'member.php?mod=logging&action=login&referer='.rawurlencode($_G['siteurl'].'plugin.php?id=xconnect:oauth&op=resolve&action=bind');
			dheader('Location: '.$loginUrl, true, 302);
		}
		$bindAccount($_G['uid'], $pending['xid'], $pending['bindname']);
		$clearPending();
		showmessage($pluginLang('xconnect_bind_success'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
	}
	if($action === 'create') {
		$member = $resolveCreateMember($pending, true);
		if(!is_array($member)) {
			$msg = match($member) {
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
		require_once libfile('function/member');
		setloginstatus($member, 2592000);
		$redirectToReferer();
	}

	$renderResolvePage($pending);
}

$flow = $getFlow();
if(empty($_GET['code']) || !is_string($_GET['code']) || empty($_GET['state']) || !is_string($_GET['state']) || empty($flow['state']) || empty($flow['verifier']) || !hash_equals($flow['state'], $_GET['state'])) {
	showmessage(lang('plugin/xconnect', 'xconnect_state_invalid'), $_G['siteurl']);
}

$token = $request(
	XOAuth::TOKEN_URL,
	'POST',
	[
		'Content-Type: application/x-www-form-urlencoded',
		XOAuth::authorizationHeader($settings['xconnect_clientid'], $settings['xconnect_clientsecret']),
	],
	XOAuth::tokenBody($_GET['code'], $callbackUrl, $flow['verifier'])
);
dsetcookie('xconnect_flow', '', -1);

if(empty($token['access_token'])) {
	showmessage(lang('plugin/xconnect', 'xconnect_token_failed'), $_G['siteurl']);
}

$profile = $request(
	XOAuth::USER_URL,
	'GET',
	['Authorization: Bearer '.$token['access_token']]
);
$user = XOAuth::userFromResponse($profile);

if(empty($user['id'])) {
	showmessage(lang('plugin/xconnect', 'xconnect_user_failed'), $_G['siteurl']);
}

$email = '';
$xId = (string)$user['id'];
$login = trim((string)$user['username']);
$displayName = trim((string)$user['name']);
$bindname = $displayName ?: $login;

$member = null;
$accountRow = C::t('common_member_account')->fetch_by_account($xId, $atype);
if($accountRow) {
	$member = $fetchMemberFromArchiveAware($accountRow['uid']);
}

if(empty($member) && $email) {
	$member = C::t('common_member')->fetch_by_email($email, 1);
	if($member) {
		$member = $fetchMemberFromArchiveAware($member['uid']);
		$bindAccount($member['uid'], $xId, $bindname);
	}
}

if($_G['uid']) {
	if($member && $member['uid'] != $_G['uid']) {
		showmessage(lang('plugin/xconnect', 'xconnect_bind_other_exists'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
	}
	if(!$accountRow) {
		$bindAccount($_G['uid'], $xId, $bindname);
	}
	showmessage(lang('plugin/xconnect', 'xconnect_bind_success'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
}

if(empty($member)) {
	$member = $resolveCreateMember([
		'xid' => $xId,
		'login' => $login,
		'bindname' => $bindname,
		'email' => $email,
	], false);

	if(!is_array($member)) {
		if($member === -3) {
			$setPending([
				'xid' => $xId,
				'login' => $login,
				'bindname' => $bindname,
				'email' => $email,
			]);
			dheader('Location: '.$_G['siteurl'].'plugin.php?id=xconnect:oauth&op=resolve', true, 302);
		}
		$msg = match($member) {
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
}

require_once libfile('function/member');
setloginstatus($member, 2592000);
$redirectToReferer();
