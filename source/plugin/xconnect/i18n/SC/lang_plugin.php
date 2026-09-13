<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['xconnect'] = [
	'xconnect_enable' => '启用 X 登录',
	'xconnect_enable_desc' => '关闭后，不在登录页和注册页显示 X 登录按钮。',
	'xconnect_clientid_desc' => 'X OAuth App 的 Client ID。',
	'xconnect_clientsecret_desc' => 'X OAuth App 的 Client Secret。',
	'xconnect_callback_url' => '回调地址',
	'xconnect_callback_desc' => '在 X OAuth App 中将回调地址配置为此 URL。',
	'xconnect_update_succeed' => 'X 登录设置已更新。',
	'xconnect_login_button' => '使用 X 登录',
	'xconnect_not_configured' => 'X 登录尚未配置。',
	'xconnect_account_type_missing' => 'X 登录账号类型未初始化，请重新安装插件。',
	'xconnect_state_invalid' => 'X 登录状态校验失败，请重试。',
	'xconnect_token_failed' => 'X 授权令牌获取失败。',
	'xconnect_user_failed' => 'X 用户信息获取失败。',
	'xconnect_resolve_title' => '选择如何继续使用 X',
	'xconnect_resolve_message' => '该 X 用户名与现有论坛用户名重复。请选择绑定现有账号，或创建一个新的论坛账号。',
	'xconnect_resolve_bind' => '绑定现有账号',
	'xconnect_resolve_create' => '创建新账号',
	'xconnect_resolve_expired' => 'X 登录会话已过期，请重试。',
	'xconnect_resolve_login' => 'X 用户名',
	'xconnect_bind_other_exists' => '该 X 账号已绑定到其他论坛账号。',
	'xconnect_bind_success' => 'X 账号绑定成功。',
];

$templatelang['xconnect'] = $scriptlang['xconnect'];

