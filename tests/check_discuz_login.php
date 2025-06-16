<?php
// Quick script to verify Discuz login status
require __DIR__ . '/../config/config_global.php';
define('IN_DISCUZ', true);
require_once __DIR__ . '/../source/class/class_core.php';

$discuz = C::app();
$discuz->init();

if (!empty($_G['uid'])) {
    echo "Logged in as {$_G['username']} (uid={$_G['uid']})\n";
} else {
    echo "Not logged in\n";
}
