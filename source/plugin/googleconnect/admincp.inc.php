<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cloud_connect.php 33756 2013-08-10 06:32:48Z nemohou $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if (!$_G['inajax']) {
	cpheader();
}

$setting = C::t('common_setting')->fetch_all_setting(array('connectappid'));
$setting['connect'] = (array)dunserialize($setting['connect']);

if(!submitcheck('connectsubmit')) {
	include_once libfile('function/forumlist');
	$forumselect = array();
		$forumselect['t'] = '<select name="connectnew[t][fids][]" multiple="multiple" size="10">'.forumselect(FALSE, 0, 0, TRUE).'</select>';
		if($setting['connect']['t']['fids']) {
			foreach($setting['connect']['t']['fids'] as $v) {
				$forumselect['t'] = str_replace('<option value="'.$v.'">', '<option value="'.$v.'" selected>', $forumselect['t']);
			}
		}

	$connectrewardcredits = $connectgroup = $connectguestgroup = '';
	$groups = C::t('common_usergroup')->fetch_all_by_type('special');
	showformheader('plugins&operation=config&do='.$pluginid.'&identifier=googleconnect&pmod=admincp', 'connectsubmit');
	showtableheader();
	showsetting('App Id', 'connectappidnew', $setting['connectappid'], 'text', '', 0, $scriptlang['googleconnect']['connect_appid_desc']);
	showtagfooter('tbody');
	showsubmit('connectsubmit');
	showtablefooter();
	showformfooter();

} else {
	C::t('common_setting')->update_batch(array(
		'connectappid' => $_GET['connectappidnew']
	));

	updatecache(array('setting', 'fields_register', 'fields_connect_register'));
	cpmsg('connect_update_succeed', 'action=plugins&operation=config&do='.$pluginid.'&identifier=googleconnect&pmod=admincp', 'succeed');

}