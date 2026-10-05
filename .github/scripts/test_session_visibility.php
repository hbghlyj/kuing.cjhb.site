<?php
define('IN_DISCUZ', true);
class discuz_table {
    protected $_table;
    protected $_pk;
    public function __construct() {}
}
class DB {
    public static $rows = [
        ['uid' => 0, 'invisible' => 1],
        ['uid' => 0, 'invisible' => 0],
        ['uid' => 12, 'invisible' => 1],
        ['uid' => 13, 'invisible' => 0],
    ];
    public static function result_first($sql, $params) {
        return count(array_filter(self::$rows, fn($row) => $row['invisible'] == $params[1] && (!str_contains($sql, 'uid>0') || $row['uid'] > 0)));
    }
}
$ajax = file_get_contents(__DIR__.'/../../source/app/forum/child/ajax/getOnlineUserListHtml.php');
if(!str_contains($ajax, '$membercount = C::app()->session->count(1);') || str_contains($ajax, '$membercount + $invisiblecount + $guestcount')) {
    throw new RuntimeException('AJAX totals must include invisible members once, independently of rendered rows');
}
require __DIR__.'/../../source/class/table/table_common_session.php';
$table = new table_common_session();
foreach([[1, true, 1], [1, false, 2], [0, false, 2], [0, true, 1]] as [$flag, $membersOnly, $expected]) {
    if($table->count_invisible($flag, $membersOnly) !== $expected) {
        throw new RuntimeException('Session visibility count mismatch');
    }
}
DB::$rows = [['uid' => 0, 'invisible' => 1]];
if($table->count_invisible(1, true) !== 0) {
    throw new RuntimeException('An invisible guest must not count as an invisible member');
}
echo "Session visibility counts passed\n";
foreach(['index/forum.php', 'ajax/getOnlineUserListHtml.php'] as $file) {
    $source = file_get_contents(__DIR__.'/../../source/app/forum/child/'.$file);
    $update = strpos($source, 'updatesession();');
    $count = strpos($source, '->count');
    if($update === false || $count === false || $update > $count) {
        throw new RuntimeException('Online counts must follow session refresh: '.$file);
    }
}
