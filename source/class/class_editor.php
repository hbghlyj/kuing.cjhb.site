<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class editor {

	private static $r_dzTpl = [
		"/[\n\r\t]*\{block\/(\d+?)\}[\n\r\t]*/i",
		"/[\n\r\t]*\{blockdata\/(\d+?)\}[\n\r\t]*/i",
		"/[\n\r\t]*\{ad\/(.+?)\}[\n\r\t]*/i",
		"/[\n\r\t]*\{ad\s+([a-zA-Z0-9_\[\]]+)\/(.+?)\}[\n\r\t]*/i",
		"/[\n\r\t]*\{date\((.+?)\)\}[\n\r\t]*/i",
		"/[\n\r\t]*\{avatar\((.+?)\)\}[\n\r\t]*/i",
		"/[\n\r\t]*\{eval\}\s*(\<\!\-\-)*(.+?)(\-\-\>)*\s*\{\/eval\}[\n\r\t]*/is",
		"/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/is",
		"/[\n\r\t]*\{csstemplate\}[\n\r\t]*/is",
		"/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\s(or|\?\?)\s([a-zA-Z0-9\']+)\}/s",
		"/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s",
		'/\{hook\/(\w+?)(\s+(.+?))?\}/i',
		"/((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)/s",
		"/\<\?\=\<\?\=((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)\?\>\?\>/s",
		"/[\n\r\t]*\{template\s+([a-z0-9_:\/]+)\}[\n\r\t]*/is",
		"/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is",
		"/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is",
		"/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/is",
		"/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is",
		'/\{else\}/i',
		'/\{\/if\}/i',
		"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/is",
		"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is",
		'/\{\/loop\}/i',
		"/[\n\r\t]*\{block\s+([a-zA-Z0-9_\[\]']+)\}(.+?)\{\/block\}/is",
	];
	private static $r_php = [
		'/<\?/',
		'/\?>/',
	];
	private static $r_js = [
		'/javascrit:/i',
		'/<script(.*?)>/i',
		'/\s+on(?!ly\b)([a-zA-Z]+)/i',
	];

	public static function checkTemplate($template) {
		$template = preg_replace('/\<\!\-\-\{(.+?)\}\-\-\>/s', "{\\1}", $template);
		foreach(self::$r_dzTpl as $r) {
			if(preg_match($r, $template)) {
				return false;
			}
		}
		foreach(self::$r_php as $r) {
			if(preg_match($r, $template)) {
				return false;
			}
		}
		foreach(self::$r_js as $r) {
			if(preg_match($r, $template)) {
				return false;
			}
		}
		return true;
	}

	public static function toArray($content) {
		if(is_array($content)) {
			return $content;
		}
		if(!is_string($content) || $content === '') {
			return null;
		}
		return json_decode($content, true);
	}

	public static function getBlockTemplate($type) {
		$editorblock_parser = $editorblock_style = $editorblock_static = '';
		// Block types come from user-submitted JSON and are used as cache keys and static paths.
		if(!is_string($type) || !preg_match('/^[a-zA-Z0-9_\-]{1,64}$/', $type)) {
			return [$editorblock_parser, $editorblock_style, $editorblock_static];
		}
		$editorblock = memory('get', 'editorblock_'.$type);
		if(!($editorblock && !empty($editorblock['parser']) && !empty($editorblock['style']))) {
			$editorblock = table_common_editorblock::t()->fetch_by_block_class($type);
			memory('set', 'editorblock_'.$type, $editorblock);
		}
		if(is_array($editorblock)) {
			$editorblock_parser = (string)($editorblock['parser'] ?? '');
			$editorblock_style = (string)($editorblock['style'] ?? '');
			$editorblock_static = empty($editorblock['plugin']) ? 'static/js/editorjs/tools/'.$type.'/' : 'source/plugin/'.$editorblock['plugin'].'/editorblock/tools/'.$type.'/';
		}
		return [$editorblock_parser, $editorblock_style, $editorblock_static];
	}

	public static function getBlockParser($tpl, $block) {
		$parser = $tpl;
		return $parser;
	}

	public static function parser($content, $context = []) {
		global $_G;
		$blocksData = self::toArray($content);
		$parserData = '';
		$styleData = ['types' => [], 'style' => ''];
		if(!is_array($blocksData) || empty($blocksData['blocks']) || !is_array($blocksData['blocks'])) {
			return [$parserData, $styleData['style']];
		}

		$context = is_array($context) ? $context : [];
		$itemid = isset($context['itemid']) ? intval($context['itemid']) : intval($_G['tid'] ?? 0);
		$context['itemid'] = $itemid;
		foreach($blocksData['blocks'] as $value) {
			if(!is_array($value) || !isset($value['type']) || !is_string($value['type'])) {
				continue;
			}
			[$editorblock_parser, $editorblock_style, $editorblock_static] = self::getBlockTemplate($value['type']);

			// 是否用户回帖可见
			$allowParser = true;
			$isHideTuneBlock = false;
			if(!empty($value['tunes']['hideTune']['hide'])) {
				$isHideTuneBlock = true;
				$authorreplyexist = null;
				if($_G['uid']) {
					$authorreplyexist = table_forum_post::t()->fetch_pid_by_tid_authorid($itemid, $_G['uid']);
				}
				if(!$authorreplyexist) {
					$allowParser = false;
				}
				if($_G['adminid'] > 0) {
					$allowParser = true;
				}
			}
			if(!$allowParser) {
				$parser = '<div class="locked">'.($_G['uid'] ? dhtmlspecialchars($_G['username']) : lang('forum/template', 'guest')).lang('forum/template', 'post_hide_reply_hidden_text').'<a href="forum.php?mod=post&action=reply&fid='.intval($_G['fid'] ?? 0).'&tid='.$itemid.'" onclick="showWindow(\'reply\', this.href)">'.lang('forum/template', 'reply').'</a></div>';
			} else {
				if($editorblock_parser !== '') {
					$parser = (new editorBlock($editorblock_parser, $value, $editorblock_static, $context))->replace();
				} else {
					$parser = self::fallbackParser($value);
				}
				if($isHideTuneBlock && $parser !== '') {
					$parser = '<div class="showhide"><h4>'.lang('forum/template', 'post_hide').'</h4>'.$parser.'</div>';
				}
			}

			// 锚点解析
			$anchorparse = explode(',', (string)getglobal('setting/anchorparse'));
			if(in_array($value['type'], $anchorparse, true)) {
				$parser = preg_replace_callback('/<a\s+href=[\'"]#([^\'"]+)[\'"](.*?)>/s', function($m) {
					$href = $m[1];
					$clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', $href);
					if($clean === '' || $clean !== $href) {
						return '<a href="#'.$href.'"'.$m[2].'>';
					}
					$onclick = "javascript: document.getElementById('".$clean."').scrollIntoView();return false;";
					return '<a href="#'.$clean.'" onclick="'.$onclick.'"'.$m[2].'>';
				}, $parser);
			}
			// 追加
			$parserData .= $parser;
			if($editorblock_parser !== '' && !in_array($value['type'], $styleData['types'], true)) {
				$styleData['types'][] = $value['type'];
				$styleData['style'] .= $editorblock_style;
			}
		}
		return [$parserData, $styleData['style']];
	}

	/*
	 * Saved blocks whose type is no longer registered (for example a retired RAW block
	 * deleted in AdminCP) must not disappear silently. Their source is shown escaped.
	 */
	public static function fallbackParser($block) {
		if(($block['type'] ?? '') !== 'raw' || !isset($block['data']['html']) || !is_scalar($block['data']['html'])) {
			return '';
		}
		$id = is_scalar($block['id'] ?? null) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$block['id']) : '';
		$html = editorBlock::filterValue($block['data']['html'], 'textarea');
		return '<div class="ce-block" data-id="'.$id.'"><div class="ce-block__content"><pre class="ce-rawtool-legacy"><code>'.$html.'</code></pre></div></div>';
	}

}

