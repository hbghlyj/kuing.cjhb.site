<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$num = table_forum_editlog::t()->count_for_admin($keyword);
$logs = table_forum_editlog::t()->fetch_all_for_admin($keyword, $start, $lpp);
$authornames = table_common_member::t()->fetch_all_username_by_uid(array_column($logs, 'authorid'));
$multipage = multi($num, $lpp, $page, $urlbase, 0, 3);

showtableheader('', 'fixpadding');
	showsubtitle([
		cplang('time'),
		cplang('logs_edit_original_author'),
		cplang('username'),
		cplang('logs_edit_action'),
		cplang('logs_edit_target'),
		cplang('logs_edit_previous_subject'),
		cplang('logs_edit_previous_message'),
		cplang('logs_edit_previous_content'),
	]);

foreach($logs as $data) {
	$action = $data['action'] === 'delete' ? cplang('delete') : cplang('edit');
	$tid = intval($data['tid']);
	$pid = intval($data['pid']);
	$target = '<a href="./forum.php?mod=viewthread&tid='.$tid.'&pid='.$pid.'" target="_blank">tid '.$tid.' / pid '.$pid.'</a>';
	$oldsubject = dhtmlspecialchars($data['old_subject']);
	$oldmessage = dhtmlspecialchars($data['old_message']);
	$oldcontent = dhtmlspecialchars($data['old_content']);
	showtablerow('', [], [
		dgmdate($data['dateline']),
		dhtmlspecialchars($authornames[$data['authorid']] ?? ('UID '.$data['authorid'])),
		dhtmlspecialchars($data['username']),
		$action,
		$target,
		$oldsubject,
		'<pre style="white-space:pre-wrap;max-width:500px;">'.$oldmessage.'</pre>',
		$oldcontent !== '' ? '<details><summary>'.cplang('logs_edit_view_previous').'</summary><pre style="white-space:pre-wrap;max-width:700px;">'.$oldcontent.'</pre></details>' : '-',
	]);
}
if(!$logs) {
	showtablerow('', [], ['-', '-', '-', '-', '-', '-', '-', cplang('logs_edit_none')]);
}
showtablefooter();
