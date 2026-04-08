<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['setting']['friendstatus']) {
	showmessage('friend_status_off');
}

$perpage = 24;
$perpage = mob_perpage($perpage);

$list = $ols = $fuids = [];
$count = 0;
$page = empty($_GET['page']) ? 0 : intval($_GET['page']);
if($page < 1) $page = 1;
$start = ($page - 1) * $perpage;

$_GET['view'] = in_array($_GET['view'], ['online', 'visitor', 'trace', 'blacklist', 'me']) ? $_GET['view'] : 'me';
$_GET['order'] = in_array($_GET['order'], ['hot', 'dateline']) ? $_GET['order'] : 'dateline';

ckstart($start, $perpage);

if($_GET['view'] == 'online') {
	$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view=online";
	$actives = ['me' => ' class="a"'];
	$formatonlinename = function($value) {
		$value['referrer'] = isset($value['referrer']) ? $value['referrer'] : '';
		$value['location'] = trim(isset($value['location']) ? $value['location'] : '');
		if(($value['location'] === '' || $value['referrer'] === '') && strpos($value['username'], "\n") !== false) {
			list($legacyusername, $legacyreferrer) = explode("\n", $value['username'], 2);
			$value['referrer'] = $value['referrer'] !== '' ? $value['referrer'] : $legacyreferrer;
			if($value['groupid'] == 8 && strpos($legacyusername, "\t") !== false) {
				list($value['username'], $legacylocation) = explode("\t", $legacyusername, 2);
				$value['location'] = $value['location'] !== '' ? $value['location'] : trim($legacylocation);
			} elseif($value['groupid'] == 7) {
				$value['username'] = '';
				$value['location'] = $value['location'] !== '' ? $value['location'] : trim($legacyusername);
			} else {
				$value['username'] = $legacyusername;
			}
		}
		if($value['groupid'] == 7) {
			$value['username'] = lang('forum/misc', 'guestuser').($value['location'] !== '' ? ' '.$value['location'] : '');
		} elseif($value['groupid'] == 8 && $value['location'] !== '') {
			$value['username'] = trim($value['username'].' '.$value['location']);
		}
		return $value;
	};

	space_merge($space, 'field_home');
	$onlinedata = [];
	$wheresql = '';
	if($_GET['type'] == 'near') {
		$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view=online&type=near";
		if(($count = C::app()->session->count_by_ip($_G['clientip']))) {
			$onlinedata = C::app()->session->fetch_all_by_ip($_G['clientip'], $start, $perpage);
		}
	} elseif($_GET['type'] == 'friend') {
		$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view=online&type=friend";
		if(!empty($space['feedfriend'])) {
			$onlinedata = C::app()->session->fetch_all_by_uid(explode(',', $space['feedfriend']), $start, $perpage);
		}
		$count = count($onlinedata);
	} elseif($_GET['type'] == 'member') {
		$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view=online&type=member";
		$count = DB::result_first('SELECT COUNT(*) FROM '.DB::table('common_session').' WHERE invisible = 0 AND uid > 0');
		if($count) {
			$onlinedata = DB::fetch_all(
				'SELECT s.*, '.(DISCUZ_LANG == 'EN/' ? 'f.name_en AS name' : 'f.name').', t.subject
				FROM '.DB::table('common_session').' AS s
				LEFT JOIN '.DB::table('forum_forum').' AS f ON s.fid=f.fid
				LEFT JOIN '.DB::table('forum_thread').' AS t ON s.tid=t.tid
				WHERE invisible = 0 AND uid > 0
				ORDER BY groupid=8 ASC,groupid=7 ASC,lastactivity DESC'.DB::limit($start, $perpage),
				null,
				'sid'
			);
			$actioncode = lang('action');
			loadcache('onlinelist');
			foreach($onlinedata as $key => $value) {
				$value['lastactivity'] = dgmdate($value['lastactivity'], 't');
				$value['action'] = $actioncode[$value['action']];
				$value = $formatonlinename($value);
				$value['icon'] = !empty($_G['cache']['onlinelist'][$value['groupid']]) ? $_G['cache']['onlinelist'][$value['groupid']] : $_G['cache']['onlinelist'][0];
				$onlinedata[$key] = $value;
			}
			unset($actioncode);
		}
	} else {
		$_GET['type'] = 'all';
		$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view=online";
		if(($count = C::app()->session->count_invisible(0))) {
			$onlinedata = DB::fetch_all(
				'SELECT s.*, '.(DISCUZ_LANG == 'EN/' ? 'f.name_en AS name' : 'f.name').', t.subject
				FROM '.DB::table('common_session').' AS s
				LEFT JOIN '.DB::table('forum_forum').' AS f ON s.fid=f.fid
				LEFT JOIN '.DB::table('forum_thread').' AS t ON s.tid=t.tid
				WHERE invisible = 0
				ORDER BY groupid=8 ASC,groupid=7 ASC,lastactivity DESC'.DB::limit($start, $perpage),
				null,
				'sid'
			);
			$actioncode = lang('action');
			loadcache('onlinelist');
			foreach($onlinedata as $key => $value) {
				$value['lastactivity'] = dgmdate($value['lastactivity'], 't');
				$value['action'] = $actioncode[$value['action']];
				$value = $formatonlinename($value);
				$value['icon'] = !empty($_G['cache']['onlinelist'][$value['groupid']]) ? $_G['cache']['onlinelist'][$value['groupid']] : $_G['cache']['onlinelist'][0];
				$onlinedata[$key] = $value;
			}
			unset($actioncode);
		}
	}

	if($count) {

		foreach($onlinedata as $value) {
			if($_GET['type'] == 'near') {
				if($value['uid'] == $space['uid']) {
					$count = $count - 1;
					continue;
				}
			}

			if(!$value['invisible']) $ols[$value['uid']] = $value['lastactivity'];
			$list[$value['uid']] = $value;
			$fuids[$value['uid']] = $value['uid'];
		}

		if($fuids) {
			require_once libfile('function/friend');
			friend_check($space['uid'], $fuids);

			$fieldhome = table_common_member_field_home::t()->fetch_all($fuids);
			foreach(table_common_member::t()->fetch_all($fuids) as $uid => $value) {
				$value = array_merge($value, $fieldhome[$uid]);
				$value['isfriend'] = $uid == $space['uid'] || $_G['home_friend_'.$space['uid'].'_'.$uid] ? 1 : 0;
				$list[$uid] = array_merge($list[$uid], $value);
			}
		}
	}
	$multi = multi($count, $perpage, $page, $theurl);

} elseif($_GET['view'] == 'visitor' || $_GET['view'] == 'trace') {

	$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view={$_GET['view']}";
	$actives = ['me' => ' class="a"'];

	if($_GET['view'] == 'visitor') {
		$count = table_home_visitor::t()->count_by_uid($space['uid']);
	} else {
		$count = table_home_visitor::t()->count_by_vuid($space['uid']);
	}
	if($count) {
		if($_GET['view'] == 'visitor') {
			$visitors = table_home_visitor::t()->fetch_all_by_uid($space['uid'], $start, $perpage);
		} else {
			$visitors = table_home_visitor::t()->fetch_all_by_vuid($space['uid'], $start, $perpage);
		}
		foreach($visitors as $value) {
			if($_GET['view'] == 'visitor') {
				$value['uid'] = $value['vuid'];
				$value['username'] = $value['vusername'];
			}
			$fuids[] = $value['uid'];
			$list[$value['uid']] = $value;
		}
	}
	$multi = multi($count, $perpage, $page, $theurl);

} elseif($_GET['view'] == 'blacklist') {

	$theurl = "home.php?mod=space&uid={$space['uid']}&do=friend&view={$_GET['view']}";
	$actives = ['me' => ' class="a"'];

	$count = table_home_blacklist::t()->count_by_uid_buid($space['uid']);
	if($count) {
		$backlist = table_home_blacklist::t()->fetch_all_by_uid($space['uid'], $start, $perpage);
		$members = table_common_member::t()->fetch_all(array_keys($backlist));
		foreach($backlist as $buid => $value) {
			$value = array_merge($value, $members[$buid]);
			$value['isfriend'] = 0;
			$fuids[] = $value['uid'];
			$list[$value['uid']] = $value;
		}
	}
	$multi = multi($count, $perpage, $page, $theurl);

} else {

	$theurl = "home.php?mod=space&uid={$space['uid']}&do=$do";
	$actives = ['me' => ' class="a"'];

	$_GET['view'] = 'me';

	$querydata = [];
	if($space['self']) {
		require_once libfile('function/friend');
		$groups = friend_group_list();
		$group = !isset($_GET['group']) ? '-1' : intval($_GET['group']);
		if($group > -1) {
			$querydata['gid'] = $group;
			$theurl .= "&group=$group";
		}
	}
	if($_GET['searchkey']) {
		require_once libfile('function/search');
		$querydata['searchkey'] = $_GET['searchkey'];
		$theurl .= "&searchkey={$_GET['searchkey']}";
	}

	$count = table_home_friend::t()->fetch_all_search($space['uid'], $querydata['gid'], $querydata['searchkey'], true);
	$membercount = table_common_member_count::t()->fetch($_G['uid']);
	$friendnum = $membercount['friends'];
	unset($membercount);
	if($count) {

		$query = table_home_friend::t()->fetch_all_search($space['uid'], $querydata['gid'], $querydata['searchkey'], false, $start, $perpage, (bool)$_GET['order']);
		foreach($query as $value) {
			$value['uid'] = $value['fuid'];
			$_G['home_friend_'.$space['uid'].'_'.$value['uid']] = $value['isfriend'] = 1;
			$fuids[$value['uid']] = $value['uid'];
			$list[$value['uid']] = $value;
		}
	} elseif(!$friendnum) {
		if(($specialuser_count = table_home_specialuser::t()->count_by_status(1))) {
			foreach(table_home_specialuser::t()->fetch_all_by_status(1, 7) as $value) {
				if($_G['uid'] !== $value['uid']) {
					$fuids[$value['uid']] = $value['uid'];
					$specialuser_list[$value['uid']] = $value;
				}
				if(count($fuids) >= 6) {
					break;
				}
			}
			$specialuser_list = getfollowflag($specialuser_list);

		}
		if(($online_count = C::app()->session->count(1)) > 1) {
			$oluids = $online_list = [];
			foreach(C::app()->session->fetch_member(1, 2, 7) as $value) {
				if($value['uid'] != $_G['uid'] && count($oluids) <= 6) {
					$fuids[$value['uid']] = $value['uid'];
					$oluids[$value['uid']] = $value['uid'];
					$online_list[$value['uid']] = $value;
				}
			}
			$online_list = getfollowflag($online_list);

			$fieldhome = table_common_member_field_home::t()->fetch_all($oluids, false, 0);
			foreach(table_common_member::t()->fetch_all($oluids, false, 0) as $uid => $value) {
				$value = array_merge($value, $fieldhome[$uid]);
				$online_list[$uid] = array_merge($online_list[$uid], $value);
			}

		}
	}

	$diymode = 1;
	if($space['self'] && ($_GET['from'] != 'space' || !$_G['setting']['homepagestyle'])) $diymode = 0;
	if($diymode) {
		$theurl .= '&from=space';
	}

	$multi = multi($count, $perpage, $page, $theurl);

	if($space['self']) {
		$groupselect = [$group => ' class="a"'];

		$maxfriendnum = checkperm('maxfriendnum');
		if($maxfriendnum) {
			$maxfriendnum = checkperm('maxfriendnum') + $space['addfriend'];
		}
	}
}

