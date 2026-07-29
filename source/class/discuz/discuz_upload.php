<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


class discuz_upload {

	var $attach = [];
	var $type = '';
	var $extid = 0;
	var $errorcode = 0;
	var $forcename = '';
	var $ftpcmd = 1;
	var $remote = 0;

	public function __construct() {

	}

	function init($attach, $type = 'temp', $extid = 0, $forcename = '', $subdir = '', $dirtype = 1, $filename = '') {

		if(!is_array($attach) || empty($attach) || !$this->is_upload_file($attach['tmp_name']) || trim($attach['name']) == '' || $attach['size'] == 0) {
			$this->attach = [];
			$this->errorcode = -1;
			return false;
		} else {
			$this->type = $this->check_dir_type($type);
			$this->extid = intval($extid);
			$this->forcename = preg_match('/^[a-z0-9_]+$/i', $forcename) ? $forcename : '';
			$subdir = preg_match('/^[a-z0-9_]+$/i', $subdir) ? $subdir : '';
			$filename = preg_match('/^[a-z0-9_]+$/i', $filename) ? $filename : '';

			$attach['size'] = intval($attach['size']);
			$attach['name'] = trim($attach['name']);
			$attach['thumb'] = '';
			$attach['ext'] = $this->fileext($attach['name']);

			$attach['name'] = dhtmlspecialchars($attach['name'], ENT_QUOTES);
			if(dstrlen($attach['name']) > 90) {
				$attach['name'] = cutstr($attach['name'], 80, '').'.'.$attach['ext'];
			}

			$attach['isimage'] = $this->is_image_ext($attach['ext']) && ($attach['ext'] != 'svg' || $this->type == 'forum');
			$attach['extension'] = $this->get_target_extension($attach['ext']);
			$attach['attachdir'] = $this->get_target_dir($this->type, $extid, true, $subdir, $dirtype);
			$attach['attachment'] = $attach['attachdir'].$this->get_target_filename($this->type, $this->extid, $this->forcename, $filename).'.'.$attach['extension'];
			$attach['target'] = getglobal('setting/attachdir').'./'.$this->type.'/'.$attach['attachment'];
			$this->attach = &$attach;
			$this->errorcode = 0;
			return true;
		}

	}

	function save($ignore = 0) {
		if($ignore) {
			if(!$this->save_to_local($this->attach['tmp_name'], $this->attach['target'])) {
				$this->errorcode = -103;
				return false;
			} else {
				$this->ftpupload();
				$this->errorcode = 0;
				return true;
			}
		}

		if(empty($this->attach) || empty($this->attach['tmp_name']) || empty($this->attach['target'])) {
			$this->errorcode = -101;
		} elseif(in_array($this->type, ['group', 'album', 'category']) && !$this->attach['isimage']) {
			$this->errorcode = -102;
		} elseif($this->type == 'common' && (!$this->attach['isimage'] && !in_array($this->attach['ext'], ['ext', 'svg']))) {
			$this->errorcode = -102;
		} elseif(!$this->save_to_local($this->attach['tmp_name'], $this->attach['target'])) {
			$this->errorcode = -103;
		} elseif($this->attach['ext'] == 'svg' && !self::sanitize_svg($this->attach['target'])) {
			$this->errorcode = -104;
			@unlink($this->attach['target']);
		} elseif(($this->attach['isimage'] || $this->attach['ext'] == 'swf') && (!$this->attach['imageinfo'] = $this->get_image_info($this->attach['target'], true))) {
			$this->errorcode = -104;
			@unlink($this->attach['target']);
		} else {
			$this->ftpupload();
			$this->errorcode = 0;
			return true;
		}

		return false;
	}

	function ftpupload() {
		if($this->ftpcmd && ftpperm(fileext($this->attach['name']), $this->attach['size'])) {
			$this->remote = ftpcmd('upload', $this->type.'/'.$this->attach['attachment']);
		}
	}

	function error() {
		return $this->errorcode;
	}

	function errormessage() {
		return lang('error', 'file_upload_error_'.$this->errorcode);
	}

	public static function fileext($filename) {
		return addslashes(strtolower(substr(strrchr($filename, '.'), 1, 10)));
	}

