<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();

$lpp = empty($_GET['lpp']) ? 50 : $_GET['lpp'];
$checklpp = [];
$checklpp[$lpp] = 'selected="selected"';
$extrainput = '';

$operation = !empty($operation) ? $operation : 'setting';

$start = ($page - 1) * $lpp;
$keyword = '';
if(isset($_GET['keywordenc']) && preg_match('/^[A-Za-z0-9_-]+$/', $_GET['keywordenc'])) {
	$keyword = base64_decode(strtr($_GET['keywordenc'], '-_', '+/'));
	$keyword = is_string($keyword) ? trim($keyword) : '';
} elseif(isset($_GET['keyword'])) {
	$keyword = trim((string)$_GET['keyword']);
}

$conditions = [];
$conditions[] = ['type', '=', "'".$operation."'"];
if(!empty($_GET['search']) && !empty($_GET['search']['field']) && in_array($_GET['search']['field'], ['data', 'device']) && preg_match('/^\w+$/', $_GET['search']['key'])
	&& !empty($_GET['search']['key'])&& !empty($_GET['search'][$_GET['search']['key']])) {
	$conditions[] = ['JSON_EXTRACT('.$_GET['search']['field'].', \'$.'.$_GET['search']['key'].'\')', '=', "'".$_GET['search'][$_GET['search']['key']]."'"];
}
if($keyword !== '') {
	$conditions[] = ['keyword', 'LIKE', $keyword];
}

$keywordenc = $keyword !== '' ? rtrim(strtr(base64_encode($keyword), '+/', '-_'), '=') : '';
$adminroute = '';
if(isset($_GET['app'])) {
	$adminroute .= 'app='.rawurlencode($_GET['app']).'&';
}
if(isset($_GET['platform'])) {
	$adminroute .= 'platform='.rawurlencode($_GET['platform']).'&';
}
$urlbase = ADMINSCRIPT."?".$adminroute."action=logs&operation=$operation&lpp=$lpp".($keywordenc !== '' ? '&keywordenc='.$keywordenc : '').(!empty($_GET['day']) ? '&day='.$_GET['day'] : '');
if(submitcheck('logbatchsubmit', true)) {
	$deleteids = !empty($_POST['deleteids']) ? dintval((array)$_POST['deleteids'], true) : [];
	if(!empty($_POST['deleteallfiltered'])) {
		table_common_log::t()->delete_by_conditions($conditions);
	} elseif($deleteids) {
		table_common_log::t()->delete_by_ids($deleteids, $conditions, $start, $lpp);
	}
	cpmsg('logs_delete_succeed', $urlbase.'&page='.$page, 'succeed');
}

$logs = [];
$num = table_common_log::t()->fetch_all_by_conditions($conditions, 0, 0, 1);
$logs = table_common_log::t()->fetch_all_by_conditions($conditions, $start, $lpp, 0);
$multipage = multi($num, $lpp, $page, $urlbase, 0, 3);

$usergroup = [];

if(in_array($operation, ['rate', 'mods', 'ban', 'cp', 'modcp'])) {
	foreach(table_common_usergroup::t()->range() as $group) {
		$usergroup[$group['groupid']] = $group['grouptitle'];
	}
}

shownav('tools', 'nav_logs', 'nav_logs_'.$operation);

$sel = '';
$menu = [
	['nav_logs_setting', 'logs&operation=setting', $operation == 'setting'],
	[['menu' => 'nav_logs_member', 'submenu' => [
		['nav_logs_illegal', 'logs&operation=illegal'],
		['nav_logs_ban', 'logs&operation=ban'],
		['nav_logs_mods', 'logs&operation=mods'],
		['nav_logs_sms', 'logs&operation=sms'],
		['nav_logs_login', 'logs&operation=login'],
	]], '', in_array($operation, ['illegal', 'ban', 'mods', 'sms', 'login'])],
	[['menu' => 'nav_logs_system', 'submenu' => [
		['nav_logs_cp', 'logs&operation=cp'],
		['nav_logs_modcp', 'logs&operation=modcp'],
		['nav_logs_error', 'logs&operation=error'],
		['nav_logs_sendmail', 'logs&operation=sendmail'],
		['nav_logs_SMTP', 'logs&operation=SMTP'],
		['nav_logs_restful', 'logs&operation=restful'],
	]], '', in_array($operation, ['cp', 'error', 'sendmail', 'SMTP'])],
	[['menu' => 'nav_logs_extended', 'submenu' => [
		['nav_logs_rate', 'logs&operation=rate'],
		['nav_logs_warn', 'logs&operation=warn'],
		['nav_logs_credit', 'logs&operation=credit'],
		['nav_logs_magic', 'logs&operation=magic'],
		['nav_logs_medal', 'logs&operation=medal'],
		['nav_logs_invite', 'logs&operation=invite'],
		['nav_logs_payment', 'logs&operation=payment'],
		['nav_logs_pmt', 'logs&operation=pmt'],
	]], '', in_array($operation, ['rate', 'warn', 'credit', 'magic', 'medal', 'invite', 'payment', 'pmt'])],
	[['menu' => 'nav_logs_crime', 'submenu' => [
		['all', 'logs&operation=crime'],
		['nav_logs_crime_delpost', 'logs&operation=crime&crimeactions=crime_delpost'],
		['nav_logs_crime_warnpost', 'logs&operation=crime&crimeactions=crime_warnpost'],
		['nav_logs_crime_banpost', 'logs&operation=crime&crimeactions=crime_banpost'],
		['nav_logs_crime_banspeak', 'logs&operation=crime&crimeactions=crime_banspeak'],
		['nav_logs_crime_banvisit', 'logs&operation=crime&crimeactions=crime_banvisit'],
		['nav_logs_crime_banstatus', 'logs&operation=crime&crimeactions=crime_banstatus'],
		['nav_logs_crime_avatar', 'logs&operation=crime&crimeactions=crime_avatar'],
		['nav_logs_crime_sightml', 'logs&operation=crime&crimeactions=crime_sightml'],
		['nav_logs_crime_customstatus', 'logs&operation=crime&crimeactions=crime_customstatus'],
	]], '', $operation == 'crime'],
];

