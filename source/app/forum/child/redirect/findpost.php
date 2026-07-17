<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$post = $thread = [];

if($ptid) {
	$thread = get_thread_by_tid($ptid);
}

if($pid) {

	if($thread) {
		$post = table_forum_post::t()->fetch_post($thread['posttableid'], $pid);
	} else {
		$post = get_post_by_pid($pid);
	}

	if($post && empty($thread)) {
		$thread = get_thread_by_tid($post['tid']);
	}
}

if(empty($thread)) {
	showmessage('thread_nonexistence');
} else {
	$tid = $thread['tid'];
}

if(empty($pid)) {

	if($postno) {
		if(getstatus($thread['status'], 3)) {
			$rowarr = table_forum_post::t()->fetch_all_by_tid_position($thread['posttableid'], $ptid, $postno);
			$pid = $rowarr[0]['pid'];
		}

		if($pid) {
			$post = table_forum_post::t()->fetch_post($thread['posttableid'], $pid);
			if($post['invisible'] != 0) {
				$post = [];
			}
		} else {
			$postno = $postno > 1 ? $postno - 1 : 0;
			$post = table_forum_post::t()->fetch_visiblepost_by_tid($thread['posttableid'], $ptid, $postno);
		}
	}

}

if(empty($post)) {
	if($ptid) {
		header('HTTP/1.1 301 Moved Permanently');
		dheader("Location: forum.php?mod=viewthread&tid=$ptid");
	} else {
		showmessage('post_check', NULL, ['tid' => $ptid]);
	}
} else {
	$pid = $post['pid'];
}

if($thread['special'] == 2 || table_forum_threaddisablepos::t()->fetch($tid)) {
	$curpostnum = table_forum_post::t()->count_by_tid_dateline($thread['posttableid'], $tid, $post['dateline']);
} else {
	if($thread['maxposition']) {
		$maxposition = $thread['maxposition'];
	} else {
		$maxposition = table_forum_post::t()->fetch_maxposition_by_tid($thread['posttableid'], $tid);
	}
	$thread['replies'] = $maxposition;
	$curpostnum = $post['position'];
}
$page = ceil($curpostnum / $_G['ppp']);

if($thread['special'] == 2 && table_forum_trade::t()->check_goods($pid)) {
	header('HTTP/1.1 301 Moved Permanently');
	dheader("Location: forum.php?mod=viewthread&do=tradeinfo&tid=$tid&pid=$pid");
}

$authoridurl = $authorid ? '&authorid='.$authorid : '';
header('HTTP/1.1 301 Moved Permanently');
dheader("Location: forum.php?mod=viewthread&tid=$tid&page=$page$authoridurl".(isset($_GET['modthreadkey']) && ($modthreadkey = modauthkey($tid)) ? "&modthreadkey=$modthreadkey" : '')."#pid$pid");
