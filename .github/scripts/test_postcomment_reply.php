<?php
// Comment-to-comment replies (MitFrame 677b40b8, fork design): the reply target is a
// transient reply_comment_id, validated server-side and stored as a text prefix;
// forum_postcomment.rpid keeps meaning "reply post that produced a quote summary".
define('IN_DISCUZ', true);
define('TIMESTAMP', 1700000000);
define('FORMHASH', 'formhash');
define('CHARSET', 'utf-8');
define('VERHASH', 'test');
chdir(__DIR__.'/../..');

set_error_handler(function($severity, $message, $file, $line) {
	// The production template compiler destructures optional language arguments without a default.
	if(basename($file) === 'class_template.php' && str_contains($message, 'Undefined array key 1')) return true;
	// Popup renders inline shared editor/seccheck subtemplates that read unrelated settings.
	if(!empty($GLOBALS['LENIENT']) && preg_match('/^(Undefined (array key|variable)|Trying to access array offset)/', $message)) return true;
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
			// "{$var}" inside strings opens with a token but closes with a bare '}'.
			if($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) $depth++;
		} else {
			$code .= $token;
			if($token === '{') { $depth++; $opened = true; }
			if($token === '}' && --$depth === 0 && $opened) break;
		}
	}
	return $code;
}

// Real escaping and comment formatting, lifted from the production sources.
eval(source_block(file_get_contents('source/function/function_core.php'), 'function dhtmlspecialchars('));
eval(str_replace('public function format_postcomment(', 'function format_postcomment(', source_block(file_get_contents('source/class/table/table_forum_postcomment.php'), 'public function format_postcomment(')));
function discuzcode_callback_parseurl_152($matches) { return '<a href="'.$matches[4].'">'.$matches[5].'</a>'; }

$locale = 'EN';
function lang($file, $key = null, $vars = [], $default = null) {
	global $locale;
	$parts = explode('/', $file);
	$name = array_pop($parts);
	$lang = [];
	include 'source/i18n/'.$locale.'/'.($parts ? implode('/', $parts).'/' : '').'lang_'.$name.'.php';
	if($key === null) return $lang;
	$value = $lang[$key] ?? '!'.$key.'!';
	foreach($vars as $k => $v) $value = str_replace('{'.$k.'}', $v, $value);
	return $value;
}

class ShowMessage extends Exception {}
$EMPTY_PHP = tempnam(sys_get_temp_dir(), 'pcr');
file_put_contents($EMPTY_PHP, '<?php');
function libfile($name) {
	return $name === 'function/postcomment' ? 'source/function/function_postcomment.php' : $GLOBALS['EMPTY_PHP'];
}
$RENDERING = false;
function template($file, $id = 0, $dir = '', $returnfile = false) {
	return $GLOBALS['RENDERING'] ? $GLOBALS['EMPTY_PHP'] : 'template/default/'.$file.'.htm';
}
function showmessage($message) { throw new ShowMessage($message); }
function submitcheck() { return true; }
function checkautoclose() { return false; }
function checkflood() { return false; }
function checkmaxperhour() { return false; }
function censor($message) { return $message; }
function cutstr($string, $length) { return mb_substr($string, 0, $length, 'UTF-8'); }
function strexists($haystack, $needle) { return str_contains($haystack, $needle); }
function forumperm($perm) { return $GLOBALS['FORUMPERM']; }
function getgpc($key) { return $_GET[$key] ?? null; }
function getglobal($key) { return $key === 'mobiletpl' ? [2 => 'touch'] : ($GLOBALS['_G'][$key] ?? null); }
function loadcache($name) {}
function update_threadpartake($tid) {}
function threadmodstatus($text) { return [0, 0]; }
function messagecutstr($message, $length) { return mb_substr($message, 0, $length, 'UTF-8'); }
function notification_add($touid, $type, $note, $vars = []) { $GLOBALS['NOTICES'][] = ['uid' => $touid, 'type' => $type, 'note' => $note, 'pid' => $vars['pid'] ?? null]; }
function pusher_trigger_forum($event, $payload, $tab) { $GLOBALS['PUSHES'][] = [$event, $payload, $tab]; }

