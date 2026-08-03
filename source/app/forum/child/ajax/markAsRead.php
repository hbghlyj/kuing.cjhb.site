<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

foreach(table_home_notification::t()->fetch_all_by_uid($_G['uid'], -1, '', 0, 5, '') as $notice) {
	$stripped = strip_tags($notice['note'], '<div><blockquote>');
	if(preg_match_all('/(<a href="[^"]*")([^>]*>)/', $notice['note'], $matches)) {
		echo '<li>'.end($matches[1]), $notice['new'] ? '&delnotice='.$notice['id'] : '', '"',
			$notice['new'] ? ' style="font-weight:600;background:#f7f7f7"' : '',
			end($matches[2]),
			'<font', $notice['new'] ? ' color="#F26C4F"' : '', ' face="dzicon"></font> ', $stripped, '</a></li>';
	} else {
		echo '<li><a><font', $notice['new'] ? ' color="#F26C4F"' : '', ' face="dzicon"></font> ', $stripped, '</a></li>';
	}
}
table_common_member::t()->update($_G['uid'], ['newprompt' => 0]);
exit();
