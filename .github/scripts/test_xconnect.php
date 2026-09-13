<?php

require_once dirname(__DIR__, 2).'/source/plugin/xconnect/lib/XOAuth.php';

$flow = XOAuth::createFlow();
assert(strlen($flow['state']) >= 43);
assert(strlen($flow['verifier']) >= 43 && strlen($flow['verifier']) <= 128);
assert($flow['challenge'] === XOAuth::base64UrlEncode(hash('sha256', $flow['verifier'], true)));

$url = XOAuth::authorizationUrl('client-id', 'https://example.com/callback', $flow);
parse_str(parse_url($url, PHP_URL_QUERY), $query);
assert(parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).parse_url($url, PHP_URL_PATH) === XOAuth::AUTHORIZE_URL);
assert($query === [
	'response_type' => 'code',
	'client_id' => 'client-id',
	'redirect_uri' => 'https://example.com/callback',
	'scope' => 'tweet.read users.read',
	'state' => $flow['state'],
	'code_challenge' => $flow['challenge'],
	'code_challenge_method' => 'S256',
]);

assert(XOAuth::tokenBody('code', 'https://example.com/callback', 'verifier') === [
	'grant_type' => 'authorization_code',
	'code' => 'code',
	'redirect_uri' => 'https://example.com/callback',
	'code_verifier' => 'verifier',
]);
assert(XOAuth::authorizationHeader('client-id', 'client-secret') === 'Authorization: Basic '.base64_encode('client-id:client-secret'));

$user = XOAuth::userFromResponse(['data' => [
	'id' => '2244994945',
	'name' => 'X Developers',
	'username' => 'XDevelopers',
]]);
assert($user['id'] === '2244994945');
assert($user['username'] === 'XDevelopers');
assert(XOAuth::userFromResponse(['errors' => []]) === []);

echo "X OAuth tests passed.\n";
