<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_forum_emailpost extends discuz_table {

	public function __construct() {
		$this->_table = 'forum_emailpost';
		$this->_pk = 'messagekey';
		parent::__construct();
	}

	public function reserve($data) {
		return $this->insert($data, false, false, true);
	}
}
