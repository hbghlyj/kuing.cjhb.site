<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./source/class/class_account.php';
require_once DISCUZ_ROOT.'./source/class/account/account_base.php';

if(account_base::getAccountType('xconnect') !== false) {
	account_base::unregisterAccount('xconnect');
}

DB::delete('common_setting', "skey IN ('xconnect_allow', 'xconnect_clientid', 'xconnect_clientsecret')");

require_once libfile('function/cache');
updatecache('setting');

$finish = true;


