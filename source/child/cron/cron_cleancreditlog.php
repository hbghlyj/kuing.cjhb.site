<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$maxday = 180;
$deltime = $_G['timestamp'] - $maxday * 86400;

table_common_credit_log::t()->delete_by_removetime($deltime);
table_common_credit_log_field::t()->delete_by_removetime($deltime);

DB::query('OPTIMIZE TABLE %t', ['common_credit_log'], true);
DB::query('OPTIMIZE TABLE %t', ['common_credit_log_field'], true);
