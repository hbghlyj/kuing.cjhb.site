/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

function _smilies_show(id, smcols, seditorkey) {
	if(seditorkey && !$(seditorkey + 'sml_menu')) {
		var div = document.createElement("div");
		div.id = seditorkey + 'sml_menu';
		div.style.display = 'none';
		div.className = 'sllt';
		$('append_parent').appendChild(div);
		div = document.createElement("div");
		div.id = id;
		div.style.overflow = 'hidden';
		$(seditorkey + 'sml_menu').appendChild(div);
	}
	if(typeof smilies_type == 'undefined') {
		var scriptNode = document.createElement("script");
		scriptNode.type = "text/javascript";
		scriptNode.charset = charset ? charset : (BROWSER.firefox ? document.characterSet : document.charset);
		scriptNode.src = JSPATH + 'common_smilies_var.js?' + VERHASH;
		$('append_parent').appendChild(scriptNode);
		if(BROWSER.ie) {
			scriptNode.onreadystatechange = function() {
				smilies_onload(id, smcols, seditorkey);
			};
		} else {
			scriptNode.onload = function() {
				smilies_onload(id, smcols, seditorkey);
			};
		}
	} else {
		smilies_onload(id, smcols, seditorkey);
	}
}

function smilies_onload(id, smcols, seditorkey) {
	seditorkey = !seditorkey ? '' : seditorkey;
	var smilecookie = getcookie('smile') || '';
	var smile = smilecookie ? smilecookie.split('D') : [];
	var i, key, smiliestype, s, smilieimg;
	var img = [];
	var k = 0;
	if(typeof smilies_type == 'object') {
		if(smile[0] && smilies_array[smile[0]]) {
			CURRENTSTYPE = smile[0];
		} else {
			for(i in smilies_array) {
				CURRENTSTYPE = i;
				break;
			}
		}
		smiliestype = '<div id="' + id + '_tb" class="tb tb_s cl"><ul>';
		for(i in smilies_type) {
			key = i.substring(1);
			if(smilies_type[i][0]) {
				smiliestype += '<li ' + (CURRENTSTYPE == key ? 'class="current"' : '') + ' id="' + seditorkey + 'stype_' + key + '" onclick="smilies_switch(\'' + id + '\', \'' + smcols + '\', ' + key + ', 1, \'' + seditorkey + '\');if(CURRENTSTYPE) {$(\'' + seditorkey + 'stype_\' + CURRENTSTYPE).className=\'\';}this.className=\'current\';CURRENTSTYPE=' + key + ';doane(event);"><a href="javascript:;" hidefocus="true">' + smilies_type[i][0] + '</a></li>';
			}
		}
		smiliestype += '</ul></div>';
		$(id).innerHTML = smiliestype + '<div id="' + id + '_data"></div><div class="sllt_p" id="' + id + '_page"></div>';
		smilies_switch(id, smcols, CURRENTSTYPE, smile[1], seditorkey);
		var smilies_fastdata = '';
		if(seditorkey == 'fastpost' && $('fastsmilies') && smilies_fast) {
			var j = 0;
			for(i = 0; i < smilies_fast.length; i++) {
				if(j === 0) {
					smilies_fastdata += '<tr>';
				}
				j = ++j > 3 ? 0 : j;
				s = smilies_array[smilies_fast[i][0]][smilies_fast[i][1]][smilies_fast[i][2]];
				if(smilies_type['_' + smilies_fast[i][0]][1] == ':emoji') {
					smilies_fastdata += s ? '<td onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + s[0] + ')' : 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[1] + '\')') +
					'" id="' + seditorkey + 'smilie_' + s[0] + '_td"><span id="smilie_' + s[0] + '">' + s[1] + '</span>' : '<td>';
				} else {
					smilieimg = STATICURL + 'image/smiley/' + smilies_type['_' + smilies_fast[i][0]][1] + '/' + s[2];
					img[k] = new Image();
					img[k].src = smilieimg;
					smilies_fastdata += s ? '<td onmouseover="smilies_preview(\'' + seditorkey + '\', \'fastsmiliesdiv\', this, ' + s[5] + ')" onmouseout="$(\'smilies_preview\').style.display = \'none\'" onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + s[0] + ')' : 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[1].replace(/'/, '\\\'') + '\')') +
						'" id="' + seditorkey + 'smilie_' + s[0] + '_td"><img id="smilie_' + s[0] + '" width="20" height="20" src="' + smilieimg + '" alt="' + s[1] + '" />' : '<td>';
				}
				k++;
			}
			$('fastsmilies').innerHTML = '<table cellspacing="0" cellpadding="0"><tr>' + smilies_fastdata + '</tr></table>';
		}
	}
}

