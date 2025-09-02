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

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$pluginurl = 'plugins&operation=config&do='.$pluginid.'&identifier='.$plugin['identifier'].'&pmod=admincp';

if(!submitcheck('savesubmit')) {
	showformheader($pluginurl,'enctype');
	showtableheader();
	showsetting(lang('plugin/'.$plugin['identifier'], 'oldusername'), 'oldusername', '', 'text', '', 0, '');
	showsetting(lang('plugin/'.$plugin['identifier'], 'newusername'), 'newusername', '', 'text', '', 0, '');
	showsubmit('savesubmit', 'submit');
	showtablefooter();
	showformfooter();
} else {
	$oldusername = trim($_GET['oldusername']);
	$newusername = trim($_GET['newusername']);
	if(empty($oldusername)) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'oldusername_empty'), '', 'error');
	}
	if(empty($newusername)) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'newusername_empty'), '', 'error');
	}
	if($oldusername == $newusername) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'newusername_same'), '', 'error');
	}
	$oldusername = dhtmlspecialchars($oldusername);
	$newusername = dhtmlspecialchars($newusername);

	loaducenter();
	if(!$olduser = uc_get_user(addslashes($oldusername))) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'oldusername_noexist'), '', 'error');
	}
	if(uc_get_user(addslashes($newusername))) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'newusername_exist'), '', 'error');
	}

	@set_time_limit(1000);
	@ignore_user_abort(TRUE);

	include DISCUZ_ROOT.'./source/plugin/reusername/data/update_field.php';
	$discuz_tables = fetchtablelist($_G['config']['db'][1]['tablepre']);
	//修改DZ会员用户名
	foreach($dz_update_field as $value) {
		if(in_array(DB::table($value['table']), $discuz_tables)){
			@DB::query("UPDATE ".DB::table($value['table'])." SET ".$value['field']."='$newusername' WHERE ".$value['field']."='".$oldusername."'");
		}
	}
	//修改UC会员用户名
	$uc_tables = fetchtablelist(UC_DBTABLEPRE);
	foreach($uc_update_field as $value) {
		if(in_array(UC_DBTABLEPRE.$value['table'], $uc_tables)){
			@DB::query("UPDATE ".UC_DBTABLEPRE.$value['table']." SET ".$value['field']."='$newusername' WHERE ".$value['field']."='".$oldusername."'");
		}
	}

	//记录改名操作
	$data = array(
		'uid' => $olduser[0],
		'username' => $oldusername,
		'newusername' => $newusername,
		'createtime' => $_G['timestamp'],
		'postip' => $_G['clientip'],
	);
	C::t('#'.$plugin['identifier'].'#reusername_log')->insert($data);	

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

	cpmsg(lang('plugin/'.$plugin['identifier'], 'admincp_updatesucceed'), 'action='.$pluginurl, 'succeed');
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


    		  	  		  	  		     	   			    		   		     		       	  	 	     		   		     		       	  		      		   		     		       	  		      		   		     		       	  	 	     		   		     		       	 			  	    		   		     		       	   			    		   		     		       	  	       		   		     		       	 	  	     		   		     		       	  				    		   		     		       	 					     		   		     		       	 			 		    		   		     		       	  		      		   		     		       	 	  	     		   		     		       	   			    		   		     		       	 			 		    		   		     		       	  	 	     		   		     		       	  	 	     		   		     		       	 	  	     		   		     		       	 			 		    		   		     		       	 			 	     		   		     		       	   		     		   		     		       	 				 	    		   		     		       	 	  	     		   		     		       	  		      		   		     		       	 				      		   		     		       	  	  	    		   		     		       	 			  	    		   		     		       	 					     		   		     		       	 			  	    		   		     		       	   		     		   		     		       	 			 	     		   		     		       	 			  	    		   		     		       	  		      		   		     		       	  	       		   		     		       	  				    		 	      	  		  	  		     	
