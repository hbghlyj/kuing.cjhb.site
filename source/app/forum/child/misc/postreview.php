<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['setting']['repliesrank'] || empty($_G['uid'])) {
	showmessage('to_login', null, [], ['showmsg' => true, 'login' => 1]);
}
if(empty($_GET['hash']) || $_GET['hash'] != formhash()) {
	showmessage('submit_invalid');
}

$doArray = ['support', 'against'];

$post = table_forum_post::t()->fetch_post('tid:'.$_GET['tid'], $_GET['pid'], false);

if(!in_array($_GET['do'], $doArray) || empty($post) || intval($post['tid']) != intval($_GET['tid']) || ($_G['setting']['threadfilternum'] && $_G['setting']['filterednovote'] && getstatus($post['status'], 11)) || $post['invisible'] < 0) {
	showmessage('undefined_action', NULL);
}

$hotreply = table_forum_hotreply_number::t()->fetch_by_pid($post['pid']);
if($_G['uid'] == $post['authorid']) {
	showmessage('postreview_yourself_error', '', [], ['msgtype' => 3]);
}
$typeid = $_GET['do'] == 'support' ? 1 : 0;
$username = json_encode($_G['username']);

$author = table_common_member::t()->fetch($post['authorid']);
$creditRule = credit::instance()->getrule('postreview', $post['fid'], $author['groupid']);
$karmaUnit = max(0, intval($creditRule['extcredits2'] ?? 0));
$applyKarma = static function($uid, $delta) use ($karmaUnit, $post, $creditRule) {
	if($karmaUnit && $delta) {
		updatemembercount($uid, ['extcredits2' => $delta * $karmaUnit], true, 'PRV', $post['pid'], $creditRule['rulename'] ?? '');
	}
};
$authorKarma = static function($attitude) {
	return $attitude == 1 ? 10 : -2;
};
$voterKarma = static function($attitude) {
	return $attitude == 1 ? 0 : -1;
};

$vote = table_forum_hotreply_member::t()->fetch_member($post['pid'], $_G['uid']);
if($vote) {
	$oldtype = intval($vote['attitude']);
	if($oldtype == $typeid) {
		table_forum_hotreply_number::t()->adjust_num($post['pid'], $typeid, -1);
		table_forum_hotreply_member::t()->delete_by_uid_pid($_G['uid'], $post['pid']);
		$applyKarma($post['authorid'], -$authorKarma($typeid));
		$applyKarma($_G['uid'], -$voterKarma($typeid));
		showmessage('follow_cancel_succeed', '', [], ['msgtype' => 3, 'extrajs' => '<script type="text/javascript">postreviewcancel('.$post['pid'].', '.$typeid.', '.$username.');</script>']);
	}
	table_forum_hotreply_number::t()->adjust_num($post['pid'], $oldtype, -1);
	table_forum_hotreply_number::t()->adjust_num($post['pid'], $typeid, 1);
	table_forum_hotreply_member::t()->update_attitude($post['pid'], $_G['uid'], $typeid, $_G['timestamp']);
	$applyKarma($post['authorid'], $authorKarma($typeid) - $authorKarma($oldtype));
	$applyKarma($_G['uid'], $voterKarma($typeid) - $voterKarma($oldtype));
	showmessage('thread_poll_succeed', '', [], ['msgtype' => 3, 'extrajs' => '<script type="text/javascript">postreviewcancel('.$post['pid'].', '.$oldtype.', '.$username.');postreviewupdate('.$post['pid'].', '.$typeid.', '.$username.');</script>']);
}

$postreviewdaycount = max(0, intval($_G['setting']['postreviewdaycount'] ?? 0));
if($postreviewdaycount && table_forum_hotreply_member::t()->count_by_uid_dateline($_G['uid'], $_G['timestamp'] - 86400) >= $postreviewdaycount) {
	showmessage('postreview_outoftimes', '', [], ['msgtype' => 3]);
}

if(empty($hotreply)) {
	table_forum_hotreply_number::t()->insert([
		'pid' => $post['pid'],
		'tid' => $post['tid'],
		'support' => 0,
		'against' => 0,
		'total' => 0,
	], false, false, true);
}

table_forum_hotreply_number::t()->update_num($post['pid'], $typeid);
table_forum_hotreply_member::t()->insert([
	'tid' => $post['tid'],
	'pid' => $post['pid'],
	'uid' => $_G['uid'],
	'attitude' => $typeid,
	'dateline' => $_G['timestamp'],
]);
$applyKarma($post['authorid'], $authorKarma($typeid));
$applyKarma($_G['uid'], $voterKarma($typeid));

$thread = table_forum_thread::t()->fetch($post['tid']);
notification_add($post['authorid'], 'post', 'postreview_'.$_GET['do'], [
	'from_id' => $post['pid'],
	'from_idtype' => 'postreview_'.$_GET['do'],
	'tid' => $post['tid'],
	'pid' => $post['pid'],
	'subject' => $thread['subject'],
]);

$hotreply[$_GET['do']]++;

showmessage('thread_poll_succeed', '', [], ['msgtype' => 3, 'extrajs' => '<script type="text/javascript">postreviewupdate('.$post['pid'].', '.$typeid.', '.$username.');</script>']);
