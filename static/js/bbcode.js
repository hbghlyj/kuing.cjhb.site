/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

var re, DISCUZCODE = [];
DISCUZCODE['num'] = '-1';
DISCUZCODE['html'] = [];
EXTRAFUNC['bbcode2html'] = [];
EXTRAFUNC['html2bbcode'] = [];

function addslashes(str) {
	return preg_replace(['\\\\', '\\\'', '\\\/', '\\\(', '\\\)', '\\\[', '\\\]', '\\\{', '\\\}', '\\\^', '\\\$', '\\\?', '\\\.', '\\\*', '\\\+', '\\\|'], ['\\\\', '\\\'', '\\/', '\\(', '\\)', '\\[', '\\]', '\\{', '\\}', '\\^', '\\$', '\\?', '\\.', '\\*', '\\+', '\\|'], str);
}

function atag(aoptions, text) {
	if(trim(text) == '') {
		return '';
	}
	var pend = parsestyle(aoptions, '', '');
	href = getoptionvalue('href', aoptions);

	if(href.substr(0, 11) == 'javascript:') {
		return trim(recursion('a', text, 'atag'));
	}

	var innerText = trim(recursion('a', text, 'atag'));
	var decodedHref = href;
	try {
		decodedHref = decodeURIComponent(href);
	} catch(e) {}

	var cleanHref = decodedHref.replace(/^https?:\/\/(www\.)?|^www\./i, '').replace(/\/$/, '');
	var cleanText = innerText.replace(/^https?:\/\/(www\.)?|^www\./i, '').replace(/\/$/, '');

	if(href === innerText || decodedHref === innerText || cleanHref === cleanText) {
		return pend['prepend'] + '[url]' + href + '[/url]' + pend['append'];
	}

	return pend['prepend'] + '[url=' + href + ']' + innerText + '[/url]' + pend['append'];
}