	public static function is_image_ext($ext) {
		static $imgext = ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'webp', 'svg'];
		return in_array($ext, $imgext) ? 1 : 0;
	}

	public static function get_image_info($target, $allowswf = false, $ext = '') {
		$ext = $ext ? strtolower($ext) : discuz_upload::fileext($target);
		$isimage = discuz_upload::is_image_ext($ext);
		if(!$isimage && ($ext != 'swf' || !$allowswf)) {
			return false;
		} elseif(!is_readable($target)) {
			return false;
		} elseif($ext == 'svg') {
			return self::sanitize_svg($target);
		} elseif($imageinfo = @getimagesize($target)) {
			list($width, $height, $type) = !empty($imageinfo) ? $imageinfo : ['', '', ''];
			$size = $width * $height;
			// Imagick 不受最大大小限制, GD 限制值从数据库读取
			if((!getglobal('setting/imagelib') && $size > (getglobal('setting/gdlimit') ? getglobal('setting/gdlimit') : 16777216)) || $size < 16) {
				return false;
			} elseif($ext == 'swf' && $type != 4 && $type != 13) {
				return false;
			} elseif($isimage && !in_array($type, [1, 2, 3, 6, 13, 18])) {
				return false;
			} elseif(!$allowswf && ($ext == 'swf' || $type == 4 || $type == 13)) {
				return false;
			}
			return $imageinfo;
		} else {
			return false;
		}
	}

	/**
	 * SVG is active XML when served directly. Keep only declarative drawing data
	 * before accepting it as an inline image attachment.
	 */
	private static function sanitize_svg($target) {
		$source = @file_get_contents($target);
		if($source === false || preg_match('/<!DOCTYPE|<!ENTITY|<\\?(?!xml\\s+version\\s*=)/i', $source) || !class_exists('DOMDocument')) {
			return false;
		}

		$previous = libxml_use_internal_errors(true);
		$document = new DOMDocument();
		$loaded = $document->loadXML($source, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		if(!$loaded || !$document->documentElement || strtolower($document->documentElement->localName) != 'svg' || $document->documentElement->namespaceURI != 'http://www.w3.org/2000/svg') {
			return false;
		}

		$allowedElements = array_flip(['svg', 'g', 'defs', 'symbol', 'use', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'pattern', 'title', 'desc', 'text', 'tspan']);
		$allowedAttributes = array_flip(['id', 'class', 'xmlns', 'width', 'height', 'viewbox', 'preserveaspectratio', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'transform', 'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'clip-path', 'clip-rule', 'mask', 'filter', 'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'spreadmethod', 'patternunits', 'patterncontentunits', 'text-anchor', 'font-family', 'font-size', 'font-weight', 'font-style', 'letter-spacing', 'word-spacing', 'dominant-baseline', 'role', 'aria-label', 'aria-hidden']);
		$nodes = [];
		foreach($document->getElementsByTagName('*') as $node) {
			$nodes[] = $node;
		}
		foreach(array_reverse($nodes) as $node) {
			if(!isset($allowedElements[strtolower($node->localName)])) {
				$node->parentNode->removeChild($node);
				continue;
			}
			$xlinkHref = $node->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
			if($xlinkHref !== '') {
				$node->removeAttributeNS('http://www.w3.org/1999/xlink', 'href');
				if(!$node->hasAttribute('href') && strtolower($node->localName) == 'use' && preg_match('/^#[A-Za-z_][\w:.-]*$/', $xlinkHref)) {
					$node->setAttribute('href', $xlinkHref);
				}
			}
			for($i = $node->attributes->length - 1; $i >= 0; $i--) {
				$attribute = $node->attributes->item($i);
				$name = strtolower($attribute->localName);
				$value = trim($attribute->value);
				if($name == 'href') {
					if(strtolower($node->localName) != 'use' || $attribute->namespaceURI || !preg_match('/^#[A-Za-z_][\w:.-]*$/', $value)) {
						$node->removeAttributeNode($attribute);
					}
					continue;
				}
				if(!isset($allowedAttributes[$name]) || (str_contains(strtolower($value), 'url(') && !preg_match('/^url\\(\\s*#[a-z][\\w.-]*\\s*\\)$/i', $value))) {
					$node->removeAttributeNode($attribute);
				}
			}
		}

		if(@file_put_contents($target, $document->saveXML()) === false) {
			return false;
		}
		return self::svg_image_info($target);
	}

	private static function svg_image_info($target) {
		$source = @file_get_contents($target);
		if($source === false || !class_exists('DOMDocument')) {
			return false;
		}
		$previous = libxml_use_internal_errors(true);
		$document = new DOMDocument();
		$loaded = $document->loadXML($source, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		if(!$loaded || !$document->documentElement || strtolower($document->documentElement->localName) != 'svg' || $document->documentElement->namespaceURI != 'http://www.w3.org/2000/svg') {
			return false;
		}
		$svg = $document->documentElement;
		$viewBox = preg_split('/[\\s,]+/', trim($svg->getAttribute('viewBox')));
		$width = self::svg_length($svg->getAttribute('width'));
		$height = self::svg_length($svg->getAttribute('height'));
		if(count($viewBox) == 4 && is_numeric($viewBox[2]) && is_numeric($viewBox[3])) {
			$width = $width ?: (float) $viewBox[2];
			$height = $height ?: (float) $viewBox[3];
		}
		if($width <= 0 || $height <= 0 || $width * $height < 16) {
			return false;
		}
		return [(int) ceil($width), (int) ceil($height), 0, 'mime' => 'image/svg+xml'];
	}

	private static function svg_length($value) {
		return preg_match('/^\\s*(\\d+(?:\\.\\d+)?)(?:px)?\\s*$/i', $value, $match) ? (float) $match[1] : 0;
	}

	public static function is_upload_file($source) {
		return $source && ($source != 'none') && (is_uploaded_file($source) || is_uploaded_file(str_replace('\\\\', '\\', $source)) || self::_is_dfile($source));
	}

	private static function _is_dfile($source) {
		$_tmpdir = dirname(tempnam(sys_get_temp_dir(), 'du'));
		return dirname($source) == $_tmpdir && !str_contains($source, '..') &&
			!empty($_ENV['DFILES'][$source]) &&
			!empty($_ENV['DFILES'][$source]['tmp_name']) && $source == $_ENV['DFILES'][$source]['tmp_name'];
	}

	public static function get_target_filename($type, $extid = 0, $forcename = '', $filename = '') {
		if(empty($filename)) {
			if($type == 'group' || ($type == 'common' && $forcename != '')) {
				$filename = $type.'_'.intval($extid).($forcename != '' ? "_$forcename" : '');
			} else {
				$filename = date('His').strtolower(random(16));
			}
		}
		return $filename;
	}

	public static function get_target_extension($ext) {
		static $safeext = ['attach', 'jpg', 'jpeg', 'gif', 'png', 'webp', 'svg', 'swf', 'bmp', 'txt', 'zip', 'rar', 'mp3', 'mp4', 'wmv', 'wma', 'mov'];
		return strtolower(!in_array(strtolower($ext), $safeext) ? 'attach' : $ext);
	}

	public static function get_target_dir($type, $extid = '', $check_exists = true, $subdir = '', $dirtype = 1) {

		$dir = $subdir1 = $subdir2 = '';
		// $dirtype == 0 表示不需要子目录
		if($dirtype == 1) {
			if($type == 'group' || $type == 'common') {
				$dir = $subdir1 = substr(md5($extid), 0, 2).'/';
			} elseif($type != 'temp') {
				$subdir1 = date('Ym');
				$subdir2 = date('d');
				$dir = $subdir1.'/'.$subdir2.'/';
			}
		} elseif($dirtype == 2) {
			$subdir1 = date('Ym');
			$subdir2 = date('d');
			$dir = $subdir1.'/'.$subdir2.'/';
		} elseif($dirtype == 3) {
			$dir = $subdir1 = substr(md5($extid), 0, 2).'/';
		}

		if($subdir) {
			$dir = $subdir.'/'.$dir;
		}

		if($check_exists) {
			if($subdir) {
				discuz_upload::check_dir_exists($type, $subdir, $subdir1);
				discuz_upload::check_dir_exists($type, $subdir.'/'.$subdir1.'/'.$subdir2);
			} else {
				discuz_upload::check_dir_exists($type, $subdir1, $subdir2);
			}
		}

		return $dir;
	}

	public static function check_dir_type($type) {
		return preg_match('/^[a-z]+[a-z0-9_]*$/i', $type) ? $type : 'temp';
	}

	public static function check_dir_exists($type = '', $sub1 = '', $sub2 = '') {

		$type = discuz_upload::check_dir_type($type);

		$basedir = !getglobal('setting/attachdir') ? (DISCUZ_ROOT.'./data/attachment') : getglobal('setting/attachdir');

		$typedir = $type ? ($basedir.'/'.$type) : '';
		$subdir1 = $type && $sub1 !== '' ? ($typedir.'/'.$sub1) : '';
		$subdir2 = $sub1 && $sub2 !== '' ? ($subdir1.'/'.$sub2) : '';

		$res = $subdir2 ? is_dir($subdir2) : ($subdir1 ? is_dir($subdir1) : is_dir($typedir));
		if(!$res) {
			$res = $typedir && discuz_upload::make_dir($typedir);
			$res && $subdir1 && ($res = discuz_upload::make_dir($subdir1));
			$res && $subdir1 && $subdir2 && ($res = discuz_upload::make_dir($subdir2));
		}

		return $res;
	}

	function save_to_local($source, $target) {
		if(!discuz_upload::is_upload_file($source)) {
			$succeed = false;
		} elseif(@copy($source, $target)) {
			$succeed = true;
		} elseif(function_exists('move_uploaded_file') && @move_uploaded_file($source, $target)) {
			$succeed = true;
		} elseif(@is_readable($source) && (@$fp_s = fopen($source, 'rb')) && (@$fp_t = fopen($target, 'wb'))) {
			while(!feof($fp_s)) {
				$s = @fread($fp_s, 1024 * 512);
				@fwrite($fp_t, $s);
			}
			fclose($fp_s);
			fclose($fp_t);
			$succeed = true;
		}
		if($succeed) {
			$this->errorcode = 0;
			@chmod($target, 0644);
			@unlink($source);
		} else {
			$this->errorcode = 0;
		}

		return $succeed;
	}

	public static function make_dir($dir, $index = true) {
		$res = true;
		if(!is_dir($dir)) {
			$res = @mkdir($dir, 0777);

		}
		return $res;
	}
}

