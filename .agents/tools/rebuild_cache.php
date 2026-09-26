<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

$processUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
if($processUser !== 'www-data' && !getenv('GITHUB_ACTIONS')) {
	exit("This tool must be run as process user www-data.\n");
}

$options = getopt('', ['cachename:', 'host:', 'scheme:']);
$cachename = $options['cachename'] ?? '';

// Caches bake absolute URLs. cache_setting.php:1183 builds nav markup with
// $_G['siteurl'], so building the 'setting' cache under a placeholder host
// permanently stores http://localhost/... in the rendered nav code. The host
// defaults to the real site and must never fall back to localhost.
$host = $options['host'] ?? (getenv('SITE_HOST') ?: 'kuing.cjhb.site');
$scheme = $options['scheme'] ?? (getenv('SITE_SCHEME') ?: 'https');
if(!$host || $host === 'localhost' || $host === '127.0.0.1') {
	fwrite(STDERR, "Refusing to build caches under host '".$host."'.\n");
	fwrite(STDERR, "The 'setting' cache bakes absolute URLs from \$_G['siteurl']; a placeholder\n");
	fwrite(STDERR, "host would store http://localhost/... in the rendered nav code.\n");
	fwrite(STDERR, "Pass --host=kuing.cjhb.site (the default) if this is wrong.\n");
	exit(1);
}

$root = dirname(__DIR__, 2);
chdir($root);

$_SERVER['HTTP_HOST'] = $host;
$_SERVER['SERVER_NAME'] = $host;
$_SERVER['SERVER_PORT'] = $scheme === 'https' ? '443' : '80';
$_SERVER['HTTPS'] = $scheme === 'https' ? 'on' : '';
$_SERVER['REQUEST_SCHEME'] = $scheme;
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.DIRECTORY_SEPARATOR.'index.php';
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once './source/class/class_core.php';
$discuz = C::app();
$discuz->init_user = false;
$discuz->init_session = false;
$discuz->init_cron = false;
$discuz->init_misc = false;
$discuz->init();
$_G['siteroot'] = '/';

// Guard: verify what Discuz actually resolved before anything is written.
$expected = $scheme.'://'.$host.'/';
echo 'host          : '.$host."\n";
echo 'scheme        : '.$scheme."\n";
echo '$_G[siteurl]  : '.$_G['siteurl']."\n";
echo 'expected      : '.$expected."\n";
if($_G['siteurl'] !== $expected) {
	fwrite(STDERR, "\nABORT: \$_G['siteurl'] is '".$_G['siteurl']."' but should be '".$expected."'.\n");
	fwrite(STDERR, "Building a cache now would store that wrong base URL. Fix the host and retry.\n");
	exit(1);
}

if(!$cachename) {
	echo "\nUsage: php .agents/tools/rebuild_cache.php --cachename=forumlinks --host=kuing.cjhb.site\n";
	exit(0);
}

require_once './source/function/function_cache.php';
echo "\nRebuilding cache: ".$cachename."\n";
updatecache($cachename);
echo "Done.\n";
