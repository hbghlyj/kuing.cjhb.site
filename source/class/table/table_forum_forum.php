<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_forum extends discuz_table {
	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'forum_forum';
		$this->_pk = 'fid';
		$this->_pre_cache_key = 'forum_forum_';

		parent::__construct();
	}

	public static function name_locale() {
		if(function_exists('currentlang')) {
			return currentlang() ?: 'SC_UTF8';
		}
		if(defined('DISCUZ_LANG')) {
			return DISCUZ_LANG == 'EN/' ? 'EN_UTF8' : (DISCUZ_LANG == 'TC/' ? 'TC_UTF8' : 'SC_UTF8');
		}
		return 'SC_UTF8';
	}

	public static function decode_name($name) {
		if(is_array($name)) {
			$names = $name;
		} elseif(is_string($name) && $name !== '') {
			$names = json_decode($name, true);
			if(!is_array($names)) {
				$names = ['SC_UTF8' => $name];
			}
		} else {
			$names = [];
		}
		foreach($names as $locale => $value) {
			if(!is_string($locale) || !preg_match('/^[A-Z]{2}_[A-Z0-9]+$/', $locale) || !is_scalar($value)) {
				unset($names[$locale]);
				continue;
			}
			$names[$locale] = (string)$value;
		}
		return $names;
	}

	public static function localize_name($name, $locale = '') {
		$names = self::decode_name($name);
		$locale = $locale ?: self::name_locale();
		$fallbacks = array_unique(array_filter([
			$locale,
			getglobal('setting/i18n_default'),
			'SC_UTF8',
			'EN_UTF8',
			'TC_UTF8',
		]));
		foreach($fallbacks as $fallback) {
			if(isset($names[$fallback]) && $names[$fallback] !== '') {
				return $names[$fallback];
			}
		}
		foreach($names as $value) {
			if($value !== '') {
				return $value;
			}
		}
		return '';
	}

	public static function localize_row($row) {
		if(!is_array($row) || !array_key_exists('name', $row) && !array_key_exists('name_i18n', $row)) {
			return $row;
		}
		$names = self::decode_name($row['name_i18n'] ?? $row['name']);
		$row['name_i18n'] = $names;
		$row['name'] = self::localize_name($names);
		return $row;
	}

	public static function localize_rows($rows) {
		foreach((array)$rows as $key => $row) {
			$rows[$key] = self::localize_row($row);
		}
		return $rows;
	}

	private static function encode_name($name, $existing = []) {
		if(is_array($name)) {
			$names = self::decode_name($name);
		} else {
			$names = self::decode_name($existing);
			$names[self::name_locale()] = (string)$name;
		}
		return json_encode($names, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
	}

	private function fetch_raw_name($fid) {
		return DB::result_first('SELECT name FROM %t WHERE fid=%d', [$this->_table, $fid]);
	}

	public function fetch($id, $force_from_db = false) {
		return self::localize_row(parent::fetch($id, $force_from_db));
	}

	public function fetch_all($ids, $force_from_db = false) {
		return self::localize_rows(parent::fetch_all($ids, $force_from_db));
	}

	public function range($start = 0, $limit = 0, $sort = '') {
		return self::localize_rows(parent::range($start, $limit, $sort));
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		if(array_key_exists('name', $data)) {
			$data['name'] = self::encode_name($data['name']);
		}
		return parent::insert($data, $return_insert_id, $replace, $silent);
	}

	public function fetch_all_by_status($status, $orderby = 1) {
		$status = $status ? 1 : 0;
		$ordersql = $orderby ? 'ORDER BY f.type, f.displayorder' : '';
		return self::localize_rows(DB::fetch_all('SELECT f.* FROM '.DB::table($this->_table)." f WHERE f.status='$status' $ordersql"));
	}

	public function fetch_all_fids($allstatus = 0, $type = '', $fup = '', $start = 0, $limit = 0, $count = 0) {
		$typesql = empty($type) ? "type<>'group'" : DB::field('type', $type);
		$statussql = empty($allstatus) ? ' AND status<>3' : '';
		$fupsql = empty($fup) ? '' : ' AND '.DB::field('fup', $fup);
		$limitsql = empty($limit) ? '' : ' LIMIT '.$start.', '.$limit;
		if($count) {
			return DB::result_first('SELECT count(*) FROM '.DB::table($this->_table)." WHERE $typesql $statussql $fupsql");
		}
		return self::localize_rows(DB::fetch_all('SELECT * FROM '.DB::table($this->_table)." WHERE $typesql $statussql $fupsql $limitsql"));
	}

	public function fetch_info_by_fid($fid) {
		$cache_name = $fid.'_with_fields';
		if(($data = $this->fetch_cache($cache_name)) === false) {
			$data = DB::fetch_first('SELECT ff.*, f.* FROM %t f LEFT JOIN %t ff ON ff.fid=f.fid WHERE f.fid=%d', [$this->_table, 'forum_forumfield', $fid]);
			$this->store_cache($cache_name, $data);
		}
		return self::localize_row($data);
	}

	public function fetch_all_name_by_fid($fids) {
		if(empty($fids)) {
			return [];
		}
		return self::localize_rows(DB::fetch_all('SELECT fid, name FROM '.DB::table($this->_table).' WHERE '.DB::field('fid', $fids), [], 'fid'));
	}

	public function fetch_all_info_by_fids($fids, $status = 0, $limit = 0, $fup = 0, $displayorder = 0, $onlyforum = 0, $noredirect = 0, $type = '', $start = 0) {
		$sql = $fids ? 'f.'.DB::field('fid', $fids) : '';
		$sql .= empty($fup) ? '' : ($sql ? ' AND ' : '').'f.'.DB::field('fup', $fup);
		if(!strcmp($status, 'available')) {
			$sql .= ($sql ? ' AND ' : '')." f.status>'0'";
		} elseif($status) {
			$sql .= $status ? ($sql ? ' AND ' : '').' f.'.DB::field('status', $status) : '';
		}
		$sql .= $onlyforum ? ($sql ? ' AND ' : '').'f.type<>\'group\'' : '';
		$sql .= $type ? ($sql ? ' AND ' : '').'f.'.DB::field('type', $type) : '';
		$sql .= $noredirect ? ($sql ? ' AND ' : '').'ff.redirect=\'\'' : '';
		$ordersql = $displayorder ? ' ORDER BY f.displayorder' : '';
		$limitsql = $limit ? DB::limit($start, $limit) : '';
		if(!$sql) {
			return [];
		}
		return self::localize_rows(DB::fetch_all("SELECT ff.*, f.* FROM %t f LEFT JOIN %t ff USING (fid) WHERE $sql $ordersql $limitsql", [$this->_table, 'forum_forumfield'], 'fid'));
	}

	public function fetch_all_default_recommend($num = 10) {
		return self::localize_rows(DB::fetch_all('SELECT f.fid, f.name, ff.description, ff.icon FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield')." ff USING(fid) WHERE f.status='3' AND f.type='sub' ORDER BY f.commoncredits desc ".DB::limit($num)));
	}

	public function fetch_all_group_type($alltypeorder = 0) {
		$ordersql = empty($alltypeorder) ? 'f.type, ' : "f.type<>'group', ";
		return self::localize_rows(DB::fetch_all('SELECT f.fid, f.type, f.status, f.name, f.fup, f.displayorder, f.forumcolumns, f.inheritedmod, ff.moderators, ff.password, ff.redirect, ff.groupnum FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield')." ff USING(fid) WHERE f.status='3' AND f.type IN('group', 'forum') ORDER BY $ordersql f.displayorder"));
	}

	public function fetch_all_recommend_by_fid($fid) {
		return self::localize_rows(DB::fetch_all('SELECT ff.*, f.* FROM %t f LEFT JOIN %t ff ON ff.fid=f.fid WHERE f.recommend=%d', [$this->_table, 'forum_forumfield', $fid]));
	}

	public function fetch_all_info_by_ignore_fid($fid) {
		if(!intval($fid)) {
			return [];
		}
		return self::localize_rows(DB::fetch_all('SELECT fid, type, name, fup FROM '.DB::table($this->_table).' WHERE '.DB::field('fid', $fid, '<>')." AND type<>'sub' AND status<>'3' ORDER BY displayorder"));
	}

	public function fetch_all_forum($status = 0) {
		$statusql = intval($status) ? 'f.'.DB::field('status', $status) : 'f.status<>\'3\'';
		return self::localize_rows(DB::fetch_all('SELECT ff.*, f.*, a.uid FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield').' ff ON ff.fid=f.fid LEFT JOIN '.DB::table('forum_access')." a ON a.fid=f.fid AND a.allowview>'0' WHERE $statusql ORDER BY f.type, f.displayorder"));
	}

	public function fetch_all_subforum_by_fup($fups) {
		return self::localize_rows(DB::fetch_all("SELECT fid, fup, name, threads, posts, todayposts, domain FROM %t WHERE status='1' AND fup IN (%n) AND type='sub' ORDER BY displayorder", [$this->_table, $fups]));
	}

	public function fetch_all_forum_ignore_access() {
		return self::localize_rows(DB::fetch_all('SELECT ff.*, f.* FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield').' ff ON ff.fid=f.fid WHERE status <3 ORDER BY f.fid'));
	}

	public function fetch_all_forum_for_sub_order() {
		return self::localize_rows(DB::fetch_all('SELECT ff.*, f.fid, f.type, f.status, f.name, f.fup, f.displayorder, f.inheritedmod FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield')." ff USING(fid) WHERE f.status<>'3' ORDER BY f.type<>'group', f.displayorder"));
	}

	public function fetch_all_valid_forum() {
		return self::localize_rows(DB::fetch_all('SELECT * FROM '.DB::table($this->_table)." WHERE status='1' AND type IN ('forum', 'sub') ORDER BY type"));
	}

	public function fetch_all_valid_fieldinfo() {
		return DB::fetch_all('SELECT ff.* FROM '.DB::table($this->_table).' f INNER JOIN '.DB::table('forum_forumfield')." ff USING(fid) WHERE f.status='1'");
	}

	public function fetch_threadcacheon_num() {
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table)." WHERE status='1' AND threadcaches>0");
	}

	public function fetch_all_by_recyclebin($recyclebin = 0) {
		return self::localize_rows(DB::fetch_all('SELECT fid, name FROM %t WHERE status<3 AND type IN (\'forum\', \'sub\') AND recyclebin=%d', [$this->_table, $recyclebin]));
	}

	public function update($val, $data, $unbuffered = false, $low_priority = false) {
		if(array_key_exists('name', $data)) {
			if(is_array($val) && !is_array($data['name'])) {
				$return = 0;
				foreach($val as $fid) {
					$row = $data;
					$row['name'] = self::encode_name($row['name'], $this->fetch_raw_name($fid));
					$return += (int)parent::update($fid, $row, $unbuffered, $low_priority);
					$this->clear_cache([$fid, $fid.'_with_fields']);
				}
				return $return;
			}
			$data['name'] = self::encode_name($data['name'], is_array($val) ? [] : $this->fetch_raw_name($val));
		}
		$cacheids = [];
		foreach((array)$val as $fid) {
			$cacheids[] = $fid;
			$cacheids[] = $fid.'_with_fields';
		}
		$this->clear_cache($cacheids);
		return parent::update($val, $data, $unbuffered, $low_priority);
	}

	public function update_threadcaches($threadcache, $fids) {
		if(empty($fids)) {
			return false;
		}
		$sqladd = in_array('all', $fids) ? '' : ' WHERE '.DB::field('fid', $fids);
		DB::query('UPDATE '.DB::table($this->_table)." SET threadcaches='".intval($threadcache)."'$sqladd");
	}

	public function update_allowhtml($fids, $val = 0) {
		return DB::update('forum_forum', ['allowhtml' => $val], 'fid IN ('.dimplode($fids).')');
	}

	public function update_styleid($ids) {
		DB::query('UPDATE '.DB::table($this->_table)." SET styleid='0' WHERE styleid IN(%n)", [$ids]);
	}

	public function fetch_forum_num($type = '', $fup = '') {
		$fupsql = $fup ? DB::field('fup', $fup).' AND ' : '';
		$addwhere = $type == 'group' ? "`status`='3'" : '`status`<>3';
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table)." WHERE $fupsql $addwhere");
	}

	public function check_forum_exists($fids, $issub = 1) {
		if(empty($fids)) {
			return false;
		}
		$typesql = $issub ? " AND type<>'group'" : '';
		return DB::result_first('SELECT COUNT(*) FROM '.DB::table($this->_table).' WHERE %i'.$typesql, [DB::field('fid', $fids)]);
	}

	public function fetch_sum_todaypost() {
		return DB::result_first('SELECT sum(todayposts) FROM '.DB::table($this->_table));
	}

	public function fetch_group_counter() {
		return DB::fetch_first('SELECT SUM(todayposts) AS todayposts, COUNT(fid) AS groupnum FROM '.DB::table($this->_table)." WHERE status='3' AND type='sub'");
	}

	public function fetch_all_sub_group_by_fup($fups, $limit = 20) {
		return self::localize_rows(DB::fetch_all("SELECT fid, fup, name FROM %t WHERE fup IN(%n) AND type='sub' AND level>'-1' ORDER BY commoncredits DESC LIMIT %d", [$this->_table, $fups, $limit], $this->_pk));
	}

	public function fetch_all_for_threadsorts() {
		return self::localize_rows(DB::fetch_all('SELECT f.fid, f.name, ff.threadsorts FROM '.DB::table($this->_table).' f , '.DB::table('forum_forumfield')." ff WHERE ff.threadsorts<>'' AND f.fid=ff.fid"));
	}

	public function fetch_all_for_search($conditions, $start = 0, $limit = 20) {
		if(empty($conditions)) {
			return [];
		}
		if($start == -1) {
			return DB::result_first('SELECT count(*) FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield')." ff ON f.fid=ff.fid
			WHERE status='3' AND type='sub' AND %i", [$conditions]);
		}
		return self::localize_rows(DB::fetch_all('SELECT f.fid, f.fup, f.type, f.name, f.posts, f.threads, ff.membernum, ff.lastupdate, ff.dateline, ff.foundername, ff.founderuid FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield')." ff ON f.fid=ff.fid
			WHERE status='3' AND type='sub' AND %i ".DB::limit($start, $limit), [$conditions]));
	}

	public function clear_todayposts() {
		DB::query('UPDATE '.DB::table($this->_table)." SET todayposts='0'");
	}

	public function clear_forum_counter_for_group() {
		DB::query('UPDATE '.DB::table($this->_table)." SET threads='0', posts='0' WHERE type='group'");
	}

	public function update_forum_counter($fid, $threads = 0, $posts = 0, $todayposts = 0, $modwork = 0, $favtimes = 0) {
		if(!dintval($fid)) {
			return false;
		}
		$addsql = [];
		if($threads) {
			$addsql[] = "threads=threads+'".intval($threads)."'";
		}
		if($posts) {
			$addsql[] = "posts=posts+'".intval($posts)."'";
		}
		if($todayposts) {
			$addsql[] = "todayposts=todayposts+'".intval($todayposts)."'";
		}
		if($modwork) {
			$addsql[] = "modworks='1'";
		}
		if($favtimes) {
			$addsql[] = "favtimes=favtimes+'".intval($favtimes)."'";
		}
		if($addsql) {
			DB::query('UPDATE '.DB::table($this->_table).' SET '.implode(', ', $addsql).' WHERE '.DB::field('fid', $fid), 'UNBUFFERED');
		}
	}

	public function update_commoncredits($fid) {
		if(!intval($fid)) {
			return false;
		}
		DB::query('UPDATE '.DB::table($this->_table).' SET commoncredits=commoncredits+1 WHERE '.DB::field('fid', $fid));
	}

	public function update_oldrank_and_yesterdayposts() {
		DB::query('UPDATE '.DB::table($this->_table).' SET oldrank=`rank`,yesterdayposts=todayposts');
	}

	public function update_group_level($levelid, $fid) {
		if(!intval($levelid) || !intval($fid)) {
			return false;
		}
		DB::query('UPDATE '.DB::table($this->_table).' SET level=%d WHERE fid=%d', [$levelid, $fid]);
	}

	public function fetch_all_fid_for_group($start, $limit, $issub = 0, $conditions = '') {
		if(!empty($conditions) && !is_string($conditions)) {
			return [];
		}
		$typesql = $issub ? 'type=\'sub\'' : 'type<>\'sub\'';
		return DB::fetch_all('SELECT fid FROM '.DB::table($this->_table)." WHERE status='3' AND $typesql %i ".DB::limit($start, $limit), [$conditions]);
	}

	public function fetch_groupnum_by_fup($fup) {
		if(!intval($fup)) {
			return false;
		}
		return DB::result_first('SELECT COUNT(*) as num FROM '.DB::table($this->_table)." WHERE fup=%d AND type='sub' GROUP BY fup", [$fup]);
	}

	public function fetch_all_group_for_ranking() {
		return DB::fetch_all('SELECT fid FROM '.DB::table($this->_table)." WHERE type='sub' AND status='3' ORDER BY commoncredits DESC LIMIT 0, 1000");
	}

	public function fetch_all_for_ranklist($status, $type, $orderfield, $start = 0, $limit = 0, $ignorefids = []) {
		if(empty($orderfield)) {
			return [];
		}
		$typesql = $type ? ' AND f.'.DB::field('type', $type) : ' AND f.type<>\'group\'';
		$ignoresql = $ignorefids ? ' AND f.fid NOT IN('.dimplode($ignorefids).')' : '';
		$fields = $jointable = '';
		if($orderfield == 'membernum') {
			$fields = ', ff.membernum';
			$jointable = ' LEFT JOIN '.DB::table('forum_forumfield').' ff ON ff.fid=f.fid';
			$orderfield = 'ff.'.$orderfield;
		}
		return self::localize_rows(DB::fetch_all("SELECT f.* $fields FROM %t f $jointable WHERE f.status=%d $typesql $ignoresql ORDER BY %i DESC ".DB::limit($start, $limit), [$this->_table, $status, $orderfield]));
	}

	public function fetch_fid_by_name($name) {
		foreach(DB::fetch_all('SELECT fid, name FROM %t', [$this->_table]) as $forum) {
			if(in_array($name, self::decode_name($forum['name']), true)) {
				return $forum['fid'];
			}
		}
		return 0;
	}

	public function insert_group($fup, $type, $name, $status, $level) {
		return $this->insert(['fup' => $fup, 'type' => $type, 'name' => $name, 'status' => $status, 'level' => $level], true);
	}

	public function fetch_all_by_fid($fids) {
		return self::localize_rows(DB::fetch_all('SELECT * FROM %t WHERE fid IN(%n)', [$this->_table, (array)$fids], $this->_pk));
	}

	public function delete_by_fid($fids) {
		if(empty($fids)) {
			return false;
		}
		DB::query('DELETE FROM '.DB::table($this->_table).' WHERE %i', [DB::field('fid', $fids)]);
		DB::query('DELETE FROM '.DB::table('forum_forumfield').' WHERE %i', [DB::field('fid', $fids)]);
	}

	public function update_fup_by_fup($sourcefup, $targetfup) {
		DB::query('UPDATE '.DB::table($this->_table).' SET fup=%d WHERE fup=%s', [$targetfup, $sourcefup]);
	}

	public function validate_level_for_group($fids) {
		if(empty($fids)) {
			return false;
		}
		DB::query('UPDATE '.DB::table($this->_table)." SET level='0' WHERE %i", [DB::field('fid', $fids)]);
	}

	public function validate_level_num() {
		return DB::result_first('SELECT count(*) FROM '.DB::table($this->_table)." WHERE status='3' AND level='-1'");
	}

	public function fetch_all_validate($start, $limit) {
		return self::localize_rows(DB::fetch_all('SELECT f.*, ff.dateline, ff.founderuid, ff.foundername, ff.description FROM '.DB::table($this->_table).' f LEFT JOIN '.DB::table('forum_forumfield')." ff ON ff.fid=f.fid WHERE status='3' AND level='-1' ORDER BY f.fid DESC LIMIT ".intval($start).', '.intval($limit)));
	}

	public function update_archive($fids) {
		return DB::update('forum_forum', ['archive' => '0'], 'fid NOT IN ('.dimplode($fids).')');
	}

	public function fetch_all_for_grouplist($orderby = 'displayorder', $fieldarray = [], $num = 1, $fids = [], $sort = 0, $getcount = 0) {
		if($fieldarray && is_array($fieldarray)) {
			$fieldadd = '';
			foreach($fieldarray as $field) {
				$fieldadd .= $field.', ';
			}
		} else {
			$fieldadd = 'ff.*, ';
		}
		$start = 0;
		if(is_array($num)) {
			list($start, $snum) = $num;
		} else {
			$snum = $num;
		}
		$orderbyarray = ['displayorder' => 'f.displayorder DESC', 'dateline' => 'ff.dateline DESC', 'lastupdate' => 'ff.lastupdate DESC', 'membernum' => 'ff.membernum DESC', 'thread' => 'f.threads DESC', 'activity' => 'f.commoncredits DESC'];
		$useindex = $orderby == 'displayorder' ? 'USE INDEX(fup_type)' : '';
		$orderby = !empty($orderby) && $orderbyarray[$orderby] ? 'ORDER BY '.$orderbyarray[$orderby].', f.fid DESC' : 'ORDER BY f.fid DESC';
		$limitsql = $num ? "LIMIT $start, $snum " : '';
		$field = $sort ? 'fup' : 'fid';
		$fids = $fids && is_array($fids) ? 'f.'.$field.' IN ('.dimplode($fids).')' : '';
		if(empty($fids)) {
			$levelsql = " AND f.level>'-1'";
		}

		$fieldsql = $fieldadd.' f.fid, f.name, f.threads, f.posts, f.todayposts, f.level as flevel ';
		if($getcount) {
			return DB::result_first('SELECT count(*) FROM '.DB::table($this->_table)." f $useindex WHERE".($fids ? " $fids AND " : '')." f.type='sub' AND f.status=3 $levelsql");
		}
		return self::localize_rows(DB::fetch_all("SELECT $fieldsql FROM ".DB::table($this->_table)." f $useindex LEFT JOIN ".DB::table('forum_forumfield').' ff ON ff.fid=f.fid WHERE'.($fids ? " $fids AND " : '')." f.type='sub' AND f.status=3 $levelsql $orderby $limitsql"));
	}

	function fetch_table_struct($tablename, $result = 'FIELD') {
		if(empty($tablename)) {
			return [];
		}
		$datas = [];
		$query = DB::query('DESCRIBE '.DB::table($tablename));
		while($data = DB::fetch($query)) {
			$datas[$data['Field']] = $result == 'FIELD' ? $data['Field'] : $data;
		}
		return $datas;
	}

	/**
	 * Build lastpost string with proper sanitization and truncation
	 * @param int $tid Thread ID
	 * @param string $subject Thread subject
	 * @param int $dateline Post dateline timestamp
	 * @param string $author Post author name
	 * @return string Tab-delimited lastpost string
	 */
	public function build_lastpost_string($tid, $subject, $dateline, $author) {
		$subject = str_replace("\t", ' ', $subject);
		$author = str_replace("\t", ' ', $author);
		if(function_exists('cutstr')) {
			$subject = cutstr($subject, 80);
		}
		return $tid."\t".$dateline."\t".$author."\t".$subject;
	}

	/**
	 * Update forum lastpost with optional parent propagation
	 * @param int $fid Forum ID to update
	 * @param int $tid Thread ID
	 * @param string $subject Thread subject
	 * @param int $dateline Post dateline timestamp
	 * @param string $author Post author name
	 * @param array $options Options: propagate_parent (bool), forum (array)
	 * @return string The lastpost string that was stored
	 */
	public function update_lastpost($fid, $tid, $subject, $dateline, $author, $options = array()) {
		$propagate_parent = isset($options['propagate_parent']) ? $options['propagate_parent'] : true;
		$forum = isset($options['forum']) ? $options['forum'] : null;

		$lastpost = $this->build_lastpost_string($tid, $subject, $dateline, $author);

		$this->update($fid, array('lastpost' => $lastpost));

		if($propagate_parent) {
			if(!$forum) {
				$forum = $this->fetch_info_by_fid($fid);
			}
			if($forum && $forum['type'] == 'sub' && !empty($forum['fup'])) {
				$this->update($forum['fup'], array('lastpost' => $lastpost));
			}
		}

		return $lastpost;
	}

	function get_forum_by_fid($fid, $field = '', $table = 'forum') {
		static $forumlist = ['forum' => [], 'forumfield' => []];
		$table = $table != 'forum' ? 'forumfield' : 'forum';
		$return = [];
		if(!array_key_exists($fid, $forumlist[$table])) {
			$forumlist[$table][$fid] = DB::fetch_first('SELECT * FROM '.DB::table('forum_'.$table).' WHERE fid=%d', [$fid]);
			if($table == 'forum') {
				$forumlist[$table][$fid] = self::localize_row($forumlist[$table][$fid]);
			}
			if(!is_array($forumlist[$table][$fid])) {
				$forumlist[$table][$fid] = [];
			}
		}

		if(!empty($field)) {
			$return = $forumlist[$table][$fid][$field] ?? null;
		} else {
			$return = $forumlist[$table][$fid];
		}
		return $return;
	}
}

