<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/relateitem');
require_once libfile('function/threadmod');

$tid = intval($_G['tid']);
$thread = table_forum_thread::t()->fetch($tid);
if(!$thread) {
	showmessage('thread_nonexistence');
}

$isorigauthor = $_G['uid'] && $_G['uid'] == $thread['authorid'];
$allowretag = $_G['forum']['ismoderator'] || $isorigauthor || !empty($_G['group']['allowretag']);

if(!$allowretag) {
	showmessage('group_nopermission', NULL, ['grouptitle' => $_G['group']['grouptitle']]);
}

if(submitcheck('retagsubmit') || $_GET['formhash'] == FORMHASH) {
	$tags = trim($_GET['tags'] ?? $_POST['tags'] ?? '');
	$class_tag = new tag();
	$tagstr = $class_tag->update_field($tags, $tid, 'tid', $thread);
	table_forum_thread::t()->update($tid, ['tags' => $tagstr]);

	$posttags = [];
	if($tagstr) {
		$tagarray_all = explode("\t", $tagstr);
		foreach($tagarray_all as $var) {
			if($var) {
				$array_temp = explode(',', $var);
				$posttags[] = ['id' => $array_temp[0], 'name' => $array_temp[1]];
			}
		}
	}

	if($_GET['inajax']) {
		$relatehtml = '';
		$relatetags = [];
		foreach($posttags as $pt) {
			$relatetags[] = [$pt['id'], $pt['name']];
		}
		$relateitem = getrelateitem($relatetags, $tid, $_G['setting']['relatenum'] ?? 0, $_G['setting']['relatetime'] ?? 0);
		if($relateitem) {
			$relatehtml = '<div class="mtw mbw"><h3 class="pbm mbm bbda">'.lang('forum/template', 'related_thread').'</h3><ul class="xl cl relatedthreadlist">';
			foreach($relateitem as $var) {
				$relatehtml .= '<li><a href="forum.php?mod=viewthread&tid='.intval($var['tid']).'" title="'.$var['subject'].'" target="_blank">'.$var['subject'].'</a></li>';
			}
			$relatehtml .= '</ul></div>';
		}
		header('Content-Type: application/json');
		$thread = table_forum_thread::t()->fetch($tid);
		$modacthtml = threadmod_render($thread);
		echo json_encode(['status' => 'success', 'tags' => $posttags, 'related' => $relatehtml, 'modact' => $modacthtml]);
		exit;
	}

	showmessage('do_success', "forum.php?mod=viewthread&tid=$tid", ['tags' => $posttags], ['msgtype' => 3]);
}
