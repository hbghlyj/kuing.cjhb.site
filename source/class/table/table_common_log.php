<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_log extends discuz_table {

	var $_class = null;

	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$logtype = getglobal('config/log/type') ?? 'mysql';
			if($logtype == 'script') {
				$script = getglobal('config/log/script');
				if(!file_exists($script)) {
					$logtype = 'mysql';
				}
				require_once $script;
			}
			$c = class_exists('table_common_log_'.$logtype) ? 'table_common_log_'.$logtype : 'table_common_log_mysql';
			$_instance = new $c();
		}
		return $_instance;
	}
}

class table_common_log_mysql extends table_common_log {

	public function __construct() {
		$this->_table = 'common_log';
		parent::__construct();
	}

	public function fetch_all_by_conditions($conditions = [], $startlimit = 0, $count = 0, $returncount = 0, $order = ['id' => 'DESC']) {
		$wheresql = ' 1=1 ';
		if(!empty($conditions)) {
			foreach($conditions as $ckey => $cvalue) {
				if($cvalue[0] == 'keyword') {
					$keyword = trim($cvalue[2]);
					if($keyword !== '') {
						$wheresql .= ' AND CONCAT_WS(\' \', uid, loginname, username, type, data, source, device, record, dateline) LIKE '.DB::quote('%'.$keyword.'%').' ';
					}
				} else {
					$wheresql .= ' AND '.$cvalue[0].' '.$cvalue[1].' '.$cvalue[2].' ';
				}
			}
		}
		$ordersql = '';
		if($order) {
			foreach($order as $okey => $ovalue) {
				$ordersql .= (!empty($ordersql) ? ',' : '').$okey.' '.$ovalue;
			}
		}
		if($returncount) {
			return DB::result_first("SELECT count(*) FROM %t WHERE $wheresql", [$this->_table]);
		}
		return DB::fetch_all("SELECT * FROM %t WHERE $wheresql ORDER BY ".$ordersql.' '.DB::limit($startlimit, $count), [$this->_table]);
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		foreach(['data', 'device'] as $field) {
			if(isset($data[$field]) && !is_string($data[$field])) {
				$data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
		}
		return parent::insert($data, $return_insert_id, $replace, $silent);
	}

	public function delete_by_removetime($removetime, $types = []) {
		return DB::query('DELETE FROM %t WHERE dateline < %d AND type IN('.dimplode($types).')', [$this->_table, $removetime]);
	}

	public function delete_by_ids($ids, $conditions = [], $startlimit = 0, $count = 0) {
		$ids = dintval((array)$ids, true);
		if(empty($ids)) {
			return 0;
		}
		return DB::query('DELETE FROM %t WHERE id IN(%n)', [$this->_table, $ids]);
	}

}

class table_common_log_file {

	var $files = [];
	var $indexs = [];

	private function _get_name($type, $date) {
		$type = str_replace(':', '_', $type);
		if(!preg_match('/^\w+$/', $type)) {
			$type = 'system';
		}
		if(!preg_match('/^[0-9]{6}$/', $date)) {
			$date = date('Ym', TIMESTAMP);
		}
		return DISCUZ_DATA.'log/'.$date.'_log_'.$type;
	}

	private function _get_files($type) {
		$type = str_replace(':', '_', $type);
		if(!preg_match('/^\w+$/', $type)) {
			$type = 'system';
		}
		$this->files = glob(DISCUZ_DATA.'log/*_log_'.$type.'.index.php');
		rsort($this->files);
	}

	private function _get_index($fileindex, $file) {
		$datafile = str_replace('.index.php', '.php', $file);
		if(!file_exists($file) || !file_exists($datafile)) {
			return [];
		}
		$indexdata = explode('|', substr(file_get_contents($file), 13));
		if(!$indexdata) {
			return [];
		}
		rsort($indexdata);
		$last = intval(current($indexdata));
		foreach($indexdata as $v) {
			$v = intval($v);
			$len = $last - $v;
			if($len > 0) {
				$this->indexs[] = [$fileindex, $v, $len];
			}
			$last = $v;
		}
	}