class DB {
	public static $queries = [];
	public static function table($name) { return 'pre_'.$name; }
	public static function result_first($sql, $args = []) {
		self::$queries[] = [$sql, $args];
		return ['alice' => 7, 'bob' => 8, 'postauthor' => 9, 'sender' => 5][$args[1] ?? ''] ?? false;
	}
}
class table_forum_post {
	public static $posts = [];
	public static function t() { return new self; }
	public function fetch_post($table, $pid) { return self::$posts[intval($pid)] ?? []; }
	public function update_post() {}
}
class table_forum_thread {
	public static function t() { return new self; }
	public function update() {}
	public function fetch_thread($tid) { return intval($tid) === 1 ? $GLOBALS['THREAD'] : []; }
}
class table_forum_postcache {
	public static function t() { return new self; }
	public function delete() {}
}
class table_forum_postcomment {
	public static $rows = [], $inserted = [], $deletedRpids = [];
	public static function t() { return new self; }
	public function fetch($id) { return self::$rows[intval($id)] ?? []; }
	public function insert($data, $return = false) { self::$inserted[] = $data; return 900 + count(self::$inserted); }
	public function count_by_tid() { return 1; }
	public function fetch_standpoint_by_pid() { return ['id' => 0]; }
	public function fetch_all_by_pid_score() { return []; }
	public function update() {}
	public function delete_by_rpid($rpids) { self::$deletedRpids[] = $rpids; }
}

require 'source/function/function_postcomment.php';

$FORUMPERM = true;
$THREAD = ['tid' => 1, 'fid' => 2, 'subject' => 'Subject', 'closed' => 0, 'isgroup' => 0, 'displayorder' => 0, 'readperm' => 0, 'authorid' => 9, 'comments' => 3, 'special' => 0];
table_forum_post::$posts = [
	100 => ['pid' => 100, 'tid' => 1, 'first' => 0, 'authorid' => 9, 'invisible' => 0],
	101 => ['pid' => 101, 'tid' => 1, 'first' => 0, 'authorid' => 9, 'invisible' => 0],
	200 => ['pid' => 200, 'tid' => 2, 'first' => 0, 'authorid' => 9, 'invisible' => 0],
];
$comment = fn($id, $pid, $tid, $authorid, $author, $rpid = 0) => ['id' => $id, 'tid' => $tid, 'pid' => $pid, 'authorid' => $authorid, 'author' => $author, 'rpid' => $rpid, 'comment' => 'c'.$id];
table_forum_postcomment::$rows = [
	11 => $comment(11, 100, 1, 7, 'alice'),
	12 => $comment(12, 101, 1, 7, 'alice'),
	13 => $comment(13, 200, 2, 7, 'alice'),
	14 => $comment(14, 100, 1, 0, ''),
	15 => $comment(15, 100, 1, -1, ''),
	16 => $comment(16, 100, 1, 9, 'postauthor'),
	17 => $comment(17, 100, 1, 5, 'sender'),
	19 => $comment(19, 100, 1, 7, 'br[b]ack[/b]et'),
	30 => $comment(30, 100, 1, 8, 'carol', 500),
	40 => $comment(40, 100, 1, 7, str_repeat('x', 50)),
];

function base_G(array $override = []) {
	return array_replace_recursive([
		'uid' => 5, 'username' => 'sender', 'tid' => 1, 'fid' => 2, 'clientip' => '127.0.0.1', 'remoteport' => 0, 'cookie' => [],
		'member' => ['username' => 'sender'],
		'setting' => ['commentnumber' => 20, 'commentpostself' => 0, 'commentfirstpost' => 1, 'allowpostcomment' => [1, 2], 'floodctrl' => 0],
		'group' => ['allowcommentpost' => 3, 'readaccess' => 10, 'allowreply' => 1, 'maxpostsperhour' => 0, 'allowviewip' => 0, 'allowdelpost' => 0],
		'forum' => ['fid' => 2, 'ismoderator' => 0, 'allowview' => 1, 'viewperm' => '', 'password' => '', 'autoclose' => 0, 'allowreply' => 0, 'replyperm' => ''],
	], $override);
}

