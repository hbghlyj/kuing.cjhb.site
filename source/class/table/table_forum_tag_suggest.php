<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class table_forum_tag_suggest extends discuz_table {
    public function __construct() {
        $this->_table = 'forum_tag_suggest';
        $this->_pk    = 'id';
        parent::__construct();
    }

    public function insert($data, $return_insert_id = false, $replace = false, $silent = false) {
        return DB::insert($this->_table, $data, $return_insert_id, $replace, $silent);
    }

    public function fetch_all_by_status($status, $start = 0, $limit = 0, $count = false) {
        $sql = 'WHERE status=%d';
        $params = array($this->_table, $status);
        if($count) {
            return DB::result_first('SELECT COUNT(*) FROM %t '.$sql, $params);
        }
        $sql .= ' ORDER BY id DESC';
        if($limit) {
            $sql .= DB::limit($start, $limit);
        }
        return DB::fetch_all('SELECT * FROM %t '.$sql, $params, 'id');
    }

    public function update_status($ids, $status) {
        $ids = dintval($ids, true);
        if(!$ids) return 0;
        return DB::query('UPDATE %t SET status=%d WHERE id IN (%n)', array($this->_table, $status, (array)$ids));
    }

    public function delete_by_id($ids) {
        $ids = dintval($ids, true);
        if(!$ids) return 0;
        return DB::query('DELETE FROM %t WHERE id IN (%n)', array($this->_table, (array)$ids));
    }
}
