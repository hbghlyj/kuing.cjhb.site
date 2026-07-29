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
	$data['legend_i18n'] = array_fill_keys(i18n::LOCALES, '');
	foreach(i18n::LOCALES as $locale) {
		foreach(table_forum_onlinelist::t()->fetch_all_order_by_displayorder($locale) as $list) {
			if(!$list['url']) {
				continue;
			}
			$url = STATICURL.'image/common/online_'.$list['url'].'.svg';
			$data[$list['groupid']] = $url;
			$data['legend_i18n'][$locale] .= "<li><img src=\"".$url."\" /> {$list['title']}</li>";
			if($list['groupid'] == 7) {
				$data['guest_i18n'][$locale] = $list['title'];
			}
		}
	}
	$data['legend'] = $data['legend_i18n']['SC'];
	$data['guest'] = $data['guest_i18n']['SC'] ?? '';

	savecache('onlinelist', $data);
}
