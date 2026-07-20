<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(submitcheck('settingsubmit')) {
	if(empty($settingnew['security_verify'])) {
		$settingnew['security_verify'] = [];
	}
} else {
	shownav('safe', 'setting_account');

	showformheader('setting&edit=yes', 'enctype');
	showhiddenfields(['operation' => $operation]);

	/*search={"setting_account":"action=setting&operation=account","setting_account_base":"action=setting&operation=account&anchor=base"}*/
	showtableheader('', 'nobottom');
	$security_verify = ['settingnew[security_verify]', [
		['secmobile', $lang['security_verify_mobile']],
		['email', $lang['security_verify_email']],
		['password', $lang['security_verify_password']],
		//array('appeal', $lang['security_verify_appeal']),
	]];
	$setting['security_verify'] = dunserialize($setting['security_verify']);
	showsetting('setting_sec_base_security_verify', $security_verify, $setting['security_verify'], 'mcheckbox', norelatedlink: true);
	showsetting('setting_sec_base_security_mobile', 'settingnew[security_mobile]', $setting['security_mobile'], 'radio');
	showsetting('setting_sec_base_security_email', 'settingnew[security_email]', $setting['security_email'], 'radio');
	showsetting('setting_sec_base_security_password', 'settingnew[security_password]', $setting['security_password'], 'radio');
	showsetting('setting_sec_base_security_question', 'settingnew[security_question]', $setting['security_question'], 'radio');
	showtablefooter();
	/*search*/

	showtableheader();
	showsubmit('settingsubmit', 'submit', '', $extbutton.(!empty($from) ? '<input type="hidden" name="from" value="'.$from.'">' : ''));
	showtablefooter();
	showformfooter();
}
