<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_discuzcode.php 36331 2016-12-28 01:08:45Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include template('forum/discuzcode');

$_G['forum_discuzcode'] = array(
	'pcodecount' => -1,
	'codecount' => 0,
	'codehtml' => array(),
	'passwordlock' => array(),
	'smiliesreplaced' => 0,
	'seoarray' => array(
		0 => '',
		1 => $_SERVER['HTTP_HOST'],
		2 => $_G['setting']['bbname'],
		3 => str_replace('{bbname}', $_G['setting']['bbname'], $_G['setting']['seotitle']),
		4 => $_G['setting']['seokeywords'],
		5 => $_G['setting']['seodescription']
	)
);

if(!isset($_G['cache']['bbcodes']) || !is_array($_G['cache']['bbcodes']) || !is_array($_G['cache']['smilies'])) {
	loadcache(array('bbcodes', 'smilies', 'smileytypes'));
}

function creditshide($creditsrequire, $message, $pid, $authorid) {
	global $_G;
	if($_G['member']['credits'] >= $creditsrequire || $_G['forum']['ismoderator'] || $_G['uid'] && $authorid == $_G['uid']) {
		return tpl_hide_credits($creditsrequire, str_replace('\\"', '"', $message));
	} else {
		return tpl_hide_credits_hidden($creditsrequire);
	}
}

function expirehide($expiration, $creditsrequire, $message, $dateline) {
	$expiration = $expiration ? substr($expiration, 1) : 0;
	if($expiration && $dateline && (TIMESTAMP - $dateline) / 86400 > $expiration) {
		return str_replace('\\"', '"', $message);
	}
	return '[hide'.($creditsrequire ? "=$creditsrequire" : '').']'.str_replace('\\"', '"', $message).'[/hide]';
}

function codedisp($code) {
	global $_G;
	$_G['forum_discuzcode']['pcodecount']++;
	$code = dhtmlspecialchars($code);
	$code = strtr($code, array("\r\n" => "<li>", "\n" => "<li>")); //kk replace both \r\n and \n
	$_G['forum_discuzcode']['codehtml'][$_G['forum_discuzcode']['pcodecount']] = tpl_codedisp($code);
	$_G['forum_discuzcode']['codecount']++;
	return "[\tDISCUZ_CODE_".$_G['forum_discuzcode']['pcodecount']."\t]";
}

function karmaimg($rate, $ratetimes) {
	$karmaimg = '';
	if($rate && $ratetimes) {
		$image = $rate > 0 ? 'agree.gif' : 'disagree.gif';
		for($i = 0; $i < ceil(abs($rate) / $ratetimes); $i++) {
			$karmaimg .= '<img src="'.$_G['style']['imgdir'].'/'.$image.'" border="0" alt="" />';
		}
	}
	return $karmaimg;
}