function getFirstSmileyPage(type) {
	if(!smilies_array[type]) {
		return 1;
	}
	for(var page = 1; page < smilies_array[type].length; page++) {
		if(smilies_array[type][page] && smilies_array[type][page].length) {
			return page;
		}
	}
	return 1;
}

function getAdjacentSmileyPage(type, page, direction) {
	if(!smilies_array[type]) {
		return 1;
	}
	var lastPage = smilies_array[type].length - 1;
	if(lastPage < 1) {
		return 1;
	}
	for(var step = 1; step <= lastPage; step++) {
		var nextPage = page + direction * step;
		while(nextPage < 1) {
			nextPage += lastPage;
		}
		while(nextPage > lastPage) {
			nextPage -= lastPage;
		}
		if(smilies_array[type][nextPage] && smilies_array[type][nextPage].length) {
			return nextPage;
		}
	}
	return getFirstSmileyPage(type);
}

function smilies_switch(id, smcols, type, page, seditorkey) {
	page = page ? parseInt(page, 10) : 1;
	if(!smilies_array[type]) {
		return;
	}
	if(!smilies_array[type][page] || !smilies_array[type][page].length) {
		page = getFirstSmileyPage(type);
	}
	if(!smilies_array[type][page] || !smilies_array[type][page].length) {
		$(id + '_data').innerHTML = '';
		$(id + '_page').innerHTML = '';
		return;
	}
	setcookie('smile', type + 'D' + page, 31536000);
	var smiliesdata = '<table id="' + id + '_table" cellpadding="0" cellspacing="0"><tr>';
	var j = 0;
	var k = 0;
	var img = [];
	var s, smilieimg, prevpage, nextpage;
	for(var i = 0; i < smilies_array[type][page].length; i++) {
		if(j >= smcols) {
			smiliesdata += '<tr>';
			j = 0;
		}
		s = smilies_array[type][page][i];
		smilieimg = STATICURL + 'image/smiley/' + smilies_type['_' + type][1] + '/' + s[2];
		if(smilies_type['_' + type][1] == ':emoji') {
			smiliesdata += s && s[0] ? '<td onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + s[0] + ')' : 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[1] + '\')') +
			'" id="' + seditorkey + 'smilie_' + s[0] + '_td"><span id="smilie_' + s[0] + '">' + s[1] + '</span>' : '<td>';
		} else {
			img[k] = new Image();
			img[k].src = smilieimg;
			smiliesdata += s && s[0] ? '<td onmouseover="smilies_preview(\'' + seditorkey + '\', \'' + id + '\', this, ' + s[5] + ')" onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + s[0] + ')' : 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[1].replace(/'/, '\\\'') + '\')') +
				'" id="' + seditorkey + 'smilie_' + s[0] + '_td"><img id="smilie_' + s[0] + '" width="20" height="20" src="' + smilieimg + '" alt="' + s[1] + '" />' : '<td>';
		}
		j++;
		k++;
	}
	smiliesdata += '</table>';
	var smiliespage = '';
	if(smilies_array[type].length > 2) {
		prevpage = getAdjacentSmileyPage(type, page, -1);
		nextpage = getAdjacentSmileyPage(type, page, 1);
		smiliespage = '<div class="z"><a href="javascript:;" onclick="smilies_switch(\'' + id + '\', \'' + smcols + '\', ' + type + ', ' + prevpage + ', \'' + seditorkey + '\');doane(event);">' + $L('prev_page_s') + '</a>' +
			'<a href="javascript:;" onclick="smilies_switch(\'' + id + '\', \'' + smcols + '\', ' + type + ', ' + nextpage + ', \'' + seditorkey + '\');doane(event);">' + $L('next_page_s') + '</a></div>' +
			page + '/' + (smilies_array[type].length - 1);
	}
	$(id + '_data').innerHTML = smiliesdata;
	$(id + '_page').innerHTML = smiliespage;
	$(id + '_tb').style.width = smcols * 36 + 'px';
}

function smilies_preview(seditorkey, id, obj, w) {
	var menu = $('smilies_preview');
	if(!menu) {
		menu = document.createElement('div');
		menu.id = 'smilies_preview';
		menu.className = 'sl_pv';
		menu.style.display = 'none';
		$('append_parent').appendChild(menu);
	}
	menu.innerHTML = '<img width="' + w + '" src="' + obj.childNodes[0].src + '" />';
	var mpos = fetchOffset($(id + '_data'));
	var spos = fetchOffset(obj);
	var pos = spos['left'] >= mpos['left'] + $(id + '_data').offsetWidth / 2 ? '13' : '24';
	showMenu({'ctrlid':obj.id,'showid':id + '_data','menuid':menu.id,'pos':pos,'layer':3});
}
