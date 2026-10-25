<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_process extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'common_process';
		$this->_pk = 'processid';

		parent::__construct();
	}

	public function acquire_process($name, $expiry) {
		return $this->write_process('INSERT IGNORE INTO %t (processid, expiry) VALUES (%s, %d)', ['common_process', $name, $expiry]) > 0;
	}

	public function delete_process($name, $time, $expiredOnly = false) {
		$sql = 'DELETE FROM %t WHERE processid=%s';
		$args = ['common_process', $name];
		if($expiredOnly) {
			$sql .= ' AND expiry<%d';
			$args[] = $time;
		}
		return $this->write_process($sql, $args);
	}

	private function write_process($sql, $args) {
		// Retry only these idempotent lock writes, never arbitrary transactions.
		for($attempt = 0; $attempt < 3; $attempt++) {
			$result = DB::query($sql, $args, true);
			if($result !== false) {
				return DB::affected_rows();
			}
			if(!in_array(DB::errno(), [1205, 1213], true)) {
				break;
			}
			usleep(10000 * ($attempt + 1));
		}
		// Preserve the normal database error diagnostics if recovery fails.
		DB::query($sql, $args);
		return DB::affected_rows();
	}
}

