<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forummisc.php 35102 2014-11-18 10:09:27Z nemohou $
 */
if (!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

$_GET['mod'] = 'misc';
include_once 'forum.php';

class mobile_api {

	public static function common() {
		if($_GET['t'] == 'common') {
			$variable = array();
			mobile_core::result(mobile_core::variable($variable));
		}
	}

	public static function output() {
		if($_GET['t'] == 'output') {
			$variable = array();
			mobile_core::result(mobile_core::variable($variable));
		}
	}

}

<<<<<<< HEAD
=======
?>
>>>>>>> 8cd3387e (migrating from https://gitee.com/kuingggg/DiscuzX/tree/test-0726)
