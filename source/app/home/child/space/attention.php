<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['uid']) {
	showmessage('login_before_enter_home', null, [], ['showmsg' => true, 'login' => 1]);
}

$space = getuserbyuid($_G['uid']);

$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
if($page < 1) {
	$page = 1;
}

$perpage = 20;

$_G['disabledwidthauto'] = 0;

$start = ($page - 1) * $perpage;
ckstart($start, $perpage);

$filter = $_GET['filter'] == 'new' ? 'new' : '';

loadcache('forums');

$gets = [
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'attention',
	'filter' => $filter
];
$theurl = 'home.php?'.url_implode($gets);

$count = table_forum_threadattention::t()->count_by_uid($_G['uid'], $filter == 'new');
$list = [];
if($count) {
	foreach(table_forum_threadattention::t()->fetch_all_by_uid($_G['uid'], $filter == 'new', $start, $perpage) as $value) {
		if(empty($value['t_tid'])) {
			table_forum_threadattention::t()->delete_by_tid_uid($value['tid'], $_G['uid']);
			continue;
		}
		$value['lastpost'] = dgmdate($value['lastpost'], 'u');
		$value['forumname'] = $_G['cache']['forums'][$value['fid']]['name'] ?? '';
		$list[$value['tid']] = $value;
	}
}

$multi = multi($count, $perpage, $page, $theurl);

$navtitle = lang('core', 'title_attention');

include template('home/space_attention');
