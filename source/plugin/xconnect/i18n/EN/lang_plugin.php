<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['xconnect'] = [
	'xconnect_enable' => 'Enable X login',
	'xconnect_enable_desc' => 'Hide the X button on login and register pages when disabled.',
	'xconnect_clientid_desc' => 'Client ID from your X OAuth App.',
	'xconnect_clientsecret_desc' => 'Client Secret from your X OAuth App.',
	'xconnect_callback_url' => 'Callback URL',
	'xconnect_callback_desc' => 'Configure this callback URL in your X OAuth App.',
	'xconnect_update_succeed' => 'X login settings updated.',
	'xconnect_login_button' => 'Sign in with X',
	'xconnect_not_configured' => 'X login is not configured yet.',
	'xconnect_account_type_missing' => 'X account type is missing. Reinstall the plugin.',
	'xconnect_state_invalid' => 'X login state validation failed. Please try again.',
	'xconnect_token_failed' => 'Unable to fetch an X access token.',
	'xconnect_user_failed' => 'Unable to load the X user profile.',
	'xconnect_resolve_title' => 'Choose how to continue with X',
	'xconnect_resolve_message' => 'The X username matches an existing forum username. Choose whether to bind X to an existing account or create a new forum account.',
	'xconnect_resolve_bind' => 'Bind an existing account',
	'xconnect_resolve_create' => 'Create a new account',
	'xconnect_resolve_expired' => 'Your X login session has expired. Please try again.',
	'xconnect_resolve_login' => 'X username',
	'xconnect_bind_other_exists' => 'This X account is already linked to another forum account.',
	'xconnect_bind_success' => 'X account linked successfully.',
];

$templatelang['xconnect'] = $scriptlang['xconnect'];
