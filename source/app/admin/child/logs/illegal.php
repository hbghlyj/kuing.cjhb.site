<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

showtips('logs_tips_illegal');

showtableheader('', 'fixpadding');

showtablerow('class="header"', ['class="td23"', 'class="td23"', 'class="td24"', 'class="td23"', 'class="td23"', 'class="td23"'], [
	'ID',
	cplang('time'),
	cplang('logs_device'),
	cplang('logs_passwd_username'),
	cplang('logs_passwd_password'),
	cplang('logs_passwd_security')
]);

foreach($logs as $k => $logrow) {
	$data = logdecode($logrow['data']);
	$device = logdecode($logrow['device']);
	showtablerow('', ['class="smallefont"', 'class="smallefont"', 'class="smallefont"', 'class="bold"', 'class="smallefont"', 'class="smallefont"'], [
		$logrow['id'],
		dgmdate($logrow['dateline']),
		$_G['group']['allowviewip'] ? 'ClientIP: '.$device['client_ip'].'&nbsp;&nbsp;<a href="javascript:;" onclick="togglelog('.$logrow['id'].')">'.cplang('more').'</a>' : '-',
		$data['username'],
		$data['password'],
		$data['questionid'],
	]);
	echo showdevice($logrow['id'], $device, 6);
}

