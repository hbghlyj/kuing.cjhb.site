<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

$options = getopt('', ['host:']);
$host = $options['host'] ?? '';
if(!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
	exit("Usage: php tools/rebuild_styles.php --host=example.com\n");
}

chdir(dirname(__DIR__));
$_SERVER['HTTP_HOST'] = $host;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
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

echo "Styles rebuilt for https://{$host}/\n";
