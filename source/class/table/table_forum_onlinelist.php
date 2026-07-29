<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_onlinelist extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'forum_onlinelist';
		$this->_pk = '';

		parent::__construct();
	}

	public static function localize_row($row, $locale = '') {
		$row['title_i18n'] = i18n::decodeValue($row['title']);
		$row['title'] = i18n::localizeValue($row['title_i18n'], $locale);
		return $row;
	}

	public static function localize_rows($rows, $locale = '') {
		foreach((array)$rows as $key => $row) {
			$rows[$key] = self::localize_row($row, $locale);
		}
		return $rows;
	}

	public function range($start = 0, $limit = 0, $sort = '', $locale = '') {
		return self::localize_rows(parent::range($start, $limit, $sort), $locale);
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		if(array_key_exists('title', $data)) {
			$data['title'] = i18n::encodeValue($data['title']);
		}
		return parent::insert($data, $return_insert_id, $replace, $silent);
	}

	public function fetch_all_order_by_displayorder($locale = '') {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t ORDER BY displayorder', [$this->_table]), $locale);
	}

	public function delete_all() {
		DB::query('DELETE FROM %t', [$this->_table]);
	}

	public function delete_by_groupid($groupid) {
		$groupid = is_array($groupid) ? array_map('intval', (array)$groupid) : dintval($groupid);
		if($groupid) {
			return DB::delete($this->_table, DB::field('groupid', $groupid));
		}
		return 0;
	}

	public function update_by_groupid($groupid, $data) {
		$groupid = is_array($groupid) ? array_map('intval', (array)$groupid) : dintval($groupid);
		if($groupid && $data && is_array($data)) {
			if(array_key_exists('title', $data)) {
				$existing = DB::result_first('SELECT title FROM %t WHERE groupid=%d', [$this->_table, $groupid]);
				$data['title'] = i18n::encodeValue($data['title'], $existing);
			}
			return DB::update($this->_table, $data, DB::field('groupid', $groupid));
		}
		return 0;
	}
}

