<?php

define('IN_DISCUZ', true);
define('TIMESTAMP', 1000);

class DB {
	static $db;

	static function fetch_all($sql, $tables) {
		foreach($tables as $table) {
			$sql = is_array($table) ? str_replace('%n', implode(',', $table), $sql) : preg_replace('/%t/', $table, $sql, 1);
		}
		return self::$db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	}
}

function dunserialize($value) {
	return $value === '' ? false : unserialize($value, ['allowed_classes' => false]);
}

function savecache($name, $data) {
	$GLOBALS['savedCache'][$name] = $data;
}

DB::$db = new PDO('sqlite::memory:');
DB::$db->exec("
	CREATE TABLE common_tagitem (tagid, itemid, idtype, created_at);
	CREATE TABLE common_tag (tagid INTEGER PRIMARY KEY, tagname, status);
	CREATE TABLE forum_thread (tid INTEGER PRIMARY KEY, fid, displayorder, readperm, isgroup);
	CREATE TABLE forum_forum (fid INTEGER PRIMARY KEY, status);
	CREATE TABLE forum_forumfield (fid INTEGER PRIMARY KEY, viewperm, password, formulaperm, price);
	INSERT INTO forum_forum VALUES (1, 1), (2, 1), (3, 1);
	INSERT INTO forum_forumfield VALUES (1, '', '', '', 0), (2, '', 'secret', '', 0), (3, '', '', '', 0);
	INSERT INTO forum_thread VALUES (1, 1, 0, 0, 0), (2, 1, 0, 0, 0),
		(3, 1, -1, 0, 0), (4, 2, 0, 0, 0), (5, 1, 0, 10, 0), (6, 1, 0, 0, 1);
	INSERT INTO common_tag VALUES (1, 'Old tag reused', 0), (2, 'Second', 0),
		(3, 'Same time', 0), (4, 'New unused tag', 0), (5, 'Disabled tag', 1);
	INSERT INTO common_tagitem VALUES (1, 1, 'tid', 1), (1, 2, 'tid', 900),
		(2, 1, 'tid', 800), (3, 2, 'tid', 800), (5, 1, 'tid', 999),
		(2, 3, 'tid', 999), (2, 4, 'tid', 999), (2, 5, 'tid', 999),
		(2, 6, 'tid', 999), (2, 1, 'blogid', 999);
");
$setRules = DB::$db->prepare('UPDATE forum_forumfield SET formulaperm = ? WHERE fid = ?');
$setRules->execute([serialize(['', '', 'users' => '', 'viewtype' => 0]), 1]);
$setRules->execute([serialize(['users' => 'members-only']), 3]);
DB::$db->exec("INSERT INTO forum_thread VALUES (7, 3, 0, 0, 0);
	INSERT INTO common_tagitem VALUES (2, 7, 'tid', 999)");
require __DIR__.'/../../source/function/cache/cache_index_recent_tags.php';
build_cache_index_recent_tags();
$cache = $_G['cache']['index_recent_tags'];
if($cache['cachetime'] !== TIMESTAMP || $cache !== $savedCache['index_recent_tags']
	|| array_column($cache['tags'], 'tagid') !== [1, 3, 2]) {
	throw new RuntimeException('Recent tags cache did not rank unique public thread associations by latest use');
}
echo "Recently used index tags cache passed\n";
