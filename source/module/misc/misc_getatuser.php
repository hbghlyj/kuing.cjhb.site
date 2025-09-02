<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: misc_getatuser.php 25782 2011-11-22 05:29:19Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$result = '';
$search_str = getgpc('search_str');
if($tid = intval($_GET['tid'])){
	// If tid is provided, we will fetch the participants from table 'forum_post' and 'forum_postcomment'
	$atlist_tid = array();
	// Fetch from forum_post
	foreach(C::t('forum_post')->fetch_all_by_tid('tid:'.$tid, $tid, true, '', 0, 0, null, 0) as $post) {
		if(!empty($post['authorid']) && $post['authorid'] != $_G['uid'] && !isset($atlist_tid[$post['authorid']])) {
			$atlist_tid[$post['authorid']] = str_replace(' ', '', $post['author']);
		}
	}
	// Fetch from forum_postcomment
	foreach(C::t('forum_postcomment')->fetch_all_by_search($tid, null, null, null, null, null, null, 0, 0) as $comment) {
		if(!empty($comment['authorid']) && $comment['authorid'] != $_G['uid'] && !isset($atlist_tid[$comment['authorid']])) {
			$atlist_tid[$comment['authorid']] = str_replace(' ', '', $comment['author']);
		}
	}
	$result = implode(',', array_values($atlist_tid));
}elseif($_G['uid']) {
	$atlist = $atlist_cookie = array();
	$limit = 200;
	if(getcookie('atlist')) {
		$cookies = explode(',', $_G['cookie']['atlist']);
		foreach(C::t('common_member')->fetch_all($cookies, false) as $row) {
			if ($row['uid'] != $_G['uid'] && in_array($row['uid'], $cookies)) {
				$atlist_cookie[$row['uid']] = str_replace(' ', '', $row['username']);
			}
		}
	}
	foreach(C::t('home_follow')->fetch_all_following_by_uid($_G['uid'], 0, 0, $limit) as $row) {
		if($atlist_cookie[$row['followuid']]) {
			continue;
		}
		$atlist[$row['followuid']] = str_replace(' ', '', $row['fusername']);
	}
	$num = count($atlist);
	if($num < $limit) {
		$query = C::t('home_friend')->fetch_all_by_uid($_G['uid'], 0, $limit * 2);
		foreach($query as $row) {
			if(count($atlist) == $limit) {
				break;
			}
			if($atlist_cookie[$row['fuid']]) {
				continue;
			}
			$atlist[$row['fuid']] = str_replace(' ', '', $row['fusername']);
		}
	}
	$result = implode(',', $atlist_cookie).($atlist_cookie && $atlist ? ',' : '').implode(',', $atlist);
}
include template('common/getatuser');
