<?php
// Quick script to verify Discuz login status
define('DISCUZ_BRIDGE_DEBUG', true);
require __DIR__ . '/../src/core/constants.php';
require __DIR__ . '/../src/core/Helper/DiscuzBridge.php';
DocPHT\Core\Helper\DiscuzBridge::syncSession();
require __DIR__ . '/../config/config_global.php';
require_once __DIR__ . '/../source/class/class_core.php';

$discuz = C::app();
$discuz->init();

if (!empty($_G['uid'])) {
    echo "Logged in as {$_G['username']} (uid={$_G['uid']})\n";
} else {
    echo "Not logged in\n";
}
