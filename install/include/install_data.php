<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') && PHP_SAPI !== 'cli') {
	exit('Access Denied');
}

function localize_install_data($table, $data) {
	if(!in_array($table, ['common_nav', 'forum_onlinelist', 'common_setting'], true)) {
		return $data;
	}

	$translated = [];
	$locales = $table == 'common_nav' ? ['SC', 'TC', 'EN'] : ['SC', 'TC'];
	foreach($locales as $locale) {
		$file = ROOT_PATH.'./source/i18n/'.$locale.'/install/lang_sql_install/table_'.$table.'.php';
		$translated[$locale] = (static function($file) {
			$data = [];
			require $file;
			return $data;
		})($file);
	}

	$key = $table == 'common_nav' ? 'id' : ($table == 'forum_onlinelist' ? 'groupid' : 'skey');
	$indexes = [];
	foreach($translated as $locale => $rows) {
		foreach($rows as $row) {
			$indexes[$locale][$row[$key]] = $row;
		}
	}

	foreach($data as &$row) {
		$id = $row[$key];
		if($table == 'common_nav') {
			$row['name'] = json_encode([
				'SC' => $indexes['SC'][$id]['name'] ?? $row['name'],
				'TC' => $indexes['TC'][$id]['name'] ?? $row['name'],
				'EN' => $indexes['EN'][$id]['name'] ?? ($indexes['SC'][$id]['name'] ?? $row['name']),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			unset($row['title']);
		} elseif($table == 'forum_onlinelist') {
			$row['title'] = json_encode([
				'SC' => $indexes['SC'][$id]['title'] ?? $row['title'],
				'TC' => $indexes['TC'][$id]['title'] ?? $row['title'],
				'EN' => $row['url'] ?: ($indexes['SC'][$id]['title'] ?? $row['title']),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} elseif(in_array($id, ['bbname', 'sitename'], true)) {
			$row['svalue'] = json_encode([
				'SC' => $indexes['SC'][$id]['svalue'] ?? $row['svalue'],
				'TC' => $indexes['TC'][$id]['svalue'] ?? $row['svalue'],
				'EN' => $indexes['SC'][$id]['svalue'] ?? $row['svalue'],
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
	}
	unset($row);
	return $data;
}
