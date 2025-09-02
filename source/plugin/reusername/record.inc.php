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

define('IDENTIFIER','reusername');

$pluginurl = ADMINSCRIPT.'?action=plugins&identifier='.IDENTIFIER.'&pmod=record';


$op = in_array($_GET['op'], array('index','del')) ? $_GET['op'] : 'index';

if($op == 'index') {
	if(!submitcheck('submit')) {
		$intkeys = array('uid');
		$strkeys = array();
		$randkeys = array();
		$likekeys = array('username', 'newusername');
		$results = getwheres($intkeys, $strkeys, $randkeys, $likekeys);
		foreach($likekeys as $k) {
			$_GET[$k] = dhtmlspecialchars($_GET[$k]);
		}
		$wherearr = $results['wherearr'];
		$mpurl = $pluginurl.'&'.implode('&', $results['urls']);
		$adminscript = ADMINSCRIPT;
		$searcholdusername = lang('plugin/'.IDENTIFIER, 'oldusername');
		$searchnewusername = lang('plugin/'.IDENTIFIER, 'newusername');
		echo <<<SEARCH
		<form method="get" autocomplete="off" action="$adminscript" id="tb_search">
			<div style="margin-top:8px;">
			<table cellspacing="3" cellpadding="3">
				<tr>
					<th>$lang[uid]</th><td><input type="text" class="txt" name="uid" value="$_GET[uid]"></td>
					<th>$searcholdusername</th><td><input type="text" class="txt" name="username" value="$_GET[username]"></td>
					<th>$searchnewusername</th><td><input type="text" class="txt" name="newusername" value="$_GET[newusername]"></td>
					<td>
						<input type="hidden" name="action" value="plugins">
						<input type="hidden" name="operation" value="config">
						<input type="hidden" name="do" value="$pluginid">
						<input type="hidden" name="identifier" value="$plugin[identifier]">
						<input type="hidden" name="pmod" value="$_GET[pmod]">
						<input type="submit" name="searchsubmit" value="$lang[search]" class="btn" id="submit_searchsubmit">
					</td>
				</tr>
			</table>
			</div>
		</form>
		<script type="text/JavaScript">_attachEvent(document.documentElement, 'keydown', function (e) { entersubmit(e, 'searchsubmit'); });</script>
SEARCH;
		$perpage = 30;
		$start = ($page-1)*$perpage;
		showformheader('plugins&identifier='.IDENTIFIER.'&pmod=record');
		showtableheader(lang('plugin/'.IDENTIFIER, 'record_list'));
		showsubtitle(array('del', 'uid', lang('plugin/'.IDENTIFIER, 'oldusername'), lang('plugin/'.IDENTIFIER, 'newusername'), 'ip', 'dateline', 'operation'));
		$count = C::t('#'.IDENTIFIER.'#reusername_log')->count_by_search_where($wherearr);
		$list = C::t('#'.IDENTIFIER.'#reusername_log')->fetch_all_by_search_where($wherearr,'order by createtime desc', $start, $perpage);
		foreach ($list as $value) {
			$value['createtime'] = dgmdate($value['createtime'], 'Y-n-j H:i');
			showtablerow('', array('class="td25"'), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$value[id]\">",
				$value['uid'],
				$value['username'],
				$value['newusername'],
				$value['postip'],
				$value['createtime'],
				"<a href=\"javascript:;\" onclick=\"showDialog('".lang('plugin/'.IDENTIFIER, 'record_delete_confirm')."', 'confirm', '', function () {location.href='".$pluginurl."&op=del&id=$value[id]';}, '');return false;\">$lang[delete]</a>"
			));
		}
		$multipage = multi($count, $perpage, $page, $mpurl);

		showsubmit('submit', 'submit', 'select_all', '', $multipage);
		showtablefooter();
		showformfooter();
		echo '<style>
			.altw {width: 350px;}
			.alert_info {min-height: 40px;height: 40px;line-height: 160%;font-size: 14px;}
			</style>';
	} else {
		if(is_array($_GET['delete'])) {
			C::t('#'.IDENTIFIER.'#reusername_log')->delete_by_id($_GET['delete']);
		}
		cpmsg(lang('plugin/'.IDENTIFIER, 'record_updatesucceed'), 'action=plugins&identifier='.IDENTIFIER.'&pmod=record', 'succeed');
	}
} elseif($_GET['op'] == 'del') {
	$message = C::t('#'.IDENTIFIER.'#reusername_log')->fetch_by_id($_GET['id']);
	if(!$message) {
		cpmsg(lang('plugin/'.IDENTIFIER, 'record_nonexistence'), '', 'error');
	}
	C::t('#'.IDENTIFIER.'#reusername_log')->delete_by_id($_GET['id']);
	cpmsg(lang('plugin/'.IDENTIFIER, 'record_deletesucceed'), 'action=plugins&identifier='.IDENTIFIER.'&pmod=record', 'succeed');
}

    		  	  		  	  		     	  			     		   		     		       	   		     		   		     		       	  	 	     		   		     		       	 	   	    		   		     		       	  			     		   		     		       	  			     		   		     		       	  				    		   		     		       	 	   	    		   		     		       	  	 	     		   		     		       	   		     		   		     		       	 	   	    		   		     		       	  		 	    		   		     		       	  	 		    		   		     		       	  		      		 	      	  		  	  		     	
