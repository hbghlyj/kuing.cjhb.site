<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!$isplugindeveloper || !$pluginid) {
	cpmsg('undefined_action', '', 'error');
}

$plugin = table_common_plugin::t()->fetch($pluginid);
if(!$plugin) {
	cpheader();
	cpmsg('plugin_not_found', '', 'error');
}

unset($plugin['pluginid']);

$pluginarray = [];
$pluginarray['plugin'] = $plugin;
$pluginarray['version'] = strip_tags($_G['setting']['version']);

foreach(table_common_pluginvar::t()->fetch_all_by_pluginid($pluginid) as $var) {
	unset($var['pluginvarid'], $var['pluginid']);
	$pluginarray['var'][] = $var;
}
$modules = dunserialize($pluginarray['plugin']['modules']);
$exportold = !empty($_GET['old']) && $_GET['old'] == 'yes';
if($modules['extra']['langexists']) {
	$languagefiles = [DISCUZ_DATA.'./plugindata/'.$pluginarray['plugin']['identifier'].'.lang.php'];
	if($exportold) {
		$languagefiles[] = DISCUZ_PLUGIN($pluginarray['plugin']['directory']).'./i18n/SC_UTF8/lang_plugin.php';
	}
	foreach($languagefiles as $file) {
		if(file_exists($file)) {
			include $file;
			foreach(['scriptlang', 'templatelang', 'installlang', 'systemlang'] as $langtype) {
				if(!empty(${$langtype}[$pluginarray['plugin']['identifier']]) && empty($pluginarray['language'][$langtype])) {
					$pluginarray['language'][$langtype] = ${$langtype}[$pluginarray['plugin']['identifier']];
				}
			}
		}
	}
}
unset($modules['extra']);
$pluginarray['plugin']['modules'] = serialize($modules);
$plugindir = DISCUZ_PLUGIN($pluginarray['plugin']['directory']);
if(file_exists($plugindir.'/install.php')) {
	$pluginarray['installfile'] = 'install.php';
}
if(file_exists($plugindir.'/uninstall.php')) {
	$pluginarray['uninstallfile'] = 'uninstall.php';
}
if(file_exists($plugindir.'/upgrade.php')) {
	$pluginarray['upgradefile'] = 'upgrade.php';
}
if(file_exists($plugindir.'/check.php')) {
	$pluginarray['checkfile'] = 'check.php';
}
if(file_exists($plugindir.'/enable.php')) {
	$pluginarray['enablefile'] = 'enable.php';
}
if(file_exists($plugindir.'/disable.php')) {
	$pluginarray['disablefile'] = 'disable.php';
}

if($exportold) {
	exportxmldata('Discuz! Plugin', $plugin['identifier'], $pluginarray);
} else {
	exportdata('Discuz! Plugin', $plugin['identifier'], $pluginarray);
}
