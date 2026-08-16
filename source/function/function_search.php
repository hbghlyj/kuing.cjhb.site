<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
function searchkey($keyword, $field, $returnsrchtxt = 0) {
	$srchtxt = '';
	if($field && $keyword) {
		if(preg_match('(AND|\+|&|\s)', $keyword) && !preg_match('(OR|\|)', $keyword)) {
			$andor = ' AND ';
			$keywordsrch = '1';
			$keyword = preg_replace('/( AND |&| )/is', '+', $keyword);
		} else {
			$andor = ' OR ';
			$keywordsrch = '0';
			$keyword = preg_replace('/( OR |\|)/is', '+', $keyword);
		}
		// Escape the LIKE escape character before wildcard characters so a literal
		// backslash cannot change how the following % or _ is interpreted.
		$keyword = str_replace('*', '%', addcslashes($keyword, "\\%_"));
		$srchtxt = $returnsrchtxt ? $keyword : '';
		foreach(explode('+', $keyword) as $text) {
			$text = trim(daddslashes($text));
			if($text) {
				$keywordsrch .= $andor;
				$keywordsrch .= str_replace('{text}', $text, $field);
			}
		}
		$keyword = " AND ($keywordsrch)";
	}
	return $returnsrchtxt ? [$srchtxt, $keyword] : $keyword;
}

function search_message_safestr($message) {
	$charset = strtolower(CHARSET) == 'utf-8' ? 'UTF-8' : 'ISO-8859-1';
	return htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, $charset, false);
}

