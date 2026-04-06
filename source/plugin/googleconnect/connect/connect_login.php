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

define('GOOGLE_ATYPE', 4);
define('GOOGLECONNECT_PENDING_TTL', 600);

$op = !empty($_GET['op']) ? $_GET['op'] : '';
if(!in_array($op, ['init', 'callback', 'change', 'resolve'])) {
	showmessage('undefined_action');
}

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
	if(strpos($referer, 'mod=register') !== false || strpos($referer, 'mod=login') !== false || strpos($referer, 'connect.php?mod=login') !== false) {
		return $_G['siteurl'];
	}
	return $referer;
};

$setPending = static function($data) {
	global $_G;
	$payload = authcode(serialize($data), 'ENCODE', $_G['config']['security']['authkey']);
	dsetcookie('googleconnect_pending', rawurlencode($payload), GOOGLECONNECT_PENDING_TTL);
};

$getPending = static function() {
	global $_G;
	if(empty($_G['cookie']['googleconnect_pending'])) {
		return [];
	}
	$payload = authcode(urldecode($_G['cookie']['googleconnect_pending']), 'DECODE', $_G['config']['security']['authkey']);
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
	dsetcookie('googleconnect_pending', '', -1);
};

$setReferer = static function($referer) {
	dsetcookie('googleconnect_referer', rawurlencode($referer), GOOGLECONNECT_PENDING_TTL);
};

