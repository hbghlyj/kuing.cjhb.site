<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_editlog extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'forum_editlog';
		$this->_pk = 'editid';
		parent::__construct();
	}

	public function fetch_all_by_pid($pid) {
		return DB::fetch_all('SELECT * FROM %t WHERE pid=%d ORDER BY dateline DESC, editid DESC', [$this->_table, dintval($pid)]);
	}

	public function count_by_pids($pids) {
		if(empty($pids)) {
			return [];
		}
		$rows = DB::fetch_all('SELECT pid, COUNT(*) AS count FROM %t WHERE pid IN(%n) GROUP BY pid', [$this->_table, dintval($pids, true)]);
		$result = [];
		foreach($rows as $row) {
			$result[$row['pid']] = (int)$row['count'];
		}
		return $result;
	}

	public function count_for_admin($keyword = '') {
		$where = '';
		$params = [$this->_table];
		if($keyword !== '') {
			$like = '%'.$keyword.'%';
			$where = ' WHERE username LIKE %s OR old_subject LIKE %s OR old_message LIKE %s OR old_content LIKE %s';
			$params = array_merge($params, [$like, $like, $like, $like]);
		}
		return DB::result_first('SELECT COUNT(*) FROM %t'.$where, $params);
	}

	public function delete_by_dateline($dateline) {
		return DB::delete($this->_table, DB::field('dateline', dintval($dateline), '<'));
	}

	public function fetch_all_for_admin($keyword = '', $start = 0, $limit = 20) {
		$where = '';
		$params = [$this->_table];
		if($keyword !== '') {
			$like = '%'.$keyword.'%';
			$where = ' WHERE username LIKE %s OR old_subject LIKE %s OR old_message LIKE %s OR old_content LIKE %s';
			$params = array_merge($params, [$like, $like, $like, $like]);
		}
		return DB::fetch_all('SELECT * FROM %t'.$where.' ORDER BY dateline DESC, editid DESC '.DB::limit($start, $limit), $params);
	}
}
