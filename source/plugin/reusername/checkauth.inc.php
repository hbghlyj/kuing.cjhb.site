<?php

/**
 *      This is NOT a freeware, use is subject to license terms
 *      应用名称: 更改用户名 2.5
 *      下载地址: https://addon.dismall.com/plugins/reusername.html
 *      应用开发者: 乘凉
 *      开发者QQ: 594433766
 *      更新日期: 202505140312
 *      授权域名: kuing.cjhb.site
 *      授权码: 2025051319u9IbMIRIBa
 *      未经应用程序开发者/所有者的书面许可，不得进行反向工程、反向汇编、反向编译等，不得擅自复制、修改、链接、转载、汇编、发表、出版、发展与之有关的衍生产品、作品等
 */


/**

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$pluginid = empty($_GET['pluginid']) ? CURMODULE : $_GET['pluginid'];
$addonid = $pluginid.'.plugin';
$checkfiledir = DISCUZ_ROOT.'./data/addonmd5/';
$checkfilepath = $checkfiledir.$addonid.'.'.substr(md5($_SERVER['HTTP_HOST']), 0, 8);
if(!file_exists($checkfilepath.'.auth.lock')) {
	require_once libfile('function/cloudaddons');
	$arraydata = cloudaddons_getmd5($addonid);
	if(cloudaddons_open('&mod=app&ac=validator&ver=2&addonid='.$addonid.($arraydata !== false ? '&rid='.$arraydata['RevisionID'].'&sn='.$arraydata['SN'].'&rd='.$arraydata['RevisionDateline'] : '')) === '0') {
		helper_output::json(array('clienturl'=>'https://kuing.cjhb.site/', 'siteid'=>'85335F87-0AD3-8D55-DE9B-3C6FAF9EF370', 'sn' => '2025051319u9IbMIRIBa'));
		$plugininfo = C::t('common_plugin')->fetch_by_identifier($pluginid);
		if($plugininfo && dfsockopen(base64_decode('aHR0cHM6Ly93d3cuZGlzbWFvLmNvbS8=').'plugin.php?id=api_check:validator&addonid='.$addonid.'&addonver='.$plugininfo['version'].'&sitever='.$_G['setting']['version'].'&sitecharset='.$_G['charset'].'&siteurl='.urlencode($_G['siteurl']).'&fromsn=2025051319u9IbMIRIBa&fromsiteid=85335F87-0AD3-8D55-DE9B-3C6FAF9EF370&fromurl='.urlencode('https://kuing.cjhb.site/').($arraydata !== false ? '&rid='.$arraydata['RevisionID'].'&sn='.$arraydata['SN'].'&rd='.$arraydata['RevisionDateline'] : ''),10) === '0') {@touch($checkfilepath.'.lock.lock');}
	}else{
		@unlink($checkfilepath.'.lock.lock');
		@touch($checkfilepath.'.auth.lock');
	}
}

?>