<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cron_cleancreditlog.php 0 2024-04-24 00:00:00Z root $
 */

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

$maxday = 180;
$deltime = $_G['timestamp'] - $maxday * 86400;

$logids = DB::fetch_all('SELECT logid FROM %t WHERE dateline<%d', array('common_credit_log', $deltime), 'logid');
if($logids) {
        DB::delete('common_credit_log', DB::field('logid', array_keys($logids)));
        DB::delete('common_credit_log_field', DB::field('logid', array_keys($logids)));
}

DB::query('OPTIMIZE TABLE %t', array('common_credit_log'), true);
DB::query('OPTIMIZE TABLE %t', array('common_credit_log_field'), true);
