<?php

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

require_once libfile('function/cache');
C::t('common_syscache')->delete_syscache(['setting', 'secqaa']);
updatecache(['setting', 'secqaa']);
savecache('secqaa', [
	1 => [
		'qid' => 1,
		'question' => '1+1=?',
		'answer' => md5('2'),
	],
]);

$cached = C::t('common_syscache')->fetch_all_syscache(['setting', 'secqaa'], true);
if(!empty($cached['setting']['secqaa']['allowcode'])
	|| empty($cached['setting']['secqaa']['allowqa'])
	|| empty($cached['secqaa'])) {
	throw new RuntimeException('Unable to initialize deterministic security question');
}
