<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function getrelateitem($tagarray, $tid, $relatenum, $relatetime, $relatecache = '', $type = 'tid') {
	$tagidarray = $relatearray = $relateitem = [];
	$updatecache = 0;
	$limit = $relatenum;
	if(!$limit) {
		return '';
	}
	foreach($tagarray as $var) {
		$tagidarray[] = $var['0'];
	}
	if(!$tagidarray) {
		return '';
	}
	if(empty($relatecache)) {
		$thread = table_forum_thread::t()->fetch_thread($tid);
		$relatecache = $thread['relatebytag'];
	}
	if($relatecache) {
		$relatecache = explode("\t", $relatecache);
		if(TIMESTAMP > $relatecache[0] + $relatetime * 60) {
			$updatecache = 1;
		} else {
			if(!empty($relatecache[1])) {
				$relatearray = explode(',', $relatecache[1]);
			}
		}
	} else {
		$updatecache = 1;
	}
	if($updatecache) {
		$query = table_common_tagitem::t()->select($tagidarray, $tid, $type, 'itemid', 'DESC', $limit, 0, '<>');
		foreach($query as $result) {
			if($result['itemid']) {
				$relatearray[] = $result['itemid'];
			}
		}
		if($relatearray) {
			$relatebytag = implode(',', $relatearray);
		}
		table_forum_thread::t()->update($tid, ['relatebytag' => TIMESTAMP."\t".$relatebytag]);
	}


	if(!empty($relatearray)) {
		rsort($relatearray);
		foreach(table_forum_thread::t()->fetch_all_by_tid($relatearray) as $result) {
			if($result['displayorder'] >= 0) {
				$relateitem[] = $result;
			}
		}
	}
	return $relateitem;
}
