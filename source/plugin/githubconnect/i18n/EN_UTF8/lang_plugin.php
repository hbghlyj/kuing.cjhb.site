<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['githubconnect'] = [
	'githubconnect_enable' => 'Enable GitHub login',
	'githubconnect_enable_desc' => 'Hide the GitHub button on login and register pages when disabled.',
	'githubconnect_clientid_desc' => 'Client ID from your GitHub OAuth App.',
	'githubconnect_clientsecret_desc' => 'Client Secret from your GitHub OAuth App.',
	'githubconnect_callback_url' => 'Callback URL',
	'githubconnect_callback_desc' => 'Configure this callback URL in your GitHub OAuth App.',
	'githubconnect_update_succeed' => 'GitHub login settings updated.',
	'githubconnect_login_button' => 'Continue with GitHub',
	'githubconnect_not_configured' => 'GitHub login is not configured yet.',
	'githubconnect_account_type_missing' => 'GitHub account type is missing. Reinstall the plugin.',
	'githubconnect_state_invalid' => 'GitHub login state validation failed. Please try again.',
	'githubconnect_token_failed' => 'Unable to fetch a GitHub access token.',
	'githubconnect_user_failed' => 'Unable to load the GitHub user profile.',
	'githubconnect_resolve_title' => 'Choose how to continue with GitHub',
	'githubconnect_resolve_message' => 'The GitHub username matches an existing forum username, but the email address does not match an existing forum account. Choose whether to bind GitHub to an existing account or create a new forum account.',
	'githubconnect_resolve_bind' => 'Bind an existing account',
	'githubconnect_resolve_create' => 'Create a new account',
	'githubconnect_resolve_expired' => 'Your GitHub login session has expired. Please try again.',
	'githubconnect_resolve_login' => 'GitHub username',
	'githubconnect_resolve_email' => 'GitHub email',
	'githubconnect_bind_other_exists' => 'This GitHub account is already linked to another forum account.',
	'githubconnect_bind_success' => 'GitHub account linked successfully.',
];

$templatelang['githubconnect'] = $scriptlang['githubconnect'];
