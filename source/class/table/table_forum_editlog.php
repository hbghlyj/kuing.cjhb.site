<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_editlog extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {
		$this->_table = 'forum_editlog';
		$this->_pk = 'editid';
		parent::__construct();
	}

	public function fetch_all_by_pid($pid) {
		return DB::fetch_all('SELECT * FROM %t WHERE pid=%d ORDER BY dateline DESC, editid DESC', [$this->_table, dintval($pid)]);
	}

	public function fetch_all_by_authorid($authorid, $start = 0, $limit = 100) {
		return DB::fetch_all('SELECT e.*, t.subject AS thread_subject, t.fid FROM %t e LEFT JOIN %t t ON t.tid=e.tid WHERE e.authorid=%d ORDER BY e.dateline DESC, e.editid DESC '.DB::limit($start, $limit), [$this->_table, 'forum_thread', dintval($authorid)]);
	}

	public function count_by_authorid($authorid) {
		return DB::result_first('SELECT COUNT(*) FROM %t WHERE authorid=%d', [$this->_table, dintval($authorid)]);
	}

	public function fetch_by_editid_pid($editid, $pid) {
		return DB::fetch_first('SELECT * FROM %t WHERE editid=%d AND pid=%d', [$this->_table, dintval($editid), dintval($pid)]);
	}

	public function count_by_pids($pids) {
		if(empty($pids)) {
			return [];
		}
		$rows = DB::fetch_all('SELECT pid, COUNT(*) AS count FROM %t WHERE pid IN(%n) GROUP BY pid', [$this->_table, dintval($pids, true)]);
		$result = [];
		foreach($rows as $row) {
			$result[$row['pid']] = (int)$row['count'];
		}
		return $result;
	}

	public function count_for_admin($keyword = '') {
		$where = '';
		$params = [$this->_table];
		if($keyword !== '') {
			$like = '%'.$keyword.'%';
			$where = ' WHERE username LIKE %s OR old_subject LIKE %s OR old_message LIKE %s OR old_content LIKE %s';
			$params = array_merge($params, [$like, $like, $like, $like]);
		}
		return DB::result_first('SELECT COUNT(*) FROM %t'.$where, $params);
	}

	public function delete_by_dateline($dateline) {
		$rows = DB::fetch_all('SELECT editid FROM %t WHERE dateline<%d', [$this->_table, dintval($dateline)]);
		return $this->delete_with_attachments(array_column($rows, 'editid'));
	}

	public function delete_by_ids($ids) {
		$ids = dintval((array)$ids, true);
		if(empty($ids)) {
			return 0;
		}
		return $this->delete_with_attachments($ids);
	}

	public function delete_by_keyword($keyword = '') {
		$where = '';
		$params = [$this->_table];
		if($keyword !== '') {
			$like = '%'.$keyword.'%';
			$where = ' WHERE username LIKE %s OR old_subject LIKE %s OR old_message LIKE %s OR old_content LIKE %s';
			$params = array_merge($params, [$like, $like, $like, $like]);
		}
		$rows = DB::fetch_all('SELECT editid FROM %t'.$where, $params);
		return $this->delete_with_attachments(array_column($rows, 'editid'));
	}

	public function fetch_all_for_admin($keyword = '', $start = 0, $limit = 20) {
		$where = '';
		$params = [$this->_table];
		if($keyword !== '') {
			$like = '%'.$keyword.'%';
			$where = ' WHERE username LIKE %s OR old_subject LIKE %s OR old_message LIKE %s OR old_content LIKE %s';
			$params = array_merge($params, [$like, $like, $like, $like]);
		}
		return DB::fetch_all('SELECT * FROM %t'.$where.' ORDER BY dateline DESC, editid DESC '.DB::limit($start, $limit), $params);
	}

	private function delete_with_attachments(array $editids) {
		$editids = dintval($editids, true);
		if(empty($editids)) {
			return 0;
		}
		$attachments = table_forum_editlog_attachment::t()->delete_by_editids($editids);
		$result = DB::delete($this->_table, DB::field('editid', $editids));
		if(!$attachments) {
			return $result;
		}
		require_once libfile('function/forum');
		foreach($attachments as $revisionAttachments) {
			foreach($revisionAttachments as $aid => $attachment) {
				if(DB::result_first('SELECT aid FROM %t WHERE aid=%d', ['forum_attachment', $aid])
					|| table_forum_editlog_attachment::t()->count_by_aid($aid)) {
					continue;
				}
				dunlink($attachment);
			}
		}
		return $result;
	}
}
