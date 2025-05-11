<?php
require './source/class/class_core.php';
header("Content-Type: text/plain");
$discuz = C::app();
$discuz->init();
foreach(range(0,9) as $i)
    foreach(DB::fetch_all('SELECT `attachment`  FROM '.DB::table('forum_attachment_'.$i)) as $j)
        foreach($j as $k)
            print($k."\n");