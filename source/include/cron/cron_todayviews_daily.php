<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cron_todayviews_daily.php 26812 2011-12-23 08:21:29Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$mergeviews = array();
foreach(C::t('forum_threadaddviews')->fetch_all_order_by_tid(0, 5000) as $tid => $addview) {
	$views = intval($addview['addviews']);
	if($views > 0) {
		$mergeviews[$tid] = $views;
	}
}
if($mergeviews) {
	C::t('forum_thread')->increase_views_by_tid_map($mergeviews, 0, true);
	C::t('forum_threadaddviews')->delete(array_keys($mergeviews));
}

?>
