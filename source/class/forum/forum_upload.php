<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_upload.php 32858 2013-03-15 03:36:22Z zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class forum_upload {

	var $uid;
	var $aid;
	var $simple;
	var $statusid;
	var $attach;
	var $error_sizelimit;
	var $getaid;

	function __construct($getaid = 0) {
		global $_G;

		$_G['uid'] = $this->uid = intval($_GET['uid']);
		$swfhash = md5(substr(md5($_G['config']['security']['authkey']), 8).$this->uid);
		$this->aid = 0;
		$this->getaid = $getaid;
		$this->simple = !empty($_GET['simple']) ? $_GET['simple'] : 0;

		if($_GET['hash'] != $swfhash) {
			return $this->uploadmsg(10);
		}


		$upload = new discuz_upload();
		$upload->init($_FILES['Filedata'], 'forum');
		$this->attach = &$upload->attach;

		if($upload->error()) {
			return $this->uploadmsg(2);
		}

		$allowupload = ($_G['group']['allowpostattach'] || $_G['group']['allowpostimage']) ? (!$_G['group']['maxattachnum'] || $_G['group']['maxattachnum'] && $_G['group']['maxattachnum'] > getuserprofile('todayattachs')) : false;
		if(!$allowupload) {
			return $this->uploadmsg(6);
		}

		if($_G['group']['attachextensions'] && (!preg_match("/(^|\s|,)".preg_quote($upload->attach['ext'], '/')."($|\s|,)/i", $_G['group']['attachextensions']) || !$upload->attach['ext'])) {
			return $this->uploadmsg(1);
		}

		if(empty($upload->attach['size'])) {
			return $this->uploadmsg(2);
		}

		if($_G['group']['maxattachsize'] && $upload->attach['size'] > $_G['group']['maxattachsize']) {
			$this->error_sizelimit = $_G['group']['maxattachsize'];
			return $this->uploadmsg(3);
		}

		loadcache('attachtype');
		if($_G['fid'] && isset($_G['cache']['attachtype'][$_G['fid']][$upload->attach['ext']])) {
			$maxsize = $_G['cache']['attachtype'][$_G['fid']][$upload->attach['ext']];
		} elseif(isset($_G['cache']['attachtype'][0][$upload->attach['ext']])) {
			$maxsize = $_G['cache']['attachtype'][0][$upload->attach['ext']];
		}
		if(isset($maxsize)) {
			if(!$maxsize) {
				$this->error_sizelimit = 'ban';
				return $this->uploadmsg(4);
			} elseif($upload->attach['size'] > $maxsize) {
				$this->error_sizelimit = $maxsize;
				return $this->uploadmsg(5);
			}
		}

		if($upload->attach['size'] && $_G['group']['maxsizeperday']) {
			$todaysize = getuserprofile('todayattachsize') + $upload->attach['size'];
			if($todaysize >= $_G['group']['maxsizeperday']) {
				$this->error_sizelimit = 'perday|'.$_G['group']['maxsizeperday'];
				return $this->uploadmsg(11);
			}
		}

		// 修复敏感词拦截无明确提示的问题
		$filename = censor($upload->attach['name'], NULL, TRUE);
		if(is_array($filename)) {
			return $this->uploadmsg(12);
		}

		if(isset($_GET['type']) && $_GET['type'] == 'image' && !$upload->attach['isimage']) {
			return $this->uploadmsg(7);
		}

		$upload->save();
		if($upload->error() == -103) {
			return $this->uploadmsg(8);
		} elseif($upload->error()) {
			return $this->uploadmsg(9);
		}

		updatemembercount($_G['uid'], array('todayattachs' => 1, 'todayattachsize' => $upload->attach['size'], 'attachsize' => $upload->attach['size']));

		$thumb = $remote = 0;
		if($upload->attach['isimage'] && $upload->attach['ext']!='svg') {
			if($_G['setting']['showexif']) {
				require_once libfile('function/attachment');
				$exif = getattachexif(0, $upload->attach['target']);
			}
		}
		if($_GET['type'] != 'image' && $upload->attach['isimage']) {
			$upload->attach['isimage'] = -1;
		}
		$this->aid = $aid = getattachnewaid($this->uid);
		$insert = array(
			'aid' => $aid,
			'dateline' => $_G['timestamp'],
			'filename' => $filename,
			'filesize' => $upload->attach['size'],
			'attachment' => $upload->attach['attachment'],
			'isimage' => $upload->attach['isimage'],
			'uid' => $this->uid,
			'thumb' => $thumb,
			'remote' => $remote,
			'width' => intval($upload->attach['imageinfo'][0]),
			'height' => intval($upload->attach['imageinfo'][1]),
		);
		C::t('forum_attachment_unused')->insert($insert);
		if($upload->attach['isimage'] && $_G['setting']['showexif']) {
			C::t('forum_attachment_exif')->insert_exif($aid, $exif);
		}
		return $this->uploadmsg(0);
	}

	function uploadmsg($statusid) {
		global $_G;
		$this->error_sizelimit = !empty($this->error_sizelimit) ? $this->error_sizelimit : 0;
		if($this->getaid) {
			$this->getaid = $statusid ? -$statusid : $this->aid;
			return;
		}
		if($this->simple == 1) {
			echo 'DISCUZUPLOAD|'.$statusid.'|'.$this->aid.'|'.$this->attach['isimage'].'|'.$this->error_sizelimit;
		} elseif($this->simple == 2) {
			echo 'DISCUZUPLOAD|'.($_GET['type'] == 'image' ? '1' : '0').'|'.$statusid.'|'.$this->aid.'|'.$this->attach['isimage'].'|'.($this->attach['isimage'] ? $this->attach['attachment'] : '').'|'.$this->attach['name'].'|'.$this->error_sizelimit;
		} else {
			echo $statusid ? -$statusid : $this->aid;
		}
		exit;
	}
}

?>