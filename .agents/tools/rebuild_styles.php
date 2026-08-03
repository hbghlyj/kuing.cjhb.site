<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

$processUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
if($processUser !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as process user www-data.\n");
}

$options = getopt('', ['host:', 'action:', 'rebuild', 'verhash']);
$targetHost = $options['host'] ?? 'localhost';
if(!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $targetHost)) {
	exit("Usage: php .agents/tools/rebuild_styles.php [--host=example.com] [--action=all|rebuild|verhash]\n");
}

$action = $options['action'] ?? 'all';
if(isset($options['rebuild']) && !isset($options['verhash'])) {
	$action = 'rebuild';
} elseif(isset($options['verhash']) && !isset($options['rebuild'])) {
	$action = 'verhash';
}

$doRebuild = in_array($action, ['all', 'both', 'rebuild'], true);
$doVerhash = in_array($action, ['all', 'both', 'verhash'], true);

if(!defined('STYLE_REBUILD_HOST')) {
	define('STYLE_REBUILD_HOST', $targetHost);
}

$root = dirname(__DIR__, 2);
chdir($root);

$_SERVER['HTTP_HOST'] = $targetHost;
$_SERVER['SERVER_NAME'] = $targetHost;
$_SERVER['REQUEST_URI'] = '/';
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

if($doRebuild) {
	require_once './source/function/function_cache.php';
	updatecache('styles');
	echo 'Styles rebuilt for https://'.STYLE_REBUILD_HOST."/\n";
}

if($doVerhash) {
	$cacheNames = ['style_default'];
	foreach(table_common_style::t()->fetch_all_data() as $style) {
		$cacheNames[] = 'style_'.$style['styleid'];
	}

	$styles = table_common_syscache::t()->fetch_all_syscache($cacheNames, true);
	$currentHashes = [];
	foreach($styles as $style) {
		if(is_array($style) && !empty($style['verhash'])) {
			$currentHashes[] = $style['verhash'];
		}
	}

	do {
		$verhash = random(3);
	} while(in_array($verhash, $currentHashes, true));

	$updated = 0;
	foreach($styles as $cacheName => $style) {
		if(!is_array($style)) {
			continue;
		}
		$style['verhash'] = $verhash;
		savecache($cacheName, $style);
		$updated++;
	}

	if(!$updated) {
		echo "No style caches were found to update verhash.\n";
	} else {
		echo "Updated VERHASH to {$verhash} in {$updated} style caches.\n";
	}
}
