<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./source/class/class_account.php';
require_once DISCUZ_ROOT.'./source/class/account/account_base.php';

if(account_base::getAccountType('githubconnect') === false) {
	account_base::registerAccount('githubconnect');
}

C::t('common_setting')->update_batch([
	'githubconnect_allow' => '0',
	'githubconnect_clientid' => '',
	'githubconnect_clientsecret' => '',
]);

require_once libfile('function/cache');
updatecache('setting');

$finish = true;
