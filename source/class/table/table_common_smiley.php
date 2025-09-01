<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_smiley extends discuz_table {
	private $allowtype = ['smiley'];

	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'common_smiley';
		$this->_pk    = 'id';
		parent::__construct();
	}

	public function fetch_all($start = 0, $limit = 0) {
		return DB::fetch_all('SELECT *, id AS displayorder FROM %t ORDER BY id '.DB::limit($start, $limit), array($this->_table), $this->_pk);
	}

	public function fetch_all_by_type($type) {
		return $this->checktype($type) ? $this->fetch_all() : [];
	}

	public function fetch_all_by_typeid_type($typeid, $type, $start = 0, $limit = 0) {
		return $this->checktype($type) ? $this->fetch_all($start, $limit) : [];
	}

	public function fetch_all_by_type_code_typeid($type, $typeid) {
		return $this->checktype($type) ? DB::fetch_all('SELECT *, id AS displayorder FROM %t WHERE code<>\'\' ORDER BY id', array($this->_table), $this->_pk) : [];
	}

	public function fetch_all_cache() {
		return DB::fetch_all('SELECT id, code, url FROM %t WHERE code<>\'\' ORDER BY LENGTH(code) DESC', array($this->_table));
	}

	public function fetch_by_id_type($id, $type) {
		return $this->checktype($type) ? DB::fetch_first('SELECT *, id AS displayorder FROM %t WHERE id=%d', [$this->_table, $id], $this->_pk) : [];
	}

	public function update_by_type($type, $data) {
		return !empty($data) && is_array($data) && $this->checktype($type) ? DB::update($this->_table, $data) : 0;
	}

	public function update_by_id_type($id, $type, $data) {
		$id = dintval($id, true);
		return $id && !empty($data) && is_array($data) && $this->checktype($type) ? DB::update($this->_table, $data, DB::field('id', $id)) : 0;
	}

	public function update_code_by_typeid($typeid) {
		if(!$typeid) {
			return 0;
		}
		return DB::query("UPDATE %t SET code=CONCAT('{:', id, ':}')", array($this->_table));
	}

	public function update_code_by_id($ids) {
		$ids = dintval($ids, true);
		if(empty($ids)) {
			return 0;
		}
		$idssql = is_array($ids) ? 'id IN(%n)' : 'id=%d';
		return DB::query("UPDATE %t SET code=CONCAT('{:', id, ':}') WHERE $idssql", array($this->_table, $ids));
	}

	public function count_by_type($type) {
		return $this->checktype($type) ? DB::result_first('SELECT COUNT(*) FROM %t', array($this->_table)) : 0;
	}

	public function count_by_typeid($typeid) {
		return DB::result_first('SELECT COUNT(*) FROM %t', array($this->_table));
	}

	public function count_by_type_typeid($type, $typeid) {
		return $this->checktype($type) ? DB::result_first('SELECT COUNT(*) FROM %t', array($this->_table)) : 0;
	}

	public function count_by_type_code_typeid($type, $typeid) {
		return $this->checktype($type) ? DB::result_first("SELECT COUNT(*) FROM %t WHERE code<>''", array($this->_table)) : 0;
	}

	private function checktype($type) {
		if(is_array($type)) {
			return !empty(array_intersect($type, $this->allowtype));
		}
		return in_array($type, $this->allowtype);
	}
}