// --- Target validation, including access checks.
$post = table_forum_post::$posts[100];
$target = function($id, array $g = [], $p = null, $t = null) use ($post) {
	$GLOBALS['_G'] = base_G($g);
	return postcomment_reply_target($id, $p ?? $post, $t ?? $GLOBALS['THREAD']);
};
check($target(11) === ['id' => 11, 'authorid' => 7, 'author' => 'alice'], 'Valid reply target was not accepted');
check($target('11') === ['id' => 11, 'authorid' => 7, 'author' => 'alice'], 'Numeric string reply target was not accepted');
foreach(['11abc', '-11', '0', '', [11], 9999] as $bad) {
	check($target($bad) === null, 'Malformed or missing reply target accepted: '.json_encode($bad));
}
check($target(12) === null, 'Comment on another post of the thread accepted as reply target');
check($target(13) === null, 'Comment from another thread accepted as reply target');
check($target(13, [], table_forum_post::$posts[200]) === null, 'Post outside the current thread accepted');
check($target(11, ['tid' => 2]) === null, 'Thread other than the current tid accepted');
check($target(14) === null && $target(15) === null, 'Guest comment or score summary accepted as reply target');
check($target(30)['author'] === 'carol', 'Quote-summary comment could not be replied to by its comment ID');
check($target(500) === null, 'Reply-post PID stored in rpid was treated as a comment ID');
check($target(11, ['forum' => ['allowview' => -1]]) === null, 'Forum with view disallowed accepted');
$FORUMPERM = false;
check($target(11, ['forum' => ['allowview' => 0, 'viewperm' => "\t1\t"]]) === null, 'Forum view permission was not enforced');
$FORUMPERM = true;
check($target(11, ['forum' => ['password' => 'secret']]) === null, 'Forum password was not enforced');
check($target(11, ['forum' => ['fid' => 3]]) === null, 'Forum/thread mismatch accepted');
check($target(11, [], null, ['readperm' => 50] + $THREAD) === null, 'Thread read permission was not enforced');
check($target(11, ['forum' => ['ismoderator' => 1]], null, ['readperm' => 50] + $THREAD) !== null, 'Moderator lost access to a read-restricted thread');
check($target(11, [], null, ['displayorder' => -1] + $THREAD) === null, 'Recycled thread accepted');
check($target(11, [], ['invisible' => -2] + $post) === null, 'Unapproved post accepted');
check($target(11, ['forum' => ['ismoderator' => 1]], ['invisible' => -2] + $post) !== null, 'Moderator could not reply on an unapproved post');

// --- Prefix escaping and length budget.
check(postcomment_reply_prefix('alice') === 'Reply @alice: ', 'EN reply prefix changed');
foreach(['SC' => '回复 @alice：', 'TC' => '回覆 @alice：'] as $locale => $expected) {
	check(postcomment_reply_prefix('alice') === $expected, $locale.' reply prefix is not localized');
}
$locale = 'EN';
$hostile = postcomment_reply_prefix('br[b]ack[/b]et [url=https://evil.test]x[/url] [color=red] <img>&"');
check(!preg_match('/[\[\]<>"]/', $hostile) && str_contains($hostile, '&#91;b&#93;') && str_contains($hostile, '&lt;img&gt;&amp;&quot;'), 'Prefix username is not escaped: '.$hostile);
$rendered = format_postcomment($hostile.'hello');
check(!str_contains($rendered, '<b>') && !str_contains($rendered, '<a ') && !str_contains($rendered, '<span'), 'Username BBCode survived formatting: '.$rendered);
check(postcomment_message_limit() === 200 && postcomment_message_limit(postcomment_reply_prefix('alice')) === 200, 'Plain message limit changed');
check(postcomment_message_limit(postcomment_reply_prefix(str_repeat('x', 50))) === 196, 'Long prefix did not reduce the message budget');
check(postcomment_message_limit(str_repeat('y', 300)) === 0, 'Message budget became negative');

