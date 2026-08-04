<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['setting']['attentionstatus']) {
	showmessage('attention_status_off');
}

$ops = ['add', 'del', 'delall'];
$op = in_array($_GET['op'], $ops) ? $_GET['op'] : '';

if(!$_G['uid']) {
	showmessage('to_login', '', [], ['showmsg' => true, 'login' => 1]);
}

if($op == 'delall') {
	if(submitcheck('delsubmit')) {
		$delete = array_map('intval', (array)$_GET['delete']);
		if($delete) {
			table_forum_threadattention::t()->delete_by_tids($delete, $_G['uid']);
		}
		showmessage('thread_attention_delete_succeed', dreferer());
	}
	showmessage('undefined_action');
}

$tid = intval($_GET['tid']);
if(empty($tid)) {
	showmessage('thread_nonexistence');
}

$thread = table_forum_thread::t()->fetch_thread($tid);
if(empty($thread) || $thread['displayorder'] < 0) {
	showmessage('thread_attention_nonexist');
}

if($op == 'add') {
	if($_GET['hash'] != FORMHASH) {
		exit('Access Denied');
	}
	if($thread['authorid'] == $_G['uid']) {
		showmessage('thread_attention_self');
	}
	if(table_forum_threadattention::t()->fetch_by_tid_uid($tid, $_G['uid'])) {
		showmessage('thread_attention_repeat');
	}
	table_forum_threadattention::t()->insert_attention($tid, $_G['uid']);
	$extrajs = '<script type="text/javascript">var fa=$("k_attention");if(fa){fa.classList.add("active");fa.href=fa.href.replace(/op=add/,"op=del");}</script>';
	showmessage('thread_attention_add_succeed', dreferer(), ['tid' => $tid, 'uid' => $_G['uid']], ['showdialog' => true, 'closetime' => true, 'extrajs' => $extrajs]);
} elseif($op == 'del') {
	if($_GET['hash'] != FORMHASH) {
		exit('Access Denied');
	}
	$affectedrows = table_forum_threadattention::t()->delete_by_tid_uid($tid, $_G['uid']);
	if(!$affectedrows) {
		showmessage('thread_attention_does_not_exist');
	}
	$extrajs = '<script type="text/javascript">var fa=$("k_attention");if(fa){fa.classList.remove("active");fa.href=fa.href.replace(/op=del/,"op=add");}</script>';
	showmessage('thread_attention_remove_succeed', dreferer(), ['tid' => $tid], ['showdialog' => true, 'closetime' => true, 'extrajs' => $extrajs]);
} else {
	showmessage('undefined_action');
}
