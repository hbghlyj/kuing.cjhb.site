<?php
if(PHP_SAPI !== 'cli') exit('CLI only');
chdir(dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = 'kuing.cjhb.site';
$_SERVER['SERVER_NAME'] = 'kuing.cjhb.site';
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require './source/class/class_core.php';
$app = C::app();
$app->init_user = false;
$app->init_session = false;
$app->init_cron = false;
$app->init_misc = false;
$app->init();
DB::delete('common_cron', ['filename' => 'cron_tag_daily.php']);
if(DB::fetch_first('SHOW COLUMNS FROM %t LIKE %s', ['common_tag', 'hot_score'])) {
    DB::query('ALTER TABLE %t DROP COLUMN hot_score', ['common_tag']);
}
if(DB::fetch_first('SHOW COLUMNS FROM %t LIKE %s', ['common_tag', 'hot_score'])) {
    throw new RuntimeException('Tag score column still exists');
}
echo "Retired tag score column and daily score cron removed\n";
