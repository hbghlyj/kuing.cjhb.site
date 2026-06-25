<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_member_auth extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'common_member_auth';
		$this->_pk = 'uid';
		parent::__construct();
	}

	public function fetch($uid) {
		return $uid ? DB::fetch_first('SELECT * FROM %t WHERE uid=%d', [$this->_table, $uid]) : [];
	}

	public function upsert($uid, $password, $salt = '', $secques = '') {
		if(!$uid) {
			return false;
		}
		return DB::insert($this->_table, [
			'uid' => intval($uid),
			'password' => (string)$password,
			'salt' => (string)$salt,
			'secques' => (string)$secques,
		], false, true);
	}

	public function delete($val, $unbuffered = false) {
		return parent::delete($val, $unbuffered);
	}
}
