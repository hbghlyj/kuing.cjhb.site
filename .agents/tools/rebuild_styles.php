<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

$processUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
if($processUser !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as process user www-data.\n");
}

$root = dirname(__DIR__, 2);
chdir($root);

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.DIRECTORY_SEPARATOR.'index.php';
$_SERVER['DOCUMENT_ROOT'] = $root;

require_once './source/class/class_core.php';
$discuz = C::app();
$discuz->init_user = false;
$discuz->init_session = false;
$discuz->init_cron = false;
$discuz->init_misc = false;
$discuz->init();
$_G['siteurl'] = '/';
$_G['siteroot'] = '/';

$options = getopt('', ['action:', 'rebuild', 'verhash']);
$action = $options['action'] ?? (defined('APP_ACTION') ? APP_ACTION : 'all');
if(isset($options['rebuild']) && !isset($options['verhash'])) {
	$action = 'rebuild';
} elseif(isset($options['verhash']) && !isset($options['rebuild'])) {
	$action = 'verhash';
}

$doRebuild = in_array($action, ['all', 'both', 'rebuild'], true);
$doVerhash = in_array($action, ['all', 'both', 'verhash'], true);

if($doRebuild) {
	require_once './source/function/function_cache.php';
	updatecache('styles');
	echo 'Styles rebuilt (root-relative URLs)'."\n";
}

if($doVerhash) {
	if($_G['setting']['jspath'] === 'data/cache/') {
		require_once './source/function/cache/cache_setting.php';
		writetojscache();
		echo "JavaScript cache rebuilt\n";
	}

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
