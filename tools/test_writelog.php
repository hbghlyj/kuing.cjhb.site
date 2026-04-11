<?php

define('IN_DISCUZ', true);
define('DISCUZ_ROOT', dirname(__DIR__).'/');
define('DISCUZ_DATA', DISCUZ_ROOT.'data/');
define('TIMESTAMP', time());

$_G = [
	'setting' => [
		'timeoffset' => 0,
	],
];

function dgmdate($timestamp, $format = 'dt', $timeoffset = 9999, $uformat = '') {
	$timestamp = intval($timestamp);
	$timeoffset = $timeoffset == 9999 ? 0 : intval($timeoffset);
	return gmdate($format, $timestamp + $timeoffset * 3600);
}

function dmkdir($dir, $mode = 0777, $makeindex = true) {
	if(is_dir($dir)) {
		return true;
	}
	return mkdir($dir, $mode, true);
}

require_once DISCUZ_ROOT.'source/class/helper/helper_log.php';

function writelog($file, $log) {
	helper_log::writelog($file, $log);
}

function test_writelog_process_user() {
	if(function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
		$user = posix_getpwuid(posix_geteuid());
		if(!empty($user['name'])) {
			return $user['name'];
		}
	}
	return get_current_user();
}

function test_writelog_owner_name($path) {
	if(!file_exists($path)) {
		return null;
	}
	$owner = fileowner($path);
	if(function_exists('posix_getpwuid')) {
		$user = posix_getpwuid($owner);
		if(!empty($user['name'])) {
			return $user['name'];
		}
	}
	return $owner;
}

function test_writelog_group_name($path) {
	if(!file_exists($path)) {
		return null;
	}
	$group = filegroup($path);
	if(function_exists('posix_getgrgid')) {
		$groupInfo = posix_getgrgid($group);
		if(!empty($groupInfo['name'])) {
			return $groupInfo['name'];
		}
	}
	return $group;
}

$file = 'writelog_test';
$processUser = test_writelog_process_user();
$marker = implode("\t", [
	'test_writelog',
	date('c', TIMESTAMP),
	'user='.$processUser,
	'pid='.(function_exists('getmypid') ? getmypid() : 'unknown'),
]);

writelog($file, $marker);

$yearmonth = dgmdate(TIMESTAMP, 'Ym', $_G['setting']['timeoffset']);
$logfile = DISCUZ_DATA.'./log/'.$yearmonth.'_'.$file.'.php';

clearstatcache(true, $logfile);

$exists = is_file($logfile);
$logContains = false;
$lastLine = null;

if($exists && is_readable($logfile)) {
	$content = file_get_contents($logfile);
	if($content !== false) {
		$logContains = str_contains($content, $marker);
		$lines = preg_split("/\r\n|\n|\r/", trim($content));
		$lastLine = end($lines);
	}
}

$result = [
	'ok' => $exists && $logContains,
	'process_user' => $processUser,
	'logfile' => $logfile,
	'logfile_exists' => $exists,
	'logfile_owner' => test_writelog_owner_name($logfile),
	'logfile_group' => test_writelog_group_name($logfile),
	'marker' => $marker,
	'last_line' => $lastLine,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

exit($result['ok'] ? 0 : 1);
