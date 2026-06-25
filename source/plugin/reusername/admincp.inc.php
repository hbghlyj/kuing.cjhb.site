<?php

/**
 *      This is NOT a freeware, use is subject to license terms
 *      Ӧ������: �����û��� 2.5
 *      ���ص�ַ: https://addon.dismall.com/plugins/reusername.html
 *      Ӧ�ÿ�����: ����
 *      ������QQ: 594433766
 *      ��������: 202505140312
 *      ��Ȩ����: kuing.cjhb.site
 *      ��Ȩ��: 2025051319u9IbMIRIBa
 *      δ��Ӧ�ó��򿪷���/�����ߵ��������ɣ����ý��з��򹤳̡������ࡢ�������ȣ��������Ը��ơ��޸ġ����ӡ�ת�ء���ࡢ���������桢��չ��֮�йص�������Ʒ����Ʒ��
 */


/**
 *      $author: ���� $
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
	if(!$olduser = native_user_get(addslashes($oldusername))) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'oldusername_noexist'), '', 'error');
	}
	if(native_user_get(addslashes($newusername))) {
		cpmsg(lang('plugin/'.$plugin['identifier'], 'newusername_exist'), '', 'error');
	}

	@set_time_limit(1000);
	@ignore_user_abort(TRUE);

	include DISCUZ_ROOT.'./source/plugin/reusername/data/update_field.php';
	$discuz_tables = fetchtablelist($_G['config']['db'][1]['tablepre']);
	//�޸�DZ��Ա�û���
	foreach($dz_update_field as $value) {
		if(in_array(DB::table($value['table']), $discuz_tables)){
			@DB::query("UPDATE ".DB::table($value['table'])." SET ".$value['field']."='$newusername' WHERE ".$value['field']."='".$oldusername."'");
		}
	}
	//��¼��������
	$data = array(
		'uid' => $olduser[0],
		'username' => $oldusername,
		'newusername' => $newusername,
		'createtime' => $_G['timestamp'],
		'postip' => $_G['clientip'],
	);
	C::t('#'.$plugin['identifier'].'#reusername_log')->insert($data);	

	//�ڴ�����
	C::memory()->clear();

	//�޸Ļ�ӭ�»�Ա
	require_once libfile('cache/userstats', 'function');
	build_cache_userstats();

	//���°�����ظ�
	require_once libfile('function/post');
	$forums = C::t('forum_forum')->fetch_all_by_status(1);
	foreach($forums as $forum) {
		updateforumcount($forum['fid']);
	}

	cpmsg(lang('plugin/'.$plugin['identifier'], 'admincp_updatesucceed'), 'action='.$pluginurl, 'succeed');
}

function fetchtablelist($tablepre = '') {
	$dbname = $tbpre = '';
	$lastDotPos = strrpos($tablepre, '.'); //ȡ���һ��С����λ�ã���ֹ���ݿ������к���С����
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


    		  	  		  	  		     	   			    		   		     		       	  	 	     		   		     		       	  		      		   		     		       	  		      		   		     		       	  	 	     		   		     		       	 			  	    		   		     		       	   			    		   		     		       	  	       		   		     		       	 	  	     		   		     		       	  				    		   		     		       	 					     		   		     		       	 			 		    		   		     		       	  		      		   		     		       	 	  	     		   		     		       	   			    		   		     		       	 			 		    		   		     		       	  	 	     		   		     		       	  	 	     		   		     		       	 	  	     		   		     		       	 			 		    		   		     		       	 			 	     		   		     		       	   		     		   		     		       	 				 	    		   		     		       	 	  	     		   		     		       	  		      		   		     		       	 				      		   		     		       	  	  	    		   		     		       	 			  	    		   		     		       	 					     		   		     		       	 			  	    		   		     		       	   		     		   		     		       	 			 	     		   		     		       	 			  	    		   		     		       	  		      		   		     		       	  	       		   		     		       	  				    		 	      	  		  	  		     	
?>
