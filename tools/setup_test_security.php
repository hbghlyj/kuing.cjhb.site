<?php

if(PHP_SAPI !== 'cli') {
	exit("This tool must be run from the command line.\n");
}

function test_security_setup_stage($stage = null) {
	static $current = 'bootstrap';
	if($stage !== null) {
		$current = $stage;
	}
	return $current;
}

$setupComplete = false;
register_shutdown_function(function() use (&$setupComplete) {
	if(!$setupComplete) {
		fwrite(STDERR, 'Test security setup terminated during '.test_security_setup_stage().".\n");
		exit(1);
	}
});

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

test_security_setup_stage('application initialization');
$discuz = C::app();
$discuz->init();

test_security_setup_stage('security settings');
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
$seccodedata = C::t('common_setting')->fetch_setting('seccodedata', true);
foreach(['register', 'post', 'login'] as $rule) {
	$seccodedata['rule'][$rule]['allow'] = 1;
}
C::t('common_setting')->update('secqaa', $secqaa);
C::t('common_setting')->update('seccodedata', $seccodedata);
C::t('common_setting')->update('seccodestatus', '0');
C::t('common_setting')->update('regname', '');
C::t('common_setting')->update('regstatus', '1');
C::t('common_setting')->update('regclose', '0');
C::t('common_setting')->update('regverify', '0');
C::t('common_setting')->update('jspath', 'static/js/');
C::t('common_setting')->update('floodctrl', '0');
C::t('common_setting')->update('pmstatus', '1');
C::t('common_setting')->update('commentnumber', '5');
C::t('common_setting')->update('allowpostcomment', [1]);
C::t('common_setting')->update('commentfirstpost', '1');
C::t('common_setting')->update('commentpostself', '1');
C::t('common_setting')->update('recommendthread', [
	'status' => '1',
	'addtext' => 'Recommend',
	'subtracttext' => 'Oppose',
	'defaultshow' => '1',
	'daycount' => '5',
	'ownthread' => '1',
	'allow' => '1',
]);
C::t('common_setting')->update('repliesrank', '1');
C::t('common_setting')->update('profilegroup', [
	'info' => [
		'title' => 'Personal Info',
		'available' => 1,
		'displayorder' => 0,
		'field' => [
			'sightml' => 'sightml',
			'customstatus' => 'customstatus',
		],
	],
]);
foreach([1, 7, 10] as $groupId) {
	C::t('common_usergroup_field')->update($groupId, [
		'disablepostctrl' => '1',
		'allowcstatus' => '1',
		'allowrecommend' => '1',
		'allowpostattach' => '1',
		'allowpostimage' => '1',
		'allowposttag' => '1',
		'allowcommentpost' => '3',
		'attachextensions' => 'gif, jpg, png, txt, svg',
		'maxsigsize' => '500',
	]);
}

test_security_setup_stage('seed thread');
require_once libfile('function/forum');
if(!C::t('forum_thread')->exists_by_subject('Admin Seed Thread')) {
	$adminTid = C::t('forum_thread')->insert([
		'fid' => 2,
		'author' => 'admin',
		'authorid' => 1,
		'subject' => 'Admin Seed Thread',
		'dateline' => TIMESTAMP,
		'lastpost' => TIMESTAMP,
		'lastposter' => 'admin',
		'displayorder' => 0,
		'views' => 1,
		'replies' => 1,
		'status' => 32,
		'maxposition' => 2,
	], true);
	insertpost([
		'fid' => 2,
		'tid' => $adminTid,
		'first' => 1,
		'author' => 'admin',
		'authorid' => 1,
		'subject' => 'Admin Seed Thread',
		'dateline' => TIMESTAMP,
		'message' => 'Admin Seed Thread Message Content',
		'invisible' => 0,
		'anonymous' => 0,
		'usesig' => 1,
		'htmlon' => 0,
		'bbcodeoff' => 0,
		'smileyoff' => -1,
		'parseurloff' => 0,
		'attachment' => 0,
		'bestanswer' => 0,
	]);
	insertpost([
		'fid' => 2,
		'tid' => $adminTid,
		'first' => 0,
		'author' => 'admin',
		'authorid' => 1,
		'subject' => 'Admin Seed Reply',
		'dateline' => TIMESTAMP,
		'message' => 'Admin Seed Reply Message Content',
		'invisible' => 0,
		'anonymous' => 0,
		'usesig' => 1,
		'htmlon' => 0,
		'bbcodeoff' => 0,
		'smileyoff' => -1,
		'parseurloff' => 0,
		'attachment' => 0,
		'bestanswer' => 0,
	]);
}

test_security_setup_stage('cache rebuild');
require_once libfile('function/cache');
C::t('common_syscache')->delete_syscache(['setting', 'secqaa']);
updatecache(['setting', 'secqaa', 'usergroups']);

test_security_setup_stage('cache validation');
$cached = C::t('common_syscache')->fetch_all_syscache(['setting', 'secqaa', 'usergroup_1', 'usergroup_7', 'usergroup_10'], true);
if(!empty($cached['setting']['secqaa']['allowcode'])
	|| empty($cached['setting']['secqaa']['allowqa'])
	|| count(array_diff(['register', 'post', 'login'], $cached['setting']['secqaa']['statuses'] ?? []))
	|| count(array_filter(['register', 'post', 'login'], fn($rule) =>
		(int)($cached['setting']['seccodedata']['rule'][$rule]['allow'] ?? 0) !== 1
	))
	|| (int)$cached['setting']['regstatus'] !== 1
	|| !empty($cached['setting']['regclose'])
	|| (int)$cached['setting']['regverify'] !== 0
	|| $cached['setting']['jspath'] !== 'static/js/'
	|| (int)$cached['setting']['floodctrl'] !== 0
	|| (int)$cached['setting']['pmstatus'] !== 1
	|| (int)$cached['setting']['commentnumber'] !== 5
	|| !is_array($cached['setting']['allowpostcomment'])
	|| !in_array(1, $cached['setting']['allowpostcomment'])
	|| empty($cached['setting']['commentfirstpost'])
	|| empty($cached['setting']['commentpostself'])
	|| empty($cached['setting']['profilegroup']['info']['available'])
	|| !in_array('sightml', $cached['setting']['profilegroup']['info']['field'] ?? [], true)
	|| !in_array('customstatus', $cached['setting']['profilegroup']['info']['field'] ?? [], true)
	|| count($cached['secqaa']) !== 9
	|| count(array_filter($cached['secqaa'], fn($question) => ($question['answer'] ?? '') !== md5('2')))
	|| count(array_filter([1, 7, 10], fn($groupId) =>
		empty($cached['usergroup_'.$groupId]['allowcstatus'])
		|| empty($cached['usergroup_'.$groupId]['disablepostctrl'])
		|| empty($cached['usergroup_'.$groupId]['allowrecommend'])
		|| empty($cached['usergroup_'.$groupId]['allowpostattach'])
		|| empty($cached['usergroup_'.$groupId]['allowpostimage'])
		|| empty($cached['usergroup_'.$groupId]['allowposttag'])
		|| (int)$cached['usergroup_'.$groupId]['allowcommentpost'] !== 3
		|| $cached['usergroup_'.$groupId]['attachextensions'] !== 'gif, jpg, png, txt, svg'
		|| (int)$cached['usergroup_'.$groupId]['maxsigsize'] !== 500
	))) {
	throw new RuntimeException('Unable to initialize deterministic test security settings');
}

$setupComplete = true;
