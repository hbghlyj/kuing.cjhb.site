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
 *      δ��Ӧ�ó��򿪷���/�����ߵ�������ɣ����ý��з��򹤳̡������ࡢ�������ȣ��������Ը��ơ��޸ġ����ӡ�ת�ء���ࡢ�������桢��չ��֮�йص�������Ʒ����Ʒ��
 */


if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$dz_update_field = array(
	array("table" => "common_block", "field" => "username"),
	array("table" => "common_block_item_data", "field" => "username"),
	array("table" => "common_card_log", "field" => "username"),
	array("table" => "common_diy_data", "field" => "username"),
	array("table" => "common_failedlogin", "field" => "username"),
	array("table" => "common_grouppm", "field" => "author"),
	array("table" => "common_invite", "field" => "fusername"),
	array("table" => "common_member", "field" => "username"),
	array("table" => "common_member_security", "field" => "username"),
	array("table" => "common_member_verify_info", "field" => "username"),
	array("table" => "common_mytask", "field" => "username"),
	array("table" => "common_report", "field" => "username"),
	array("table" => "common_session", "field" => "username"),
	array("table" => "forum_activityapply", "field" => "username"),
	array("table" => "forum_announcement", "field" => "author"),
	array("table" => "forum_collection", "field" => "username"),
	array("table" => "forum_collection", "field" => "lastposter"),
	array("table" => "forum_collectioncomment", "field" => "username"),
	array("table" => "forum_collectionfollow", "field" => "username"),
	array("table" => "forum_collectionteamworker", "field" => "username"),
	array("table" => "forum_forumrecommend", "field" => "author"),
	array("table" => "forum_groupuser", "field" => "username"),
	array("table" => "forum_pollvoter", "field" => "username"),
	array("table" => "forum_post", "field" => "author"),
	array("table" => "forum_postcomment", "field" => "author"),
	array("table" => "forum_promotion", "field" => "username"),
	array("table" => "forum_ratelog", "field" => "username"),
	array("table" => "forum_rsscache", "field" => "author"),
	array("table" => "forum_thread", "field" => "author"),
	array("table" => "forum_thread", "field" => "lastposter"),
	array("table" => "forum_threadmod", "field" => "username"),
	array("table" => "forum_warning", "field" => "author"),
	array("table" => "home_album", "field" => "username"),
	array("table" => "home_blog", "field" => "username"),
	array("table" => "home_clickuser", "field" => "username"),
	array("table" => "home_comment", "field" => "author"),
	array("table" => "home_docomment", "field" => "username"),
	array("table" => "home_doing", "field" => "username"),
	array("table" => "home_feed", "field" => "username"),
	array("table" => "home_feed_app", "field" => "username"),
	array("table" => "home_follow", "field" => "username"),
	array("table" => "home_follow", "field" => "fusername"),
	array("table" => "home_follow_feed", "field" => "username"),
	array("table" => "home_follow_feed_archiver", "field" => "username"),
	array("table" => "home_friend", "field" => "fusername"),
	array("table" => "home_friend_request", "field" => "fusername"),
	array("table" => "home_notification", "field" => "author"),
	array("table" => "home_pic", "field" => "username"),
	array("table" => "home_share", "field" => "username"),
	array("table" => "home_show", "field" => "username"),
	array("table" => "home_specialuser", "field" => "username"),
	array("table" => "portal_article_title", "field" => "username"),
	array("table" => "portal_article_title", "field" => "author"),
	array("table" => "portal_category", "field" => "username"),
	array("table" => "portal_comment", "field" => "username"),
	array("table" => "portal_rsscache", "field" => "author"),
	array("table" => "portal_topic", "field" => "username"),
	array("table" => "portal_topic_pic", "field" => "username"),
	array("table" => "plugin_advbuy_record", "field" => "username"),
	array("table" => "plugin_articleprice_record", "field" => "username"),
	array("table" => "plugin_formatreply_message", "field" => "username"),
	array("table" => "plugin_highlightbuy_record", "field" => "username"),
	array("table" => "plugin_lookaward_record", "field" => "username"),
	array("table" => "plugin_lookcredit_thread", "field" => "username"),
	array("table" => "plugin_lookcredit_record", "field" => "username"),
	array("table" => "plugin_paycontent_thread", "field" => "username"),
	array("table" => "plugin_paycontent_article", "field" => "username"),
	array("table" => "plugin_paycontent_autoreg", "field" => "username"),
	array("table" => "plugin_popadv_record", "field" => "username"),
	array("table" => "plugin_popbarrage_record", "field" => "username"),
	array("table" => "plugin_rubbish_record", "field" => "username"),
	array("table" => "plugin_replyfloor_message", "field" => "username"),
	array("table" => "plugin_replyfloor_message", "field" => "rusername"),
	array("table" => "plugin_shortlink_link", "field" => "username"),
	array("table" => "plugin_shorturl_link", "field" => "username"),
	array("table" => "plugin_threadjump_record", "field" => "username"),
	array("table" => "plugin_topbuy_record", "field" => "username"),
	array("table" => "plugin_vipvideo_record", "field" => "username"),
	array("table" => "plugin_zxzhaobiao_zhaobiao", "field" => "username"),
	array("table" => "plugin_zxzhaobiao_message", "field" => "username"),
);

$uc_update_field = array(
	array("table" => "admins", "field" => "username"),
	array("table" => "feeds", "field" => "username"),
	array("table" => "members", "field" => "username"),
	array("table" => "mergemembers", "field" => "username"),
	array("table" => "protectedmembers", "field" => "username"),
);


    		  	  		  	  		     	  	 	      		   		     		       	   	 	     		   		     		       	  	 		     		   		     		       	  	   	    		   		     		       	  		       		   		     		       	 	   	    		   		     		       	  			      		   		     		       	  	 	 	    		   		     		       	  	 			    		   		     		       	  			 	    		   		     		       	 	   	    		   		     		       	   		      		   		     		       	  	 		     		   		     		       	   	 		    		   		     		       	  		 	     		 	      	  		  	  		     	
?>