if($fuids) {
	foreach(C::app()->session->fetch_all_by_uid($fuids) as $value) {
		if(!$value['invisible']) {
			$ols[$value['uid']] = $value['lastactivity'];
		} elseif($list[$value['uid']] && !in_array($_GET['view'], ['me', 'trace', 'blacklist'])) {
			unset($list[$value['uid']]);
			$count = $count - 1;
		}
	}
	if($_GET['view'] != 'me') {
		require_once libfile('function/friend');
		friend_check($fuids);
	}
	if($list) {
		$fieldhome = table_common_member_field_home::t()->fetch_all($fuids);
		foreach(table_common_member::t()->fetch_all($fuids) as $uid => $value) {
			$fieldhome_value = is_array($fieldhome[$uid]) ? $fieldhome[$uid] : [];
			$value = array_merge($value, $fieldhome_value);
			$value['isfriend'] = $uid == $space['uid'] || $_G['home_friend_'.$space['uid'].'_'.$uid] ? 1 : 0;
			if(empty($list[$uid])) $list[$uid] = [];
			$list[$uid] = array_merge($list[$uid], $value);
		}
	}
}
if($list) {
	$list = getfollowflag($list);
}
$navtitle = lang('core', 'title_friend_list');

$navtitle = lang('space', 'sb_friend', ['who' => $space['username']]);
$metakeywords = lang('space', 'sb_friend', ['who' => $space['username']]);
$metadescription = lang('space', 'sb_share', ['who' => $space['username']]);

$a_actives = [$_GET['view'].$_GET['type'] => ' class="a"'];
include_once template('diy:home/space_friend');

function getfollowflag($data) {
	global $_G;
	if($data) {
		$follows = table_home_follow::t()->fetch_all_by_uid_followuid($_G['uid'], array_keys($data));
		foreach($data as $uid => $value) {
			$data[$uid]['follow'] = isset($follows[$uid]) ? 1 : 0;
		}
	}
	return $data;
}

