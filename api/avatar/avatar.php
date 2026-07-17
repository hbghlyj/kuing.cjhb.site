<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

error_reporting(0);

$uid = $_GET['uid'] ?? 0;
$size = $_GET['size'] ?? '';
$random = $_GET['random'] ?? '';
$type = $_GET['type'] ?? '';
$check = $_GET['check_file_exists'] ?? '';
$ts = $_GET['ts'] ?? '';

$avatar = get_avatar($uid, $size, $type);
$avatar_file = dirname(__DIR__, 2).'/data/avatar/'.$avatar;
if(file_exists($avatar_file)) {
	if($check) {
		echo 1;
		exit;
	}
	$avatar_url = $avatar;
} else {
	if($check) {
		echo 0;
		exit;
	}
	http_response_code(404);
	exit;
}

if(empty($random)) {
	if(empty($ts)) {
		header('HTTP/1.1 301 Moved Permanently');
		header('Last-Modified: '.date('r'));
		header('Expires: '.date('r', time() + 86400));
	} else {
		$avatar_url .= '?ts='.filemtime($avatar_file);
	}
} else {
	$avatar_url .= '?random='.rand(1000, 9999);
}

$avatar_base = get_site_url().'/data/avatar';
header('Location: '.$avatar_base.'/'.$avatar_url);
exit;

function get_avatar($uid, $size = 'middle', $type = '') {
	$size = in_array($size, ['big', 'middle', 'small']) ? $size : 'middle';
	$uid = abs(intval($uid));
	$uid = sprintf('%09d', $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

function get_site_url() {
	$scheme = is_https() ? 'https' : 'http';
	$path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '/api/avatar/avatar.php');
	$path = preg_replace('#/api/avatar/avatar\.php$#', '', $path);
	return $scheme.'://'.$_SERVER['HTTP_HOST'].$path;
}

function is_https() {
	if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
		return true;
	}
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
		return true;
	}
	if(isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https') {
		return true;
	}
	if(isset($_SERVER['HTTP_FROM_HTTPS']) && strtolower($_SERVER['HTTP_FROM_HTTPS']) != 'off') {
		return true;
	}
	if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
		return true;
	}
	return false;
}
