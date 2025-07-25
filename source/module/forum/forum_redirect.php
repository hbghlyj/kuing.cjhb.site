<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_redirect.php 28464 2012-03-01 06:35:27Z liulanbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

foreach(array('pid', 'ptid', 'authorid', 'ordertype', 'postno') as $k) {
	$$k = !empty($_GET[$k]) ? intval($_GET[$k]) : 0;
}

if(empty($_GET['goto']) && $ptid) {
	$_GET['goto'] = 'findpost';
}

if($_GET['goto'] == 'findpost') {
	header('Expires: 0');
	if(isset($_GET['delnotice'])) {
		DB::query("UPDATE ".DB::table('home_notification')." SET `new` = 0 WHERE id=%d AND uid=%d", array(intval($_GET['delnotice']), $_G['uid']));
	}

	$post = $thread = array();

	if($ptid) {
		$thread = get_thread_by_tid($ptid);
	}

	if($pid) {

		if($thread) {
			$post = C::t('forum_post')->fetch_post($thread['posttableid'], $pid);
		} else {
			$post = get_post_by_pid($pid);
		}

		if($post && empty($thread)) {
			$thread = get_thread_by_tid($post['tid']);
		}
	}

	if(empty($thread)) {
		showmessage('thread_nonexistence', '', array(), array('return' => true));
	} else {
		$tid = $thread['tid'];
	}

	if(empty($pid)) {

		if($postno) {
			if(getstatus($thread['status'], 3)) {
				$rowarr = C::t('forum_post')->fetch_all_by_tid_position($thread['posttableid'], $ptid, $postno);
				$pid = $rowarr[0]['pid'];
			}

			if($pid) {
				$post = C::t('forum_post')->fetch_post($thread['posttableid'], $pid);
				if($post['invisible'] != 0) {
					$post = array();
				}
			} else {
				$postno = $postno > 1 ? $postno - 1 : 0;
				$post = C::t('forum_post')->fetch_visiblepost_by_tid($thread['posttableid'], $ptid, $postno);
			}
		}

	}

	if(empty($post)) {
		if($ptid) {
			if(empty($pid)) {
				header("HTTP/1.1 301 Moved Permanently");
				dheader("Location: forum.php?mod=viewthread&tid=$ptid");
			}
			showmessage('post_nonexistence_but_thread_exist',"forum.php?mod=viewthread&tid=$ptid");
		} else {
			showmessage('post_check', NULL, array('tid' => $ptid));
		}
	} else {
		$pid = $post['pid'];
	}

	$ordertype = !isset($_GET['ordertype']) && getstatus($thread['status'], 4) ? 1 : $ordertype;
	if($thread['special'] == 2 || C::t('forum_threaddisablepos')->fetch($tid)) {
		$curpostnum = C::t('forum_post')->count_by_tid_dateline($thread['posttableid'], $tid, $post['dateline']);
	} else {
		if($thread['maxposition']) {
			$maxposition = $thread['maxposition'];
		} else {
			$maxposition = C::t('forum_post')->fetch_maxposition_by_tid($thread['posttableid'], $tid);
		}
		$thread['replies'] = $maxposition;
		$curpostnum = $post['position'];
	}
	$page = ceil($ordertype > 1 ? ($thread['replies'] - $curpostnum + 3) : $curpostnum / $_G['ppp']);
	$pageadd = $page > 1 ? '&page='.$page : '';

	if($thread['special'] == 2 && C::t('forum_trade')->check_goods($pid)) {
		header("HTTP/1.1 301 Moved Permanently");
		dheader("Location: forum.php?mod=viewthread&do=tradeinfo&tid=$tid&pid=$pid");
	}

	$authoridurl = $authorid ? '&authorid='.$authorid : '';
	$ordertypeurl = $ordertype ? '&ordertype='.$ordertype : '';
	header("HTTP/1.1 301 Moved Permanently");
	dheader("Location: forum.php?mod=viewthread&tid=$tid$pageadd$authoridurl$ordertypeurl".(isset($_GET['modthreadkey']) && ($modthreadkey = modauthkey($tid)) ? "&modthreadkey=$modthreadkey": '')."#pid$pid");
}


if(empty($_G['thread'])) {
	showmessage('thread_nonexistence');
}
if($_GET['goto'] == 'lastpost') {

	$pageadd = '';
	if(!getstatus($_G['thread'], 4)) {
		$page = ceil(($_G['thread']['special'] ? $_G['thread']['replies'] : $_G['thread']['replies'] + 1) / $_G['ppp']);
		$pageadd = $page > 1 ? '&page='.$page : '';
	}

	dheader('Location: forum.php?mod=viewthread&tid='.$_G['tid'].$pageadd.'#lastpost');

} elseif($_GET['goto'] == 'nextnewset' || $_GET['goto'] == 'nextoldset') {

	$lastpost = $_G['thread']['lastpost'];


	$glue = '<';
	$sort = 'DESC';
	if($_GET['goto'] == 'nextnewset') {
		$glue = '>';
		$sort = 'ASC';
	}
	$next = C::t('forum_thread')->fetch_next_tid_by_fid_lastpost($_G['fid'], $lastpost, $glue, $sort, $_G['thread']['threadtableid']);
	if($next) {
		dheader("Location: forum.php?mod=viewthread&tid=$next");
	} elseif($_GET['goto'] == 'nextnewset') {
		showmessage('redirect_nextnewset_nonexistence');
	} else {
		showmessage('redirect_nextoldset_nonexistence');
	}

} else {
	showmessage('undefined_action', NULL);
}

?>