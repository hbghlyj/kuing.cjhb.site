<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forumupload.php 34314 2014-02-20 01:04:24Z nemohou $
 */

if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

$_GET['mod'] = 'upload';
$_GET['action'] = 'upload';
$_GET['operation'] = 'upload';
include_once 'misc.php';

class mobile_api {

	public static function common() {}

	public static function output() {}

}

?>