<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./source/class/class_account.php';
require_once DISCUZ_ROOT.'./source/class/account/account_base.php';

if(account_base::getAccountType('githubconnect') !== false) {
	account_base::unregisterAccount('githubconnect');
}

DB::delete('common_setting', "skey IN ('githubconnect_allow', 'githubconnect_clientid', 'githubconnect_clientsecret')");

require_once libfile('function/cache');
updatecache('setting');

$finish = true;
