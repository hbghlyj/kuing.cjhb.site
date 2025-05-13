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
 *      $author: 乘凉 $
 */

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('IDENTIFIER','reusername');

$csspath = 'source/plugin/'.IDENTIFIER.'/css/';
if(empty($_G['uid'])) {
	showmessage('to_login', '', array(), array('showmsg' => true, 'login' => 1));
}
loadcache('plugin');
$setconfig = $_G['cache']['plugin'][IDENTIFIER];
$setconfig['allow_user_group'] = (array)unserialize($setconfig['allow_user_group']);
if(in_array('', $setconfig['allow_user_group'])) {
	$setconfig['allow_user_group'] = array();
}

if($setconfig['allow_user_group'] && !in_array($_G['groupid'],$setconfig['allow_user_group'])){
	showmessage(lang('plugin/'.IDENTIFIER, 'no_permission'));
}
$showcredit = array();
if($setconfig['use_credit_item'] && $setconfig['use_credit_num'] && $setconfig['use_credit_num'] > 0) {
	$showcredit['title'] = lang('plugin/'.IDENTIFIER, 'current_credit', array('credititem' => $_G['setting']['extcredits'][$setconfig['use_credit_item']]['title']));
	$showcredit['num'] = getuserprofile('extcredits'.$setconfig['use_credit_item']);
	$showcredit['tip'] = lang('plugin/'.IDENTIFIER, 'deduct_credit', array('creditnum' => $setconfig['use_credit_num'], 'creditunit' => $_G['setting']['extcredits'][$setconfig['use_credit_item']]['unit'], 'credititem' => $_G['setting']['extcredits'][$setconfig['use_credit_item']]['title']));
}

