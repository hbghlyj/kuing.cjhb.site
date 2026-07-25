<?php

$options = getopt('', ['url:']);
$siteUrl = $options['url'] ?? '';
$parsedUrl = parse_url($siteUrl);
if(!is_array($parsedUrl) || !in_array($parsedUrl['scheme'] ?? '', ['http', 'https'], true) || empty($parsedUrl['host'])) {
	exit("Usage: php tools/setup_test_security.php --url=http://example.com\n");
}

$root = dirname(__DIR__);
$host = $parsedUrl['host'].(isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : '');
$_SERVER['HTTP_HOST'] = $host;
$_SERVER['SERVER_NAME'] = $parsedUrl['host'];
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['SERVER_PORT'] = (string)($parsedUrl['port'] ?? ($parsedUrl['scheme'] === 'https' ? 443 : 80));
if($parsedUrl['scheme'] === 'https') {
	$_SERVER['HTTPS'] = 'on';
	$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
}

require './source/class/class_core.php';

$discuz = C::app();
$discuz->init();

DB::query('TRUNCATE TABLE '.DB::table('common_secquestion'));
C::t('common_secquestion')->insert([
	'type' => 0,
	'question' => '1+1=?',
	'answer' => '2',
]);

$secqaa = [
	'status' => 3,
	'minposts' => 0,
	'statuses' => ['register', 'post', 'login'],
	'allowcode' => 0,
	'allowqa' => 1,
];
C::t('common_setting')->update('secqaa', $secqaa);
C::t('common_setting')->update('seccodestatus', '0');
C::t('common_setting')->update('floodctrl', '0');
C::t('common_setting')->update('commentnumber', '5');
C::t('common_setting')->update('allowpostcomment', [1]);
C::t('common_setting')->update('commentfirstpost', '1');
C::t('common_setting')->update('commentpostself', '1');
foreach([1, 7, 10] as $groupId) {
	C::t('common_usergroup_field')->update($groupId, [
		'disablepostctrl' => '1',
		'allowcommentpost' => '3',
	]);
}

require_once libfile('function/cache');
C::t('common_syscache')->delete_syscache(['setting', 'secqaa']);
updatecache(['setting', 'secqaa', 'usergroups']);

$cached = C::t('common_syscache')->fetch_all_syscache(['setting', 'secqaa', 'usergroup_1', 'usergroup_7', 'usergroup_10'], true);
if(!empty($cached['setting']['secqaa']['allowcode'])
	|| empty($cached['setting']['secqaa']['allowqa'])
	|| (int)$cached['setting']['floodctrl'] !== 0
	|| (int)$cached['setting']['commentnumber'] !== 5
	|| !is_array($cached['setting']['allowpostcomment'])
	|| !in_array(1, $cached['setting']['allowpostcomment'])
	|| empty($cached['setting']['commentfirstpost'])
	|| empty($cached['setting']['commentpostself'])
	|| count($cached['secqaa']) !== 9
	|| count(array_filter($cached['secqaa'], fn($question) => ($question['answer'] ?? '') !== md5('2')))
	|| count(array_filter([1, 7, 10], fn($groupId) =>
		empty($cached['usergroup_'.$groupId]['disablepostctrl'])
		|| (int)$cached['usergroup_'.$groupId]['allowcommentpost'] !== 3
	))) {
	throw new RuntimeException('Unable to initialize deterministic test security settings');
}
