<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_nav extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'common_nav';
		$this->_pk = 'id';

		parent::__construct();
	}

	public static function localize_row($row, $locale = '') {
		if(!$row) {
			return $row;
		}
		$row['name_i18n'] = i18n::decodeValue($row['name']);
		$row['name'] = i18n::localizeValue($row['name_i18n'], $locale);
		$row['title'] = i18n::localizeValue($row['name_i18n'], 'EN');
		return $row;
	}

	public static function localize_rows($rows, $locale = '') {
		foreach((array)$rows as $key => $row) {
			$rows[$key] = self::localize_row($row, $locale);
		}
		return $rows;
	}

	private function fetch_raw_name($id) {
		return DB::result_first('SELECT name FROM %t WHERE id=%d', [$this->_table, $id]);
	}

	private function prepare_localized_data($data, $existing = '') {
		if(!is_array($data) || (!array_key_exists('name', $data) && !array_key_exists('title', $data))) {
			return $data;
		}
		$names = i18n::decodeValue(array_key_exists('name', $data) ? i18n::encodeValue($data['name'], $existing) : $existing);
		if(isset($data['title']) && $data['title'] !== '') {
			$names['EN'] = (string)$data['title'];
		}
		$data['name'] = i18n::encodeValue($names);
		unset($data['title']);
		return $data;
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		$data = $this->prepare_localized_data($data);
		return parent::insert($data, $return_insert_id, $replace, $silent);
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if(!is_array($val) && (array_key_exists('name', $data) || array_key_exists('title', $data))) {
			$data = $this->prepare_localized_data($data, $this->fetch_raw_name($val));
		} else {
			unset($data['title']);
		}
		return parent::update($val, $data, $unbuffered, $low_priority);
	}

	public function fetch_by_id_navtype($id, $navtype, $locale = '') {
		return self::localize_row(DB::fetch_first('SELECT * FROM %t WHERE id=%d AND navtype=%d', [$this->_table, $id, $navtype]), $locale);
	}

	public function fetch_by_type_identifier($type, $identifier, $locale = '') {
		return self::localize_row(DB::fetch_first('SELECT * FROM %t WHERE type=%d AND identifier=%s', [$this->_table, $type, $identifier]), $locale);
	}

	public function fetch_all_by_type_identifier($type, $identifier, $locale = '') {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE type=%d AND identifier=%s', [$this->_table, $type, $identifier]), $locale);
	}

	public function fetch_all_by_navtype($navtype = null, $locale = '') {
		$parameter = [$this->_table];
		$wheresql = '';
		if($navtype !== null) {
			$parameter[] = $navtype;
			$wheresql = ' WHERE navtype=%d';
		}
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t '.$wheresql.' ORDER BY available DESC, displayorder', $parameter, $this->_pk), $locale);
	}

	public function fetch_all_by_navtype_parentid($navtype, $parentid, $locale = '') {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE navtype=%d AND parentid=%d ORDER BY displayorder', [$this->_table, $navtype, $parentid], $this->_pk), $locale);
	}

	public function fetch_all_by_navtype_type($navtype, $type, $locale = '') {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE navtype=%d AND type=%d', [$this->_table, $navtype, $type], $this->_pk), $locale);
	}

	public function fetch_all_mainnav($locale = '') {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE navtype=0 AND (available=1 OR type=0) AND parentid=0 ORDER BY displayorder', [$this->_table], $this->_pk), $locale);
	}

	public function fetch_all_subnav($parentid, $locale = '') {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE navtype=0 AND parentid=%d AND available=1 ORDER BY displayorder', [$this->_table, $parentid], $this->_pk), $locale);
	}

	public function fetch_all_by_navtype_type_identifier($navtype, $type, $identifier, $locale = '') {
		$navtype = dintval($navtype, true);
		$type = dintval($type, true);
		if($navtype && $type) {
			$wherearr[] = DB::field('navtype', $navtype);
			$wherearr[] = DB::field('type', $type);
			$wherearr[] = DB::field('identifier', $identifier);
			return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE %i', [$this->_table, implode(' AND ', $wherearr)], 'identifier'), $locale);
		}
		return [];
	}

	public function update_by_identifier($identifier, $data) {
		if(is_array($identifier) && empty($identifier)) {
			return 0;
		}
		if(!empty($data) && is_array($data)) {
			$where = DB::field('identifier', $identifier);
			if(array_key_exists('name', $data) || array_key_exists('title', $data)) {
				$existing = DB::result_first('SELECT name FROM %t WHERE %i LIMIT 1', [$this->_table, $where]);
				$data = $this->prepare_localized_data($data, $existing);
			}
			return DB::update($this->_table, $data, $where);
		}
		return 0;
	}

	public function update_by_navtype_type_identifier($navtype, $type, $identifier, $data) {
		if(!empty($data) && is_array($data)) {
			$navtype = dintval($navtype, true);
			$type = dintval($type, true);
			if(is_array($navtype) && empty($navtype) || is_array($type) && empty($type) || is_array($identifier) && empty($identifier)) {
				return 0;
			}
			$wherearr[] = DB::field('navtype', $navtype);
			$wherearr[] = DB::field('type', $type);
			$wherearr[] = DB::field('identifier', $identifier);
			$where = implode(' AND ', $wherearr);
			if(array_key_exists('name', $data) || array_key_exists('title', $data)) {
				$existing = DB::result_first('SELECT name FROM %t WHERE %i LIMIT 1', [$this->_table, $where]);
				$data = $this->prepare_localized_data($data, $existing);
			}
			return DB::update($this->_table, $data, $where);
		}
		return 0;
	}

	public function update_by_type_identifier($type, $identifier, $data) {
		$type = dintval($type, is_array($type));
		if(is_array($identifier) && empty($identifier)) {
			return 0;
		}
		if(!empty($data) && is_array($data)) {
			$where = DB::field('type', $type).' AND '.DB::field('identifier', $identifier);
			if(array_key_exists('name', $data) || array_key_exists('title', $data)) {
				$existing = DB::result_first('SELECT name FROM %t WHERE %i LIMIT 1', [$this->_table, $where]);
				$data = $this->prepare_localized_data($data, $existing);
			}
			return DB::update($this->_table, $data, $where);
		}
		return 0;
	}

	public function delete_by_navtype_id($navtype, $ids) {
		$ids = dintval($ids, is_array($ids));
		$navtype = dintval($navtype, is_array($navtype));
		if($ids) {
			return DB::delete($this->_table, DB::field('id', $ids).' AND '.DB::field('navtype', $navtype));
		}
		return 0;
	}

	public function delete_by_navtype_parentid($navtype, $parentid) {
		$navtype = dintval($navtype, is_array($navtype));
		$parentid = dintval($parentid, is_array($parentid));
		return DB::delete($this->_table, DB::field('navtype', $navtype).' AND '.DB::field('parentid', $parentid));
	}

	public function delete_by_type_identifier($type, $identifier) {
		if(is_array($identifier) && empty($identifier)) {
			return 0;
		}
		$type = dintval($type, is_array($type));
		return DB::delete($this->_table, DB::field('type', $type).' AND '.DB::field('identifier', $identifier));
	}

	public function delete_by_parentid($id) {
		$id = dintval($id, is_array($id));
		if($id) {
			return DB::delete($this->_table, DB::field('parentid', $id));
		}
		return 0;
	}

	public function count_by_navtype_type_identifier($navtype, $type, $identifier) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE navtype=%d AND type=%d AND identifier=%s', [$this->_table, $navtype, $type, $identifier]);
	}

}

