<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_forum_threadtable extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'forum_forum_threadtable';
		$this->_pk = '';

		parent::__construct();
	}

	public function count_by_fid($fids) {
		if(empty($fids)) {
			return 0;
		}
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE '.DB::field('fid', $fids), [$this->_table]);
	}

	public function fetch_all_by_fid($fids) {
		if(empty($fids)) {
			return [];
		}
		return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('fid', $fids), [$this->_table]);
	}

	public function update_threadtable($fid, $threadtableid, $data, $unbuffered = false, $low_priority = false) {
		if(empty($data)) {
			return false;
		}
		return DB::update($this->_table, $data, ['fid' => $fid, 'threadtableid' => $threadtableid], $unbuffered, $low_priority);
	}

	public function update_by_threadtableid($threadtableid, $data, $unbuffered = false, $low_priority = false) {
		if(empty($data)) {
			return false;
		}
		return DB::update($this->_table, $data, DB::field('threadtableid', $threadtableid), $unbuffered, $low_priority);
	}

	public function delete_threadtable($fid, $threadtableid, $unbuffered = false) {
		return DB::delete($this->_table, ['fid' => dintval($fid), 'threadtableid' => dintval($threadtableid)], null, $unbuffered);
	}

	public function delete_none_threads() {
		return DB::delete($this->_table, "threads='0'");
	}
}

