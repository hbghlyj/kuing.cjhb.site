<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

chdir(dirname(__DIR__, 2));
require_once './source/class/class_core.php';

$discuz = C::app();
$discuz->init();

if(getgpc('m') !== 'user' || getgpc('a') !== 'rectavatar') {
	exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_G['uid']) || !hash_equals((string)FORMHASH, (string)getgpc('formhash', 'P'))) {
	avatar_response(false, 403);
}

$ftp = getglobal('setting/ftp');
$oss = getglobal('setting/oss');

@header('Expires: 0');
@header('Cache-Control: private, post-check=0, pre-check=0, max-age=0', FALSE);
@header('Pragma: no-cache');

$avatartype = getgpc('avatartype', 'G') == 'real' ? 'real' : 'virtual';
$storageRoot = (!empty($ftp['on']) && $ftp['on'] == 2 && $oss['oss_avatar']) ? DISCUZ_DATA.'attachment/' : DISCUZ_ROOT.'./data/';
$storagePrefix = 'avatar/';

$avatars = [
	['file' => $storagePrefix.get_avatar($_G['uid'], 'big', $avatartype), 'data' => avatar_image('avatar1', 200, 250)],
	['file' => $storagePrefix.get_avatar($_G['uid'], 'middle', $avatartype), 'data' => avatar_image('avatar2', 120, 120)],
	['file' => $storagePrefix.get_avatar($_G['uid'], 'small', $avatartype), 'data' => avatar_image('avatar3', 48, 48)],
];
foreach($avatars as $avatar) {
	if($avatar['data'] === false) {
		avatar_response(false, 400);
	}
}

$tempfiles = [];
$success = true;
foreach($avatars as $avatar) {
	$target = $storageRoot.$avatar['file'];
	dmkdir(dirname($target));
	$temp = $target.'.'.bin2hex(random_bytes(8)).'.tmp';
	if(!is_dir(dirname($target)) || file_put_contents($temp, $avatar['data'], LOCK_EX) !== strlen($avatar['data'])) {
		if(is_file($temp)) {
			@unlink($temp);
		}
		$success = false;
		break;
	}
	$tempfiles[$temp] = $target;
}
if($success) {
	foreach($tempfiles as $temp => $target) {
		if(!@rename($temp, $target)) {
			$success = false;
			break;
		}
	}
}
foreach($tempfiles as $temp => $target) {
	if(is_file($temp)) {
		@unlink($temp);
	}
}

if($success && !empty($ftp['on']) && $ftp['on'] == 2 && $oss['oss_avatar']) {
	foreach($avatars as $avatar) {
		if(!ftpcmd('upload', $avatar['file'])) {
			$success = false;
		}
	}
	if($success) {
		foreach($avatars as $avatar) {
			@unlink($storageRoot.$avatar['file']);
		}
	}
}

if($success && !$_G['member']['avatarstatus']) {
	table_common_member::t()->update($_G['uid'], ['avatarstatus' => '1']);
}

avatar_response($success, $success ? 200 : 500);

function avatar_image($name, $maxwidth, $maxheight) {
	$encoded = getgpc($name, 'P');
	if(!is_string($encoded) || strlen($encoded) > 4 * 1024 * 1024) {
		return false;
	}
	$image = base64_decode($encoded, true);
	if($image === false || strlen($image) < 4 || strlen($image) > 2 * 1024 * 1024 || !str_starts_with($image, "\xFF\xD8") || !str_ends_with($image, "\xFF\xD9")) {
		return false;
	}
	if(function_exists('getimagesizefromstring')) {
		$info = @getimagesizefromstring($image);
		if(!$info || $info[2] !== IMAGETYPE_JPEG || $info[0] < 1 || $info[1] < 1 || $info[0] > $maxwidth || $info[1] > $maxheight) {
			return false;
		}
	}
	return $image;
}

function avatar_response($success, $status = 200) {
	http_response_code($status);
	$result = $success ? 'success' : 'failure';
	echo '<script>window.parent.postMessage('.json_encode($result).',"*");</script>';
	exit;
}

function get_avatar($uid, $size = 'big', $type = '') {
	$size = in_array($size, ['big', 'middle', 'small']) ? $size : 'big';
	$uid = abs(intval($uid));
	$uid = sprintf('%09d', $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}
