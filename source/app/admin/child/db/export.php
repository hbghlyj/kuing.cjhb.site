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
$backupFiles = [
	'backup_monday.sql.gz',
	'backup_wednesday.sql.gz',
	'backup_friday.sql.gz',
];

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
		$url = $backupUrl.rawurlencode($filename);
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