function discuzcode($message, $smileyoff = false, $bbcodeoff = false, $htmlon = 0, $allowsmilies = 1, $allowbbcode = 1, $allowimgcode = 1, $allowhtml = 0, $jammer = 0, $parsetype = '0', $authorid = '0', $allowmediacode = '0', $pid = 0, $lazyload = 0, $pdateline = 0, $first = 0) {
	global $_G;

	static $authorreplyexist;

	$message = preg_replace('/\[\tDISCUZ_CODE_\d+\t\]/', '', $message);
	if($parsetype != 1 && !$bbcodeoff && $allowbbcode && (strpos($message, '[/code]') || strpos($message, '[/CODE]')) !== FALSE) {
		$message = preg_replace_callback("/\[code\](.+?)\[\/code\]/is", 'discuzcode_callback_codedisp_1', $message);
	}
	// parse [tikz] and [asy]
	$code_arr = array();
	if($parsetype != 1 && !$bbcodeoff && $allowbbcode && strpos($message, '[/tikz]') !== FALSE) {
		$message = preg_replace_callback("/\[tikz\](.+?)\[\/tikz\]/s", function ($matches) use (&$code_arr) {
			$code = $matches[1];
			$code_arr[] = $code;
			// Remove comments: only unescaped '%' and the rest of the line
			$code = preg_replace('/(?<!\\\\)%.*$/m', '', $code);
			// Remove leading and trailing spaces or tabs from each line while preserving empty lines
			$code = preg_replace('/^[ \t]+|[ \t]+$/m', '', $code);
			$strb = rtrim(strtr(base64_encode(gzdeflate($code)), '+/', '-_'), '=');
			return '[img]//i.upmath.me/svgb/'.$strb.'[/img]';
		}, $message);
	}
	if($parsetype != 1 && !$bbcodeoff && $allowbbcode && strpos($message, '[/asy]') !== FALSE) {
		$message = preg_replace_callback("/\[asy\](.+?)\[\/asy\]/s", function ($matches) use (&$code_arr) {
			$code = $matches[1];
			$code_arr[] = $code;
			// Remove comments: double slashes and the rest of the line
			$code = preg_replace('/(?<!\\\\)\/\/.*$/m', '', $code);
			// Remove leading and trailing spaces or tabs from each line while preserving empty lines
			$code = preg_replace('/^[ \t]+|[ \t]+$/m', '', $code);
			$format = (str_contains($code, 'import graph3')||str_contains($code, 'import three'))? 'png' : 'svg';
			return '[img]asy/?code='.rawurlencode($code).'&format='.$format.'[/img]';
		}, $message);
	}

	$msglower = strtolower($message);

	$htmlon = $htmlon && $allowhtml ? 1 : 0;

	if(!$htmlon) {
		$message = dhtmlspecialchars($message);
	}

	if($_G['setting']['plugins']['func'][HOOKTYPE]['discuzcode']) {
		$_G['discuzcodemessage'] = & $message;
		$param = func_get_args();
		hookscript('discuzcode', 'global', 'funcs', array('param' => $param, 'caller' => 'discuzcode'), 'discuzcode');
	}

	if(!$smileyoff && $allowsmilies) {
		$message = parsesmiles($message);
	}

	if($_G['setting']['allowattachurl'] && strpos($msglower, 'attach://') !== FALSE) {
		$message = preg_replace_callback("/attach:\/\/(\d+)\.?(\w*)/i", 'discuzcode_callback_parseattachurl_12', $message);
	}

	if($allowbbcode) {
		if(strpos($msglower, 'ed2k://') !== FALSE) {
			$message = preg_replace_callback("/ed2k:\/\/([^\/\s'\"]+)\//", 'discuzcode_callback_parseed2k_1', $message);
		}
	}

	if(!$bbcodeoff && $allowbbcode) {
		if(strpos($msglower, '[/url]') !== FALSE) {
			$message = preg_replace_callback("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.|mailto:|tel:|magnet:)?([^\r\n\[\"]+?))?\](.*?)\[\/url\]/is", 'discuzcode_callback_parseurl_152', $message);
		}
		if(strpos($msglower, '[/email]') !== FALSE) {
			$message = preg_replace_callback("/\[email(=([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-_]+[.][A-Za-z0-9\-_.]+))?\](.+?)\[\/email\]/is", 'discuzcode_callback_parseemail_14', $message);
		}

		$nest = 0;
		while(strpos($msglower, '[table') !== FALSE && strpos($msglower, '[/table]') !== FALSE){
			$message = preg_replace_callback("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/is", 'discuzcode_callback_parsetable_123', $message);
			if(++$nest > 4) break;
		}

		$message = preg_replace(array(
			"/\[color=([#\w]+?)\]/i",
			"/\[color=((rgb|rgba)\([\d\s,]+?\))\]/i",
			"/\[backcolor=([#\w]+?)\]/i",
			"/\[backcolor=((rgb|rgba)\([\d\s,]+?\))\]/i",
			"/\[size=(\d{1,2}?)\]/i",
			"/\[size=(\d{1,2}(\.\d{1,5})?(px|pt)+?)\]/i",
			"/\[font=([^\[\<]+?)\]/i",
			"/\[align=(left|center|right)\]/i",
			"/\[p=(\d{1,2}|null), (\d{1,2}|null), (left|center|right)\]/i",
			"/\[float=left\]/i",
			"/\[float=right\]/i"
			), array(
			"<font color=\"\\1\">",
			"<font style=\"color:\\1\">",
			"<font style=\"background-color:\\1\">",
			"<font style=\"background-color:\\1\">",
			"<font size=\"\\1\">",
			"<font style=\"font-size:\\1\">",
			"<font face=\"\\1\">",
			"<div align=\"\\1\">",
			"<p style=\"line-height:\\1px;text-indent:\\2em;text-align:\\3\">",
			"<span style=\"float:left;margin-right:5px\">",
			"<span style=\"float:right;margin-left:5px\">"
			), $message);		
			
		$message = str_replace(array(
			'[/color]', '[/backcolor]', '[/size]', '[/font]', '[/align]', '[hr]', '[/p]', '[/float]', '[indent]', '[/indent]'
			), array(
			'</font>', '</font>', '</font>', '</font>', '</div>', '<hr class="l" />', '</p>', '</span>', '<blockquote>', '</blockquote>'
			), $message);

		// if the close tag is found, replace the closest open tag before it
		// this is to prevent the open tag from being replaced but the close tag not, which would result in invalid html
		foreach (array('[/b]', '[/s]', '[/i]', '[/u]') as $i => $close_tag) {
			$open_tag = array('[b]', '[s]', '[i]', '[u]')[$i];
			$parts = explode($close_tag, $message);
			$message = '';
			$count = count($parts);
			for ($j = 0; $j < $count - 1; $j++) {
				$part = $parts[$j];
				// for each part, using strrpos() to find the last open tag and replace it with the open tag replacement
				$pos = strrpos($part, $open_tag);
				if ($pos === false) {
					// no open tag found, just append the part and the close tag as in the original
					$message .= $part . $close_tag;
				} else {
					// open tag found, replace it with the open tag replacement
					$open_tag_replace = array('<strong>', '<strike>', '<i>', '<u>')[$i];
					$close_tag_replace = array('</strong>', '</strike>', '</i>', '</u>')[$i];
					$message .= substr_replace($part, $open_tag_replace, $pos, strlen($open_tag)) . $close_tag_replace;
				}
			}
			// append the last part
			$message .= $parts[$count-1];
		}

		// list
		$open_tags = array(
			'[list]' => '<ul>',
			'[list=1]' => '<ul type="1" class="litype_1">',
			'[list=a]' => '<ul type="a" class="litype_2">',
			'[list=A]' => '<ul type="A" class="litype_3">'
		);
		$close_tag = '</ul>';
		$stack = array();
		$parts = preg_split('/(\[list(?:=[1aA])?\]|\[\/list\]|\[\*\])/', $message, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$message = '';
		foreach ($parts as $part) {
			if (isset($open_tags[$part])) {
				array_push($stack, $open_tags[$part]);
				$message .= $open_tags[$part];
			} elseif ($part === '[/list]') {
				if (!empty($stack)) {
					array_pop($stack);
					$message = rtrim($message, "\r\n");
					$message .= $close_tag;
				}
			} elseif ($part === '[*]') {
				if (!empty($stack)) {
					$message = rtrim($message, "\r\n");
					$message .= '<li>';
				} else {
					$message .= '[*]';
				}
			} else {
				$message .= $part;
			}
		}
		// close all unclosed tags
		while (!empty($stack)) {
			array_pop($stack);
			$message .= $close_tag;
		}

		if($pid && !defined('IN_MOBILE')) {
			$message = preg_replace_callback(
				"/\s?\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]\s?/is",
				function ($matches) use ($pid) {
					return parsepostbg($matches[1], intval($pid));
				},
				$message
			);
		} else {
			$message = preg_replace("/\s?\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]\s?/is", "", $message);
		}

		if($parsetype != 1) {
			if(strpos($msglower, '[/quote]') !== FALSE) {
				$message = preg_replace("/\[quote\](.+?)\[\/quote\]/is", tpl_quote(), $message);
			}
			if(strpos($msglower, '[/free]') !== FALSE) {
				$message = preg_replace("/\[free\](.+?)\[\/free\]/is", tpl_free(), $message);
			}
		}
		if(!defined('IN_MOBILE') || !in_array(constant('IN_MOBILE'), array('1', '3', '4'))) {
			if(strpos($msglower, '[/media]') !== FALSE) {
				$message = preg_replace_callback("/\[media=([\w%,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/is", $allowmediacode ? 'discuzcode_callback_parsemedia_12' : 'discuzcode_callback_bbcodeurl_2', $message);
			}
			if(strpos($msglower, '[/audio]') !== FALSE) {
				$message = preg_replace_callback("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/is", $allowmediacode ? 'discuzcode_callback_parseaudio_2' : 'discuzcode_callback_bbcodeurl_2', $message);
			}
		} else {
			if(strpos($msglower, '[/media]') !== FALSE) {
				$message = preg_replace("/\[media=([\w%,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/is", "[media]\\2[/media]", $message);
			}
			if(strpos($msglower, '[/audio]') !== FALSE) {
				$message = preg_replace("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/is", "[media]\\2[/media]", $message);
			}
		}

		if($parsetype != 1 && $allowbbcode < 0 && isset($_G['cache']['bbcodes'][-$allowbbcode])) {
			$message = preg_replace($_G['cache']['bbcodes'][-$allowbbcode]['searcharray'], $_G['cache']['bbcodes'][-$allowbbcode]['replacearray'], $message);
		}
		if($parsetype != 1 && strpos($msglower, '[/hide]') !== FALSE && $pid) {
			if($_G['setting']['hideexpiration'] && $pdateline && (TIMESTAMP - $pdateline) / 86400 > $_G['setting']['hideexpiration']) {
				$message = preg_replace("/\[hide[=]?(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/is", "\\3", $message);
				$msglower = strtolower($message);
			}
			if(strpos($msglower, '[hide=d') !== FALSE) {
				$message = preg_replace_callback(
					"/\[hide=(d\d+)?[,]?(\d+)?\]\s*(.*?)\s*\[\/hide\]/is",
					function ($matches) use ($pdateline) {
						return expirehide($matches[1], $matches[2], $matches[3], intval($pdateline));
					},
					$message
				);
				$msglower = strtolower($message);
			}
			if(strpos($msglower, '[hide]') !== FALSE) {
				if($authorreplyexist === null) {
					if(!$_G['forum']['ismoderator']) {
						if($_G['uid']) {
							$_post = C::t('forum_post')->fetch_post('tid:'.$_G['tid'], $pid);
							$authorreplyexist = $_post['tid'] == $_G['tid'] ? C::t('forum_post')->fetch_pid_by_tid_authorid($_G['tid'], $_G['uid']) : FALSE;
						}
					} else {
						$authorreplyexist = TRUE;
					}
				}
				if($authorreplyexist) {
					$message = preg_replace("/\[hide\]\s*(.*?)\s*\[\/hide\]/is", tpl_hide_reply(), $message);
				} else {
					$message = preg_replace("/\[hide\](.*?)\[\/hide\]/is", tpl_hide_reply_hidden(), $message);
					$message = '<script type="text/javascript">replyreload += \',\' + '.$pid.';</script>'.$message;
				}
			}
			if(strpos($msglower, '[hide=') !== FALSE) {
				$message = preg_replace_callback(
					"/\[hide=(\d+)\]\s*(.*?)\s*\[\/hide\]/is",
					function ($matches) use ($pid, $authorid) {
						return creditshide($matches[1], $matches[2], intval($pid), intval($authorid));
					},
					$message
				);
			}
		}
	}

	if(!$bbcodeoff) {
		$attrsrc = !IS_ROBOT && $lazyload ? 'file' : 'src';
		if(strpos($msglower, '[/img]') !== FALSE) {
			$message = preg_replace_callback(
				"/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is",
				function ($matches) use ($allowimgcode, $lazyload, $pid, $allowbbcode, &$code_arr) {
					if (intval($allowimgcode)) {
						return parseimg(0, 0, $matches[1], intval($lazyload), intval($pid), (intval($lazyload) ? 'lazyloadthumb="1"' : 'onload="this.parentNode.classList.add(\'jiazed\');this.setAttribute(\'width\',this.width);this.parentNode.style.display=\'inline-block\';"').(str_starts_with($matches[1], '//i.upmath.me/svgb/') || str_starts_with($matches[1], 'asy/?code=') ? ' onclick="show_tikz_window('.htmlspecialchars(json_encode(array_shift($code_arr))).')"' : ''));
					}
					return (intval($allowbbcode) ? (!defined('IN_MOBILE') ? bbcodeurl($matches[1], '<a href="{url}" target="_blank">{url}</a>') : bbcodeurl($matches[1], '')) : bbcodeurl($matches[1], '{url}'));
				},
				$message
			);
			$message = preg_replace_callback(
				"/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/is",
				function ($matches) use ($allowimgcode, $lazyload, $pid, $allowbbcode) {
					if (intval($allowimgcode))  {
						return parseimg($matches[1], $matches[2], $matches[3], intval($lazyload), intval($pid), (intval($lazyload) ? 'lazyloadthumb="1"' : 'onload="this.parentNode.classList.add(\'jiazed\');this.setAttribute(\'width\',this.width);this.parentNode.style.display=\'inline-block\';"'));
					}
					return (intval($allowbbcode) ? (!defined('IN_MOBILE') ? bbcodeurl($matches[3], '<a href="{url}" target="_blank">{url}</a>') : bbcodeurl($matches[3], '')) : bbcodeurl($matches[3], '{url}'));
				},
				$message
			);
		}
	}

	for($i = 0; $i <= $_G['forum_discuzcode']['pcodecount']; $i++) {
		$message = str_replace("[\tDISCUZ_CODE_$i\t]", $_G['forum_discuzcode']['codehtml'][$i], $message);
	}

	unset($msglower);

	if($jammer) {
		$message = preg_replace_callback("/\r\n|\n|\r/", 'discuzcode_callback_jammer', $message);
	}
	if($first) {
		if(helper_access::check_module('group')) {
			$message = preg_replace("/\[groupid=(\d+)\](.*)\[\/groupid\]/i", lang('forum/template', 'fromgroup').': <a href="forum.php?mod=forumdisplay&fid=\\1" target="_blank">\\2</a>', $message);
		} else {
			$message = preg_replace("/(\[groupid=\d+\].*\[\/groupid\])/i", '', $message);
		}

	}
	if($htmlon) {
		return $message;
	}

	$segments = preg_split('/(<style>.*?<\/style>)/is', $message, -1, PREG_SPLIT_DELIM_CAPTURE);
	$result = '';
	foreach($segments as $seg) {
		// leave [style]…[/style] blocks untouched
		if(preg_match('/^<style>.*<\/style>$/is', $seg)) {
			$result .= $seg;
		} else {
			$seg = preg_replace(
				['/(<div\b[^>]*>)\r?\n/','/(<\/div>)\r?\n/'],
				'$1',
				$seg
			);
			$seg = str_replace(
				["\t", '   ', '  '],
				['&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'],
				$seg
			);
			$result .= nl2br($seg);
		}
	}
	return $result;
}

function discuzcode_callback_codedisp_1($matches) {
	return codedisp($matches[1]);
}

function discuzcode_callback_parseattachurl_12($matches) {
	return parseattachurl($matches[1], $matches[2], 1);
}

function discuzcode_callback_parseed2k_1($matches) {
	return parseed2k($matches[1]);
}

function discuzcode_callback_parseurl_152($matches) {
	return parseurl($matches[1], $matches[5], $matches[2]);
}

function discuzcode_callback_parseemail_14($matches) {
	return parseemail($matches[1], $matches[4]);
}

function discuzcode_callback_parsetable_123($matches) {
	return parsetable($matches[1], $matches[2], $matches[3]);
}

function discuzcode_callback_parsemedia_12($matches) {
	return parsemedia($matches[1], $matches[2]);
}

function discuzcode_callback_bbcodeurl_2($matches) {
	return bbcodeurl($matches[2], '<a href="{url}" target="_blank">{url}</a>');
}

function discuzcode_callback_parseaudio_2($matches) {
	return parseaudio($matches[2], 400);
}


function discuzcode_callback_jammer($matches) {
	return jammer();
}

function parseurl($url, $text, $scheme) {
	global $_G;
	$link_rel_attribute = '';
	if(!$url) {
		// Either an external link, example [url]http://www.qq.com[/url]
		// Or an internal link, example [url]forum.php?mod=viewthread&tid=1[/url]
		// 1. Decode percent-encoded characters
		$text = urldecode($url = $text);
		// Determine if the link is external based on $text (before stripping its prefix for display)
		// $text at this point holds the full URL like "http://example.com" or "www.example.com"
		if (preg_match("/^https?:\/\//i", $text)) {
			// It's an external link, set the rel attribute.
			// This variable $link_rel_attribute should be used in the return statement later.
			$link_rel_attribute = '" rel="external nofollow';
		}elseif(str_starts_with($url, 'www.')) {
			$url = '//' . $url;
			$link_rel_attribute = '" rel="external nofollow';
		}
		// 2. Hide the prefix http:// or www. from $text for display
		$text = preg_replace("/^https?:\/\/(www\.)?|^www\./i", '', $text);
		// 3. Truncate if too long (multibyte safe)
		if(mb_strlen($text) > 65) {
			$text = mb_substr($text, 0, 45) . ' &hellip; ' . mb_substr($text, -20);
		}
		return '<a href="' . $url . $link_rel_attribute . '" target="_blank">' . $text . '</a>';
	} else {
		$url = substr($url, 1);// remove the prefix =
		if($url[0] == '#') {
			if(!$text) {// destination anchor, example [url=#sec1][/url]
				return '<a name="'.substr($url, 1).'"></a>';
			}
			return '<a href="'.$url.'">'.$text.'</a>';// example [url=#sec1]go to sec1[/url]
		} else {
			// Either an external link, example [url=http://www.qq.com]go to qq[/url]
			// Or an internal link, example [url=forum.php?mod=viewthread&tid=1]go to thread 1[/url]
			if (preg_match("/^https?:\/\//i", $url)) {
				$link_rel_attribute = '" rel="external nofollow';
			}elseif(str_starts_with($url, 'www.')) {
				$url = '//' . $url;// If starts with www., must be an external link, example [url=www.qq.com]qq[/url]
				$link_rel_attribute = '" rel="external nofollow';
			}
			return '<a href="' . $url . $link_rel_attribute . '" target="_blank">'.$text.'</a>';
		}
	}
}


function parseed2k($url) {
	global $_G;
	list(,$type, $name, $size,) = explode('|', $url);
	$url = 'ed2k://'.$url.'/';
	$name = addslashes($name);
	if($type == 'file') {
		$ed2kid = 'ed2k_'.random(3);
		return '<a id="'.$ed2kid.'" href="'.$url.'" target="_blank">'.dhtmlspecialchars(urldecode($name)).' ('.sizecount($size).')</a><script language="javascript">$(\''.$ed2kid.'\').innerHTML=htmlspecialchars(unescape(decodeURIComponent(\''.$name.'\')))+\' ('.sizecount($size).')\';</script>';
	} else {
		return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
	}
}

function parseattachurl($aid, $ext, $ignoretid = 0) {
	global $_G;
	require_once libfile('function/attachment');
	$_G['forum_skipaidlist'][] = $aid;
	if(!empty($ext)) {
		$attach = C::t('forum_attachment_n')->fetch('aid:'.$aid, $aid);
		// 如果不是音视频类附件则不允许生成无条件限制的地址, 此处不支持附件收费以及阅读权限判定
		if(!in_array(attachtype(fileext($attach['filename'])."\t", 'id'), array(9, 10))) {
			$ext = 0;
		}
	}
	return $_G['siteurl'].'forum.php?mod=attachment&aid='.aidencode($aid, $ext, $ignoretid ? '' : $_G['tid']).($ext ? '&request=yes&_f=.'.$ext : '');
}

function parseemail($email, $text) {
	$text = str_replace('\"', '"', $text);
	if(!$email && preg_match("/\s*([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-_]+[.][A-Za-z0-9\-_.]+)\s*/i", $text, $matches)) {
		$email = trim($matches[0]);
		return '<a href="mailto:'.$email.'">'.$email.'</a>';
	} else {
		return '<a href="mailto:'.substr($email, 1).'">'.$text.'</a>';
	}
}

function parsetable($width, $bgcolor, $message) {
	if(strpos($message, '[/tr]') === FALSE && strpos($message, '[/td]') === FALSE && strpos($message, '[/th]') === FALSE) {
		$rows = explode("\n", $message);
		$s = !defined('IN_MOBILE') ? '<table cellspacing="0" class="t_table" '.
			($width == '' ? NULL : 'width="'.$width.'"').
			($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>') : '<table>';
		foreach($rows as $row) {
			$s .= '<tr><td>'.str_replace(array('\|', '|', '\n'), array('&#124;', '</td><td>', "\n"), $row).'</td></tr>';
		}
		$s .= '</table>';
		return $s;
	} else {
		if(!preg_match("/^\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[(td|th)([=\d,%]+)?\]/", $message) && !preg_match("/^<tr[^>]*?>\s*<(td|th)[^>]*?>/", $message)) {
			return str_replace('\\"', '"', preg_replace("/\[tr(?:=([\(\)\s%,#\w]+))?\]|\[(td|th)([=\d,%]+)?\]|\[\/(td|th)\]|\[\/tr\]/", '', $message));
		}
		$message = preg_replace_callback("/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[(td|th)(?:=(\d{1,4}%?))?\]/i", 'parsetable_callback_open_simple', $message);
		$message = preg_replace_callback("/\[\/(td|th)\]\s*\[(td|th)(?:=(\d{1,4}%?))?\]/i", 'parsetable_callback_replace_simple', $message);
		$message = preg_replace_callback("/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[(td|th)(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/i", 'parsetable_callback_open_complex', $message);
		$message = preg_replace_callback("/\[\/(td|th)\]\s*\[(td|th)(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/i", 'parsetable_callback_replace_complex', $message);
		$message = preg_replace("/\[\/(td|th)\]\s*\[\/tr\]\s*/i", '</$1></tr>', $message);
		return (!defined('IN_MOBILE') ? '<table cellspacing="0" class="t_table" '.
			($width == '' ? NULL : 'width="'.$width.'"').
			($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>') : '<table>').
			str_replace('\\"', '"', $message).'</table>';
	}
}

function parsetable_callback_open_simple($matches) {
    return _parsetable_cell_generator(
        isset($matches[1]) ? $matches[1] : null,
        0,
        0,
        isset($matches[3]) ? $matches[3] : null,
        strtolower($matches[2]),
        'open'
    );
}

function parsetable_callback_replace_simple($matches) {
    return _parsetable_cell_generator(
        strtolower($matches[1]),
        0,
        0,
        isset($matches[3]) ? $matches[3] : null,
        strtolower($matches[2]),
        'replace'
    );
}

function parsetable_callback_open_complex($matches) {
    return _parsetable_cell_generator(
        isset($matches[1]) ? $matches[1] : null,
        isset($matches[3]) && $matches[3] !== '' ? $matches[3] : 0,
        isset($matches[4]) && $matches[4] !== '' ? $matches[4] : 0,
        isset($matches[5]) ? $matches[5] : null,
        strtolower($matches[2]),
        'open'
    );
}

function parsetable_callback_replace_complex($matches) {
    return _parsetable_cell_generator(
        strtolower($matches[1]),
        isset($matches[3]) && $matches[3] !== '' ? $matches[3] : 0,
        isset($matches[4]) && $matches[4] !== '' ? $matches[4] : 0,
        isset($matches[5]) ? $matches[5] : null,
        strtolower($matches[2]),
        'replace'
    );
}

function _parsetable_cell_generator($param1, $colspan, $rowspan, $width, $current_cell_tag, $mode) {
    $output = '';
    if ($mode == 'open') {
        $tr_style = $param1;
        $output .= '<tr'.($tr_style && !defined('IN_MOBILE') ? ' style="background-color:'.$tr_style.'"' : '').'>';
    } elseif ($mode == 'replace') {
        $prev_cell_tag = $param1;
        if ($prev_cell_tag !== 'td' && $prev_cell_tag !== 'th') {
            $prev_cell_tag = 'td'; // Default to closing td if invalid
        }
        $output .= '</'.$prev_cell_tag.'>';
    }

    if ($current_cell_tag !== 'td' && $current_cell_tag !== 'th') {
        $current_cell_tag = 'td'; // Default to td if invalid
    }

    $output .= '<'.$current_cell_tag;
    if ($colspan > 1) {
        $output .= ' colspan="'.$colspan.'"';
    }
    if ($rowspan > 1) {
        $output .= ' rowspan="'.$rowspan.'"';
    }
    if ($width && $width !== '' && !defined('IN_MOBILE')) {
        $output .= ' width="'.$width.'"';
    }
    $output .= '>';
    return $output;
}

function bbcodeurl($url, $tags) {
	if(!preg_match("/<.+?>/s", $url)) {
		if(!in_array(strtolower(substr($url, 0, 6)), array('http://', 'https://', 'ftp://', 'rtsp:/', 'mms://')) && str_starts_with($url, 'www.')) {
			$url = '//'.$url;
		}
		return str_replace(array('submit', 'member.php?mod=logging'), array('', ''), str_replace('{url}', addslashes($url), $tags));
	} else {
		return '&nbsp;'.$url;
	}
}

function jammer() {
	$randomstr = '';
	for($i = 0; $i < mt_rand(5, 15); $i++) {
		$randomstr .= chr(mt_rand(32, 59)).' '.chr(mt_rand(63, 126));
	}
	return mt_rand(0, 1) ? '<font class="jammer">'.$randomstr.'</font>'."\r\n" :
		"\r\n".'<span style="display:none">'.$randomstr.'</span>';
}

function highlightword($text, $words, $prepend) {
	$text = str_replace('\"', '"', $text);
	foreach($words AS $key => $replaceword) {
		$text = str_replace($replaceword, '<highlight>'.$replaceword.'</highlight>', $text);
	}
	return "$prepend$text";
}

function parseiframe($url, $width = 0, $height = 0) {
	global $_G;
        $lowerurl = strtolower($url);
        $iframe = $imgurl = '';
	if(empty($_G['setting']['parseflv']) || !is_array($_G['setting']['parseflv'])) {
		return FALSE;
	}

	foreach($_G['setting']['parseflv'] as $script => $checkurl) {
		$check = FALSE;
		foreach($checkurl as $row) {
			if(strpos($lowerurl, $row) !== FALSE) {
			    $check = TRUE;
			    break;
			}
		}
                if($check) {
                        @include_once libfile('media/'.$script, 'function');
                        if(function_exists('media_'.$script)) {
                            list($iframe, $url, $imgurl) = call_user_func('media_'.$script, $url, $width, $height);
                        }
                        break;
                }
	}
        if($iframe) {
                if(!$width && !$height) {
                        return array('iframe' => $iframe, 'imgurl' => $imgurl);
                } else {
                        $width = addslashes($width);
                        $height = addslashes($height);
                        $iframe = addslashes($iframe);
                        $randomid = 'flv_'.random(3);
                        $player_iframe = "\"<iframe src='$iframe' border='0' scrolling='no' framespacing='0' allowfullscreen='true' style='max-width: 100%' width='$width' height='$height' frameborder='no'></iframe>\"";
                        return '<ignore_js_op><span id="'.$randomid.'"></span><script type="text/javascript" reload="1">document.getElementById(\''.$randomid.'\').innerHTML='.$player_iframe.';</script></ignore_js_op>';
                }
        } else {
                return FALSE;
        }
}

function parseimg($width, $height, $src, $lazyload, $pid, $extra = '') {
	global $_G, $aimgs;
	static $styleoutput = null;
	if($_G['setting']['domainwhitelist_affectimg']) {
		$tmp = parse_url($src);
		if(!empty($tmp['host']) && !iswhitelist($tmp['host'])) {
			return $src;
		}
	}
	if(strstr($src, 'file:') || substr($src, 1, 1) == ':') {
		return $src;
	}
	if($width > $_G['setting']['imagemaxwidth']) {
		$height = intval($_G['setting']['imagemaxwidth'] * $height / $width);
		$width = $_G['setting']['imagemaxwidth'];
		if(defined('IN_MOBILE')) {
			$extra = '';
		} else {
			$extra = 'onmouseover="img_onmouseoverfunc(this)" onclick="zoom(this)" style="cursor:pointer"';
		}
	}
	$attrsrc = !IS_ROBOT && $lazyload ? 'file' : 'src';
	$rimg_id = random(5);
	$aimgs[$pid][] = $rimg_id;
	$guestviewthumb = !empty($_G['setting']['guestviewthumb']['flag']) && empty($_G['uid']);
	$img = '';
	if($guestviewthumb) {
		if(!isset($styleoutput)) {
			$img .= guestviewthumbstyle();
			$styleoutput = true;
		}
		$img .= '<div class="guestviewthumb"><img id="aimg_'.$rimg_id.'" class="guestviewthumb_cur" onclick="showWindow(\'login\', \'{loginurl}\'+\'&referer=\'+encodeURIComponent(location))" '.$attrsrc.'="{url}" border="0" alt="" />
				<br><a href="{loginurl}" onclick="showWindow(\'login\', this.href+\'&referer=\'+encodeURIComponent(location));">'.lang('forum/template', 'guestviewthumb').'</a></div>';

	} else {
		if(defined('IN_MOBILE')) {
			$img = '<img'.($width > 0 ? ' width="'.$width.'"' : '').($height > 0 ? ' height="'.$height.'"' : '').' src="{url}" border="0" alt="" />';
		} else {
            $img = '<div class="tupian"><div class="jiaz"></div><div class="tuozt" onmousedown="tuozhuai2(event,this.parentNode);return false;"></div><div class="guiw" onclick="guiwei(this.parentNode);return false;"></div><img id="aimg_'.$rimg_id.'" class="zoom mw100"'.($width > 0 ? ' width="'.$width.'"' : '').($height > 0 ? ' height="'.$height.'"' : '').' '.$attrsrc.'="{url}" '.($extra ? $extra.' ' : '')."/></div>\n";
		}
	}
	$code = bbcodeurl($src, $img);
	if($guestviewthumb) {
		$code = str_replace('{loginurl}', 'member.php?mod=logging&action=login', $code);
	}
	return $code;
}

function parsesmiles(&$message) {
	global $_G;
	static $enablesmiles;
	if($enablesmiles === null) {
		$enablesmiles = false;
		if(!empty($_G['cache']['smilies']) && is_array($_G['cache']['smilies'])) {
			foreach($_G['cache']['smilies']['replacearray'] AS $key => $smiley) {
				$_G['cache']['smilies']['replacearray'][$key] = '<img src="'.STATICURL.'image/smiley/'.$_G['cache']['smileytypes'][$_G['cache']['smilies']['typearray'][$key]]['directory'].'/'.$smiley.'" smilieid="'.$key.'" border="0" alt="" />';
			}
			$enablesmiles = true;
		}
	}
	$enablesmiles && $message = preg_replace($_G['cache']['smilies']['searcharray'], $_G['cache']['smilies']['replacearray'], $message, $_G['setting']['maxsmilies']);
	return $message;
}

function parsepostbg($bgimg, $pid) {
	global $_G;
	static $postbg;
	if($postbg[$pid]) {
		return '';
	}
	loadcache('postimg');
	foreach($_G['cache']['postimg']['postbg'] as $postbg) {
		if($postbg['url'] != $bgimg) {
			continue;
		}
		$bgimg = dhtmlspecialchars(basename($bgimg), ENT_QUOTES);
		$postbg[$pid] = true;
		$_G['forum_posthtml']['header'][$pid] .= '<style type="text/css">#pid'.$pid.'{background-image:url("'.STATICURL.'image/postbg/'.$bgimg.'");}</style>';
		break;
	}
	return '';
}

function guestviewthumbstyle() {
	static $styleoutput = null;
	$return = '';
	if ($styleoutput === null) {
		global $_G;
		$return = '<style>.guestviewthumb {margin:10px auto; text-align:center;}.guestviewthumb a {font-size:12px;}.guestviewthumb_cur {cursor:url("'.IMGDIR.'/scf.gif"), default; max-width:'.$_G['setting']['guestviewthumb']['width'].'px;}.ie6 .guestviewthumb_cur { width:'.$_G['setting']['guestviewthumb']['width'].'px !important;}</style>';
		$styleoutput = true;
	}
	return $return;
}
?>