/*
 * Renders one JSON block through its registered template.
 *
 * Rendering happens in two phases so that block data can never be interpreted as
 * template markup: first every directive is expanded with block data replaced by
 * opaque tokens, then the resulting markup is scanned to determine the HTML context
 * of each token, and each token is replaced by its value filtered for that context.
 */
class editorBlock {
	var $html = '';
	var $block = [];
	var $static = '';
	var $context = [];

	private $tokens = [];
	private $tagNames = [];

	const TOKEN = "\x1A";

	// Complete tag names that may be produced from block data (header levels and similar).
	private static $dynamicTags = [
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span', 'b', 'i', 'u', 's', 'em', 'strong', 'small',
		'sub', 'sup', 'mark', 'code', 'pre', 'blockquote', 'ol', 'ul', 'li', 'dl', 'dt', 'dd', 'section',
		'article', 'aside', 'header', 'footer', 'figure', 'figcaption', 'table', 'caption', 'thead', 'tbody',
		'tfoot', 'tr', 'td', 'th',
	];

	// Attributes whose template placeholders are URLs.
	private static $urlAttrs = ['src', 'href', 'action', 'formaction', 'poster', 'xlink:href', 'data', 'cite', 'longdesc', 'background', 'srcset', 'dynsrc', 'lowsrc', 'usemap', 'codebase', 'archive', 'ping', 'manifest', 'icon', 'classid', 'profile'];
	private static $allowedSchemes = ['http', 'https', 'ftp', 'ftps', 'mailto', 'tel', 'sms', 'magnet', 'ed2k', 'rtsp', 'rtmp', 'mms', 'thunder'];
	// Elements whose content is not ordinary markup.
	private static $rawTextTags = ['script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript', 'plaintext'];

	// Rich text allowed inside block data.
	private static $allowedTags = [
		'a', 'abbr', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup',
		'dd', 'del', 'details', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'font',
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'mark',
		'ol', 'p', 'pre', 'q', 's', 'samp', 'small', 'span', 'strike', 'strong', 'sub',
		'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul', 'var',
	];
	private static $allowedAttrs = [
		'abbr', 'align', 'alt', 'border', 'cellpadding', 'cellspacing', 'checked', 'class', 'color',
		'colspan', 'datetime', 'dir', 'disabled', 'face', 'headers', 'height', 'lang', 'open', 'rel',
		'reversed', 'rowspan', 'scope', 'size', 'span', 'start', 'style', 'target', 'title', 'type',
		'valign', 'value', 'width',
	];
	private static $richUrlAttrs = ['href', 'src', 'srcset', 'cite', 'background', 'longdesc', 'poster'];
	private static $allowedStyles = [
		'background-color', 'border', 'border-bottom', 'border-collapse', 'border-color', 'border-left',
		'border-radius', 'border-right', 'border-spacing', 'border-style', 'border-top', 'border-width',
		'clear', 'color', 'direction', 'display', 'float', 'font', 'font-family', 'font-size',
		'font-style', 'font-variant', 'font-weight', 'height', 'letter-spacing', 'line-height',
		'list-style', 'list-style-type', 'margin', 'margin-bottom', 'margin-left', 'margin-right',
		'margin-top', 'max-height', 'max-width', 'min-height', 'min-width', 'overflow-wrap', 'padding',
		'padding-bottom', 'padding-left', 'padding-right', 'padding-top', 'text-align', 'text-decoration',
		'text-decoration-color', 'text-decoration-line', 'text-decoration-style', 'text-indent',
		'text-transform', 'vertical-align', 'white-space', 'width', 'word-break', 'word-spacing',
	];
	// Elements removed together with their content.
	private static $dropContentTags = ['script', 'style', 'template', 'iframe', 'object', 'embed', 'noscript', 'noembed', 'noframes', 'xmp', 'textarea', 'title'];

	/*
	 * [loop data.items]
	 *   <li>{text}</li>
	 * [/loop]
	 */
	const rLoop = '/\[loop ([a-zA-Z\.]+)\](.+?)\[\/loop\]/s';
	const rLoopIndex = '/\[loopindex\]/s';
	const rLoopObject = '/\[loopobject ([a-zA-Z\.]+)\](.+?)\[\/loopobject\]/s';
	const rLoopObjectData = '/\{loopobjectdata\}/';
	const rVar = '/\{([a-zA-Z\.]+)\}/';

	/*
	 * [if data.ok]
	 *   {text} or string
	 * [/if]
	 */
	const rIf = '/\[if ([a-zA-Z\.]+)=(.+?)\](.*?)\[\/if\]/s';

	/*
	 * [url data.url]
	 */
	const rUrl = '/\[url ([a-zA-Z\.]+),([a-zA-Z\.]+),([a-zA-Z\.]+)\]/s';

	/*
	 * [attach data.url]
	 */
	const rAttach = '/\[attach ([a-zA-Z\.]+)\]/s';

	/*
	* [column blocks]
	*/
	const rColumn = '/\[column ([a-zA-Z\.]+)\]/s';

	/*
	 * [script path]
	 */
	const rScript = '/\[script (.+?)\]/s';

	/*
	* [codeflask id,language,code]
	*/
	const rCodeflask = '/\[codeflask ([a-zA-Z\.]+),([a-zA-Z\.]+),([a-zA-Z\.]+),([a-zA-Z\.]+)\]/s';

	/*
	 * [media id,url,width]
	 * 支持audio、video解析，用法：[media id,[url data.file.url,data.file.remote,data.file.directory],300]
	 */
	const rMedia = '/\[media ([a-zA-Z\.]+),([^,\]]+),([a-zA-Z0-9%\.]+)(?:,([^,\]]+))?\]/s';

	const rJsFile = '/\[jsfile ([a-zA-Z0-9_\.]+\.js)\]/s';
	const rCssFile = '/\[cssfile ([a-zA-Z0-9_\.]+\.css)\]/s';
	const rRecursive = '/\[recursive ([a-zA-Z\.]+),([^\]]+)\]/s';

