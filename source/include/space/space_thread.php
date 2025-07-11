<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_thread.php 31365 2012-08-20 03:19:33Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!$_G['setting']['forumstatus']) {
	showmessage('forum_status_off');
}

$minhot = $_G['setting']['feedhotmin']<1?3:$_G['setting']['feedhotmin'];
$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;
$id = empty($_GET['id'])?0:intval($_GET['id']);
$opactives['thread'] = 'class="a"';

$_GET['view'] = in_array($_GET['view'], array('we', 'me', 'all')) ? $_GET['view'] : 'we';
$_GET['order'] = in_array(getgpc('order'), array('hot', 'dateline')) ? $_GET['order'] : 'dateline';

$allowviewuserthread = $_G['setting']['allowviewuserthread'];

$perpage = 40;
$start = ($page-1)*$perpage;

$list = array();
$userlist = array();
$hiddennum = $count = $pricount = 0;
$_GET['from'] = dhtmlspecialchars(preg_replace("/[^\[A-Za-z0-9_\]]/", '', getgpc('from')));
$gets = array(
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'thread',
	'fid' => getgpc('fid'),
	'view' => $_GET['view'],
	'type' => getgpc('type'),
	'order' => $_GET['order'],
	'fuid' => getgpc('fuid'),
	'searchkey' => getgpc('searchkey'),
	'from' => $_GET['from'],
	'filter' => getgpc('filter')
);
$theurl = 'home.php?'.url_implode($gets);
unset($gets['fid']);
$forumurl = 'home.php?'.url_implode($gets);
$multi = '';
$authorid = 0;
$replies = $closed = $displayorder = null;
$dglue = '=';
$vfid = getgpc('fid') ? intval($_GET['fid']) : null;

require_once libfile('function/misc');
require_once libfile('function/forum');
loadcache(array('forums'));
$fids = $comma = '';
if($_GET['view'] != 'me') {
	$displayorder = 0;
	$dglue = '>=';
}
$f_index = '';
$ordersql = 't.dateline DESC';
$need_count = true;
$viewuserthread = false;
$viewtype = in_array(getgpc('type'), array('reply', 'thread', 'postcomment')) ? $_GET['type'] : 'thread';
$orderactives = array($viewtype => ' class="a"');

