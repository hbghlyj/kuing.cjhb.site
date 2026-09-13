<?php

class XOAuth {
	public const AUTHORIZE_URL = 'https://x.com/i/oauth2/authorize';
	public const TOKEN_URL = 'https://api.x.com/2/oauth2/token';
	public const USER_URL = 'https://api.x.com/2/users/me';
	public const SCOPE = 'tweet.read users.read';

	public static function base64UrlEncode($value) {
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	public static function createFlow() {
		$verifier = self::base64UrlEncode(random_bytes(64));
		return [
			'state' => self::base64UrlEncode(random_bytes(32)),
			'verifier' => $verifier,
			'challenge' => self::base64UrlEncode(hash('sha256', $verifier, true)),
		];
	}

	public static function authorizationUrl($clientId, $redirectUri, array $flow) {
		return self::AUTHORIZE_URL.'?'.http_build_query([
			'response_type' => 'code',
			'client_id' => $clientId,
			'redirect_uri' => $redirectUri,
			'scope' => self::SCOPE,
			'state' => $flow['state'],
			'code_challenge' => $flow['challenge'],
			'code_challenge_method' => 'S256',
		]);
	}

	public static function tokenBody($code, $redirectUri, $verifier) {
		return [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirectUri,
			'code_verifier' => $verifier,
		];
	}

	public static function authorizationHeader($clientId, $clientSecret) {
		return 'Authorization: Basic '.base64_encode($clientId.':'.$clientSecret);
	}

	public static function userFromResponse(array $response) {
		return !empty($response['data']) && is_array($response['data']) ? $response['data'] : [];
	}
}
