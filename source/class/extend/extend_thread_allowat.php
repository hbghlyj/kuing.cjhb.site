<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: extend_thread_allowat.php 34144 2013-10-21 05:56:02Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class extend_thread_allowat extends extend_thread_base {

       protected function extract_mentions($message, $limit) {
               preg_match_all('/(?<!\S)@\K[^\r\n@\s]+(?=\s)/', $message.' ', $m);
               $m = array_unique($m[0] ?? $m);
               $m = array_filter($m, function($v) {
                       $len = dstrlen($v);
                       return $len >= 3 && $len <= 15;
               });
               return array_slice($m, 0, $limit);
       }

	public $atlist;
	public $allowat;

	public function before_newthread($parameters) {

		if($this->group['allowat']) {
                       $this->atlist = array();
                       $atlist_tmp = $this->extract_mentions($parameters['message'], $this->group['allowat']);
			if(!empty($atlist_tmp)) {
				if(!$this->setting['at_anyone']) {
					foreach(C::t('home_follow')->fetch_all_by_uid_fusername($this->member['uid'], $atlist_tmp) as $row) {
						$this->atlist[$row['followuid']] = $row['fusername'];
					}
					if(count($this->atlist) < $this->group['allowat']) {
						$query = C::t('home_friend')->fetch_all_by_uid_username($this->member['uid'], $atlist_tmp);
						foreach($query as $row) {
							$this->atlist[$row['fuid']] = $row['fusername'];
						}
					}
				} else {
                                        foreach ($atlist_tmp as $username) {
                                                $stripped_username = str_replace(' ', '', $username);
                                                $uid = DB::result_first('SELECT uid FROM %t WHERE REPLACE(username, " ", "")=%s', array('common_member', $stripped_username));
                                                $this->atlist[$uid] = $username;
					}
				}
			}
		}
	}

	public function after_newthread() {
		if($this->group['allowat'] && $this->atlist) {
			foreach($this->atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', array('from_id' => $this->tid, 'from_idtype' => 'at', 'buyerid' => $this->member['uid'], 'buyer' => $this->member['username'], 'tid' => $this->tid, 'subject' => $this->param['subject'], 'pid' => $this->pid, 'message' => dhtmlspecialchars(messagecutstr($this->param['message'], 150, null, $this->param['htmlon']))));
			}
			set_atlist_cookie(array_keys($this->atlist));
		}
	}

	public function before_newreply($parameters) {
		if($this->group['allowat']) {
                       $this->atlist = $ateduids = array();
                       $atlist_tmp = $this->extract_mentions($parameters['message'], $this->group['allowat']);
			$atnum = $maxselect = 0;
			foreach(C::t('home_notification')->fetch_all_by_authorid_fromid($this->member['uid'], $this->thread['tid'], 'at') as $row) {
				$atnum ++;
				$ateduids[$row['uid']] = $row['uid'];
			}
			$maxselect = $this->group['allowat'] - $atnum;
			if($maxselect > 0 && !empty($atlist_tmp)) {
				$at_anyone = $this->setting['at_anyone'];
				if(empty($at_anyone)) {
					foreach(C::t('home_follow')->fetch_all_by_uid_fusername($this->member['uid'], $atlist_tmp) as $row) {
						if(!in_array($row['followuid'], $ateduids)) {
							$this->atlist[$row['followuid']] = $row['fusername'];
						}
						if(count($this->atlist) == $maxselect) {
							break;
						}
					}
					if(count($this->atlist) < $maxselect) {
						$query = C::t('home_friend')->fetch_all_by_uid_username($this->member['uid'], $atlist_tmp);
						foreach($query as $row) {
							if(!in_array($row['fuid'], $ateduids)) {
								$this->atlist[$row['fuid']] = $row['fusername'];
							}
						}
					}
				} else {
                                        foreach ($atlist_tmp as $username) {
                                                $stripped_username = str_replace(' ', '', $username);
                                                $uid = DB::result_first('SELECT uid FROM %t WHERE REPLACE(username, " ", "")=%s', array('common_member', $stripped_username));
                                                if(!in_array($uid, $ateduids)) {
							$this->atlist[$uid] = $username;
							if(count($this->atlist) == $maxselect) {
								break;
							}
						}
					}
				}
			}
		}
	}

	public function after_newreply() {
		if($this->group['allowat'] && $this->atlist) {
			foreach($this->atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', array('from_id' => $this->thread['tid'], 'from_idtype' => 'at', 'buyerid' => $this->member['uid'], 'buyer' => $this->member['username'], 'tid' => $this->thread['tid'], 'subject' => $this->thread['subject'], 'pid' => $this->pid, 'message' => dhtmlspecialchars(messagecutstr($this->param['message'], 150, null, $this->param['htmlon']))));
			}
			set_atlist_cookie(array_keys($this->atlist));
		}
	}

	public function before_editpost($parameters) {
		if($this->group['allowat']) {
                       $this->atlist = $ateduids = array();
                       $atnum = $maxselect = 0;
			foreach(C::t('home_notification')->fetch_all_by_authorid_fromid($this->member['uid'], $this->thread['tid'], 'at') as $row) {
				$atnum ++;
				$ateduids[$row['uid']] = $row['uid'];
			}
			$maxselect = $this->group['allowat'] - $atnum;
                       $atlist_tmp = $this->extract_mentions($parameters['message'], $this->group['allowat']);
			if($maxselect > 0 && !empty($atlist_tmp)) {
				if(empty($this->setting['at_anyone'])) {
					foreach(C::t('home_follow')->fetch_all_by_uid_fusername($this->member['uid'], $atlist_tmp) as $row) {
						if(!in_array($row['followuid'], $ateduids)) {
							$this->atlist[$row['followuid']] = $row['fusername'];
						}
						if(count($this->atlist) == $maxselect) {
							break;
						}
					}
					if(count($this->atlist) < $maxselect) {
						$query = C::t('home_friend')->fetch_all_by_uid_username($this->member['uid'], $atlist_tmp);
						foreach($query as $row) {
							if(!in_array($row['fuid'], $ateduids)) {
								$this->atlist[$row['fuid']] = $row['fusername'];
							}
						}
					}
				} else {
                                        foreach ($atlist_tmp as $username) {
                                                $stripped_username = str_replace(' ', '', $username);
                                                $uid = DB::result_first('SELECT uid FROM %t WHERE REPLACE(username, " ", "")=%s', array('common_member', $stripped_username));
                                                if(!in_array($uid, $ateduids)) {
							$this->atlist[$uid] = $username;
							if(count($this->atlist) == $maxselect) {
								break;
							}
						}
					}
				}
			}
		}
	}

	public function after_editpost() {
		if($this->group['allowat'] && $this->atlist) {
			foreach($this->atlist as $atuid => $atusername) {
				notification_add($atuid, 'at', 'at_message', array('from_id' => $this->thread['tid'], 'from_idtype' => 'at', 'buyerid' => $this->post['authorid'], 'buyer' => $this->post['author'], 'tid' => $this->thread['tid'], 'subject' => $this->thread['subject'], 'pid' => $this->post['pid'], 'message' => dhtmlspecialchars(messagecutstr($this->param['message'], 150, null, $this->param['htmlon']))));
			}
			set_atlist_cookie(array_keys($this->atlist));
		}
	}
}

?>