loadcache('adminlog');
$cleartypesext = [];
if(!empty($_G['cache']['adminlog'])) {
	$_submenu = [];
	foreach($_G['cache']['adminlog'] as $_operation) {
		$p = strpos($_operation, ':');
		if($p !== false) {
			list($pluginid, $f) = explode(':', $_operation);
			$_submenu[] = [lang('plugin/'.$pluginid, 'log_'.$f), 'logs&operation='.$_operation, $operation == $_operation];
			$cleartypesext[] = [$_operation, lang('plugin/'.$pluginid, 'log_'.$f)];
		}
	}
	$menu[] = [['menu' => 'nav_logs_plugin', 'submenu' => $_submenu]];
}

showsubmenu('nav_logs', $menu, $sel);

$filters = '';
if($operation != 'setting') {
	$keywordhtml = dhtmlspecialchars($keyword);
	echo '<form name="logsearchform" method="get" autocomplete="on" action="'.ADMINSCRIPT.'" id="logsearchform" onsubmit="return encodeLogKeywordSearch();">';
	showtableheader('', 'fixpadding');
	if(isset($_GET['app'])) {
		echo '<input type="hidden" name="app" value="'.dhtmlspecialchars($_GET['app']).'" />';
	}
	if(isset($_GET['platform'])) {
		echo '<input type="hidden" name="platform" value="'.dhtmlspecialchars($_GET['platform']).'" />';
	}
	echo '<input type="hidden" name="action" value="logs" />';
	echo '<input type="hidden" name="operation" value="'.dhtmlspecialchars($operation).'" />';
	echo '<input type="hidden" name="lpp" value="'.$lpp.'" />';
	echo '<input type="hidden" name="keywordenc" id="keywordenc" value="'.dhtmlspecialchars($keywordenc).'" />';
	showtablerow('', [], [
		'Keyword',
		'<input type="text" class="txt" style="width:280px" id="keywordraw" value="'.$keywordhtml.'" />',
		'<input type="submit" class="btn" value="'.$lang['search'].'" />'.($keyword !== '' ? ' <a href="'.ADMINSCRIPT.'?'.$adminroute.'action=logs&operation='.rawurlencode($operation).'&lpp='.$lpp.'">Clear</a>' : ''),
	]);
	showtablefooter();
	echo '</form>';
}

echo <<<EOD
<script type="text/javascript">
function togglelog(k) {
	var logobj = $('log_'+k);
	if(logobj.style.display == 'none') {
		logobj.style.display = '';
	} else {
		logobj.style.display = 'none';
	}
}
function encodeLogKeywordSearch() {
	var raw = $('keywordraw');
	var encoded = $('keywordenc');
	if(raw && encoded) {
		encoded.value = btoa(unescape(encodeURIComponent(raw.value))).replace(/\\+/g, '-').replace(/\\//g, '_').replace(/=+$/g, '');
	}
	return true;
}
function initLogBatchDelete() {
	var tables = document.getElementsByTagName('table');
	for(var i = 0; i < tables.length; i++) {
		var rows = tables[i].getElementsByTagName('tr');
		for(var j = 0; j < rows.length; j++) {
			var cells = rows[j].getElementsByTagName('td');
			if(!cells.length || cells[0].getAttribute('data-log-batch')) {
				continue;
			}
			var text = cells[0].innerText || cells[0].textContent || '';
			text = text.replace(/^\\s+|\\s+$/g, '');
			if(/^\\d+$/.test(text)) {
				cells[0].setAttribute('data-log-batch', '1');
				cells[0].innerHTML = '<input type="checkbox" class="checkbox" name="deleteids[]" value="' + text + '" form="logbatchform" /> ' + cells[0].innerHTML;
			}
		}
	}
}
function submitLogBatchDelete() {
	var form = $('logbatchform');
	var deleteall = $('deleteallfiltered');
	if(deleteall && deleteall.value == '1') {
		return confirm('Delete all logs matching current filter?');
	}
	var checked = false;
	var inputs = document.getElementsByName('deleteids[]');
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].checked) {
			checked = true;
			break;
		}
	}
	if(!checked) {
		alert('Please select logs to delete.');
		return false;
	}
	return confirm('Delete selected logs?');
}
function toggleLogFilterDelete(chk) {
	var deleteall = $('deleteallfiltered');
	if(deleteall) {
		deleteall.value = chk.checked ? '1' : '';
	}
	checkAll('prefix', document.getElementById('logbatchform'), 'deleteids');
}
</script>
EOD;

