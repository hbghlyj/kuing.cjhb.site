<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/post');

$pid = dintval($_GET['pid']);
$post = table_forum_post::t()->fetch_post('tid:'.$_G['tid'], $pid);
$editlogs = table_forum_editlog::t()->fetch_all_by_pid($pid);
if($post && $post['tid'] != $_G['tid']) {
	$post = [];
}
if(!$post && empty($editlogs)) {
	showmessage('post_not_found');
}
$revision_tid = $post ? $post['tid'] : $editlogs[0]['tid'];
$authorid = $post ? $post['authorid'] : $editlogs[0]['authorid'];
if($_G['uid'] != $authorid && $_G['adminid'] <= 0) {
	showmessage('post_revision_no_permission');
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && getgpc('do') == 'rollback') {
	if(getgpc('formhash') != FORMHASH) {
		showmessage('submit_invalid');
	}
	$revision = table_forum_editlog::t()->fetch_by_editid_pid(getgpc('editid'), $pid);
	if(!$revision) {
		showmessage('post_revision_not_found');
	}
	if(!$post) {
		if($revision['action'] != 'delete' || !$revision['tid'] || !$revision['authorid']) {
			showmessage('post_revision_not_found');
		}
		$thread = table_forum_thread::t()->fetch_thread($revision['tid']);
		$member = table_common_member::t()->fetch($revision['authorid']);
		if(!$thread || !$member || $thread['displayorder'] < 0) {
			showmessage('post_revision_not_found');
		}
		$position = table_forum_post::t()->fetch_maxposition_by_tid('tid:'.$thread['tid'], $thread['tid']) + 1;
		$content = $revision['old_content'] !== '' ? $revision['old_content'] : null;
		table_forum_post::t()->insert_post('tid:'.$thread['tid'], [
			'pid' => $pid,
			'fid' => $thread['fid'],
			'tid' => $thread['tid'],
			'repid' => 0,
			'first' => 0,
			'author' => $member['username'],
			'authorid' => $member['uid'],
			'subject' => $revision['old_subject'],
			'dateline' => TIMESTAMP,
			'message' => $revision['old_message'],
			'content' => $content,
			'invisible' => 0,
			'anonymous' => 0,
			'htmlon' => 0,
			'bbcodeoff' => 0,
			'smileyoff' => 0,
			'parseurloff' => 0,
			'attachment' => 0,
			'status' => 0,
			'comment' => 0,
			'replycredit' => 0,
			'position' => $position,
			'bestanswer' => 0,
		]);
		table_forum_thread::t()->increase($thread['tid'], [
			'replies' => 1,
		]);
		table_forum_thread::t()->update($thread['tid'], [
			'lastpost' => TIMESTAMP,
			'lastposter' => $member['username'],
		]);
		table_forum_forum::t()->update_forum_counter($thread['fid'], 0, 1);
		if($revision['attachments_saved']) {
			restoreposteditlogattachments($revision['editid'], $thread['tid'], $pid);
		}
		showmessage('post_revision_restored', 'forum.php?mod=viewthread&tid='.$thread['tid']);
	}
	createposteditlog($post, $_G['uid'], $_G['username']);
	table_forum_post::t()->update_post('tid:'.$post['tid'], $post['pid'], [
		'subject' => $revision['old_subject'],
		'message' => $revision['old_message'],
		'content' => $revision['old_content'] !== '' && $revision['old_content'] !== null ? $revision['old_content'] : null,
	]);
	if($revision['attachments_saved']) {
		restoreposteditlogattachments($revision['editid'], $post['tid'], $post['pid']);
	}
	if($post['first']) {
		table_forum_thread::t()->update($post['tid'], ['subject' => $revision['old_subject']]);
	}
	showmessage('post_revision_restored', 'forum.php?mod=viewthread&tid='.$post['tid']);
}

$versions = [];
$editlogAttachmentRows = table_forum_editlog_attachment::t()->fetch_all_by_editids(array_column($editlogs, 'editid'));
$attachmentNames = static function($attachments) {
	return array_values(array_map(static function($attachment) {
		return $attachment['filename'].(!empty($attachment['description']) ? ' - '.$attachment['description'] : '');
	}, $attachments));
};
if($post) {
	$versions[] = [
		'id' => 0,
		'label' => lang('template', 'post_revision_current'),
		'dateline' => TIMESTAMP,
		'subject' => $post['subject'],
		'message' => $post['message'],
		'content' => $post['content'],
		'attachments' => $attachmentNames(geteditlogattachments($post['tid'], $post['pid'])),
	];
}
foreach($editlogs as $editlog) {
	$editor = $editlog['username'] !== '' ? ' - '.lang('template', 'post_revision_editor').': '.$editlog['username'] : '';
	$versions[] = [
		'id' => (int)$editlog['editid'],
		'label' => dgmdate($editlog['dateline']).' - '.($editlog['action'] == 'delete' ? lang('template', 'delete') : lang('template', 'edit')).$editor,
		'dateline' => (int)$editlog['dateline'],
		'subject' => $editlog['old_subject'],
		'message' => $editlog['old_message'],
		'content' => $editlog['old_content'],
		'attachments' => $editlog['attachments_saved'] ? $attachmentNames($editlogAttachmentRows[$editlog['editid']] ?? []) : null,
	];
}
$versions_json = json_encode($versions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$attachment_label_json = json_encode(lang('template', 'attachment'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
include template('forum/editlog');
