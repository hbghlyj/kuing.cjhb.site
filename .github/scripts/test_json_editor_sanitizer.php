<?php
// JSON Editor block rendering (MitFrame 9bf00903 sanitizer, fork rewrite): every built-in
// block keeps rendering, block data is filtered for the HTML context it lands in, and data
// can never be re-read as template directives. Also covers the retired RAW block (badcea04)
// and the Editor.js / editorjs-undo bundle provenance.
define('IN_DISCUZ', true);
define('CHARSET', 'utf-8');
define('TIMESTAMP', 1700000000);
chdir(__DIR__.'/../..');
$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? '';

set_error_handler(function($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});

function check($condition, $message) {
	if(!$condition) throw new RuntimeException($message);
}

function source_block($source, $needle) {
	$start = strpos($source, $needle);
	check($start !== false, 'Missing source block: '.$needle);
	$code = '';
	$depth = 0;
	$opened = false;
	foreach(token_get_all('<?php '.substr($source, $start)) as $token) {
		if(is_array($token)) {
			if($token[0] !== T_OPEN_TAG) $code .= $token[1];
			if($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) $depth++;
		} else {
			$code .= $token;
			if($token === '{') { $depth++; $opened = true; }
			if($token === '}' && --$depth === 0 && $opened) break;
		}
	}
	return $code;
}

// Real helpers lifted from the production sources.
$core = file_get_contents('source/function/function_core.php');
foreach(['dhtmlspecialchars', 'fileext', 'aidencode', 'random'] as $function) {
	eval(source_block($core, 'function '.$function.'('));
}

$_G = [
	'uid' => 0, 'username' => '', 'adminid' => 0, 'fid' => 2, 'tid' => 7,
	'siteurl' => 'https://bbs.test/',
	'setting' => ['attachurl' => 'data/attachment/', 'ftp' => ['attachurl' => 'https://ftp.test/'], 'anchorparse' => 'paragraph,header'],
	'style' => ['verhash' => 'vh'],
	'config' => ['security' => ['authkey' => 'authkey']],
];
function getglobal($key) {
	global $_G;
	$value = $_G;
	foreach(explode('/', $key) as $part) {
		if(!is_array($value) || !array_key_exists($part, $value)) return null;
		$value = $value[$part];
	}
	return $value;
}
function lang($file, $key, $vars = []) { return '['.$key.']'; }
$MEMORY = [];
function memory($cmd, $key = '', $value = '') {
	global $MEMORY;
	if($cmd === 'get') return $MEMORY[$key] ?? false;
	if($cmd === 'set') { $MEMORY[$key] = $value; return true; }
	return false;
}
class table_common_editorblock {
	static $rows = [];
	static function t() { static $instance; return $instance ??= new self(); }
	function fetch_by_block_class($class) { return self::$rows[$class] ?? false; }
}
class table_forum_post {
	static $replies = [];
	static function t() { static $instance; return $instance ??= new self(); }
	function fetch_pid_by_tid_authorid($tid, $uid) { return self::$replies[$tid.':'.$uid] ?? null; }
}

// Register the shipped block templates exactly as AdminCP stores them.
foreach(glob('source/class/editorblock/editorblock_*.php') as $file) {
	require_once $file;
	$class = basename($file, '.php');
	$block = new $class();
	$parser = $block->getParser();
	if(trim($parser) !== '') {
		table_common_editorblock::$rows[substr($class, 12)] = ['parser' => $parser, 'style' => $block->getStyle(), 'plugin' => ''];
	}
}
require 'source/class/class_editor.php';

function render(array $blocks) {
	global $MEMORY;
	$MEMORY = [];
	[$html] = editor::parser(json_encode(['blocks' => $blocks]));
	return $html;
}

