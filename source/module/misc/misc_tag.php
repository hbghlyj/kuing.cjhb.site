<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: misc_tag.php 32232 2012-12-03 08:57:08Z zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$id = array_filter(array_map('intval', explode(',',$_GET['id'])), function($value) {
	return $value > 0;
});
$type = trim($_GET['type']);
$name = trim($_GET['name']);
$page = intval($_GET['page']);
if($type == 'countitem') {
	$num = 0;
	if($id) {
		$num = C::t('common_tagitem')->count_by_tagid($id);
	}
	include_once template('tag/tag');
	exit();
}
$taglang = lang('tag/template', 'tag');
if(!empty($id) || $name) {
	$tpp = 20;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;
	if($name) {
		$tagname = $name;
		$name = array_map('trim',explode(',', $name));
		$id = array();
		foreach ($name as $value) {
			if(!preg_match('/^([\x7f-\xff_-]|\w|\s)+$/', $value) || strlen($value) > 20) {
				showmessage('tag_does_not_exist', '', array('tag' => $value));
			}
			$result = C::t('common_tag')->get_bytagname($value,'tid');
			if($result) {
				$id[] = $result['tagid'];
			}else{
				showmessage('tag_does_not_exist', '', array('tag' => $value));
			}
		}
	}else{
		$tagnames = array();
		$tags = C::t('common_tag')->get_byids($id);
		$id_not_exist = array_diff($id, array_map(function($value) {
			return $value['tagid'];
		}, $tags));
		if(!empty($id_not_exist)) {
			showmessage('tag_does_not_exist', '', array('tag' => implode(',', $id_not_exist)));
		}
		$tagname = implode(',', array_map(function($value) {
			return $value['tagname'];
		}, $tags));
	}
	foreach($tags as $tag) {
		if($tag['status'] == 1) {
			showmessage('tag_closed', '', array('tag' => $tag['tagname']));
		}
	}
	$navtitle = $tagname ? $taglang.' - '.$tagname : $taglang;
	$metakeywords = $tagname ? $taglang.' - '.$tagname : $taglang;
	$metadescription = $tagname ? $taglang.' - '.$tagname : $taglang;


	$showtype = 'thread';
	$tidarray = $threadlist = array();
	$sql_parts = array();
	foreach($id as $tagid) {
		$sql_parts[] = '(SELECT itemid FROM '.DB::table('common_tagitem').' WHERE tagid='.$tagid.' AND idtype=\'tid\')';
	}
	$sql = implode(' INTERSECT ', $sql_parts);
	$count = DB::result_first("SELECT count(*) FROM ($sql) t");
	if($count) {
		$query = DB::fetch_all($sql . ' ORDER BY itemid DESC' . DB::limit($start_limit, $tpp));
		foreach($query as $result) {
			$tidarray[$result['itemid']] = $result['itemid'];
		}
		$threadlist = getthreadsbytids($tidarray);
	}
	$multipage = multi($count, $tpp, $page, 'misc.php?mod=tag&id='.implode(',',$id).'&type=thread');

	include_once template('tag/tagitem');

} else {
	$navtitle = $metakeywords = $metadescription = $taglang;
	$tpp = 200;
	$page = max(1, intval($page));
	$start_limit = ($page - 1) * $tpp;
	$tagarray = array();
	$count = C::t('common_tag')->fetch_all_by_status(status:0,returncount:1);
	$sql = 'SELECT tag.tagname AS tagname,tag.tagid AS tagid, count(*) AS threadnum FROM '. DB::table('common_tag') .' tag LEFT JOIN '. DB::table('common_tagitem') .' tagitem ON tagitem.tagid=tag.tagid WHERE tag.status=0 AND tagitem.idtype=\'tid\' GROUP BY tagitem.tagid';
	if(isset($_GET['sortby']) && $_GET['sortby'] == 'threadnum') {
		$sql .= ' ORDER BY threadnum DESC';
	}else{
		$sql .= ' ORDER BY tagid DESC';
	}
	$sql .= DB::limit($start_limit, $tpp);
	$query = DB::fetch_all($sql);
	foreach($query as $result) {
		$tagarray[] = $result;
	}
	$multipage = multi($count, $tpp, $page, 'misc.php?mod=tag');
	include_once template('tag/tag');
}

function getthreadsbytids($tidarray) {
	global $_G;

	$threadlist = array();
	if(!empty($tidarray)) {
		loadcache('forums');
		include_once libfile('function_misc', 'function');
		$fids = array();
		foreach(C::t('forum_thread')->fetch_all_by_tid($tidarray) as $result) {
			if($result['displayorder'] >= 0){
				if(!isset($_G['cache']['forums'][$result['fid']]['name'])) {
					$fids[$result['fid']][] = $result['tid'];
				} else {
					$result['name'] = $_G['cache']['forums'][$result['fid']]['name'];
				}
				$threadlist[$result['tid']] = procthread($result);
			}
		}
		if(!empty($fids)) {
			foreach(C::t('forum_forum')->fetch_all_by_fid(array_keys($fids)) as $fid => $forum) {
				foreach($fids[$fid] as $tid) {
					$threadlist[$tid]['forumname'] = $forum['name'];
				}
			}
		}
	}
	return $threadlist;
}

?>