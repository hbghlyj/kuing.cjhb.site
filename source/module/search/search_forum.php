<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: search_forum.php 33198 2013-05-06 09:23:45Z jeffjzhang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
define('NOROBOT', TRUE);

if(!$_G['setting']['search']['forum']['status']) {
	showmessage('search_forum_closed');
}

if(in_array($_G['adminid'], array(0, -1)) && !($_G['group']['allowsearch'] & 2)) {
	showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
}

$_G['setting']['search']['forum']['searchctrl'] = intval($_G['setting']['search']['forum']['searchctrl']);

require_once libfile('function/forumlist');
require_once libfile('function/forum');
require_once libfile('function/post');
loadcache(array('forums', 'posttable_info'));
$posttableselect = '';
if(!empty($_G['cache']['posttable_info']) && is_array($_G['cache']['posttable_info'])) {
	$posttableselect = '<select name="seltableid" id="seltableid" class="ps" style="display:none">';
	foreach($_G['cache']['posttable_info'] as $posttableid => $data) {
		$posttableselect .= '<option value="'.$posttableid.'"'.($_GET['posttableid'] == $posttableid ? ' selected="selected"' : '').'>'.($data['memo'] ? $data['memo'] : 'post_'.$posttableid).'</option>';
	}
	$posttableselect .= '</select>';
}

$srchmod = 2;

$cachelife_time = 300;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching

$fulltext = $_GET['fulltext'] == 1 ? 1 : 0;
$Aa = $_GET['Aa'] == 1 ? 1 : 0;
$searchid = isset($_GET['searchid']) ? intval($_GET['searchid']) : 0;
$seltableid = intval(getgpc('seltableid'));

$srchtxt = trim(getgpc('srchtxt'));
$srchuname = isset($_GET['srchuname']) ? trim(str_replace('|', '', $_GET['srchuname'])) : '';;
$srchtag = getgpc('srchtag');
$srchfrom = intval(getgpc('srchfrom'));
$before = intval(getgpc('before'));
$srchfid = getgpc('srchfid');
$srhfid = intval($_GET['srhfid']);
$logicalconnective = getgpc('logicalconnective');
if(!in_array($logicalconnective, array('and', 'or', 'exact', 'regexp'))) {
	$logicalconnective = 'and';
}

$keyword = isset($srchtxt) ? trim($srchtxt) : '';

$forumselect = forumselect();
if(!empty($srchfid) && !is_numeric($srchfid)) {
	$forumselect = str_replace('<option value="'.$srchfid.'">', '<option value="'.$srchfid.'" selected="selected">', $forumselect);
}

