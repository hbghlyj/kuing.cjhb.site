<?php
define('IN_DISCUZ', true);
define('DISCUZ_ROOT', dirname(__DIR__, 2).'/');
define('PLUGIN_ROOT', './source/plugin/');
$source = file_get_contents(DISCUZ_ROOT.'source/function/function_plugin.php');
$start = strpos($source, 'function issafepath(');
$end = strpos($source, "\nfunction ", $start + 1);
eval(substr($source, $start, $end - $start));
function check_port($condition, $message) {
    if(!$condition) throw new RuntimeException($message);
}
$plugin = DISCUZ_ROOT.'source/plugin/googleconnect/connect.class.php';
check_port(issafepath($plugin) === str_replace('\\', '/', realpath($plugin)), 'Plugin PHP path rejected');
check_port(issafepath(DISCUZ_ROOT.'source/discuz_version.php') === false, 'Outside plugin root accepted');
check_port(issafepath(DISCUZ_ROOT.'source/plugin/googleconnect/../../discuz_version.php') === false, 'Traversal outside plugin root accepted');
check_port(issafepath(DISCUZ_ROOT.'source/plugin/googleconnect') === false, 'Directory accepted as script');

$source = file_get_contents(DISCUZ_ROOT.'source/function/function_core.php');
$start = strpos($source, 'function adshow(');
$end = strpos($source, "\nfunction ", $start + 1);
eval(substr($source, $start, $end - $start));
function getgpc($name) { return ''; }
function loadcache($name) {}
$_G = ['group' => ['closead' => 0], 'cache' => ['advs' => []], 'config' => []];
check_port(adshow('addon_unregistered', false) === '', 'Read-only ad lookup did not reject missing slot');

class discuz_table {
    protected $_table;
    protected $_pk;
    public function __construct() {}
}
class DB {
    static $consumed = false;
    static $affected = 0;
    static $condition;
    static function field($name, $value) { return $name.'='.intval($value); }
    static function update($table, $data, $condition, $unbuffered) {
        self::$condition = $condition;
        self::$affected = self::$consumed ? 0 : 1;
        self::$consumed = true;
    }
    static function affected_rows() { return self::$affected; }
}
require DISCUZ_ROOT.'source/class/table/table_common_invite.php';
$invite = new table_common_invite;
check_port($invite->update_consume(1, 2, 'first') === 1, 'First invite consumption failed');
check_port(str_contains(DB::$condition, 'fuid=0'), 'Invite update lacks atomic unused condition');
check_port($invite->update_consume(1, 3, 'second') === 0, 'Invite consumed twice');
check_port($invite->update_consume(0, 3, 'invalid') === 0, 'Invalid invite consumed');
echo "Upstream plugin-path, read-only ad and invite regressions passed\n";
