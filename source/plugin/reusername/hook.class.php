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


/**
 *      $author: ���� $
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