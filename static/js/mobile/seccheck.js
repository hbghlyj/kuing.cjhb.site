var seccheck_tpl = window.seccheck_tpl || [];
var seccheck_modid = window.seccheck_modid || [];

function updatesecqaa(idhash, tpl) {
	if(!document.getElementById('secqaa_' + idhash)) {
		return;
	}
	if(tpl) {
		seccheck_tpl[idhash] = tpl;
	}
	var id = 'seqaajs_' + idhash;
	var src = 'misc.php?mod=secqaa&action=update&idhash=' + idhash + '&' + Math.random();
	if(document.getElementById(id)) {
		document.getElementsByTagName('head')[0].appendChild(document.getElementById(id));
	}
	var scriptNode = document.createElement('script');
	scriptNode.type = 'text/javascript';
	scriptNode.id = id;
	scriptNode.src = src;
	document.getElementsByTagName('head')[0].appendChild(scriptNode);
}

function updateseccode(idhash, tpl, modid) {
	if(!document.getElementById('seccode_' + idhash)) {
		return;
	}
	if(tpl) {
		seccheck_tpl[idhash] = tpl;
	}
	if(modid) {
		seccheck_modid[idhash] = modid;
	} else {
		modid = seccheck_modid[idhash];
	}
	var id = 'seccodejs_' + idhash;
	var src = 'misc.php?mod=seccode&action=update&idhash=' + idhash + '&' + Math.random() + '&modid=' + modid;
	if(document.getElementById(id)) {
		document.getElementsByTagName('head')[0].appendChild(document.getElementById(id));
	}
	var scriptNode = document.createElement('script');
	scriptNode.type = 'text/javascript';
	scriptNode.id = id;
	scriptNode.src = src;
	document.getElementsByTagName('head')[0].appendChild(scriptNode);
}
