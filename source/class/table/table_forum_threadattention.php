<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_threadattention extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'forum_threadattention';
		$this->_pk = '';

		parent::__construct();
	}

	public function fetch_by_tid_uid($tid, $uid) {
		return DB::fetch_first('SELECT * FROM %t WHERE tid=%d AND uid=%d', [$this->_table, $tid, $uid]);
	}

	public function fetch_all_uid_by_tid($tid) {
		return DB::fetch_all('SELECT uid FROM %t WHERE tid=%d', [$this->_table, $tid]);
	}

	public function insert_attention($tid, $uid) {
		DB::query('INSERT INTO %t (tid, uid, dateline) VALUES (%d, %d, %d) ON DUPLICATE KEY UPDATE dateline=VALUES(dateline)', [$this->_table, $tid, $uid, TIMESTAMP]);
	}

	public function delete_by_tid_uid($tid, $uid) {
		return DB::delete($this->_table, DB::field('tid', $tid).' AND '.DB::field('uid', $uid));
	}

	public function delete_by_tids($tids, $uid = 0) {
		if(!$tids) {
			return 0;
		}
		$tids = dintval($tids, true);
		$uidsql = $uid ? ' AND '.DB::field('uid', $uid) : '';
		return DB::delete($this->_table, DB::field('tid', $tids).$uidsql);
	}

	public function increase_newreplies($tid, $uid, $authorid = 0) {
		if(!$tid) {
			return;
		}
		$authorsql = $authorid ? ' AND uid<>' . intval($authorid) : '';
		DB::query('UPDATE %t SET newreplies=newreplies+1, dateline=%d WHERE tid=%d AND uid<>%d' . $authorsql, [$this->_table, TIMESTAMP, $tid, $uid]);
	}

	public function clear_newreplies($tid, $uid) {
		if(!$tid || !$uid) {
			return;
		}
		DB::query('UPDATE %t SET newreplies=0 WHERE tid=%d AND uid=%d', [$this->_table, $tid, $uid]);
	}

	public function count_by_uid($uid, $new = 0) {
		$sqladd = $new ? ' AND ta.newreplies>0' : '';
		return DB::result_first(
			'SELECT COUNT(*) FROM %t ta INNER JOIN '.DB::table('forum_thread').' t ON ta.tid=t.tid WHERE ta.uid=%d'.$sqladd,
			[$this->_table, $uid]
		);
	}

	public function fetch_all_by_uid($uid, $new = 0, $start = 0, $limit = 0) {
		$sqladd = $new ? ' AND ta.newreplies>0' : '';
		return DB::fetch_all(
			'SELECT t.tid AS t_tid, t.fid, t.subject, t.replies, t.lastpost, t.lastposter, ta.*'.
			' FROM %t ta INNER JOIN '.DB::table('forum_thread').' t ON ta.tid=t.tid'.
			' WHERE ta.uid=%d'.$sqladd.' ORDER BY t.lastpost DESC '.DB::limit($start, $limit),
			[$this->_table, $uid]
		);
	}
}
