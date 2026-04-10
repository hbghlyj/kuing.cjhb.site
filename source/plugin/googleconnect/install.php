<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./source/class/class_account.php';
require_once DISCUZ_ROOT.'./source/class/account/account_base.php';

if(account_base::getAccountType('googleconnect') === false) {
	account_base::registerAccount('googleconnect');
}

require_once libfile('function/cache');
updatecache('setting');

$finish = true;