// --- Notification recipients.
check(postcomment_notification_recipients(5, 9, 7) === ['reply' => 7, 'post' => 9], 'Reply target and post author were not both notified');
check(postcomment_notification_recipients(5, 9, 9) === ['reply' => 9, 'post' => 0], 'Post author who is the reply target was notified twice');
check(postcomment_notification_recipients(5, 5, 7) === ['reply' => 7, 'post' => 0], 'Own post produced a comment_add notification');
check(postcomment_notification_recipients(5, 9, 5) === ['reply' => 0, 'post' => 9], 'Reply to own comment notified the sender');
check(postcomment_notification_recipients(5, 9) === ['reply' => 0, 'post' => 9], 'Ordinary comment lost its comment_add eligibility');
check(postcomment_notification_recipients(9, 9) === ['reply' => 0, 'post' => 0], 'Own-post comment notified its author');
check(postcomment_notification_recipients(0, 9, 7) === ['reply' => 0, 'post' => 0], 'Guest comment produced notifications');

// --- The real newreply.php comment branch.
$NEWREPLY_COMMENT = source_block(file_get_contents('source/app/forum/child/post/newreply.php'), "if(\$_G['setting']['commentnumber'] && !empty(\$_GET['comment']))");
function submit_comment(array $get, array $g = []) {
	global $_G, $NEWREPLY_COMMENT;
	$_G = base_G($g);
	$_GET = array_merge(['comment' => 'yes', 'pid' => 100, 'message' => '', 'commentitem' => '', 'pusher_tab_id' => 'tab-1'], $get);
	$GLOBALS['NOTICES'] = $GLOBALS['PUSHES'] = DB::$queries = table_forum_postcomment::$inserted = [];
	$thread = $GLOBALS['THREAD'];
	$seccodecheck = $secqaacheck = 0;
	try {
		eval($NEWREPLY_COMMENT);
	} catch(ShowMessage $message) {
		return $message->getMessage();
	}
	return 'no message';
}
$notices = fn() => array_map(fn($n) => $n['note'].':'.$n['uid'], $GLOBALS['NOTICES']);
$stored = fn() => table_forum_postcomment::$inserted[0] ?? null;

check(submit_comment(['message' => 'plain']) === 'comment_add_succeed', 'Plain comment did not succeed');
check($stored()['comment'] === 'plain' && !array_key_exists('rpid', $stored()) && $stored()['pid'] === 100, 'Plain comment storage changed');
check($notices() === ['comment_add:9'], 'Plain comment notifications changed: '.json_encode($notices()));
check($GLOBALS['PUSHES'] === [['commentadd', ['tid' => 1, 'pid' => 100, 'uid' => 5], 'tab-1']], 'Comment Pusher event lost sender tab or payload');

check(submit_comment(['message' => 'hello', 'reply_comment_id' => 11]) === 'comment_add_succeed', 'Valid reply failed');
check($stored()['comment'] === 'Reply @alice: hello' && !array_key_exists('rpid', $stored()) && $stored()['authorid'] === 5, 'Reply was not stored as a prefixed comment without rpid');
check($notices() === ['comment_reply:7', 'comment_add:9'], 'Reply notifications wrong: '.json_encode($notices()));
check($GLOBALS['NOTICES'][0]['pid'] === 100, 'Reply notification does not link the containing post');

submit_comment(['message' => 'to author', 'reply_comment_id' => 16]);
check($notices() === ['comment_reply:9'], 'Post author replied to was notified twice: '.json_encode($notices()));
submit_comment(['message' => 'to self', 'reply_comment_id' => 17]);
check($stored()['comment'] === 'Reply @sender: to self' && $notices() === ['comment_add:9'], 'Reply to own comment notified self');
submit_comment(['message' => 'guest', 'reply_comment_id' => 11], ['uid' => 0, 'username' => '']);
check($stored()['comment'] === 'Reply @alice: guest' && $notices() === [], 'Guest reply produced notifications');

