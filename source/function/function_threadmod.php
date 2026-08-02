<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function viewthread_lastmod(&$thread) {
	global $_G;
	if(!$thread['moderated']) {
		return [];
	}
	$lastmod = [];
	$lastlog = table_forum_threadmod::t()->fetch_by_tid($thread['tid']);
	if($lastlog) {
		$lastmod = [
			'moduid' => $lastlog['uid'],
			'modusername' => $lastlog['username'],
			'moddateline' => $lastlog['dateline'],
			'modaction' => $lastlog['action'],
			'magicid' => $lastlog['magicid'],
			'reason' => $lastlog['reason']
		];
	}
	if($lastmod) {
		$modactioncode = lang('forum/modaction');
		$lastmod['moduid'] = $_G['setting']['moduser_public'] ? $lastmod['moduid'] : 0;
		$lastmod['modusername'] = $lastmod['modusername'] ? ($_G['setting']['moduser_public'] ? $lastmod['modusername'] : lang('forum/template', 'thread_moderations_team')) : lang('forum/template', 'thread_moderations_cron');
		$lastmod['moddateline'] = dgmdate($lastmod['moddateline'], 'u');
		$lastmod['modactiontype'] = $lastmod['modaction'];
		$lastmod['modaction'] = $modactioncode[$lastmod['modaction']] ?? '';
		if($lastmod['magicid']) {
			loadcache('magics');
			$lastmod['magicname'] = $_G['cache']['magics'][$lastmod['magicid']]['name'];
		}
	} else {
		table_forum_thread::t()->update($thread['tid'], ['moderated' => 0], false, false, $thread['threadtableid']);
		$thread['moderated'] = 0;
	}
	return $lastmod;
}

function threadmod_render($thread) {
	global $_G;
	$lastmod = viewthread_lastmod($thread);
	if(empty($lastmod['modaction'])) {
		return '';
	}
	$modtext = lang('forum/template', $lastmod['modactiontype'] == 'REB' ? 'thread_mod_recommend_by' : 'thread_mod_by');
	$modtext = preg_replace_callback('/\$lastmod\[\'?(\w+)\'?\]/', function($m) use ($lastmod) {
		return $lastmod[$m[1]] ?? '';
	}, $modtext);
	$modacthtml = '<div class="modact"><a href="forum.php?mod=misc&action=viewthreadmod&tid='.intval($thread['tid']).'" title="'.dhtmlspecialchars(lang('forum/template', 'thread_mod')).'" onclick="showWindow(\'viewthreadmod\', this.href)">'.$modtext.'</a></div>';
	return $modacthtml;
}