function paragraph($text, $extra = []) {
	return array_replace_recursive(['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text, 'alignment' => 'left']], $extra);
}

function paragraph_inner($html) {
	check(preg_match('/<div class="ce-paragraph cdx-block ce-paragraph--left">(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $html, $m) === 1, 'Paragraph wrapper missing: '.$html);
	return $m[1];
}

// Every tag must be canonical: lower-case attribute names, double-quoted values without
// quotes or angle brackets, and no event handlers except the fork's anchor scroll helper.
function assert_safe($html, $label, $allowScripts = false, $templateTags = []) {
	// Elements that only block templates may contribute (SVG icons, embed iframe, RAW textarea).
	$templateTags = array_merge(['svg'], $templateTags);
	$offset = 0;
	while(preg_match('/<(\/?)([a-zA-Z][a-zA-Z0-9]*)/', $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
		$start = $m[0][1];
		$tag = strtolower($m[2][0]);
		$end = $start + strlen($m[0][0]);
		$quote = '';
		for(; $end < strlen($html); $end++) {
			$c = $html[$end];
			if($quote !== '') { if($c === $quote) $quote = ''; continue; }
			if($c === '"' || $c === "'") { $quote = $c; continue; }
			if($c === '>') break;
		}
		$attrs = substr($html, $start + strlen($m[0][0]), $end - $start - strlen($m[0][0]));
		if($tag === 'script' && $m[1][0] === '') {
			check($allowScripts, "$label: unexpected <script>");
			$close = stripos($html, '</script', $end);
			check($close !== false, "$label: unterminated script");
			$offset = $close + 1;
			continue;
		}
		check(!in_array($tag, ['iframe', 'object', 'embed', 'svg', 'math', 'style', 'base', 'meta', 'link', 'form', 'textarea', 'input', 'frame', 'template'], true)
			|| in_array($tag, $templateTags, true), "$label: unexpected <$tag>");
		$attrs = rtrim($attrs, " \t\r\n/");
		$rest = preg_replace('/\s+[a-zA-Z_:][a-zA-Z0-9_:.\-]*(?:="[^"<>]*")?/', '', $attrs);
		check(trim($rest) === '', "$label: non-canonical attributes in <$tag$attrs>");
		preg_match_all('/\s([a-zA-Z_:][a-zA-Z0-9_:.\-]*)(?:="([^"]*)")?/', $attrs, $am, PREG_SET_ORDER);
		foreach($am as $a) {
			$name = strtolower($a[1]);
			$value = html_entity_decode($a[2] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if(str_starts_with($name, 'on')) {
				check($name === 'onclick' && (preg_match("/^javascript: document\.getElementById\('[a-zA-Z0-9_\-]+'\)\.scrollIntoView\(\);return false;$/", $value) || $value === "showWindow('reply', this.href)"), "$label: event handler $name=$value");
			}
			if(in_array($name, ['href', 'src', 'srcset', 'action', 'formaction', 'data', 'poster', 'background', 'cite', 'xlink:href'], true)) {
				$plain = strtolower(preg_replace('/[\x00-\x20]+/', '', $value));
				check(!preg_match('/^(javascript|vbscript|data:(?!image\/(png|gif|jpe?g|webp);base64,))/', $plain), "$label: dangerous URL $name=$value");
			}
			if($name === 'style') {
				check(!preg_match('/(^|[;\s])(position|top|left|z-index|behavior|-moz-binding)\s*:|expression\s*\(|url\s*\(|javascript\s*:|@import|[\\\\]/i', $value), "$label: dangerous style $value");
			}
			check($name !== 'srcdoc', "$label: srcdoc attribute");
		}
		$offset = $end;
	}
}

$renderStart = microtime(true);

// --- 1. Supported blocks keep their rendering -------------------------------------------
$rich = '<b>Bold</b> <i>it</i> <u class="cdx-underline">u</u> <a href="https://example.com/?a=1&amp;b=2" target="_blank" rel="nofollow">link</a> '
	.'<mark class="cdx-marker">m</mark> <code class="inline-code">c</code> <span style="color: rgb(255, 0, 0);">red</span> <font color="#ff0000">f</font> '
	.'x<sup>2</sup>y<sub>i</sub> $a&lt;b$ and \\(x &gt; y\\)&nbsp;$$\\frac{a}{b}$$ [tikz]\\draw (0,0) -- (1,1);[/tikz] `code` {data.text} [b]bb[/b]<br>';
$expected = str_replace('<span style="color: rgb(255, 0, 0);">', '<span style="color: rgb(255, 0, 0)">', $rich);
$html = render([paragraph($rich)]);
check(paragraph_inner($html) === $expected, "Paragraph rich text changed:\n".paragraph_inner($html));
check(str_contains($html, 'data-id="p1"'), 'Block id missing');
assert_safe($html, 'paragraph');

$html = render([['id' => 'h1', 'type' => 'header', 'data' => ['text' => 'Title <i>x</i>', 'level' => 2, 'alignment' => 'center'], 'tunes' => ['anchorTune' => ['anchor' => 'sec-1']]]]);
check(str_contains($html, '<h2 class="ce-header ce-header--center">Title <i>x</i></h2>'), 'Header rendering changed: '.$html);
check(str_contains($html, 'id="sec-1"'), 'Header anchor lost: '.$html);
assert_safe($html, 'header');

$html = render([paragraph('<a href="#sec-1">go</a> <a href="#x\');alert(1)//">bad</a>')]);
check(str_contains($html, '<a href="#sec-1" onclick="javascript: document.getElementById(\'sec-1\').scrollIntoView();return false;">go</a>'), 'Anchor scrolling lost: '.$html);
check(substr_count($html, 'onclick=') === 1, 'Anchor helper added to an unsafe fragment: '.$html);
assert_safe($html, 'anchors');

$list = ['type' => 'list', 'data' => ['style' => 'ordered', 'meta' => ['counterType' => 'upper-roman', 'start' => 3], 'items' => [
	['content' => 'One <b>b</b>', 'meta' => [], 'items' => [['content' => 'Sub', 'meta' => [], 'items' => []]]],
	['content' => 'Two', 'meta' => [], 'items' => []],
]]];
$html = render([$list]);
check(str_contains($html, '<ol class="cdx-list-ordered" style="list-style-type:none; margin-left: -15px;"><li class="cdx-list__item">III. One <b>b</b><ol class="cdx-list__item-children" class="cdx-list-ordered" style="list-style-type:none; margin-left: -15px;"><li class="cdx-list__item">III.III. Sub</li></ol></li><li class="cdx-list__item">IV. Two</li></ol>'), 'Ordered list changed: '.$html);
assert_safe($html, 'ordered list');
$html = render([['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['legacy <i>a</i>', 'b']]]]);
check(str_contains($html, '<ul class="cdx-list-unordered"><li class="cdx-list__item">legacy <i>a</i></li><li class="cdx-list__item">b</li></ul>'), 'Legacy string list items not rendered: '.$html);
$html = render([['type' => 'list', 'data' => ['style' => 'checklist', 'items' => [['content' => 'Done', 'meta' => ['checked' => true], 'items' => []], ['content' => 'Todo', 'meta' => ['checked' => false], 'items' => []]]]]]);
check(str_contains($html, 'cdx-list__checkbox--checked-1"') && str_contains($html, 'cdx-list__checkbox--checked-"') && str_contains($html, '<svg xmlns="http://www.w3.org/2000/svg"'), 'Checklist list changed: '.$html);
check(str_contains($html, 'contenteditable="true" data-empty="false">Done</div>'), 'Checklist list content lost: '.$html);
assert_safe($html, 'checklist list');

$html = render([['type' => 'checklist', 'data' => ['items' => [['text' => 'Do <b>it</b>', 'checked' => true], ['text' => 'Not', 'checked' => false]]]]]);
check(substr_count($html, 'cdx-checklist__item--checked') === 1 && str_contains($html, '>Do <b>it</b></div>') && str_contains($html, '>Not</div>'), 'Checklist block changed: '.$html);

$html = render([['type' => 'table', 'data' => ['content' => [['<b>a</b>', 'b &amp; c'], ['3', '<img src=x onerror=alert(1)>']]]]]);
check(str_contains($html, '<div class="tc-cell"><b>a</b></div>') && str_contains($html, '<div class="tc-cell">b &amp; c</div>') && str_contains($html, '<div class="tc-cell"><img src="x"></div>'), 'Table rendering changed: '.$html);
assert_safe($html, 'table');

$html = render([['id' => 'img1', 'type' => 'image', 'data' => ['file' => ['url' => '202601/01/abc.jpg', 'aid' => 12, 'remote' => 0, 'directory' => 'forum'], 'caption' => 'A &amp; <b>B</b>', 'withBorder' => true]]]);
check(str_contains($html, 'id="aimg_img1"') && str_contains($html, 'src="https://bbs.test/data/attachment/forum/202601/01/abc.jpg"'), 'Image source changed: '.$html);
check(str_contains($html, 'title="A &amp; B" alt="A &amp; B" data-aid="12"') && str_contains($html, '>A &amp; <b>B</b></div>') && str_contains($html, 'image-tool--withBorder'), 'Image caption/attributes changed: '.$html);
assert_safe($html, 'image');
$html = render([['type' => 'image', 'data' => ['file' => ['url' => 'https://cdn.test/a.png?x=1&y=2'], 'caption' => '']]]);
check(str_contains($html, 'src="https://cdn.test/a.png?x=1&amp;y=2"'), 'Remote image URL changed: '.$html);

$html = render([['type' => 'attaches', 'data' => ['file' => ['aid' => '34'], 'title' => 'doc <b>1</b>.pdf']]]);
check(preg_match('/href="forum\.php\?mod=attachment&aid=([A-Za-z0-9%]+)"/', $html, $am) === 1, 'Attachment link missing: '.$html);
check(str_starts_with(base64_decode(rawurldecode($am[1])), '34|'), 'Attachment id not encoded');
check(str_contains($html, 'data-placeholder="doc 1.pdf"') && str_contains($html, '>doc <b>1</b>.pdf</a>'), 'Attachment title changed: '.$html);
assert_safe($html, 'attaches');
$html = render([['type' => 'attaches', 'data' => ['file' => ['aid' => '0 OR 1'], 'title' => 't']]]);
check(str_contains($html, 'aid=" target'), 'Non-numeric attachment id produced a link: '.$html);

$html = render([['type' => 'quote', 'data' => ['text' => 'Q <i>t</i>', 'caption' => 'C', 'alignment' => 'left']]]);
check(str_contains($html, 'Q <i>t</i>') && str_contains($html, 'cdx-quote__caption-left'), 'Quote changed: '.$html);
$html = render([['type' => 'delimiter', 'data' => ['style' => 'line', 'lineThickness' => '2', 'lineWidth' => '60']]]);
check(str_contains($html, '<hr class="ce-delimiter-thickness-2" style="width: 60%;">'), 'Delimiter changed: '.$html);

$html = render([['type' => 'embed', 'data' => ['service' => 'youtube', 'source' => 'https://youtu.be/x', 'embed' => 'https://www.youtube.com/embed/x?a=1&b=2', 'width' => 580, 'height' => 320, 'caption' => 'cap']]]);
check(str_contains($html, 'height="320"') && str_contains($html, 'style="width: 580;" src="https://www.youtube.com/embed/x?a=1&amp;b=2"') && str_contains($html, 'https://youtu.be/x'), 'Embed changed: '.$html);
assert_safe($html, 'embed block', false, ['iframe']);

$html = render([['type' => 'video', 'data' => ['file' => ['url' => 'https://cdn.test/v.mp4'], 'caption' => 'V', 'controls' => true]]]);
check(str_contains($html, 'src="https://cdn.test/v.mp4"') && str_contains($html, ' controls ') && str_contains($html, 'title="V"'), 'Video changed: '.$html);
$html = render([['type' => 'audio', 'data' => ['file' => ['url' => 'https://cdn.test/a.mp3'], 'coverImage' => ['url' => 'https://cdn.test/c.jpg'], 'caption' => 'Song']]]);
check(str_contains($html, '<h3 class="audio-title">Song</h3>') && str_contains($html, 'src="https://cdn.test/c.jpg"') && str_contains($html, 'src="https://cdn.test/a.mp3"'), 'Audio changed: '.$html);
assert_safe($html, 'audio');

$html = render([['id' => 'col', 'type' => 'columns', 'data' => ['cols' => [
	['blocks' => [paragraph('Left')]],
	['blocks' => [paragraph('<img src=x onerror=alert(1)>Right')]],
]]]]);
check(str_contains($html, 'editorjs_col_0') && str_contains($html, 'editorjs_col_1') && str_contains($html, '>Left</div>') && str_contains($html, '<img src="x">Right'), 'Columns changed: '.$html);
assert_safe($html, 'columns', false, ['style']);

$html = render([['id' => 'raw1', 'type' => 'raw', 'data' => ['html' => '</textarea><script>alert(1)</script><b>x</b>']]]);
check(str_contains($html, '&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;&lt;b&gt;x&lt;/b&gt;'), 'Raw block not escaped: '.$html);
check(substr_count(strtolower($html), '<textarea') === 1 && substr_count(strtolower($html), '</textarea') === 1, 'Raw block broke out of its textarea');
assert_safe($html, 'raw block', false, ['textarea']);

// Code blocks: the code is a JSON string literal that nothing can end or rewrite.
check(editorBlock::filterValue('javascript:alert(/http/)', 'urlattr', 'url') === '', 'Computed URLs bypassed the scheme allow-list');
$code = "const s = `\${a}`;\n\\`;alert(1);// </script><script>alert(2)</script> &lt;kept&gt; &amp; 'q' \"d\" \\n [i]x[/i] \u{2028}";
$html = render([['id' => 'cf_1', 'type' => 'codeflask', 'data' => ['language' => 'javascript', 'length' => 3, 'code' => $code]]]);
check(substr_count($html, '<script') === 2, 'Codeflask scripts changed: '.$html);
check(str_contains($html, "getElementById('codeflask-cf_1')") && str_contains($html, "getElementById('codeflask-copy-cf_1')") && str_contains($html, "language: 'javascript'"), 'Codeflask wiring changed');
check(preg_match('/var code(\w+) = ("[^"]*");\r?\n/', $html, $cm) === 1, 'Codeflask literal missing');
$literal = $cm[2];
check(strpbrk(substr($literal, 1, -1), "<>&'\"`[\n") === false, 'Code literal contains characters discuzcode() or HTML could act on: '.$literal);
check(json_decode($literal) === $code, 'Code not preserved: '.$literal);
preg_match('/<script>(.*?)<\/script>/s', $html, $script);
check(strpbrk(str_replace("Prism.languages['javascript']", '', $script[1]), '`[') === false, 'Codeflask script contains backticks or BBCode brackets');
assert_safe($html, 'codeflask block', true);
if(trim((string)@shell_exec('node --version')) !== '') {
	preg_match_all('/<script>(.*?)<\/script>/s', $html, $scripts);
	$tmp = tempnam(sys_get_temp_dir(), 'cf').'.js';
	file_put_contents($tmp, implode("\n", $scripts[1]));
	exec('node --check '.escapeshellarg($tmp).' 2>&1', $out, $status);
	@unlink($tmp);
	check($status === 0, 'Codeflask script is not valid JavaScript: '.implode("\n", $out));
}

// Hide tune.
$hidden = paragraph('secret', ['tunes' => ['hideTune' => ['hide' => true]]]);
$html = render([$hidden]);
check(str_contains($html, '<div class="locked">[guest][post_hide_reply_hidden_text]<a href="forum.php?mod=post&action=reply&fid=2&tid=7"') && !str_contains($html, 'secret'), 'Hidden block shown to guest');
$_G['uid'] = 5; $_G['username'] = '<img src=x onerror=alert(1)>';
$html = render([$hidden]);
check(!str_contains($html, 'secret') && str_contains($html, '&lt;img src=x onerror=alert(1)&gt;'), 'Locked message username not escaped: '.$html);
assert_safe($html, 'locked message');
table_forum_post::$replies['7:5'] = 99;
$html = render([$hidden]);
check(str_contains($html, '<div class="showhide"><h4>[post_hide]</h4>') && str_contains($html, '>secret</div>'), 'Hidden block not shown after reply');
$_G['uid'] = 0; $_G['username'] = '';

// --- 2. Rich text sanitizer ---------------------------------------------------------------
$vectors = [
	'<img src=x onerror=alert(1)>',
	'<script>alert(1)</script>',
	'<SCRIPT SRC=//evil.test/x.js></SCRIPT>',
	'<img src=x onerror=alert(1) x="<b>hi</b>',
	'<img src=x onerror=alert(1) x=\'<b>hi</b>',
	'<a href="javascript:alert(1)">a</a>',
	'<a href="JaVaScRiPt:alert(1)">a</a>',
	'<a href="&#106;avascript:alert(1)">a</a>',
	'<a href="&#0000106avascript:alert(1)">a</a>',
	'<a href="java&#x09;script:alert(1)">a</a>',
	"<a href=\"java\tscript:alert(1)\">a</a>",
	'<a href=" javascript:alert(1)">a</a>',
	'<a href="javascript&colon;alert(1)">a</a>',
	'<a href="vbscript:msgbox(1)">a</a>',
	'<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">a</a>',
	'<img src="data:image/svg+xml;base64,PHN2Zz4=">',
	'<svg onload=alert(1)>',
	'<svg><script>alert(1)</script></svg>',
	'<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>',
	'<iframe srcdoc="<script>alert(1)</script>"></iframe>',
	'<details open ontoggle=alert(1)>',
	'<b/onmouseover=alert(1)>x</b>',
	'<img/src="x"/onerror=alert(1)>',
	'<b <img src=x onerror=alert(1)>>',
	'<a title="<img src=x onerror=alert(1)>">t</a>',
	'<b title="x"onmouseover="alert(1)">t</b>',
	'<span style="position:fixed;top:0;left:0;width:100%;height:100%;background:url(javascript:alert(1))">o</span>',
	'<span style="color:red;x:expression(alert(1))">e</span>',
	'<span style="color:\\72 ed;background-image:url(//evil.test/p.png)">e</span>',
	'<!--<img src=x onerror=alert(1)>-->',
	'<!-- unterminated <img src=x onerror=alert(1)>',
	'<!--><img src=x onerror=alert(1)>-->',
	'</script><script>alert(1)</script>',
	'<style>*{display:none}</style><b>x</b>',
	'<textarea><img src=x onerror=alert(1)></textarea>',
	'<input autofocus onfocus=alert(1)>',
	'<form action="javascript:alert(1)"><button>go</button></form>',
	'<a href="#" style="behavior:url(x.htc)">b</a>',
	"<img src=x\x00 onerror=alert(1)>",
	'<p id="fastpostform" class="x\' onclick=">clobber</p>',
	'<<script>script>alert(1)<</script>/script>',
];
foreach($vectors as $index => $vector) {
	$html = render([paragraph($vector)]);
	assert_safe($html, "vector #$index");
	$inner = paragraph_inner($html);
	check(!preg_match('/<(script|iframe|svg|math|style|textarea|input|form|object|embed)\b/i', $inner), "vector #$index: dangerous element survived: $inner");
}
check(paragraph_inner(render([paragraph('<span style="position:fixed;top:0;width:100%;background-image:url(x);color:red;font-family:\'Times New Roman\'">s</span>')]))
	=== '<span style="width: 100%; color: red; font-family: Times New Roman">s</span>', 'Style allow-list changed');
check(paragraph_inner(render([paragraph('<p id="fastpostform" class="ok x">c</p>')])) === '<p class="ok x">c</p>', 'Rich text kept a clobbering id');
check(paragraph_inner(render([paragraph('<img src=x onerror=alert(1) x="<b>hi</b>')])) === '&lt;img src=x onerror=alert(1) x=&quot;<b>hi</b>', 'Unbalanced-quote tag was not neutralised');
check(paragraph_inner(render([paragraph('He said "hi" & left \'now\' $f\'(x)$')])) === 'He said &quot;hi&quot; & left \'now\' $f\'(x)$', 'Text quotes not escaped like an ordinary post');

// URL allow-list.
foreach(['https://a.test/x?y=1&z=2', 'http://a.test', '//cdn.test/x.png', '/forum.php?mod=viewthread', 'forum.php?a=b:c', '#top', 'mailto:a@b.test', 'tel:+123', 'magnet:?xt=urn:btih:abc', 'data:image/png;base64,iVBOR'] as $url) {
	check(editorBlock::sanitizeUrl($url) === $url, "Allowed URL rejected: $url");
}
foreach(['javascript:alert(1)', ' JAVASCRIPT:alert(1)', "java\nscript:alert(1)", "\x01javascript:alert(1)", '&#x6A;avascript:alert(1)', 'javascript&colon;alert(1)', 'vbscript:x', 'data:text/html,<script>', 'data:image/svg+xml;base64,PHN2Zz4=', 'livescript:x', 'file:///etc/passwd', 'chrome://settings', 'blob:https://a.test/x'] as $url) {
	check(editorBlock::sanitizeUrl($url) === '', "Dangerous URL allowed: $url");
}

// --- 3. Template-level injection --------------------------------------------------------
// Data must never be re-read as directives or placeholders.
$directives = [
	'[url data.text,data.alignment,data.alignment]http<b>x</b>',
	'[recursive data.items,"{\\"itemTemplate\\":\\"\\\\u003cimg src=x onerror=alert(1)\\\\u003e\\"}"]',
	'[media id,javascript:alert(1),300]',
	'[attach data.text]',
	'[if data.text=notnull]{data.text}[/if]',
	'[codeflask id,id,id,data.text]',
	'[jsfile x.js][cssfile x.css]',
	'[loop data.items]{text}[/loop][loopobject null]{loopobjectdata}[/loopobject][loopindex]',
	'{id} {data.alignment} {tunes.anchorTune.anchor}',
];
foreach($directives as $index => $text) {
	$html = render([paragraph($text, ['data' => ['items' => [['text' => '<img src=x onerror=alert(1)>']]]])]);
	assert_safe($html, "directive #$index");
	check(paragraph_inner($html) === editorBlock::sanitizeRich($text), "directive #$index was interpreted: ".paragraph_inner($html));
}
// Second-order: an anchor naming another field stays literal inside the attribute.
$html = render([['type' => 'header', 'data' => ['text' => '<b title="onmouseover=alert(1) x">t</b>', 'level' => 3, 'alignment' => 'left'], 'tunes' => ['anchorTune' => ['anchor' => '{data.text}']]]]);
check(str_contains($html, 'id="{data.text}"'), 'Anchor placeholder was expanded: '.$html);
assert_safe($html, 'second-order anchor');

$attacks = [
	'header level' => ['type' => 'header', 'data' => ['text' => 't', 'level' => '1 onmouseover=alert(1) x', 'alignment' => 'left" onclick="alert(1)']],
	'header script tag' => ['type' => 'header', 'data' => ['text' => 'alert(1)', 'level' => '1><script', 'alignment' => 'left']],
	'anchor' => paragraph('a', ['tunes' => ['anchorTune' => ['anchor' => 'x" onfocus="alert(1)" autofocus="']]]),
	'block id' => ['id' => '"><img src=x onerror=alert(1)>', 'type' => 'paragraph', 'data' => ['text' => 'a', 'alignment' => 'left']],
	'list config' => ['type' => 'list', 'data' => ['style' => 'ordered', 'meta' => ['counterType' => 'numeric","itemTemplate":"<img src=x onerror=alert(1)>', 'start' => '1"}]<img src=x onerror=alert(1)>'], 'items' => [['content' => 'c', 'items' => []]]]],
	'list checked' => ['type' => 'list', 'data' => ['style' => 'checklist', 'items' => [['content' => '<img src=x onerror=alert(1)>', 'meta' => ['checked' => 'x" onmouseover="alert(1)'], 'items' => []]]]],
	'list object keys' => ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['a" onclick="x' => ['content' => 'k', 'items' => ['b' => 'nested']]]]],
	'columns keys' => ['type' => 'columns', 'data' => ['cols' => ['x" onmouseover="alert(1)' => ['blocks' => [paragraph('c')]]]]],
	'checklist' => ['type' => 'checklist', 'data' => ['items' => [['text' => '<svg onload=alert(1)>', 'checked' => '1" onclick="alert(1)']]]],
	'image' => ['type' => 'image', 'data' => ['file' => ['url' => 'javascript:alert(1)//http', 'aid' => '" onload="alert(1)'], 'caption' => '" onerror="alert(1)']],
	'image directory' => ['type' => 'image', 'data' => ['file' => ['url' => 'a.jpg"onerror="alert(1)', 'directory' => '../../"><script>'], 'caption' => 'c']],
	'embed' => ['type' => 'embed', 'data' => ['embed' => 'javascript:alert(1)', 'source' => '<script>alert(1)</script>', 'width' => '100%;position:fixed;top:0', 'height' => '1" onload="alert(1)', 'caption' => 'c']],
	'embed data' => ['type' => 'embed', 'data' => ['embed' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==', 'width' => '1', 'height' => '1', 'caption' => 'c']],
	'video' => ['type' => 'video', 'data' => ['file' => ['url' => 'javascript:alert(1)//http'], 'caption' => 'x" autoplay onplay="alert(1)']],
	'delimiter' => ['type' => 'delimiter', 'data' => ['style' => 'line" onclick="alert(1)', 'lineThickness' => '"><script>', 'lineWidth' => '1;background:url(javascript:alert(1))']],
	'table' => ['type' => 'table', 'data' => ['content' => [['{loopobjectdata}', ['<img src=x onerror=alert(1)>']]]]],
];
foreach($attacks as $label => $block) {
	$html = render([$block]);
	assert_safe($html, $label, false, ['iframe', 'style']);
}
check(str_contains(render([$attacks['header level']]), '<h class="ce-header ce-header--left&quot; onclick=&quot;alert(1)">'), 'Unexpected header level fallback');
check(!str_contains(render([$attacks['header script tag']]), '<h1script'), 'Header level produced an unexpected element');
check(str_contains(render([$attacks['list config']]), '<ol class="cdx-list-ordered" style="list-style-type:none; margin-left: -15px;"><li class="cdx-list__item">1. c</li></ol>'), 'List config injection changed the list');

// Codeflask parameters cannot leave their JavaScript strings.
$html = render([['id' => "x');alert(1);//", 'type' => 'codeflask', 'data' => ['language' => "js');alert(1);//", 'length' => "1');alert(1);//", 'code' => 'c']]]);
check(str_contains($html, "getElementById('codeflask-xalert1')") && str_contains($html, "language: 'jsalert1'") && str_contains($html, "parseInt('1')"), 'Codeflask parameters not filtered: '.$html);
assert_safe($html, 'codeflask block', true);

// Every HTML context a custom (plugin or AdminCP-edited) template can use.
table_common_editorblock::$rows['custom'] = ['plugin' => '', 'style' => '', 'parser' => '<div data-id="{id}" onclick="{data.a}" {data.b}="1" style="{data.c}"><{data.d}>x</{data.d}><a href={data.e}>l</a>'
	."<script>var v='{data.f}';</script><textarea>{data.g}</textarea><!-- {data.h} --><img src=\"[url data.u,data.r,data.dir]\" alt='{data.i}'>"
	.'[media id,[url data.m,data.r,data.dir],320,[url data.t,data.r,data.dir]]<p title={data.j}>{data.k}</p></div>'];
$customData = ['a' => 'alert(1)', 'b' => 'onmouseover', 'c' => 'color:red;background:url(javascript:alert(1))', 'd' => 'script', 'e' => 'javascript:alert(1)',
	'f' => "';alert(1);//", 'g' => '</textarea><script>alert(1)</script>', 'h' => '--><script>alert(1)</script><!--', 'u' => 'javascript:alert(1)//http',
	'i' => "' onerror='alert(1)", 'm' => 'https://a.test/x.mp3?"onload="alert(1)</script>', 't' => 'javascript:alert(1)//x.jpg', 'j' => 'x onmouseover=alert(1)', 'k' => '<b>ok</b>'];
$html = render([['id' => 'c1', 'type' => 'custom', 'data' => $customData]]);
check(str_contains($html, 'onclick="alert1"') && str_contains($html, ' mouseover="1"') && str_contains($html, 'style="colorredbackgroundurljavascriptalert1"'), 'Attribute contexts not filtered: '.$html);
check(str_contains($html, '<>x</>') && str_contains($html, '<a href=>l</a>') && str_contains($html, "var v='alert1';"), 'Tag/URL/script contexts not filtered: '.$html);
// Strict values keep "-" but can never contain ">", so they cannot close the comment.
check(str_contains($html, '<textarea>&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;</textarea>') && str_contains($html, '<!-- --scriptalert1script-- -->'), 'RCDATA/comment contexts not filtered: '.$html);
check(str_contains($html, '<img src="" alt=\'&#039; onerror=&#039;alert(1)\'>') && str_contains($html, '<p title=xonmouseoveralert(1)><b>ok</b></p>'), 'URL/quoted/unquoted contexts not filtered: '.$html);
$u = '\\'.'u00';
// A scheme-less thumbnail is resolved against siteurl by [url], so it stays an https path.
check(str_contains($html, 'detectPlayer("mp3_c1", "mp3", "https://a.test/x.mp3?onload=alert(1)'.$u.'3C/script'.$u.'3E", "320", "66", "https://bbs.test/javascript:alert(1)//x.jpg");'), 'Media script arguments not encoded: '.$html);
check(substr_count(strtolower($html), '<script') === 2 && substr_count(strtolower($html), '</script') === 2, 'Custom template gained or lost scripts: '.$html);

// Block types are restricted before reaching caches or static paths.
check(render([['type' => '../../evil', 'data' => []], ['type' => 'raw<script>', 'data' => ['html' => 'x']], ['type' => ['paragraph'], 'data' => []], 'junk', ['data' => []]]) === '', 'Invalid block types rendered');
check(editor::parser('not json') === ['', ''] && editor::parser('{"blocks":"x"}') === ['', ''] && editor::parser('') === ['', ''], 'Malformed content not tolerated');

// Pathological input stays linear and never trips the PCRE limits.
foreach([str_repeat('<a "', 20000), str_repeat('<!--<script>', 8000), str_repeat('<b ', 30000), str_repeat('<a title="', 20000), str_repeat('<<<>>>', 20000)] as $index => $payload) {
	$start = microtime(true);
	$html = render([paragraph($payload)]);
	check(microtime(true) - $start < 3, "Pathological input #$index too slow");
	check(!preg_match('/<(a|b|script)\b/i', paragraph_inner($html)), "Pathological input #$index left markup");
}

// --- 3b. viewthread passes the rendered blocks through discuzcode() with HTML on -------------
define('HOOKTYPE', 'hookscript');
$_G['setting']['plugins'] = [];
$_G['setting']['allowattachurl'] = 0;
class helper_access { static function check_module($module) { return false; } }
function parsesmiles($message) { return $message; }
function parseimg($width, $height, $src, $pid, $extra = '') { return '<img src="'.$src.'"'.($extra ? ' '.$extra : '').'>'; }
function bbcodeurl($url, $tags) { return str_replace('{url}', $url, $tags); }
$discuzcode = file_get_contents('source/function/function_discuzcode.php');
foreach(['function discuzcode(', 'function discuzcode_callback_parseurl_152(', 'function parseurl('] as $needle) {
	eval(source_block($discuzcode, $needle));
}
function view($blocks) {
	// Same flags as viewthread.php for JSON posts (smilies off for the test).
	return discuzcode(render($blocks), 1, 0, 1, 0, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 1, 1);
}
$html = view([paragraph('[font=x" onmouseover="alert(1)]t[/font] [b]bold[/b] [color=red]r[/color]')]);
assert_safe($html, 'bbcode font');
check(str_contains($html, '<span style="font-family:x&quot; onmouseover=&quot;alert(1)">t</span>') && str_contains($html, '<strong>bold</strong>') && str_contains($html, '<span style="color:red">r</span>'), 'BBCode in JSON text changed: '.$html);
foreach([
	'image url' => ['type' => 'image', 'data' => ['file' => ['url' => 'https://cdn.test/a.png?[url=x onmouseover=alert(1)//]t[/url]'], 'caption' => '[url=x onmouseover=alert(1)//]c[/url]']],
	'rich href' => paragraph('<a href="http://x.test/?[url=y onmouseover=alert(1)//]t[/url]" title="[b]t[/b]">l</a>'),
	'anchor' => paragraph('a', ['tunes' => ['anchorTune' => ['anchor' => '[url=x onfocus=alert(1)//]t[/url]']]]),
	'raw' => ['type' => 'raw', 'data' => ['html' => '[url=x]</textarea><script>alert(1)</script>[/url]']],
] as $label => $block) {
	$html = view([$block]);
	assert_safe($html, "bbcode $label", false, ['textarea']);
	check(!preg_match('/="[^"]*<a /', $html), "bbcode $label: BBCode expanded inside an attribute: $html");
}
check(str_contains(view([['type' => 'image', 'data' => ['file' => ['url' => 'https://cdn.test/a[1].png'], 'caption' => 'c']]]), 'src="https://cdn.test/a&#91;1].png"'), 'Bracket in image URL not encoded');
// The code block script survives discuzcode() untouched (inline `code` and BBCode included).
$code = '$a[i] = "[url=x]y[/url]"; echo `ls` . `pwd`; [b]b[/b]';
$html = view([['id' => 'cf', 'type' => 'codeflask', 'data' => ['language' => 'php', 'length' => 1, 'code' => $code]]]);
check(preg_match('/var code(\w+) = ("[^"]*");\r?\n/', $html, $cm) === 1 && json_decode($cm[2]) === $code, 'discuzcode() rewrote a code block: '.$html);
check(!str_contains($html, '<code>') && !str_contains($html, '<strong>'), 'discuzcode() rewrote the code block script');
// TikZ/Asymptote typed in the JSON editor reach the renderers as plain source.
$tikz = view([paragraph('[tikz]\node at (0,0) {"a&lt;b" \&amp; c};<br>  \draw (0,0) -- (1,1); % note[/tikz]')]);
check(preg_match('#<img src="//i\.upmath\.me/svgb/([A-Za-z0-9_-]+)" data-tikz-code="([^"]*)">#', $tikz, $tm) === 1, 'TikZ image missing: '.$tikz);
check(gzinflate(base64_decode(strtr($tm[1], '-_', '+/'))) === "\\node at (0,0) {\"a<b\" \\& c};\n\\draw (0,0) -- (1,1);", 'TikZ source not decoded: '.gzinflate(base64_decode(strtr($tm[1], '-_', '+/'))));
check(rawurldecode($tm[2]) === "\\node at (0,0) {\"a<b\" \\& c};\n  \\draw (0,0) -- (1,1); % note", 'TikZ dialog source not decoded');
$asy = view([paragraph('[asy]label("$A$", (0,0)); // p&amp;q[/asy]')]);
check(str_contains($asy, '<img src="/asy/?code='.rawurlencode('label("$A$", (0,0));').'" data-tikz-code="'.rawurlencode('label("$A$", (0,0)); // p&q').'">'), 'Asymptote source not decoded: '.$asy);
check(str_contains(discuzcode('[tikz]a &amp; "b"[/tikz]', 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0), rtrim(strtr(base64_encode(gzdeflate('a &amp; "b"')), '+/', '-_'), '=')), 'Ordinary BBCode TikZ changed');

// --- 4. Retired RAW block (badcea04) --------------------------------------------------------
$raw = new editorblock_raw();
check(version_compare($raw->version, '1.0.3', '>'), 'RAW block version not bumped for the AdminCP update');
check(preg_match('/class:\s*RawTool,\s*toolbox:\s*false,/', $raw->getConfig()) === 1, 'RAW block still offered in the toolbox');
check(is_file('static/js/editorjs/tools/raw/raw.js'), 'RAW tool script removed; saved RAW blocks would become uneditable');
$rawRow = table_common_editorblock::$rows['raw'];
unset(table_common_editorblock::$rows['raw']);
$html = render([['id' => 'r"x', 'type' => 'raw', 'data' => ['html' => '<script>alert(1)</script>']], ['type' => 'mystery', 'data' => ['text' => 'x']]]);
check($html === '<div class="ce-block" data-id="rx"><div class="ce-block__content"><pre class="ce-rawtool-legacy"><code>&lt;script&gt;alert(1)&lt;/script&gt;</code></pre></div></div>', 'Unregistered RAW fallback changed: '.$html);
table_common_editorblock::$rows['raw'] = $rawRow;

// --- 5. Editor.js bundles ---------------------------------------------------------------------
$bundle = str_replace("\r\n", "\n", file_get_contents('static/js/editorjs/editorjs.umd.js'));
check(hash('sha256', $bundle) === '2eb726ea7fd85055be6c9b236b1276631867dab8c55bf16459ea2595d9be47db', 'editorjs.umd.js is not the npm @editorjs/editorjs@2.31.7 dist file');
$undo = str_replace("\r\n", "\n", file_get_contents('static/js/editorjs/tools/editorjs-undo/editorjs-undo.js'));
check(hash('sha256', $undo) === 'cf0eee59c98a637ab80174cc1b5b055d045f0d3870608775e2066bcb993e6206', 'editorjs-undo.js differs from the reviewed MitFrame 9bf00903 build');
check(str_contains($undo, 'key:"setTextContent",value:function(t,e){if(t&&void 0!==t.innerText){t.innerText=e;'), 'Undo setTextContent is missing or not text-only');
check(substr_count($undo, 'this.setTextContent(') === 2 && !str_contains($undo, '.innerHTML='), 'Undo bundle writes HTML');

echo 'JSON editor sanitizer: blocks preserved, contexts filtered, RAW retained, bundles verified ('.round(microtime(true) - $renderStart, 2)."s)\n";
