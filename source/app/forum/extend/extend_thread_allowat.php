<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

namespace forum;

use DB;
use table_home_follow;
use table_home_friend;
use table_home_notification;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class extend_thread_allowat extends extend_thread_base {

	public $atlist;
	public $allowat;

	private function fetch_at_members($usernames) {
		$members = [];
		foreach($usernames as $username) {
			$normalized = str_replace(' ', '', $username);
			if($normalized === '') {
				continue;
			}
			$uid = DB::result_first("SELECT uid FROM %t WHERE REPLACE(username, ' ', '')=%s", ['common_member', $normalized]);
			if($uid) {
				$members[$uid] = $username;
			}
		}
		return $members;
	}

	public function before_newthread($parameters) {

		if($this->group['allowat']) {
			$this->atlist = $atlist_tmp = [];
			if(is_valid_non_empty_json($parameters['content'], true)) {
				preg_match_all("/@([^\r\n]*?)\s/i", $parameters['content'].' ', $atlist_tmp);
			}else{
				preg_match_all("/@([^\r\n]*?)\s/i", $parameters['message'].' ', $atlist_tmp);
			}
			$atlist_tmp = array_slice(array_unique($atlist_tmp[1]), 0, $this->group['allowat']);
			if(!empty($atlist_tmp)) {
				if(!$this->setting['at_anyone']) {
					foreach(table_home_follow::t()->fetch_all_by_uid_fusername($this->member['uid'], $atlist_tmp) as $row) {
						$this->atlist[$row['followuid']] = $row['fusername'];
					}
					if(count($this->atlist) < $this->group['allowat']) {
						$query = table_home_friend::t()->fetch_all_by_uid_username($this->member['uid'], $atlist_tmp);
						foreach($query as $row) {
							$this->atlist[$row['fuid']] = $row['fusername'];
						}
					}
				} else {
					$this->atlist = $this->fetch_at_members($atlist_tmp);
				}
			}
		}
	}

	public function after_newthread() {
		if($this->group['allowat'] && $this->atlist) {
			foreach($this->atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', ['from_id' => $this->tid, 'from_idtype' => 'at', 'buyerid' => $this->member['uid'], 'buyer' => $this->member['username'], 'tid' => $this->tid, 'subject' => $this->param['subject'], 'pid' => $this->pid, 'message' => dhtmlspecialchars(messagecutstr($this->param['message'], 150, null, $this->param['htmlon']))]);
			}
			set_atlist_cookie(array_keys($this->atlist));
		}
	}

	public function before_newreply($parameters) {
		if($this->group['allowat']) {
			$this->atlist = $atlist_tmp = $ateduids = [];
			if(is_valid_non_empty_json($parameters['content'], true)) {
				preg_match_all("/@([^\r\n]*?)\s/i", $parameters['content'].' ', $atlist_tmp);
			}else{
				preg_match_all("/@([^\r\n]*?)\s/i", $parameters['message'].' ', $atlist_tmp);
			}
			$atlist_tmp = array_slice(array_unique($atlist_tmp[1]), 0, $this->group['allowat']);
			$atnum = $maxselect = 0;
			foreach(table_home_notification::t()->fetch_all_by_authorid_fromid($this->member['uid'], $this->thread['tid'], 'at') as $row) {
				$atnum++;
				$ateduids[$row['uid']] = $row['uid'];
			}
			$maxselect = $this->group['allowat'] - $atnum;
			if($maxselect > 0 && !empty($atlist_tmp)) {
				$at_anyone = $this->setting['at_anyone'];
				if(empty($at_anyone)) {
					foreach(table_home_follow::t()->fetch_all_by_uid_fusername($this->member['uid'], $atlist_tmp) as $row) {
						if(!in_array($row['followuid'], $ateduids)) {
							$this->atlist[$row['followuid']] = $row['fusername'];
						}
						if(count($this->atlist) == $maxselect) {
							break;
						}
					}
					if(count($this->atlist) < $maxselect) {
						$query = table_home_friend::t()->fetch_all_by_uid_username($this->member['uid'], $atlist_tmp);
						foreach($query as $row) {
							if(!in_array($row['fuid'], $ateduids)) {
								$this->atlist[$row['fuid']] = $row['fusername'];
							}
						}
					}
				} else {
					foreach($this->fetch_at_members($atlist_tmp) as $uid => $username) {
						if(!in_array($uid, $ateduids)) {
							$this->atlist[$uid] = $username;
						}
						if(count($this->atlist) == $maxselect) {
							break;
						}
					}
				}
			}
		}
	}

	public function after_newreply() {
		if($this->group['allowat'] && $this->atlist) {
			foreach($this->atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', ['from_id' => $this->thread['tid'], 'from_idtype' => 'at', 'buyerid' => $this->member['uid'], 'buyer' => $this->member['username'], 'tid' => $this->thread['tid'], 'subject' => $this->thread['subject'], 'pid' => $this->pid, 'message' => dhtmlspecialchars(messagecutstr($this->param['message'], 150, null, $this->param['htmlon']))]);
			}
			set_atlist_cookie(array_keys($this->atlist));
		}
	}

	public function before_editpost($parameters) {
		if($this->group['allowat']) {
			$this->atlist = $atlist_tmp = $ateduids = [];
			$atnum = $maxselect = 0;
			foreach(table_home_notification::t()->fetch_all_by_authorid_fromid($this->member['uid'], $this->thread['tid'], 'at') as $row) {
				$atnum++;
				$ateduids[$row['uid']] = $row['uid'];
			}
			$maxselect = $this->group['allowat'] - $atnum;
			if(is_valid_non_empty_json($parameters['content'], true)) {
				preg_match_all("/@([^\r\n]*?)\s/i", $parameters['content'].' ', $atlist_tmp);
			}else{
				preg_match_all("/@([^\r\n]*?)\s/i", $parameters['message'].' ', $atlist_tmp);
			}
			$atlist_tmp = array_slice(array_unique($atlist_tmp[1]), 0, $this->group['allowat']);
			if($maxselect > 0 && !empty($atlist_tmp)) {
				if(empty($this->setting['at_anyone'])) {
					foreach(table_home_follow::t()->fetch_all_by_uid_fusername($this->member['uid'], $atlist_tmp) as $row) {
						if(!in_array($row['followuid'], $ateduids)) {
							$this->atlist[$row['followuid']] = $row['fusername'];
						}
						if(count($this->atlist) == $maxselect) {
							break;
						}
					}
					if(count($this->atlist) < $maxselect) {
						$query = table_home_friend::t()->fetch_all_by_uid_username($this->member['uid'], $atlist_tmp);
						foreach($query as $row) {
							if(!in_array($row['fuid'], $ateduids)) {
								$this->atlist[$row['fuid']] = $row['fusername'];
							}
						}
					}
				} else {
					foreach($this->fetch_at_members($atlist_tmp) as $uid => $username) {
						if(!in_array($uid, $ateduids)) {
							$this->atlist[$uid] = $username;
						}
						if(count($this->atlist) == $maxselect) {
							break;
						}
					}
				}
			}
		}
	}

	public function after_editpost() {
		if($this->group['allowat'] && $this->atlist) {
			foreach($this->atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', ['from_id' => $this->thread['tid'], 'from_idtype' => 'at', 'buyerid' => $this->post['authorid'], 'buyer' => $this->post['author'], 'tid' => $this->thread['tid'], 'subject' => $this->thread['subject'], 'pid' => $this->post['pid'], 'message' => dhtmlspecialchars(messagecutstr($this->param['message'], 150, null, $this->param['htmlon']))]);
			}
			set_atlist_cookie(array_keys($this->atlist));
		}
	}
}