submit_comment(['message' => '@alice @bob thanks', 'reply_comment_id' => 11]);
check($notices() === ['comment_reply:7', 'comment_add:9', 'at_message:8'], 'Mention of the reply target duplicated its notification: '.json_encode($notices()));
check(!in_array('alice:', array_map(fn($q) => $q[1][1], DB::$queries), true), 'Reply prefix was parsed as a mention');
submit_comment(['message' => "@x'OR'1'='1 hi"]);
check(DB::$queries && !str_contains(DB::$queries[0][0], "x'OR") && DB::$queries[0][1][1] === "x'OR'1'='1", 'Mention lookup is not parameterized');

foreach([12 => 'other post', 13 => 'other thread', 14 => 'guest comment', 15 => 'score summary', 500 => 'rpid value', 9999 => 'missing'] as $id => $label) {
	check(submit_comment(['message' => 'x', 'reply_comment_id' => $id]) === 'postcomment_reply_target_invalid' && !$stored() && !$GLOBALS['NOTICES'], 'Invalid target accepted: '.$label);
}
check(submit_comment(['pid' => 200, 'message' => 'x', 'reply_comment_id' => 13]) === 'postcomment_reply_target_invalid', 'Cross-thread post accepted for a reply');
check(submit_comment(['message' => '  ', 'reply_comment_id' => 11]) === 'post_sm_isnull' && !$stored(), 'Empty reply was stored as a bare prefix');

submit_comment(['message' => 'quoted', 'reply_comment_id' => 30]);
check($stored()['comment'] === 'Reply @carol: quoted' && !array_key_exists('rpid', $stored()), 'Reply to a quote summary copied its rpid');

submit_comment(['message' => '<script>[b]x', 'reply_comment_id' => 19]);
check($stored()['comment'] === 'Reply @br&#91;b&#93;ack&#91;/b&#93;et: &lt;script&gt;[b]x', 'Stored reply escaping changed: '.$stored()['comment']);

check(submit_comment(['message' => str_repeat('m', 196), 'reply_comment_id' => 40]) === 'comment_add_succeed' && mb_strlen($stored()['comment']) === 255, 'Reply at the 255-character limit failed');
check(submit_comment(['message' => str_repeat('m', 197), 'reply_comment_id' => 40]) === 'postcomment_too_long' && !$stored(), 'Prefix pushed the comment past 255 characters');
check(submit_comment(['message' => str_repeat('m', 201)]) === 'postcomment_too_long', 'Plain 200-character message limit changed');

// --- The comment popup handler block.
$popup = file_get_contents('source/app/forum/child/misc/comment.php');
$start = strpos($popup, "require_once libfile('function/postcomment');");
$POPUP = substr($popup, $start, strpos($popup, '$extra =', $start) - $start);
$open_popup = function($id) use ($POPUP, $post) {
	global $_G;
	$_G = base_G();
	$_GET = ['reply_comment_id' => $id];
	$thread = $GLOBALS['THREAD'];
	try {
		eval($POPUP);
	} catch(ShowMessage $message) {
		return $message->getMessage();
	}
	return compact('replycomment', 'commentmaxlength', 'commentreplymaxlength');
};
$opened = $open_popup(19);
check($opened['replycomment']['author_html'] === 'br[b]ack[/b]et' && $opened['commentmaxlength'] === 200, 'Popup target lookup changed');
check($open_popup(40)['commentreplymaxlength'] === 196, 'Popup did not budget for the reply prefix');
check($open_popup(12) === 'postcomment_reply_target_invalid', 'Popup accepted a target from another post');

