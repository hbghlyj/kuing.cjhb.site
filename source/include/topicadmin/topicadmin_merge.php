<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: topicadmin_merge.php 31741 2012-09-26 08:12:08Z zhangjie $
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
	$modaction = 'MRG';

	$reason = checkreasonpm();

	$other = C::t('forum_thread')->fetch_by_tid_displayorder($othertid, 0);
	if(!$other) {
		showmessage('admin_merge_nonexistence');
	} elseif($other['special']) {
		showmessage('special_noaction');
	}
if($othertid == $_G['tid'] || ($_G['adminid'] == 3 && $other['fid'] != $_G['forum']['fid'])) {
        showmessage('admin_merge_invalid');
}

$thread = C::t('forum_thread')->fetch($_G['tid']);
$taglist = array();
foreach(array($thread['tags'], $other['tags']) as $tagstr) {
    foreach(explode("\t", $tagstr) as $var) {
        if($var) {
            list($id, $name) = explode(',', $var);
            $taglist[$id] = $name;
            C::t('common_tagitem')->replace($id, $_G['tid'], 'tid');
        }
    }
}
C::t('common_tagitem')->delete_tagitem(0, $othertid, 'tid');
$newtagstr = '';
foreach($taglist as $id => $name) {
    $newtagstr .= $id.','.$name."\t";
}
C::t('forum_thread')->update($_G['tid'], array('tags' => $newtagstr));

	$other['views'] = intval($other['views']);
	$other['replies']++;
	if(!$other['maxposition']) {
		$other['maxposition'] = C::t('forum_post')->fetch_maxposition_by_tid($other['posttableid'], $othertid);
	}
	if(!$thread['maxposition']) {
		$thread['maxposition'] = C::t('forum_post')->fetch_maxposition_by_tid($thread['posttableid'], $_G['tid']);
	}
	$pos = 1;
	if($posttable != $otherposttable) {
		$pidlist = array();
		C::t('forum_post')->increase_position_by_tid($thread['posttableid'], $_G['tid'], $other['maxposition'] + $thread['maxposition']);
		C::t('forum_post')->increase_position_by_tid($other['posttableid'], $othertid, $other['maxposition'] + $thread['maxposition']);
		foreach(C::t('forum_post')->fetch_all_by_tid('tid:'.$_G['tid'], $_G['tid'], false, 'ASC') as $row) {
			$pidlist[$row['dateline']] = array('pid' => $row['pid'], 'tid' => $row['tid']);
		}
		foreach(C::t('forum_post')->fetch_all_by_tid('tid:'.$othertid, $othertid, false, 'ASC') as $row) {
			$pidlist[$row['dateline']] = array('pid' => $row['pid'], 'tid' => $row['tid']);
		}
		ksort($pidlist);
		foreach($pidlist as $row) {
			C::t('forum_post')->update_post('tid:'.$row['tid'], $row['pid'], array('position' => $pos));
			$pos ++;
		}
		unset($pidlist);
	} else {
		C::t('forum_post')->increase_position_by_tid($thread['posttableid'], array($_G['tid'], $othertid), $other['maxposition'] + $thread['maxposition']);
		foreach(C::t('forum_post')->fetch_all_by_tid('tid:'.$_G['tid'], array($_G['tid'], $othertid), false, 'ASC') as $row) {
			C::t('forum_post')->update_post('tid:'.$_G['tid'], $row['pid'], array('position' => $pos));
			$pos ++;
		}
	}
	if($posttable != $otherposttable) {
		foreach(C::t('forum_post')->fetch_all_by_tid('tid:'.$othertid, $othertid) as $row) {
			C::t('forum_post')->insert_post('tid:'.$_G['tid'], $row);
		}
		C::t('forum_post')->delete_by_tid('tid:'.$othertid, $othertid);
	}

	$query = C::t('forum_post')->fetch_all_by_tid('tid:'.$_G['tid'], array($_G['tid'], $othertid), false, 'ASC', 0, 1, null, 0);
	foreach($query as $row) {
		$firstpost = $row;
	}

	$postsmerged = C::t('forum_post')->update_by_tid('tid:'.$_G['tid'], $othertid, array('tid' => $_G['tid']));
	DB::update('forum_postcomment', array('tid' => $_G['tid']), DB::field('tid', $othertid), false, false);
	updateattachtid('tid', array($othertid), $othertid, $_G['tid']);
	C::t('forum_thread')->delete_by_tid($othertid);
	C::t('forum_threadmod')->delete_by_tid($othertid);

	C::t('forum_post')->update_by_tid('tid:'.$_G['tid'], $_G['tid'], array('first' => 0, 'fid' => $_G['forum']['fid']));
	C::t('forum_post')->update_post('tid:'.$_G['tid'], $firstpost['pid'], array('first' => 1));
	$fieldarr = array(
			'views' => $other['views'],
			'replies' => $other['replies'],
		);
	C::t('forum_thread')->increase($_G['tid'], $fieldarr);
	$fieldarr = array(
			'authorid' => $firstpost['authorid'],
			'author' => $firstpost['author'],
			'subject' => $firstpost['subject'],
			'dateline' => $firstpost['dateline'],
			'moderated' => 1,
			'maxposition' => $other['maxposition'] + $thread['maxposition'],
		);
	C::t('forum_thread')->update($_G['tid'], $fieldarr);
	updateforumcount($other['fid']);
	updateforumcount($_G['fid']);

	$_G['forum']['threadcaches'] && deletethreadcaches($thread['tid']);

	$modpostsnum ++;
	$resultarray = array(
	'redirect'	=> "forum.php?mod=viewthread&tid=$_G[tid]",
	'reasonpm'	=> ($sendreasonpm ? array('data' => array($thread), 'var' => 'thread', 'item' => 'reason_merge', 'notictype' => 'post') : array()),
	'reasonvar'	=> array('tid' => $thread['tid'], 'subject' => $thread['subject'], 'modaction' => $modaction, 'reason' => $reason),
	'modtids'	=> $thread['tid'],
	'modlog'	=> array($thread, $other)
	);

}

?>