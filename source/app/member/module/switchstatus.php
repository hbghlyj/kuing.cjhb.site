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
	$icon = $_G['session']['invisible'] ? '<svg class="stealth-status-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 9V7c0-2 2.7-4 6-4s6 2 6 4v2M3 9h18M4 12h5l1 1.5h4l1-1.5h6M4 12v2c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2v-2M14 12v2c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2v-2"></path></svg>' : '';
	$msg = $_G['session']['invisible'] ? $language['login_invisible_mode'] : $language['login_normal_mode'];
	showmessage('<a href="member.php?mod=switchstatus" title="'.$language['login_switch_invisible_mode'].'" onclick="ajaxget(this.href, \'loginstatus\');return false;" class="xi2'.($_G['session']['invisible'] ? ' stealth-status' : '').'">'.$icon.$msg.'</a>', dreferer(), [], ['msgtype' => 3, 'showmsg' => 1]);

}

