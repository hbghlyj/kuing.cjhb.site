<?php
define('IN_DISCUZ', true);
function verify($condition, $message) {
    if(!$condition) throw new RuntimeException($message);
}
class discuz_table {
    protected $_table;
    protected $_pk;
    public function __construct() {}
}
class DB {
    static $rows = [];
    static $affected = 0;
    static $failures = 0;
    static $calls = 0;
    static function errno() { return 1213; }
    static function affected_rows() { return self::$affected; }
    static function query($sql, $args, $silent = false) {
        self::$calls++;
        if(self::$failures > 0) {
            self::$failures--;
            if(!$silent) throw new RuntimeException('Unrecovered deadlock');
            return false;
        }
        self::$affected = 0;
        $key = $args[1];
        if(str_starts_with($sql, 'INSERT IGNORE')) {
            if(!isset(self::$rows[$key])) {
                self::$rows[$key] = $args[2];
                self::$affected = 1;
            }
            return 0; // No auto-increment ID: successful INSERT returns zero.
        }
        verify(!str_contains($sql, ' OR '), 'Lock cleanup must not delete unrelated locks');
        if(isset(self::$rows[$key]) && (count($args) === 2 || self::$rows[$key] < $args[2])) {
            unset(self::$rows[$key]);
            self::$affected = 1;
        }
        return self::$affected;
    }
}
require __DIR__.'/../../source/class/table/table_common_process.php';
$table = table_common_process::t();
verify($table->acquire_process('a', 200), 'First acquisition failed');
verify(!$table->acquire_process('a', 300), 'Competing lock overwrote its owner');
verify(DB::$rows['a'] === 200, 'Lock expiry changed on contention');
DB::$rows['unrelated'] = 1;
$table->delete_process('a', 100, true);
verify(isset(DB::$rows['a']), 'Stale expiry cleanup deleted refreshed lock');
$table->delete_process('a', 201, true);
verify(!isset(DB::$rows['a']) && isset(DB::$rows['unrelated']), 'Expiry cleanup was not scoped');
DB::$failures = 2;
verify($table->acquire_process('b', 400), 'Deadlock retry did not recover');
$table->delete_process('b', 0);
verify(!isset(DB::$rows['b']), 'Unlock failed');
DB::$failures = 4;
try {
    $table->acquire_process('c', 400);
    throw new RuntimeException('Persistent database errors were swallowed');
} catch(RuntimeException $error) {
    verify($error->getMessage() === 'Unrecovered deadlock', 'Unexpected failure');
}

// Execute the real endpoint while replacing only its external dependencies.
function libfile($name) { return __FILE__; }
function loadcache($name) {}
function getattach(...$args) { return ['attachs' => []]; }
function template($name) { return __DIR__.'/fixtures/empty_template.php'; }
function dexit() { throw new RuntimeException('endpoint complete'); }
$_GET = ['pid' => 1, 'posttime' => 1, 'aids' => ''];
$_G = ['group' => [], 'setting' => ['extcredits' => [], 'creditstrans' => 0]];
try {
    include __DIR__.'/../../source/app/forum/child/ajax/attachlist.php';
} catch(RuntimeException $error) {
    verify($error->getMessage() === 'endpoint complete', 'Attachment endpoint failed');
}
verify(is_array($attachlist) && count($attachlist) === 0, 'Empty attachments must remain countable');

// Isolate the real helper: do not execute the cron and its database mutations.
$source = file_get_contents(__DIR__.'/../../source/child/cron/cron_cleanup_daily.php');
$helper = substr($source, strpos($source, 'function removedir('));
eval('namespace CleanupFixture; function opendir($path) { return false; } '.$helper);
verify(\CleanupFixture\removedir(__DIR__) === false, 'Failed directory open must not reach readdir');
eval('namespace CleanupNormal; '.$helper);
$directory = sys_get_temp_dir().'/cleanup-'.bin2hex(random_bytes(8));
mkdir($directory);
mkdir($directory.'/nested');
verify(\CleanupNormal\removedir($directory), 'Nested directory cleanup failed');
verify(!is_dir($directory), 'Directory was not removed');
echo "System error regressions passed\n";
