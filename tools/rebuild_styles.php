<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

$processUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
if($processUser !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as process user www-data.\n");
}

$options = getopt('', ['host:']);
$targetHost = $options['host'] ?? '';
if(!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $targetHost)) {
	exit("Usage: php tools/rebuild_styles.php --host=example.com\n");
}
define('STYLE_REBUILD_HOST', $targetHost);

$root = dirname(__DIR__);
chdir($root);

// The CLI entry point is tools/rebuild_styles.php, but style URLs must be
// generated as if the site's root index.php handled the request.
$_SERVER['HTTP_HOST'] = $targetHost;
$_SERVER['SERVER_NAME'] = $targetHost;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

require './source/class/class_core.php';
$discuz = C::app();
$discuz->init_user = false;
$discuz->init_session = false;
$discuz->init_cron = false;
$discuz->init_misc = false;
$discuz->init();

require_once './source/function/function_cache.php';
updatecache('styles');

echo 'Styles rebuilt for https://'.STYLE_REBUILD_HOST."/\n";
