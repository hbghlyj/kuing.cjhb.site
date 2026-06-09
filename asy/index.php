<?php

const ASY_MAX_CODE_BYTES = 65536;
const ASY_MAX_RESPONSE_BYTES = 8 * 1024 * 1024;
const ASY_CONNECT_TIMEOUT = 3;
const ASY_REQUEST_TIMEOUT = 20;

function asyError(int $status, string $message): never {
	http_response_code($status);
	header('Content-Type: text/plain; charset=utf-8');
	header('Cache-Control: no-store');
	exit($message);
}

function asySend(string $body, string $format, string $etag): never {
	$contentTypes = [
		'svg' => 'image/svg+xml',
		'png' => 'image/png',
	];
	header('Content-Type: '.$contentTypes[$format]);
	header('Cache-Control: public, max-age=31536000, immutable');
	header('Content-Security-Policy: sandbox');
	header('X-Content-Type-Options: nosniff');
	header('ETag: "'.$etag.'"');
	if(trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '', '"') === $etag) {
		http_response_code(304);
		exit;
	}
	echo $body;
	exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
	asyError(405, 'Method not allowed');
}

$format = $_GET['format'] ?? 'svg';
if(!in_array($format, ['svg', 'png'], true)) {
	asyError(400, 'Unsupported output format');
}

$code = $_GET['code'] ?? '';
if(!is_string($code) || $code === '') {
	asyError(400, 'Missing Asymptote code');
}
if(strlen($code) > ASY_MAX_CODE_BYTES) {
	asyError(413, 'Asymptote code is too large');
}

$etag = hash('sha256', $format."\0".$code);

$response = '';
$curl = curl_init('http://asymptote.ualberta.ca:10007?f='.$format);
curl_setopt_array($curl, [
	CURLOPT_POST => true,
	CURLOPT_RETURNTRANSFER => false,
	CURLOPT_HTTPHEADER => ['Content-Type: text/plain'],
	CURLOPT_POSTFIELDS => $code,
	CURLOPT_CONNECTTIMEOUT => ASY_CONNECT_TIMEOUT,
	CURLOPT_TIMEOUT => ASY_REQUEST_TIMEOUT,
	CURLOPT_FOLLOWLOCATION => false,
	CURLOPT_WRITEFUNCTION => static function($curl, string $chunk) use (&$response) {
		if(strlen($response) + strlen($chunk) > ASY_MAX_RESPONSE_BYTES) {
			return 0;
		}
		$response .= $chunk;
		return strlen($chunk);
	},
]);
$success = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$error = curl_errno($curl);
curl_close($curl);

if(!$success || $error || $status !== 200 || $response === '') {
	asyError($error === CURLE_OPERATION_TIMEDOUT ? 504 : 502, 'Asymptote renderer unavailable');
}

asySend($response, $format, $etag);
