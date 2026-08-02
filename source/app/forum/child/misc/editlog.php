<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$pid = dintval($_GET['pid']);
$post = table_forum_post::t()->fetch_post('tid:'.$_G['tid'], $pid);
$editlogs = table_forum_editlog::t()->fetch_all_by_pid($pid);
if($post && $post['tid'] != $_G['tid']) {
	$post = [];
}
if(!$post && empty($editlogs)) {
	showmessage('post_not_found');
}
$authorid = $post ? $post['authorid'] : $editlogs[0]['authorid'];
if($_G['uid'] != $authorid && $_G['adminid'] <= 0) {
	showmessage('post_revision_no_permission');
}
include template('forum/editlog');
