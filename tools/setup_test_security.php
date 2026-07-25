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
set_exception_handler(function(Throwable $exception) {
	fwrite(STDERR, sprintf(
		"%s: %s in %s:%d\n",
		get_class($exception),
		$exception->getMessage(),
		$exception->getFile(),
		$exception->getLine()
	));
	exit(1);
});

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
C::t('common_setting')->update('regname', 'register');
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
updatecache(['setting', 'secqaa', 'usergroups', 'smilies_js']);

test_security_setup_stage('cache read');
$cached = C::t('common_syscache')->fetch_all_syscache(['setting', 'secqaa', 'usergroup_1', 'usergroup_7', 'usergroup_10'], true);
test_security_setup_stage('cache validation');
$setting = (array)($cached['setting'] ?? []);
$secqaaSetting = (array)($setting['secqaa'] ?? []);
$seccodeData = (array)($setting['seccodedata'] ?? []);
$seccodeRules = (array)($seccodeData['rule'] ?? []);
$profileGroup = (array)($setting['profilegroup'] ?? []);
$profileInfo = (array)($profileGroup['info'] ?? []);
$profileFields = (array)($profileInfo['field'] ?? []);
$secqaaCache = (array)($cached['secqaa'] ?? []);
$failures = [];
$expect = function($condition, $label) use (&$failures) {
	if(!$condition) {
		$failures[] = $label;
	}
};
$expect(empty($secqaaSetting['allowcode']), 'secqaa.allowcode');
$expect(!empty($secqaaSetting['allowqa']), 'secqaa.allowqa');
$expect(!array_diff(['register', 'post', 'login'], (array)($secqaaSetting['statuses'] ?? [])), 'secqaa.statuses');
foreach(['register', 'post', 'login'] as $rule) {
	$ruleSetting = (array)($seccodeRules[$rule] ?? []);
	$expect((int)($ruleSetting['allow'] ?? 0) === 1, 'seccodedata.'.$rule);
}
$expect(($setting['regname'] ?? '') === 'register', 'regname');
$expect((int)($setting['regstatus'] ?? 0) === 1, 'regstatus');
$expect(empty($setting['regclose']), 'regclose');
$expect((int)($setting['regverify'] ?? -1) === 0, 'regverify');
$expect(($setting['jspath'] ?? '') === 'static/js/', 'jspath');
$expect((int)($setting['floodctrl'] ?? -1) === 0, 'floodctrl');
$expect((int)($setting['pmstatus'] ?? 0) === 1, 'pmstatus');
$expect((int)($setting['commentnumber'] ?? 0) === 5, 'commentnumber');
$expect(is_array($setting['allowpostcomment'] ?? null)
	&& in_array(1, $setting['allowpostcomment'], true), 'allowpostcomment');
$expect(!empty($setting['commentfirstpost']), 'commentfirstpost');
$expect(!empty($setting['commentpostself']), 'commentpostself');
$expect(!empty($profileInfo['available']), 'profilegroup.info.available');
$expect(in_array('sightml', $profileFields, true), 'profilegroup.info.sightml');
$expect(in_array('customstatus', $profileFields, true), 'profilegroup.info.customstatus');
$expect(count($secqaaCache) === 9, 'secqaa cache count');
$expect(!array_filter($secqaaCache, fn($question) => (((array)$question)['answer'] ?? '') !== md5('2')), 'secqaa answers');
foreach([1, 7, 10] as $groupId) {
	$group = $cached['usergroup_'.$groupId] ?? [];
	$expect(!empty($group['allowcstatus']), "usergroup_{$groupId}.allowcstatus");
	$expect(!empty($group['disablepostctrl']), "usergroup_{$groupId}.disablepostctrl");
	$expect(!empty($group['allowrecommend']), "usergroup_{$groupId}.allowrecommend");
	$expect(!empty($group['allowpostattach']), "usergroup_{$groupId}.allowpostattach");
	$expect(!empty($group['allowpostimage']), "usergroup_{$groupId}.allowpostimage");
	$expect(!empty($group['allowposttag']), "usergroup_{$groupId}.allowposttag");
	$expect((int)($group['allowcommentpost'] ?? 0) === 3, "usergroup_{$groupId}.allowcommentpost");
	$expect(($group['attachextensions'] ?? '') === 'gif, jpg, png, txt, svg', "usergroup_{$groupId}.attachextensions");
	$expect((int)($group['maxsigsize'] ?? 0) === 500, "usergroup_{$groupId}.maxsigsize");
}
$expect(is_file(DISCUZ_ROOT.'./data/cache/common_smilies_var.js'), 'common_smilies_var.js');
if($failures) {
	fwrite(STDERR, 'Test security setup validation failed: '.implode(', ', $failures).".\n");
	throw new RuntimeException('Unable to initialize deterministic test security settings');
}

$setupComplete = true;
