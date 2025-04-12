<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      Google Connect Login
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$op = !empty($_GET['op']) ? $_GET['op'] : '';
if(!in_array($op, array('init', 'callback', 'change'))) {
	showmessage('undefined_action');
}

$referer = dreferer();

require_once DISCUZ_ROOT.'/source/plugin/googleconnect/lib/GoogleOAuth.php';

try {
	$googleOAuthClient = new Cloud_Service_Client_GoogleOAuth();
} catch(Exception $e) {
	showmessage('googleconnect:connect_app_invalid');
}

if($op == 'init') {

	$callback = $_G['connect']['callback_url'] . '&referer=' . urlencode($_GET['referer']);

	try {
		dsetcookie('google_request_uri', $callback);
		$redirect = $googleOAuthClient->getOAuthAuthorizeURL($callback);
		if(defined('IN_MOBILE') || $_GET['oauth_style'] == 'mobile') {
			$redirect .= '&display=mobile';
		}
	} catch(Exception $e) {
		showmessage('googleconnect:connect_get_request_token_failed_code', $referer, array('codeMessage' => $e->getMessage()));
	}

	dheader('Location:' . $redirect);

} elseif($op == 'callback') {

	$params = $_GET;

	if($_GET['state'] != md5(FORMHASH)) {
		showmessage('googleconnect:connect_get_access_token_failed', $referer);
	}
	try {
		$response = $googleOAuthClient->getAccessToken($_GET['code']);
	} catch(Exception $e) {
		showmessage('googleconnect:connect_get_access_token_failed_code', $referer, array('codeMessage' => $e->getMessage()));
	}

	$googleToken = $response['access_token'];
	$googleId = $response['id'];
	if(!$googleToken || !$googleId) {
		showmessage('googleconnect:connect_get_access_token_failed', $referer);
	}

	// Additional logic for handling Google login...

} elseif($op == 'change') {
	$callback = $_G['connect']['callback_url'] . '&referer=' . urlencode($_GET['referer']);

	try {
		dsetcookie('google_request_uri', $callback);
		$redirect = $googleOAuthClient->getOAuthAuthorizeURL($callback);
		if(defined('IN_MOBILE') || $_GET['oauth_style'] == 'mobile') {
			$redirect .= '&display=mobile';
		}
	} catch(Exception $e) {
		showmessage('googleconnect:connect_get_request_token_failed_code', $referer, array('codeMessage' => $e->getMessage()));
	}

	dheader('Location:' . $redirect);
}