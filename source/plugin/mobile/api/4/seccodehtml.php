<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: seccodehtml.php 34428 2014-04-25 09:09:34Z nemohou $
 */

if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

include_once 'misc.php';

class mobile_api {

	public static function common() {
		global $_G;
		echo '<img src="'.$_G['siteurl'].'api/mobile/index.php?module=seccode&sechash='.urlencode($_GET['sechash']).'&version='.(empty($_GET['secversion']) ? '4' : $_GET['secversion']).'" />';
		exit;
	}

	public static function output() {}

}

<<<<<<< HEAD
=======
?>
>>>>>>> 8cd3387e (migrating from https://gitee.com/kuingggg/DiscuzX/tree/test-0726)
