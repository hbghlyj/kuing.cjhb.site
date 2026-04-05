<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$whosonline = [];
if($_G['setting']['whosonlinestatus'] == 1 || $_G['setting']['whosonlinestatus'] == 3) {
	$_G['uid'] && updatesession();

	$actioncode = lang('action');
	loadcache('onlinelist');
	$sessions = C::app()->session->fetch_member(1, 0);
	$sessions_guests = C::app()->session->fetch_member(2, 0);
	$forumIds = [];
	foreach($sessions as $online) {
		if(!empty($online['fid'])) {
			$forumIds[$online['fid']] = $online['fid'];
		}
	}
	foreach($sessions_guests as $online) {
		if(!empty($online['fid'])) {
			$forumIds[$online['fid']] = $online['fid'];
		}
	}
	$forumlist = $forumIds ? table_forum_forum::t()->fetch_all_info_by_fids(array_values($forumIds)) : [];

	foreach($sessions as $online) {
		if(!$online['invisible']) {
			$online_user = [];
			$online_user['uid'] = $online['uid'];
			$online_user['username'] = htmlspecialchars($online['username']);
			$online_user['icon'] = !empty($_G['cache']['onlinelist'][$online['groupid']]) ? $_G['cache']['onlinelist'][$online['groupid']] : $_G['cache']['onlinelist'][0];
			$online_user['tid'] = $online['tid'];
			$titleLabel = '';
			if(!empty($online['fid']) && !empty($forumlist[$online['fid']])) {
				$titleLabel = DISCUZ_LANG == 'EN/' && !empty($forumlist[$online['fid']]['name_en'])
					? $forumlist[$online['fid']]['name_en']
					: $forumlist[$online['fid']]['name'];
			} elseif(!empty($actioncode[$online['action']])) {
				$titleLabel = $actioncode[$online['action']];
			}
			$online_user['title_attr'] = htmlspecialchars($titleLabel).'&#013;'.$online['ip'].'&#013;'.ip::convert($online['ip']).'&#013;'.lang('template', 'time').': '.dgmdate($online['lastactivity'], 'u');
			$whosonline[] = $online_user;
		}
	}

	foreach($sessions_guests as $online) {
		$online_user = [];
		$online_user['uid'] = 0;
		$online_user['username'] = htmlspecialchars($online['username']);
		$online_user['icon'] = $_G['cache']['onlinelist'][7];
		$online_user['tid'] = $online['tid'];
		$titleLabel = '';
		if(!empty($online['fid']) && !empty($forumlist[$online['fid']])) {
			$titleLabel = DISCUZ_LANG == 'EN/' && !empty($forumlist[$online['fid']]['name_en'])
				? $forumlist[$online['fid']]['name_en']
				: $forumlist[$online['fid']]['name'];
		} elseif(!empty($actioncode[$online['action']])) {
			$titleLabel = $actioncode[$online['action']];
		}
		$online_user['title_attr'] = htmlspecialchars($titleLabel).'&#013;'.$online['ip'].'&#013;'.ip::convert($online['ip']).'&#013;'.lang('template', 'time').': '.dgmdate($online['lastactivity'], 'u');
		$whosonline[] = $online_user;
	}
}

include template('forum/ajax_whosonline_list');
exit();
