<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!submitcheck('onlinesubmit')) {

	shownav('style', 'misc_onlinelist');
	showsubmenu('nav_misc_onlinelist');
	showtips('misc_onlinelist_tips');
	showformheader('misc&operation=onlinelist&');
	showtableheader('', 'fixpadding');
	showsubtitle(['', 'display_order', 'usergroup', 'SC / TC / EN', 'misc_onlinelist_image']);

	$listarray = [];
	foreach(table_forum_onlinelist::t()->range() as $list) {
		$listarray[$list['groupid']] = $list;
	}

	$onlinelist = '';
	$query = array_merge([0 => ['groupid' => 0, 'grouptitle' => 'Member']], table_common_usergroup::t()->range());
	foreach($query as $group) {
		$id = $group['groupid'];
		$url = '';
		if(!empty($listarray[$id]['url'])) {
			if(preg_match('/^https?:\/\//is', $listarray[$id]['url'])) {
				$url = $listarray[$id]['url'];
			} elseif(preg_match('/\.(?:gif|png|jpe?g|svg|webp)$/i', $listarray[$id]['url'])) {
				$url = STATICURL.'image/common/'.$listarray[$id]['url'];
			} else {
				$url = STATICURL.'image/common/online_'.$listarray[$id]['url'].'.svg';
			}
		}
		showtablerow('', ['class="td25"', 'class="td23 td28"', 'class="td24"', 'class="td24"', 'class="td21 td26"'], [
			$listarray[$id]['url'] ? " <img src=\"$url\">" : '',
			'<input type="text" class="txt" name="displayordernew['.$id.']" value="'.$listarray[$id]['displayorder'].'" size="3" />',
			$group['groupid'] <= 8 ? cplang('usergroups_system_'.$id) : $group['grouptitle'],
			implode('<br />', array_map(
				fn($locale) => '<label>'.$locale.' <input type="text" class="txt" name="titlenew['.$id.']['.$locale.']" value="'.dhtmlspecialchars($listarray[$id]['title_i18n'][$locale] ?? ($locale == 'SC' ? $group['grouptitle'] : '')).'" size="15" /></label>',
				i18n::LOCALES
			)),
			'<input type="text" class="txt" name="urlnew['.$id.']" value="'.$listarray[$id]['url'].'" size="20" />'
		]);

	}

	showsubmit('onlinesubmit', 'submit', 'td');
	showtablefooter();
	showformfooter();

} else {

	if(is_array($_GET['urlnew'])) {
		table_forum_onlinelist::t()->delete_all();
		foreach($_GET['urlnew'] as $id => $url) {
			$url = trim($url);
			if($id == 0 || $url) {
				$data = [
					'groupid' => $id,
					'displayorder' => $_GET['displayordernew'][$id],
					'title' => array_map(fn($value) => trim(dhtmlspecialchars($value)), $_GET['titlenew'][$id]),
					'url' => $url,
				];
				table_forum_onlinelist::t()->insert($data);
			}
		}
	}

	updatecache(['onlinelist', 'groupicon']);
	cpmsg('onlinelist_succeed', 'action=misc&operation=onlinelist', 'succeed');

}
	
