<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

loaducenter();

if(!submitcheck('mergesubmit', 1)) {

	shownav('user', 'nav_members');
	showsubmenu('nav_members', [
		['search', 'members&operation=search', 0],
		['clean', 'members&operation=clean', 0],
		['nav_repeat', 'members&operation=repeat', 0],
		['members_merge', 'members&operation=merge', 1],
		['add', 'members&operation=add', 0],
	]);

	showformheader('members&operation=merge');
	showtableheader();
	showsetting('members_merge_source', 'source', '', 'text');
	showsetting('members_merge_target', 'target', '', 'text');
	showsubmit('mergesubmit');
	showtablefooter();
	showformfooter();

} else {

	$source = trim((string)($_GET['source'] ?? ''));
	$target = trim((string)($_GET['target'] ?? ''));
	if($source == $target) {
		cpmsg('members_sameness', '', 'error');
	}
	$sourcememberinfo = table_common_member::t()->fetch_by_username($source, 1);
	if(!$sourcememberinfo || $sourcememberinfo['adminid'] == 1 || $sourcememberinfo['groupid'] == 1 || native_user_isprotected($sourcememberinfo)) {
		cpmsg('members_dont_contain_admin_merge', '', 'error');
	}
	$suid = intval($sourcememberinfo['uid']);
	$sourcemember = $sourcememberinfo['username'];

	$targetmemberinfo = table_common_member::t()->fetch_by_username($target, 1);
	if(!$targetmemberinfo || !$suid) {
		cpmsg('members_merge_invalid', '', 'error');
	}
	$tuid = intval($targetmemberinfo['uid']);
	$targetmember = $targetmemberinfo['username'];

	if(!submitcheck('confirmed')) {

		cpmsg('members_merge_confirm', 'action=members&operation=merge&mergesubmit=yes&confirmed=yes', 'form', ['sourcemember' => $sourcemember, 'targetmember' => $targetmember],
			'<input type="hidden" name="target" value="'.dhtmlspecialchars($target).'"><input type="hidden" name="source" value="'.dhtmlspecialchars($source).'">');

	} else {

		DB::delete('forum_access', ['uid' => $suid]);
		DB::update('common_adminnote', ['admin' => $targetmember], ['admin' => $sourcemember]);
		DB::update('common_admincp_session', ['uid' => $tuid], ['uid' => $suid]);
		DB::update('forum_announcement', ['author' => $targetmember], ['author' => $sourcemember]);
		DB::update('common_banned', ['admin' => $targetmember], ['admin' => $sourcemember]);
		DB::update('home_favorite', ['uid' => $tuid], ['uid' => $suid]);
		DB::update('common_member_validate', ['admin' => $targetmember], ['admin' => $sourcemember]);
		DB::delete('common_member_validate', ['uid' => $suid]);
		DB::delete('common_onlinetime', ['uid' => $suid]);
		DB::delete('forum_spacecache', ['uid' => $suid]);
		DB::update('common_credit_log', ['uid' => $tuid], ['uid' => $suid]);
		DB::update('common_credit_log_field', ['uid' => $tuid], ['uid' => $suid]);
		DB::update('common_credit_rule_log', ['uid' => $tuid], ['uid' => $suid]);
		DB::update('common_credit_rule_log_field', ['uid' => $tuid], ['uid' => $suid]);

		foreach([['forum_moderator', 'fid'], ['common_admincp_session', 'panel'], ['common_member_grouppm', 'gpmid'], ['common_pm_member', 'plid'], ['common_pm_message_status', 'pmid']] as [$table, $other]) {
			$keys = DB::fetch_all('SELECT DISTINCT %i AS k FROM %t WHERE uid=%d', [$other, $table, $suid], 'k');
			if($keys) {
				DB::query('DELETE FROM %t WHERE uid=%d AND %i IN(%n)', [$table, $tuid, $other, array_keys($keys)]);
			}
			DB::update($table, ['uid' => $tuid], ['uid' => $suid]);
		}
		DB::delete('common_pm_blacklist', ['uid' => $tuid]);
		DB::update('common_pm_blacklist', ['uid' => $tuid], ['uid' => $suid]);
		DB::update('common_pm_thread', ['authorid' => $tuid, 'lastauthorid' => $tuid], ['authorid' => $suid]);
		DB::update('common_pm_thread', ['lastauthorid' => $tuid], ['lastauthorid' => $suid]);
		DB::update('common_pm_message', ['authorid' => $tuid], ['authorid' => $suid]);

		DB::update('forum_post', ['author' => $targetmember, 'authorid' => $tuid], ['authorid' => $suid]);
		DB::update('forum_thread', ['author' => $targetmember, 'authorid' => $tuid], ['authorid' => $suid]);
		DB::update('forum_thread', ['lastposter' => $targetmember], ['lastposter' => $sourcemember]);
		DB::update('forum_threadmod', ['uid' => $tuid, 'username' => $targetmember], ['uid' => $suid]);

		$shards = DB::fetch_all('SELECT DISTINCT tableid FROM %t WHERE uid=%d', ['forum_attachment', $suid], 'tableid');
		foreach(array_keys($shards) as $tid) {
			if($tid != 127) {
				DB::update('forum_attachment_'.$tid, ['uid' => $tuid], ['uid' => $suid]);
			}
		}
		DB::update('forum_attachment', ['uid' => $tuid], ['uid' => $suid]);

		$member = DB::fetch_first('SELECT credits FROM %t WHERE uid=%d', ['common_member', $suid]);
		$credit = DB::fetch_first('SELECT extcredits1, extcredits2, extcredits3, extcredits4, extcredits5, extcredits6, extcredits7, extcredits8, posts, threads, digestposts, attachsize, views, oltime, todayattachs, todayattachsize FROM %t WHERE uid=%d', ['common_member_count', $suid]);
		if($member && intval($member['credits'])) {
			DB::query('UPDATE %t SET credits=credits+%d WHERE uid=%d', ['common_member', intval($member['credits']), $tuid]);
		}
		$set = [];
		foreach(['extcredits1', 'extcredits2', 'extcredits3', 'extcredits4', 'extcredits5', 'extcredits6', 'extcredits7', 'extcredits8', 'posts', 'threads', 'digestposts', 'attachsize', 'views', 'oltime', 'todayattachs', 'todayattachsize'] as $col) {
			if($credit && intval($credit[$col])) {
				$set[] = DB::field($col, intval($credit[$col]), '+');
			}
		}
		if($set) {
			DB::query('UPDATE %t SET '.implode(',', $set).' WHERE uid=%d', ['common_member_count', $tuid]);
		}

		if(empty($targetmemberinfo['avatarstatus']) && !empty($sourcememberinfo['avatarstatus'])) {
			native_user_transferavatar($suid, $tuid);
		}

		require_once libfile('function/delete');
		deletemember([$suid], 0);

		require_once libfile('function/cache');
		updatecache('setting');

		cpmsg('members_merge_succeed', '', 'succeed', ['sourcemember' => $sourcemember, 'targetmember' => $targetmember]);
	}
}