$redirectToReferer = static function() use ($normalizeReferer, $clearPending) {
	global $_G;
	$referer = !empty($_G['cookie']['googleconnect_referer']) ? urldecode($_G['cookie']['googleconnect_referer']) : $_G['siteurl'];
	$referer = $normalizeReferer($referer);
	dsetcookie('googleconnect_referer', '', -1);
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

$bindAccount = static function($uid, $gsub, $gmail) {
	C::t('common_member_account')->insert([
		'uid' => $uid,
		'atype' => GOOGLE_ATYPE,
		'account' => $gsub,
		'bindname' => $gmail,
	], false, true, true);
};

$resolveCreateMember = static function($data, $allowRename = false) use ($bindAccount, $fetchMemberFromArchiveAware) {
	global $_G;

	require_once libfile('function/misc');
	loaducenter();

	$usernameBase = trim((string)$data['username']);
	if($usernameBase === '') {
		$usernameBase = 'googleuser';
	}
	$username = $usernameBase;
	$uid = 0;
	$attempts = $allowRename ? 6 : 1;

	for($i = 0; $i < $attempts && $uid <= 0; $i++) {
		$candidate = $i === 0 ? $username : substr($usernameBase, 0, 24).rand(100, 9999);
		$uid = uc_user_register(addslashes($candidate), '', $data['gmail']);
		if($uid > 0) {
			$username = $candidate;
			break;
		}
		if($uid === -6 && $data['gmail']) {
			$member = C::t('common_member')->fetch_by_email($data['gmail'], 1);
			if($member) {
				$member = $fetchMemberFromArchiveAware($member['uid']);
				$bindAccount($member['uid'], $data['gsub'], $data['gmail']);
				return $member;
			}
		}
		if($uid !== -3 || !$allowRename) {
			break;
		}
	}

	if($uid <= 0) {
		return $uid;
	}

	C::t('common_member')->insert_user($uid, $username, '', $data['gmail'], $_G['clientip'], $_G['setting']['newusergroupid'], ['emailstatus' => 1], 0, $_G['remoteport']);
	C::t('common_member')->update($uid, ['conisbind' => '1']);
	$bindAccount($uid, $data['gsub'], $data['gmail']);
	require_once libfile('cache/userstats', 'function');
	build_cache_userstats();
	include_once libfile('function/stat');
	updatestat('register');

	return [
		'uid' => $uid,
		'username' => $username,
		'adminid' => 0,
		'password' => '',
		'groupid' => $_G['setting']['newusergroupid'],
	];
};

$renderResolvePage = static function($data) {
	global $_G;
	if(!is_array($data)) {
		showmessage(lang('plugin/googleconnect', 'googleconnect_resolve_expired'), $_G['siteurl']);
	}
	$_G['setting']['seohead']['title'] = lang('plugin/googleconnect', 'googleconnect_resolve_title');
	include template('common/header');
	$bindUrl = htmlspecialchars($_G['siteurl'].'connect.php?mod=login&op=resolve&action=bind', ENT_QUOTES);
	$createUrl = htmlspecialchars($_G['siteurl'].'connect.php?mod=login&op=resolve&action=create', ENT_QUOTES);
	$usernameHint = dhtmlspecialchars($data['username']);
	$emailHint = dhtmlspecialchars($data['gmail']);
	echo '<div id="ct" class="wp cl"><div class="mn"><div class="bm"><div class="bm_h"><h1>'.lang('plugin/googleconnect', 'googleconnect_resolve_title').'</h1></div><div class="bm_c">';
	echo '<p>'.lang('plugin/googleconnect', 'googleconnect_resolve_message').'</p>';
	if($usernameHint) {
		echo '<p class="xg1">'.lang('plugin/googleconnect', 'googleconnect_resolve_username').': '.$usernameHint.'</p>';
	}
	if($emailHint) {
		echo '<p class="xg1">'.lang('plugin/googleconnect', 'googleconnect_resolve_email').': '.$emailHint.'</p>';
	}
	echo '<p><a class="pn" href="'.$bindUrl.'"><span>'.lang('plugin/googleconnect', 'googleconnect_resolve_bind').'</span></a> ';
	echo '<a class="pn" href="'.$createUrl.'"><span>'.lang('plugin/googleconnect', 'googleconnect_resolve_create').'</span></a></p>';
	echo '</div></div></div></div>';
	include template('common/footer');
	exit;
};

if($op === 'resolve') {
	$pending = $getPending();
	if(!is_array($pending) || empty($pending['gsub'])) {
		showmessage(lang('plugin/googleconnect', 'googleconnect_resolve_expired'), $_G['siteurl']);
	}

	$accountRow = C::t('common_member_account')->fetch_by_account($pending['gsub'], GOOGLE_ATYPE);
	if($accountRow) {
		$member = $fetchMemberFromArchiveAware($accountRow['uid']);
		require_once libfile('function/member');
		setloginstatus($member, 1296000);
		$redirectToReferer();
	}

	$action = in_array($_GET['action'], ['bind', 'create']) ? $_GET['action'] : '';
	if($action === 'bind') {
		if(!$_G['uid']) {
			$loginUrl = $_G['siteurl'].'member.php?mod=logging&action=login&referer='.rawurlencode($_G['siteurl'].'connect.php?mod=login&op=resolve&action=bind');
			dheader('Location: '.$loginUrl, true, 302);
		}
		$bindAccount($_G['uid'], $pending['gsub'], $pending['gmail']);
		$clearPending();
		showmessage(lang('plugin/googleconnect', 'googleconnect_bind_success'), $_G['siteurl'].'home.php?mod=spacecp&ac=account');
	}
	if($action === 'create') {
		$member = $resolveCreateMember($pending, true);
		if(!is_array($member)) {
			if($member == -1) {
				showmessage('profile_username_illegal');
			} elseif($member == -2) {
				showmessage('profile_username_protect');
			} elseif($member == -3) {
				showmessage('profile_username_duplicate');
			} elseif($member == -4) {
				showmessage('profile_email_illegal');
			} elseif($member == -5) {
				showmessage('profile_email_domain_illegal');
			} elseif($member == -6) {
				showmessage('profile_email_duplicate');
			} else {
				showmessage('undefined_action');
			}
		}
		require_once libfile('function/member');
		setloginstatus($member, 1296000);
		$redirectToReferer();
	}

	$renderResolvePage($pending);
}

$referer = $normalizeReferer(dreferer());
$setReferer($referer);

if(empty($_POST['credential'])) {
	showmessage('Invalid ID token', $referer);
}

require_once 'vendor/autoload.php';
$client = new Google_Client(['client_id' => $_G['setting']['connectappid']]);
$payload = $client->verifyIdToken($_POST['credential']);

if(!$payload) {
	showmessage('Invalid ID token', $referer);
}

$gsub = $payload['sub'];
$gmail = $payload['email'];
$username = $payload['name'];

if($op == 'callback') {
	global $_G;

	$accountRow = C::t('common_member_account')->fetch_by_account($gsub, GOOGLE_ATYPE);
	if($accountRow) {
		$member = $fetchMemberFromArchiveAware($accountRow['uid']);
	}

	if(empty($member)) {
		$member = C::t('common_member')->fetch_by_email($gmail, 1);
		if($member) {
			$member = $fetchMemberFromArchiveAware($member['uid']);
			$bindAccount($member['uid'], $gsub, $gmail);
		}
	}

	if(empty($member)) {
		$member = $resolveCreateMember([
			'gsub' => $gsub,
			'gmail' => $gmail,
			'username' => $username,
		], false);

		if(!is_array($member)) {
			if($member == -3) {
				$setPending([
					'gsub' => $gsub,
					'gmail' => $gmail,
					'username' => $username,
				]);
				dheader('Location: '.$_G['siteurl'].'connect.php?mod=login&op=resolve', true, 302);
			}
			if($member == -1) {
				showmessage('profile_username_illegal');
			} elseif($member == -2) {
				showmessage('profile_username_protect');
			} elseif($member == -3) {
				showmessage('profile_username_duplicate');
			} elseif($member == -4) {
				showmessage('profile_email_illegal');
			} elseif($member == -5) {
				showmessage('profile_email_domain_illegal');
			} elseif($member == -6) {
				showmessage('profile_email_duplicate');
			} else {
				showmessage('undefined_action');
			}
		}
	}

	require_once libfile('function/member');
	$cookietime = 1296000;
	setloginstatus($member, $cookietime);
	$param = ['username' => $_G['member']['username'], 'usergroup' => $_G['group']['grouptitle'], 'timeoffsetupdated' => ''];

	C::t('common_member_status')->update($member['uid'], ['lastip' => $_G['clientip'], 'lastvisit' => TIMESTAMP, 'lastactivity' => TIMESTAMP]);
	showmessage('login_succeed', $referer, $param);
}
