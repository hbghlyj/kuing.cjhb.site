<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: misc_secqaa.php 33682 2013-08-01 06:37:41Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$idhash = isset($_GET['idhash']) && preg_match('/^\w+$/', $_GET['idhash']) ? $_GET['idhash'] : '';

if($_GET['action'] == 'update' && !defined("IN_MOBILE")) {

	$refererhost = parse_url($_SERVER['HTTP_REFERER']);
	$refererhost['host'] .= !empty($refererhost['port']) ? (':'.$refererhost['port']) : '';

	if($refererhost['host'] != $_SERVER['HTTP_HOST']) {
		exit('Access Denied');
	}

	$message = '';
	$showid = 'secqaa_'.$idhash;
	if($_G['setting']['secqaa']) {
		$question = make_secqaa();
	}

	$message = preg_replace("/\r|\n/", '', $question);
	$message = str_replace("'", "\'", $message);
	$seclang = lang('forum/misc');
	header("Content-Type: application/javascript");
echo <<<EOF
if($('$showid')) {
	var sectpl = seccheck_tpl['$idhash'] != '' ? seccheck_tpl['$idhash'].replace(/<hash>/g, 'code$idhash') : '';
	var sectplcode = sectpl != '' ? sectpl.split('<sec>') : Array('<br />',': ','<br />','');
	var string = '<input name="secqaahash" type="hidden" value="$idhash" />' + sectplcode[0] + '{$seclang['secqaa']}' + sectplcode[1] + '$message' +
		sectplcode[2] + '<input name="secanswer" id="secqaaverify_$idhash" type="text" autocomplete="off" style="{$imemode}width:100px" class="txt px vm" onblur="checksec(\'qaa\', \'$idhash\')" pattern="\\\\d*" />' +
		' <a href="javascript:;" onclick="updatesecqaa(\'$idhash\');doane(event);" class="xi2">{$seclang['seccode_update']}</a>' +
		'<span id="checksecqaaverify_$idhash"><img src="' + STATICURL + 'image/common/none.gif" width="16" height="16" class="vm" /></span>' + sectplcode[3];
	evalscript(string);
	$('$showid').innerHTML = string;
}
EOF;

} elseif(getgpc('action') == 'update' && defined("IN_MOBILE") && constant("IN_MOBILE") == 2) {
	$refererhost = parse_url($_SERVER['HTTP_REFERER']);
	$refererhost['host'] .= !empty($refererhost['port']) ? (':'.$refererhost['port']) : '';

	if($refererhost['host'] != $_SERVER['HTTP_HOST']) {
		exit('Access Denied');
	}

	$message = '';
	$showid = 'secqaa_'.$idhash;
	if($_G['setting']['secqaa']) {
		$question = make_secqaa();
	}

	$message = preg_replace("/\r|\n/", '', $question);
	$message = str_replace("'", "\'", $message);
	$seclang = lang('forum/misc');
	header("Content-Type: application/javascript");
echo <<<EOF
if(document.getElementById('$showid')) {
	if(!document.getElementById('v$showid')) {
		var sectpl = seccheck_tpl['$idhash'] != '' && typeof seccheck_tpl['$idhash'] != 'undefined' ? seccheck_tpl['$idhash'].replace(/<hash>/g, 'code$idhash') : '';
		var sectplcode = sectpl != '' ? sectpl.split('<sec>') : Array('<br />',': ','','');
		var string = '<input name="secqaahash" type="hidden" value="$idhash" /><input type="text" class="txt px vm" style="ime-mode:disabled;width:115px;background:white;" autocomplete="off" value="" name="secanswer" id="secqaaverify_$idhash" placeholder="$seclang[secqaa]" /><span id="v$showid"><a href="javascript:;" onclick="updatesecqaa(\'$idhash\');" class="xi2">' + '$message' + 
			'</a></span>' +
			'<span id="checksecqaaverify_$idhash"></span>';
		evalscript(string);
		document.getElementById('$showid').innerHTML = string;
	} else {
		var string = '<a href="javascript:;" onclick="updatesecqaa(\'$idhash\');" class="xi2">' + '$message' + 
			'</a>';
		evalscript(string);
		document.getElementById('v$showid').innerHTML = string;
	}
}
EOF;

} elseif($_GET['action'] == 'check') {

	include template('common/header_ajax');
	echo helper_seccheck::check_secqaa($_GET['secverify'], $idhash, true) ? 'succeed' : 'invalid';
	include template('common/footer_ajax');

}

?>