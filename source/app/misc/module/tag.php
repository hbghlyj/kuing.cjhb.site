<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$op = in_array($_GET['op'], ['add', 'list', 'detail', 'search', 'manage', 'set']) ? $_GET['op'] : '';

if(!$op || in_array($op, ['list', 'detail'])) {
	require_once libfile('misc/tag', 'module');
	return;
}elseif(in_array($_GET['op'], ['add', 'search', 'manage', 'set'])) {
	global $_G;
	$taglist = [];
	if(intval($_GET['tid']) > 0) {
		$postinfo = table_forum_post::t()->fetch_threadpost_by_tid_invisible(intval($_GET['tid']));
		if($postinfo['tags']) {
			$tagarray_all = $array_temp = $threadtag_array = [];
			$tagarray_all = explode("\t", $postinfo['tags']);
			if($tagarray_all) {
				foreach($tagarray_all as $var) {
					if($var) {
						$array_temp = explode(',', $var);
						$threadtag_array[] = $array_temp['1'];
					}
				}
			}
			$tags = implode(',', $threadtag_array);
		}
	}
	$file = childfile($op);
	if(file_exists($file)) {
		require_once $file;
	}

	include_once template('tag/tagmanage');
}
