<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cron_security_daily.php 29568 2012-04-19 03:39:25Z songlixin $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$queryf = C::t('forum_forum')->fetch_all_fids();
$parent_fups = array();
foreach($queryf as $forum) {
        if($forum['type'] == 'sub' && !empty($forum['fup'])) {
                $parent_fups[] = $forum['fup'];
        }
}

$parents = array();
if(!empty($parent_fups)) {
        $parents = C::t('forum_forum')->fetch_all_info_by_fids(array_unique($parent_fups));
}

foreach($queryf as $forum) {
        $thread = C::t('forum_thread')->fetch_by_fid_displayorder($forum['fid']);
        $thread['shortsubject'] = cutstr($thread['subject'], 80);
        $lastpost = "{$thread['tid']}\t{$thread['shortsubject']}\t{$thread['lastpost']}\t{$thread['lastposter']}";

        C::t('forum_forum')->update($forum['fid'], array('lastpost' => $lastpost));
        if($forum['type'] == 'sub') {
                $parent = isset($parents[$forum['fup']]) ? $parents[$forum['fup']] : null;
                if($parent) {
                        $parent_lastpost = 0;
                        if(!empty($parent['lastpost'])) {
                                $tmp = explode("\t", $parent['lastpost']);
                                $parent_lastpost = intval($tmp[2]);
                        }
                        if($thread['lastpost'] > $parent_lastpost) {
                                C::t('forum_forum')->update($forum['fup'], array('lastpost' => $lastpost));
                        }
                }
        }
}
?>
