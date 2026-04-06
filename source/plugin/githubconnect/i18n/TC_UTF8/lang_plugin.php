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
	'githubconnect_bind_other_exists' => '該 GitHub 帳號已綁定到其他論壇帳號。',
	'githubconnect_bind_success' => 'GitHub 帳號綁定成功。',
];

$templatelang['githubconnect'] = $scriptlang['githubconnect'];
