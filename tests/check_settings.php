<?php
require __DIR__ . '/../config/config_global.php';
require_once __DIR__ . '/../source/class/class_core.php';

$discuz = C::app();
$discuz->init();

print "domain.app type: " . gettype($_G['setting']['domain']['app']) . "\n";
print_r($_G['setting']['domain']['app']);
print "\nfooternavs type: " . gettype($_G['setting']['footernavs']) . "\n";
print_r($_G['setting']['footernavs']);
