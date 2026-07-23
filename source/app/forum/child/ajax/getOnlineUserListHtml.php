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
$membercount = 0;
$guestcount = 0;
$invisiblecount = 0;
$onlinenum = 0;
if($_G['setting']['whosonlinestatus'] == 1 || $_G['setting']['whosonlinestatus'] == 3) {
	updatesession();

	$actioncode = lang('action');
	$maxonlinelist = isset($_G['setting']['maxonlinelist']) && $_G['setting']['maxonlinelist'] !== '' ? intval($_G['setting']['maxonlinelist']) : 500;
	$memberlimit = $maxonlinelist > 0 ? $maxonlinelist : 0;

	$sessions = C::app()->session->fetch_member(1, 0, $memberlimit);

	$membercount_fetched = count($sessions);
	$sessions_guests = [];
	if($maxonlinelist == 0 || $maxonlinelist > $membercount_fetched) {
		$guestlimit = $maxonlinelist > 0 ? ($maxonlinelist - $membercount_fetched) : 0;
		$sessions_guests = C::app()->session->fetch_member(2, 0, $guestlimit);
	}
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
				$titleLabel = $forumlist[$online['fid']]['name'];
			} elseif(!empty($actioncode[$online['action']])) {
				$titleLabel = $actioncode[$online['action']];
			}
			$online_user['title_label'] = htmlspecialchars($titleLabel);
			$online_user['ip'] = htmlspecialchars($online['ip']);
			$online_user['ip_location'] = htmlspecialchars(ip::convert($online['ip']));
			$online_user['lastactivity'] = htmlspecialchars(dgmdate($online['lastactivity'], 'u'));
			$whosonline[] = $online_user;
		}
	}

	foreach($sessions_guests as $online) {
		$online_user = [];
		$online_user['uid'] = 0;
		$location = ip::format_session_location($online['location'] ?? '', $online['city'] ?? null);
		$isRobot = intval($online['groupid']) === 8;
		$online_user['username'] = htmlspecialchars($isRobot ? $location['organization'] : $location['compact']);
		$online_user['network_title'] = htmlspecialchars($isRobot ? $location['asn'] : $location['network']);
		$online_user['icon'] = $isRobot
			? ($_G['cache']['onlinelist'][8] ?? STATICURL.'image/common/online_bot.svg')
			: $_G['cache']['onlinelist'][7];
		$online_user['tid'] = $online['tid'];
		$titleLabel = '';
		if(!empty($online['fid']) && !empty($forumlist[$online['fid']])) {
			$titleLabel = $forumlist[$online['fid']]['name'];
		} elseif(!empty($actioncode[$online['action']])) {
			$titleLabel = $actioncode[$online['action']];
		}
		$online_user['title_label'] = htmlspecialchars($titleLabel);
		$online_user['ip'] = htmlspecialchars($online['ip']);
		$online_user['ip_location'] = '';
		$online_user['lastactivity'] = htmlspecialchars(dgmdate($online['lastactivity'], 'u'));
		$whosonline[] = $online_user;
	}

	$membercount = C::app()->session->count(1);
	$guestcount = C::app()->session->count(2);
	$invisiblecount = C::app()->session->count_invisible();
	$onlinenum = $membercount + $guestcount;
}

ob_start();
include template('forum/ajax_whosonline_list');
$html = ob_get_clean();
header('Content-Type: application/json; charset='.CHARSET);
echo json_encode([
	'html' => $html,
	'onlinenum' => intval($onlinenum),
	'membercount' => intval($membercount),
	'guestcount' => intval($guestcount),
	'invisiblecount' => intval($invisiblecount),
]);
exit();
