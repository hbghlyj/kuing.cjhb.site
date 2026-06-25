<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('GITHUBCONNECT_SCOPE', 'read:user user:email');
define('GITHUBCONNECT_PENDING_TTL', 600);

$settings = C::t('common_setting')->fetch_all_setting([
	'githubconnect_allow',
	'githubconnect_clientid',
	'githubconnect_clientsecret',
]);

if(empty($settings['githubconnect_allow']) || empty($settings['githubconnect_clientid']) || empty($settings['githubconnect_clientsecret'])) {
	showmessage(lang('plugin/githubconnect', 'githubconnect_not_configured'), $_G['siteurl']);
}

$callbackUrl = $_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=callback';
$op = in_array($_GET['op'], ['init', 'callback', 'resolve']) ? $_GET['op'] : 'init';
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

$setPending = static function($data) {
	global $_G;
	$payload = authcode(serialize($data), 'ENCODE', $_G['config']['security']['authkey']);
	dsetcookie('githubconnect_pending', rawurlencode($payload), GITHUBCONNECT_PENDING_TTL);
};

$getPending = static function() {
	global $_G;
	if(empty($_G['cookie']['githubconnect_pending'])) {
		return [];
	}
	$payload = authcode(urldecode($_G['cookie']['githubconnect_pending']), 'DECODE', $_G['config']['security']['authkey']);
	if(!$payload) {
		return [];
	}
	$data = @dunserialize($payload);
	if(!is_array($data)) {
		$data = @unserialize($payload);
	}
	return is_array($data) ? $data : [];
};

$clearPending = static function() {
	dsetcookie('githubconnect_pending', '', -1);
};

$pluginLang = static function($key, $default = null) {
	static $texts;
	global $_G;
	if($texts === null) {
		$texts = [];
		$langdirs = [];
		if(currentlang() === 'EN_UTF8') {
			$langdirs[] = 'SC_UTF8';
		}
		if(!empty($_G['i18n'])) {
			$langdirs[] = $_G['i18n'];
		}
		$langdirs[] = currentlang();
		foreach(array_unique($langdirs) as $langdir) {
			$loadfile = DISCUZ_PLUGIN('githubconnect').'/i18n/'.$langdir.'/lang_plugin.php';
			if(!file_exists($loadfile)) {
				continue;
			}
			$scriptlang = [];
			$templatelang = [];
			include $loadfile;
			if(!empty($scriptlang['githubconnect']) && is_array($scriptlang['githubconnect'])) {
				$texts = array_merge($texts, $scriptlang['githubconnect']);
			} elseif(!empty($templatelang['githubconnect']) && is_array($templatelang['githubconnect'])) {
				$texts = array_merge($texts, $templatelang['githubconnect']);
			}
		}
	}
	return $texts[$key] ?? ($default !== null ? $default : 'githubconnect:'.$key);
};

$redirectToReferer = static function() use ($normalizeReferer, $clearPending) {
	global $_G;
	$referer = !empty($_G['cookie']['githubconnect_referer']) ? urldecode($_G['cookie']['githubconnect_referer']) : $_G['siteurl'];
	$referer = $normalizeReferer($referer);
	dsetcookie('githubconnect_state', '', -1);
	dsetcookie('githubconnect_referer', '', -1);
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

$bindAccount = static function($uid, $githubId, $bindname) use ($atype) {
	C::t('common_member_account')->insert([
		'uid' => $uid,
		'atype' => $atype,
		'account' => $githubId,
		'bindname' => $bindname,
	], false, true, true);
};

$makeUsernameBase = static function($login, $bindname) {
	$usernameBase = $login ?: preg_replace('/\s+/', '', $bindname);
	$usernameBase = preg_replace('/[^\w\x7f-\xff]+/u', '', $usernameBase);
	return $usernameBase ?: 'githubuser';
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
				$bindAccount($member['uid'], $data['githubid'], $data['bindname']);
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

	$bindAccount($uid, $data['githubid'], $data['bindname']);
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
		showmessage($pluginLang('githubconnect_resolve_expired'), $_G['siteurl']);
	}
	$navtitle = $pluginLang('githubconnect_resolve_title');
	include template('common/header');
	$bindUrl = htmlspecialchars($_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=resolve&action=bind', ENT_QUOTES);
	$createUrl = htmlspecialchars($_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=resolve&action=create', ENT_QUOTES);
	$loginHint = dhtmlspecialchars($data['login']);
	$emailHint = dhtmlspecialchars($data['email']);
	echo '<div id="ct" class="wp cl"><div class="mn"><div class="bm"><div class="bm_h"><h1>'.$pluginLang('githubconnect_resolve_title').'</h1></div><div class="bm_c">';
	echo '<p>'.$pluginLang('githubconnect_resolve_message').'</p>';
	if($loginHint) {
		echo '<p class="xg1">'.$pluginLang('githubconnect_resolve_login').': '.$loginHint.'</p>';
	}
	if($emailHint) {
		echo '<p class="xg1">'.$pluginLang('githubconnect_resolve_email').': '.$emailHint.'</p>';
	}
	echo '<p><a class="pn" href="'.$bindUrl.'"><span>'.$pluginLang('githubconnect_resolve_bind').'</span></a> ';
	echo '<a class="pn" href="'.$createUrl.'"><span>'.$pluginLang('githubconnect_resolve_create').'</span></a></p>';
	echo '</div></div></div></div>';
	include template('common/footer');
	exit;
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

if($op === 'resolve') {
	$pending = $getPending();
	if(!is_array($pending) || empty($pending['githubid'])) {
		showmessage($pluginLang('githubconnect_resolve_expired'), $_G['siteurl']);
	}

	$accountRow = C::t('common_member_account')->fetch_by_account($pending['githubid'], $atype);
	if($accountRow) {
		$member = $fetchMemberFromArchiveAware($accountRow['uid']);
		require_once libfile('function/member');
		setloginstatus($member, 2592000);
		$redirectToReferer();
	}

	$action = in_array($_GET['action'], ['bind', 'create']) ? $_GET['action'] : '';
	if($action === 'bind') {
		if(!$_G['uid']) {
			$loginUrl = $_G['siteurl'].'member.php?mod=logging&action=login&referer='.rawurlencode($_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=resolve&action=bind');
			dheader('Location: '.$loginUrl, true, 302);
		}
		$bindAccount($_G['uid'], $pending['githubid'], $pending['bindname']);
		$clearPending();
		showmessage($pluginLang('githubconnect_bind_success'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
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
	$member = $fetchMemberFromArchiveAware($accountRow['uid']);
}

if(empty($member) && $email) {
	$member = C::t('common_member')->fetch_by_email($email, 1);
	if($member) {
		$member = $fetchMemberFromArchiveAware($member['uid']);
		$bindAccount($member['uid'], $githubId, $bindname);
	}
}

if($_G['uid']) {
	if($member && $member['uid'] != $_G['uid']) {
		showmessage(lang('plugin/githubconnect', 'githubconnect_bind_other_exists'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
	}
	if(!$accountRow) {
		$bindAccount($_G['uid'], $githubId, $bindname);
	}
	showmessage(lang('plugin/githubconnect', 'githubconnect_bind_success'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
}

if(empty($member)) {
	$member = $resolveCreateMember([
		'githubid' => $githubId,
		'login' => $login,
		'bindname' => $bindname,
		'email' => $email,
	], false);

	if(!is_array($member)) {
		if($member === -3) {
			$setPending([
				'githubid' => $githubId,
				'login' => $login,
				'bindname' => $bindname,
				'email' => $email,
			]);
			dheader('Location: '.$_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=resolve', true, 302);
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
