<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['reusername'] = [
	'reusername' => '修改用户名',
	'newusername' => '新用户名',
	'newusername_comment' => '请输入要修改的新用户名。',
	'password' => '当前密码',
	'password_comment' => '请输入当前账号密码以确认修改。',
	'left_time' => '剩余可修改次数',
	'no_permission' => '您所在的用户组无权使用本功能。',
	'current_credit' => '当前{credititem}',
	'deduct_credit' => '本次修改将扣除 {creditnum}{creditunit}{credititem}',
	'change_limit' => '用户名最多只能修改 {change_limit} 次。',
	'change_time' => '请在 {change_time} 小时后再尝试修改用户名。',
	'newusername_empty' => '新用户名不能为空。',
	'newusername_same' => '新用户名与当前用户名相同。',
	'newusername_exist' => '新用户名已存在。',
	'newusername_tooshort' => '新用户名长度不能少于 {length} 个字符。',
	'newusername_toolong' => '新用户名长度不能超过 {length} 个字符。',
	'password_error' => '密码输入错误。',
	'credit_notenough' => '{credititem}不足，无法修改用户名。',
	'rename_succeed' => '用户名修改成功。',
	'oldusername' => '原用户名',
	'oldusername_empty' => '原用户名不能为空。',
	'oldusername_noexist' => '原用户名不存在。',
	'record_list' => '修改记录',
	'record_delete_confirm' => '确定要删除这条记录吗？',
	'record_deletesucceed' => '记录删除成功。',
	'record_nonexistence' => '记录不存在。',
	'record_updatesucceed' => '记录更新成功。',
	'admincp_updatesucceed' => '设置更新成功。',
];

$templatelang['reusername'] = $scriptlang['reusername'];
