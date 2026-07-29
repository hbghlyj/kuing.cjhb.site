<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_secquestion extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'common_secquestion';
		$this->_pk = 'id';

		parent::__construct();
	}

	public function fetch_all($ids = null, $force_from_db = false) {

			$ids = $ids === null ? 0 : $ids;
			$force_from_db = $force_from_db === false ? 0 : $force_from_db;
			return $this->fetch_all_secquestion($ids, $force_from_db);

	}

	public function fetch_all_secquestion($start = 0, $limit = 0) {
		return DB::fetch_all('SELECT * FROM %t'.DB::limit($start, $limit), [$this->_table]);
	}

	public static function question_locales() {
		return ['SC', 'TC', 'EN'];
	}

	public static function decode_question($question) {
		$questions = is_array($question) ? $question : json_decode((string)$question, true);
		if(!is_array($questions)) {
			return [];
		}
		$result = [];
		foreach(self::question_locales() as $locale) {
			if(isset($questions[$locale]) && is_scalar($questions[$locale])) {
				$value = trim((string)$questions[$locale]);
				if($value !== '') {
					$result[$locale] = $value;
				}
			}
		}
		return $result;
	}

	public static function encode_question($question) {
		return json_encode(self::decode_question($question), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function localize_question($question) {
		$questions = self::decode_question($question);
		$locale = currentlang();
		foreach(array_unique([$locale, 'SC', 'EN', 'TC']) as $fallback) {
			if(isset($questions[$fallback])) {
				return $questions[$fallback];
			}
		}
		return reset($questions) ?: '';
	}

	public function delete_by_type($type) {
		DB::query('DELETE FROM %t WHERE type=%d', [$this->_table, $type]);
	}

}

