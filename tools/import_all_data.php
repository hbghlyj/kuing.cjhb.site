<?php
define('IN_DISCUZ', true);
require './source/class/class_core.php';
$discuz = C::app();
$discuz->init();

$tablepre = $_config['db'][1]['tablepre'];
$dir = './source/i18n/SC_UTF8/install/lang_sql_install';

foreach(glob($dir.'/*.php') as $file) {
    $table = basename($file, '.php');
    $table = str_replace('table_', '', $table);
    $name = $tablepre.$table;

    // Check if the table exists
    $table_exists = false;
    try {
        DB::query("SELECT 1 FROM `$name` LIMIT 1");
        $table_exists = true;
    } catch(Exception $e) {
        // Table doesn't exist, we can ignore or report
        echo "Table $name does not exist, skipping.\n";
        continue;
    }

    if ($table_exists) {
        // Truncate table first to avoid duplicate key or inconsistent data
        DB::query("TRUNCATE TABLE `$name`");

        $data = [];
        require $file;
        echo "Importing $name (" . count($data) . " rows)... ";

        foreach($data as $row) {
            $fields = [];
            foreach($row as $field => $value) {
                if(is_array($value)) {
                    $value = serialize($value);
                }
                $value = addslashes($value);
                $fields[] = "`{$field}`='{$value}'";
            }
            $sql = "INSERT INTO `$name` SET " . implode(', ', $fields);
            DB::query($sql);
        }
        echo "done.\n";
    }
}

// Ensure the admin user and basic settings exist after importing
$password = md5('Testpassword123!');
DB::query("REPLACE INTO `{$tablepre}common_member` (uid, loginname, username, password, adminid, groupid, email, regdate) VALUES ('1', 'admin', 'admin', '$password', '1', '1', 'admin@admin.com', '".time()."');");
DB::query("REPLACE INTO `{$tablepre}common_member_count` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_status` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_field_forum` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_field_home` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_profile` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_setting` SET skey='siteurl', svalue='';");

require_once libfile('function/cache');
cleartemplatecache();
updatecache();
echo "All default table data successfully imported and cache updated!\n";
?>
