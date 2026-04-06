<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['githubconnect'] = [
	'githubconnect_enable' => '启用 GitHub 登录',
	'githubconnect_enable_desc' => '关闭后，不在登录页和注册页显示 GitHub 登录按钮。',
	'githubconnect_clientid_desc' => 'GitHub OAuth App 的 Client ID。',
	'githubconnect_clientsecret_desc' => 'GitHub OAuth App 的 Client Secret。',
	'githubconnect_callback_url' => '回调地址',
	'githubconnect_callback_desc' => '在 GitHub OAuth App 中将回调地址配置为此 URL。',
	'githubconnect_update_succeed' => 'GitHub 登录设置已更新。',
	'githubconnect_login_button' => '使用 GitHub 登录',
	'githubconnect_not_configured' => 'GitHub 登录尚未配置。',
	'githubconnect_account_type_missing' => 'GitHub 登录账号类型未初始化，请重新安装插件。',
	'githubconnect_state_invalid' => 'GitHub 登录状态校验失败，请重试。',
	'githubconnect_token_failed' => 'GitHub 授权令牌获取失败。',
	'githubconnect_user_failed' => 'GitHub 用户信息获取失败。',
	'githubconnect_bind_other_exists' => '该 GitHub 账号已绑定到其他论坛账号。',
	'githubconnect_bind_success' => 'GitHub 账号绑定成功。',
];

$templatelang['githubconnect'] = $scriptlang['githubconnect'];
