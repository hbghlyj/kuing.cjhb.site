<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_emailpost extends discuz_table {

	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'forum_emailpost';
		$this->_pk = 'messagekey';
		parent::__construct();
	}

	public function reserve($data) {
		return $this->insert($data, false, false, true);
	}

	public function delete_by_pid($pids) {
		$pids = dintval((array)$pids, true);
		if(!$pids) {
			return 0;
		}
		return DB::delete($this->_table, 'pid IN ('.dimplode($pids).') AND status=1');
	}

	public function complete($messagekey, $fid, $tid, $pid, $parentkey = '') {
		return $this->update($messagekey, [
			'parentkey' => $parentkey,
			'fid' => intval($fid),
			'tid' => intval($tid),
			'pid' => intval($pid),
			'status' => 1,
			'detail' => '',
		]);
	}

	public function reject($messagekey, $detail) {
		return $this->update($messagekey, [
			'status' => -1,
			'detail' => cutstr(strip_tags($detail), 255),
		]);
	}
}
