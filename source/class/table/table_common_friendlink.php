<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_friendlink extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'common_friendlink';
		$this->_pk = 'id';

		parent::__construct();
	}

	public function fetch_all_by_displayorder($type = []) {
		$args = [$this->_table];
		$sql = '';
		$types = is_array($type) ? $type : [$type];
		$types = array_values(array_unique(array_filter(array_map('intval', $types), function($t) {
			return in_array($t, [1, 2, 3, 4], true);
		})));
		if($types) {
			$sql = 'WHERE type IN ('.dimplode($types).')';
		}
		return DB::fetch_all("SELECT * FROM %t $sql ORDER BY displayorder", $args, $this->_pk);
	}

}

