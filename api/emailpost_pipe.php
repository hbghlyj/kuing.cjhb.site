<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 *
 * postfix pipe(8) / alias delivery target for email posting.
 * Reads a single raw message from stdin and imports it as a forum post.
 */

if(PHP_SAPI !== 'cli') {
	exit;
}

require_once dirname(__DIR__).'/source/class/class_core.php';
$discuz = C::app();
$discuz->init();

require_once DISCUZ_ROOT.'source/class/class_emailpost.php';

$recipient = getenv('ORIGINAL_RECIPIENT') ?: (getenv('RECIPIENT') ?: getenv('EXTENSION'));
$raw = stream_get_contents(STDIN);
if($raw !== false && $raw !== '') {
	emailpost::importRaw($raw, (string)$recipient);
}
