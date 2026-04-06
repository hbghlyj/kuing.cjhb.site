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
	'githubconnect_resolve_title' => '选择如何继续使用 GitHub',
	'githubconnect_resolve_message' => '该 GitHub 用户名与现有论坛用户名重复，但邮箱并未匹配到现有论坛账号。请选择绑定现有账号，或创建一个新的论坛账号。',
	'githubconnect_resolve_bind' => '绑定现有账号',
	'githubconnect_resolve_create' => '创建新账号',
	'githubconnect_resolve_expired' => 'GitHub 登录会话已过期，请重试。',
	'githubconnect_resolve_login' => 'GitHub 用户名',
	'githubconnect_resolve_email' => 'GitHub 邮箱',
	'githubconnect_bind_other_exists' => '该 GitHub 账号已绑定到其他论坛账号。',
	'githubconnect_bind_success' => 'GitHub 账号绑定成功。',
];

$templatelang['githubconnect'] = $scriptlang['githubconnect'];
