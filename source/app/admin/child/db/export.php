<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$backupPath = DISCUZ_ROOT.'./data/'.$backupdir.'/';
$backupUrl = 'data/'.$backupdir.'/';
// The cron job writes backup_<weekday>_<am|pm>.sql.gz, two slots per day, so the
// original three hardcoded names (backup_monday.sql.gz and friends) never existed
// and every row rendered as "Unavailable". Build the real scheme instead: the
// Unavailable branch still earns its keep for a slot that has not run yet.
$backupFiles = [];
foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
	foreach(['am', 'pm'] as $slot) {
		$backupFiles[] = 'backup_'.$day.'_'.$slot.'.sql.gz';
	}
}

shownav('founder', 'nav_db', 'nav_db_export');
showsubmenu('nav_db', [
	['nav_db_export', 'db&operation=export', 1],
	['nav_db_runquery', 'db&operation=runquery', 0],
	['nav_db_optimize', 'db&operation=optimize', 0],
	['nav_db_dbcheck', 'db&operation=dbcheck', 0]
]);
showtips('db_system_backup_tips');
showtableheader('db_system_backup_list');
showsubtitle(['filename', 'size', 'dateline', 'download']);

foreach($backupFiles as $filename) {
	$filepath = $backupPath.$filename;
	if(is_file($filepath) && !is_link($filepath)) {
		$url = $backupUrl.rawurlencode($filename).'?t='.filemtime($filepath);
		$size = sizecount(filesize($filepath));
		$dateline = dgmdate(filemtime($filepath));
		$download = '<a href="'.$url.'" target="_blank">'.cplang('download').'</a>';
	} else {
		$size = $dateline = $download = cplang('db_system_backup_unavailable');
	}
	showtablerow('', [], [
		dhtmlspecialchars($filename),
		$size,
		$dateline,
		$download,
	]);
}

showtablefooter();
