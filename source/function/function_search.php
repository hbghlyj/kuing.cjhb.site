<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_search.php 36278 2016-12-09 07:52:35Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
function searchkey($keyword, $field, $returnsrchtxt = 0, $crit = 'and') {
    $keyword = preg_replace('/\s+/', ' ', $keyword);
	$srchtxt = $keyword;
	if($field && $keyword) {
		$keyword=addslashes($keyword);
		if ($crit=='regexp') {
			$text = trim($keyword);
			if($text)$keywordsrch .= str_replace('%{text}%', $text, str_replace('LIKE','REGEXP',$field));
		}else{
			$keyword=addslashes($keyword);
		}
		if($crit=='and'||$crit=='or'){
			foreach(explode(' ', $keyword) as $value) {
				if(isset($text))$keywordsrch .= " $crit ";
				$text = trim($value);
				if($text)$keywordsrch .= str_replace('{text}', $text, $field);
			}
		}
		if ($crit=='exact') {
			$text = trim($keyword);
			if($text)$keywordsrch .= str_replace('{text}', $text, $field);
		}
		$keyword = " AND ($keywordsrch)";
	}
	$srchtxt || $srchtxt = '';
	return $returnsrchtxt ? array($srchtxt, $keyword) : $keyword;
}

function highlight($text, $words, $prepend) {
	$text = str_replace('\"', '"', $text);
	foreach($words AS $key => $replaceword) {
		$text = str_replace($replaceword, '<highlight>'.$replaceword.'</highlight>', $text);
	}
	return "$prepend$text";
}

function bat_highlight($message, $words, $color = '#ff0000') {
	if(!empty($words)) {
		$highlightarray = explode(' ', $words);
		$sppos = strrpos($message, chr(0).chr(0).chr(0));
		if($sppos !== FALSE) {
			$specialextra = substr($message, $sppos + 3);
			$message = substr($message, 0, $sppos);
		}
		bat_highlight_callback_highlight_21($highlightarray, 1);
		$message = preg_replace_callback("/(^|>)([^<]+)(?=<|$)/sU", 'bat_highlight_callback_highlight_21', $message);
		$message = preg_replace("/<highlight>(.*)<\/highlight>/siU", "<strong><font color=\"$color\">\\1</font></strong>", $message);
		if($sppos !== FALSE) {
			$message = $message.chr(0).chr(0).chr(0).$specialextra;
		}
	}
	return $message;
}

function bat_highlight_callback_highlight_21($matches, $action = 0) {
	static $highlightarray = array();

	if($action == 1) {
		$highlightarray = $matches;
	} else {
		return highlight($matches[2], $highlightarray, $matches[1]);
	}
}

<<<<<<< HEAD
=======
?>
>>>>>>> 8cd3387e (migrating from https://gitee.com/kuingggg/DiscuzX/tree/test-0726)
