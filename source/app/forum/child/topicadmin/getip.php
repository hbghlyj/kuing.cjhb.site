<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(empty($_G['group']['allowviewip'])) {
	showmessage('admin_nopermission', NULL);
}

$topiclist = !empty($_GET['topiclist']) ? (is_array($_GET['topiclist']) ? $_GET['topiclist'] : [$_GET['topiclist']]) : [];
if(!empty($_GET['pid'])) {
	$topiclist[] = $_GET['pid'];
}
$topiclist = array_values(array_unique(array_filter(array_map('intval', $topiclist))));
if(!$topiclist || empty($_G['tid'])) {
	showmessage('admin_nopermission', NULL);
}

$postlist = table_forum_post::t()->fetch_all_post('tid:'.$_G['tid'], $topiclist, false);
if(!$postlist) {
	showmessage('admin_nopermission', NULL);
}

$uids = [];
foreach($postlist as $post) {
	if($post['authorid'] && $post['username']) {
		$uids[$post['authorid']] = 1;
	}
}
if(!$uids) {
	showmessage('admin_nopermission', NULL);
}

$memberlist = table_common_member::t()->fetch_all(array_keys($uids));
foreach($postlist as $post) {
	$member = $memberlist[$post['authorid']] ?? [];
	if(empty($member['username'])) {
		continue;
	}

	// Nobody may look up the address of a higher ranked administrator.
	if(max(0, (int)$member['adminid']) > max(0, (int)$_G['adminid'])) {
		showmessage('admin_getip_nopermission');
	}

	$status = table_common_member_status::t()->fetch($post['authorid']);
	$ip = trim($status['lastip'] ?? '');
	if($ip === '') {
		showmessage('admin_getip_noip');
	}

	showmessage(lang('magic/showip', 'showip_ip_message', ['username' => $member['username'], 'ip' => $ip]), '', [], ['alert' => 'info']);
}

showmessage('admin_nopermission', NULL);
