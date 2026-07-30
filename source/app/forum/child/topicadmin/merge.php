<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['group']['allowmergethread']) {
	showmessage('no_privilege_mergethread');
}

if(!submitcheck('modsubmit')) {

	include template('forum/topicadmin_action');

} else {

	$posttable = getposttablebytid($_G['tid']);
	$othertid = intval($_GET['othertid']);
	$otherposttable = getposttablebytid($othertid);
	$newpostposition = intval($_GET['newpostposition']);
	$modaction = 'MRG';

	$reason = checkreasonpm();

	$other = table_forum_thread::t()->fetch_by_tid_displayorder($othertid, 0);
	if(!$other) {
		showmessage('admin_merge_nonexistence');
	} elseif($other['special']) {
		showmessage('special_noaction');
	}
	if($othertid == $_G['tid'] || ($_G['adminid'] == 3 && $other['fid'] != $_G['forum']['fid'])) {
		showmessage('admin_merge_invalid');
	}

	$other['views'] = intval($other['views']);
	$other['replies']++;
	if(!$other['maxposition']) {
		$other['maxposition'] = table_forum_post::t()->fetch_maxposition_by_tid($other['posttableid'], $othertid);
	}
	if(!$thread['maxposition']) {
		$thread['maxposition'] = table_forum_post::t()->fetch_maxposition_by_tid($thread['posttableid'], $_G['tid']);
	}
	if($posttable != $otherposttable) {
		table_forum_post::t()->increase_position_by_tid($thread['posttableid'], $_G['tid'], $other['maxposition'] + $thread['maxposition']);
		table_forum_post::t()->increase_position_by_tid($other['posttableid'], $othertid, $other['maxposition'] + $thread['maxposition']);
	} else {
		table_forum_post::t()->increase_position_by_tid($thread['posttableid'], [$_G['tid'], $othertid], $other['maxposition'] + $thread['maxposition']);
	}
	$postlist1 = [];
	$postlist2 = [];
	foreach(table_forum_post::t()->fetch_all_by_tid('tid:'.$_G['tid'], $_G['tid'], false, 'ASC') as $row) {
		$postlist1[] = $row;
	}
	foreach(table_forum_post::t()->fetch_all_by_tid('tid:'.$othertid, $othertid, false, 'ASC') as $row) {
		$postlist2[] = $row;
	}
	$pidlist = $newpostposition ? array_merge($postlist2, $postlist1) : array_merge($postlist1, $postlist2);
	$pos = 1;
	foreach($pidlist as $row) {
		table_forum_post::t()->update_post('tid:'.$row['tid'], $row['pid'], ['position' => $pos]);
		$pos++;
	}
	$firstpost = reset($pidlist);
	unset($postlist1, $postlist2, $pidlist);
	if($posttable != $otherposttable) {
		foreach(table_forum_post::t()->fetch_all_by_tid('tid:'.$othertid, $othertid) as $row) {
			table_forum_post::t()->insert_post('tid:'.$_G['tid'], $row);
		}
		table_forum_post::t()->delete_by_tid('tid:'.$othertid, $othertid);
	}

	$postsmerged = table_forum_post::t()->update_by_tid('tid:'.$_G['tid'], $othertid, ['tid' => $_G['tid']]);

	updateattachtid('tid', [$othertid], $othertid, $_G['tid']);
	table_forum_thread::t()->delete_by_tid($othertid);
	table_forum_threadmod::t()->delete_by_tid($othertid);

	table_forum_post::t()->update_by_tid('tid:'.$_G['tid'], $_G['tid'], ['first' => 0, 'fid' => $_G['forum']['fid']]);
	table_forum_post::t()->update_post('tid:'.$_G['tid'], $firstpost['pid'], ['first' => 1]);
	$fieldarr = [
		'views' => $other['views'],
		'replies' => $other['replies'],
	];
	table_forum_thread::t()->increase($_G['tid'], $fieldarr);
	$fieldarr = [
		'authorid' => $firstpost['authorid'],
		'author' => $firstpost['author'],
		'subject' => $firstpost['subject'],
		'dateline' => $firstpost['dateline'],
		'moderated' => 1,
		'maxposition' => $other['maxposition'] + $thread['maxposition'],
	];
	table_forum_thread::t()->update($_G['tid'], $fieldarr);
	updateforumcount($other['fid']);
	updateforumcount($_G['fid']);

	$_G['forum']['threadcaches'] && deletethreadcaches($thread['tid']);

	$modpostsnum++;
	$resultarray = [
		'redirect' => "forum.php?mod=viewthread&tid={$_G['tid']}",
		'reasonpm' => ($sendreasonpm ? ['data' => [$thread], 'var' => 'thread', 'item' => 'reason_merge', 'notictype' => 'post'] : []),
		'reasonvar' => ['tid' => $thread['tid'], 'subject' => $thread['subject'], 'modaction' => $modaction, 'reason' => $reason],
		'modtids' => $thread['tid'],
		'modlog' => [$thread, $other]
	];

}

