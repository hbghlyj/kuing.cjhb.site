<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

showtableheader('', 'fixpadding');

showtablerow('class="header"', ['class="td23"', 'class="td23"', 'class="td23"', 'class="td23"', 'class="td24"', 'class="td24"', 'class="td24"', ''], [
	'ID',
	cplang('operator'),
	cplang('usergroup'),
	cplang('logs_device'),
	cplang('time'),
	cplang('action'),
	cplang('other')
]);

foreach($logs as $k => $logrow) {
	$data = logdecode($logrow['data']);
	$device = logdecode($logrow['device']);
	$logurl = cplog_url($data['action'], $data['extralog']);
	$log = [];
	$log[0] = $logrow['id'];
	$log[1] = dgmdate($logrow['dateline']);
	$log[2] = "<a href=\"home.php?mod=space&username=".rawurlencode($data['operator_username'])."\" target=\"_blank\">".($data['operator_username'] != $_G['member']['username'] ? '<b>'.$data['operator_username'].'</b>' : $data['operator_username'])."</a>";
	$log[3] = $usergroup[$data['operator_adminid']];
	$log[4] = $_G['group']['allowviewip'] ? 'ClientIP: '.$device['client_ip'].'&nbsp;&nbsp;<a href="javascript:;" onclick="togglelog('.$logrow['id'].')">'.cplang('more').'</a>' : '-';
	preg_match('/operation=(.[^;]*)/i', $data['extralog'], $operationInfo);
	$logExplain = $explainAction[rtrim($data['action']).'_'.$operationInfo[1]] ? $explainAction[rtrim($data['action']).'_'.$operationInfo[1]] : $explainAction[rtrim($data['action'])];
	$log[5] = $logExplain ? $logExplain : rtrim($data['action']);
	showtablerow('', ['class="bold"'], [
		$log[0],
		$log[2],
		$log[3],
		$log[4],
		$log[1],
		$log[5],
		$logurl ? '<a href="'.dhtmlspecialchars($logurl).'">'.cutstr($data['extralog'], 200).'</a> <a href="javascript:;" onclick="togglecplog('.$k.')">('.cplang('more').')</a>' : '<a href="javascript:;" onclick="togglecplog('.$k.')">'.cutstr($data['extralog'], 200).'</a>',
	]);
	echo '<tbody id="cplog_'.$k.'" style="display:none;">';
	echo '<tr><td colspan="7">'.$data['extralog'].'</td></tr>';
	echo '</tbody>';
	echo showdevice($logrow['id'], $device, 7);
}

function cplog_url($action, $extralog) {
	if(!preg_match('/^\w+$/', $action) || !preg_match('/GET=\{(.*?)\};\s*POST=/s', $extralog, $matches)) {
		return '';
	}

	$query = ['action' => $action];
	$skip = ['app', 'action', 'ajaxdata', 'ajaxtarget', 'attachsize', 'blank', 'chart', 'dbsize', 'frames', 'homecheck', 'inajax', 'timestamp', 't', '_r'];
	foreach(explode('; ', $matches[1]) as $item) {
		[$key, $value] = array_pad(explode('=', $item, 2), 2, '');
		if(!preg_match('/^\w+$/', $key) || in_array($key, $skip, true)) {
			continue;
		}
		$query[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
	}

	return ADMINSCRIPT.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}
