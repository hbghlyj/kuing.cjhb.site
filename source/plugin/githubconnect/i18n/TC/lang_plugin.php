<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['githubconnect'] = [
	'githubconnect_enable' => '啟用 GitHub 登入',
	'githubconnect_enable_desc' => '關閉後，不在登入頁和註冊頁顯示 GitHub 登入按鈕。',
	'githubconnect_clientid_desc' => 'GitHub OAuth App 的 Client ID。',
	'githubconnect_clientsecret_desc' => 'GitHub OAuth App 的 Client Secret。',
	'githubconnect_callback_url' => '回呼位址',
	'githubconnect_callback_desc' => '在 GitHub OAuth App 中將回呼位址配置為此 URL。',
	'githubconnect_update_succeed' => 'GitHub 登入設定已更新。',
	'githubconnect_login_button' => '使用 GitHub 登入',
	'githubconnect_not_configured' => 'GitHub 登入尚未設定。',
	'githubconnect_account_type_missing' => 'GitHub 登入帳號類型未初始化，請重新安裝外掛。',
	'githubconnect_state_invalid' => 'GitHub 登入狀態校驗失敗，請重試。',
	'githubconnect_token_failed' => 'GitHub 授權權杖取得失敗。',
	'githubconnect_user_failed' => 'GitHub 使用者資訊取得失敗。',
	'githubconnect_resolve_title' => '選擇如何繼續使用 GitHub',
	'githubconnect_resolve_message' => '該 GitHub 使用者名稱與現有論壇使用者名稱重複，但電子郵件並未匹配到現有論壇帳號。請選擇綁定現有帳號，或建立新的論壇帳號。',
	'githubconnect_resolve_bind' => '綁定現有帳號',
	'githubconnect_resolve_create' => '建立新帳號',
	'githubconnect_resolve_expired' => 'GitHub 登入工作階段已過期，請重試。',
	'githubconnect_resolve_login' => 'GitHub 使用者名稱',
	'githubconnect_resolve_email' => 'GitHub 電子郵件',
	'githubconnect_bind_other_exists' => '該 GitHub 帳號已綁定到其他論壇帳號。',
	'githubconnect_bind_success' => 'GitHub 帳號綁定成功。',
];

$templatelang['githubconnect'] = $scriptlang['githubconnect'];
