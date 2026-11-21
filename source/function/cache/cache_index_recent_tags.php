<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_index_recent_tags() {
	global $_G;
	// A shared homepage cache must not rank tags by activity in restricted threads.
	$tags = DB::fetch_all(
		"SELECT tag.tagid, tag.tagname
		FROM %t AS item
		JOIN %t AS tag ON tag.tagid = item.tagid
		JOIN %t AS thread ON thread.tid = item.itemid
		JOIN %t AS forum ON forum.fid = thread.fid
		JOIN %t AS field ON field.fid = forum.fid
		WHERE item.idtype = 'tid' AND tag.status = 0
			AND thread.displayorder >= 0 AND thread.readperm = 0 AND thread.isgroup = 0
			AND forum.status = 1 AND field.viewperm = '' AND field.password = ''
			AND field.formulaperm = '' AND field.price = 0
		GROUP BY tag.tagid, tag.tagname
		ORDER BY MAX(item.created_at) DESC, tag.tagid DESC
		LIMIT 12",
		['common_tagitem', 'common_tag', 'forum_thread', 'forum_forum', 'forum_forumfield']
	);
	$data = ['cachetime' => TIMESTAMP, 'tags' => $tags];
	savecache('index_recent_tags', $data);
	$_G['cache']['index_recent_tags'] = $data;
}
