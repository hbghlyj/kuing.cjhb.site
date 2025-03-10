<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_styles.php 36353 2017-01-17 07:19:28Z nemohou $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!empty($_GET['preview'])) {
	loadcache('style_'.$_GET['styleid']);
	$_G['style'] = $_G['cache']['style_'.$_GET['styleid']];
	include template('common/preview', $_G['style']['templateid'], $_G['style']['tpldir']);
	exit;
}

require_once libfile('function/cloudaddons');

$scrolltop = $_GET['scrolltop'];
$anchor = $_GET['anchor'];
$namenew = $_GET['namenew'];
$defaultnew = $_GET['defaultnew'];
$newname = $_GET['newname'];
$id = $_GET['id'];
$isplugindeveloper = isset($_G['config']['plugindeveloper']) && $_G['config']['plugindeveloper'] > 0;

$operation = empty($operation) ? 'admin' : $operation;

if($operation == 'export' && $id) {
	$stylearray = C::t('common_style')->fetch_by_styleid($id);
	if(!$stylearray) {
		cpheader();
		cpmsg('styles_export_invalid', '', 'error');
	}

	$addonid = '';
	if(preg_match('/^.?\/template\/([a-z]+[a-z0-9_]*)$/', $stylearray['directory'], $a) && $a[1] != 'default') {
		$addonid = $a[1].'.template';
	}

	if(($isplugindeveloper && $isfounder) || !$addonid || !cloudaddons_getmd5($addonid)) {
		if (ispluginkey(basename($stylearray['directory']))) {
			cpheader();
			cloudaddons_validator(basename($stylearray['directory']).'.template');
		}
		foreach(C::t('common_stylevar')->fetch_all_by_styleid($id) as $style) {
			$stylearray['style'][$style['variable']] = $style['substitute'];
		}

		$stylearray['version'] = strip_tags($_G['setting']['version']);
		exportdata('Discuz! Style', basename($stylearray['directory']), $stylearray);
	} else {
		cpheader();
		cpmsg('styles_export_invalid', '', 'error');
	}
}

cpheader();

$predefinedvars = array('available' => array(), 'boardimg' => array(), 'searchimg' => array(), 'touchimg' => array(), 'imgdir' => array(), 'styleimgdir' => array(), 'stypeid' => array(),
	'headerbgcolor' => array(0, $lang['styles_edit_type_bg']),
	'bgcolor' => array(0),
	'sidebgcolor' => array(0, '', '#FFF sidebg.gif repeat-y 100% 0'),
	'titlebgcolor' => array(0),

	'headerborder' => array(1, $lang['styles_edit_type_header'], '1px'),
	'headertext' => array(0),
	'footertext' => array(0),

	'font' => array(1, $lang['styles_edit_type_font']),
	'fontsize' => array(1),
	'threadtitlefont' => array(1, $lang['styles_edit_type_thread_title']),
	'threadtitlefontsize' => array(1),
	'smfont' => array(1),
	'smfontsize' => array(1),
	'tabletext' => array(0),
	'midtext' => array(0),
	'lighttext' => array(0),

	'link' => array(0, $lang['styles_edit_type_url']),
	'highlightlink' => array(0),
	'lightlink' => array(0),

	'wrapbg' => array(0),
	'wrapbordercolor' => array(0),

	'msgfontsize' => array(1, $lang['styles_edit_type_post'], '14px'),
	'contentwidth' => array(1),
	'contentseparate' => array(0),

	'menubgcolor' => array(0, $lang['styles_edit_type_menu']),
	'menutext' => array(0),
	'menucurbgcolor' => array(0),
	'menuhoverbgcolor' => array(0),
	'menuhovertext' => array(0),

	'inputborder' => array(0, $lang['styles_edit_type_input']),
	'inputborderdarkcolor' => array(0),
	'inputbg' => array(0, '', '#FFF'),

	'dropmenuborder' => array(0, $lang['styles_edit_type_dropmenu']),
	'dropmenubgcolor' => array(0),

	'floatbgcolor' => array(0, $lang['styles_edit_type_float']),
	'floatmaskbgcolor' => array(0),

	'commonborder' => array(0, $lang['styles_edit_type_other']),
	'commonbg' => array(0),
	'specialborder' => array(0),
	'specialbg' => array(0),
	'noticetext' => array(0),
);

