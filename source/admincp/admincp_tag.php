<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_tag.php 25889 2011-11-24 09:52:20Z monkey $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
cpheader();
$operation = in_array($operation, array('admin','suggest')) ? $operation : 'admin';
$current = array($operation => 1);
shownav('global', 'tag');
showsubmenu('tag', array(
        array('search', 'tag&operation=admin', $current['admin']),
        array('pending_tag_suggest', 'tag&operation=suggest', $current['suggest']),
));
/*search={"tag":"action=tag"}*/
if($operation == 'admin') {
	$tagarray = array();
	if(submitcheck('submit') && !empty($_GET['tagidarray']) && is_array($_GET['tagidarray']) && !empty($_GET['operate_type'])) {
		$class_tag = new tag();
		$tagidarray = array();
		$operate_type = $newtag = $thread =  '';
		$tagidarray = $_GET['tagidarray'];
		$operate_type = $_GET['operate_type'];
		if($operate_type == 'delete') {
			$class_tag->delete_tag($tagidarray);
		} elseif($operate_type == 'open') {
			C::t('common_tag')->update($tagidarray, array('status' => 0));
		} elseif($operate_type == 'close') {
			C::t('common_tag')->update($tagidarray, array('status' => 1));
		} elseif($operate_type == 'merge') {
			$data = $class_tag->merge_tag($tagidarray, $_GET['newtag']);
			if($data != 'succeed') {
				cpmsg($data);
			}
		}
		cpmsg('tag_admin_updated', 'action=tag&operation=admin&searchsubmit=yes&tagname='.$_GET['tagname'].'&perpage='.$_GET['perpage'].'&status='.$_GET['status'].'&page='.$_GET['page'], 'succeed');
	}
	if(!submitcheck('searchsubmit', 1)) {
		showformheader('tag&operation=admin');
		showtableheader();
		showsetting('tagname', 'tagname', $tagname, 'text');
		showsetting('feed_search_perpage', '', $_GET['perpage'], "<select name='perpage'><option value='20'>{$lang['perpage_20']}</option><option value='50'>{$lang['perpage_50']}</option><option value='100'>{$lang['perpage_100']}</option></select>");
		showsetting('misc_tag_status', array('status', array(
			array('', cplang('unlimited')),
			array(0, cplang('misc_tag_status_0')),
			array(1, cplang('misc_tag_status_1')),
		), TRUE), '', 'mradio');
		showsubmit('searchsubmit');
		showtablefooter();
        showformfooter();
        showtagfooter('div');
        } else {
		$tagname = trim($_GET['tagname']);
		$status = $_GET['status'];
		if(!$status) {
			$table_status = NULL;
		} else {
			$table_status = $status;
		}
		$ppp = $_GET['perpage'];
		$startlimit = ($page - 1) * $ppp;
		$multipage = '';
		$totalcount  = C::t('common_tag')->fetch_all_by_status($table_status, $tagname, 0, 0, 1);
		$multipage = multi($totalcount, $ppp, $page, ADMINSCRIPT."?action=tag&operation=admin&searchsubmit=yes&tagname=$tagname&perpage=$ppp&status=$status");
		$query = C::t('common_tag')->fetch_all_by_status($table_status, $tagname, $startlimit, $ppp);
		showformheader('tag&operation=admin');
		showtableheader(cplang('tag_result').' '.$totalcount.' <a href="###" onclick="location.href=\''.ADMINSCRIPT.'?action=tag&operation=admin;\'" class="act lightlink normal">'.cplang('research').'</a>', 'nobottom');
		showhiddenfields(array('page' => $_GET['page'], 'tagname' => $tagname, 'status' => $status, 'perpage' => $ppp));
		showsubtitle(array('', 'tagname', 'misc_tag_status'));
		foreach($query as $result) {
			if($result['status'] == 0) {
				$tagstatus = cplang('misc_tag_status_0');
			} elseif($result['status'] == 1) {
				$tagstatus = cplang('misc_tag_status_1');
			}
			showtablerow('', array('class="td25"', 'width=400', ''), array(
				"<input class=\"checkbox\" type=\"checkbox\" name=\"tagidarray[]\" value=\"{$result['tagid']}\" />",
				$result['tagname'],
				$tagstatus
			));
		}
		showtablerow('', array('class="td25" colspan="3"'), array('<input name="chkall" id="chkall" type="checkbox" class="checkbox" onclick="checkAll(\'prefix\', this.form, \'tagidarray\', \'chkall\')" /><label for="chkall">'.cplang('select_all').'</label>'));
		showtablerow('', array('class="td25"', 'colspan="2"'), array(
				cplang('operation'),
				'<input class="radio" type="radio" name="operate_type" value="open" checked> '.cplang('misc_tag_status_0').' &nbsp; &nbsp;<input class="radio" type="radio" name="operate_type" value="close"> '.cplang('misc_tag_status_1').' &nbsp; &nbsp;<input class="radio" type="radio" name="operate_type" value="delete"> '.cplang('delete').' &nbsp; &nbsp;<input class="radio" type="radio" name="operate_type" value="merge"> '.cplang('mergeto').' <input name="newtag" value="" class="txt" type="text">'
			));
		showsubmit('submit', 'submit', '', '', $multipage);
		showtablefooter();
		showformfooter();
        }
} elseif($operation == 'suggest') {
       $allowed = array('approve', 'reject');
       if(in_array($_GET['modaction'], $allowed) && isset($_GET['id']) && $_GET['formhash'] == FORMHASH) {
               $id = dintval($_GET['id']);
               if(!$id) {
                       cpmsg('error_invalid_id', '', 'error');
               }
                if($_GET['modaction'] == 'approve') {
                        $suggest = C::t('forum_tag_suggest')->fetch($id);
                        if($suggest) {
                               $class_tag = new tag();
                               $tagstr = $class_tag->add_tag($suggest['tagname'], $suggest['tid'], 'tid');
                               if($tagstr) {
                                       $thread_table = C::t('forum_thread');
                                       $thread_info = $thread_table->fetch_thread($suggest['tid']);
                                       $current_tags_on_thread = isset($thread_info['tags']) ? $thread_info['tags'] : '';
                                       $new_tag_part = rtrim($tagstr, "\t");
                                       $tag_already_present = false;
                                       if ($current_tags_on_thread) {
                                               $existing_tag_parts_array = explode("\t", rtrim($current_tags_on_thread, "\t"));
                                               if (in_array($new_tag_part, $existing_tag_parts_array)) {
                                                       $tag_already_present = true;
                                               }
                                       }
                                       if (!$tag_already_present) {
                                               $thread_table->concat_tags_by_tid($suggest['tid'], $tagstr);
                                       }
                               }
                               C::t('forum_tag_suggest')->update_status($id, 1);
                       }
                } elseif($_GET['modaction'] == 'reject') {
                        C::t('forum_tag_suggest')->update_status($id, 2);
                }
                cpmsg('tag_admin_updated', 'action=tag&operation=suggest', 'succeed');
        }
        $perpage = 20;
        $start = ($page - 1) * $perpage;
        $count = C::t('forum_tag_suggest')->fetch_all_by_status(0, 0, 0, true);
        $multipage = multi($count, $perpage, $page, ADMINSCRIPT.'?action=tag&operation=suggest');
        $data = C::t('forum_tag_suggest')->fetch_all_by_status(0, $start, $perpage);
        $uids = array();
        foreach($data as $row) {
                $uids[] = $row['uid'];
        }
        $members = array();
        if($uids) {
                $members = C::t('common_member')->fetch_all(array_unique($uids));
        }
        showtableheader(cplang('pending_tag_suggest')." ($count)", 'nobottom');
        showsubtitle(array('tagname', 'author', 'time', 'operation'));
        foreach($data as $row) {
                $username = isset($members[$row['uid']]) ? $members[$row['uid']]['username'] : cplang('unknown');
                showtablerow('', array('', '', '', ''), array(
                        $row['tagname'],
                        $username,
                        dgmdate($row['dateline']),
                        '<a href="'.ADMINSCRIPT.'?action=tag&operation=suggest&modaction=approve&id='.$row['id'].'&formhash='.FORMHASH.'">'.cplang('pass').'</a> / '.
                        '<a href="'.ADMINSCRIPT.'?action=tag&operation=suggest&modaction=reject&id='.$row['id'].'&formhash='.FORMHASH.'">'.cplang('delete').'</a>'
                ));
        }
        showsubmit('', '', '', '', $multipage);
        showtablefooter();
}
/*search*/
?>