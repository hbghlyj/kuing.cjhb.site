<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['googleconnect'] = [
	'connect_mobile_login' => '使用 Google 登入',
	'connect_moile_login' => '使用 Google 登入',
	'googleconnect_resolve_title' => '選擇如何繼續使用 Google',
	'googleconnect_resolve_message' => '該 Google 帳號名稱與現有論壇使用者名稱重複，但電子郵件並未匹配到現有論壇帳號。請選擇綁定現有帳號，或建立新的論壇帳號。',
	'googleconnect_resolve_bind' => '綁定現有帳號',
	'googleconnect_resolve_create' => '建立新帳號',
	'googleconnect_resolve_expired' => 'Google 登入工作階段已過期，請重試。',
	'googleconnect_resolve_username' => 'Google 顯示名稱',
	'googleconnect_resolve_email' => 'Google 電子郵件',
	'googleconnect_bind_success' => 'Google 帳號綁定成功。',
];

$templatelang['googleconnect'] = $scriptlang['googleconnect'];
