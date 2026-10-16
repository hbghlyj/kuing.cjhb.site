<?php
// Each mode needs a separate process because IN_MOBILE is immutable.
if($argc === 1) {
    foreach(['desktop', 'touch', 'cookie', 'invalid', 'array'] as $case) {
        passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$case, $status);
        if($status) exit($status);
    }
    echo "Explicit URL mode overrides conflicting cookies; invalid values retain cookie behavior\n";
    exit;
}
define('IN_DISCUZ', true);
define('IS_ROBOT', false);
define('CHARSET', 'utf-8');
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class discuz_base {}
function getgpc($key) { return $_GET[$key] ?? null; }
function checkmobile() { return false; }
function dsetcookie(...$args) {}
function setglobal(...$args) {}
require 'source/class/discuz/discuz_application.php';
$case = $argv[1];
$_GET = match($case) {
    'desktop' => ['mobile' => 'no'],
    'touch' => ['mobile' => '2'],
    'invalid' => ['mobile' => 'invalid'],
    'array' => ['mobile' => ['no']],
    default => [],
};
$reflection = new ReflectionClass('discuz_application');
$app = $reflection->newInstanceWithoutConstructor();
$app->var = [
    'cookie' => ['mobile' => $case === 'touch' ? 'no' : '2'],
    'mobiletpl' => ['1' => 'touch', '2' => 'touch', 'yes' => 'touch'],
    'setting' => ['mobile' => ['allowmobile' => 1, 'mobileforward' => 1, 'legacy' => 0, 'mobilesimpletype' => 1, 'mobileregister' => 1], 'regstatus' => 1],
];
$reflection->getMethod('_init_mobile')->invoke($app);
if($case !== 'desktop') ob_end_clean();
if(defined('IN_MOBILE') !== ($case !== 'desktop') || ($case !== 'desktop' && IN_MOBILE !== '2')) {
    throw new RuntimeException('Incorrect template mode for '.$case);
}
