<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_editlog_attachment extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'forum_editlog_attachment';
		$this->_pk = 'editid';
		parent::__construct();
	}

	public function insert_all($editid, array $attachments) {
		foreach($attachments as $attachment) {
			$aid = dintval($attachment['aid']);
			if(!$aid) {
				continue;
			}
			DB::insert($this->_table, [
				'editid' => dintval($editid),
				'aid' => $aid,
				'attachment_data' => json_encode($attachment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			], false, true);
		}
	}

	public function fetch_all_by_editid($editid) {
		return $this->fetch_all_by_editids([$editid]);
	}

	public function fetch_all_by_editids(array $editids) {
		$rows = [];
		foreach(DB::fetch_all('SELECT * FROM %t WHERE editid IN(%n) ORDER BY aid', [$this->_table, dintval($editids, true)]) as $row) {
			$data = json_decode($row['attachment_data'], true);
			if(is_array($data)) {
				$rows[$row['editid']][$row['aid']] = $data;
			}
		}
		return $rows;
	}

	public function delete_by_editids(array $editids) {
		$rows = $this->fetch_all_by_editids($editids);
		if($rows) {
			DB::delete($this->_table, DB::field('editid', dintval($editids, true)));
		}
		return $rows;
	}

	public function count_by_aid($aid) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE aid=%d', [$this->_table, dintval($aid)]);
	}
}
