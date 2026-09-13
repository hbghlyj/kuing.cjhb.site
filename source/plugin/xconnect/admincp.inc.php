<?php

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!$_G['inajax']) {
	cpheader();
}

$setting = C::t('common_setting')->fetch_all_setting([
	'xconnect_allow',
	'xconnect_clientid',
	'xconnect_clientsecret',
]);

if(!submitcheck('xconnectsubmit')) {
	showformheader('plugins&operation=config&do='.$pluginid.'&identifier=xconnect&pmod=admincp', 'xconnectsubmit');
	showtableheader();
	showsetting(
		$scriptlang['xconnect']['xconnect_enable'],
		'xconnect_allownew',
		(int)!empty($setting['xconnect_allow']),
		'radio',
		'',
		0,
		$scriptlang['xconnect']['xconnect_enable_desc']
	);
	showsetting(
		'Client ID',
		'xconnect_clientidnew',
		$setting['xconnect_clientid'],
		'text',
		'',
		0,
		$scriptlang['xconnect']['xconnect_clientid_desc']
	);
	showsetting(
		'Client Secret',
		'xconnect_clientsecretnew',
		$setting['xconnect_clientsecret'],
		'password',
		'',
		0,
		$scriptlang['xconnect']['xconnect_clientsecret_desc']
	);
	showsetting(
		$scriptlang['xconnect']['xconnect_callback_url'],
		'',
		'',
		'<input type="text" class="txt" readonly value="'.$_G['siteurl'].'plugin.php?id=xconnect:oauth&op=callback" style="width: 420px;">',
		'',
		0,
		$scriptlang['xconnect']['xconnect_callback_desc']
	);
	showtablefooter();
	showsubmit('xconnectsubmit');
	showformfooter();
} else {
	C::t('common_setting')->update_batch([
		'xconnect_allow' => $_GET['xconnect_allownew'] ? '1' : '0',
		'xconnect_clientid' => trim($_GET['xconnect_clientidnew']),
		'xconnect_clientsecret' => trim($_GET['xconnect_clientsecretnew']),
	]);
	updatecache('setting');
	cpmsg($scriptlang['xconnect']['xconnect_update_succeed'], 'action=plugins&operation=config&do='.$pluginid.'&identifier=xconnect&pmod=admincp', 'succeed');
}


