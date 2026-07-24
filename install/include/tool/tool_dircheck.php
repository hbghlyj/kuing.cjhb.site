<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('RUN_MODE') || RUN_MODE != 'tool') {
	show_msg('method_undefined', $method, 0);
}

if(!empty($_GET['getExport']) && preg_match('/^\w+$/', $_GET['getExport'])) {
	$f = sys_get_temp_dir().'/'.$_GET['getExport'];
	$export = file_get_contents($f);
	header('Content-type: application/octet-stream');
	header('Accept-Ranges: bytes');
	header('Content-Length: '.strlen($export));
	header('Content-Disposition: attachment; filename=filecheck.txt');
	echo "\n".$export;
	@unlink($f);
	exit;
}

$entryarray = [
	'data',
	'data/attachment',
	'data/attachment/album',
	'data/attachment/category',
	'data/attachment/common',
	'data/attachment/forum',
	'data/attachment/group',
	'data/attachment/portal',
	'data/attachment/profile',
	'data/attachment/temp',
	'data/cache',
	'data/log',
	'data/template',
	'data/threadcache',
	'data/diy'
];

$result = '';
foreach($entryarray as $entry) {
	$fullentry = ROOT_PATH.'./'.$entry;
	if(!is_dir($fullentry) && !file_exists($fullentry)) {
		continue;
	} else {
		if(!dir_writeable($fullentry)) {
			show_msg('tool_dircheck_unwritable', $entry, 0);
		}
	}
}

show_header();
echo '</div><div class="main">';
echo '<div class="box">';
show_tips('tool_dircheck_result_noerror');
echo '</div>
	<div class="btnbox">
		<em>'.lang('tool_tips').'</em>
		<div class="inputbox">
		<input type="button" name="oldbtn" value="'.lang('old_step').'" class="btn oldbtn" onclick="location.href=\'?\'">
		<input type="button" value="'.lang('done').'" class="btn" onclick="location.href=\'?method=done\'">
        </div></div>';
show_footer();
