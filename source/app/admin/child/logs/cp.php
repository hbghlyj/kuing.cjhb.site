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
	$logdetails = cplog_diff($data['extralog']);
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
	echo '<tr><td colspan="7">'.$logdetails.'</td></tr>';
	echo '</tbody>';
	echo showdevice($logrow['id'], $device, 7);
}

function cplog_url($action, $extralog) {
	$blocks = cplog_blocks($extralog);
	if(!preg_match('/^\w+$/', $action) || !$blocks) {
		return '';
	}

	$query = ['action' => $action];
	$skip = ['app', 'action', 'ajaxdata', 'ajaxtarget', 'attachsize', 'blank', 'chart', 'dbsize', 'frames', 'homecheck', 'inajax', 'timestamp', 't', '_r'];
	foreach(cplog_params($blocks['GET']) as $key => $value) {
		if(!preg_match('/^\w+$/', $key) || in_array($key, $skip, true)) {
			continue;
		}
		$query[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
	}

	return ADMINSCRIPT.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function cplog_blocks($extralog) {
	if(!preg_match('/GET=\{(.*?)\};\s*POST=\{(.*?)\};?$/s', $extralog, $matches)) {
		return [];
	}
	return ['GET' => $matches[1], 'POST' => $matches[2]];
}

function cplog_params($block) {
	$params = [];
	$start = $depth = 0;
	$length = strlen($block);
	for($i = 0; $i <= $length; $i++) {
		$char = $i < $length ? $block[$i] : ';';
		if($char == '{') {
			$depth++;
		} elseif($char == '}' && $depth) {
			$depth--;
		} elseif($char == ';' && !$depth) {
			$item = trim(substr($block, $start, $i - $start));
			$start = $i + 1;
			if(str_contains($item, '=')) {
				[$key, $value] = explode('=', $item, 2);
				$params[trim($key)] = trim($value);
			}
		}
	}
	return $params;
}

function cplog_diff($extralog) {
	$blocks = cplog_blocks($extralog);
	if(!$blocks) {
		return dhtmlspecialchars(htmlspecialchars_decode($extralog, ENT_QUOTES));
	}

	$get = cplog_params($blocks['GET']);
	$post = cplog_params($blocks['POST']);
	$keys = array_unique(array_merge(array_keys($get), array_keys($post)));
	$rows = '';
	foreach($keys as $key) {
		$hasget = array_key_exists($key, $get);
		$haspost = array_key_exists($key, $post);
		if($hasget && $haspost && $get[$key] === $post[$key]) {
			continue;
		}
		$getvalue = $hasget ? dhtmlspecialchars($key.'='.htmlspecialchars_decode($get[$key], ENT_QUOTES)) : '';
		$postvalue = $haspost ? dhtmlspecialchars($key.'='.htmlspecialchars_decode($post[$key], ENT_QUOTES)) : '';
		$rows .= '<tr><td>'.$getvalue.'</td><td>'.$postvalue.'</td></tr>';
	}

	return $rows ? '<table class="tb"><tr class="header"><th>GET</th><th>POST</th></tr>'.$rows.'</table>' : '<em>GET = POST</em>';
}
