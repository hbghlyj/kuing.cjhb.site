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

// Requiring class_core.php WIPES the global scope: every variable assigned above
// is gone by the time init() returns, so $root/$tplDir/$before are null in any
// code that runs after the bootstrap. Constants survive. Same reason
// rebuild_cache.php passes DZ_BUILD_HOST / DZ_BUILD_CACHE as constants.
define('DZ_CLEAR_ROOT', $root);
define('DZ_CLEAR_TPLDIR', $root . '/data/template');
define('DZ_CLEAR_DRY', isset($options['dry-run']));

function compiled_templates(string $dir): array {
	if(!is_dir($dir)) { return []; }
	$out = [];
	foreach(glob($dir.'/*.tpl.php') ?: [] as $f) { $out[] = $f; }
	return $out;
}

$before = compiled_templates(DZ_CLEAR_TPLDIR);
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

if(DZ_CLEAR_DRY) {
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

// $root/$tplDir/$before are all null here: class_core.php cleared them. Use the
// constants, and re-glob the directory because cleartemplatecache() removes it.
$after  = compiled_templates(DZ_CLEAR_TPLDIR);
$beforeCount = count(glob(DZ_CLEAR_TPLDIR . '/*.tpl.php') ?: []);
printf("\ncompiled templates after clear    : %d\n", count($after));
printf("directory still present          : %s\n", is_dir(DZ_CLEAR_TPLDIR) ? 'yes' : 'no (recreated on next request)');
printf("note                             : %d were present before this run\n", $beforeCount);
echo "They are regenerated on next page view.\n";
