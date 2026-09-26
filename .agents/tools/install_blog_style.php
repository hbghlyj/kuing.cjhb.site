<?php

if(PHP_SAPI !== 'cli') {
	exit("CLI only.\n");
}

$processUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
if($processUser !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as process user www-data.\n");
}

$root = dirname(__DIR__, 2);
chdir($root);
define('IN_ADMINCP', true);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';
$_SERVER['DOCUMENT_ROOT'] = $root;

require_once './source/class/class_core.php';
$discuz = C::app();
$discuz->init_user = false;
$discuz->init_session = false;
$discuz->init_cron = false;
$discuz->init_misc = false;
$discuz->init();

foreach(table_common_style::t()->fetch_all_data(true, 1) as $style) {
	if($style['directory'] === './template/discuz_blog') {
		require_once './source/function/function_cache.php';
		updatecache(['setting', 'styles']);
		echo "Blog style already installed: {$style['styleid']}\n";
		exit(0);
	}
}

require_once libfile('function/admincp');
require_once libfile('function/importdata');
import_styles(1, 'discuz_blog', 0, 1, 0, 0, false);

foreach(table_common_style::t()->fetch_all_data(true, 1) as $style) {
	if($style['directory'] === './template/discuz_blog') {
		echo "Blog style installed: {$style['styleid']}\n";
		exit(0);
	}
}
throw new RuntimeException('Blog style import did not create an available style.');