if($operation == 'admin') {

	$sarray = $tpldirs = $addonids = array();
	foreach(C::t('common_style')->fetch_all_data(true) as $row) {
		if(preg_match('/^.?\/template\/([a-z]+[a-z0-9_]*)$/', $row['directory'], $a) && $a[1] != 'default') {
			$addonids[$row['styleid']] = $a[1].'.template';
		}
		$sarray[$row['styleid']] = $row;
		$tpldirs[] = realpath($row['directory']);
	}

	$defaultid = C::t('common_setting')->fetch_setting('styleid');
	$defaultid1 = C::t('common_setting')->fetch_setting('styleid1');
	$defaultid2 = C::t('common_setting')->fetch_setting('styleid2');
	$defaultid3 = C::t('common_setting')->fetch_setting('styleid3');

	if(!submitcheck('stylesubmit')) {
		$narray = array();
		$dir = DISCUZ_ROOT.'./template/';
		$templatedir = dir($dir);$i = -1;
		while($entry = $templatedir->read()) {
			$tpldir = realpath($dir.'/'.$entry);
			if(!in_array($entry, array('.', '..')) && !in_array($tpldir, $tpldirs) && is_dir($tpldir)) {
				$styleexist = 0;
				$searchdir = dir($tpldir);
				while($searchentry = $searchdir->read()) {
					if(substr($searchentry, 0, 13) == 'discuz_style_' && fileext($searchentry) == 'xml') {
						$styleexist++;
					}
				}
				if($styleexist) {
					$narray[$i] = array(
						'styleid' => '',
						'available' => '',
						'directory' => './template/'.$entry,
						'name' => $entry,
						'tplname' => $entry,
						'filemtime' => @filemtime($dir.'/'.$entry),
						'stylecount' => $styleexist
					);
					$i--;
				}
			}
		}

		uasort($narray, 'filemtimesort');
		$recommendaddon = dunserialize($_G['setting']['cloudaddons_recommendaddon']);
		if(empty($recommendaddon['updatetime']) || abs($_G['timestamp'] - $recommendaddon['updatetime']) > 7200 || (isset($_GET['checknew']) && $_G['formhash'] == $_GET['formhash'])) {
			$recommendaddon = json_decode(cloudaddons_recommendaddon($addonids), true);
			if(empty($recommendaddon) || !is_array($recommendaddon)){
				$recommendaddon = array();
			}
			$recommendaddon['updatetime'] = $_G['timestamp'];
			C::t('common_setting')->update('cloudaddons_recommendaddon', $recommendaddon);
			updatecache('setting');
		}
		if(!empty($recommendaddon['templates']) && is_array($recommendaddon['templates'])){
			$count = 0;
			foreach ($recommendaddon['templates'] as $key => $value) {
				if (!empty($value['identifier']) && !is_dir($dir.'/'.$value['identifier'])) {
					$narray[$i] = array(
						'styleid' => '',
						'available' => '',
						'name' => diconv($value['name'], 'utf-8', CHARSET),
						'directory' => './template/'.$value['identifier'],
						'tplname' => diconv($value['tplname'], 'utf-8', CHARSET),
						'filemtime' => $value['updatetime'],
						'stylecount' => $value['stylecount'],
						'down' => 1,
					);
					$i--;
					$count++;
					if (!empty($recommendaddon['templateshownum']) && $count >= $recommendaddon['templateshownum']) {
						break;
					}
				}
			}
		}
		$sarray += $narray;

		$stylelist = '';
		$i = 0;
		$updatestring = array();
		foreach($sarray as $id => $style) {
			$style['name'] = dhtmlspecialchars($style['name']);
			$isdefault = $id == $defaultid || $id == 1 ? 'checked' : '';
			$isdefault1 = $id == $defaultid1 ? 'checked' : '';
			$isdefault2 = $id == $defaultid2 ? 'checked' : '';
			$isdefault3 = $id == $defaultid3 ? 'checked' : '';
			$d2exists = file_exists($style['directory'].'/touch');
			$available = $style['available'] ? 'checked' : NULL;
			$identifier = end(explode('/', $style['directory']));
			$preview = file_exists($style['directory'].'/preview.jpg') ? $style['directory'].'/preview.jpg' : cloudaddons_pluginlogo_url($identifier, 'template');
			$previewlarge = file_exists($style['directory'].'/preview_large.jpg') ? $style['directory'].'/preview_large.jpg' : cloudaddons_pluginlogo_url($identifier, 'template');
			$styleicons = isset($styleicons[$id]) ? $styleicons[$id] : '';
			if($addonids[$style['styleid']]) {
				if(!isset($updatestring[$addonids[$style['styleid']]])) {
					$updatestring[$addonids[$style['styleid']]] = "<p id=\"update_".$addonids[$style['styleid']]."\"></p>";
				} else {
					$updatestring[$addonids[$style['styleid']]] = '';
				}
			}
			$stylelist .=
				'<table cellspacing="0" cellpadding="0" style="margin-left: 10px; width: 250px;height: 200px;" class="left"><tr><th class="partition" colspan="2">'.($addonids[$id] ? "<a href=\"".ADMINSCRIPT."?action=cloudaddons&frame=no&id=".$identifier.".template\" target=\"_blank\" title=\"$lang[cloudaddons_linkto]\">$style[tplname]</a>" : $style['tplname']).'</th></tr><tr><td style="width: 130px;height:150px" valign="top">'.
				($id > 0 ? "<p style=\"margin-bottom: 12px;\"><img width=\"110\" height=\"120\" ".($previewlarge ? 'style="cursor:pointer" title="'.$lang['preview_large'].'" onclick="zoom(this, \''.$previewlarge.'\', 1)" ' : '')."src=\"$preview\" alt=\"{$lang['preview']}\" onerror=\"this.onerror=null;this.src='./static/image/admincp/stylepreview.gif'\"/></p>
				<p style=\"margin: 2px 0\"><input type=\"text\" class=\"txt\" name=\"namenew[$id]\" value=\"{$style['name']}\" style=\"margin:0; width: 104px;\"></p></td><td valign=\"top\">
				<p> {$lang['styles_default']}</p>
				<p style=\"margin: 1px 0\"><label><input type=\"radio\" class=\"radio\" name=\"defaultnew\" value=\"$id\" $isdefault /> {$lang['styles_default0']}</label></p>
				".($d2exists ? "<p style=\"margin: 1px 0\"><label><input type=\"radio\" class=\"radio\" name=\"defaultnew2\" value=\"$id\" $isdefault2 /> {$lang['styles_default2']}</label></p>" : "<p style=\"margin: 1px 0\" class=\"lightfont\"><label><input type=\"radio\" class=\"radio\" disabled readonly /> {$lang['styles_default2']}</label></p>")."
				<p style=\"margin: 8px 0 0 0\"><label>".($isdefault || $isdefault1 || $isdefault2 || $isdefault3 ? '<input class="checkbox" type="checkbox" disabled="disabled" />' : '<input class="checkbox" type="checkbox" name="delete[]" value="'.$id.'" />')." {$lang['styles_uninstall']}</label></p>
				<p style=\"margin: 8px 0 2px 0\"><a href=\"".ADMINSCRIPT."?action=styles&operation=edit&id=$id\">{$lang['edit']}</a> &nbsp;".
					(($isplugindeveloper && $isfounder) || !$addonids[$style['styleid']] || !cloudaddons_getmd5($addonids[$style['styleid']]) ? " <a href=\"".ADMINSCRIPT."?action=styles&operation=export&id=$id\">{$lang['export']}</a><br />" : '<br />').
					"<a href=\"".ADMINSCRIPT."?action=styles&operation=copy&id=$id\">{$lang['copy']}</a> &nbsp; <a href=\"".ADMINSCRIPT."?action=styles&operation=import&dir=".$identifier."&restore=$id\">{$lang['restore']}</a>
					".($isfounder && $addonids[$id] ? " &nbsp; <a href=\"".ADMINSCRIPT."?action=cloudaddons&frame=no&id=".$identifier.".template&from=comment\" target=\"_blank\" title=\"{$lang['cloudaddons_linkto']}\">{$lang['plugins_visit']}</a>" : '')."
				</p>"
				:
				"<img width=\"110\" height=\"120\" src=\"$preview\" ".($previewlarge ? 'style="cursor:pointer" title="'.$lang['preview_large'].'" onclick="zoom(this, \''.$previewlarge.'\', 1)" ' : '')." onerror=\"this.onerror=null;this.src='./static/image/admincp/stylepreview.gif'\" /></td><td valign=\"top\">
				<p style=\"margin: 2px 0\"><a href=\"".ADMINSCRIPT."?action=styles&operation=import&dir=$identifier\">{$lang['styles_install']}</a></p>".
				($style['stylecount'] > 0 ? "<p style=\"margin: 2px 0\">{$lang['styles_stylecount']}: {$style['stylecount']}</p>" : '').
				($style['filemtime'] > $timestamp - 86400 ? '<p style=\"margin-bottom: 2px;\"><font color="red">New!</font></p>' : '')).
				"</td></tr><tr><td colspan=\"2\">".$updatestring[$addonids[$style['styleid']]]."</td></tr></table>\n".($i == 3 ? '</tr>' : '');
			$i++;
			if($i == 3) {
				$i = 0;
			}
		}
		if($i > 0) {
			$stylelist .= str_repeat('<td></td>', 3 - $i);
		}

		if(empty($_G['cookie']['addoncheck_template'])) {
			$checkresult = dunserialize(cloudaddons_upgradecheck($addonids));
			savecache('addoncheck_template', $checkresult);
			dsetcookie('addoncheck_template', 1, 3600);
		} else {
			loadcache('addoncheck_template');
			$checkresult = $_G['cache']['addoncheck_template'];
		}

		$updatecount = 0;
		$newvers = '';
		foreach($checkresult as $addonid => $value) {
			list($return, $newver) = explode(':', $value);
			if($newver) {
				$updatecount++;
				$newvers .= "if($('update_$addonid')) $('update_$addonid').innerHTML=' <a href=\"".ADMINSCRIPT."?action=cloudaddons&frame=no&id=$addonid&from=newver\" target=\"_blank\"><font color=\"red\">".cplang('styles_find_newversion')." $newver</font></a>';";
			}
		}

		shownav('template', 'styles_list');
		showsubmenu('styles_admin', array(
			array('styles_list', 'styles', 1),
			array('styles_import', 'styles&operation=import', 0),
			$isfounder ? array('plugins_validator'.($updatecount ? '_new' : ''), 'styles&operation=upgradecheck', 0) : array(),
			$isfounder ? array('cloudaddons_style_link', 'cloudaddons&frame=no&operation=templates&from=more', 0, 1) : array(),
		), '<a href="https://www.dismall.com/?from=templates_question" target="_blank" class="rlink">'.$lang['templates_question'].'</a>', array('updatecount' => $updatecount));
		showtips('styles_home_tips');
		showformheader('styles');
		showhiddenfields(array('updatecsscache' => 0));
		showboxheader('', 'nobottom');
		echo $stylelist;
		showboxfooter();
		showtableheader();
		showsubmit('stylesubmit', 'submit', 'del', '<input onclick="this.form.updatecsscache.value=1" type="submit" class="btn" name="stylesubmit" value="'.cplang('styles_csscache_update').'">'.($isfounder ? '&nbsp;&nbsp;<a href="'.ADMINSCRIPT.'?action=cloudaddons&frame=no&operation=templates&from=more" target="_blank">'.cplang('cloudaddons_style_link').'</a>' : ''));
		showtablefooter();
		showformfooter();
		if($newvers) {
			echo '<script type="text/javascript">'.$newvers.'</script>';
		}

	} else {

		if($_GET['updatecsscache']) {
			updatecache(array('setting', 'styles'));
			loadcache('style_default', true);
			updatecache('updatediytemplate');
			$tpl = dir(DISCUZ_ROOT.'./data/template');
			while($entry = $tpl->read()) {
				if(preg_match("/\.tpl\.php$/", $entry)) {
					@unlink(DISCUZ_ROOT.'./data/template/'.$entry);
				}
			}
			$tpl->close();
			cpmsg('csscache_update', 'action=styles', 'succeed');
		} else {
			$defaultids = array();
			$dfids = array('', '1', '2', '3');
			foreach ($dfids as $dfid) {
				$defaultnew = $_GET['defaultnew'.$dfid];
				if(is_numeric($defaultnew) && isset($sarray[$defaultnew])) {
					if (!in_array($defaultnew, $defaultids)) {
						if (basename($sarray[$defaultnew]['directory']) != 'default' && ispluginkey(basename($sarray[$defaultnew]['directory']))) {
							cpheader();
							$addonid = basename($sarray[$defaultnew]['directory']).'.template';
							$array = cloudaddons_getmd5($addonid);
							if(cloudaddons_open('&mod=app&ac=validator&ver=2&addonid='.$addonid.($array !== false ? '&rid='.$array['RevisionID'].'&sn='.$array['SN'].'&rd='.$array['RevisionDateline'] : '')) === '0') {
								cpmsg('clo'.'uda'.'ddon'.'s_gen'.'uine_'.'mes'.'sage', '', 'error', array('addonid' => $addonid));
							}
						}
						$defaultids[] = $defaultnew;
					}
					C::t('common_setting')->update_setting('styleid'.$dfid, $defaultnew);
				}
			}

			if(isset($_GET['namenew'])) {
				foreach($sarray as $id => $old) {
					$namenew[$id] = trim($_GET['namenew'][$id]);
					if($namenew[$id] != $old['name']) {
						C::t('common_style')->update($id, array('name' => $namenew[$id]));
					}
				}
			}

			$delete = $_GET['delete'];
			if(!empty($delete) && is_array($delete)) {
				$did = array();
				foreach($delete as $id) {
					$id = intval($id);
					if(in_array($id, $defaultids)) {
						cpmsg('styles_delete_invalid', '', 'error');
					} elseif($id != 1){
						$did[] = intval($id);
					}
				}
				if($did) {
					$tplids = array();
					foreach(C::t('common_style')->fetch_all_data() as $style) {
						$tplids[$style['templateid']] = $style['templateid'];
					}
					C::t('common_style')->delete($did);
					C::t('common_stylevar')->delete_by_styleid($did);
					C::t('forum_forum')->update_styleid($did);
					foreach(C::t('common_style')->fetch_all_data() as $style) {
						unset($tplids[$style['templateid']]);
					}
					if($tplids) {
						foreach(C::t('common_template')->fetch_all($tplids) as $tpl) {
							cloudaddons_uninstall(basename($tpl['directory']).'.template', $tpl['directory']);
						}
						C::t('common_template')->delete_tpl($tplids);
					}
				}
			}

			if($_GET['newname']) {
				$styleidnew = C::t('common_style')->insert(array('name' => $_GET['newname'], 'templateid' => 1), true);
				foreach(array_keys($predefinedvars) as $variable) {
					$substitute = isset($predefinedvars[$variable][2]) ? $predefinedvars[$variable][2] : '';
					C::t('common_stylevar')->insert(array('styleid' => $styleidnew, 'variable' => $variable, 'substitute' => $substitute));
				}
			}

			updatecache(array('setting', 'styles'));
			loadcache('style_default', true);
			updatecache('updatediytemplate');
			cpmsg('styles_edit_succeed', 'action=styles', 'succeed');
		}

	}

} elseif($operation == 'import') {
	$_GET['dir'] = isset($_GET['dir']) ? preg_replace('#([^\w]+)#is', '', $_GET['dir']) : '';
	if(!submitcheck('importsubmit') && empty($_GET['dir'])) {

		shownav('template', 'styles_import');
		showsubmenu('styles_admin', array(
			array('styles_list', 'styles', 0),
			array('styles_import', 'styles&operation=import', 1),
			$isfounder ? array('cloudaddons_style_link', 'cloudaddons&frame=no&operation=templates&from=more', 0, 1) : array(),
		), '<a href="https://www.dismall.com/?from=templates_question" target="_blank" class="rlink">'.$lang['templates_question'].'</a>');
		showformheader('styles&operation=import', 'enctype');
		showtableheader('styles_import');
		showimportdata();
		showtablerow('', 'colspan="2"', '<input class="checkbox" type="checkbox" name="ignoreversion" id="ignoreversion" value="1" /><label for="ignoreversion"> '.cplang('styles_import_ignore_version').'</label>');
		showsubmit('importsubmit');
		showtablefooter();
		showformfooter();

	} else {
		if (!is_dir(DISCUZ_ROOT.'./template/'.$_GET['dir'])) {
			echo '<script type="text/javascript">top.location.href=\''.ADMINSCRIPT.'?action=cloudaddons&frame=no&id='.$_GET['dir'].'.template&from=recommendaddon\';</script>';
			exit;
		}
		require_once libfile('function/importdata');
		$restore = !empty($_GET['restore']) ? $_GET['restore'] : 0;
		if($restore) {
			$style = C::t('common_style')->fetch_by_styleid($restore);
			$_GET['dir'] = $style['directory'];
		}
		if(!empty($_GET['dir'])) {
			$renamed = import_styles($_GET['ignoreversion'], $_GET['dir'], $restore);
		} else {
			$renamed = import_styles($_GET['ignoreversion'], $_GET['dir']);
		}

		dsetcookie('addoncheck_template', '', -1);
		cpmsg(!empty($_GET['dir']) ? (!$restore ? 'styles_install_succeed' : 'styles_restore_succeed') : ($renamed ? 'styles_import_succeed_renamed' : 'styles_import_succeed'), 'action=styles', 'succeed');
	}

} elseif($operation == 'copy') {

	$style = C::t('common_style')->fetch_by_styleid($id);
	if (ispluginkey(basename($style['directory']))) {
		cloudaddons_validator(basename($style['directory']).'.template');
	}
	$style['name'] .= '_'.random(4);
	$styleidnew = C::t('common_style')->insert(array('name' => $style['name'], 'available' => $style['available'], 'templateid' => $style['templateid']), true);

	foreach(C::t('common_stylevar')->fetch_all_by_styleid($id) as $stylevar) {
		C::t('common_stylevar')->insert(array('styleid' => $styleidnew, 'variable' => $stylevar['variable'], 'substitute' => $stylevar['substitute']));
	}

	updatecache(array('setting', 'styles'));
	cpmsg('styles_copy_succeed', 'action=styles', 'succeed');

} elseif($operation == 'edit') {

	if(!submitcheck('editsubmit')) {

		if(empty($id)) {
			$stylelist = "<select name=\"id\" style=\"width: 150px\">\n";
			foreach(C::t('common_style')->fetch_all_data() as $style) {
				$stylelist .= "<option value=\"{$style['styleid']}\">{$style['name']}</option>\n";
			}
			$stylelist .= '</select>';
			$highlight = getgpc('highlight');
			$highlight = !empty($highlight) ? dhtmlspecialchars($highlight, ENT_QUOTES) : '';
			cpmsg('styles_nonexistence', 'action=styles&operation=edit'.(!empty($highlight) ? "&highlight=$highlight" : ''), 'form', array(), $stylelist);
		}

		$style = C::t('common_style')->fetch_by_styleid($id);
		if(!$style) {
			cpmsg('style_not_found', '', 'error');
		}
		list($style['extstyle'], $style['defaultextstyle']) = explode('|', $style['extstyle']);
		$style['extstyle'] = explode("\t", $style['extstyle']);

		$extstyle = $defaultextstyle = array();
		if(file_exists($extstyledir = DISCUZ_ROOT.$style['directory'].'/style')) {
			$defaultextstyle[] = array('', $lang['default']);
			$tpl = dir($extstyledir);
			while($entry = $tpl->read()) {
				if($entry != '.' && $entry != '..' && file_exists($extstylefile = $extstyledir.'/'.$entry.'/style.css')) {
					$content = file_get_contents($extstylefile);
					if(preg_match('/\[name\](.+?)\[\/name\]/i', $content, $r1) && preg_match('/\[iconbgcolor](.+?)\[\/iconbgcolor]/i', $content, $r2)) {
						$extstyle[] = array($entry, '<em style="background:'.$r2[1].'">&nbsp;&nbsp;&nbsp;&nbsp;</em> '.$r1[1]);
						$defaultextstyle[] = array($entry, $r1[1]);
					}
				}
			}
			$tpl->close();
		}

		$stylecustom = '';
		$stylestuff = $existvars = $stylecustomvars = array();
		foreach(C::t('common_stylevar')->fetch_all_by_styleid($id) as $stylevar) {
			if(array_key_exists($stylevar['variable'], $predefinedvars)) {
				$stylestuff[$stylevar['variable']] = array('id' => $stylevar['stylevarid'], 'subst' => $stylevar['substitute']);
				$existvars[] = $stylevar['variable'];
			} else {
				$stylecustom .= showtablerow('', array('class="td25"', 'class="td24 bold"', 'class="td26"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"{$stylevar['stylevarid']}\">",
					'{'.strtoupper($stylevar['variable']).'}',
					"<textarea name=\"stylevar[{$stylevar['stylevarid']}]\" style=\"height: 45px\" cols=\"50\" rows=\"2\">{$stylevar['substitute']}</textarea>",
				), TRUE);
				$stylecustomvars[$stylevar['stylevarid']] = $stylevar;
			}
		}
		if($diffvars = array_diff(array_keys($predefinedvars), $existvars)) {
			foreach($diffvars as $variable) {
				$stylestuff[$variable] = array(
					'id' => C::t('common_stylevar')->insert(array('styleid' => $id, 'variable' => $variable, 'substitute' => ''), true),
					'subst' => ''
				);
			}
		}

		$tplselect = array();
		foreach(C::t('common_template')->fetch_all_data() as $template) {
			$tplselect[] = array($template['templateid'], $template['name']);
		}

		$smileytypes = array();
		foreach(C::t('forum_imagetype')->fetch_all_available() as $type) {
			$smileytypes[] = array($type['typeid'], $type['name']);
		}

		$adv = !empty($_GET['adv']) ? 1 : 0;

		shownav('template', 'styles_edit');

		showsubmenu(cplang('styles_admin').' - '.$style['name'], array(
			array('admin', 'styles', 0),
			array('edit' , 'styles&operation=edit&id='.$id, 1),
			$isfounder ? array('export', 'styles&operation=export&id='.$id, 0) : array(),
			$isplugindeveloper ? array('templates_add', 'templates&operation=add', 0) : array(),
			array('cloudaddons_style_link', 'cloudaddons&frame=no&operation=templates&from=more', 0, 1),
		));

?>
<script type="text/JavaScript">
function imgpre_onload(obj) {
	if(!obj.complete) {
		setTimeout(function() {imgpre_resize(obj)}, 100);
	}
	imgpre_resize(obj);
}
function imgpre_resize(obj) {
	if(obj.width > 350) {
		obj.style.width = '350px';
	}
}
function imgpre_update(id, obj) {
	url = obj.value;
	if(url) {
		re = /^(https?:)?\/\//i;
		var matches = re.exec(url);
		if(matches == null) {
			url = ($('styleimgdir').value ? $('styleimgdir').value : ($('imgdir').value ? $('imgdir').value : '<?php echo STATICURL; ?>image/common')) + '/' + url;
		}
		$('bgpre_' + id).style.backgroundImage = 'url(' + url + ')';
	} else {
		$('bgpre_' + id).style.backgroundImage = 'url(<?php echo STATICURL; ?>image/common/none.gif)';
	}
}
function imgpre_switch(id) {
	if($('bgpre_' + id).innerHTML == '') {
		url = $('bgpre_' + id).style.backgroundImage.substring(4, $('bgpre_' + id).style.backgroundImage.length - 1);
		$('bgpre_' + id).innerHTML = '<img onload="imgpre_onload(this)" src="' + url + '" />';
		$('bgpre_' + id).backgroundImage = $('bgpre_' + id).style.backgroundImage;
		$('bgpre_' + id).style.backgroundImage = '';
	} else {
		$('bgpre_' + id).style.backgroundImage = $('bgpre_' + id).backgroundImage;
		$('bgpre_' + id).innerHTML = '';
	}
}
</script>
<?php

		//是否有自定义配置文件
		$configflag = false;
		if(preg_match('/^.?\/template\/([a-z]+[a-z0-9_]*)$/', $style['directory'], $a)) {
			$configfile = DISCUZ_ROOT . './template/' . $a[1] . '/config.inc.php';
			if(file_exists($configfile)) {
				$configflag = true;
				include $configfile;
			}
		}

		if(!$configflag) {
			echo '<iframe class="preview" frameborder="0" src="' . ADMINSCRIPT . '?action=styles&preview=yes&styleid=' . $id . '"></iframe>';
			/*search={"styles_admin":"action=styles&operation=edit"}*/
			showtips('styles_tips');

			showformheader("styles&operation=edit&id=$id", 'enctype');
			showtableheader($lang['styles_edit'], 'nobottom');
			showsetting('styles_edit_name', 'namenew', $style['name'], 'text');
			showsetting('styles_edit_tpl', array('templateidnew', $tplselect), $style['templateid'], 'select');
			showsetting('styles_edit_extstyle', array('extstylenew', $extstyle), $style['extstyle'], 'mcheckbox');
			if($extstyle) {
				showsetting('styles_edit_defaultextstyle', array('defaultextstylenew', $defaultextstyle), $style['defaultextstyle'], 'select');
			}
			showsetting('styles_edit_smileytype', array("stylevar[{$stylestuff['stypeid']['id']}]", $smileytypes), $stylestuff['stypeid']['subst'], 'select');
			showsetting('styles_edit_imgdir', '', '', '<input type="text" class="txt" name="stylevar['.$stylestuff['imgdir']['id'].']" id="imgdir" value="'.$stylestuff['imgdir']['subst'].'" />');
			showsetting('styles_edit_styleimgdir', '', '', '<input type="text" class="txt" name="stylevar['.$stylestuff['styleimgdir']['id'].']" id="styleimgdir" value="'.$stylestuff['styleimgdir']['subst'].'" />');
			empty($stylestuff['imgdir']['subst']) && $stylestuff['imgdir']['subst'] = 'static/image/common';
			empty($stylestuff['styleimgdir']['subst']) && $stylestuff['styleimgdir']['subst'] = $stylestuff['imgdir']['subst'];
			$boardimghtml = '<br /><img src="'.(empty($stylestuff['boardimg']['subst']) ? $stylestuff['imgdir']['subst'].'/logo_m.svg' : (preg_match('/^(https?:)?\/\//i', $stylestuff['boardimg']['subst']) || file_exists($stylestuff['boardimg']['subst']) ? '' : (file_exists($stylestuff['styleimgdir']['subst'].'/'.$stylestuff['boardimg']['subst']) ? $stylestuff['styleimgdir']['subst'].'/' : $stylestuff['imgdir']['subst'].'/')).$stylestuff['boardimg']['subst']).'" style="max-height: 70px;" />';
			$searchimghtml = '<img src="'.(empty($stylestuff['searchimg']['subst']) ? $stylestuff['imgdir']['subst'].'/logo_m.svg' : (preg_match('/^(https?:)?\/\//i', $stylestuff['searchimg']['subst']) || file_exists($stylestuff['searchimg']['subst']) ? '' : (file_exists($stylestuff['styleimgdir']['subst'].'/'.$stylestuff['searchimg']['subst']) ? $stylestuff['styleimgdir']['subst'].'/' : $stylestuff['imgdir']['subst'].'/')).$stylestuff['searchimg']['subst']).'" style="max-height: 70px;" />';
			$touchimghtml = '<img src="'.(empty($stylestuff['touchimg']['subst']) ? $stylestuff['imgdir']['subst'].'/logo_m.svg' : (preg_match('/^(https?:)?\/\//i', $stylestuff['touchimg']['subst']) || file_exists($stylestuff['touchimg']['subst']) ? '' : (file_exists($stylestuff['styleimgdir']['subst'].'/'.$stylestuff['touchimg']['subst']) ? $stylestuff['styleimgdir']['subst'].'/' : $stylestuff['imgdir']['subst'].'/')).$stylestuff['touchimg']['subst']).'" style="max-height: 70px;" />';
			showsetting('styles_edit_logo', "stylevar[{$stylestuff['boardimg']['id']}]", $stylestuff['boardimg']['subst'], 'filetext', '', 0, cplang('styles_edit_logo_comment').$boardimghtml);
			showsetting('styles_edit_searchlogo', "stylevar[{$stylestuff['searchimg']['id']}]", $stylestuff['searchimg']['subst'], 'filetext', '', 0, $searchimghtml);
			showsetting('styles_edit_touchlogo', "stylevar[{$stylestuff['touchimg']['id']}]", empty($stylestuff['touchimg']['subst']) ? 'logo_m.svg' : $stylestuff['touchimg']['subst'], 'filetext', '', 0, $touchimghtml);

			foreach($predefinedvars as $predefinedvar => $v) {
				if($v !== array()) {
					if(!empty($v[1])) {
						showtitle($v[1]);
					}
					$type = $v[0] == 1 ? 'text' : 'color';
					$extra = '';
					$comment = ($type == 'text' ? $lang['styles_edit_'.$predefinedvar.'_comment'] : $lang['styles_edit_hexcolor']).$lang['styles_edit_'.$predefinedvar.'_comment'];
					if(substr($predefinedvar, -7, 7) == 'bgcolor') {
						$stylestuff[$predefinedvar]['subst'] = explode(' ', $stylestuff[$predefinedvar]['subst']);
						$bgimg = $stylestuff[$predefinedvar]['subst'][1];
						$bgextra = implode(' ', array_slice($stylestuff[$predefinedvar]['subst'], 2));
						$stylestuff[$predefinedvar]['subst'] = $stylestuff[$predefinedvar]['subst'][0];
						$bgimgpre = $bgimg ? (preg_match('/^(https?:)?\/\//i', $bgimg) ? $bgimg : ($stylestuff['styleimgdir']['subst'] ? $stylestuff['styleimgdir']['subst'] : ($stylestuff['imgdir']['subst'] ? $stylestuff['imgdir']['subst'] : (STATICURL.'image/common'))).'/'.$bgimg) : (STATICURL.'image/common/none.gif');
						$comment .= '<div id="bgpre_'.$stylestuff[$predefinedvar]['id'].'" onclick="imgpre_switch('.$stylestuff[$predefinedvar]['id'].')" style="background-image:url('.$bgimgpre.');cursor:pointer;float:right;width:350px;height:40px;overflow:hidden;border: 1px solid #ccc"></div>'.$lang['styles_edit_'.$predefinedvar.'_comment'].$lang['styles_edit_bg'];
						$extra = '<br /><input name="stylevarbgimg['.$stylestuff[$predefinedvar]['id'].']" value="'.$bgimg.'" onchange="imgpre_update('.$stylestuff[$predefinedvar]['id'].', this)" type="text" class="txt" style="margin:5px 0;" />'.
							'<br /><input name="stylevarbgextra['.$stylestuff[$predefinedvar]['id'].']" value="'.$bgextra.'" type="text" class="txt" />';
						$varcomment = ' {'.strtoupper($predefinedvar).'},{'.strtoupper(substr($predefinedvar, 0, -7)).'BGCODE}:';
					} else {
						$varcomment = ' {'.strtoupper($predefinedvar).'}:';
					}
					showsetting(cplang('styles_edit_'.$predefinedvar).$varcomment, 'stylevar['.$stylestuff[$predefinedvar]['id'].']', $stylestuff[$predefinedvar]['subst'], $type, '', 0, $comment, $extra);
				}
			}
			showtablefooter();

			showtableheader('styles_edit_customvariable', 'notop');
			showsubtitle(array('', 'styles_edit_variable', 'styles_edit_subst'));
			echo $stylecustom;
			showtablerow('', array('class="td25"', 'class="td24 bold"', 'class="td26"'), array(
				cplang('add_new'),
				'<input type="text" class="txt" name="newcvar">',
				'<textarea name="newcsubst" class="tarea" style="height: 45px" cols="50" rows="2"></textarea>'

			));

			showsubmit('editsubmit', 'submit', 'del');
			showtablefooter();
			showformfooter();
			/*search*/
		}
	} else {
		$style = C::t('common_style')->fetch_by_styleid($id);
		if(!$style) {
			cpmsg('style_not_found', '', 'error');
		}

		//是否有自定义配置文件
		$configflag = false;
		if(preg_match('/^.?\/template\/([a-z]+[a-z0-9_]*)$/', $style['directory'], $a)) {
			$configfile = DISCUZ_ROOT . './template/' . $a[1] . '/config.inc.php';
			if(file_exists($configfile)) {
				$configflag = true;
				include $configfile;
			}
		}

		if($_GET['newcvar'] && $_GET['newcsubst']) {
			if(C::t('common_stylevar')->check_duplicate($id, $_GET['newcvar'])) {
				cpmsg('styles_edit_variable_duplicate', '', 'error');
			} elseif(!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $_GET['newcvar'])) {
				cpmsg('styles_edit_variable_illegal', '', 'error');
			}
			$newcvar = strtolower($_GET['newcvar']);
			C::t('common_stylevar')->insert(array('styleid' => $id, 'variable' => $newcvar, 'substitute' => $_GET['newcsubst']));
		}

		if(!$configflag) {

			$data = array();
			if(isset($_GET['namenew'])) {
				$data['name'] = $_GET['namenew'];
			}
			if(isset($_GET['templateidnew'])) {
				$data['templateid'] = $_GET['templateidnew'];
			}
			if(isset($_GET['defaultextstylenew'])) {
				if(!isset($_GET['extstylenew']) || !is_array($_GET['extstylenew'])) {
					$_GET['extstylenew'] = array();
				}
				if(!in_array($_GET['defaultextstylenew'], $_GET['extstylenew'])) {
					$_GET['extstylenew'][] = $_GET['defaultextstylenew'];
				}
				$data['extstyle'] = implode("\t", $_GET['extstylenew']) . '|' . $_GET['defaultextstylenew'];
			}
			if(!empty($data)) {
				C::t('common_style')->update($id, $data);
			}
			if(isset($_GET['stylevar'])) {
				$stylevar = $_GET['stylevar'];
				$stylevarbgimg = $_GET['stylevarbgimg'];
				$stylevarbgextra = $_GET['stylevarbgextra'];
				foreach($stylevar as $varid => $substitute) {
					if(!empty($stylevarbgimg[$varid])) {
						$substitute .= ' '.$stylevarbgimg[$varid];
						if(!empty($stylevarbgextra[$varid])) {
							$substitute .= ' '.$stylevarbgextra[$varid];
						}
					}
					$substitute = @dhtmlspecialchars($substitute);
					$stylevarids = array($varid);
					C::t('common_stylevar')->update_substitute_by_styleid($substitute, $id, $stylevarids);
				}

				if(isset($_FILES['stylevar']['name'])) {
					foreach(C::t('common_stylevar')->fetch_all_by_styleid($id) as $stylevar) {
						$stylesvar[$stylevar['stylevarid']] = $stylevar['variable'];
					}
					$upload = new discuz_upload();
					foreach ($_FILES['stylevar']['name'] as $varid => $value) {
						if($stylesvar[$varid]) {
							$file = array(
								'name' => $_FILES['stylevar']['name'][$varid],
								'type' => $_FILES['stylevar']['type'][$varid],
								'tmp_name' => $_FILES['stylevar']['tmp_name'][$varid],
								'error' => $_FILES['stylevar']['error'][$varid],
								'size' => $_FILES['stylevar']['size'][$varid],
							);
							if($upload->init($file, 'common', 0, '', 'template', 0, $stylesvar[$varid].'_'.date('Ymd').strtolower(random(8))) && $upload->save()) {
								$logonew = $_G['setting']['attachurl'].'common/'.$upload->attach['attachment'];
								$stylevarids = array($varid);
								C::t('common_stylevar')->update_substitute_by_styleid($logonew, $id, $stylevarids);
							}
						}
					}
				}
			}
		}

		if($_GET['delete']) {
			C::t('common_stylevar')->delete_by_styleid($id, $_GET['delete']);
		}

		updatecache(array('setting', 'styles'));

		$tpl = dir(DISCUZ_ROOT.'./data/template');
		while($entry = $tpl->read()) {
			if(preg_match("/\.tpl\.php$/", $entry)) {
				@unlink(DISCUZ_ROOT.'./data/template/'.$entry);
			}
		}
		$tpl->close();
		cpmsg('styles_edit_succeed', 'action=styles&operation=edit&id=' . $id, 'succeed');

	}

} elseif($operation == 'upgradecheck') {
	if(!$admincp->isfounder) {
		cpmsg('noaccess_isfounder', '', 'error');
	}
	$templatearray = C::t('common_template')->fetch_all_data();
	if(!$templatearray) {
		cpmsg('plugin_not_found', '', 'error');
	} else {
		$addonids = $result = $errarray = $newarray = array();
		foreach($templatearray as $k => $row) {
			if(preg_match('/^.?\/template\/([a-z]+[a-z0-9_]*)$/', $row['directory'], $a) && $a[1] != 'default') {
				$addonids[$k] = $a[1].'.template';
			}
		}
		$checkresult = dunserialize(cloudaddons_upgradecheck($addonids));
		savecache('addoncheck_template', $checkresult);
		foreach($addonids as $k => $addonid) {
			if(isset($checkresult[$addonid])) {
				list($return, $newver) = explode(':', $checkresult[$addonid]);
				$result[$addonid]['result'] = $return;
				$result[$addonid]['id'] = $k;
				if($newver) {
					$result[$addonid]['newver'] = $newver;
				}
			}
		}
	}
	foreach($result as $id => $row) {
		if($row['result'] == 0) {
			$errarray[] = '<a href="'.ADMINSCRIPT.'?action=cloudaddons&frame=no&id='.$id.'&from=newver" target="_blank">'.$templatearray[$row['id']]['name'].'</a>';
		} elseif($row['result'] == 2) {
			$newarray[] = '<a href="'.ADMINSCRIPT.'?action=cloudaddons&frame=no&id='.$id.'&from=newver" target="_blank">'.$templatearray[$row['id']]['name'].($row['newver'] ? ' -> '.$row['newver'] : '').'</a>';
		}
	}
	if(!$newarray && !$errarray) {
		cpmsg('styles_validator_noupdate', '', 'error');
	} else {
		shownav('template', 'plugins_validator');
		showsubmenu('styles_admin', array(
			array('styles_list', 'styles', 0),
			array('styles_import', 'styles&operation=import', 0),
			array('plugins_validator', 'styles&operation=upgradecheck', 1),
			array('cloudaddons_style_link', 'cloudaddons&frame=no&operation=templates&from=more', 0, 1),
		), '<a href="https://www.dismall.com/?from=templates_question" target="_blank" class="rlink">'.$lang['templates_question'].'</a>');
		showtableheader();
		if($newarray) {
			showtitle('styles_validator_newversion');
			foreach($newarray as $row) {
				showtablerow('class="hover"', array(), array($row));
			}
		}
		if($errarray) {
			showtitle('styles_validator_error');
			foreach($errarray as $row) {
				showtablerow('class="hover"', array(), array($row));
			}
		}
		showtablefooter();
	}
}

?>