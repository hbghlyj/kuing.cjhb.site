<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

if(get_current_user() !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as script owner www-data.\n");
}

$root = dirname(__DIR__);
chdir($root);

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';
$_SERVER['DOCUMENT_ROOT'] = $root;

require './source/class/class_core.php';
$discuz = C::app();
$discuz->init_user = false;
$discuz->init_session = false;
$discuz->init_cron = false;
$discuz->init_misc = false;
$discuz->init();

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
	exit("No style caches were found. Rebuild styles first.\n");
}

echo "Updated VERHASH to {$verhash} in {$updated} style caches.\n";
