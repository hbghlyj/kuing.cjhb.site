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

if(!in_array($_GET['do'], $doArray) || empty($post) || $post['first'] == 1 || ($_G['setting']['threadfilternum'] && $_G['setting']['filterednovote'] && getstatus($post['status'], 11)) || $post['invisible'] < 0) {
	showmessage('undefined_action', NULL);
}

$hotreply = table_forum_hotreply_number::t()->fetch_by_pid($post['pid']);
if($_G['uid'] == $post['authorid']) {
	showmessage('noreply_yourself_error', '', [], ['msgtype' => 3]);
}
$typeid = $_GET['do'] == 'support' ? 1 : 0;
$username = json_encode($_G['username']);
$creditValue = static function($attitude) {
	return $attitude == 1 ? 1 : -1;
};

if(empty($hotreply)) {
	$hotreply['pid'] = table_forum_hotreply_number::t()->insert([
		'pid' => $post['pid'],
		'tid' => $post['tid'],
		'support' => 0,
		'against' => 0,
		'total' => 0,
	], true);
} else {
	$vote = table_forum_hotreply_member::t()->fetch_member($post['pid'], $_G['uid']);
	if($vote) {
		$oldtype = intval($vote['attitude']);
		if($oldtype == $typeid) {
			table_forum_hotreply_number::t()->adjust_num($post['pid'], $typeid, -1);
			table_forum_hotreply_member::t()->delete_by_uid_pid($_G['uid'], $post['pid']);
			updatemembercount($post['authorid'], ['extcredits1' => -$creditValue($typeid)]);
			showmessage('follow_cancel_succeed', '', [], ['msgtype' => 3, 'extrajs' => '<script type="text/javascript">postreviewcancel('.$post['pid'].', '.$typeid.', '.$username.');</script>']);
		}
		table_forum_hotreply_number::t()->adjust_num($post['pid'], $oldtype, -1);
		table_forum_hotreply_number::t()->adjust_num($post['pid'], $typeid, 1);
		table_forum_hotreply_member::t()->update_attitude($post['pid'], $_G['uid'], $typeid);
		updatemembercount($post['authorid'], ['extcredits1' => $creditValue($typeid) - $creditValue($oldtype)]);
		showmessage('thread_poll_succeed', '', [], ['msgtype' => 3, 'extrajs' => '<script type="text/javascript">postreviewcancel('.$post['pid'].', '.$oldtype.', '.$username.');postreviewupdate('.$post['pid'].', '.$typeid.', '.$username.');</script>']);
	}
}

table_forum_hotreply_number::t()->update_num($post['pid'], $typeid);
table_forum_hotreply_member::t()->insert([
	'tid' => $post['tid'],
	'pid' => $post['pid'],
	'uid' => $_G['uid'],
	'attitude' => $typeid,
]);
updatemembercount($post['authorid'], ['extcredits1' => $creditValue($typeid)]);

$hotreply[$_GET['do']]++;

showmessage('thread_poll_succeed', '', [], ['msgtype' => 3, 'extrajs' => '<script type="text/javascript">postreviewupdate('.$post['pid'].', '.$typeid.', '.$username.');</script>']);
