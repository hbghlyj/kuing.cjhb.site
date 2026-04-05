<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$op = in_array($_GET['op'], ['add', 'list', 'detail', 'search', 'manage', 'set']) ? $_GET['op'] : '';

if(!$op || in_array($op, ['list', 'detail'])) {
	require_once libfile('misc/tag', 'module');
	return;
}elseif(in_array($_GET['op'], ['add', 'search', 'manage', 'set'])) {
	require_once libfile('forum/tag', 'module');
	return;
}
