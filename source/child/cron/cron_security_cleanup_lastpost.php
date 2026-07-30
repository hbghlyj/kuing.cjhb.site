<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$queryf = table_forum_forum::t()->fetch_all_fids();
$parent_fups = [];
foreach($queryf as $forum) {
	if($forum['type'] == 'sub' && !empty($forum['fup'])) {
		$parent_fups[] = $forum['fup'];
	}
}

$parents = [];
if(!empty($parent_fups)) {
	$parents = table_forum_forum::t()->fetch_all_info_by_fids(array_unique($parent_fups));
}

foreach($queryf as $forum) {
	$thread = table_forum_thread::t()->fetch_by_fid_displayorder($forum['fid']);

	table_forum_forum::t()->update_lastpost($forum['fid'], $thread['tid'], $thread['subject'], $thread['lastpost'], $thread['lastposter'], ['forum' => $forum, 'propagate_parent' => false]);
	if($forum['type'] == 'sub') {
		$parent = isset($parents[$forum['fup']]) ? $parents[$forum['fup']] : null;
		if($parent) {
			$parent_lastpost = 0;
			if(!empty($parent['lastpost'])) {
				$tmp = explode("\t", $parent['lastpost']);
				$parent_lastpost = intval($tmp[1]);
			}
			if($thread['lastpost'] > $parent_lastpost) {
				table_forum_forum::t()->update_lastpost($forum['fup'], $thread['tid'], $thread['subject'], $thread['lastpost'], $thread['lastposter'], ['forum' => $parent, 'propagate_parent' => false]);
			}
		}
	}
}
