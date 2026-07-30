<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class check {

	public function run() {
		global $_G;

		$status = $this->gitStatus();
		if($status === null) {
			return false;
		}

		table_common_cache::t()->insert([
			'cachekey' => 'checktools_filecheck',
			'cachevalue' => serialize(['dateline' => $_G['timestamp']]),
			'dateline' => TIMESTAMP,
		], false, true);

		$modifiedfiles = $deletedfiles = $unknownfiles = $doubt = 0;
		$dirlist = [];
		foreach($status as [$type, $file]) {
			if($type == 'modify') {
				$modifiedfiles++;
			} elseif($type == 'del') {
				$deletedfiles++;
			} else {
				$unknownfiles++;
			}
			$path = DISCUZ_ROOT.$file;
			$dirlist[$type][dirname($file)][basename($file)] = file_exists($path) ? [number_format(filesize($path)).' Bytes', dgmdate(filemtime($path))] : ['', ''];
		}

		$v = [$modifiedfiles, $deletedfiles, $unknownfiles, $doubt, $dirlist];

		table_common_cache::t()->insert([
			'cachekey' => 'checktools_filecheck_result',
			'cachevalue' => serialize($v),
			'dateline' => $_G['timestamp'],
		], false, true);

		return $v;
	}

	private function gitStatus() {
		if(!function_exists('proc_open')) {
			return null;
		}
		$process = @proc_open('git -C '.escapeshellarg(DISCUZ_ROOT).' status --porcelain=v1 -z --untracked-files=all', [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		if(!is_resource($process)) {
			return null;
		}
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		if(proc_close($process) !== 0) {
			return null;
		}

		$status = [];
		$records = explode("\0", $output);
		for($i = 0, $count = count($records); $i < $count; $i++) {
			if($records[$i] === '') {
				continue;
			}
			$code = substr($records[$i], 0, 2);
			$file = substr($records[$i], 3);
			if($code == '??') {
				$type = 'add';
			} elseif(str_contains($code, 'D')) {
				$type = 'del';
			} else {
				$type = 'modify';
			}
			$status[] = [$type, $file];
			if(str_contains($code, 'R') || str_contains($code, 'C')) {
				$i++;
			}
		}
		return $status;
	}

	const EXTENSIONS = [
		'mysqli' => ['mysqli_connect', 'mysqli_query'],
		'json' => ['json_encode', 'json_decode'],
		'mbstring' => ['mb_convert_encoding'],
		'gd' => ['imagecreatetruecolor'],
		'curl' => ['curl_init', 'curl_setopt'],
		'openssl' => ['openssl_random_pseudo_bytes', 'openssl_sign'],
		'xml' => ['xml_parser_create'],
		'filter' => ['filter_var'],
		'ctype' => ['ctype_alnum'],
		'spl' => ['spl_autoload_register'],
		'imap' => ['imap_open', 'imap_search', 'imap_fetchbody'],
	];

	private static function extensionCheck($extension, $testFunctions = []) {
		if($extension && !extension_loaded($extension)) {
			return ['extension', $extension];
		}

		foreach($testFunctions as $function) {
			if(!function_exists($function) && !class_exists($function)) {
				return ['function', $function.'()'];
			}
		}

		return ['', ''];
	}

	public static function extensions() {
		$missing = [];
		foreach(self::EXTENSIONS as $extension => $functions) {
			[$type, $name] = self::extensionCheck($extension, $functions);
			if($type) {
				$missing[$type][] = $name;
			}
		}
		return $missing;
	}
}
