<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!isfounder()) {
	cpmsg('noaccess_isfounder', '', 'error');
}

$uid = intval($_GET['uid']);
$username = trim($_GET['username']);
if($username !== '') {
	$member = table_common_member::t()->fetch_by_username($username);
	$uid = $member ? intval($member['uid']) : -1;
}

if(submitcheck('pmsubmit')) {
	$deleted = native_pm::deleteThreadsByAdmin($_GET['deleteplid']);
	cpmsg('members_pm_delete_succeed', 'action=members&operation=pm'.($username !== '' ? '&username='.rawurlencode($username) : ($uid > 0 ? '&uid='.$uid : '')).'&page='.$page, 'succeed', ['number' => $deleted]);
}

$where = $uid ? 'WHERE EXISTS (SELECT 1 FROM '.DB::table('common_pm_member').' pm_member WHERE pm_member.plid=t.plid AND pm_member.uid='.intval($uid).')' : '';
$ppp = 50;
$start = ($page - 1) * $ppp;
$count = DB::result_first('SELECT COUNT(*) FROM %t t %i', ['common_pm_thread', $where]);
$threads = DB::fetch_all('SELECT t.*, (SELECT COUNT(*) FROM %t p WHERE p.plid=t.plid) AS messagecount, (SELECT GROUP_CONCAT(m.uid ORDER BY m.uid SEPARATOR \',\') FROM %t m WHERE m.plid=t.plid) AS memberids FROM %t t %i ORDER BY t.lastdateline DESC %i', [
	'common_pm_message', 'common_pm_member', 'common_pm_thread', $where, DB::limit($start, $ppp),
]);

$memberids = [];
foreach($threads as $thread) {
	$memberids = array_merge($memberids, dintval(explode(',', $thread['memberids']), true));
}
$members = $memberids ? table_common_member::t()->fetch_all_username_by_uid(array_unique($memberids)) : [];
$multipage = multi($count, $ppp, $page, ADMINSCRIPT.'?action=members&operation=pm'.($username !== '' ? '&username='.rawurlencode($username) : ($uid > 0 ? '&uid='.$uid : '')));

shownav('user', 'menu_members_pm');
showsubmenu('menu_members_pm');
showformheader('members&operation=pm', '', 'pmsearchform', 'get');
showtableheader('members_pm_search');
showsetting('members_pm_search_uid', 'uid', $uid > 0 ? $uid : '', 'text');
showsetting('members_pm_search_username', 'username', $username, 'text');
showsubmit('searchsubmit', 'search');
showtablefooter();
showformfooter();

showformheader('members&operation=pm', '', 'pmform');
showhiddenfields(['uid' => $uid > 0 ? $uid : '', 'username' => $username, 'page' => $page]);
showtableheader('members_pm', 'fixpadding');
showsubmit('pmsubmit', 'delete', '', '', $multipage, true, "onclick=\"return confirm('".cplang('members_pm_delete_confirm')."');\"");
showtablerow('class="header"', ['', 'class="td24"', 'class="td25"', '', '', 'class="td23"'], [
	'<input type="checkbox" class="checkbox" onclick="checkAll(\'value\', this.form, \'deleteplid\')">',
	cplang('members_pm_type'), cplang('members_pm_members'), cplang('members_pm_subject'), cplang('members_pm_summary'), cplang('members_pm_messages'),
]);
foreach($threads as $thread) {
	$names = [];
	foreach(dintval(explode(',', $thread['memberids']), true) as $memberid) {
		$names[] = isset($members[$memberid]) ? '<a href="home.php?mod=space&uid='.$memberid.'" target="_blank">'.dhtmlspecialchars($members[$memberid]).'</a>' : '#'.$memberid;
	}
	showtablerow('', ['', '', '', '', '', ''], [
		'<input type="checkbox" class="checkbox" name="deleteplid[]" value="'.$thread['plid'].'">',
		$thread['pmtype'] == 2 ? cplang('members_pm_type_chat') : cplang('members_pm_type_private'),
		implode(', ', $names),
		dhtmlspecialchars($thread['subject']),
		dhtmlspecialchars(cutstr(strip_tags($thread['lastsummary']), 120, '...')).'<br><span class="lightfont">'.dgmdate($thread['lastdateline'], 'u').'</span>',
		intval($thread['messagecount']),
	]);
}
if(!$threads) {
	showtablerow('', '', '<div class="infobox">'.cplang('members_pm_empty').'</div>');
}
showtablefooter();
showformfooter();