// --- Existing rpid semantics in reply-post quote summaries stay unchanged.
eval('namespace forum; class extend_thread_base { public $param = []; public $thread = []; public $setting = []; public $group = []; public $member = []; public $pid = 0; public $post = []; public $events = []; public function trigger_chat_activity($event, $payload) { $this->events[] = [$event, $payload]; } }');
require 'source/app/forum/extend/extend_thread_comment.php';
table_forum_postcomment::$inserted = [];
$extend = new forum\extend_thread_comment();
$extend->thread = $THREAD;
$extend->setting = ['allowpostcomment' => [1, 2], 'commentpostself' => 0];
$extend->group = ['allowcommentreply' => 1];
$extend->member = ['uid' => 5, 'username' => 'sender'];
$extend->param = ['subject' => '', 'message' => 'quoted reply', 'extramessage' => '', 'isanonymous' => 0, 'repid' => 100];
$extend->pid = 500;
$extend->before_newreply(['modnewreplies' => 0, 'repid' => 100, 'message' => 'quoted reply']);
$extend->after_newreply();
check(count(table_forum_postcomment::$inserted) === 1 && table_forum_postcomment::$inserted[0]['pid'] === 100 && table_forum_postcomment::$inserted[0]['rpid'] === 500, 'Quote summary no longer stores the reply post PID in rpid');
$extend->post = ['pid' => 500];
$extend->after_deletepost();
check(table_forum_postcomment::$deletedRpids === [500], 'Deleting a reply post no longer removes its quote summary by rpid');

// --- The old quote-copy affordance is gone, replaced by the direct reply link.
$js = file_get_contents('static/js/forum_viewthread.js');
$css = file_get_contents('template/default/common/module.css');
check(!str_contains($js, 'initCommentReplyButtons') && !str_contains($js, 'reply-btn') && !str_contains($js, '点评引用已复制'), 'Old comment quote-copy button is still present');
check(preg_match('/function initViewthreadEnhancements\(root\) \{\s*initCodeCopyButton\(root\);\s*initPostZoomAndWheel\(root\);\s*initThreadSubjectDblClick\(\);\s*initMulu\(\);\s*\}/', $js) === 1, 'Viewthread enhancement initialisation changed beyond the removal');
check(str_contains($js, 'function removePostComment(id)') && str_contains($js, "document.getElementById('comment_row_' + id)"), 'Row-only comment deletion changed');
check(!str_contains($css, 'reply-btn') && !preg_match('/\.psti\s*\{\s*position:\s*relative;\s*\}/', $css), 'Old comment quote-copy styles are still present');

// --- Templates compile in every locale and render the reply link only where allowed.
require 'source/class/class_tplfile.php';
require 'source/class/class_template.php';
$templates = ['forum/comment', 'forum/comment_more', 'forum/viewthread_node_body'];
$compiled = [];
// Pre-existing gap unrelated to comments: touch viewthread's popup.open('{lang error}').
$knownMissing = ['touch/forum/viewthread' => ['!error!']];
foreach(['EN', 'SC', 'TC'] as $locale) {
	foreach(array_merge($templates, ['touch/forum/comment', 'touch/forum/comment_more', 'touch/forum/viewthread']) as $file) {
		if(str_starts_with($file, 'touch/') && !defined('IN_MOBILE')) define('IN_MOBILE', 2);
		$code = (new template())->parse_template('template/default/'.$file.'.htm', 1, '', $file);
		token_get_all($code, TOKEN_PARSE);
		preg_match_all('/![a-z_]+!/', $code, $missing);
		$missing = array_diff(array_unique($missing[0]), $knownMissing[$file] ?? []);
		check(!$missing, $locale.' '.$file.': missing language '.implode(', ', $missing));
		check(!str_contains($code, 'rpid=$comment[id]') && !str_contains($code, "rpid=<?php echo \$comment['id']"), $file.' reuses rpid for comment IDs');
		$compiled[$locale][$file] = $code;
	}
}
foreach(['forum/comment_more', 'forum/viewthread_node_body', 'touch/forum/comment_more', 'touch/forum/viewthread'] as $file) {
	check(str_contains($compiled['EN'][$file], 'reply_comment_id=') && str_contains($compiled['EN'][$file], 'cmreply'), $file.' lacks the comment reply link');
}
check(str_contains($compiled['EN']['forum/viewthread_node_body'], 'comment_row_') && str_contains($compiled['EN']['forum/viewthread_node_body'], 'data-local-timestamp') && str_contains($compiled['EN']['forum/viewthread_node_body'], 'delcomment('), 'Viewthread comment rows lost row IDs, localized time or self-delete');

