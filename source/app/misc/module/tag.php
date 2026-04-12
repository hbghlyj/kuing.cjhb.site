<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$op = in_array(getgpc('op'), ['search', 'manage', 'set']) ? getgpc('op') : '';

if($op === 'search') {
	$file = appfile('child/tag/search', 'misc');
	if(!$file || !file_exists($file)) {
		showmessage('undefined_action');
	}
	require_once $file;
	echo json_encode(array_column($taglist ?? [], 'tagname'));
	exit;
}

if($op === 'manage') {
	lang('forum/template');
	$file = appfile('child/tag/manage', 'misc');
	if(!$file || !file_exists($file)) {
		showmessage('undefined_action');
	}
	require_once $file;
	include template('forum/tag');
	exit;
}

if($op === 'set') {
	$file = appfile('child/tag/set', 'misc');
	if(!$file || !file_exists($file)) {
		showmessage('undefined_action');
	}
	require_once $file;
	exit('1');
}

$id = array_filter(array_map('intval', explode(',', getgpc('id'))), function($value) {
	return $value > 0;
});
$type = trim((string)getgpc('type'));
$name = trim((string)getgpc('name'));
$page = max(1, intval(getgpc('page')));

if($type === 'countitem') {
	$num = 0;
	if($id) {
		$num = table_common_tagitem::t()->count_by_tagid($id);
	}
	include template('tag/tag');
	exit;
}

$taglang = lang('tag/template', 'tag');

if(!empty($id) || $name !== '') {
	$tpp = 20;
	$start_limit = ($page - 1) * $tpp;

	if($name !== '') {
		$tagname = $name;
		$nameparts = array_map('trim', explode(',', $name));
		$id = [];
		$html_title = [];
		foreach($nameparts as $value) {
			if(!preg_match('/^([\x7f-\xff_-]|\w|\s)+$/', $value) || strlen($value) > 30 || strlen($value) < 3) {
				showmessage('tag_does_not_exist', '', ['tag' => $value]);
			}
			$result = table_common_tag::t()->get_bytagname($value, 'tid');
			if($result) {
				$id[] = $result['tagid'];
				$html_title[] = "<a href=\"misc.php?mod=tag&id={$result['tagid']}\">{$result['tagname']}</a>";
			} else {
				showmessage('tag_does_not_exist', '', ['tag' => $value]);
			}
		}
	} else {
		$tags = table_common_tag::t()->get_byids($id);
		$id_not_exist = array_diff($id, array_column($tags, 'tagid'));
		if(!empty($id_not_exist)) {
			showmessage('tag_does_not_exist', '', ['tag' => implode(',', $id_not_exist)]);
		}
		$tagname = implode(',', array_column($tags, 'tagname'));
		$html_title = [];
		foreach($tags as $tag) {
			if($tag['status'] == 1) {
				showmessage('tag_closed', '', ['tag' => $tag['tagname']]);
			}
			$html_title[] = "<a href=\"misc.php?mod=tag&id={$tag['tagid']}\">{$tag['tagname']}</a>";
		}
	}

	$html_title = implode(', ', $html_title);
	$navtitle = $metakeywords = $metadescription = $tagname ? $taglang.' - '.$tagname : $taglang;

	$showtype = 'thread';
	$tidarray = $threadlist = [];
	$sql_parts = [];
	foreach($id as $tagid) {
		$sql_parts[] = '(SELECT itemid FROM '.DB::table('common_tagitem')." WHERE tagid={$tagid} AND idtype='tid')";
	}
	$sql = implode(' INTERSECT ', $sql_parts);
	$count = DB::result_first("SELECT count(*) FROM ({$sql}) t");
	if($count) {
		$query = DB::fetch_all($sql.' ORDER BY itemid DESC'.DB::limit($start_limit, $tpp));
		foreach($query as $result) {
			$tidarray[$result['itemid']] = $result['itemid'];
		}
		$threadlist = getthreadsbytids($tidarray);
	}
	$multipage = multi($count, $tpp, $page, 'misc.php?mod=tag&id='.implode(',', $id).'&type=thread');

	include template('tag/tagitem');
	exit;
}

$navtitle = $metakeywords = $metadescription = $taglang;
$tpp = 200;
$start_limit = ($page - 1) * $tpp;
$tagarray = [];
$count = table_common_tag::t()->fetch_all_by_status(status: 0, returncount: 1);
$sortby = trim((string)getgpc('sortby'));
$viewtype = 'time';
if(in_array($sortby, ['threadnum', 'related_count'], true)) {
	$viewtype = 'related_count';
} elseif($sortby === 'hot') {
	$viewtype = 'hot';
}
$orderactives[$viewtype] = 'class="a"';

$sql = 'SELECT tag.tagname AS tagname, tag.tagid AS tagid, tag.related_count AS related_count FROM '.DB::table('common_tag').' tag WHERE tag.status=0';
if(in_array($sortby, ['threadnum', 'related_count'], true)) {
	$sql .= ' ORDER BY tag.related_count DESC, tag.tagid DESC';
} elseif($sortby === 'hot') {
	$sql .= ' ORDER BY tag.hot_score DESC, tag.tagid DESC';
} else {
	$sql .= ' ORDER BY tag.tagid DESC';
}
$sql .= DB::limit($start_limit, $tpp);
$query = DB::fetch_all($sql);
foreach($query as $result) {
	$tagarray[] = $result;
}
$multipage = multi($count, $tpp, $page, 'misc.php?mod=tag'.($sortby ? '&sortby='.$sortby : ''));

include template('tag/tag');

function getthreadsbytids($tidarray) {
	global $_G;

	$threadlist = [];
	if(empty($tidarray)) {
		return $threadlist;
	}

	loadcache('forums');
	include_once libfile('function_misc', 'function');
	$fids = [];
	foreach(table_forum_thread::t()->fetch_all_by_tid($tidarray) as $result) {
		if($result['displayorder'] >= 0) {
			if(!isset($_G['cache']['forums'][$result['fid']]['name'])) {
				$fids[$result['fid']][] = $result['tid'];
			} else {
				$result['name'] = $_G['cache']['forums'][$result['fid']]['name'];
			}
			$threadlist[$result['tid']] = procthread($result);
		}
	}
	if(!empty($fids)) {
		foreach(table_forum_forum::t()->fetch_all_by_fid(array_keys($fids)) as $fid => $forum) {
			foreach($fids[$fid] as $tid) {
				$threadlist[$tid]['forumname'] = $forum['name'];
			}
		}
	}

	return $threadlist;
}
