<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

require_once '../../source/class/class_core.php';

$discuz = C::app();
$discuz->init();

if(getgpc('m') !== 'user' || getgpc('a') !== 'rectavatar') {
	exit;
}

$ftp = getglobal('setting/ftp');
$oss = getglobal('setting/oss');

@header('Expires: 0');
@header('Cache-Control: private, post-check=0, pre-check=0, max-age=0', FALSE);
@header('Pragma: no-cache');

$avatartype = getgpc('avatartype', 'G') == 'real' ? 'real' : 'virtual';
$storageRoot = (!empty($ftp['on']) && $ftp['on'] == 2 && $oss['oss_avatar']) ? DISCUZ_DATA.'attachment/' : DISCUZ_ROOT.'./data/';
$storagePrefix = 'avatar/';

$bigavatarfile = $storagePrefix.get_avatar($_G['uid'], 'big', $avatartype);
dmkdir(dirname($storageRoot.$bigavatarfile));
$middleavatarfile = $storagePrefix.get_avatar($_G['uid'], 'middle', $avatartype);
dmkdir(dirname($storageRoot.$middleavatarfile));
$smallavatarfile = $storagePrefix.get_avatar($_G['uid'], 'small', $avatartype);
dmkdir(dirname($storageRoot.$smallavatarfile));

$bigavatar = base64_decode(getgpc('avatar1', 'P'));
$middleavatar = base64_decode(getgpc('avatar2', 'P'));
$smallavatar = base64_decode(getgpc('avatar3', 'P'));
if(!$bigavatar || !$middleavatar || !$smallavatar) {
	echo "<script>window.parent.postMessage('failure','*');</script>";
	exit;
}

$success = file_put_contents($storageRoot.$bigavatarfile, $bigavatar) !== false
	&& file_put_contents($storageRoot.$middleavatarfile, $middleavatar) !== false
	&& file_put_contents($storageRoot.$smallavatarfile, $smallavatar) !== false;

if($success && !empty($ftp['on']) && $ftp['on'] == 2 && $oss['oss_avatar']) {
	ftpcmd('upload', $bigavatarfile);
	ftpcmd('upload', $middleavatarfile);
	ftpcmd('upload', $smallavatarfile);
	@unlink($storageRoot.$bigavatarfile);
	@unlink($storageRoot.$middleavatarfile);
	@unlink($storageRoot.$smallavatarfile);
}

if($success && !$_G['member']['avatarstatus']) {
	table_common_member::t()->update($_G['uid'], ['avatarstatus' => '1']);
}

echo $success ? "<script>window.parent.postMessage('success','*');</script>" : "<script>window.parent.postMessage('failure','*');</script>";

function get_avatar($uid, $size = 'big', $type = '') {
	$size = in_array($size, ['big', 'middle', 'small']) ? $size : 'big';
	$uid = abs(intval($uid));
	$uid = sprintf('%09d', $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}
