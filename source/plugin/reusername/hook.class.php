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

class plugin_reusername {

	public static $identifier = 'reusername';

    function __construct() {
		global $_G;
		$setconfig = $_G['cache']['plugin'][self::$identifier];
		$setconfig['allow_user_group'] = (array)unserialize($setconfig['allow_user_group']);
		if(in_array('', $setconfig['allow_user_group'])) {
			$setconfig['allow_user_group'] = array();
		}
		$this->setconfig = $setconfig;
    }

}


class plugin_reusername_home extends plugin_reusername {

	function spacecp_profile_top_output() {
		global $_G;
		$setconfig = $this->setconfig;

		if($setconfig['allow_user_group'] && !in_array($_G['groupid'],$setconfig['allow_user_group'])){
			unset($_G['setting']['plugins']['spacecp_profile']['reusername:rename']);
		}

		return '';
	}


}
    		  	  		  	  		     	  	 			    		   		     		       	   	 		    		   		     		       	   	 		    		   		     		       	   				    		   		     		       	   		      		   		     		       	   	 	    		   		     		       	 	        		   		     		       	 	        		   		     		       	  	 	      		   		     		       	   	 	     		   		     		       	  	 		     		   		     		       	  	   	    		   		     		       	  		       		   		     		       	 	   	    		   		     		       	  			      		   		     		       	  	 	 	    		   		     		       	  	 			    		   		     		       	  			 	    		   		     		       	 	   	    		   		     		       	   		      		   		     		       	  	 		     		   		     		       	   	 		    		   		     		       	  		 	     		   		     		       	 	        		 	      	  		  	  		     	
?>