if(!submitcheck('searchsubmit', 1)) {
	$keyword = dhtmlspecialchars($keyword);
	if(getgpc('adv')) {
		include template('search/forum_adv');
	} else {
		include template('search/forum');
	}

} else {
	$orderby = in_array(getgpc('orderby'), array('dateline', 'replies', 'views')) ? $_GET['orderby'] : 'lastpost';
	$ascdesc = isset($_GET['ascdesc']) && $_GET['ascdesc'] == 'asc' ? 'asc' : 'desc';
	$orderbyselected = array($orderby => 'selected="selected"');
	$ascchecked = array($ascdesc => 'checked="checked""');

	if(!empty($searchid)) {

		require_once libfile('function/misc');

		$page = max(1, intval(getgpc('page')));
		$start_limit = ($page - 1) * $_G['tpp'];

		$index = C::t('common_searchindex')->fetch_by_searchid_srchmod($searchid, $srchmod);
		if(!$index) {
			showmessage('search_id_invalid');
		}

		$keyword = $index['keywords'];

		$index['keywords'] = rawurlencode($index['keywords']);
		$searchstring = explode('|', $index['searchstring']);
		$index['searchtype'] = $searchstring[0];//preg_replace("/^([a-z]+)\|.*/", "\\1", $index['searchstring']);
		$searchstring[2] = base64_decode($searchstring[2]);
		$srchuname = $searchstring[4];
		$srchtag = base64_decode($searchstring[3]);
		$modfid = 0;
		if($keyword) {
			$modkeyword = str_replace(' ', ',', $keyword);
			$fids = explode(',', str_replace('\\\'', '', $searchstring[5]));
			foreach ($fids as $srchfid) {
				if(!empty($srchfid) ) {
					$forumselect = str_replace('<option value="'.$srchfid.'">', '<option value="'.$srchfid.'" selected="selected">', $forumselect);
				}
			}
			if(count($fids) == 1 && in_array($_G['adminid'], array(1,2,3))) {
				$modfid = $fids[0];
				if($_G['adminid'] == 3 && !C::t('forum_moderator')->fetch_uid_by_fid_uid($modfid, $_G['uid'])) {
					$modfid = 0;
				}
			}
		}
		$threadlist = $posttables = array();
		foreach(C::t('forum_thread')->fetch_all_by_tid_fid_displayorder(explode(',',$index['ids']), null, 0, $orderby, $start_limit, $_G['tpp'], '>=', $ascdesc) as $thread) {
			$thread['subject'] = bat_highlight($thread['subject'], $keyword);
			$thread['realtid'] = $thread['isgroup'] == 1 ? $thread['closed'] : $thread['tid'];
			$threadlist[$thread['tid']] = procthread($thread, 'dt');
			$posttables[$thread['posttableid']][] = $thread['tid'];
		}
		if($threadlist) {
			foreach($posttables as $tableid => $tids) {
				foreach(C::t('forum_post')->fetch_all_by_tid($tableid, $tids, true, '', 0, 0, 1) as $post) {
					if($post['status'] & 1) {
						$threadlist[$post['tid']]['message'] = lang('forum/template', 'message_single_banned');
					} else {
						$threadlist[$post['tid']]['message'] = bat_highlight(threadmessagecutstr($threadlist[$post['tid']], dhtmlspecialchars($post['message']), 200), $keyword);// kk add dhtmlspecialchars()
					}
				}
			}

		}
		$multipage = multi($index['num'], $_G['tpp'], $page, "search.php?mod=forum&searchid=$searchid&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes");

		$url_forward = 'search.php?mod=forum&'.$_SERVER['QUERY_STRING'];

		$fulltextchecked = $searchstring[1][0] == 1 ? 'checked="checked"' : '';
		$Aachecked = $searchstring[1][1] == 1 ? 'checked="checked"' : '';

		$srchfrom = $searchstring[6];
		$before = $searchstring[7];
		$logicalconnective = $searchstring[8];
		$specials = explode(',', $searchstring[9]);
		foreach($specials as $key) {
			$specialchecked[$key] = 'checked="checked""';
		}
		$logicalconnectivechecked[$logicalconnective] = ' checked="checked"';
		$advextra = '&orderby='.$orderby.'&ascdesc='.$ascdesc.'&searchid='.$searchid.'&searchsubmit=yes';
		$keyword = dhtmlspecialchars($keyword);
		if($_GET['adv']) {
			include template('search/forum_adv');
		} else {
			include template('search/forum');
		}

	} else {


		if($_G['group']['allowsearch'] & 32 && $fulltext) {
			periodscheck('searchbanperiods');
		}

		$forumsarray = array();
		if(!empty($srchfid)) {
			foreach((is_array($srchfid) ? $srchfid : explode('_', $srchfid)) as $forum) {
				if($forum = intval(trim($forum))) {
					$forumsarray[] = $forum;
				}
			}
		}

		$fids = $comma = '';
		foreach($_G['cache']['forums'] as $fid => $forum) {
			if($forum['type'] != 'group' && (!$forum['viewperm'] && $_G['group']['readaccess']) || ($forum['viewperm'] && forumperm($forum['viewperm']))) {
				if(!$forumsarray || in_array($fid, $forumsarray)) {
					$fids .= "$comma'$fid'";
					$comma = ',';
				}
			}
		}

		$special = getgpc('special');
		$specials = $special ? implode(',', $special) : '';

		$searchstring = 'forum|'.$fulltext.$Aa.'|'.base64_encode($srchtxt).'|'.base64_encode($srchtag).'|'.$srchuname.'|'.addslashes($fids).'|'.$srchfrom.'|'.$before.'|'.$logicalconnective.'|'.$specials;
		$searchindex = array('id' => 0, 'dateline' => '0');

		foreach(C::t('common_searchindex')->fetch_all_search($_G['setting']['search']['forum']['searchctrl'], $_G['clientip'], $_G['uid'], $_G['timestamp'], $searchstring, $srchmod) as $index) {
			if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
				$searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
				break;
			} elseif($_G['adminid'] != '1' && $index['flood']) {
				showmessage('search_ctrl', NULL, array('searchctrl' => $_G['setting']['search']['forum']['searchctrl']));
			}
		}

		if($searchindex['id']) {

			$searchid = $searchindex['id'];

		} else {

			!($_G['group']['exempt'] & 2) && checklowerlimit('search');

			if(!$srchtxt && !$srchuname && !$srchtag && !$srchfrom && !$before && !is_array($special)) {
				dheader('Location: search.php?mod=forum&adv=yes');
			} elseif(isset($srchfid) && !empty($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
				showmessage('search_forum_invalid');
			} elseif(!$fids) {
				showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
			}

			if($_G['adminid'] != '1' && $_G['setting']['search']['forum']['maxspm']) {
				if(C::t('common_searchindex')->count_by_dateline($_G['timestamp'], $srchmod) >= $_G['setting']['search']['forum']['maxspm']) {
					showmessage('search_toomany', NULL, array('maxspm' => $_G['setting']['search']['forum']['maxspm']));
				}
			}

			if($fulltext){
				$sqlsrch = "FROM ".DB::table(getposttable($seltableid))." p, ".DB::table('forum_thread').' t'
				." WHERE t.fid IN ($fids) AND p.tid=t.tid AND p.invisible='0'";
			}else{
				$sqlsrch = "FROM ".DB::table('forum_thread').' t'
				." WHERE t.fid IN ($fids)";
			}
			if($srchuname) {
				$srchuid = array_keys(C::t('common_member')->fetch_all_by_like_username($srchuname, 0, 50));
				if($srchuid) {
				$sqlsrch .= ' AND '.($fulltext ? 'p' : 't').'.authorid IN ('.dimplode((array)$srchuid).')';
				}else{
					showmessage('username_nonexistence');
				}
			}

			if($srchtxt) {
				if($logicalconnective == 'regexp') {
					$match_type = $Aa ? '\'cm\'' : '\'im\'';// see https://dev.mysql.com/doc/refman/8.4/en/regexp.html#function_regexp-like
					$sqlsrch .= $fulltext ? searchkey($keyword, 'REGEXP_LIKE(p.message,\'{text}\','.$match_type.') OR REGEXP_LIKE(p.subject,\'{text}\','.$match_type.')', false, $logicalconnective) : searchkey($keyword,'REGEXP_LIKE(t.subject,\'{text}\','.$match_type.')', false, $logicalconnective);
				} else {
					$binary = $Aa ? 'BINARY ' : '';
					$sqlsrch .= $fulltext ? searchkey($keyword, $binary.'p.message LIKE \'%{text}%\' OR '.$binary.'p.subject LIKE \'%{text}%\'', false, $logicalconnective) : searchkey($keyword, $binary.'t.subject LIKE \'%{text}%\'', false, $logicalconnective);
				}
			}

			if(!empty($srchfrom)) {
				if($srchfrom > $_G['timestamp'] || !empty($before) && $before < $srchfrom) {
					showmessage('search_time_invalid');
				}
				$sqlsrch .= " AND t.lastpost>=$srchfrom";
			}
			if(!empty($before)) {
				$sqlsrch .= " AND t.lastpost<=$before";
			}

			if(!empty($srchtag)) {
				$srchtag = array_map('trim',explode(',', $srchtag));
				$id = array();
				foreach ($srchtag as $value) {
					if(!preg_match('/^([\x7f-\xff_-]|\w|\s)+$/', $value) || strlen($value) > 20) {
						showmessage('tag_does_not_exist', NULL, array('tag' => $value));
					}
					$result = C::t('common_tag')->get_bytagname($value,'tid');
					if($result) {
						$id[] = $result['tagid'];
					}else{
						showmessage('tag_does_not_exist', NULL, array('tag' => $value));
					}
				}
				$sql_parts = array();
				foreach($id as $tagid) {
					$sql_parts[] = '(SELECT itemid FROM '.DB::table('common_tagitem').' WHERE tagid='.$tagid.' AND idtype=\'tid\')';
				}
				$sqlsrch .= ' AND t.tid IN ('.implode(' INTERSECT ', $sql_parts).')';
			}

			$expiration = TIMESTAMP + $cachelife_text;
			

			$num = $ids = 0;
			$_G['setting']['search']['forum']['maxsearchresults'] = $_G['setting']['search']['forum']['maxsearchresults'] ? intval($_G['setting']['search']['forum']['maxsearchresults']) : 500;
			if($_GET['debug']){
				exit("SELECT ".($fulltext ? 'DISTINCT' : '')." t.tid, t.closed, t.author, t.authorid $sqlsrch ORDER BY tid DESC");
			}
			$query = DB::query("SELECT ".($fulltext ? 'DISTINCT' : '')." t.tid, t.closed, t.author, t.authorid $sqlsrch ORDER BY tid DESC LIMIT ".$_G['setting']['search']['forum']['maxsearchresults']);
			while($thread = DB::fetch($query)) {
				$ids .= ','.$thread['tid'];
				$num++;
			}
			DB::free_result($query);
		

			$searchid = C::t('common_searchindex')->insert(array(
				'srchmod' => $srchmod,
				'keywords' => $keyword,
				'searchstring' => $searchstring,
				'useip' => $_G['clientip'],
				'uid' => $_G['uid'],
				'dateline' => $_G['timestamp'],
				'expiration' => $expiration,
				'num' => $num,
				'ids' => $ids
			), true);

			!($_G['group']['exempt'] & 2) && updatecreditbyaction('search');
		}

		dheader("location: search.php?mod=forum&searchid=$searchid&orderby=$orderby&ascdesc=$ascdesc&searchsubmit=yes");

	}

}

?>