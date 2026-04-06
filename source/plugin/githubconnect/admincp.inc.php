<?php

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!$_G['inajax']) {
	cpheader();
}

$setting = C::t('common_setting')->fetch_all_setting([
	'githubconnect_allow',
	'githubconnect_clientid',
	'githubconnect_clientsecret',
]);

if(!submitcheck('githubconnectsubmit')) {
	showformheader('plugins&operation=config&do='.$pluginid.'&identifier=githubconnect&pmod=admincp', 'githubconnectsubmit');
	showtableheader();
	showsetting(
		$scriptlang['githubconnect']['githubconnect_enable'],
		'githubconnect_allownew',
		(int)!empty($setting['githubconnect_allow']),
		'radio',
		'',
		0,
		$scriptlang['githubconnect']['githubconnect_enable_desc']
	);
	showsetting(
		'Client ID',
		'githubconnect_clientidnew',
		$setting['githubconnect_clientid'],
		'text',
		'',
		0,
		$scriptlang['githubconnect']['githubconnect_clientid_desc']
	);
	showsetting(
		'Client Secret',
		'githubconnect_clientsecretnew',
		$setting['githubconnect_clientsecret'],
		'password',
		'',
		0,
		$scriptlang['githubconnect']['githubconnect_clientsecret_desc']
	);
	showsetting(
		$scriptlang['githubconnect']['githubconnect_callback_url'],
		'',
		'',
		'<input type="text" class="txt" readonly value="'.$_G['siteurl'].'plugin.php?id=githubconnect:oauth&op=callback" style="width: 420px;" />',
		'',
		0,
		$scriptlang['githubconnect']['githubconnect_callback_desc']
	);
	showtablefooter();
	showsubmit('githubconnectsubmit');
	showformfooter();
} else {
	C::t('common_setting')->update_batch([
		'githubconnect_allow' => $_GET['githubconnect_allownew'] ? '1' : '0',
		'githubconnect_clientid' => trim($_GET['githubconnect_clientidnew']),
		'githubconnect_clientsecret' => trim($_GET['githubconnect_clientsecretnew']),
	]);
	updatecache('setting');
	cpmsg($scriptlang['githubconnect']['githubconnect_update_succeed'], 'action=plugins&operation=config&do='.$pluginid.'&identifier=githubconnect&pmod=admincp', 'succeed');
}
