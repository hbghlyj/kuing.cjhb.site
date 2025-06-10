<?php
require __DIR__ . '/../config/config_global.php';
define("IN_DISCUZ", true);
require_once __DIR__ . '/../source/function/function_core.php';

$conf = $_config['db'][1];
$mysqli = new mysqli($conf['dbhost'], $conf['dbuser'], $conf['dbpw'], $conf['dbname']);
if ($mysqli->connect_errno) {
    die("DB connect error: {$mysqli->connect_error}\n");
}
$tablepre = $conf['tablepre'];

function fetch_setting($mysqli, $tablepre, $key) {
    $key = $mysqli->real_escape_string($key);
    $sql = "SELECT svalue FROM {$tablepre}common_setting WHERE skey='$key'";
    $res = $mysqli->query($sql);
    if (!$res) {
        echo "Query error: " . $mysqli->error . "\n";
        return null;
    }
    $row = $res->fetch_assoc();
    return $row ? $row['svalue'] : null;
}

$domain_raw = fetch_setting($mysqli, $tablepre, 'domain');
// fetch footer navigation entries directly from the common_nav table
function fetch_footernavs($mysqli, $tablepre) {
    $sql = "SELECT * FROM {$tablepre}common_nav WHERE navtype=1 ORDER BY displayorder";
    $res = $mysqli->query($sql);
    if (!$res) {
        echo "Query error: " . $mysqli->error . "\n";
        return [];
    }
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$footernavs_rows = fetch_footernavs($mysqli, $tablepre);

echo "domain raw: ";
var_dump($domain_raw);
$domain_arr = dunserialize($domain_raw);

echo "domain after dunserialize:\n";
var_dump($domain_arr);

if (isset($domain_arr['app'])) {
    echo "domain.app type: " . gettype($domain_arr['app']) . "\n";
    var_dump($domain_arr['app']);
}

echo "footernavs from common_nav:\n";
var_dump($footernavs_rows);

?>
