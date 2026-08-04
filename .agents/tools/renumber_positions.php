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

$argv = $_SERVER['argv'] ?? [];
$rawHost = 'localhost';
foreach($argv as $arg) {
	if(preg_match('/^--host=(.+)$/', $arg, $m)) {
		$rawHost = $m[1];
	}
}

$_SERVER['HTTP_HOST'] = $rawHost;
$_SERVER['SERVER_NAME'] = $rawHost;
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
$_G['siteurl'] = 'http://'.$rawHost.'/';
$_G['siteroot'] = '/';

$options = getopt('', ['host:', 'dry-run']);
$targetHost = $options['host'] ?? $rawHost;
if(!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $targetHost)) {
	exit("Usage: php .agents/tools/renumber_positions.php [--host=example.com] [--dry-run]\n");
}
$dryRun = isset($options['dry-run']);

loadcache('posttableids');
$posttableids = (array)($_G['cache']['posttableids'] ?? []);
foreach(DB::fetch_all('SELECT posttableid, COUNT(*) AS c FROM %t GROUP BY posttableid', ['forum_thread']) as $row) {
	$ptid = intval($row['posttableid']);
	if(!in_array($ptid, $posttableids, true)) {
		$posttableids[] = $ptid;
	}
}
sort($posttableids);

$affected = [];
foreach($posttableids as $ptid) {
	$table = table_forum_post::getposttable($ptid, true);
	$rows = DB::fetch_all(
		'SELECT p.tid FROM '.$table.' p JOIN %t t ON t.tid=p.tid '.
		'GROUP BY p.tid '.
		'HAVING COUNT(*) != MAX(p.position) OR COUNT(DISTINCT p.position) != COUNT(*) OR MAX(p.position) != MAX(t.maxposition)',
		['forum_thread']
	);
	foreach($rows as $row) {
		$affected[intval($row['tid'])] = 1;
	}
}

foreach(DB::fetch_all('SELECT tid FROM %t', ['forum_threaddisablepos']) as $row) {
	$affected[intval($row['tid'])] = 1;
}

$affected = array_keys($affected);
sort($affected);
printf("Post tables checked: %s\n", implode(', ', array_map(function($ptid) {
	return table_forum_post::getposttable($ptid, true);
}, $posttableids)));
printf("Affected threads: %d\n", count($affected));

if($dryRun) {
	foreach($affected as $tid) {
		printf("%d\n", $tid);
	}
	exit(0);
}

$renumbered = 0;
foreach($affected as $tid) {
	table_forum_post::t()->renumber_positions_by_tid($tid);
	$renumbered++;
	if($renumbered % 500 === 0) {
		printf("...%d renumbered\n", $renumbered);
	}
}
table_forum_threaddisablepos::t()->truncate();
printf("Done. Renumbered %d threads, cleared forum_threaddisablepos.\n", $renumbered);
