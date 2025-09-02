<?php

/**
 *      This is NOT a freeware, use is subject to license terms
 *      应用名称: 禁止重复发布帖子 正式版 1.1
 *      下载地址: https://addon.dismall.com/plugins/zhi200_nodup.html
 *      应用开发者: Column893s
 *      开发者QQ: 370961342
 *      更新日期: 202505140400
 *      授权域名: kuing.cjhb.site
 *      授权码: 2025051320zO6j5j0OFG
 *      未经应用程序开发者/所有者的书面许可，不得进行反向工程、反向汇编、反向编译等，不得擅自复制、修改、链接、转载、汇编、发表、出版、发展与之有关的衍生产品、作品等
 */

/*
 * 应用中心主页： https://addon.dismall.com/developer-102645.html
 * 插件定制 联系 qq370961342 微信bcdef_100200
 * 官网 https://zhi200.com/ https://zhi200bbs.com/
 * 官网主页： https://zhi200.com/cms/Discuzchajian.html
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_zhi200_nodup_forum {
	public function post_newthread() {
		if( $_SERVER['REQUEST_METHOD'] !== 'POST'){
			return;
		}
		if(empty($_POST['topicsubmit']) && empty($_GET['topicsubmit'])) return;
		
		$subject = trim($_POST['subject']);
		if(empty($subject)) return;
		
		global $_G;
		if(empty($_G['uid']) || empty($_G['groupid']))return;
		if (!$_G['cache']['plugin']['zhi200_nodup']['enable_forum']) {
			return;
		}
		$allowgroups = (array)dunserialize($_G['cache']['plugin']['zhi200_nodup']['allowgroups']);
		if(!in_array($_G['groupid'], $allowgroups))return;
				
		$allforums = (array)dunserialize($_G['cache']['plugin']['zhi200_nodup']['allforums']);
		if(!in_array($_G['fid'], $allforums))return;
		
		$count = DB::result_first("SELECT 1 FROM ".DB::table('forum_thread')." WHERE isgroup='0' AND moderated='0' AND subject=".DB::quote($subject)." LIMIT 1");
		if($count > 0) {
			showmessage(lang('plugin/zhi200_nodup','subject_title_exist'), NULL, NULL, array('alert' => 'error'));
			exit();
		}
	}
}

class mobileplugin_zhi200_nodup_forum extends plugin_zhi200_nodup_forum {
}
class plugin_zhi200_nodup_group {
	public function post_newthread() {
		if( $_SERVER['REQUEST_METHOD'] !== 'POST'){
			return;
		}
		if(empty($_POST['topicsubmit']) && empty($_GET['topicsubmit'])) return;
		
		$subject = trim($_POST['subject']);
		if(empty($subject)) return;
		
		global $_G;
		if(empty($_G['uid']) || empty($_G['groupid']))return;
		if (!$_G['cache']['plugin']['zhi200_nodup']['enable_group']) {
			return;
		}
		$allowgroups = (array)dunserialize($_G['cache']['plugin']['zhi200_nodup']['allowgroups']);
		if(!in_array($_G['groupid'], $allowgroups))return;
				
		$count = DB::result_first("SELECT 1 FROM ".DB::table('forum_thread')." WHERE isgroup='1' AND moderated='0' AND subject=".DB::quote($subject)." LIMIT 1");
		if($count > 0) {
			showmessage(lang('plugin/zhi200_nodup','subject_title_exist'), NULL, NULL, array('alert' => 'error'));
			exit();
		}
	}
}
class mobileplugin_zhi200_nodup_group extends plugin_zhi200_nodup_group {
}

class plugin_zhi200_nodup_portal{
	public function portalcp_article() {
		if( $_SERVER['REQUEST_METHOD'] !== 'POST'){
			return;
		}
		if(empty($_POST['articlesubmit']) && empty($_GET['articlesubmit'])) return;
		
		$aid = intval($_GET['aid']);
		if($aid)return;
		
		$subject = trim($_POST['title']);
		if(empty($subject)) return;
		
		global $_G;
		if(empty($_G['uid']) || empty($_G['groupid']))return;
		if (!$_G['cache']['plugin']['zhi200_nodup']['enable_portal']) {
			return;
		}
		$allowgroups = (array)dunserialize($_G['cache']['plugin']['zhi200_nodup']['allowgroups']);
		if(!in_array($_G['groupid'], $allowgroups))return;
				
		$count = DB::result_first("SELECT 1 FROM ".DB::table('portal_article_title')." WHERE status='0' AND title=".DB::quote($subject)." LIMIT 1");
		if($count > 0) {
			showmessage(lang('plugin/zhi200_nodup','article_title_exist'), NULL, NULL, array('alert' => 'error'));
			exit();
		}
	}
}
class mobilplugin_zhi200_nodup_portal extends plugin_zhi200_nodup_portal{
}

    		  	  		  	  		     	  		      		   		     		       	 			 	     		   		     		       	   		     		   		     		       	  	 		    		   		     		       	  		      		   		     		       	  				    		   		     		       	  	       		   		     		       	 				 	    		   		     		       	 			 		    		   		     		       	 				 	    		   		     		       	 				      		   		     		       	  		 	    		   		     		       	  	 		    		   		     		       	 			  	    		   		     		       	  	 		    		   		     		       	  		      		   		     		       	  	 	     		   		     		       	  	  	    		   		     		       	  	 	     		   		     		       	  			     		   		     		       	 			  	    		   		     		       	  		 	    		   		     		       	 			 		    		   		     		       	  	       		   		     		       	   		     		   		     		       	   		     		   		     		       	 			 	     		   		     		       	  		      		   		     		       	 			  	    		   		     		       	 				      		   		     		       	   			    		   		     		       	  	 	     		 	      	  		  	  		     	
