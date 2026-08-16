<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

const NOROBOT = true;

if($_G['uid']) {

	if(!$_G['group']['allowinvisible']) {
		showmessage('group_nopermission', NULL, ['grouptitle' => $_G['group']['grouptitle']], ['login' => 1]);
	}

	$_G['session']['invisible'] = $_G['session']['invisible'] ? 0 : 1;
	C::app()->session->update_by_uid($_G['uid'], ['invisible' => $_G['session']['invisible']]);
	table_common_member_status::t()->update($_G['uid'], ['invisible' => $_G['session']['invisible']], 'UNBUFFERED');
	if(!empty($_G['setting']['sessionclose'])) {
		dsetcookie('ulastactivity', TIMESTAMP.'|'.getuserprofile('invisible'), 31536000);
	}
	$language = lang('forum/misc');
	$icon = $_G['session']['invisible'] ? '<svg class="stealth-status-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3"></circle><path d="M5 21c.7-4 2.8-6 7-6s6.3 2 7 6M4 9h16M7 9l1.5 3h2L12 9l1.5 3h2L17 9"></path></svg>' : '';
	$msg = $_G['session']['invisible'] ? $language['login_invisible_mode'] : $language['login_normal_mode'];
	showmessage('<a href="member.php?mod=switchstatus" title="'.$language['login_switch_invisible_mode'].'" onclick="ajaxget(this.href, \'loginstatus\');return false;" class="xi2'.($_G['session']['invisible'] ? ' stealth-status' : '').'">'.$icon.$msg.'</a>', dreferer(), [], ['msgtype' => 3, 'showmsg' => 1]);

}

