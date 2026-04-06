<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

class i18n {

	private static function getDefaultPath($file) {
		return DISCUZ_ROOT.'./source/i18n/'.currentlang().'/'.$file;
	}

	private static function loadLangFile($path) {
		$lang = [];
		if($path && file_exists($path)) {
			require $path;
		}
		return (array)$lang;
	}

	private static function getFallbackPath($file, $i18n = '') {
		$requested = strtoupper((string)$i18n);
		if($requested === 'EN' || $requested === 'EN_UTF8' || currentlang() === 'EN_UTF8') {
			return DISCUZ_ROOT.'./source/i18n/SC_UTF8/'.$file;
		}
		return '';
	}

	public static function getLang($file, $i18n = '') {
		global $_G;

		static $loaded = [];

		if(empty($i18n) && isset($loaded[$file])) {
			return $loaded[$file];
		}

		$i18n = !empty($i18n) ? $i18n : ($_G['i18n'] ?? '');

		$lang = self::loadLangFile(self::getFallbackPath($file, $i18n));

		if($i18n && !empty($_G['setting']['i18n']) && !empty($_G['setting']['i18n'][$i18n])) {
			if(!empty($_G['setting']['i18n_custom']) && isset($_G['setting']['i18n_custom'][$i18n])) {
				$customSource = $_G['setting']['i18n_custom'][$i18n] ?? 'default';
				loadcache('lang');
				if(!empty($_G['cache']['lang'][$_G['setting']['i18n'][$i18n]][$file])) {
					return $loaded[$file] = $_G['cache']['lang'][$_G['setting']['i18n'][$i18n]][$file];
				} elseif(is_dir($path = $_G['setting']['i18n'][$customSource].'/')) {
					$lang = array_merge($lang, self::loadLangFile($path.$file));
					if(!empty($lang)) {
						return $loaded[$file] = $lang;
					}
				}
			} elseif(is_dir($path = $_G['setting']['i18n'][$i18n].'/')) {
				$lang = array_merge($lang, self::loadLangFile($path.$file));
				if(!empty($lang)) {
					return $loaded[$file] = $lang;
				}
			}
		}

		$lang = array_merge($lang, self::loadLangFile(self::getDefaultPath($file)));
		return $loaded[$file] = $lang;
	}

	public static function cmd($cmd, $langkey = '', $path = '') {
		global $_G;

		$i18n = !empty($_G['setting']['i18n']) ? $_G['setting']['i18n'] : [];

		switch($cmd) {
			case 'get':
				return !empty($langkey) ? $i18n[$langkey] : $i18n;
			case 'set':
				$i18n[$langkey] = $path;
				break;
			case 'rm':
				unset($i18n[$langkey]);
				break;
		}

		table_common_setting::t()->update_batch(['i18n' => $i18n]);
		require_once libfile('function/cache');
		updatecache('setting');

		$_G['setting']['i18n'] = $i18n;
		return '';
	}

}
