<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function stat_moderation_chart($actions, $labels, $otherLabel) {
	$counts = [];
	foreach((array)$actions as $key => $action) {
		if($key !== 'total' && is_array($action) && ($count = intval($action['count'] ?? 0)) > 0) {
			$counts[$key] = $count;
		}
	}
	arsort($counts);
	$top = array_slice($counts, 0, 15, true);
	$categories = [];
	foreach($top as $key => $count) {
		$categories[] = $labels[$key] ?? (string)$key;
	}
	$values = array_values($top);
	if(count($counts) > 15) {
		$categories[] = $otherLabel;
		$values[] = array_sum(array_slice($counts, 15));
	}
	return ['categories' => $categories, 'values' => $values];
}

function updatestat($type, $primary = 0, $num = 1) {
	$uid = getglobal('uid');
	$updatestat = getglobal('setting/updatestat');
	if(empty($uid) || empty($updatestat)) return false;
	table_common_stat::t()->updatestat($uid, $type, $primary, $num);
}

