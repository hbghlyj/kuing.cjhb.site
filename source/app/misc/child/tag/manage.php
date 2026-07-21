<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if($_G['tid']) {
	$tagarray_all = $array_temp = $threadtag_array = [];
	$tags = table_forum_post::t()->fetch_threadpost_by_tid_invisible($_G['tid']);
	$tags = $tags['tags'];
	$tagarray_all = explode("\t", $tags);
	if($tagarray_all) {
		foreach($tagarray_all as $var) {
			if($var) {
				$array_temp = explode(',', $var);
				$threadtag_array[] = $array_temp['1'];
			}
		}
	}
	$tags = implode(',', $threadtag_array);

	$recent_use_tag = recent_use_tag($_G['thread']['fid']);
}
