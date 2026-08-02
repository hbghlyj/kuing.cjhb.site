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

if($_SERVER['REQUEST_METHOD'] == 'POST' && getgpc('do') == 'rollback') {
	if(!$post || getgpc('formhash') != FORMHASH) {
		showmessage('submit_invalid');
	}
	$revision = table_forum_editlog::t()->fetch_by_editid_pid(getgpc('editid'), $pid);
	if(!$revision) {
		showmessage('post_revision_not_found');
	}
	table_forum_editlog::t()->insert([
		'tid' => $post['tid'],
		'pid' => $post['pid'],
		'authorid' => $post['authorid'],
		'uid' => $_G['uid'],
		'username' => $_G['username'],
		'dateline' => TIMESTAMP,
		'action' => 'edit',
		'old_subject' => $post['subject'],
		'old_message' => $post['message'],
		'old_content' => $post['content'],
	]);
	table_forum_post::t()->update_post('tid:'.$post['tid'], $post['pid'], [
		'subject' => $revision['old_subject'],
		'message' => $revision['old_message'],
		'content' => $revision['old_content'],
	]);
	if($post['first']) {
		table_forum_thread::t()->update($post['tid'], ['subject' => $revision['old_subject']]);
	}
	showmessage('post_revision_restored', 'forum.php?mod=viewthread&tid='.$post['tid']);
}

$versions = [];
if($post) {
	$versions[] = [
		'id' => 0,
		'label' => lang('template', 'post_revision_current'),
		'dateline' => TIMESTAMP,
		'subject' => $post['subject'],
		'message' => $post['message'],
		'content' => $post['content'],
	];
}
foreach($editlogs as $editlog) {
	$versions[] = [
		'id' => (int)$editlog['editid'],
		'label' => dgmdate($editlog['dateline']).' - '.($editlog['action'] == 'delete' ? lang('template', 'delete') : lang('template', 'edit')),
		'dateline' => (int)$editlog['dateline'],
		'subject' => $editlog['old_subject'],
		'message' => $editlog['old_message'],
		'content' => $editlog['old_content'],
	];
}
$versions_json = json_encode($versions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
include template('forum/editlog');
