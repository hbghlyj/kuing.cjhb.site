<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_index_recent_tags() {
	global $_G;
	// A shared homepage cache must not rank tags by activity in restricted threads.
	$publicForums = [];
	foreach(DB::fetch_all(
		"SELECT f.fid, field.formulaperm FROM %t AS f JOIN %t AS field ON field.fid = f.fid
		WHERE f.status = 1 AND field.viewperm = '' AND field.password = '' AND field.price = 0",
		['forum_forum', 'forum_forumfield']
	) as $forum) {
		$rules = dunserialize($forum['formulaperm']);
		if(($forum['formulaperm'] === '' || is_array($rules))
			&& empty($rules[1]) && empty($rules['users']) && empty($rules['medal']) && empty($rules['viewtype'])) {
			$publicForums[] = (int)$forum['fid'];
		}
	}
	$tags = $publicForums ? DB::fetch_all(
		"SELECT tag.tagid, tag.tagname
		FROM %t AS item
		JOIN %t AS tag ON tag.tagid = item.tagid
		JOIN %t AS thread ON thread.tid = item.itemid
		WHERE item.idtype = 'tid' AND tag.status = 0
			AND thread.displayorder >= 0 AND thread.readperm = 0 AND thread.isgroup = 0
			AND thread.fid IN (%n)
		GROUP BY tag.tagid, tag.tagname
		ORDER BY MAX(item.created_at) DESC, tag.tagid DESC
		LIMIT 12",
		['common_tagitem', 'common_tag', 'forum_thread', $publicForums]
	) : [];
	$data = ['cachetime' => TIMESTAMP, 'tags' => $tags];
	savecache('index_recent_tags', $data);
	$_G['cache']['index_recent_tags'] = $data;
}
