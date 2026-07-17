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
		return DB::fetch_all('SELECT * FROM %t ORDER BY displayorder, id '.DB::limit($start, $limit), [$this->_table], $this->_pk);
	}

	public function fetch_all_by_type($type) {
		if(!$this->checktype($type)) {
			return [];
		}
		return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('type', $type).' ORDER BY displayorder, id', [$this->_table], $this->_pk);
	}

	public function fetch_all_by_typeid_type($typeid, $type, $start = 0, $limit = 0) {
		if(!$this->checktype($type)) {
			return [];
		}
		return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('typeid', $typeid).' AND '.DB::field('type', $type).' ORDER BY displayorder, id '.DB::limit($start, $limit), [$this->_table], $this->_pk);
	}

	public function fetch_all_by_type_code_typeid($type, $typeid) {
		if(!$this->checktype($type)) {
			return [];
		}
		return DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('type', $type).' AND '.DB::field('typeid', $typeid)." AND code<>'' ORDER BY displayorder, id", [$this->_table], $this->_pk);
	}

	public function fetch_all_cache() {
		return DB::fetch_all("SELECT id, typeid, code, url FROM %t WHERE type='smiley' AND code<>'' ORDER BY LENGTH(code) DESC", [$this->_table]);
	}

	public function fetch_by_id_type($id, $type) {
		return $this->checktype($type) ? DB::fetch_first('SELECT * FROM %t WHERE id=%d AND '.DB::field('type', $type), [$this->_table, $id], $this->_pk) : [];
	}

	public function update_by_type($type, $data) {
		return !empty($data) && is_array($data) && $this->checktype($type) ? DB::update($this->_table, $data, DB::field('type', $type)) : 0;
	}

	public function update_by_id_type($id, $type, $data) {
		$id = dintval($id, true);
		return $id && !empty($data) && is_array($data) && $this->checktype($type) ? DB::update($this->_table, $data, DB::field('id', $id).' AND '.DB::field('type', $type)) : 0;
	}

	public function update_code_by_typeid($typeid) {
		if(!$typeid) {
			return 0;
		}
		return DB::query("UPDATE %t SET code=CONCAT('{:', id, ':}') WHERE ".DB::field('typeid', $typeid)." AND ".DB::field('type', 'smiley'), [$this->_table]);
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
		return $this->checktype($type) ? DB::result_first('SELECT COUNT(*) FROM %t WHERE '.DB::field('type', $type), [$this->_table]) : 0;
	}

	public function count_by_typeid($typeid) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE '.DB::field('typeid', $typeid), [$this->_table]);
	}

	public function count_by_type_typeid($type, $typeid) {
		return $this->checktype($type) ? DB::result_first('SELECT COUNT(*) FROM %t WHERE '.DB::field('type', $type).' AND '.DB::field('typeid', $typeid), [$this->_table]) : 0;
	}

	public function count_by_type_code_typeid($type, $typeid) {
		return $this->checktype($type) ? DB::result_first("SELECT COUNT(*) FROM %t WHERE ".DB::field('type', $type).' AND '.DB::field('typeid', $typeid)." AND code<>''", [$this->_table]) : 0;
	}

	private function checktype($type) {
		if(is_array($type)) {
			return !empty(array_intersect($type, $this->allowtype));
		}
		return in_array($type, $this->allowtype);
	}
}

