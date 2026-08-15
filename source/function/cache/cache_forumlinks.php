<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_forumlinks() {
	global $_G;

	$data = [];
	$query = table_common_friendlink::t()->fetch_all_by_displayorder();

	if($_G['setting']['forumlinkstatus']) {
		foreach($query as $flink) {
			$group = (int)$flink['type'];
			if(!in_array($group, [1, 2, 3, 4], true)) {
				$group = 1;
			}
			if(!isset($data[$group])) {
				$data[$group] = ['content' => '', 'logo' => '', 'text' => '', 'count' => 0];
			}
			$data[$group]['count']++;
			if($flink['description']) {
				if($flink['logo']) {
					$data[$group]['content'] .= '<li class="lk_logo mbm bbda cl"><img src="'.$flink['logo'].'" border="0" alt="'.strip_tags($flink['name']).'"><div class="lk_content z"><h5><a href="'.$flink['url'].'" target="_blank" rel="external nofollow">'.$flink['name'].'</a></h5><p>'.$flink['description'].'</p></div></li>';
				} else {
					$data[$group]['content'] .= '<li class="mbm bbda"><div class="lk_content"><h5><a href="'.$flink['url'].'" target="_blank" rel="external nofollow">'.$flink['name'].'</a></h5><p>'.$flink['description'].'</p></div></li>';
				}
			} else {
				if($flink['logo']) {
					$data[$group]['logo'] .= '<a href="'.$flink['url'].'" target="_blank" rel="external nofollow"><img src="'.$flink['logo'].'" border="0" alt="'.strip_tags($flink['name']).'"></a> ';
				} else {
					$data[$group]['text'] .= '<li><a href="'.$flink['url'].'" target="_blank" rel="external nofollow" title="'.strip_tags($flink['name']).'">'.$flink['name'].'</a></li>';
				}
			}
		}
		ksort($data, SORT_NUMERIC);
	}

	savecache('forumlinks', $data);
}

