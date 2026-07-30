<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$mergeviews = [];
foreach(table_forum_threadaddviews::t()->fetch_all_order_by_tid(0, 5000) as $tid => $addview) {
	$views = intval($addview['addviews']);
	if($views > 0) {
		$mergeviews[$tid] = $views;
	}
}
if($mergeviews) {
	table_forum_thread::t()->increase_views_by_tid_map($mergeviews, 0, true);
	table_forum_threadaddviews::t()->delete(array_keys($mergeviews));
}

