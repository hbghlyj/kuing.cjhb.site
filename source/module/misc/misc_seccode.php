<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: misc_seccode.php 34646 2014-06-17 03:23:15Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$idhash = isset($_GET['idhash']) && preg_match('/^\w+$/', $_GET['idhash']) ? $_GET['idhash'] : '';
$modid = isset($_GET['modid']) && preg_match('/^[\w:]+$/', $_GET['modid']) ? $_GET['modid'] : '';

if(getgpc('action') == 'update' && !defined("IN_MOBILE")) {

	$message = '';
	$showid = 'seccode_'.$idhash;
	$rand = random(5, 1);
	$htmlcode = '';
	$ani = $_G['setting']['seccodedata']['animator'] ? '_ani' : '';
if($_G['setting']['seccodedata']['type'] == 3) {
		$htmlcode = "(function(){var a=document.createElement('audio');a.src='{$_G['siteurl']}misc.php?mod=seccode&update=$rand&idhash=$idhash&fromFlash=1',a.style.display='none',$('seccodeplay_$idhash').onclick=function(){a.play()},$('seccodeswf_$idhash').appendChild(a)})();";
		$message = '<span id="seccodeswf_'.$idhash.'">'.lang('core', 'seccode_sound_tips').'</span>'.lang('forum/misc', 'seccode_player', array('idhash' => $idhash));
	} else {
		if(!is_numeric($_G['setting']['seccodedata']['type']) && preg_match('/^[\w\d:_]+$/i', $_G['setting']['seccodedata']['type'])) {
			$etype = explode(':', $_G['setting']['seccodedata']['type']);
			if(count($etype) > 1) {
				$codefile = DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/seccode/seccode_'.$etype[1].'.php';
				$class = $etype[1];
			} else {
				$codefile = libfile('seccode/'.$_G['setting']['seccodedata']['type'], 'class');
				$class = $_G['setting']['seccodedata']['type'];
			}
			if(file_exists($codefile)) {
				@include_once $codefile;
				$class = 'seccode_'.$class;
				if(class_exists($class)) {
					$code = new $class();
					if(method_exists($code, 'make')) {
						ob_start();
						$seccode = $code->make($idhash, $modid);
						make_seccode($seccode);
						$message = preg_replace("/\r|\n/", '', ob_get_contents());
						ob_end_clean();
					}
				}
			}
		} else {
			$message = lang('core', 'seccode_image'.$ani.'_tips').'<img onclick="updateseccode(\''.$idhash.'\')" width="'.$_G['setting']['seccodedata']['width'].'" height="'.$_G['setting']['seccodedata']['height'].'" src="misc.php?mod=seccode&update='.$rand.'&idhash='.$idhash.'" class="vm" alt="" />';
		}
	}
	$imemode = $_G['setting']['seccodedata']['type'] != 1 ? 'ime-mode:disabled;' : '';
	$message = str_replace("'", "\'", $message);
	$seclang = lang('forum/misc');
	header("Content-Type: application/javascript");
echo <<<EOF
if($('$showid')) {
	if(!$('v$showid')) {
		var sectpl = seccheck_tpl['$idhash'] != '' ? seccheck_tpl['$idhash'].replace(/<hash>/g, 'code$idhash') : '';
		var sectplcode = sectpl != '' ? sectpl.split('<sec>') : Array('<br />',': ','<br />','');
		var string = '<input name="seccodehash" type="hidden" value="$idhash" /><input name="seccodemodid" type="hidden" value="$modid" />' + sectplcode[0] + '{$seclang['seccode']}' + sectplcode[1] + '<input name="seccodeverify" id="seccodeverify_$idhash" type="text" autocomplete="off" style="{$imemode}width:100px" class="txt px vm" onblur="checksec(\'code\', \'$idhash\', 0, null, \'$modid\')" />' +
			' <a href="javascript:;" onclick="updateseccode(\'$idhash\');doane(event);" class="xi2">{$seclang['seccode_update']}</a>' +
			'<span id="checkseccodeverify_$idhash"><i class="fico-checkbox fic4 fc-t fnmr vm"></i></span>' +
			sectplcode[2] + '<span id="v$showid">$message</span>' + sectplcode[3];
		evalscript(string);
		$('$showid').innerHTML = string;
	} else {
		var string = '$message';
		evalscript(string);
		$('v$showid').innerHTML = string;
	}
	$htmlcode
}
EOF;

} elseif(getgpc('action') == 'update' && defined("IN_MOBILE") && constant("IN_MOBILE") == 2) {
	//移动端和PC端验证码功能拉齐
	$message = '';
	$showid = 'seccode_'.$idhash;
	$rand = random(5, 1);
	$htmlcode = '';
	$ani = $_G['setting']['seccodedata']['animator'] ? '_ani' : '';
if($_G['setting']['seccodedata']['type'] == 3) {
		$message = '<img onclick="updateseccode(\''.$idhash.'\')" width="'.$_G['setting']['seccodedata']['width'].'" height="'.$_G['setting']['seccodedata']['height'].'" src="misc.php?mod=seccode&mobile='.constant("IN_MOBILE").'&update='.$rand.'&idhash='.$idhash.'" class="seccodeimg" alt="" style="display: inline; visibility: visible;" />';
	} else {
		if(!is_numeric($_G['setting']['seccodedata']['type']) && preg_match('/^[\w\d:_]+$/i', $_G['setting']['seccodedata']['type'])) {
			$etype = explode(':', $_G['setting']['seccodedata']['type']);
			if(count($etype) > 1) {
				$codefile = DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/seccode/seccode_'.$etype[1].'.php';
				$class = $etype[1];
			} else {
				$codefile = libfile('seccode/'.$_G['setting']['seccodedata']['type'], 'class');
				$class = $_G['setting']['seccodedata']['type'];
			}
			if(file_exists($codefile)) {
				@include_once $codefile;
				$class = 'seccode_'.$class;
				if(class_exists($class)) {
					$code = new $class();
					if(method_exists($code, 'make')) {
						ob_start();
						$seccode = $code->make($idhash, $modid);
						make_seccode($seccode);
						$message = preg_replace("/\r|\n/", '', ob_get_contents());
						ob_end_clean();
					}
				}
			}
		} else {
			$message = '<img onclick="updateseccode(\''.$idhash.'\')" width="'.$_G['setting']['seccodedata']['width'].'" height="'.$_G['setting']['seccodedata']['height'].'" src="misc.php?mod=seccode&mobile='.constant("IN_MOBILE").'&update='.$rand.'&idhash='.$idhash.'" class="seccodeimg" alt="" style="display: inline; visibility: visible;" />';
		}
	}
	$imemode = $_G['setting']['seccodedata']['type'] != 1 ? 'ime-mode:disabled;' : '';
	$message = str_replace("'", "\'", $message);
	$seclang = lang('forum/misc');
	header("Content-Type: application/javascript");
echo <<<EOF
if(document.getElementById('$showid')) {
	if(!document.getElementById('v$showid')) {
		var sectpl = seccheck_tpl['$idhash'] != '' ? seccheck_tpl['$idhash'].replace(/<hash>/g, 'code$idhash') : '';
		var sectplcode = sectpl != '' ? sectpl.split('<sec>') : Array('<br />',': ','<br />','');
		var string = '<input name="seccodehash" type="hidden" value="$idhash" /><input name="seccodemodid" type="hidden" value="$modid" /><input type="text" class="txt px vm" style="ime-mode:disabled;width:115px;background:white;" autocomplete="off" value="" id="seccodeverify_$idhash" name="seccodeverify" placeholder="$seclang[seccode]" fwin="seccode"><span id="v$showid">$message</span>';
		evalscript(string);
		document.getElementById('$showid').innerHTML = string;
	} else {
		var string = '$message';
		evalscript(string);
		document.getElementById('v$showid').innerHTML = string;
	}
	$htmlcode
}
EOF;

} elseif(getgpc('action') == 'check') {

	include template('common/header_ajax');
	echo helper_seccheck::check_seccode($_GET['secverify'], $_GET['idhash'], 1, $modid, true) ? 'succeed' : 'invalid';
	include template('common/footer_ajax');

} else {

	$refererhost = parse_url(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
	$refererhost['host'] = (isset($refererhost['host']) ? $refererhost['host'] : '').(!empty($refererhost['port']) ? (':'.$refererhost['port']) : '');

	if(($_G['setting']['seccodedata']['type'] < 2 && ($refererhost['host'] != $_SERVER['HTTP_HOST'])) || ((defined("IN_MOBILE") && in_array($_G['setting']['seccodedata']['type'], array(2, 3)) && ($refererhost['host'] != $_SERVER['HTTP_HOST'])) && ($_G['setting']['seccodedata']['type'] == 2 && !extension_loaded('ming') && $_POST['fromFlash'] != 1 || $_G['setting']['seccodedata']['type'] == 3 && $_GET['fromFlash'] != 1))) {
		//当模式为英文=0、中文=1两种验证码时，校验Referer和Host是否为同一站点，不是就退出
		//当模式为Flash=2、语音=3两种验证码时，要么是手机版且Referer和Host为同一站点（不是就退出，X3.5新增验证码降级功能），
		//要么是Flash+Ming+FromFlash，要么是语音验证码+FromFlash（不是就退出）
		//这个逻辑浪费了我二三十分钟，写上注释以免后人入坑
		exit('Access Denied');
	}

	if(is_numeric($_G['setting']['seccodedata']['type']) || !preg_match('/^[\w\d:_]+$/i', $_G['setting']['seccodedata']['type'])) {

		$seccode = make_seccode();

		if(!$_G['setting']['nocacheheaders']) {
			@header("Expires: -1");
			@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
			@header("Pragma: no-cache");
		}

		require_once libfile('class/seccode');

		$code = new seccode();
		$code->code = $seccode;
		$code->type = (in_array($_G['setting']['seccodedata']['type'], array(2, 3)) && defined("IN_MOBILE")) ? 0 : $_G['setting']['seccodedata']['type'];
		$code->width = $_G['setting']['seccodedata']['width'];
		$code->height = $_G['setting']['seccodedata']['height'];
		$code->background = $_G['setting']['seccodedata']['background'];
		$code->adulterate = $_G['setting']['seccodedata']['adulterate'];
		$code->ttf = $_G['setting']['seccodedata']['ttf'];
		$code->angle = $_G['setting']['seccodedata']['angle'];
		$code->warping = $_G['setting']['seccodedata']['warping'];
		$code->scatter = $_G['setting']['seccodedata']['scatter'];
		$code->color = $_G['setting']['seccodedata']['color'];
		$code->size = $_G['setting']['seccodedata']['size'];
		$code->shadow = $_G['setting']['seccodedata']['shadow'];
		$code->animator = $_G['setting']['seccodedata']['animator'];
		$code->fontpath = DISCUZ_ROOT.'./static/image/seccode/font/';
		$code->datapath = DISCUZ_ROOT.'./static/image/seccode/';
		$code->includepath = DISCUZ_ROOT.'./source/class/';

		$code->display();

	} else {
		$etype = explode(':', $_G['setting']['seccodedata']['type']);
		if(count($etype) > 1) {
			$codefile = DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/seccode/seccode_'.$etype[1].'.php';
			$class = $etype[1];
		} else {
			$codefile = libfile('seccode/'.$_G['setting']['seccodedata']['type'], 'class');
			$class = $_G['setting']['seccodedata']['type'];
		}
		if(file_exists($codefile)) {
			@include_once $codefile;
			$class = 'seccode_'.$class;
			if(class_exists($class)) {
				make_seccode();
				$code = new $class();
				$image = $code->image($idhash, $modid);
				if($image) {
					dheader('location: '.$image);
				}
			}
		}
	}

}

?>