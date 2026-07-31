<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */


if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_hotreply_member extends discuz_table {

	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'forum_hotreply_member';
		$this->_pk = '';

		parent::__construct();
	}

	public function fetch($id, $force_from_db = false) {

			return $this->fetch_member($id, $force_from_db);

	}

	public function fetch_member($pid, $uid) {
		return DB::fetch_first('SELECT * FROM %t WHERE pid=%d AND uid=%d', [$this->_table, $pid, $uid]);
	}

	public function update_attitude($pid, $uid, $attitude, $dateline = 0) {
		$data = ['attitude' => $attitude];
		if($dateline) {
			$data['dateline'] = dintval($dateline);
		}
		return DB::update($this->_table, $data, 'pid='.dintval($pid).' AND uid='.dintval($uid));
	}

	public function count_by_uid_dateline($uid, $dateline) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND dateline>%d', [$this->_table, $uid, $dateline]);
	}

	public function delete_by_uid_pid($uid, $pid) {
		return DB::delete($this->_table, 'uid='.dintval($uid).' AND pid='.dintval($pid));
	}

	public function delete_by_tid($tid) {
		if(empty($tid)) {
			return false;
		}
		return DB::query('DELETE FROM %t WHERE tid IN (%n)', [$this->_table, $tid]);
	}

	public function delete_by_pid($pids) {
		if(empty($pids)) {
			return false;
		}
		return DB::query('DELETE FROM %t WHERE '.DB::field('pid', $pids), [$this->_table]);
	}
}

