<?php
if(PHP_SAPI !== 'cli' || getenv('DISCUZ_SEED_DATABASE') !== '1') {
	fwrite(STDERR, "Refusing to seed the database. This script is for automated initial setup only.\n");
	exit(1);
}

require './source/class/class_core.php';
$discuz = C::app();
$discuz->init();

$tablepre = $_G['config']['db'][1]['tablepre'];
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
require_once libfile('function/nativeuser');
$adminPassword = 'Testpassword123!';
$adminPasswordHash = native_user_generate_password($adminPassword);
$passwordMd5 = md5($adminPassword);
DB::query("REPLACE INTO `{$tablepre}common_member` (uid, loginname, username, password, adminid, groupid, email, regdate) VALUES ('1', 'admin', 'admin', '$passwordMd5', '1', '1', 'admin@admin.com', '".time()."');");
DB::query("REPLACE INTO `{$tablepre}common_member_count` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_status` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_field_forum` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_field_home` SET uid='1';");
DB::query("REPLACE INTO `{$tablepre}common_member_profile` SET uid='1', fields='{}';");
// Populate common_member_auth so native_user_login can verify the admin password
// (native_user_login reads exclusively from common_member_auth, not common_member.password)
C::t('common_member_auth')->upsert(1, $adminPasswordHash, '', '');
DB::query("REPLACE INTO `{$tablepre}common_setting` SET skey='siteurl', svalue='';");
DB::query("REPLACE INTO `{$tablepre}common_setting` SET skey='pmstatus', svalue='1';");

require_once libfile('function/cache');
cleartemplatecache();
updatecache();
echo "All default table data successfully imported and cache updated!\n";
?>
