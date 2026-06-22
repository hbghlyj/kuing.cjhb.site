<?php

error_reporting(E_ERROR | E_PARSE);

const IN_DISCUZ = true;
define('DISCUZ_ROOT', dirname(__DIR__).'/');
const DISCUZ_DATA = DISCUZ_ROOT.'data/';
const DISCUZ_LANG = 'SC_UTF8/';
const CHARSET = 'UTF-8';
const MITFRAME_APP = '';
const HOOKTYPE = 'hookscript';

$_G = [
	'groupid' => 0,
	'setting' => [
		'plugins' => [
			'func' => [
				HOOKTYPE => [
					'discuzcode' => false,
				],
			],
		],
	],
	'cache' => [
		'bbcodes_display' => [],
		'smilies' => [
			'searcharray' => [],
		],
	],
];

class i18n {
	public static function cmd($cmd, $langkey = '', $path = '') {
		return '';
	}

	public static function getLang($file) {
		return [];
	}
}

require_once DISCUZ_ROOT.'source/function/function_core.php';

class table_common_syscache {
	public static function t() {
		return new self();
	}

	public function fetch_all_syscache($cachenames, $force = false) {
		global $_G;
		$data = [];
		foreach((array)$cachenames as $name) {
			$data[$name] = $_G['cache'][$name] ?? [];
		}
		return $data;
	}
}

require_once DISCUZ_ROOT.'source/function/function_post.php';
require_once DISCUZ_ROOT.'source/function/function_search.php';

function fail($message, $context = []) {
	echo json_encode([
		'ok' => false,
		'message' => $message,
		'context' => $context,
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
	exit(1);
}

function pass($context = []) {
	echo json_encode([
		'ok' => true,
		'context' => $context,
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
	exit(0);
}

function assert_no_raw_script_tag($value, $label) {
	if(preg_match('/<\s*script\b/i', $value)) {
		fail($label.' contains a raw script tag', [$label => $value]);
	}
}

function assert_no_raw_math_angle($value, $label) {
	if(str_contains($value, 'r<|z-a|<R')) {
		fail($label.' contains raw math angle brackets', [$label => $value]);
	}
}

$thread = [
	'readperm' => 0,
	'price' => 0,
];
$rawUserMessage = 'normal post text <script>alert(1)</script> tail';

// This mirrors the normal post model path that stores thread/message snippets.
$storedMessage = dhtmlspecialchars(messagecutstr($rawUserMessage, 150, null, 0));
if(!str_contains($storedMessage, '&lt;script&gt;alert(1)&lt;/script&gt;')) {
	fail('stored normal post message is not HTML-escaped as expected', [
		'raw' => $rawUserMessage,
		'stored' => $storedMessage,
	]);
}
assert_no_raw_script_tag($storedMessage, 'storedMessage');

// This mirrors source/app/search/module/forum.php before template/default/search/thread_list.htm outputs $thread[message].
$searchMessage = bat_highlight(search_message_safestr(threadmessagecutstr($thread, $storedMessage, 200)), 'normal');
assert_no_raw_script_tag($searchMessage, 'searchMessage');
if(!str_contains($searchMessage, '&lt;script&gt;alert(1)&lt;/script&gt;')) {
	fail('search result message no longer contains escaped user input', [
		'stored' => $storedMessage,
		'search' => $searchMessage,
	]);
}

$legacyRawMessage = 'Let A={z: r<|z-a|<R} and continue.';
$legacySearchMessage = bat_highlight(search_message_safestr(threadmessagecutstr($thread, $legacyRawMessage, 200)), 'Let');
assert_no_raw_math_angle($legacySearchMessage, 'legacySearchMessage');
if(!str_contains($legacySearchMessage, 'r&lt;|z-a|&lt;R')) {
	fail('legacy raw message angle brackets were not escaped for search output', [
		'raw' => $legacyRawMessage,
		'search' => $legacySearchMessage,
	]);
}

$alreadyEscapedMessage = 'Let A={z: r&lt;|z-a|&lt;R} and continue.';
$escapedSearchMessage = search_message_safestr(threadmessagecutstr($thread, $alreadyEscapedMessage, 200));
if(str_contains($escapedSearchMessage, '&amp;lt;')) {
	fail('already escaped message was double-escaped for search output', [
		'stored' => $alreadyEscapedMessage,
		'search' => $escapedSearchMessage,
	]);
}

pass([
	'stored' => $storedMessage,
	'search' => $searchMessage,
	'legacy_search' => $legacySearchMessage,
	'already_escaped_search' => $escapedSearchMessage,
]);
