<?php

$source = file_get_contents(__DIR__.'/../../source/app/misc/child/patch/pluginnotice.php');
$start = strpos($source, '$newversion = 0;');
$end = strpos($source, "include template('common/header_ajax');", $start);
if($start === false || $end === false) throw new RuntimeException('Missing upgrade notice counter');
$counter = substr($source, $start, $end - $start);
foreach([
    [['removed.plugin' => ':2:2'], 0],
    [['installed.plugin' => ':2:', 'removed.plugin' => ':2:2'], 1],
    [['installed.plugin' => '::2'], 1],
    [['installed.plugin' => '::1'], 0],
] as [$checkresult, $expected]) {
    $vers = ['installed.plugin' => '1'];
    eval($counter);
    if($newversion !== $expected) throw new RuntimeException('Upgrade notice counted an uninstalled plugin or lost a valid update');
}
echo "Upgrade notices ignore cached entries for uninstalled plugins\n";
