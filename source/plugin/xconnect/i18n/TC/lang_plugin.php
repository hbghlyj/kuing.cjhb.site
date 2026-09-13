<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['xconnect'] = [
	'xconnect_enable' => '啟用 X 登入',
	'xconnect_enable_desc' => '關閉後，不在登入頁和註冊頁顯示 X 登入按鈕。',
	'xconnect_clientid_desc' => 'X OAuth App 的 Client ID。',
	'xconnect_clientsecret_desc' => 'X OAuth App 的 Client Secret。',
	'xconnect_callback_url' => '回呼位址',
	'xconnect_callback_desc' => '在 X OAuth App 中將回呼位址配置為此 URL。',
	'xconnect_update_succeed' => 'X 登入設定已更新。',
	'xconnect_login_button' => '使用 X 登入',
	'xconnect_not_configured' => 'X 登入尚未設定。',
	'xconnect_account_type_missing' => 'X 登入帳號類型未初始化，請重新安裝外掛。',
	'xconnect_state_invalid' => 'X 登入狀態校驗失敗，請重試。',
	'xconnect_token_failed' => 'X 授權權杖取得失敗。',
	'xconnect_user_failed' => 'X 使用者資訊取得失敗。',
	'xconnect_resolve_title' => '選擇如何繼續使用 X',
	'xconnect_resolve_message' => '該 X 使用者名稱與現有論壇使用者名稱重複。請選擇綁定現有帳號，或建立新的論壇帳號。',
	'xconnect_resolve_bind' => '綁定現有帳號',
	'xconnect_resolve_create' => '建立新帳號',
	'xconnect_resolve_expired' => 'X 登入工作階段已過期，請重試。',
	'xconnect_resolve_login' => 'X 使用者名稱',
	'xconnect_bind_other_exists' => '該 X 帳號已綁定到其他論壇帳號。',
	'xconnect_bind_success' => 'X 帳號綁定成功。',
];

$templatelang['xconnect'] = $scriptlang['xconnect'];

