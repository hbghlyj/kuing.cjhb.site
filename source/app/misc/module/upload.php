<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$_G['uid'] || !hash_equals(FORMHASH, (string)($_POST['formhash'] ?? ''))) {
	http_response_code(403);
	exit;
}

$file = childfile($_GET['operation']);
if(file_exists($file)) {
	require_once $file;
}
