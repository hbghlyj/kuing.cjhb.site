<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['reusername'] = [
	'reusername' => '修改用戶名',
	'newusername' => '新用戶名',
	'newusername_comment' => '請輸入要修改的新用戶名。',
	'password' => '當前密碼',
	'password_comment' => '請輸入當前帳號密碼以確認修改。',
	'left_time' => '剩餘可修改次數',
	'no_permission' => '您所在的用戶組無權使用本功能。',
	'current_credit' => '當前{credititem}',
	'deduct_credit' => '本次修改將扣除 {creditnum}{creditunit}{credititem}',
	'change_limit' => '用戶名最多只能修改 {change_limit} 次。',
	'change_time' => '請在 {change_time} 小時後再嘗試修改用戶名。',
	'newusername_empty' => '新用戶名不能為空。',
	'newusername_same' => '新用戶名與當前用戶名相同。',
	'newusername_exist' => '新用戶名已存在。',
	'newusername_tooshort' => '新用戶名長度不能少於 {length} 個字符。',
	'newusername_toolong' => '新用戶名長度不能超過 {length} 個字符。',
	'password_error' => '密碼輸入錯誤。',
	'credit_notenough' => '{credititem}不足，無法修改用戶名。',
	'rename_succeed' => '用戶名修改成功。',
	'oldusername' => '原用戶名',
	'oldusername_empty' => '原用戶名不能為空。',
	'oldusername_noexist' => '原用戶名不存在。',
	'record_list' => '修改記錄',
	'record_delete_confirm' => '確定要刪除這條記錄嗎？',
	'record_deletesucceed' => '記錄刪除成功。',
	'record_nonexistence' => '記錄不存在。',
	'record_updatesucceed' => '記錄更新成功。',
	'admincp_updatesucceed' => '設置更新成功。',
];

$templatelang['reusername'] = $scriptlang['reusername'];
