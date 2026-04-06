<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['reusername'] = [
	'reusername' => 'Rename Username',
	'newusername' => 'New Username',
	'newusername_comment' => 'Enter the new username you want to use.',
	'password' => 'Current Password',
	'password_comment' => 'Enter your current password to confirm the rename.',
	'left_time' => 'Remaining rename chances',
	'no_permission' => 'Your user group is not allowed to use this feature.',
	'current_credit' => 'Current {credititem}',
	'deduct_credit' => 'This change will cost {creditnum}{creditunit} {credititem}',
	'change_limit' => 'You can rename your username at most {change_limit} times.',
	'change_time' => 'Please wait {change_time} hours before trying again.',
	'newusername_empty' => 'The new username cannot be empty.',
	'newusername_same' => 'The new username is the same as the current username.',
	'newusername_exist' => 'The new username already exists.',
	'newusername_tooshort' => 'The new username must be at least {length} characters long.',
	'newusername_toolong' => 'The new username cannot be longer than {length} characters.',
	'password_error' => 'The password is incorrect.',
	'credit_notenough' => 'Not enough {credititem} to rename the username.',
	'rename_succeed' => 'Username renamed successfully.',
	'oldusername' => 'Old Username',
	'oldusername_empty' => 'The old username cannot be empty.',
	'oldusername_noexist' => 'The old username does not exist.',
	'record_list' => 'Rename Records',
	'record_delete_confirm' => 'Delete this record?',
	'record_deletesucceed' => 'Record deleted successfully.',
	'record_nonexistence' => 'The record does not exist.',
	'record_updatesucceed' => 'Record updated successfully.',
	'admincp_updatesucceed' => 'Settings updated successfully.',
];

$templatelang['reusername'] = $scriptlang['reusername'];
