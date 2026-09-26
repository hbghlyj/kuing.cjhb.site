<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

class i18n {

	public const LOCALES = ['SC', 'TC', 'EN'];

	public static function decodeValue($value) {
		if(is_array($value)) {
			$values = $value;
		} elseif(is_string($value) && $value !== '') {
			$values = json_decode($value, true);
			if(!is_array($values)) {
				$values = ['SC' => $value];
			}
		} else {
			$values = [];
		}
		foreach($values as $locale => $text) {
			if(!in_array($locale, self::LOCALES, true) || !is_scalar($text)) {
				unset($values[$locale]);
				continue;
			}
			$values[$locale] = (string)$text;
		}
		return $values;
	}

	public static function localizeValue($value, $locale = '') {
		$values = self::decodeValue($value);
		$locale = strtoupper($locale ?: (string)getglobal('i18n') ?: currentlang());
		$fallbacks = array_unique(array_filter([
			$locale,
			getglobal('setting/i18n_default'),
			'SC',
			'EN',
			'TC',
		]));
		foreach($fallbacks as $fallback) {
			if(isset($values[$fallback]) && $values[$fallback] !== '') {
				return $values[$fallback];
			}
		}
		foreach($values as $text) {
			if($text !== '') {
				return $text;
			}
		}
		return '';
	}

	public static function encodeValue($value, $existing = [], $locale = '') {
		if(is_array($value)) {
			$values = self::decodeValue($value);
		} else {
			$values = self::decodeValue($existing);
			$locale = strtoupper($locale ?: (string)getglobal('i18n') ?: currentlang());
			$values[in_array($locale, self::LOCALES, true) ? $locale : 'SC'] = (string)$value;
		}
		return json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
	}

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
		if($requested === 'EN' || currentlang() === 'EN') {
			return DISCUZ_ROOT.'./source/i18n/SC/'.$file;
		}
		return '';
	}

	/**
	 * Per-style language overrides.
	 *
	 * The template compiler already merges template/<style>/i18n/<locale>/<file>
	 * over the shared pack for {lang ...} in markup; see class_template. The
	 * runtime lang() helper reads $_G['lang'], which is filled from here, so
	 * without this a style-local key resolved to its own name at runtime and the
	 * two paths disagreed. Same overlay, same precedence, so they agree.
	 */
	private static function getStyleLang($file, $i18n = '') {
		global $_G;

		if(empty($_G['style']['tpldir']) || !function_exists('DISCUZ_TEMPLATE')) {
			return [];
		}

		// Style language files guard on IN_DISCUZ and exit when it is missing.
		// The compiler only ever reaches them from inside a bootstrapped
		// request, and this keeps the runtime path from being able to fatal
		// where it previously could not.
		if(!defined('IN_DISCUZ')) {
			return [];
		}

		$tpldir = DISCUZ_TEMPLATE($_G['style']['tpldir']);
		$locale = strtoupper($i18n ?: (string)currentlang());
		$lang = [];

		// English falls back to the style's SC copy, as the compiler does
		if($locale === 'EN' && is_file($fallback = $tpldir.'/i18n/SC/'.$file)) {
			$lang = array_merge($lang, self::loadLangFile($fallback));
		}
		if(is_file($override = $tpldir.'/i18n/'.$locale.'/'.$file)) {
			$lang = array_merge($lang, self::loadLangFile($override));
		}

		return $lang;
	}

	public static function getLang($file, $i18n = '') {
		global $_G;

		static $loaded = [];

		if(empty($i18n) && isset($loaded[$file])) {
			return $loaded[$file];
		}

		$i18n = !empty($i18n) ? $i18n : ($_G['i18n'] ?? '');

		// style-local overrides are merged last so they win, which is the same
		// precedence the template compiler applies
		return $loaded[$file] = array_merge(
			self::resolveLang($file, $i18n),
			self::getStyleLang($file, $i18n)
		);
	}

	private static function resolveLang($file, $i18n = '') {
		global $_G;

		$lang = self::loadLangFile(self::getFallbackPath($file, $i18n));

		if($i18n && !empty($_G['setting']['i18n']) && !empty($_G['setting']['i18n'][$i18n])) {
			if(!empty($_G['setting']['i18n_custom']) && isset($_G['setting']['i18n_custom'][$i18n])) {
				$customSource = $_G['setting']['i18n_custom'][$i18n] ?? 'default';
				loadcache('lang');
				if(!empty($_G['cache']['lang'][$_G['setting']['i18n'][$i18n]][$file])) {
					return $_G['cache']['lang'][$_G['setting']['i18n'][$i18n]][$file];
				} elseif(is_dir($path = $_G['setting']['i18n'][$customSource].'/')) {
					$lang = array_merge($lang, self::loadLangFile($path.$file));
					if(!empty($lang)) {
						return $lang;
					}
				}
			} elseif(is_dir($path = $_G['setting']['i18n'][$i18n].'/')) {
				$lang = array_merge($lang, self::loadLangFile($path.$file));
				if(!empty($lang)) {
					return $lang;
				}
			}
		}

		return array_merge($lang, self::loadLangFile(self::getDefaultPath($file)));
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