	private function _get_data($data) {
		$fps = $return = [];
		$i = 0;
		foreach($data as $row) {
			[$fileindex, $seek, $len] = $row;
			if(!isset($fps[$fileindex])) {
				if(empty($this->files[$fileindex])) {
					continue;
				}
				$file = str_replace('.index.php', '.php', $this->files[$fileindex]);
				if(!file_exists($file)) {
					continue;
				}
				$fps[$fileindex] = fopen($file, 'r');
			}

			fseek($fps[$fileindex], $seek);
			$row = json_decode(substr(fread($fps[$fileindex], $len), 36), true);
			foreach(['data', 'device'] as $field) {
				if(isset($row[$field]) && !is_string($row[$field])) {
					$row[$field] = json_encode($row[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				}
			}
			$row['id'] = $i++;
			$return[] = $row;
		}
		foreach($fps as $fp) {
			fclose($fp);
		}
		return $return;
	}

	private function _filter_indexs_by_conditions($conditions) {
		$keyword = '';
		$jsonfilters = [];
		foreach((array)$conditions as $condition) {
			if($condition[0] == 'keyword') {
				$keyword = trim($condition[2]);
			} elseif(preg_match('/^JSON_EXTRACT\((data|device), \'\$\.(\w+)\'\)$/', $condition[0], $match)) {
				$jsonfilters[] = [$match[1], $match[2], trim($condition[2], "'")];
			}
		}
		if($keyword === '' && empty($jsonfilters)) {
			return;
		}
		$indexs = [];
		foreach($this->_get_data($this->indexs) as $offset => $row) {
			if($keyword !== '' && stripos(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $keyword) === false) {
				continue;
			}
			$matched = true;
			foreach($jsonfilters as $filter) {
				$data = json_decode($row[$filter[0]], true);
				if(!is_array($data) || (string)($data[$filter[1]] ?? '') !== $filter[2]) {
					$matched = false;
					break;
				}
			}
			if($matched) {
				$indexs[] = $this->indexs[$offset];
			}
		}
		$this->indexs = $indexs;
	}

	public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
		$time = date('Y-m-d H:i:s', $data['dateline']);
		$date = date('Ym', $data['dateline']);

		$str = '['.$time.'] ';
		$str .= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$filename = $this->_get_name($data['type'], $date);
		$fp = fopen($filename.'.php', 'a');
		if(!$fp) {
			fclose($fp);
			return;
		}
		fwrite($fp, "<?PHP exit;?>\t".str_replace(['<?', '?>'], '', $str)."\n");
		fflush($fp);
		fclose($fp);
		$str = file_exists($filename.'.index.php') ? '' : '<?PHP exit;?>';
		$str .= '|'.filesize($filename.'.php');
		$fp = fopen($filename.'.index.php', 'a');
		if(!$fp) {
			fclose($fp);
		}
		fwrite($fp, $str);
		fflush($fp);
		fclose($fp);
	}

	public function fetch_all_by_conditions($conditions = [], $startlimit = 0, $count = 0, $returncount = 0, $order = ['id' => 'DESC']) {
		$this->indexs = [];
		if($conditions[0][0] != 'type') {
			return [];
		}
		$type = substr($conditions[0][2], 1, -1);
		$this->_get_files($type);
		foreach($this->files as $fileindex => $file) {
			$this->_get_index($fileindex, $file);
		}
		$this->_filter_indexs_by_conditions($conditions);
		if($returncount) {
			return count($this->indexs);
		}
		return $this->_get_data(array_slice($this->indexs, $startlimit, $count));
	}

	public function delete_by_removetime($removetime, $types = []) {
	}

	public function delete_by_ids($ids, $conditions = [], $startlimit = 0, $count = 0) {
		$ids = dintval((array)$ids, true);
		if(empty($ids)) {
			return 0;
		}
		$this->indexs = [];
		if($conditions[0][0] != 'type') {
			return 0;
		}
		$type = substr($conditions[0][2], 1, -1);
		$this->_get_files($type);
		foreach($this->files as $fileindex => $file) {
			$this->_get_index($fileindex, $file);
		}
		$this->_filter_indexs_by_conditions($conditions);
		$delete = [];
		foreach(array_slice($this->indexs, $startlimit, $count) as $rowid => $index) {
			if(in_array($rowid, $ids)) {
				$delete[$index[0].':'.$index[1]] = true;
			}
		}
		if(empty($delete)) {
			return 0;
		}
		$deleted = 0;
		foreach($this->files as $fileindex => $indexfile) {
			$datafile = str_replace('.index.php', '.php', $indexfile);
			if(!file_exists($datafile)) {
				continue;
			}
			$rows = file($datafile);
			$newrows = [];
			$offset = 0;
			$filedeleted = 0;
			foreach($rows as $row) {
				$key = $fileindex.':'.$offset;
				if(isset($delete[$key])) {
					$filedeleted++;
				} else {
					$newrows[] = $row;
				}
				$offset += strlen($row);
			}
			if($filedeleted) {
				$deleted += $filedeleted;
				file_put_contents($datafile, implode('', $newrows), LOCK_EX);
				$index = '<?PHP exit;?>';
				$offset = 0;
				foreach($newrows as $row) {
					$offset += strlen($row);
					$index .= '|'.$offset;
				}
				file_put_contents($indexfile, $index, LOCK_EX);
			}
		}
		return $deleted;
	}

}
