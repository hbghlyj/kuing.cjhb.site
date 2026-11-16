<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/*
 * Comment-to-comment replies, adapted from MitFrame 677b40b8.
 *
 * forum_postcomment.rpid keeps its fork meaning: the PID of the reply post that
 * produced a quote summary (extend_thread_comment). A reply to another comment is
 * identified only by the transient reply_comment_id request field; the target is
 * re-fetched and validated here and recorded as a localized "Reply @username"
 * prefix inside the stored comment text, so no schema change is needed.
 */

function postcomment_thread_viewable($thread) {
	global $_G;
	$forum = $_G['forum'] ?? [];
	if(empty($thread['tid']) || empty($forum['fid']) || intval($forum['fid']) != intval($thread['fid'])) {
		return false;
	}
	$ismoderator = !empty($forum['ismoderator']);
	if(!$ismoderator && !($thread['displayorder'] >= 0)) {
		return false;
	}
	if(!empty($forum['password']) && $forum['password'] != ($_G['cookie']['fidpw'.$forum['fid']] ?? '')) {
		return false;
	}
	if(empty($forum['allowview'])) {
		if(empty($forum['viewperm']) && empty($_G['group']['readaccess'])) {
			return false;
		}
		if(!empty($forum['viewperm']) && !forumperm($forum['viewperm'])) {
			return false;
		}
	} elseif($forum['allowview'] == -1) {
		return false;
	}
	if(!empty($thread['readperm']) && $thread['readperm'] > ($_G['group']['readaccess'] ?? 0) && !$ismoderator && $thread['authorid'] != $_G['uid']) {
		return false;
	}
	return true;
}

/**
 * Re-fetch and validate the comment a new comment replies to.
 *
 * Returns ['id', 'authorid', 'author'] when $commentid names a member's comment
 * on $post, $post belongs to $thread (the current $_G['tid']) and the member may
 * view it; otherwise null. Never trusts any author data from the request.
 */
function postcomment_reply_target($commentid, $post, $thread) {
	global $_G;
	$commentid = is_scalar($commentid) && preg_match('/^\d+$/', (string)$commentid) ? intval($commentid) : 0;
	if($commentid <= 0 || empty($post['pid']) || empty($thread['tid'])) {
		return null;
	}
	$tid = intval($thread['tid']);
	if(intval($post['tid']) != $tid || intval($_G['tid'] ?? 0) != $tid) {
		return null;
	}
	if(!postcomment_thread_viewable($thread)) {
		return null;
	}
	if(intval($post['invisible'] ?? 0) != 0 && empty($_G['forum']['ismoderator'])) {
		return null;
	}
	$target = table_forum_postcomment::t()->fetch($commentid);
	if(!$target || intval($target['id']) != $commentid || intval($target['tid']) != $tid || intval($target['pid']) != intval($post['pid'])) {
		return null;
	}
	$authorid = intval($target['authorid']);
	$author = trim((string)$target['author']);
	if($authorid <= 0 || $author === '') {
		return null;
	}
	return ['id' => $commentid, 'authorid' => $authorid, 'author' => $author];
}

/**
 * Localized "Reply @username" prefix in stored-comment format: HTML-escaped,
 * with brackets neutralized so a username cannot open or close comment BBCode.
 */
function postcomment_reply_prefix($author) {
	$author = str_replace(['[', ']'], ['&#91;', '&#93;'], dhtmlspecialchars(trim((string)$author)));
	return lang('forum/misc', 'postcomment_reply_prefix', ['author' => $author]);
}

/**
 * Characters left for the typed message once the prefix is stored, within both
 * the 200-character message limit and the 255-character comment column.
 */
function postcomment_message_limit($prefix = '') {
	return max(0, min(200, 255 - mb_strlen($prefix, 'UTF-8')));
}

/**
 * Who is notified about a new comment. The reply target gets comment_reply; the
 * post author keeps the original comment_add eligibility unless they are that
 * same target. Nobody is notified about their own comment, and guests send none.
 */
function postcomment_notification_recipients($senderuid, $postauthorid, $replyauthorid = 0) {
	$senderuid = intval($senderuid);
	$postauthorid = intval($postauthorid);
	$replyauthorid = intval($replyauthorid);
	$recipients = ['reply' => 0, 'post' => 0];
	if($senderuid <= 0) {
		return $recipients;
	}
	if($replyauthorid > 0 && $replyauthorid != $senderuid) {
		$recipients['reply'] = $replyauthorid;
	}
	if($postauthorid > 0 && $postauthorid != $senderuid && $postauthorid != $recipients['reply']) {
		$recipients['post'] = $postauthorid;
	}
	return $recipients;
}

/**
 * Whether comment rows outside viewthread (the commentmore fragment) may offer a
 * reply link: the conditions viewthread uses for its Comment button.
 */
function postcomment_reply_link_allowed($post, $thread) {
	global $_G;
	if(empty($_G['uid']) || empty($post['pid']) || empty($thread['tid']) || intval($post['tid']) != intval($thread['tid']) || intval($post['invisible'] ?? 0) != 0) {
		return false;
	}
	$setting = $_G['setting'];
	$forum = $_G['forum'] ?? [];
	$ismoderator = !empty($forum['ismoderator']);
	$allowcomment = !empty($setting['commentnumber']) && is_array($setting['allowpostcomment'] ?? null) && in_array(1, $setting['allowpostcomment']) &&
		(!empty($setting['commentpostself']) || $post['authorid'] != $_G['uid']) &&
		($post['first'] && !empty($setting['commentfirstpost']) && in_array($_G['group']['allowcommentpost'], [1, 3]) ||
			(!$post['first'] && in_array($_G['group']['allowcommentpost'], [2, 3])));
	if(!$allowcomment || ($thread['closed'] && !$ismoderator)) {
		return false;
	}
	if(!function_exists('checkautoclose')) {
		require_once libfile('function/forumlist');
	}
	$allowreply = $forum['allowreply'] ?? '';
	$replyperm = $forum['replyperm'] ?? '';
	return $allowreply != -1 &&
		(!empty($thread['isgroup']) || (!$thread['closed'] && !checkautoclose($thread)) || $ismoderator) &&
		((!$replyperm && !empty($_G['group']['allowreply'])) || ($replyperm && forumperm($replyperm)) || $allowreply);
}
