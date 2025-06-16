<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/core/Helper/DiscuzBridge.php';
DocPHT\Core\Helper\DiscuzBridge::syncSession();
if (isset($_SESSION['Active']) && $_SESSION['Username']) {
    echo "Logged in as {$_SESSION['Username']}\n";
} else {
    echo "Not logged in\n";
}