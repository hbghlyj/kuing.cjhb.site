<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$count = 0;
if(isset($_GET['aids']) && isset($_GET['formhash']) && formhash() == $_GET['formhash']) {
	$_GET['aids'] = (array)$_GET['aids'];
	$updatedtids = [];
	foreach($_GET['aids'] as $aid) {
		$attach = table_forum_attachment_n::t()->fetch_attachment('aid:'.$aid, $aid);
		if($attach && ($attach['pid'] && $attach['pid'] == $_GET['pid'] && $_G['uid'] == $attach['uid'])) {
			updatecreditbyaction('postattach', $attach['uid'], [], '', -1, 1, $_G['fid']);
		}
		if($attach && ($attach['pid'] && $attach['pid'] == $_GET['pid'] && $_G['uid'] == $attach['uid'] || $_G['forum']['ismoderator'] || !$attach['pid'] && $_G['uid'] == $attach['uid'])) {
			table_forum_attachment_n::t()->delete_attachment('aid:'.$aid, $aid);
			table_forum_attachment::t()->delete($aid);
			updatemembercount($attach['uid'], ['todayattachs' => -1, 'todayattachsize' => -$attach['filesize'], 'attachsize' => -$attach['filesize']], false);
			dunlink($attach);
			if($_G['setting']['ftp']['on'] == 2) {
				ftpcmd('delete', 'forum/'.$attach['attachment']);
				ftpcmd('delete', 'forum/'.getimgthumbname($attach['attachment']));
			}
			if($attach['tid'] && $attach['pid']) {
				require_once libfile('function/post');
				$thread = table_forum_thread::t()->fetch_thread($attach['tid']);
				updateattach($thread['displayorder'] == -4 || $_G['forum_auditstatuson'], $attach['tid'], $attach['pid'], [], [], $attach['uid']);
				if($attach['isimage'] != 0 && empty($updatedtids[$attach['tid']])) {
					$threadimage = table_forum_attachment_n::t()->fetch_max_image('tid:'.$attach['tid'], 'tid', $attach['tid']);
					table_forum_threadimage::t()->delete_by_tid($attach['tid']);
					if($threadimage) {
						table_forum_threadimage::t()->insert([
							'tid' => $attach['tid'],
							'attachment' => $threadimage['attachment'],
							'remote' => $threadimage['remote'],
						]);
						setthreadcover($threadimage['pid'], $attach['tid'], 0, 1);
					} else {
						table_forum_thread::t()->update($attach['tid'], ['cover' => 0]);
					}
					$updatedtids[$attach['tid']] = 1;
				}
			}
			$count++;
		}
	}
}
if(defined('IN_RESTFUL')) {
	echo $count;
	exit();
}
include template('common/header_ajax');
echo $count;
include template('common/footer_ajax');
dexit();
	
