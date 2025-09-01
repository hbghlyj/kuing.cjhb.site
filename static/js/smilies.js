/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: smilies.js 29684 2012-04-25 04:00:58Z zhangguosheng $
*/

function _smilies_show(id, smcols, seditorkey) {
	if(seditorkey && !$(seditorkey + 'sml_menu')) {
		var div = document.createElement("div");
		div.id = seditorkey + 'sml_menu';
		div.style.display = 'none';
		div.className = 'sllt';
		$('append_parent').appendChild(div);
		var div = document.createElement("div");
		div.id = id;
		div.style.overflow = 'hidden';
		$(seditorkey + 'sml_menu').appendChild(div);
	}
	if(typeof smilies_type == 'undefined') {
		var scriptNode = document.createElement("script");
		scriptNode.type = "text/javascript";
		scriptNode.charset = charset ? charset : (BROWSER.firefox ? document.characterSet : document.charset);
		scriptNode.src = 'data/cache/common_smilies_var.js?' + VERHASH;
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
	if(typeof smilies_type == 'object') {
		$(id).innerHTML = '<div id="' + id + '_data"></div><div class="sllt_p" id="' + id + '_page"></div>';
		smilies_switch(id, smcols, CURRENTSTYPE, 0, seditorkey);
		smilies_fastdata = '';
		if(seditorkey == 'fastpost' && $('fastsmilies') && smilies_fast) {
			var j = 0;
			for(i = 0;i < smilies_fast.length; i++) {
				if(j == 0) {
					smilies_fastdata += '<tr>';
				}
				j = ++j > 3 ? 0 : j;
				s = smilies_array[smilies_fast[i][0]][smilies_fast[i][1]][smilies_fast[i][2]];
				smilieimg = STATICURL + 'image/smiley/' + s[1];
				img[k] = new Image();
				img[k].src = smilieimg;
				smilies_fastdata += s ? '<td onmouseover="smilies_preview(\'' + seditorkey + '\', \'fastsmiliesdiv\', this, ' + s[5] + ')" onmouseout="$(\'smilies_preview\').style.display = \'none\'" onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + i + ')': 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[0].replace(/'/, '\\\'') + '\')') +
					'" id="' + seditorkey + 'smilie_' + i + '_td"><img id="smilie_' + i + '" width="' + s[2] +'" height="' + s[4] +'" src="' + smilieimg + '" alt="' + s[0] + '" />' : '<td>';
			}
			$('fastsmilies').innerHTML = '<table cellspacing="0" cellpadding="0"><tr>' + smilies_fastdata + '</tr></table>';
		}
	}
}

function smilies_switch(id, smcols, type, page = 0, seditorkey) {
	smiliesdata = '<table id="' + id + '_table" cellpadding="0" cellspacing="0"><tr>';
	j = k = 0;
	img = [];
	for(var i = page * 40; i < smilies_array.length && i < (page + 1) * 40;j++,k++) {
		i++;
		if(j >= smcols) {
			smiliesdata += '<tr>';
			j = 0;
		}
		s = smilies_array[i];
		if(!s) continue;
		smilieimg = STATICURL + 'image/smiley/' + s[1];
		img[k] = new Image();
		img[k].src = smilieimg;
		smiliesdata += s && i ? '<td onmouseover="smilies_preview(\'' + seditorkey + '\', \'' + id + '\', this, ' + s[5] + ')" onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + i + ')': 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[0].replace(/'/, '\\\'') + '\')') +
			'" id="' + seditorkey + 'smilie_' + i + '_td"><img id="smilie_' + i + '" width="' + s[2] +'" height="' + s[4] +'" src="' + smilieimg + '" alt="' + s[0] + '" />' : '<td>';
	}
	smiliesdata += '</table>';
	smiliespage = '';
	if(smilies_array.length > 40) {
		prevpage = page - 1;
		nextpage = page + 1;
               smiliespage = '<div class="z">';
			   if (prevpage >=0) smiliespage += '<a href="javascript:;" onclick="smilies_switch(\'' + id + '\', \'' + smcols + '\', ' + type + ', ' + prevpage + ', \'' + seditorkey + '\');doane(event);">'+lng['page_prev']+'</a>';
			   if (nextpage < Math.ceil(smilies_array.length / 40)) smiliespage += '<a href="javascript:;" onclick="smilies_switch(\'' + id + '\', \'' + smcols + '\', ' + type + ', ' + nextpage + ', \'' + seditorkey + '\');doane(event);">'+lng['page_next']+'</a>';
			   smiliespage += '</div>';
			page + '/' + Math.ceil(smilies_array.length / 40);
	}
	$(id + '_data').innerHTML = smiliesdata;
	$(id + '_page').innerHTML = smiliespage;
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
	mpos = fetchOffset($(id + '_data'));
	spos = fetchOffset(obj);
	pos = spos['left'] >= mpos['left'] + $(id + '_data').offsetWidth / 2 ? '13' : '24';
	showMenu({'ctrlid':obj.id,'showid':id + '_data','menuid':menu.id,'pos':pos,'layer':3});
}
