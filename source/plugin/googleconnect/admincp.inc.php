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

$op = $_GET['op'];

$_GET['anchor'] = in_array($_GET['anchor'], array('setting', 'service')) ? $_GET['anchor'] : 'setting';
$current = array($_GET['anchor'] => 1);

if (!$_G['inajax']) {
	cpheader();
}

if ($_GET['anchor'] == 'setting') {

	$setting = C::t('common_setting')->fetch_all_setting(array('connect', 'connectsiteid', 'connectsitekey', 'regconnect', 'connectappid', 'connectappkey'));
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
		showsetting('App Key', 'connectappkeynew', $setting['connectappkey'], 'text');
		showtagfooter('tbody');
		showsubmit('connectsubmit');
		showtablefooter();
		showformfooter();

	} else {

		$_GET['connectnew'] = array_merge($setting['connect'], $_GET['connectnew']);
		$_GET['connectnew']['like_url'] = '';
		$_GET['connectnew']['turl_code'] = '';
		$connectnew = serialize($_GET['connectnew']);
		$regconnectnew = !$setting['connect']['allow'] && $_GET['connectnew']['allow'] ? 1 : $setting['regconnect'];
		C::t('common_setting')->update_batch(array(
			'regconnect' => $regconnectnew,
			'connect' => $connectnew,
			'connectappid' => $_GET['connectappidnew'],
			'connectappkey' => $_GET['connectappkeynew'],
		));

		updatecache(array('setting', 'fields_register', 'fields_connect_register'));
		cpmsg('connect_update_succeed', 'action=plugins&operation=config&do='.$pluginid.'&identifier=googleconnect&pmod=admincp', 'succeed');

	}
}