if(submitcheck('savesubmit', 0, $setconfig['open_seccode'])) {
	if($setconfig['change_limit']) {
		$wherearr = array();
		$wherearr[] = "uid = ".$_G['uid'];
		$count = C::t('#'.IDENTIFIER.'#reusername_log')->count_by_search_where($wherearr);
		if($count >= $setconfig['change_limit']) {
			showmessage(lang('plugin/'.IDENTIFIER, 'change_limit', array('change_limit' => $setconfig['change_limit'])));
		}
	}
	if($setconfig['change_time']) {
		$wherearr = array();
		$wherearr[] = "uid = ".$_G['uid'];
		$renamelog = C::t('#'.IDENTIFIER.'#reusername_log')->fetch_one_by_search_where($wherearr, 'order by createtime desc');
		if($renamelog['createtime'] > $_G['timestamp'] - $setconfig['change_time']*3600) {
			showmessage(lang('plugin/'.IDENTIFIER, 'change_time', array('change_time' => $setconfig['change_time'])));
		}
	}
	$username = trim($_GET['newusername']);
	if(empty($username)) {
		showmessage(lang('plugin/'.IDENTIFIER, 'newusername_empty'));
	}
	$username = dhtmlspecialchars($username);
	if($username == $_G['username']) {
		showmessage(lang('plugin/'.IDENTIFIER, 'newusername_same'));
	}
	$usernamelen = dstrlen($username);
	if($usernamelen < $setconfig['min_length']) {
		showmessage(lang('plugin/'.IDENTIFIER, 'newusername_tooshort', array('length' => $setconfig['min_length'])));
	} elseif($usernamelen > $setconfig['max_length']) {
		showmessage(lang('plugin/'.IDENTIFIER, 'newusername_toolong', array('length' => $setconfig['max_length'])));
	}
	loaducenter();
	if(uc_get_user(addslashes($username))) {
		showmessage('profile_username_duplicate');
	}
	$censorexp = '/^('.str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($_G['setting']['censoruser'] = trim($_G['setting']['censoruser'])), '/')).')$/i';
	if($_G['setting']['censoruser'] && @preg_match($censorexp, $username)) {
		showmessage('profile_username_protect');
	}
    //判断密码是否输入正确
	if($setconfig['need_password']) {
		require_once DISCUZ_ROOT.'./uc_client/control/user.php';
		$usercontrol = new usercontrol();
		$usercontrol->load('user');
		$temp = array();
		$ucresult = $_ENV['user']->check_login($_G['username'], $_GET['password'], $temp);
		if($ucresult < 0) {
			showmessage(lang('plugin/'.IDENTIFIER, 'password_error'));
		}
	}

	if($setconfig['use_credit_item'] && $setconfig['use_credit_num'] && $setconfig['use_credit_num'] > 0) {
		if(getuserprofile('extcredits'.$setconfig['use_credit_item']) < $setconfig['use_credit_num']) {
			showmessage(lang('plugin/'.IDENTIFIER, 'credit_notenough', array('credititem' => $_G['setting']['extcredits'][$setconfig['use_credit_item']]['title'])));
		}
		updatemembercount($_G['uid'], array ('extcredits'.$setconfig['use_credit_item'] => -$setconfig['use_credit_num']), 1, '', 0, '', lang('plugin/'.IDENTIFIER, 'reusername'), lang('plugin/'.IDENTIFIER, 'reusername'));
	}

	@set_time_limit(1000);
	@ignore_user_abort(TRUE);

	include DISCUZ_ROOT.'./source/plugin/reusername/data/update_field.php';
	$discuz_tables = fetchtablelist($_G['config']['db'][1]['tablepre']);
	//修改DZ会员用户名
	foreach($dz_update_field as $value) {
		if(in_array(DB::table($value['table']), $discuz_tables)){
			@DB::query("UPDATE ".DB::table($value['table'])." SET ".$value['field']."='$username' WHERE ".$value['field']."='".$_G['username']."'");
		}
	}
	//修改UC会员用户名
	$uc_tables = fetchtablelist(UC_DBTABLEPRE);
	foreach($uc_update_field as $value) {
		if(in_array(UC_DBTABLEPRE.$value['table'], $uc_tables)){
			@DB::query("UPDATE ".UC_DBTABLEPRE.$value['table']." SET ".$value['field']."='$username' WHERE ".$value['field']."='".$_G['username']."'");
		}
	}

	//记录改名操作
	$data = array(
		'uid' => $_G['uid'],
		'username' => $_G['username'],
		'newusername' => $username,
		'createtime' => $_G['timestamp'],
		'postip' => $_G['clientip'],
	);
	C::t('#'.IDENTIFIER.'#reusername_log')->insert($data);	

	//内存清理
	C::memory()->clear();

	//修改欢迎新会员
	require_once libfile('cache/userstats', 'function');
	build_cache_userstats();

	//更新版块最后回复
	require_once libfile('function/post');
	$forums = C::t('forum_forum')->fetch_all_by_status(1);
	foreach($forums as $forum) {
		updateforumcount($forum['fid']);
	}

	showmessage(lang('plugin/'.IDENTIFIER, 'rename_succeed'), dreferer(), array(), array('showdialog' => 1, 'showmsg' => true, 'locationtime' => 5, 'alert' => 'right'));
}else{
	if($setconfig['change_limit']) {
		$wherearr = array();
		$wherearr[] = "uid = ".$_G['uid'];
		$count = C::t('#'.IDENTIFIER.'#reusername_log')->count_by_search_where($wherearr);
		$left_time = $setconfig['change_limit'] - $count;
	}
	if(defined('IN_MOBILE')) {
		if(CURSCRIPT == 'home') {
			dheader('location: plugin.php?id=reusername:rename');
		}
		$navtitle = lang('plugin/'.IDENTIFIER, 'reusername');
		include template(IDENTIFIER.':rename');
	}else{
		if(empty($pluginkey)) {
			dheader('location: home.php?mod=spacecp&ac=plugin&op=profile&id=reusername:rename');
		}
	}
}

function fetchtablelist($tablepre = '') {
	$dbname = $tbpre = '';
	$lastDotPos = strrpos($tablepre, '.'); //取最后一个小数点位置，防止数据库名称中含有小数点
	if($lastDotPos){
		$dbname = substr($tablepre, 0, $lastDotPos);
		$tbpre = substr($tablepre, $lastDotPos + 1);
	}
	$tablepre = str_replace('_', '\_', $tablepre);
	$sqladd = $dbname ? " FROM $dbname LIKE '$tbpre%'" : "LIKE '$tablepre%'";
	$tables = $table = array();
	$query = DB::query("SHOW TABLE STATUS $sqladd");
	while($table = DB::fetch($query)) {
		$table['Name'] = ($dbname ? "$dbname." : '').$table['Name'];
		$tables[] = $table['Name'];
	}
	return $tables;
}

function fetchfieldlist($table = '') {
	$fields = $field = array();
	$query = DB::query("SHOW COLUMNS FROM ".$table);
	while($field = DB::fetch($query)) {
		$fields[] = $field;
	}
	return $fields;
}