<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_onlinelist() {
	$data = [];
	$data['legend_en'] = $data['legend'] = '';
	foreach(table_forum_onlinelist::t()->fetch_all_order_by_displayorder() as $list) {
		if(!$list['url']) {
			continue;
		}
		$url = STATICURL.'image/common/online_'.$list['url'].'.svg';
		$data[$list['groupid']] = $url;
		$data['legend_en'] .= !empty($url) ? "<img src=\"".$url."\" /> {$list['url']} &nbsp; &nbsp; &nbsp; " : '';
		$data['legend'] .= !empty($url) ? "<img src=\"".$url."\" /> {$list['title']} &nbsp; &nbsp; &nbsp; " : '';
		if($list['groupid'] == 7) {
			$data['guest'] = $list['title'];
		}
	}

	savecache('onlinelist', $data);
}