function render($code, array $vars) {
	global $_G;
	$GLOBALS['RENDERING'] = true;
	extract($vars);
	ob_start();
	try {
		eval('?>'.$code);
	} catch(Throwable $e) {
		ob_end_clean();
		throw $e;
	} finally {
		$GLOBALS['RENDERING'] = false;
	}
	return ob_get_clean();
}
$_G = base_G();
$_GET = ['extra' => '', 'from' => ''];
$rows = [
	['id' => 11, 'tid' => 1, 'pid' => 100, 'authorid' => 7, 'author' => 'alice', 'avatar' => '', 'dateline' => 'now', 'comment' => 'Reply @bob: hi', 'rpid' => 0, 'useip' => '', 'port' => 0],
	['id' => 17, 'tid' => 1, 'pid' => 100, 'authorid' => 5, 'author' => 'sender', 'avatar' => '', 'dateline' => 'now', 'comment' => 'mine', 'rpid' => 0, 'useip' => '', 'port' => 0],
	['id' => 30, 'tid' => 1, 'pid' => 100, 'authorid' => 8, 'author' => 'carol', 'avatar' => '', 'dateline' => 'now', 'comment' => 'quoted', 'rpid' => 500, 'useip' => '', 'port' => 0],
	['id' => 14, 'tid' => 1, 'pid' => 100, 'authorid' => 0, 'author' => '', 'avatar' => '', 'dateline' => 'now', 'comment' => 'guest', 'rpid' => 0, 'useip' => '', 'port' => 0],
];
$more = ['comments' => $rows, 'totalcomment' => '', 'count' => 4, 'commentlimit' => 20, 'multi' => '', 'page' => 1];
foreach(['forum/comment_more', 'touch/forum/comment_more'] as $file) {
	$html = render($compiled['EN'][$file], $more + ['commentreplyallowed' => true]);
	preg_match_all('/reply_comment_id=(\d+)/', $html, $links);
	check($links[1] === ['11'], $file.' reply links wrong: '.json_encode($links[1]));
	check(!str_contains(render($compiled['EN'][$file], $more + ['commentreplyallowed' => false]), 'reply_comment_id='), $file.' offered replies without permission');
	if($file === 'forum/comment_more') {
		check(str_contains($html, 'id="comment_row_17"') && str_contains($html, 'delcomment(17,100)'), 'Self-delete or row ID lost in comment_more');
		check(str_contains($html, 'reppost=500') && str_contains($html, 'pid=500&amp;ptid=1'), 'Quote-summary rpid links changed');
	}
}
$popupvars = ['post' => ['tid' => 1], 'extra' => '', 'commentitem' => [], 'seccodecheck' => 0, 'secqaacheck' => 0, 'commentmaxlength' => 200, 'commentreplymaxlength' => 196];
$_GET = ['pid' => 100, 'handlekey' => 'comment', 'infloat' => 'yes'];
$LENIENT = true;
foreach(['forum/comment', 'touch/forum/comment'] as $file) {
	$html = render($compiled['EN'][$file], $popupvars + ['replycomment' => ['id' => 40, 'authorid' => 7, 'author_html' => 'a&amp;b']]);
	check(str_contains($html, 'name="reply_comment_id" id="reply_comment_id" value="40"') && str_contains($html, '@a&amp;b') && str_contains($html, '? 196 : 200'), $file.' reply popup markup wrong');
	$plain = render($compiled['EN'][$file], $popupvars + ['replycomment' => null]);
	check(!str_contains($plain, 'name="reply_comment_id"') && !str_contains($plain, 'reply_comment_notice"'), $file.' plain comment popup shows reply state');
}
check(str_contains(render($compiled['EN']['forum/comment'], $popupvars + ['replycomment' => ['id' => 40, 'authorid' => 7, 'author_html' => 'x']]), '<strong id="checklen">196</strong>'), 'Desktop counter ignores the reply budget');
$LENIENT = false;

unlink($EMPTY_PHP);
echo "Comment reply target, prefix, notification, rpid and template regressions passed\n";
