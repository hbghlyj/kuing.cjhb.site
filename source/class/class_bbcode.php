<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class bbcode {

	var $search_exp = [];
	var $replace_exp = [];
	var $html_s_exp = [];
	var $html_r_exp = [];
	var $html_s_str = [];
	var $html_r_str = [];

	public static function &instance() {
		static $object;
		if(empty($object)) {
			$object = new bbcode();
		}
		return $object;
	}

	function __construct() {
	}

	function bbcode2html($message, $parseurl = 0) {
		if(empty($this->search_exp)) {
			$this->search_exp = [
				"/\s*\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s*/is",
				"/\[url\]\s*(https?:\/\/|ftp:\/\/|gopher:\/\/|news:\/\/|telnet:\/\/|rtsp:\/\/|mms:\/\/|callto:\/\/|ed2k:\/\/){1}([^\[\"']+?)\s*\[\/url\]/i",
				'/\[em:([0-9]+):\]/i',
				"/`([^`\r\n]+)`/",
			];
			$this->replace_exp = [
				"<div class=\"quote\"><blockquote>\\1</blockquote></div>",
				"<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>",
				" <img src=\"".STATICURL."image/smiley/comcom/\\1.gif\" class=\"vm\"> ",
				"<code>\\1</code>",
			];
			$this->replace_exp[] = '$this->bb_img(\'\\1\')';
		}

		if($parseurl == 2) {
			$message = bbcode::parseurl($message);
		}

		@$message = preg_replace($this->search_exp, $this->replace_exp, $message, 20);

		if($parseurl == 2) {
			@$message = preg_replace_callback("/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is", [$this, 'bbcode2html_callback_bb_img_1'], $message, 20);
		}

		foreach([
			['[b]', '[/b]', '<strong>', '</strong>'],
			['[s]', '[/s]', '<strike>', '</strike>'],
			['[i]', '[/i]', '<i>', '</i>'],
			['[u]', '[/u]', '<u>', '</u>'],
		] as $tag_pair) {
			$message = $this->replace_paired_bbcode($message, $tag_pair[0], $tag_pair[1], $tag_pair[2], $tag_pair[3]);
		}
		return nl2br($message);
	}

	function replace_paired_bbcode($message, $open_tag, $close_tag, $open_html, $close_html) {
		$parts = explode($close_tag, $message);
		$message = '';
		$count = count($parts);
		for($i = 0; $i < $count - 1; $i++) {
			$part = $parts[$i];
			$pos = strrpos($part, $open_tag);
			if($pos === false) {
				$message .= $part.$close_tag;
			} else {
				$message .= substr_replace($part, $open_html, $pos, strlen($open_tag)).$close_html;
			}
		}
		return $message.$parts[$count - 1];
	}

	function bbcode2html_callback_bb_img_1($matches) {
		return $this->bb_img($matches[1]);
	}

	function parseurl($message) {
		return preg_replace("/(?<=[^\]a-z0-9-=\"'\\/])((https?|ftp|gopher|news|telnet|mms|rtsp):\/\/)([a-z0-9\/\-_+=.~!%@?#%&;:$\\()|]+)/i", "[url]\\1\\3[/url]", ' '.$message);
	}

	function html2bbcode($message) {

		if(empty($this->html_s_exp)) {
			$this->html_s_exp = [
				"/\<div class=\"quote\"\>\<blockquote\>(.*?)\<\/blockquote\>\<\/div\>/is",
				"/\<a href=\"(.+?)\".*?\<\/a\>/is",
				"/(\r\n|\n|\r)/",
				'/<br.*>/siU',
				"/[ \t]*\<img src=\"static\/image\/smiley\/comcom\/(.+?).gif\".*?\>[ \t]*/is",
				"/\s*\<img src=\"(.+?)\".*?\>\s*/is",
				"/\<code\>(.*?)\<\/code\>/is",
			];
			$this->html_r_exp = [
				"[quote]\\1[/quote]",
				"\\1",
				'',
				"\n",
				"[em:\\1:]",
				"\n[img]\\1[/img]\n",
				"`\\1`",
			];
			$this->html_s_str = ['<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;', '&lt;', '&gt;', '&amp;'];
			$this->html_r_str = ['[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', "\t", '   ', '  ', '<', '>', '&'];
		}

		@$message = str_replace($this->html_s_str, $this->html_r_str,
			preg_replace($this->html_s_exp, $this->html_r_exp, $message));

		$message = dhtmlspecialchars($message);

		return trim($message);
	}

	function bb_img($url) {
		global $_G;

		if(!in_array(strtolower(substr($url, 0, 6)), ['http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://'])) {
			$url = isset($_G['siteurl']) && !empty($_G['siteurl']) ? $_G['siteurl'].$url : 'http://'.$url;
		}
		$url = addslashes($url);
		return "<img src=\"$url\" class=\"vm\">";
	}
}

