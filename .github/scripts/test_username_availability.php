<?php
define('IN_DISCUZ', true);
class discuz_table_archive {
    protected $_table, $_pk, $_pre_cache_key;
    protected $membersplit = true;
    public function __construct() {}
}
class DB {
    public static function result_first($sql, $data) {
        $field = str_contains($sql, 'loginname=%s') ? 'loginname' : 'username';
        $rows = [
            'common_member' => [['uid' => 1, 'username' => 'ActiveUser', 'loginname' => 'UsedLogin']],
            'common_member_archive' => [['uid' => 2, 'username' => 'ArchivedUser', 'loginname' => 'ArchivedLogin']],
        ];
        foreach($rows[$data[0]] ?? [] as $row) {
            if($row[$field] === $data[1]) return $row['uid'];
        }
        return 0;
    }
}
class C {
    public static function t($table) {
        if($table !== 'common_member_archive') throw new RuntimeException('Unexpected table: '.$table);
        return table_common_member_archive::t();
    }
}
class table_common_member_username_history {
    public static function t() { return new self(); }
    public function fetch($name) { return $name === 'OldName' ? ['uid' => 3] : []; }
}
function dstrlen($value) { return strlen($value); }
function censor($value, ...$args) { return $value; }
require 'source/class/table/table_common_member.php';
require 'source/class/table/table_common_member_archive.php';
require 'source/function/function_nativeuser.php';
foreach(['ActiveUser', 'ArchivedUser', 'UsedLogin', 'ArchivedLogin', 'OldName'] as $name) {
    if(native_user_checkname($name) !== -3) throw new RuntimeException('Existing identity accepted: '.$name);
}
if(native_user_checkname('FreshUser') !== 1) throw new RuntimeException('Available username rejected');
echo "Native username checks cover active, archived, login and historical identities\n";
