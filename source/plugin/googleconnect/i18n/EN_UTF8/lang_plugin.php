<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['googleconnect'] = [
	'googleconnect_resolve_title' => 'Choose how to continue with Google',
	'googleconnect_resolve_message' => 'The Google account name matches an existing forum username, but the email address does not match an existing forum account. Choose whether to bind Google to an existing account or create a new forum account.',
	'googleconnect_resolve_bind' => 'Bind an existing account',
	'googleconnect_resolve_create' => 'Create a new account',
	'googleconnect_resolve_expired' => 'Your Google login session has expired. Please try again.',
	'googleconnect_resolve_username' => 'Google display name',
	'googleconnect_resolve_email' => 'Google email',
	'googleconnect_bind_success' => 'Google account linked successfully.',
];

$templatelang['googleconnect'] = $scriptlang['googleconnect'];
