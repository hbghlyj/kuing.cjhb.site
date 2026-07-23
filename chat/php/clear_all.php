<?php
$discuzRoot = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
chdir($discuzRoot);
require $discuzRoot.'source/class/class_core.php';
require $discuzRoot.'config/config_global.php';

$discuz = C::app();
$discuz->init_cron = false;
$discuz->init();

$conn = new mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Truncate chat database table
$conn->query("TRUNCATE TABLE chat");
echo "Chat table cleared. ";

// 2. Delete attached photos
$chatDir = $discuzRoot . 'data/attachment/chat';
if (is_dir($chatDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($chatDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
    echo "Chat photo attachments cleared.\n";
}

$conn->close();
?>
