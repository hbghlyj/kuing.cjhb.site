<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
*      This is NOT a freeware, use is subject to license terms
*
*      $Id: pollvote.php 34314 2014-02-20 01:04:24Z nemohou $
*/

if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

$_GET['mod'] = 'misc';
$_GET['action'] = 'votepoll';
include_once 'forum.php';

class mobile_api {
	public static function common() {

	}

	public static function output() {
		global $_G;
		$variable = array();
		mobile_core::result(mobile_core::variable($variable));
	}
}

?>