$file = childfile('logs/'.$operation);
if(!file_exists($file)) {
	$p = strpos($operation, ':');
	if($p !== false) {
		list($pluginid, $f) = explode(':', $operation);
		if(!ispluginkey($pluginid) || !preg_match('/^\w+$/', $f) || !file_exists($file = DISCUZ_PLUGIN($pluginid).'/log/log_'.$f.'.php')) {
			cpmsg('undefined_action');
		}
	} else {
		cpmsg('undefined_action');
	}
}

require_once $file;

if($operation != 'setting') {
	echo '<form id="logbatchform" method="post" action="'.$urlbase.'&page='.$page.'" onsubmit="return submitLogBatchDelete();">';
	echo '<input type="hidden" name="formhash" value="'.FORMHASH.'" />';
	echo '<input type="hidden" name="logbatchsubmit" value="yes" />';
	echo '<input type="hidden" name="deleteallfiltered" id="deleteallfiltered" value="" />';
	echo '</form>';
	echo '<script type="text/javascript">initLogBatchDelete();</script>';
	showtableheader('', 'fixpadding');
	showsubmit('', '', '', '<input type="checkbox" name="chkall" id="chkall" class="checkbox" onclick="toggleLogFilterDelete(this)" form="logbatchform" /><label for="chkall">'.cplang('select_all').'</label>&nbsp;&nbsp;<input type="submit" class="btn" value="'.cplang('delete').'" form="logbatchform" />', $multipage);
	showtablefooter();
}

function showdevice($id, $device, $colspan = 1) {
	return '<tbody id="log_'.$id.'" style="display:none; background-color: #cfd6dd;">'.
		'<tr><td colspan="'.$colspan.'"><strong>ClientIP:</strong> '.$device['client_ip'].'</td></tr>'.
		'<tr><td colspan="'.$colspan.'"><strong>Port:</strong> '.$device['client_port'].'</td></tr>'.
		'<tr><td colspan="'.$colspan.'"><strong>Browser:</strong> '.$device['client_browser'].'</td></tr>'.
		'<tr><td colspan="'.$colspan.'"><strong>Os:</strong> '.$device['client_os'].'</td></tr>'.
		'<tr><td colspan="'.$colspan.'"><strong>Device:</strong> '.$device['client_device'].'</td></tr>'.
		'<tr><td colspan="'.$colspan.'"><strong>User Agent:</strong> '.$device['client_useragent'].'</td></tr>'.
		'</tbody>';
}

function getactionarray() {
	$isfounder = true;
	$menu = $topmenu = [];
	foreach(table_common_admincp_menu_platform::t()->fetch_all_data() as $menuData) {
		$menu += (array)dunserialize($menuData['menu'])['menu'];
	}
	foreach($menu as $top => $v) {
		if(empty($v)) {
			continue;
		}
		$topmenu[$top] = '';
	}

	unset($topmenu['index'], $menu['index']);
	$actioncat = $actionarray = [];
	$actioncat[] = 'setting';
	$actioncat = array_merge($actioncat, array_keys($topmenu));
	foreach($menu as $tkey => $items) {
		foreach($items as $item) {
			$actionarray[$tkey][] = $item;
		}
	}
	return ['actions' => $actionarray, 'cats' => $actioncat];
}

function get_log_files($logdir = '', $action = 'action') {
	$dir = opendir($logdir);
	$files = [];
	while($entry = readdir($dir)) {
		$files[] = $entry;
	}
	closedir($dir);

	if($files) {
		sort($files);
		$logfile = $action;
		$logfiles = [];
		$ym = '';
		foreach($files as $file) {
			if(str_contains($file, $logfile)) {
				if(substr($file, 0, 6) != $ym) {
					$ym = substr($file, 0, 6);
				}
				$logfiles[$ym][] = $file;
			}
		}
		if($logfiles) {
			$lfs = [];
			foreach($logfiles as $ym => $lf) {
				$lastlogfile = $lf[0];
				unset($lf[0]);
				$lf[] = $lastlogfile;
				$lfs = array_merge($lfs, $lf);
			}
			return $lfs;
		}
		return [];
	}
	return [];
}

showtablefooter();
showtableheader('', 'fixpadding');
echo $multipage;
showtablefooter();

echo <<<EOD
<script type="text/javascript">
function togglecplog(k) {
	var cplogobj = $('cplog_'+k);
	if(cplogobj.style.display == 'none') {
		cplogobj.style.display = '';
	} else {
		cplogobj.style.display = 'none';
	}
}
</script>
EOD;


