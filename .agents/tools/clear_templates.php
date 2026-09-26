<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

$processUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
if($processUser !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as process user www-data.\n");
}

$options = getopt('', ['dry-run']);

// Discuz substitutes {lang key} at TEMPLATE COMPILE time, so an i18n edit is not
// visible until the compiled templates in data/template are rebuilt. Editing
// source/i18n/*/lang_*.php alone changes nothing on a live site.
//
// This is a different cache from the one rebuild_cache.php handles: that tool
// calls updatecache() for a named data cache (setting, forumlinks, members) and
// writes to data/cache. The compiled templates live in data/template as
// <LANG>_<STYLEID>_...tpl.php and are only removable via cleartemplatecache().
//
// Usage:
//   php .agents/tools/clear_templates.php            # clear
//   php .agents/tools/clear_templates.php --dry-run  # report only

$root = dirname(__DIR__, 2);
chdir($root);

$tplDir = $root . '/data/template';
$isDry  = isset($options['dry-run']);

function compiled_templates(string $dir): array {
	if(!is_dir($dir)) { return []; }
	$out = [];
	foreach(glob($dir.'/*.tpl.php') ?: [] as $f) { $out[] = $f; }
	return $out;
}

$before = compiled_templates($tplDir);
printf("compiled templates in data/template : %d\n", count($before));

// Report anything still holding a known-stale string, so an operator can tell
// whether a clear is needed at all.
$stale = [];
foreach($before as $f) {
	$body = (string)@file_get_contents($f);
	if(preg_match('/>Login<|>Login<\/a>|>Logout<|Register\/Login|Login\/Register/', $body)) {
		$stale[] = basename($f);
	}
}
printf("  holding a pre-rename label   : %d\n", count($stale));
if($stale) {
	echo "  e.g.\n";
	foreach(array_slice($stale, 0, 5) as $s) { echo "    $s\n"; }
}

if($isDry) {
	echo "\nDRY RUN - nothing removed.\n";
	exit(0);
}

if(!$before) {
	echo "Nothing to clear.\n";
	exit(0);
}

// Bootstrap exactly as rebuild_cache.php does, so cleartemplatecache() has the
// globals it expects. User/session/cron init are disabled: this only needs the
// template layer, and skipping them avoids touching live sessions.
$_SERVER['HTTP_HOST']       = 'localhost';
$_SERVER['SERVER_NAME']     = 'localhost';
$_SERVER['SERVER_PORT']     = '80';
$_SERVER['REQUEST_URI']     = '/index.php';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['DOCUMENT_ROOT']   = $root;
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';

require_once './source/class/class_core.php';
$discuz = C::app();
$discuz->init_user = false;
$discuz->init_session = false;
$discuz->init_cron = false;
$discuz->init_misc = false;
$discuz->init();

// cleartemplatecache() lives in function_cache.php, not in class_core.php, so it
// is not defined by the bootstrap above. rebuild_cache.php requires this file
// explicitly for the same reason.
require_once './source/function/function_cache.php';

if(!function_exists('cleartemplatecache')) {
	fwrite(STDERR, "cleartemplatecache() still undefined after requiring function_cache.php\n");
	exit(1);
}

cleartemplatecache();

$after = compiled_templates($tplDir);
printf("\ncompiled templates after clear    : %d\n", count($after));
printf("removed                           : %d\n", count($before) - count($after));
echo "They are regenerated on next page view.\n";
