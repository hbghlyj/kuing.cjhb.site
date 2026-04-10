<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

global $_G;
$op = in_array($_GET['op'], array('search', 'manage', 'set')) ? $_GET['op'] : '';
$thread = & $_G['thread'];

if($op == 'search') {
	$searchkey = stripsearchkey($_GET['searchkey']);
	if(empty($searchkey)) {
		exit;
	}
	$taglist = C::t('common_tag')->fetch_all_by_status(0, $searchkey, 50, 0);
	echo json_encode(array_column($taglist, 'tagname'));
	exit;
} elseif($op == 'manage') {
	if($_G['tid']) {
		$tagarray_all = $array_temp = $threadtag_array = array();
		$tags = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
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
	}
} elseif($op == 'set' && $_GET['formhash'] == FORMHASH && $_G['group']['allowmanagetag']) {
        $class_tag = new tag();
        $tagstr = $class_tag->update_field($_GET['tags'], $_G['tid'], 'tid', $_G['thread']);
        C::t('forum_thread')->update($_G['tid'], array('tags' => $tagstr));
} else {
	exit('Access Denied');
}

include_once template("forum/tag");
?>