function bbcode2html(str) {
	if(str == '') {
		return '';
	}

	if(typeof(parsetype) == 'undefined') {
		parsetype = 0;
	}

	if(!fetchCheckbox('bbcodeoff') && allowbbcode && parsetype != 1) {
		str = str.replace(/\[code\]([\s\S]+?)\[\/code\]/ig, function($1, $2) {return parsecode($2);});
	}

	if(fetchCheckbox('allowimgurl')) {
		str = str.replace(/([^>=\]"'\/]|^)((((https?|ftp):\/\/)|www\.)([\w\-]+\.)*[\w\-\u4e00-\u9fa5]+\.([\.a-zA-Z0-9]+|\u4E2D\u56FD|\u7F51\u7EDC|\u516C\u53F8)((\?|\/|:)+[\w\.\/=\?%\-&~`@':+!]*)+\.(jpg|gif|png|bmp|webp))/ig, '$1[img]$2[/img]');
	}

	if(!allowhtml || !fetchCheckbox('htmlon')) {
		str = str.replace(/</g, '&lt;');
		str = str.replace(/>/g, '&gt;');
		if(!fetchCheckbox('parseurloff')) {
			str = parseurl(str, 'html', false);
		}
	}

	for(i in EXTRAFUNC['bbcode2html']) {
		EXTRASTR = str;
		try {
			eval('str = ' + EXTRAFUNC['bbcode2html'][i] + '()');
		} catch(e) {}
	}

	if(!fetchCheckbox('smileyoff') && allowsmilies) {
		if(typeof smilies_type == 'object') {
			for(var typeid in smilies_array) {
				for(var page in smilies_array[typeid]) {
					for(var i in smilies_array[typeid][page]) {
							if(smilies_type['_' + typeid][1] == ':emoji') {
								continue;
							}
						var smileypath = smilies_array[typeid][page][i][2];
						if(smileypath.indexOf('/') === -1) {
							smileypath = smilies_type['_' + typeid][1] + '/' + smileypath;
						}
						re = new RegExp(preg_quote(smilies_array[typeid][page][i][1]), "g");
						str = str.replace(re, '<img src="' + STATICURL + 'image/smiley/' + smileypath + '" border="0" smilieid="' + smilies_array[typeid][page][i][0] + '" alt="' + smilies_array[typeid][page][i][1] + '" />');
					}
				}
			}
		}
	}

	if(!fetchCheckbox('bbcodeoff') && allowbbcode) {
		str = clearcode(str);
		str = str.replace(/\[url(=((https?|ftp){1}:\/\/|www\.|mailto:|tel:|magnet:)?([^\r\n\[\"']+?))?\]([\s\S]*?)\[\/url\]/ig, function($0, $1, $2, $3, $4, $5) {
			return parseurl_bbcode($1, $5, $2);
		});
		str = str.replace(/\[email\](.[^\\=[]*)\[\/email\]/ig, '<a href="mailto:$1">$1</a>');
		str = str.replace(/\[email=(.[^\\=[]*)\](.*?)\[\/email\]/ig, '<a href="mailto:$1" target="_blank">$2</a>');
		str = str.replace(/\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]/ig, function($1, $2) {
			addCSS = '';
			if(in_array($2, postimg_type["postbg"])) {
				addCSS = '<style type="text/css" name="editorpostbg">body{background-image:url("'+STATICURL+'image/postbg/'+$2+'");}</style>';
			}
			return addCSS;
		});
		str = str.replace(/\[color=([\w#\(\),\.\s]+?)\]/ig, '<span style="color: $1">');
		str = str.replace(/\[backcolor=([\w#\(\),\.\s]+?)\]/ig, '<font style="background-color:$1">');
		str = str.replace(/\[size=(\d+?)\]/ig, '<font size="$1">');
		str = str.replace(/\[size=(\d+(\.\d+)?(px|pt)+?)\]/ig, '<font style="font-size: $1">');
		str = str.replace(/\[font=([^\[\<\=]+?)\]/ig, '<font face="$1">');
		str = str.replace(/\[align=([^\[\<\=]+?)\]/ig, '<div align="$1">');
		str = str.replace(/\[p=(\d{1,2}|null), (\d{1,2}|null), (left|center|right)\]/ig, '<p style="line-height: $1px; text-indent: $2em; text-align: $3;">');
		str = str.replace(/\[float=left\]/ig, '<br style="clear: both"><span style="float: left; margin-right: 5px;">');
		str = str.replace(/\[float=right\]/ig, '<br style="clear: both"><span style="float: right; margin-left: 5px;">');
		str = replacePairedBbcode(str, '[b]', '[/b]', '<strong>', '</strong>');
		str = replacePairedBbcode(str, '[s]', '[/s]', '<strike>', '</strike>');
		str = replacePairedBbcode(str, '[i]', '[/i]', '<i>', '</i>');
		str = replacePairedBbcode(str, '[u]', '[/u]', '<u>', '</u>');
		if(parsetype != 1) {
			str = str.replace(/\[quote]([\s\S]*?)\[\/quote\]\s?\s?/ig, '<div class="quote"><blockquote>$1</blockquote></div>\n');
			str = str.replace(/`([^`]+)`/g, '<code>$1</code>');
		}

		re = /\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*([\s\S]+?)\s*\[\/table\]/ig;
		for (i = 0; i < 4; i++) {
			str = str.replace(re, function($1, $2, $3, $4) {return parsetable($2, $3, $4);});
		}

		str = preg_replace([
			'\\\[\\\/color\\\]', '\\\[\\\/backcolor\\\]', '\\\[\\\/size\\\]', '\\\[\\\/font\\\]', '\\\[\\\/align\\\]', '\\\[\\\/p\\\]', '\\\[hr\\\]', '\\\[list\\\]', '\\\[list=1\\\]', '\\\[list=a\\\]',
			'\\\[list=A\\\]', '\\s?\\\[\\\*\\\]', '\\\[\\\/list\\\]', '\\\[indent\\\]', '\\\[\\\/indent\\\]', '\\\[\\\/float\\\]'
			], [
			'</span>', '</font>', '</font>', '</font>', '</div>', '</p>', '<hr class="l" />', '<ul>', '<ul type=1 class="litype_1">', '<ul type=a class="litype_2">',
			'<ul type=A class="litype_3">', '<li>', '</ul>', '<blockquote>', '</blockquote>', '</span>'
			], str, 'g');
	}

	if(!fetchCheckbox('bbcodeoff')) {
		if(allowimgcode) {
			str = str.replace(/\[img\]\s*([^\[\"\<\r\n]+?)\s*\[\/img\]/ig, '<img src="$1" border="0" alt="" style="max-width:400px" />');
			str = str.replace(/\[attachimg(?:=(\d{1,4}))?\](\d+)\[\/attachimg\]/ig, function ($1, $2, $3) {
				if(!$('image_' + $3)) {
					return '';
				}
				width = $2 || $('image_' + $3).getAttribute('cwidth');
				if(!width) {
					re = /cwidth=(["']?)(\d+)(\1)/i;
					var matches = re.exec($('image_' + $3).outerHTML);
					if(matches != null) {
						width = matches[2];
					}
				}
				return '<img src="' + $('image_' + $3).src + '" border="0" aid="attachimg_' + $3 + '" width="' + width + '" alt="" />';
			});
			str = str.replace(/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\"\<\r\n]+?)\s*\[\/img\]/ig, function ($1, $2, $3, $4) {return '<img' + ($2 > 0 ? ' width="' + $2 + '"' : '') + ($3 > 0 ? ' _height="' + $3 + '"' : '') + ' src="' + $4 + '" border="0" alt="" />'});
		} else {
			str = str.replace(/\[img\]\s*([^\[\"\<\r\n]+?)\s*\[\/img\]/ig, '<a href="$1" target="_blank">$1</a>');
			str = str.replace(/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\"\<\r\n]+?)\s*\[\/img\]/ig, '<a href="$3" target="_blank">$3</a>');
		}
	}

	for(var i = 0; i <= DISCUZCODE['num']; i++) {
		str = str.replace("[\tDISCUZ_CODE_" + i + "\t]", DISCUZCODE['html'][i]);
	}

	if(!allowhtml || !fetchCheckbox('htmlon')) {
		str = str.replace(/(^|>)([^<]+)(?=<|$)/ig, function($1, $2, $3) {
			return $2 + preg_replace(['(\r\n|\n|\r)'], ['<br />'], $3);
		});
	} else {
		str = str.replace(/<script[^\>]*?>([^\x00]*?)<\/script>/ig, '');
	}

	return str;
}

function replacePairedBbcode(str, openTag, closeTag, openHtml, closeHtml) {
	var parts = str.split(closeTag), result = '', count = parts.length;
	for(var i = 0; i < count - 1; i++) {
		var part = parts[i], pos = part.lastIndexOf(openTag);
		if(pos < 0) {
			result += part + closeTag;
		} else {
			result += part.substring(0, pos) + openHtml + part.substring(pos + openTag.length) + closeHtml;
		}
	}
	return result + parts[count - 1];
}

function clearcode(str) {
	str = str.replace(/\[url(=((https?|ftp){1}:\/\/|www\.|mailto:|tel:|magnet:)?([^\r\n\[\"']+?))?\]\[\/url\]/ig, '');
	str= str.replace(/\[email\]\[\/email\]/ig, '', str);
	str= str.replace(/\[email=(.[^\[]*)\]\[\/email\]/ig, '', str);
	str= str.replace(/\[color=([^\[\<]+?)\]\[\/color\]/ig, '', str);
	str= str.replace(/\[size=(\d+?)\]\[\/size\]/ig, '', str);
	str= str.replace(/\[size=(\d+(\.\d+)?(px|pt)+?)\]\[\/size\]/ig, '', str);
	str= str.replace(/\[font=([^\[\<]+?)\]\[\/font\]/ig, '', str);
	str= str.replace(/\[align=([^\[\<]+?)\]\[\/align\]/ig, '', str);
	str= str.replace(/\[p=(\d{1,2}), (\d{1,2}), (left|center|right)\]\[\/p\]/ig, '', str);
	str= str.replace(/\[float=([^\[\<]+?)\]\[\/float\]/ig, '', str);
	str= str.replace(/\[quote\]\[\/quote\]/ig, '', str);
	str= str.replace(/\[code\]\[\/code\]/ig, '', str);
	str= str.replace(/\[table\]\[\/table\]/ig, '', str);
	str= str.replace(/\[free\]\[\/free\]/ig, '', str);
	str= str.replace(/\[b\]\[\/b]/ig, '', str);
	str= str.replace(/\[u\]\[\/u]/ig, '', str);
	str= str.replace(/\[i\]\[\/i]/ig, '', str);
	str= str.replace(/\[s\]\[\/s]/ig, '', str);
	return str;
}

function parseurl_bbcode(url, text, scheme) {
	var link_rel_attribute = '';
	if(!url) {
		url = text;
		var displaytext = text;
		try {
			displaytext = decodeURIComponent(displaytext);
		} catch(e) {}
		if(/^https?:\/\//i.test(displaytext)) {
			link_rel_attribute = ' rel="external nofollow"';
		} else if(/^www\./i.test(url)) {
			url = '//' + url;
			link_rel_attribute = ' rel="external nofollow"';
		}
		if(displaytext.length > 95) {
			displaytext = displaytext.substring(0, 64) + ' &hellip; ' + displaytext.substring(displaytext.length - 20);
		}
		return '<a href="' + url + '"' + link_rel_attribute + ' target="_blank">' + displaytext + '</a>';
	} else {
		url = url.substr(1);
		if(!text) {
			return '<a name="' + url + '"></a>';
		}
		if(url.charAt(0) == '#') {
			return '<a href="' + url + '">' + text + '</a>';
		} else {
			if(/^https?:\/\//i.test(url)) {
				link_rel_attribute = ' rel="external nofollow"';
			} else if(/^www\./i.test(url)) {
				url = '//' + url;
				link_rel_attribute = ' rel="external nofollow"';
			}
			return '<a href="' + url + '"' + link_rel_attribute + ' target="_blank">' + text + '</a>';
		}
	}
}

function cuturl(url) {
	var length = 65;
	var urllink = '<a href="' + (url.toLowerCase().substr(0, 4) == 'www.' ? 'http://' + url : url) + '" target="_blank">';
	if(url.length > length) {
		url = url.substr(0, parseInt(length * 0.5)) + ' ... ' + url.substr(url.length - parseInt(length * 0.3));
	}
	urllink += url + '</a>';
	return urllink;
}

function dstag(options, text, tagname) {
	if(trim(text) == '') {
		return '\n';
	}
	var pend = parsestyle(options, '', '');
	var prepend = pend['prepend'];
	var append = pend['append'];
	if(in_array(tagname, ['div', 'p'])) {
		align = getoptionvalue('align', options);
		if(in_array(align, ['left', 'center', 'right'])) {
			prepend = '[align=' + align + ']' + prepend;
			append += '[/align]';
		} else {
			append += '\n';
		}
	}
	return prepend + recursion(tagname, text, 'dstag') + append;
}

function ptag(options, text, tagname) {
	if(trim(text) == '') {
		return '\n';
	}
	if(trim(options) == '') {
		return text + '\n';
	}

	var lineHeight = null;
	var textIndent = null;
	var align, re, matches;

	re = /line-height\s?:\s?(\d{1,3})px/i;
	matches = re.exec(options);
	if(matches != null) {
		lineHeight = matches[1];
	}

	re = /text-indent\s?:\s?(\d{1,3})em/i;
	matches = re.exec(options);
	if(matches != null) {
		textIndent = matches[1];
	}

	re = /text-align\s?:\s?(left|center|right)/i;
	matches = re.exec(options);
	if(matches != null) {
		align = matches[1];
	} else {
		align = getoptionvalue('align', options);
	}
	align = in_array(align, ['left', 'center', 'right']) ? align : 'left';
	style = getoptionvalue('style', options);
	style = preg_replace(['line-height\\s?:\\s?(\\d{1,3})px', 'text-indent\\s?:\\s?(\\d{1,3})em', 'text-align\\s?:\\s?(left|center|right)'], '', style);
	if(lineHeight === null && textIndent === null) {
		return '[align=' + align + ']' + (style ? '<span style="' + style + '">' : '') + text + (style ? '</span>' : '') + '[/align]';
	} else {
		return '[align=' + lineHeight + ', ' + textIndent + ', ' + align + ']' + (style ? '<span style="' + style + '">' : '') + text + (style ? '</span>' : '') + '[/align]';
	}
}

function fetchCheckbox(cbn) {
	return $(cbn) && $(cbn).checked == true ? 1 : 0;
}

function fetchoptionvalue(option, text) {
	if((position = strpos(text, option)) !== false) {
		delimiter = position + option.length;
		if(text.charAt(delimiter) == '"') {
			delimchar = '"';
		} else if(text.charAt(delimiter) == '\'') {
			delimchar = '\'';
		} else {
			delimchar = ' ';
		}
		delimloc = strpos(text, delimchar, delimiter + 1);
		if(delimloc === false) {
			delimloc = text.length;
		} else if(delimchar == '"' || delimchar == '\'') {
			delimiter++;
		}
		return trim(text.substr(delimiter, delimloc - delimiter));
	} else {
		return '';
	}
}

function normalizefontvalue(value) {
	return trim(value
		.replace(/&quot;|&#0*34;|&#x0*22;/ig, '"')
		.replace(/&apos;|&#0*39;|&#x0*27;/ig, "'")
		.replace(/["']/g, ''));
}

function fonttag(fontoptions, text) {
	var prepend = '';
	var append = '';
	var tags = new Array();
	tags = {'font' : 'face=', 'size' : 'size=', 'color' : 'color='};
	for(bbcode in tags) {
		optionvalue = fetchoptionvalue(tags[bbcode], fontoptions);
		if(optionvalue) {
			if(bbcode == 'font') {
				optionvalue = normalizefontvalue(optionvalue);
			}
			if(!isDefaultFontAttr(bbcode, optionvalue)) {
				prepend += '[' + bbcode + '=' + optionvalue + ']';
				append = '[/' + bbcode + ']' + append;
			}
		}
	}

	var pend = parsestyle(fontoptions, prepend, append);
	return pend['prepend'] + recursion('font', text, 'fonttag') + pend['append'];
}

function getoptionvalue(option, text) {
	re = new RegExp(option + "(\\s+?)?=(\\s+?)?[\"']?(.+?)([\"']|$|>)", "ig");
	var matches = re.exec(text);
	if(matches != null) {
		return trim(matches[3]);
	}
	return '';
}

function html2bbcode(str) {

	var codespanRe = /<span\b[^>]*\bstyle=(["'])(?=[^"']*font-family:[^"']*Monaco)(?=[^"']*white-space:[^"']*pre-wrap)[^"']*\1[^>]*>([\s\S]*?)<\/span>/ig;

	if((allowhtml && fetchCheckbox('htmlon')) || trim(str) == '') {
		for(i in EXTRAFUNC['html2bbcode']) {
			EXTRASTR = str;
			try {
				eval('str = ' + EXTRAFUNC['html2bbcode'][i] + '()');
			} catch(e) {}
		}
		str = str.replace(codespanRe, function($0, $1, $2) {return codetag($2);});
		str = str.replace(/<img[^>]+smilieid=(["']?)(\d+)(\1)[^>]*>/ig, function($1, $2, $3) {return smileycode($3);});
		str = str.replace(/<img([^>]*aid=[^>]*)>/ig, function($1, $2) {return imgtag($2);});
		return str;
	}

	if(navigator.userAgent.indexOf('Chrome') > -1){
		str = str.replace(/<div><br><\/div>/ig, '<br>');
		str = str.replace(/<div>/ig, '<br><div>');
		str = str.replace(/<\/div>((<br[^>]*>){1,})<div>/ig, '$1');
	}

	str = str.replace(/<div\sclass=["']?blockcode["']?>[\s\S]*?<pre[^>]*>([\s\S]+?)<\/pre>[\s\S]*?<\/div>/ig, function($1, $2) {
		return codetag($2.replace(codespanRe, function($0, $1, $2) {return $2;}));
	});

	str = str.replace(codespanRe, function($0, $1, $2) {return codetag($2);});

	if(!fetchCheckbox('bbcodeoff') && allowbbcode) {
		var postbg = '';
		str = str.replace(/<style[^>]+name="editorpostbg"[^>]*>body{background-image:url\("([^\[\<\r\n;'\"\?\(\)]+?)"\);}<\/style>/ig, function($1, $4) {
			$4 = $4.replace(STATICURL+'image/postbg/', '');
			return '[postbg]'+$4+'[/postbg]';
		});
		str = str.replace(/\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]/ig, function($1, $2) {
			postbg = $2;
			return '';
		});
		if(postbg) {
			str = '[postbg]'+postbg+'[/postbg]' + str;
		}
	}

	str = preg_replace(['<style.*?>[\\s\\S]*?</style>', '<script.*?>[\\s\\S]*?</script>', '<noscript.*?>[\\s\\S]*?</noscript>', '<select.*?>[\\s\\S]*?</select>', '<object.*?>[\\s\\S]*?</object>', '<!--[\\s\\S]*?-->', ' on[a-zA-Z]{3,16}\\s?=\\s?"[\\s\\S]*?"'], '', str);

	str = str.replace(/([^>])(\r\n|\n|\r)([^<])/ig, '$1 $3');
	str = str.replace(/(\r\n|\n|\r)/ig, '');

	str= str.replace(/&((#(32|127|160|173))|shy|nbsp);/ig, ' ');

	if(fetchCheckbox('allowimgurl')) {
		str = str.replace(/([^>=\]"'\/]|^)((((https?|ftp):\/\/)|www\.)([\w\-]+\.)*[\w\-\u4e00-\u9fa5]+\.([\.a-zA-Z0-9]+|\u4E2D\u56FD|\u7F51\u7EDC|\u516C\u53F8)((\?|\/|:)+[\w\.\/=\?%\-&~`@':+!]*)+\.(jpg|gif|png|bmp|webp))/ig, '$1[img]$2[/img]');
	}

	if(!fetchCheckbox('parseurloff')) {
		str = parseurl(str, 'bbcode', false);
	}

	for(i in EXTRAFUNC['html2bbcode']) {
		EXTRASTR = str;
		try {
			eval('str = ' + EXTRAFUNC['html2bbcode'][i] + '()');
		} catch(e) {}
	}

	str = str.replace(/<br\s+?style=(["']?)clear: both;?(\1)[^\>]*>/ig, '');
	str = str.replace(/<br[^\>]*>/ig, "\n");

	if(!fetchCheckbox('bbcodeoff') && allowbbcode) {
		str = str.replace(/<\/?(thead|tbody|tfoot)[^>]*>/ig, '');
		str = preg_replace([
			'<table[^>]*float:\\s*(left|right)[^>]*><tbody><tr><td>\\s*([\\s\\S]+?)\\s*</td></tr></tbody></table>',
			'<table([^>]*(width|background|background-color|backcolor)[^>]*)>',
			'<table[^>]*>',
			'<tr[^>]*(?:background|background-color|backcolor)[:=]\\s*(["\']?)([()\\s%,#\\w]+)(\\1)[^>]*>',
			'<tr[^>]*>',
			'<(td|th)\\b([^>]*(width|colspan|rowspan)[^>]*)>',
			'<(td|th)\\b[^>]*>',
			'<\\/(td|th)>',
			'<\/tr>',
			'<\/table>',
		], [
			function($1, $2, $3) {return '[float=' + $2 + ']' + $3 + '[/float]';},
			function($1, $2) {return tabletag($2);},
			'[table]\n',
			function($1, $2, $3) {return '[tr=' + $3 + ']';},
			'[tr]',
			function($1, $2, $3, $4) {return tdtag($4, $2);},
			function($1, $2) {return '[' + $2 + ']';},
			function($1, $2) {return '[/' + $2 + ']';},
			'[/tr]\n',
			'[/table]',
		], str);

		str = str.replace(/\[table\][\s\r\n]*/ig, '[table]\n');
		str = str.replace(/[\s\r\n]*\[\/table\]/ig, '\n[/table]');
		str = str.replace(/[\s\t]*\[tr\][\s\t]*/ig, '[tr]');
		str = str.replace(/[\s\t]*\[\/tr\][\s\t]*/ig, '[/tr]');
		str = str.replace(/\[\/tr\]\n*/ig, '[/tr]\n');
		str = str.replace(/[\s\t]*\[(td|th)\][\s\t]*/ig, '[$1]');
		str = str.replace(/[\s\t]*\[\/(td|th)\][\s\t]*/ig, '[/$1]');

		str = str.replace(/<h([1-6])[^>]*>([\s\S]*?)<\/h\1>/ig, function ($1, $2, $3) {
			let sizeValue = 7 - parseInt($2);
			return "[size=" + sizeValue + "]" + $3 + "[/size]\n\n";
		});
		str = str.replace(/<hr[^>]*>/ig, "[hr]");
		str = str.replace(/<img[^>]+smilieid=(["']?)(\d+)(\1)[^>]*>/ig, function($1, $2, $3) {return smileycode($3);});
		str = str.replace(/<img([^>]*src[^>]*)>/ig, function($1, $2) {return imgtag($2);});
		str = str.replace(/<a\s+?name=(["']?)(.+?)(\1)[\s\S]*?>([\s\S]*?)<\/a>/ig, '$4');
		str = str.replace(/<div[^>]*quote[^>]*><blockquote>([\s\S]*?)<\/blockquote><\/div>([\s\S]*?)(<br[^>]*>)?/ig, "[quote]$1[/quote]");
		str = str.replace(/<div[^>]*blockcode[^>]*><pre[^>]*>([\s\S]*?)<\/pre><\/div>([\s\S]*?)(<br[^>]*>)?/ig, "[code]$1[/code]");
		str = str.replace(/<code[^>]*>([\s\S]*?)<\/code>/ig, "`$1`");

		str = recursion('b', str, 'simpletag', 'b');
		str = recursion('strong', str, 'simpletag', 'b');
		str = recursion('i', str, 'simpletag', 'i');
		str = recursion('em', str, 'simpletag', 'i');
		str = recursion('u', str, 'simpletag', 'u');
		str = recursion('strike', str, 'simpletag', 's');
		str = recursion('a', str, 'atag');
		str = recursion('font', str, 'fonttag');
		str = recursion('blockquote', str, 'simpletag', 'indent');
		str = recursion('ol', str, 'listtag');
		str = recursion('ul', str, 'listtag');
		str = recursion('div', str, 'dstag');
		str = recursion('p', str, 'ptag');
		str = recursion('span', str, 'fonttag');
	}

	str = str.replace(/<[\/\!]*?[^<>]*?>/ig, '');

	for(var i = 0; i <= DISCUZCODE['num']; i++) {
		str = str.replace("[\tDISCUZ_CODE_" + i + "\t]", DISCUZCODE['html'][i]);
	}
	str = clearcode(str);

	return preg_replace(['&nbsp;', '&lt;', '&gt;', '&amp;'], [' ', '<', '>', '&'], str).trim();
}

function tablesimple(s, table, str) {
	if(strpos(str, '[tr=') || strpos(str, '[td=') || strpos(str, '[th=')) {
		return s;
	} else {
		return '[table=' + table + ']\n' + preg_replace(['\\[tr\\]', '\\[/(td|th)\\]\\s*\\[(td|th)\\]', '\\[/tr\\]\\s?', '\\[(td|th)\\]', '\\[/(td|th)\\]', '\\[/(td|th)\\]\\[/tr\\]'], ['', '|', '', '', '', '', ''], str) + '[/table]';
	}
}

function imgtag(attributes) {
	var width = '';
	var height = '';

	re = /src=(["']?)([\s\S]*?)(\1)/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		var src = matches[2];
	} else {
		return '';
	}

	re = /(max-)?width\s?:\s?(\d{1,4})(px)?/i;
	var matches = re.exec(attributes);
	if(matches != null && !matches[1]) {
		width = matches[2];
	}

	re = /height\s?:\s?(\d{1,4})(px)?/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		height = matches[1];
	}

	if(!width) {
		re = /width=(["']?)(\d+)(\1)/i;
		var matches = re.exec(attributes);
		if(matches != null) {
			width = matches[2];
		}
	}

	if(!height) {
		re = /height=(["']?)(\d+)(\1)/i;
		var matches = re.exec(attributes);
		if(matches != null) {
			height = matches[2];
		}
	}

	re = /aid=(["']?)attachimg_(\d+)(\1)/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		return '[attachimg' + (width > 0 ? '=' + width : '') + ']' + matches[2] + '[/attachimg]';
	}

	width = width > 0 ? width : 0;
	height = height > 0 ? height : 0;
	return width > 0 || height > 0 ?
		'[img=' + width + ',' + height + ']' + src + '[/img]' :
		'[img]' + src + '[/img]';
}

function listtag(listoptions, text, tagname) {
	text = text.replace(/<li>(([\s\S](?!<\/li))*?)(?=<\/?ol|<\/?ul|<li|\[list|\[\/list)/ig, '<li>$1</li>') + (BROWSER.opera ? '</li>' : '');
	text = recursion('li', text, 'litag');
	var opentag = '[list]';
	var listtype = fetchoptionvalue('type=', listoptions);
	listtype = listtype != '' ? listtype : (tagname == 'ol' ? '1' : '');
	if(in_array(listtype, ['1', 'a', 'A'])) {
		opentag = '[list=' + listtype + ']';
	}
	return text ? opentag + '\n' + recursion(tagname, text, 'listtag') + '[/list]' : '';
}

function litag(listoptions, text) {
	return '[*]' + text.replace(/(\s+)$/g, '') + '\n';
}

function parsecode(text) {
	DISCUZCODE['num']++;
	text = text.replace(/\$/ig, '$$$$');
	DISCUZCODE['html'][DISCUZCODE['num']] = '<div class="blockcode"><pre>' + htmlspecialchars(text) + '</pre></div>';
	return "[\tDISCUZ_CODE_" + DISCUZCODE['num'] + "\t]";
}

function isDefaultFontAttr(type, value) {
	if(!value) return true;
	var val = String(value).trim().toLowerCase();
	if(type === 'font') {
		return ['times new roman', 'times', 'serif', 'sans-serif', 'inherit', 'initial', '-webkit-standard', 'var(--dz-ff)'].indexOf(val) !== -1;
	}
	if(type === 'size') {
		return ['3', 3, '14px', '16px', 'medium', '13.3333px', '13px', '100%', '1em', '1.5em', 'inherit', 'initial'].indexOf(val) !== -1;
	}
	if(type === 'color') {
		return ['#066eff', '#066ff', '#0066ff', '#0000ee', '#0000ff', '#000000', '#000', '#333333', '#333', 'inherit', 'initial', 'rgb(6, 110, 255)', 'rgb(6,110,255)', 'rgb(0, 0, 238)', 'rgb(0,0,238)', 'rgb(0, 0, 255)', 'rgb(0,0,255)', 'rgb(0, 0, 0)', 'rgb(51, 51, 51)'].indexOf(val) !== -1;
	}
	return false;
}

function parsestyle(tagoptions, prepend, append) {
	var searchlist = [
		['align', true, 'text-align:\\s*(left|center|right);?', 1],
		['float', true, 'float:\\s*(left|right);?', 1],
		['color', true, '(^|[;\\s])color:\\s*([^;]+);?', 2],
		['backcolor', true, '(^|[;\\s])background-color:\\s*([^;]+);?', 2],
		['font', true, 'font-family:\\s*([^;]+);?', 1],
		['size', true, 'font-size:\\s*(\\d+(\\.\\d+)?(px|pt|in|cm|mm|pc|em|ex|%|));?', 1],
		['size', true, 'font-size:\\s*(x\\-small|small|medium|large|x\\-large|xx\\-large|\\-webkit\\-xxx\\-large);?', 1, 'size'],
		['b', false, 'font-weight:\\s*(bold);?'],
		['i', false, 'font-style:\\s*(italic);?'],
		['u', false, 'text-decoration:\\s*(underline);?'],
		['s', false, 'text-decoration:\\s*(line-through);?']
	];
	var sizealias = {'x-small':1,'small':2,'medium':3,'large':4,'x-large':5,'xx-large':6,'-webkit-xxx-large':7};
	var style = getoptionvalue('style', tagoptions);
	style = style
		.replace(/&quot;|&#0*34;|&#x0*22;/ig, '"')
		.replace(/&apos;|&#0*39;|&#x0*27;/ig, "'");
	re = /(^|[;\s])color:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)(;?)/ig;
	style = style.replace(re, function($1, $2, $3, $4, $5, $6) {
		var r = parseInt($3).toString(16); if(r.length < 2) r = '0' + r;
		var g = parseInt($4).toString(16); if(g.length < 2) g = '0' + g;
		var b = parseInt($5).toString(16); if(b.length < 2) b = '0' + b;
		return $2 + "color:#" + r + g + b + $6;
	});
	var len = searchlist.length;
	for(var i = 0; i < len; i++) {
		searchlist[i][4] = !searchlist[i][4] ? '' : searchlist[i][4];
		re = new RegExp(searchlist[i][2], "ig");
		match = re.exec(style);
		if(match != null) {
			opnvalue = match[searchlist[i][3]];
			if(searchlist[i][4] == 'size') {
				opnvalue = sizealias[opnvalue];
			} else if(searchlist[i][0] == 'font') {
				opnvalue = normalizefontvalue(opnvalue);
			}
			if(!isDefaultFontAttr(searchlist[i][0], opnvalue)) {
				prepend += '[' + searchlist[i][0] + (searchlist[i][1] == true ? '=' + opnvalue + ']' : ']');
				append = '[/' + searchlist[i][0] + ']' + append;
			}
		}
	}
	return {'prepend' : prepend, 'append' : append};
}

function parsetable(width, bgcolor, str) {

	if(isUndefined(width)) {
		var width = '';
	} else {
		try {
			width = width.substr(width.length - 1, width.length) == '%' ? (width.substr(0, width.length - 1) <= 98 ? width : '98%') : (width <= 560 ? width : '98%');
		} catch(e) { width = ''; }
	}
	if(isUndefined(str)) {
		return;
	}

	if(strpos(str, '[/tr]') === false && strpos(str, '[/td]') === false && strpos(str, '[/th]') === false) {
		var rows = str.split('\n');
		var s = '';
		for(i = 0;i < rows.length;i++) {
			s += '<tr><td>' + preg_replace(['\r', '\\\\\\\|', '\\\|', '\\\\n'], ['', '&#124;', '</td><td>', '\n'], rows[i]) + '</td></tr>';
		}
		str = s;
		simple = ' simpletable';
	} else {
		simple = '';
		str = str.replace(/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[(td|th)(?:=(\d{1,4}%?))?\]/ig, function($1, $2, $3, $4) {
			return '<tr' + ($2 ? ' style="background-color: ' + $2 + '"' : '') + '><' + $3 + ($4 ? ' width="' + $4 + '"' : '') + '>';
		});
		str = str.replace(/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[(td|th)(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/ig, function($1, $2, $3, $4, $5, $6) {
			return '<tr' + ($2 ? ' style="background-color: ' + $2 + '"' : '') + '><' + $3 + ($4 ? ' colspan="' + $4 + '"' : '') + ($5 ? ' rowspan="' + $5 + '"' : '') + ($6 ? ' width="' + $6 + '"' : '') + '>';
		});
		str = str.replace(/\[\/(td|th)\]\s*\[(td|th)(?:=(\d{1,4}%?))?\]/ig, function($1, $2, $3, $4) {
			return '</$2><' + $3 + ($4 ? ' width="' + $4 + '"' : '') + '>';
		});
		str = str.replace(/\[\/(td|th)\]\s*\[(td|th)(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/ig, function($1, $2, $3, $4, $5, $6) {
			return '</$2><' + $3 + ($4 ? ' colspan="' + $4 + '"' : '') + ($5 ? ' rowspan="' + $5 + '"' : '') + ($6 ? ' width="' + $6 + '"' : '') + '>';
		});
		str = str.replace(/\[\/(td|th)\]\s*\[\/tr\]\s*/ig, '</$1></tr>');
		str = str.replace(/<td> <\/td>|<th> <\/th>/ig, '<td>&nbsp;</td>');
	}
	return '<table ' + (width == '' ? '' : 'width="' + width + '" ') + 'class="t_table"' + (isUndefined(bgcolor) ? '' : ' style="background-color: ' + bgcolor + '"') + simple +'>' + str + '</table>';
}

function preg_quote(str) {
	return (str+'').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!<>\|\:])/g, "\\$1");
}

function recursion(tagname, text, dofunction, extraargs) {
	if(extraargs == null) {
		extraargs = '';
	}
	tagname = tagname.toLowerCase();

	var open_tag = '<' + tagname;
	var open_tag_len = open_tag.length;
	var close_tag = '</' + tagname + '>';
	var close_tag_len = close_tag.length;
	var beginsearchpos = 0;

	do {
		var textlower = text.toLowerCase();
		var tagbegin = textlower.indexOf(open_tag, beginsearchpos);
		if(tagbegin == -1) {
			break;
		}

		var strlen = text.length;

		var inquote = '';
		var found = false;
		var tagnameend = false;
		var optionend = 0;
		var t_char = '';

		for(optionend = tagbegin; optionend <= strlen; optionend++) {
			t_char = text.charAt(optionend);
			if((t_char == '"' || t_char == "'") && inquote == '') {
				inquote = t_char;
			} else if((t_char == '"' || t_char == "'") && inquote == t_char) {
				inquote = '';
			} else if(t_char == '>' && !inquote) {
				found = true;
				break;
			} else if((t_char == '=' || t_char == ' ') && !tagnameend) {
				tagnameend = optionend;
			}
		}

		if(!found) {
			break;
		}
		if(!tagnameend) {
			tagnameend = optionend;
		}

		var offset = optionend - (tagbegin + open_tag_len);
		var tagoptions = text.substr(tagbegin + open_tag_len, offset);
		var acttagname = textlower.substr(tagbegin * 1 + 1, tagnameend - tagbegin - 1);

		if(acttagname != tagname) {
			beginsearchpos = optionend;
			continue;
		}

		var tagend = textlower.indexOf(close_tag, optionend);
		if(tagend == -1) {
			break;
		}

		var nestedopenpos = textlower.indexOf(open_tag, optionend);
		while(nestedopenpos != -1 && tagend != -1) {
			if(nestedopenpos > tagend) {
				break;
			}
			tagend = textlower.indexOf(close_tag, tagend + close_tag_len);
			nestedopenpos = textlower.indexOf(open_tag, nestedopenpos + open_tag_len);
		}

		if(tagend == -1) {
			beginsearchpos = optionend;
			continue;
		}

		var localbegin = optionend + 1;
		var localtext = eval(dofunction)(tagoptions, text.substr(localbegin, tagend - localbegin), tagname, extraargs);

		text = text.substring(0, tagbegin) + localtext + text.substring(tagend + close_tag_len);

		beginsearchpos = tagbegin + localtext.length;

	} while(tagbegin != -1);

	return text;
}

function simpletag(options, text, tagname, parseto) {
	if(trim(text) == '') {
		return '';
	}
	text = recursion(tagname, text, 'simpletag', parseto);
	return '[' + parseto + ']' + text + '[/' + parseto + ']';
}

function smileycode(smileyid) {
	if(typeof smilies_type != 'object') return;
	for(var typeid in smilies_array) {
		for(var page in smilies_array[typeid]) {
			for(var i in smilies_array[typeid][page]) {
				if(smilies_array[typeid][page][i][0] == smileyid) {
					return smilies_array[typeid][page][i][1];
					break;
				}
			}
		}
	}
}

function strpos(haystack, needle, _offset) {
	if(isUndefined(_offset)) {
		_offset = 0;
	}

	var _index = haystack.toLowerCase().indexOf(needle.toLowerCase(), _offset);

	return _index == -1 ? false : _index;
}

function tabletag(attributes) {
	var width = '';
	re = /width=(["']?)(\d{1,4}%?)(\1)/i;
	var matches = re.exec(attributes);

	if(matches != null) {
		width = matches[2].substr(matches[2].length - 1, matches[2].length) == '%' ?
			(matches[2].substr(0, matches[2].length - 1) <= 98 ? matches[2] : '98%') :
			(matches[2] <= 560 ? matches[2] : '98%');
	} else {
		re = /width\s?:\s?(\d{1,4})([px|%])/i;
		var matches = re.exec(attributes);
		if(matches != null) {
			width = matches[2] == '%' ? (matches[1] <= 98 ? matches[1] + '%' : '98%') : (matches[1] <= 560 ? matches[1] : '98%');
		}
	}

	var bgcolor = '';
	re = /(?:background|background-color|bgcolor)[:=]\s*(["']?)((rgb\(\d{1,3}%?,\s*\d{1,3}%?,\s*\d{1,3}%?\))|(#[0-9a-fA-F]{3,6})|([a-zA-Z]{1,20}))(\1)/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		bgcolor = matches[2];
		width = width ? width : '98%';
	}

	return bgcolor ? '[table=' + width + ',' + bgcolor + ']\n' : (width ? '[table=' + width + ']\n' : '[table]\n');
}

function tdtag(attributes, tagName) {
	tagName = tagName || 'td';

	var colspan = 1;
	var rowspan = 1;
	var width = '';

	re = /colspan=(["']?)(\d{1,2})(\1)/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		colspan = matches[2];
	}

	re = /rowspan=(["']?)(\d{1,2})(\1)/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		rowspan = matches[2];
	}

	re = /width=(["']?)(\d{1,4}%?)(\1)/i;
	var matches = re.exec(attributes);
	if(matches != null) {
		width = matches[2];
	}

	return in_array(width, ['', '0', '100%']) ?
		(colspan == 1 && rowspan == 1 ? '[' + tagName + ']' : '[' + tagName + '=' + colspan + ',' + rowspan + ']') :
		(colspan == 1 && rowspan == 1 ? '[' + tagName + '=' + width + ']' : '[' + tagName + '=' + colspan + ',' + rowspan + ',' + width + ']');
}

if(typeof jsloaded == 'function') {
	jsloaded('bbcode');
}