if($_GET['view'] == 'me') {

	if($_GET['from'] == 'space') $diymode = 1;
	$allowview = true;
	$filter = in_array(getgpc('filter'), array('recyclebin', 'ignored', 'save', 'aduit', 'close', 'common')) ? $_GET['filter'] : '';
	if($space['uid'] != $_G['uid'] && in_array($viewtype, array('reply', 'thread'))) {
		if($allowviewuserthread === -1 && $_G['adminid'] != 1) {
			$allowview = false;
		}
		if($allowview) {
			$viewuserthread = true;
			$viewfids = str_replace("'", '', $allowviewuserthread);
			if(!empty($viewfids)) {
				$viewfids = explode(',', $viewfids);
			}
		}
	}

	if($viewtype == 'thread' && $allowview) {
		$authorid = $space['uid'];




		if($filter == 'recyclebin') {
			$displayorder = -1;
		} elseif($filter == 'aduit') {
			$displayorder = -2;
		} elseif($filter == 'ignored') {
			$displayorder = -3;
		} elseif($filter == 'save') {
			$displayorder = -4;
		} elseif($filter == 'close') {
			$closed = 1;
		} elseif($filter == 'common') {
			$closed = 0;
			$displayorder = 0;
			$dglue = '>=';
		}

		$ordersql = 't.tid DESC';
	} elseif($allowview) {
		$invisible = null;

		$postsql = $threadsql = '';
		if($filter == 'recyclebin') {
			$invisible = -5;
		} elseif($filter == 'aduit') {
			$invisible = -2;
		} elseif($filter == 'save' || $filter == 'ignored') {
			$invisible = -3;
			$displayorder = -4;
		} elseif($filter == 'close') {
			$closed = 1;
		} elseif($filter == 'common') {
			$invisible = 0;
			$displayorder = 0;
			$dglue = '>=';
			$closed = 0;
		} else {
			if($space['uid'] != $_G['uid']) {
				$invisible = 0;
			}
		}
		require_once libfile('function/post');
		$posts =  $viewtype == 'postcomment' ? C::t('forum_postcomment')->fetch_all_by_authorid($space['uid'], $start, $perpage) : C::t('forum_post')->fetch_all_by_authorid(0, $space['uid'], true, 'DESC', $start, $perpage, 0, $invisible, $vfid);
		foreach($posts as $pid => $post) {
			$delrow = false;
			if($post['anonymous'] && $post['authorid'] != $_G['uid']) {
				$delrow = true;
			} elseif($viewuserthread && $post['authorid'] != $_G['uid']) {
				if(($_G['adminid'] != 1 && !empty($viewfids) && !in_array($post['fid'], $viewfids))) {
					$delrow = true;
				}
			}
			if($delrow) {
				unset($posts[$pid]);
				$hiddennum++;
			} else {
				$posts[$pid]['message'] = $post['status'] & 1 && $_G['adminid'] != 1 ? '' : (!getstatus($post['status'], 2) || $post['authorid'] == $_G['uid'] ? ($viewtype == 'postcomment' ? $post['comment'] : (dhtmlspecialchars(messagecutstr($post['message'],100,null,$post['htmlon']))?:'......')) : '');
			}
		}
		if(!empty($posts)) {
			$currentGroup = []; // This will hold the current group of posts with the same tid
			$stid = 0;
			foreach ($posts as $pid => &$post) {
				if (empty($currentGroup)) {
					// If the current group is empty, initialize it with the current post's tid
					$list[$stid] = $post['tid'];
				}
				if (empty($currentGroup) || end($list) === $post['tid']) {
					// If the current group is empty or if the current post's tid matches the tid of the last post in the current group, add the current post to the current group
					$currentGroup[] = $pid;
				} else {
					// If the tid has changed, the current group is complete. Add it to the main result.
					$tids[$stid] = $currentGroup;
					// Start a new group with the current post
					$list[++$stid] = $post['tid'];
					$currentGroup = [$pid];
				}
			}
			// After the loop, there might be a current group that hasn't been added to $tids yet
			// (this happens for the very last group of posts, or if all posts had the same tid).
			// So, add it if it's not empty.
			$tids[$stid] = $currentGroup;
			unset($stid,$currentGroup);
			$threads = C::t('forum_thread')->fetch_all_by_tid_displayorder($list, $displayorder, $dglue, array(), $closed);
			foreach($threads as $tid => $thread) {
				$delrow = false;
				if($_G['adminid'] != 1 && $thread['displayorder'] < 0) {
					$delrow = true;
				} elseif($_G['adminid'] != 1 && $_G['uid'] != $thread['authorid'] && getstatus($thread['status'], 2)) {
					$delrow = true;
				} elseif(!isset($_G['cache']['forums'][$thread['fid']])) {
					if(!$_G['setting']['groupstatus']) {
						$delrow = true;
					} else {
						$gids[$thread['fid']] = $thread['tid'];
					}
				}
				if($delrow) {
					unset($threads[$tid]);
					continue;
				} else {
					$threads[$tid] = procthread($thread);
					$forums[$thread['fid']] = $threads[$tid]['forumname'];
				}

			}
			if(!empty($gids)) {
				$groupforums = C::t('forum_forum')->fetch_all_name_by_fid(array_keys($gids));
				foreach($gids as $fid => $tid) {
					$threads[$tid]['forumname'] = $groupforums[$fid]['name'];
					$forums[$fid] = $groupforums[$fid]['name'];
				}
			}
			foreach($list as $stid => $tid) {
				if(isset($threads[$tid])) {
					$list[$stid] = $threads[$tid];
				} else {
					unset($list[$stid]);
					foreach($tids[$stid] as $pid) {
						unset($posts[$pid]);
						$hiddennum++;
					}
					unset($tids[$stid]);
				}
			}
			unset($threads);
		}
		if($viewtype == 'postcomment') {
			$multi = multi(C::t('forum_postcomment')->count_by_authorid($space['uid']), $perpage, $page, $theurl);
		} else {
			space_merge($space, 'count');
			$multi = multi($space['posts'] - $space['threads'], $perpage, $page, $theurl);
		}
		$need_count = false;
	}
	if(!$allowview) {
		$need_count = false;
	}
} else {

	if(!$_G['setting']['friendstatus']) {
		showmessage('friend_status_off');
	}

	space_merge($space, 'field_home');

	if($space['feedfriend']) {

		$fuid_actives = array();

		require_once libfile('function/friend');
		$fuid = intval($_GET['fuid']);
		if($fuid && friend_check($fuid, $space['uid'])) {
			$authorid = $fuid;
			$fuid_actives = array($fuid=>' selected');
		} else {
			$authorid = explode(',', $space['feedfriend']);
		}

		$query = C::t('home_friend')->fetch_all_by_uid($_G['uid'], 0, 100, true);
		foreach($query as $value) {
			$userlist[] = $value;
		}
	} else {
		$need_count = false;
	}
}