	public function __construct($html, $block, $static, $context = []) {
		$this->html = is_string($html) ? $html : '';
		$this->block = is_array($block) ? $block : [];
		$this->static = $static;
		$this->context = is_array($context) ? $context : [];
	}

	public function replace() {
		$this->tokens = $this->tagNames = [];
		if($this->html === '') {
			return '';
		}
		$html = $this->html;
		// Directives first, so that substituted data never reaches a directive pattern.
		$passes = [
			[self::rJsFile, '_jsfile'],
			[self::rCssFile, '_cssfile'],
			[self::rCodeflask, '_codeflask'],
			[self::rLoop, '_loop'],
			[self::rLoopObject, '_object'],
			[self::rRecursive, '_recursive'],
			[self::rVar, '_var'],
			[self::rUrl, '_url'],
			[self::rAttach, '_attach'],
			[self::rIf, '_if'],
			[self::rMedia, '_media'],
		];
		foreach($passes as [$pattern, $method]) {
			$html = preg_replace_callback($pattern, [$this, $method], $html);
			if($html === null) {
				$this->tokens = $this->tagNames = [];
				return $this->html = '';
			}
		}
		$this->html = $this->expandTokens($html);
		$this->tokens = $this->tagNames = [];
		return $this->html;
	}

	/*
	 * Context-aware output filtering for one value.
	 * $ctx: body, attr, urlattr, styleattr, textarea, strict, tag (inside a tag but outside
	 * attribute values), optionally prefixed by "unq:" for unquoted attribute values
	 * $kind: value (block data, may be rich text), url (computed URL), plain (generated text)
	 */
	public static function filterValue($value, $ctx, $kind = 'value') {
		if($value === null || is_array($value) || is_object($value)) {
			return '';
		}
		if(is_bool($value)) {
			$value = $value ? '1' : '';
		}
		$value = self::stripControl((string)$value);
		if(strncmp($ctx, 'unq:', 4) === 0) {
			$value = preg_replace('/[\s"\'=<>`]+/', '', $value);
			$ctx = substr($ctx, 4);
		}
		switch($ctx) {
			case 'strict':
				return preg_replace('/[^a-zA-Z0-9_\-]+/', '', $value);
			case 'tag':
				// Never let data start an event handler attribute name.
				return preg_replace('/^(?:on)+/i', '', preg_replace('/[^a-zA-Z0-9_\-]+/', '', $value));
			case 'textarea':
				return self::escapeAttr($value);
			case 'urlattr':
				return self::escapeAttr(self::sanitizeUrl($value));
			case 'styleattr':
				return self::escapeAttr(preg_replace('/[^a-zA-Z0-9#%.,\s\-+]+/', '', $value));
			case 'attr':
				return self::escapeAttr($kind === 'value' ? self::plainText($value) : $value);
			case 'body':
			default:
				return $kind === 'value' ? self::sanitizeRich($value) : dhtmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);
		}
	}

	/*
	 * Escapes text for an attribute value or RCDATA element. The rendered post is later
	 * passed through discuzcode() with HTML enabled, so "[" is encoded as well: BBCode
	 * expanded inside an attribute would inject quotes and markup into it. Browsers decode
	 * &#91; back to "[", so URLs and titles are unchanged.
	 */
	public static function escapeAttr($value) {
		return str_replace('[', '&#91;', dhtmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE));
	}

	/*
	 * Returns the URL unchanged when its scheme is allowed, otherwise an empty string.
	 * Callers must still HTML-escape the result.
	 */
	public static function sanitizeUrl($url) {
		if(!is_scalar($url) || is_bool($url)) {
			return '';
		}
		$url = trim(self::stripControl((string)$url));
		if($url === '') {
			return '';
		}
		$check = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$check = str_replace('\\', '/', preg_replace('/[\x00-\x20\x7f]+/', '', $check));
		if(preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/', $check, $scheme)) {
			$scheme = strtolower($scheme[1]);
			if($scheme === 'data') {
				return preg_match('/^data:image\/(?:png|jpe?g|gif|webp|bmp|avif|x-icon);base64,/i', $check) ? $url : '';
			}
			return in_array($scheme, self::$allowedSchemes, true) ? $url : '';
		}
		return $url;
	}

	/*
	 * Allow-list sanitizer for rich text stored in block data (Editor.js inline HTML).
	 * Output never contains a "<" or ">" that is not part of a rebuilt, allowed tag, nor a
	 * '"' outside an attribute delimiter: like the escaped message of an ordinary post,
	 * text may contain BBCode, and discuzcode() must not be able to place quotes from the
	 * text into the attributes it generates (for example [font=...]).
	 */
	public static function sanitizeRich($value) {
		if($value === null || is_array($value) || is_object($value)) {
			return '';
		}
		$value = self::stripControl((string)$value);
		if(strpos($value, '<') === false && strpos($value, '>') === false) {
			return str_replace('"', '&quot;', $value);
		}
		$value = self::removeDropContentElements(self::removeComments($value));
		$result = preg_replace_callback('/<(\/?)([a-zA-Z][a-zA-Z0-9]*+)((?:[^<>"\']++|"[^"<]*+"|\'[^\'<]*+\')*+)>|[<>"]/', function($m) {
			if(!isset($m[2]) || $m[2] === '') {
				return $m[0] === '<' ? '&lt;' : ($m[0] === '>' ? '&gt;' : '&quot;');
			}
			$tag = strtolower($m[2]);
			if(!in_array($tag, self::$allowedTags, true)) {
				return '';
			}
			if($m[1] === '/') {
				return '</'.$tag.'>';
			}
			return '<'.$tag.self::rebuildAttrs($m[3]).'>';
		}, $value);
		return $result === null ? dhtmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE) : $result;
	}

	private static function stripControl($value) {
		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $value);
	}

	private static function plainText($value) {
		if(strpos($value, '<') !== false) {
			$value = preg_replace('/<\/?[a-zA-Z][^<>]*+>/', '', $value);
		}
		if(strpos($value, '&') !== false) {
			$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		return self::stripControl($value);
	}

	private static function removeComments($value) {
		$out = '';
		$pos = 0;
		while(($start = strpos($value, '<!--', $pos)) !== false) {
			$end = strpos($value, '-->', $start + 4);
			if($end === false) {
				break;
			}
			$out .= substr($value, $pos, $start - $pos);
			$pos = $end + 3;
		}
		return $out.substr($value, $pos);
	}

	private static function removeDropContentElements($value) {
		foreach(self::$dropContentTags as $tag) {
			$lower = strtolower($value);
			if(strpos($lower, '<'.$tag) === false) {
				continue;
			}
			$out = '';
			$pos = $search = 0;
			$length = strlen($tag) + 1;
			while(($start = strpos($lower, '<'.$tag, $search)) !== false) {
				$next = $lower[$start + $length] ?? '';
				if($next !== '' && (ctype_alnum($next) || $next === '-' || $next === ':')) {
					$search = $start + 1;
					continue;
				}
				$close = strpos($lower, '</'.$tag, $start + $length);
				if($close === false) {
					break;
				}
				$closeEnd = strpos($lower, '>', $close);
				$closeEnd = $closeEnd === false ? strlen($lower) : $closeEnd + 1;
				$out .= substr($value, $pos, $start - $pos);
				$pos = $search = $closeEnd;
			}
			$value = $out.substr($value, $pos);
		}
		return $value;
	}

	private static function rebuildAttrs($attrStr) {
		$out = '';
		if(trim($attrStr, " \t\n\r\f/") === '') {
			return $out;
		}
		if(!preg_match_all('/([a-zA-Z_:][a-zA-Z0-9_:.\-]*)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'`=<>]+))?/', $attrStr, $am, PREG_SET_ORDER)) {
			return $out;
		}
		$seen = [];
		foreach($am as $a) {
			$name = strtolower($a[1]);
			if(isset($seen[$name]) || strncmp($name, 'on', 2) === 0) {
				continue;
			}
			$isUrl = in_array($name, self::$richUrlAttrs, true);
			$isData = (strncmp($name, 'data-', 5) === 0 || strncmp($name, 'aria-', 5) === 0) && preg_match('/^[a-z][a-z0-9\-]*$/', $name);
			if(!$isUrl && !$isData && !in_array($name, self::$allowedAttrs, true)) {
				continue;
			}
			$seen[$name] = true;
			if(!isset($a[2]) || $a[2] === '') {
				if(!$isUrl && $name !== 'style') {
					$out .= ' '.$name;
				}
				continue;
			}
			$val = $a[2];
			if($val[0] === '"' || $val[0] === "'") {
				$val = substr($val, 1, -1);
			}
			$val = self::stripControl(html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			if($isUrl) {
				$val = self::sanitizeUrl($val);
			} elseif($name === 'style') {
				$val = self::sanitizeStyle($val);
			} elseif($name === 'class') {
				$val = trim(preg_replace('/[^a-zA-Z0-9_\-\s]+/', '', $val));
			}
			if($val === '' && ($isUrl || $name === 'style' || $name === 'class')) {
				continue;
			}
			$out .= ' '.$name.'="'.self::escapeAttr($val).'"';
		}
		return $out;
	}

	private static function sanitizeStyle($css) {
		$out = [];
		foreach(explode(';', $css) as $declaration) {
			$pos = strpos($declaration, ':');
			if($pos === false) {
				continue;
			}
			$property = strtolower(trim(substr($declaration, 0, $pos)));
			$value = trim(substr($declaration, $pos + 1));
			if($property === 'font-family') {
				$value = trim(str_replace(['"', "'"], '', $value));
			}
			if($value === '' || !in_array($property, self::$allowedStyles, true)) {
				continue;
			}
			if(preg_match('/[\\\\<>{}"\'`@]|\/\*|url\s*\(|image-set|expression|javascript|vbscript|behaviou?r|binding/i', $value)) {
				continue;
			}
			$out[] = $property.': '.$value;
		}
		return implode('; ', $out);
	}

	private function token($value, $kind = 'value') {
		$id = count($this->tokens);
		$this->tokens[$id] = [$kind, $value];
		return self::TOKEN.$id.self::TOKEN;
	}

	private function itemid() {
		return isset($this->context['itemid']) ? intval($this->context['itemid']) : intval(getglobal('tid'));
	}

	// Text of a directive argument, with any data tokens resolved to their raw values.
	private function tokenText($text) {
		return preg_replace_callback('/\x1A(\d+)\x1A/', function($m) {
			$value = $this->tokens[(int)$m[1]][1] ?? '';
			return is_scalar($value) && !is_bool($value) ? (string)$value : '';
		}, trim((string)$text));
	}

	private function expandTokens($html) {
		if(strpos($html, self::TOKEN) === false) {
			return $html;
		}
		$out = '';
		$last = 0;
		foreach($this->scanTokens($html) as [$start, $end, $id, $ctx]) {
			$out .= substr($html, $last, $start - $last).$this->renderToken($id, $ctx);
			$last = $end;
		}
		return str_replace(self::TOKEN, '', $out.substr($html, $last));
	}

	private function renderToken($id, $ctx) {
		if(!isset($this->tokens[$id])) {
			return '';
		}
		[$kind, $value] = $this->tokens[$id];
		if($kind === 'raw') {
			// Pre-rendered markup is only meaningful between elements.
			return $ctx === 'body' ? $value : '';
		}
		if(strncmp($ctx, 'tagname:', 8) === 0) {
			return $this->renderTagName($id, (int)substr($ctx, 8));
		}
		if($kind === 'url') {
			$value = self::sanitizeUrl($value);
		}
		return self::filterValue($value, $ctx, $kind);
	}

	/*
	 * Minimal HTML tokenizer over the template skeleton. Returns every token occurrence
	 * as [start, end, id, context]. Tokens in tag names are checked as a whole name;
	 * anything else inside a tag that is not an attribute value gets the tag context;
	 * script/style/comment content is strict and textarea/title content is escaped.
	 */
	private function scanTokens($html) {
		$occ = [];
		$len = strlen($html);
		$i = 0;
		while($i < $len) {
			$i += strcspn($html, '<'.self::TOKEN, $i);
			if($i >= $len) {
				break;
			}
			if($html[$i] === self::TOKEN) {
				$i = $this->takeToken($html, $i, 'body', $occ);
				continue;
			}
			if(substr($html, $i, 4) === '<!--') {
				$end = strpos($html, '-->', $i + 4);
				$stop = $end === false ? $len : $end + 3;
				$this->collectTokens($html, $i, $stop, 'strict', $occ);
				$i = $stop;
				continue;
			}
			$closing = ($html[$i + 1] ?? '') === '/';
			$j = $i + ($closing ? 2 : 1);
			$first = $html[$j] ?? '';
			if($first === '' || !(ctype_alpha($first) || $first === self::TOKEN)) {
				$i++;
				continue;
			}
			// The tag name may be built from template text and data, e.g. <h{data.level}>.
			$parts = [];
			$tag = '';
			while($j < $len) {
				$n = strspn($html, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789:-', $j);
				if($n > 0) {
					$parts[] = substr($html, $j, $n);
					$tag .= strtolower(substr($html, $j, $n));
					$j += $n;
				} elseif($html[$j] === self::TOKEN && ($end = strpos($html, self::TOKEN, $j + 1)) !== false && ctype_digit($id = substr($html, $j + 1, $end - $j - 1))) {
					$parts[] = (int)$id;
					$occ[] = [$j, $end + 1, (int)$id, 'tagname:'.count($this->tagNames)];
					$j = $end + 1;
				} else {
					break;
				}
			}
			if(count($parts) > 1 || !is_string($parts[0] ?? '')) {
				$this->tagNames[] = $parts;
			}
			$i = $this->scanTag($html, $j, $tag, $closing, $occ);
		}
		return $occ;
	}

	// A tag name containing data is only kept when the complete name is harmless.
	private function renderTagName($id, $group) {
		$name = '';
		foreach($this->tagNames[$group] ?? [] as $part) {
			$name .= is_int($part) ? self::filterValue($this->tokens[$part][1] ?? '', 'strict') : $part;
		}
		if(!in_array(strtolower($name), self::$dynamicTags, true)) {
			return '';
		}
		return self::filterValue($this->tokens[$id][1] ?? '', 'strict');
	}

	private function scanTag($html, $i, $tag, $closing, &$occ) {
		$len = strlen($html);
		$space = " \t\n\r\f";
		$nameStop = $space."/>=\"'<".self::TOKEN;
		while($i < $len) {
			$c = $html[$i];
			if($c === self::TOKEN) {
				$i = $this->takeToken($html, $i, 'tag', $occ);
				continue;
			}
			if($c === '>') {
				$i++;
				if(!$closing && in_array($tag, self::$rawTextTags, true)) {
					$end = stripos($html, '</'.$tag, $i);
					$stop = $end === false ? $len : $end;
					$this->collectTokens($html, $i, $stop, in_array($tag, ['textarea', 'title'], true) ? 'textarea' : 'strict', $occ);
					$i = $stop;
				}
				return $i;
			}
			if(strpos($space."/\"'=<", $c) !== false) {
				$i++;
				continue;
			}
			$n = strcspn($html, $nameStop, $i);
			$attr = strtolower(substr($html, $i, $n));
			$i += $n;
			while($i < $len && $html[$i] === self::TOKEN) {
				// A data-controlled attribute name: its value gets the strict context too.
				$i = $this->takeToken($html, $i, 'tag', $occ);
				$i += strcspn($html, $nameStop, $i);
				$attr = '';
			}
			$i += strspn($html, $space, $i);
			if($i >= $len || $html[$i] !== '=') {
				continue;
			}
			$i++;
			$i += strspn($html, $space, $i);
			$ctx = $this->attrContext($attr);
			if($i < $len && ($html[$i] === '"' || $html[$i] === "'")) {
				$end = strpos($html, $html[$i], $i + 1);
				$stop = $end === false ? $len : $end;
				$this->collectTokens($html, $i + 1, $stop, $ctx, $occ);
				$i = $end === false ? $len : $end + 1;
			} else {
				$n = strcspn($html, $space.'>', $i);
				$this->collectTokens($html, $i, $i + $n, 'unq:'.$ctx, $occ);
				$i += $n;
			}
		}
		return $i;
	}

	private function attrContext($attr) {
		if($attr === '' || strncmp($attr, 'on', 2) === 0 || $attr === 'srcdoc') {
			return 'strict';
		}
		if(in_array($attr, self::$urlAttrs, true)) {
			return 'urlattr';
		}
		if($attr === 'style') {
			return 'styleattr';
		}
		return 'attr';
	}

	private function takeToken($html, $i, $ctx, &$occ) {
		$end = strpos($html, self::TOKEN, $i + 1);
		if($end === false) {
			return strlen($html);
		}
		$id = substr($html, $i + 1, $end - $i - 1);
		if($id === '' || !ctype_digit($id)) {
			return $i + 1;
		}
		$occ[] = [$i, $end + 1, (int)$id, $ctx];
		return $end + 1;
	}

	private function collectTokens($html, $from, $to, $ctx, &$occ) {
		$i = $from;
		while(($i = strpos($html, self::TOKEN, $i)) !== false && $i < $to) {
			$i = $this->takeToken($html, $i, $ctx, $occ);
		}
	}

	private function _codeflask($m, $value = null) {
		$id = self::filterValue($this->_value($m[1], $value), 'strict');
		$language = self::filterValue($this->_value($m[2], $value), 'strict');
		$n_count = $this->_value($m[3], $value);
		$n_count = is_scalar($n_count) ? intval($n_count) : 0;
		$code = $this->_value($m[4], $value);
		$code = is_scalar($code) && !is_bool($code) ? (string)$code : '';
		// The code becomes a JSON string literal without any of < > & ' " ` [ characters,
		// so it cannot end the script and discuzcode() (which turns `...` into <code> and
		// expands BBCode in JSON posts) finds nothing to rewrite.
		$code = str_replace(['`', '['], ['\\'.'u0060', '\\'.'u005B'], json_encode($code, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
		$rand = time().random(5);
		$script = <<<EOF
<script>
			const editorElem$rand = document.getElementById('codeflask-$id');
			var isCollapsed$rand = true;
			const flask$rand = new CodeFlask(editorElem$rand, {
				language: '$language',
				lineNumbers: true,
				styleParent: this.shadowRoot,
				rtl: false,
				readonly: true
			});
			var code$rand = $code;
			flask$rand.addLanguage('$language', Prism.languages['$language']);
			flask$rand.onUpdate((code) => {
				// do something with code here.
				// this will trigger whenever the code
				// in the editor changes.
			});
			// flask.updateCode('');
			// This will also trigger .onUpdate()
			flask$rand.updateCode(code$rand);

			const currentCode$rand = flask$rand.getCode();

		            var coderow$rand = parseInt('$n_count');
			    if (coderow$rand === undefined || coderow$rand !== coderow$rand || coderow$rand === 0) {
				    coderow$rand = flask$rand.lineNumber;
			    }

		            if (coderow$rand < 20) {
		                editorElem$rand.parentElement.style.height = '300px';
				editorElem$rand.style.height = '300px';
		            } else if (coderow$rand >= 20) {
		                editorElem$rand.parentElement.style.height = '500px';
		                editorElem$rand.style.height = '500px';
		            }


			const copyBtn$rand = document.getElementById('codeflask-copy-$id');
			copyBtn$rand.addEventListener('click', function() {
			    try {
			        // 先检查 Clipboard API 是否可用
			        if (navigator.clipboard && navigator.clipboard.writeText) {
			            // 使用 Clipboard API 复制代码
			            navigator.clipboard.writeText(code$rand).then(function() {
			                // 复制成功提示
			                const originalText = copyBtn$rand.innerHTML;
			                copyBtn$rand.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> 已复制';

			                // 一段时间后恢复原文本
			                setTimeout(function() {
			                    copyBtn$rand.innerHTML = originalText;
			                }, 2000);
			            }).catch(function() {
			                // 复制失败处理
			                // 降级方案：使用传统的复制方法
			                fallbackCopyTextToClipboard(code$rand);
			            });
			        } else {
			            // Clipboard API 不可用时直接使用降级方案
			            fallbackCopyTextToClipboard(code$rand);
			        }

			        // 提取降级方案为独立函数
			        function fallbackCopyTextToClipboard(text) {
			            const textArea = document.createElement('textarea');
			            textArea.value = text;
			            textArea.style.position = 'fixed';
			            textArea.style.opacity = '0';
			            document.body.appendChild(textArea);
			            textArea.select();

			            try {
			                document.execCommand('copy');
			                const originalText = copyBtn$rand.innerHTML;
			                copyBtn$rand.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> 已复制';

			                setTimeout(function() {
			                    copyBtn$rand.innerHTML = originalText;
			                }, 2000);
			            } catch (copyErr) {
			            } finally {
			                document.body.removeChild(textArea);
			            }
			        }
			    } catch (err) {
			    }
			});

			const bottomBtn$rand = document.getElementById('codeflask-bottomBtn-$id');
			const Toggle$rand = document.getElementById('codeflask-Toggle-$id');
			bottomBtn$rand.addEventListener('click', function() {
			        if (isCollapsed$rand) {
			            // 展开代码
			            isCollapsed$rand = false;
				    const codeElement$rand = editorElem$rand.querySelector('.codeflask__code');
			            if (codeElement$rand) {
				            const actualHeight$rand = codeElement$rand.scrollHeight + 20;

				            editorElem$rand.parentElement.style.height = actualHeight$rand + 'px';
				            editorElem$rand.style.height = actualHeight$rand + 'px';
			            }
			            bottomBtn$rand.innerHTML = '<button class="editorjs-codeFlask_BottomToggle" title="折叠代码"><span class="toggle-icon">▲</span> 折叠</button>';
			            Toggle$rand.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';
				} else {
			            // 折叠代码
			            isCollapsed$rand = true;
				    if (coderow$rand < 20) {
			                editorElem$rand.parentElement.style.height = '300px';
					editorElem$rand.style.height = '300px';
			            } else if (coderow$rand >= 20) {
			                editorElem$rand.parentElement.style.height = '500px';
			                editorElem$rand.style.height = '500px';
			            }
			            bottomBtn$rand.innerHTML = '<button class="editorjs-codeFlask_BottomToggle expand-mode" title="展开代码" data-empty="false"><span class="toggle-icon">▼</span> 展开</button>';
			            Toggle$rand.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';
				}
			});

			Toggle$rand.addEventListener('click', function() {
				bottomBtn$rand.click();
			});
</script>
EOF;
		return $this->token($script, 'raw');
	}

	/*
	 * 调用示例：[media id,[url data.file.url,data.file.remote,data.file.directory],300,[url data.coverImage.url,data.coverImage.remote,data.coverImage.directory]]
	 */
	private function _media($m, $value = null) {
		$id = self::filterValue($this->_value($m[1], $value), 'strict');
		$url = self::sanitizeUrl($this->tokenText($m[2]));
		$width = intval($m[3]) ?: 300;
		$thumbnail = isset($m[4]) ? self::sanitizeUrl($this->tokenText($m[4])) : '';

		// 验证URL合法性
		if($url === '' || !preg_match('/^(?:https?:\/\/|ftp:\/\/|rtsp:\/\/|mms:\/\/|static\/|data\/)/i', $url)) {
			return $this->token($url, 'plain');
		}

		// 获取文件类型
		$cleanUrl = parse_url($url, PHP_URL_PATH) ?: $url;
		$type = fileext($cleanUrl);

		// 定义常见的图片扩展名
		$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heif', 'heic', 'bmp', 'ico', 'svg'];
		if($thumbnail !== '') {
			$thumbExt = fileext(parse_url($thumbnail, PHP_URL_PATH) ?: $thumbnail);
			if(!in_array($thumbExt, $imageExtensions, true)) {
				$thumbnail = '';
			}
		}

		// 定义音频和视频扩展名数组
		$audio = ['aac', 'flac', 'ogg', 'mp3', 'm4a', 'weba', 'wma', 'mid', 'wav', 'ra', 'ram'];
		$video = ['rm', 'rmvb', 'flv', 'swf', 'asf', 'asx', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4', 'm4v', '3gp', 'ogv', 'webm', 'mov'];

		$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
		// JSON escape for "[" keeps discuzcode() from expanding BBCode inside the script.
		$bracket = '\\'.'u005B';
		$jsUrl = str_replace('[', $bracket, json_encode($url, $jsonFlags));
		$jsThumb = str_replace('[', $bracket, json_encode($thumbnail, $jsonFlags));
		$htmlUrl = self::escapeAttr($url);
		$domid = $type.'_'.$id;
		if(in_array($type, $audio, true)) {
			// 音频类型，高度使用固定值66
			$html = '<ignore_js_op><div id="'.$domid.'" class="media" style="margin-left: auto;margin-right: auto;"><div id="'.$domid.'_container" class="media_container"></div><div id="'.$domid.'_tips" class="media_tips"><a href="'.$htmlUrl.'" target="_blank">'.lang('template', 'parse_av_tips').'</a></div></div><script>detectPlayer("'.$domid.'", "'.$type.'", '.$jsUrl.', "'.$width.'", "66", '.$jsThumb.');</script></ignore_js_op>';
		} elseif(in_array($type, $video, true)) {
			// 视频类型，保持4:3的宽高比
			$height = intval($width * 0.75);
			$html = '<ignore_js_op><div id="'.$domid.'" class="media" style="margin-left: auto;margin-right: auto;"><div id="'.$domid.'_container" class="media_container"></div><div id="'.$domid.'_tips" class="media_tips"><a href="'.$htmlUrl.'" target="_blank">'.lang('template', 'parse_av_tips').'</a></div></div><script>detectPlayer("'.$domid.'", "'.$type.'", '.$jsUrl.', "'.$width.'", "'.$height.'", '.$jsThumb.');</script></ignore_js_op>';
		} else {
			// 未知类型，返回链接
			$html = '<a href="'.$htmlUrl.'" target="_blank">'.$htmlUrl.'</a>';
		}
		return $this->token($html, 'raw');
	}

	private function _object($m, $value = null) {
		if($m[1] !== 'null' && $m[1] !== '') {
			$value = $this->_value($m[1], $value);
		}
		if(is_object($value)) {
			$value = json_decode(json_encode($value), true);
		}
		if(!is_array($value)) {
			return $m[2];
		}
		$t2 = '';
		foreach($value as $ad) {
			if(is_array($ad) || is_object($ad)) {
				$ad = json_encode($ad, JSON_UNESCAPED_UNICODE);
			}
			$t2 .= str_replace('{loopobjectdata}', $this->token($ad), $m[2]);
		}
		return $t2;
	}

	private function _if($m, $value = null) {
		$result = $this->_value($m[1], $value);
		if(!$result) {
			return '';
		}
		if((string)$m[2] !== 'notnull' && (!is_scalar($result) || (string)$result != (string)$m[2])) {
			return '';
		}
		$content = preg_replace_callback(self::rVar, function($m) {
			return $this->_var($m);
		}, $m[3]);
		return !empty($content) ? $content : $m[3];
	}

	private function _url($m, $value = null) {
		$url = $this->_value($m[1], $value);
		$remoteParam = $this->_value($m[2], $value);
		$directoryParam = $this->_value($m[3], $value);

		if(!$url || !is_scalar($url)) {
			return '';
		}
		$url = str_replace(['"', "'", '\\'], '', (string)$url);
		$directoryParam = is_scalar($directoryParam) ? preg_replace('/[^a-zA-Z0-9_\-\/]/', '', (string)$directoryParam) : '';
		// 兼容旧版url格式
		$url = $directoryParam ?
			(($remoteParam ? getglobal('setting/ftp/attachurl') : getglobal('setting/attachurl')).$directoryParam.'/'.$url)
			: (str_contains($url, 'http') ? $url : getglobal('siteurl').$url);
		if(!str_contains($url, 'http')) {
			$url = getglobal('siteurl').$url;
		}
		return $this->token($url, 'url');
	}

	private function _attach($m, $value = null) {
		$aid = $this->_value($m[1], $value);
		$aid = is_scalar($aid) ? intval($aid) : 0;
		if($aid <= 0) {
			return '';
		}
		return $this->token(aidencode($aid, 0, $this->itemid()), 'plain');
	}

	private function _jsfile($m, $value = null) {
		$filename = $m[1];
		$blockType = $this->block['type'] ?? '';
		if(empty($blockType) || empty($this->static) || strpos($this->static, $blockType) === false) {
			return '';
		}

		$path = $this->static . $filename . '?' . getglobal('style/verhash');

		return '<script src="' . $path . '"></script>';
	}

	private function _cssfile($m, $value = null) {
		$filename = $m[1];
		$blockType = $this->block['type'] ?? '';
		if(empty($blockType) || empty($this->static) || strpos($this->static, $blockType) === false) {
			return '';
		}

		$path = $this->static . $filename . '?' . getglobal('style/verhash');

		return '<link rel="stylesheet" href="' . $path . '">';
	}

	private function _script($m, $value = null) {
		$path = $m[1];
		if(str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'ftp://')) {
			return '';
		}
		$path = str_replace('{STATICURL}', getglobal('staticurl'), $path);
		$path = str_replace('{VERHASH}', getglobal('style/verhash'), $path);

		return '<script src="'.$path.'"></script>';
	}

	private function _column($m, $value = null) {
		$result = $this->_value($m[1], $value);
		if(!$result) {
			return '';
		} else {
			return $m[2];
		}
	}

	private function _loop($m) {
		$nodes = $this->_value($m[1]);
		if(!is_array($nodes)) {
			return $m[0];
		}
		$s = '';
		foreach($nodes as $index => $node) {
			$t = preg_replace(self::rLoopIndex, $this->token($index), $m[2]);
			$t = preg_replace_callback(self::rLoopObject, function($m_sub) use ($node) {
				return $this->_object($m_sub, $node);
			}, $t);
			$t = preg_replace_callback(self::rVar, function($m_sub) use ($node) {
				return $this->_var($m_sub, $node);
			}, $t);
			$t = preg_replace_callback(self::rUrl, function($m_sub) use ($node) {
				return $this->_url($m_sub, $node);
			}, $t);
			$t = preg_replace_callback(self::rIf, function($m_sub) use ($node) {
				return $this->_if($m_sub, $node);
			}, $t);
			$s .= preg_replace_callback(self::rColumn, function($m_sub) use ($node) {
				$blocks = $this->_value($m_sub[1], $node);
				if(!is_array($blocks)) {
					return '';
				}
				[$parserData, $styleData] = editor::parser(['blocks' => array_values($blocks)], $this->context);
				return $this->token($parserData.$styleData, 'raw');
			}, $t);
		}
		return $s;
	}


	/**
	 * 完全参数化的无限级递归渲染方法
	 * @param array $data 要处理的数据数组
	 * @param array $config 配置参数数组
	 * @param array $context 上下文数据，用于递归传递
	 * @return string 生成的HTML内容
	 *
	 */
	private function _recursiveRender($data, $config, $context = []) {
		// 验证数据
		if(!is_array($data) || empty($data)) {
			return isset($config['emptyTemplate']) && is_scalar($config['emptyTemplate']) ? (string)$config['emptyTemplate'] : '';
		}

		// 获取配置参数，确保所有必要参数都有默认值
		$config = $this->_normalizeConfig($config);
		$childrenKey = $config['childrenKey'];

		// 转换数字格式的辅助函数
		$formatNumber = function($number, $type) {
			switch($type) {
				case 'lower-roman':
					return strtolower($this->_intToRoman($number));
				case 'upper-roman':
					return $this->_intToRoman($number);
				case 'lower-alpha':
					return chr(96 + $number); // 'a' 是 97
				case 'upper-alpha':
					return chr(64 + $number); // 'A' 是 65
				case 'numeric':
				default:
					return (string)$number;
			}
		};

		// 获取起始数字
		$startNumber = is_scalar($config['startNumber'] ?? null) && intval($config['startNumber']) > 0 ? intval($config['startNumber']) : 1;

		// 渲染所有子项目
		$itemsHtml = '';
		foreach(array_values($data) as $index => $item) {
			// 兼容旧版列表数据（字符串项）
			if(is_scalar($item) && !is_bool($item)) {
				$item = ['content' => (string)$item];
			}
			if(!is_array($item)) {
				continue;
			}
			// 设置当前项的上下文
			$itemContext = array_merge($context, [
				'index' => $index,
				'depth' => isset($context['depth']) ? $context['depth'] + 1 : 0,
				'parent' => $context
			]);

			$currentNumber = $index + $startNumber;

			// 生成层级编号
			if(isset($context['levelNumber'])) {
				// 对于多级编号，只转换最后一部分
				$levelNumber = $context['levelNumber'].'.'.$formatNumber($currentNumber, $config['counterType']);
			} else {
				// 对于第一级，直接转换整个编号
				$levelNumber = $formatNumber($currentNumber, $config['counterType']);
			}
			$itemContext['levelNumber'] = $levelNumber;

			// 如果有子项，递归处理
			$childrenHtml = '';
			if(!empty($item[$childrenKey]) && is_array($item[$childrenKey])) {
				$childrenHtml = $this->_recursiveRender($item[$childrenKey], $config, $itemContext);

				// 如果配置了childrenClass，则为子项容器添加这个类
				if($config['childrenClass'] !== '') {
					$childrenHtml = preg_replace('/^<([a-z][a-z0-9]*)\s*/i', '<$1 class="'.$config['childrenClass'].'" ', $childrenHtml);
				}
			}

			// 替换项目模板中的占位符
			$itemsHtml .= $this->_replacePlaceholders($config['itemTemplate'], $item, $itemContext, [$childrenKey => $childrenHtml]);
		}

		// 组装完整的容器HTML
		return $this->_replacePlaceholders($config['containerTemplate'], [], $context, ['items' => $itemsHtml]);
	}

	/**
	 * 将整数转换为罗马数字
	 * @param int $num 要转换的整数
	 * @return string 罗马数字字符串
	 */
	private function _intToRoman($num) {
		// 确保输入是正整数
		$num = intval($num);
		if($num <= 0) return '0';

		// 罗马数字映射
		$romanMap = [
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1
		];

		$result = '';
		foreach($romanMap as $roman => $value) {
			while($num >= $value) {
				$result .= $roman;
				$num -= $value;
			}
		}
		return $result;
	}

	/**
	 * 替换模板中的占位符（单次扫描，插入的数据不会再被解析）
	 * @param string $template HTML模板
	 * @param array $data 用于替换的数据
	 * @param array $context 上下文数据
	 * @param array $raw 已生成的子级HTML
	 * @return string 替换后的HTML
	 */
	private function _replacePlaceholders($template, $data, $context, $raw = []) {
		return preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)*)\}/', function($m) use ($data, $context, $raw) {
			$key = $m[1];
			if(array_key_exists($key, $raw)) {
				return $raw[$key];
			}
			if($key === 'depth' || $key === 'index') {
				return isset($context[$key]) ? $this->token(intval($context[$key])) : '';
			}
			if($key === 'levelNumber') {
				return isset($context['levelNumber']) ? $this->token(preg_replace('/[^a-zA-Z0-9.\-]/', '', (string)$context['levelNumber'])) : '';
			}
			if(strncmp($key, 'context.', 8) === 0) {
				$contextValue = $context[substr($key, 8)] ?? null;
				return is_scalar($contextValue) ? $this->token($contextValue) : $m[0];
			}
			if(strpos($key, '.') === false) {
				if(is_array($data) && array_key_exists($key, $data) && !is_array($data[$key]) && !is_object($data[$key])) {
					return $this->token($data[$key]);
				}
				return $m[0];
			}
			// 任意嵌套数据，不存在时替换为空字符串
			[$rootKey, $nestedPath] = explode('.', $key, 2);
			if(is_array($data) && isset($data[$rootKey])) {
				$nestedValue = $this->_value($nestedPath, $data[$rootKey]);
				if($nestedValue !== false && !is_array($nestedValue) && !is_object($nestedValue)) {
					return $this->token($nestedValue);
				}
			}
			return '';
		}, (string)$template);
	}

	/**
	 * 规范化配置参数
	 * @param array $config 原始配置
	 * @return array 规范化后的配置
	 */
	private function _normalizeConfig($config) {
		// 确保所有必要的配置项都存在
		$defaultConfig = [
			'childrenKey' => 'items',
			'itemTemplate' => '{content}',
			'containerTemplate' => '{items}',
			'emptyTemplate' => '',
			'separator' => '',
			'childrenClass' => 'cdx-list__item-children', // 默认的children类名
			'counterType' => 'numeric', // 默认的计数器类型
			'start' => 1 // 默认的起始编号
		];

		$config = array_merge($defaultConfig, $config);
		foreach(['itemTemplate', 'containerTemplate', 'emptyTemplate', 'childrenKey', 'childrenClass', 'counterType'] as $key) {
			$config[$key] = is_scalar($config[$key]) ? (string)$config[$key] : $defaultConfig[$key];
		}
		$config['childrenKey'] = preg_replace('/[^a-zA-Z0-9_]/', '', $config['childrenKey']) ?: 'items';
		$config['childrenClass'] = trim(preg_replace('/[^a-zA-Z0-9_\- ]/', '', $config['childrenClass']));
		if(!in_array($config['counterType'], ['numeric', 'lower-roman', 'upper-roman', 'lower-alpha', 'upper-alpha'], true)) {
			$config['counterType'] = 'numeric';
		}
		return $config;
	}

	/*
	 * Placeholders in a [recursive] configuration are resolved against the block:
	 * option values receive the raw value (validated later), templates receive tokens.
	 */
	private function _resolveConfig($config, $value) {
		foreach($config as $key => $option) {
			if(!is_scalar($option)) {
				unset($config[$key]);
				continue;
			}
			if(!is_string($option)) {
				continue;
			}
			if(in_array($key, ['itemTemplate', 'containerTemplate', 'emptyTemplate'], true)) {
				$config[$key] = preg_replace_callback(self::rVar, function($m) use ($value) {
					return $this->_var($m, $value);
				}, $option);
			} else {
				$config[$key] = preg_replace_callback(self::rVar, function($m) use ($value) {
					$v = $this->_value($m[1], $value);
					return $v === false ? $m[0] : (is_scalar($v) && !is_bool($v) ? (string)$v : '');
				}, $option);
			}
		}
		return $config;
	}

	/**
	 * 用于在模板中调用完全参数化递归渲染的方法
	 * 用法: [recursive dataPath,configJson]
	 * configJson格式: {"childrenKey":"items","itemTemplate":"<li>{content}</li>","containerTemplate":"<ul>{items}</ul>"}
	 * 基本使用（使用默认配置）：[recursive data.data.items]
	 * 自定义所有参数（匹配您提供的HTML格式）：[recursive data.data.items,"{\"childrenKey\":\"items\",\"itemTemplate\":\"<li class=\\\"cdx-list__item\\\"><div class=\\\"cdx-list__item-content\\\" contenteditable=\\\"true\\\" data-empty=\\\"false\\\">{content}</div>{items}</li>\",\"containerTemplate\":\"<ol class=\\\"cdx-list cdx-list-ordered\\\" style=\\\"--list-counter-type: numeric;\\\">{items}</ol>\"}"]
	 * 使用不同的数据结构和键名：[recursive data.categories,"{\"childrenKey\":\"subcategories\",\"itemTemplate\":\"<option value=\\\"{id}\\\">{name}</option>{subcategories}\",\"containerTemplate\":\"<select>{items}</select>\"}"]
	 * 使用上下文变量（如索引、深度等）：[recursive data.items,"{\"itemTemplate\":\"<div class=\\\"level-{depth}\\\">{index}: {content}</div>{items}\",\"containerTemplate\":\"<div>{items}</div>\"}"]
	 */
	private function _recursive($m, $value = null) {
		// 解析参数 - 使用正则捕获组直接获取数据
		$dataPath = isset($m[1]) ? trim($m[1]) : '';

		// 获取JSON配置，只使用m[2]捕获组（与正则表达式匹配）
		$configJson = isset($m[2]) ? trim($m[2]) : '{}';

		// 移除配置字符串两端可能存在的引号
		if(strlen($configJson) >= 2 && ($configJson[0] === '"' || $configJson[0] === "'") && substr($configJson, -1) === $configJson[0]) {
			$configJson = substr($configJson, 1, -1);
		}

		// 处理JSON中的转义字符，确保HTML标签正确解析
		$configJson = stripslashes($configJson);

		// 解析配置JSON，并添加错误处理
		$config = json_decode($configJson, true);
		if(!is_array($config)) {
			$config = [];
		}
		$config = $this->_resolveConfig($config, $value);

		// 获取数据
		$data = [];
		if($dataPath !== 'null' && $dataPath !== '') {
			$data = $this->_value($dataPath, $value);
		}

		// 调用递归渲染方法
		return $this->_recursiveRender($data, $config);
	}

	private function _var($m, $value = null) {
		$v = $this->_value($m[1], $value);
		return $v !== false ? $this->token($v) : $m[0];
	}

	private function _value($var, $value = null) {
		$e = explode('.', (string)$var);
		$value = $value ?? $this->block;
		foreach($e as $_v) {
			if(!isset($value[$_v])) {
				return false;
			}
			$value = $value[$_v];
		}
		return $value;
	}
}
