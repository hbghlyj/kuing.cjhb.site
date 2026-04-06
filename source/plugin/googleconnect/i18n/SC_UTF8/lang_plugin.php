<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['googleconnect'] = [
	'googleconnect_resolve_title' => '选择如何继续使用 Google',
	'googleconnect_resolve_message' => '该 Google 账号名称与现有论坛用户名重复，但邮箱并未匹配到现有论坛账号。请选择绑定现有账号，或创建一个新的论坛账号。',
	'googleconnect_resolve_bind' => '绑定现有账号',
	'googleconnect_resolve_create' => '创建新账号',
	'googleconnect_resolve_expired' => 'Google 登录会话已过期，请重试。',
	'googleconnect_resolve_username' => 'Google 显示名称',
	'googleconnect_resolve_email' => 'Google 邮箱',
	'googleconnect_bind_success' => 'Google 账号绑定成功。',
];

$templatelang['googleconnect'] = $scriptlang['googleconnect'];
