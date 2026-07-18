<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$fid = dintval($_GET['fid']);
$page = max(1, dintval($_GET['page']));
$pagesize = 6;

$forum = table_forum_forum::t()->fetch($fid);
if(!$forum || $forum['type'] != 'forum' || $forum['permission'] == 1) {
	showmessage('', '', ['list' => [], 'hasmore' => false]);
	exit;
}

$threadlist = table_forum_thread::t()->fetch_all_by_fid_displayorder($fid, 0, null, null, ($page - 1) * $pagesize, $pagesize, 'dateline', 'DESC');

$data = [];
foreach($threadlist as $thread) {
	$data[] = [
		'tid' => $thread['tid'],
		'subject' => cutstr(dhtmlspecialchars($thread['subject']), 40),
		'author' => $thread['author'] ?: $_G['setting']['anonymoustext'],
		'replies' => $thread['replies'],
		'dateline' => dgmdate($thread['dateline'], 'u', '9999', getglobal('setting/dateformat')),
	];
}

showmessage('', '', ['list' => $data, 'hasmore' => count($threadlist) == $pagesize]);
exit;
