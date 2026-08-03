<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$post = table_forum_post::t()->fetch_post('tid:'.$_G['tid'], $_GET['pid']);
if($post['authorid'] != $_G['uid']) {
	showmessage('postdelete_only_yourself');
}
if(submitcheck('postdeletesubmit')) {
	$url_forward = 'forum.php?mod=viewthread&tid=' .$post['tid'];
	require_once libfile('function/delete');

	$visible_count = table_forum_post::t()->count_visiblepost_by_tid($post['tid']);

	if($post['first'] && $visible_count <= 1) {
		deletethread([$post['tid']], true, true);
		updateforumcount($post['fid']);

		deletepost([$post['tid']], 'tid', true);
		updatethreadcount($post['tid']);
		$url_forward = 'forum.php?mod=forumdisplay&fid=' .$post['fid'];
	} else {
		table_forum_editlog::t()->insert([
			'tid' => $post['tid'],
			'pid' => $post['pid'],
			'authorid' => $post['authorid'],
			'uid' => $_G['uid'],
			'username' => $_G['username'],
			'dateline' => TIMESTAMP,
			'action' => 'delete',
			'old_subject' => $post['subject'],
			'old_message' => $post['message'],
			'old_content' => $post['content'],
		]);
		deletepost([$post['pid']], 'pid', true);
		if($post['first']) {
			$nextpost = table_forum_post::t()->fetch_visiblepost_by_tid('tid:'.$post['tid'], $post['tid'], 0, 0);
			if($nextpost) {
				table_forum_post::t()->update_post('tid:'.$post['tid'], $nextpost['pid'], ['first' => 1, 'subject' => $post['subject']]);
			}
		}
		updatethreadcount($post['tid']);
	}

	if(!empty($_G['inajax'])) {
		showmessage('postdelete_succeed', $url_forward, [], ['location' => true]);
	} else {
		showmessage('postdelete_succeed', $url_forward);
	}
} else {
	include template('forum/postdelete');
}
