<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

defined('UC_STANDALONE') || define('UC_STANDALONE', 1);
defined('UC_AVTPATH') || define('UC_AVTPATH', '');
defined('UC_AVTURL') || define('UC_AVTURL', '');
defined('UC_API') || define('UC_API', rtrim(getglobal('siteurl'), '/').'/api/avatar');

function native_user_generate_password($password) {
	$hash = password_hash($password, PASSWORD_DEFAULT);
	return ($hash === false || $hash === null || !password_verify($password, $hash)) ? password_hash($password, PASSWORD_BCRYPT) : $hash;
}

function native_user_verify_password($password, $hash, $salt = '') {
	if($hash === '') {
		return $password === '';
	}
	if(empty($salt)) {
		return password_verify($password, $hash);
	}
	if(strlen($salt) == 6) {
		return hash_equals($hash, md5(md5($password).$salt));
	}
	return false;
}

function native_user_quescrypt($questionid, $answer) {
	return $questionid > 0 && $answer !== '' ? substr(md5($answer.md5($questionid)), 16, 8) : '';
}

function native_user_avatar_path($uid, $size = 'big', $type = '') {
	$size = in_array($size, ['big', 'middle', 'small']) ? $size : 'big';
	$uid = abs(intval($uid));
	$uid = sprintf('%09d', $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

function uc_avatar_path($uid, $size = 'big', $type = '') {
	return native_user_avatar_path($uid, $size, $type);
}

function uc_avatar($uid, $type = 'virtual', $returnhtml = 1) {
	$endpoint = rtrim(getglobal('siteurl'), '/').'/api/avatar/index.php?m=user&inajax=1&a=rectavatar&avatartype='.$type.'&base64=yes';
	return [
		'width', '450',
		'height', '253',
		'stl_src', $endpoint,
	];
}

function uc_rectavatar($uid) {
	return '';
}

function uc_check_avatar($uid, $size = 'middle', $type = 'virtual') {
	return is_file(DISCUZ_ROOT.'./data/avatar/'.native_user_avatar_path($uid, $size, $type == 'real' ? 'real' : ''));
}

function native_user_fetch($username, $isuid = 0) {
	if($isuid == 1) {
		$user = getuserbyuid(intval($username), 1);
	} elseif($isuid == 2) {
		$user = table_common_member::t()->fetch_by_email(strtolower(trim($username)), 1);
	} elseif($isuid == 4) {
		$mobile = trim($username);
		[$secmobicc, $secmobile] = str_contains($mobile, '-') ? explode('-', $mobile, 2) : [getglobal('setting/smsdefaultcc'), $mobile];
		$users = table_common_member::t()->fetch_all_by_secmobile($secmobicc, $secmobile, 1);
		$user = $users ? reset($users) : [];
	} else {
		$user = table_common_member::t()->fetch_by_loginname(trim($username), 1);
		if(empty($user)) {
			$user = table_common_member::t()->fetch_by_username(trim($username), 1);
		}
	}
	return $user ?: [];
}

function native_user_get($username, $isuid = 0) {
	$user = native_user_fetch($username, $isuid);
	return $user ? [$user['uid'], $user['username'], $user['email']] : [];
}

function native_user_register($username, $password, $email = '', $questionid = '', $answer = '', $regip = '', $secmobicc = '', $secmobile = '', $censor = true) {
	$username = stripslashes(trim($username));
	$email = strtolower(trim($email));
	if($censor) {
		$ret = censor($username, NULL, TRUE, FALSE);
		if(is_array($ret)) {
			return -1;
		}
	}
	$checkname = native_user_checkname($username, false);
	if($checkname < 0) {
		return $checkname;
	}
	$checkemail = native_user_checkemail($email);
	if($checkemail < 0) {
		return $checkemail;
	}
	if($secmobile !== '' && native_user_checksecmobile($secmobicc, $secmobile) < 0) {
		return -9;
	}
	return native_user_create($username, $password, $email, $regip, getglobal('setting/newusergroupid'), [
		'credits' => explode(',', getglobal('setting/initcredits')),
		'profile' => [],
		'emailstatus' => 0,
	], 0, 0, $secmobicc, $secmobile, 0, $questionid, $answer);
}

function native_user_create($username, $password, $email, $ip, $groupid, $extdata, $adminid = 0, $port = 0, $secmobicc = '', $secmobile = '', $secmobilestatus = 0, $questionid = '', $answer = '') {
	$uid = table_common_member::t()->insert_user(0, $username, md5(random(10)), $email, $ip, $groupid, $extdata, $adminid, $port, $secmobicc, $secmobile, $secmobilestatus);
	if($uid > 0) {
		table_common_member_auth::t()->upsert($uid, native_user_generate_password($password), '', native_user_quescrypt($questionid, $answer));
	}
	return $uid;
}

function native_user_login($username, $password, $isuid = 0, $checkques = 0, $questionid = '', $answer = '', $ip = '', $nolog = 0) {
	$user = native_user_fetch(stripslashes($username), intval($isuid));
	if(empty($user['uid'])) {
		return [-1, '', '', ''];
	}
	$auth = table_common_member_auth::t()->fetch($user['uid']);
	if(empty($auth) || !native_user_verify_password($password, $auth['password'], $auth['salt'])) {
		if(!$nolog) {
			native_user_loginfailed($username, $ip);
		}
		return [-2, '', '', ''];
	}
	if($checkques && !empty($auth['secques']) && $auth['secques'] !== native_user_quescrypt($questionid, $answer)) {
		return [-3, '', '', ''];
	}
	if(!empty($auth['salt']) || password_needs_rehash($auth['password'], PASSWORD_DEFAULT)) {
		table_common_member_auth::t()->upsert($user['uid'], native_user_generate_password($password), '', $auth['secques']);
	}
	return [$user['uid'], $user['username'], $user['password'], $user['email']];
}

function native_user_edit($username, $oldpw, $newpw, $email = '', $ignoreoldpw = 0, $questionid = '', $answer = '', $secmobicc = '', $secmobile = '') {
	$user = native_user_fetch(stripslashes($username), 0);
	if(empty($user['uid'])) {
		return -4;
	}
	$auth = table_common_member_auth::t()->fetch($user['uid']);
	if(!$ignoreoldpw && (empty($auth) || !native_user_verify_password($oldpw, $auth['password'], $auth['salt']))) {
		return -1;
	}
	if($email !== '') {
		$email = strtolower(trim($email));
		$check = native_user_checkemail($email, $user['uid']);
		if($check < 0) {
			return $check;
		}
		table_common_member::t()->update($user['uid'], ['email' => $email]);
	}
	if($secmobicc !== '' || $secmobile !== '') {
		$check = native_user_checksecmobile($secmobicc, $secmobile, $user['uid']);
		if($check < 0) {
			return $check;
		}
		table_common_member::t()->update($user['uid'], ['secmobicc' => (string)$secmobicc, 'secmobile' => (string)$secmobile]);
	}
	if($newpw !== '' || $questionid !== '') {
		$newauth = $auth ?: ['password' => '', 'salt' => '', 'secques' => ''];
		if($newpw !== '') {
			$newauth['password'] = native_user_generate_password($newpw);
			$newauth['salt'] = '';
		}
		if($questionid !== '') {
			$newauth['secques'] = native_user_quescrypt($questionid, $answer);
		}
		table_common_member_auth::t()->upsert($user['uid'], $newauth['password'], $newauth['salt'], $newauth['secques']);
	}
	return 1;
}

function native_user_delete($uid) {
	$uids = dintval((array)$uid, true);
	if(!$uids) {
		return 0;
	}
	table_common_member_auth::t()->delete($uids);
	native_user_deleteavatar($uids);
	return count($uids);
}

function native_user_deleteavatar($uid) {
	$uids = dintval((array)$uid, true);
	foreach($uids as $id) {
		foreach(['big', 'middle', 'small'] as $size) {
			foreach(['real', ''] as $type) {
				$path = native_user_avatar_path($id, $size, $type);
				$file = DISCUZ_ROOT.'./data/avatar/'.$path;
				if(is_file($file)) {
					@unlink($file);
				}
				if(getglobal('setting/ftp/on') || getglobal('setting/oss/status')) {
					ftpcmd('delete', 'avatar/'.$path);
				}
			}
		}
		table_common_member::t()->update($id, ['avatarstatus' => 0]);
	}
}

function native_user_checkname($username, $censor = true) {
	$username = trim(stripslashes($username));
	if($username === '' || dstrlen($username) < 3 || dstrlen($username) > 50 || preg_match('/[<>\'\"\\\\]/', $username)) {
		return -1;
	}
	if($censor) {
		$ret = censor($username, NULL, TRUE, FALSE);
		if(is_array($ret)) {
			return -2;
		}
	}
	return table_common_member::t()->fetch_uid_by_loginname($username, 1) || table_common_member::t()->fetch_uid_by_username($username, 1) ? -3 : 1;
}

function native_user_checkemail($email, $ignoreuid = 0) {
	$email = strtolower(trim($email));
	if(!isemail($email) || strlen($email) > 255) {
		return -4;
	}
	$user = table_common_member::t()->fetch_by_email($email, 1);
	return $user && intval($user['uid']) !== intval($ignoreuid) ? -6 : 1;
}

function native_user_checksecmobile($secmobicc, $secmobile, $ignoreuid = 0) {
	if($secmobile === '' || $secmobile === 0 || $secmobile === '0') {
		return 1;
	}
	$users = table_common_member::t()->fetch_all_by_secmobile($secmobicc, $secmobile, 1);
	foreach($users as $user) {
		if(intval($user['uid']) !== intval($ignoreuid)) {
			return -9;
		}
	}
	return 1;
}

function native_user_chgusername($uid, $newusername, $oldusername = '') {
	global $_G;
	$uid = intval($uid);
	$newusername = stripslashes(trim($newusername));
	$oldusername = $oldusername ? $oldusername : $_G['username'];
	if($newusername == $oldusername) {
		return 1;
	}
	$ucresult = native_user_checkname($newusername);
	if($ucresult < 0) {
		return $ucresult;
	}
	if(table_common_member_username_history::t()->fetch($oldusername)) {
		table_common_member_username_history::t()->update($oldusername, ['uid' => $uid, 'dateline' => TIMESTAMP]);
	} else {
		table_common_member_username_history::t()->insert(['username' => $oldusername, 'uid' => $uid, 'dateline' => TIMESTAMP]);
	}
	table_common_member::t()->update_username($uid, $newusername);
	return 1;
}

function native_user_logincheck($username, $ip) {
	$login = table_common_failedlogin::t()->fetch_ip($ip);
	$return = (!$login || (TIMESTAMP - $login['lastupdate'] > 900)) ? 5 : max(0, 5 - $login['count']);
	if(!$login || TIMESTAMP - $login['lastupdate'] > 900) {
		table_common_failedlogin::t()->insert(['ip' => $ip, 'count' => 0, 'lastupdate' => TIMESTAMP], false, true);
		table_common_failedlogin::t()->delete_old(901);
	}
	return $return;
}

function native_user_loginfailed($username, $ip = '') {
	$ip = $ip ?: getglobal('clientip');
	table_common_failedlogin::t()->update_failed($ip);
}

function native_user_synlogin($uid) {
	return '';
}

function native_user_synlogout() {
	return '';
}

function native_user_addprotected($username, $admin = '') {
	return 1;
}

function native_user_deleteprotected($username) {
	return 1;
}

function native_user_getprotected() {
	return [];
}

function uc_pm_location($uid, $newpm = 0) {
	return '';
}

function uc_pm_checknew($uid, $more = 0) {
	return $more ? [] : 0;
}

function uc_pm_send($fromuid, $msgto, $subject, $message, $instantly = 1, $replypmid = 0, $isusername = 0, $type = 0) {
	return 0;
}

function uc_pm_delete($uid, $folder, $pmids) {
	return 0;
}

function uc_pm_deleteuser($uid, $touids) {
	return 0;
}

function uc_pm_deletechat($uid, $plids, $type = 0) {
	return 0;
}

function uc_pm_readstatus($uid, $uids, $plids = [], $status = 0) {
	return 0;
}

function uc_pm_list($uid, $page = 1, $pagesize = 10, $folder = 'inbox', $filter = 'newpm', $msglen = 0) {
	return ['count' => 0, 'data' => []];
}

function uc_pm_ignore($uid) {
	return 1;
}

function uc_pm_view($uid, $pmid = 0, $touid = 0, $daterange = 1, $page = 0, $pagesize = 10, $type = 0, $isplid = 0) {
	return [];
}

function uc_pm_view_num($uid, $touid, $isplid) {
	return 0;
}

function uc_pm_viewnode($uid, $type, $pmid) {
	return [];
}

function uc_pm_chatpmmemberlist($uid, $plid = 0) {
	return [];
}

function uc_pm_kickchatpm($plid, $uid, $touid) {
	return 0;
}

function uc_pm_appendchatpm($plid, $uid, $touid) {
	return 0;
}

function uc_pm_blackls_get($uid) {
	return '';
}

function uc_pm_blackls_set($uid, $blackls) {
	return 1;
}

function uc_pm_blackls_add($uid, $username) {
	return 1;
}

function uc_pm_blackls_delete($uid, $username) {
	return 1;
}