$actives = array($_GET['view'] =>' class="a"');

if($need_count) {

	if($searchkey = stripsearchkey(getgpc('searchkey'))) {
		$searchkey = dhtmlspecialchars($searchkey);
	}


	loadcache('forums');
	$gids = $fids = $forums = array();

	foreach(C::t('forum_thread')->fetch_all_by_authorid_displayorder($authorid, $displayorder, $dglue, $closed, $searchkey, $start, $perpage, $replies, $vfid) as $tid => $value) {
		if(empty($value['author']) && $value['authorid'] != $_G['uid']) {
			$hiddennum++;
			continue;
		} elseif($viewuserthread && $value['authorid'] != $_G['uid']) {
			if(($_G['adminid'] != 1 && !empty($viewfids) && !in_array($value['fid'], $viewfids)) || $value['displayorder'] < 0) {
				$hiddennum++;
				continue;
			}
		}
		if(!isset($_G['cache']['forums'][$value['fid']])) {
			if(!$_G['setting']['groupstatus']) {
				$hiddennum++;
				continue;
			} else {
				$gids[$value['fid']] = $value['tid'];
			}
		}
		$list[$value['tid']] = procthread($value);
		$forums[$value['fid']] = $list[$value['tid']]['forumname'];
	}

	if(!empty($gids)) {
		$gforumnames = C::t('forum_forum')->fetch_all_name_by_fid(array_keys($gids));
		foreach($gids as $fid => $tid) {
			$list[$tid]['forumname'] = $gforumnames[$fid]['name'];
			$forums[$fid] = $gforumnames[$fid]['name'];
		}
	}

	$threads = &$list;
	space_merge($space, 'count');
	$multi = multi($space['threads'], $perpage, $page, $theurl);
}

require_once libfile('function/forumlist');
$forumlist = forumselect(FALSE, 0, intval(getgpc('fid')));
dsetcookie('home_diymode', $diymode);

if($_G['uid']) {
	$_GET['view'] = !$_GET['view'] ? 'we' : $_GET['view'];
	$navtitle = lang('core', 'title_'.$_GET['view'].'_thread');
} else {
	$navtitle = lang('core', 'title_thread');
}

if($space['username']) {
	$navtitle = lang('space', 'sb_thread', array('who' => $space['username']));
}
$metakeywords = $navtitle;
$metadescription = $navtitle;
if(!getglobal('follow')) {
	include_once template("diy:home/space_thread");
}
?>