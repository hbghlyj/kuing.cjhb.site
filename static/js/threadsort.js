/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

var forum_optionlist_obj = forum_optionlist;

function changeselectthreadsort(selectchoiceoptionid, optionid, type) {
	if(selectchoiceoptionid == '0') {
		return;
	}
	var soptionid = 's' + optionid;
	var sselectchoiceoptionid = 's' + selectchoiceoptionid;

	forum_optionlist = forum_optionlist_obj['forum_optionlist'];
	var choicesarr = forum_optionlist[soptionid]['schoices'];
	var lastcount = 1;
	var name = issearch = id = nameid = '';
	if(type == 'search') {
		issearch = ', \'search\'';
		name = ' name="searchoption[' + optionid + '][value]"';
		id = 'id="' + forum_optionlist[soptionid]['sidentifier'] + '"';
	} else {
		name = ' name="typeoption[' + forum_optionlist[soptionid]['sidentifier'] + ']"';
		id = 'id="typeoption_' + forum_optionlist[soptionid]['sidentifier'] + '"';
	}
	if((choicesarr[sselectchoiceoptionid]['slevel'] == 1 || type == 'search') && choicesarr[sselectchoiceoptionid]['scount'] == 1) {
		nameid = name + ' ' + id;
	}
	var selectoption = '<select' + nameid + ' class="ps vm" onchange="changeselectthreadsort(this.value, \'' + optionid + '\'' + issearch + ');checkoption(\'' + forum_optionlist[soptionid]['sidentifier'] + '\', \'' + forum_optionlist[soptionid]['srequired'] + '\', \'' + forum_optionlist[soptionid]['stype'] + '\')" ' + ((forum_optionlist[soptionid]['sunchangeable'] == 1 && type == 'update') ? 'disabled' : '') + '><option value="0">' + $L('select') + '</option>';
	for(var i in choicesarr) {
		nameid = '';
		if((choicesarr[sselectchoiceoptionid]['slevel'] == 1 || type == 'search') && choicesarr[i]['scount'] == choicesarr[sselectchoiceoptionid]['scount']) {
				nameid = name + ' ' + id;
		}
		if(choicesarr[i]['sfoptionid'] != '0') {
			var patrn1 = new RegExp("^" + choicesarr[i]['sfoptionid'] + "\\.", 'i');
			var patrn2 = new RegExp("^" + choicesarr[i]['sfoptionid'] + "$", 'i');
			if(selectchoiceoptionid.match(patrn1) == null && selectchoiceoptionid.match(patrn2) == null) {
				continue;
			}
		}
		if(choicesarr[i]['scount'] != lastcount) {
			if(parseInt(choicesarr[i]['scount']) >= (parseInt(choicesarr[sselectchoiceoptionid]['scount']) + parseInt(choicesarr[sselectchoiceoptionid]['slevel']))) {
				break;
			}
			selectoption += '</select>' + "\r\n" + '<select' + nameid + ' class="ps vm" onchange="changeselectthreadsort(this.value, \'' + optionid + '\'' + issearch + ');checkoption(\'' + forum_optionlist[soptionid]['sidentifier'] + '\', \'' + forum_optionlist[soptionid]['srequired'] + '\', \'' + forum_optionlist[soptionid]['stype'] + '\')" ' + ((forum_optionlist[soptionid]['sunchangeable'] == 1 && type == 'update') ? 'disabled' : '') + '><option value="0">' + $L('select') + '</option>';

			lastcount = parseInt(choicesarr[i]['scount']);
		}
		var patrn1 = new RegExp("^" + choicesarr[i]['soptionid'] + "\\.", 'i');
		var patrn2 = new RegExp("^" + choicesarr[i]['soptionid'] + "$", 'i');
		var isnext = '';
		if(parseInt(choicesarr[i]['slevel']) != 1) {
			isnext = '&raquo;';
		}
		if(selectchoiceoptionid.match(patrn1) != null || selectchoiceoptionid.match(patrn2) != null) {
			selectoption += "\r\n" + '<option value="' + choicesarr[i]['soptionid'] + '" selected="selected">' + choicesarr[i]['scontent'] + isnext + '</option>';
		} else {
			selectoption += "\r\n" + '<option value="' + choicesarr[i]['soptionid'] + '">' + choicesarr[i]['scontent'] + isnext + '</option>';
		}
	}
	selectoption += '</select>';
	if(type == 'search') {
		selectoption  += "\r\n" + '<input type="hidden" name="searchoption[' + optionid + '][type]" value="select">';
	}
	$('select_' + forum_optionlist[soptionid]['sidentifier']).innerHTML = selectoption;
}

function checkoption(identifier, required, checktype, checkmaxnum, checkminnum, checkmaxlength) {
	if(checktype != 'image' && checktype != 'select' && !$('typeoption_' + identifier) || !$('check' + identifier)) {
		return true;
	}

	var ce = $('check' + identifier);
	ce.innerHTML = '';

	if(checktype == 'select') {
		if(required != '0' && ($('typeoption_' + identifier) == null || $('typeoption_' + identifier).value == '0')) {
			warning(ce, $L('miss_required'));
			return false;
		} else if(required == '0' && ($('typeoption_' + identifier) == null || $('typeoption_' + identifier).value == '0')) {
			ce.innerHTML = '<i class="fico-error fic4 fc-l vm"></i> ' + $L('select_next_level');
			ce.className = "warning";
			return true;
		}
	}

	if(checktype == 'radio' || checktype == 'checkbox') {
		var nodes = $('typeoption_' + identifier).parentNode.parentNode.parentNode.getElementsByTagName('INPUT');
		var nodechecked = false;
		for(var i=0; i<nodes.length; i++) {
			if(nodes[i].id == 'typeoption_' + identifier) {
				if(nodes[i].checked) {
					nodechecked = true;
				}
			}
		}
		if(!nodechecked && required != '0') {
			warning(ce, $L('miss_required'));
			return false;
		}
	}

	if(checktype == 'image') {
		var checkvalue = $('sortaid_' + identifier).value;
	} else {
		var checkvalue = $('typeoption_' + identifier).value.trim();
	}

	if(required != '0') {
		if(checkvalue == '') {
			warning(ce, $L('miss_required'));
			return false;
		} else {
			ce.innerHTML = '<i class="fico-check_right fic4 fc-v vm"></i>';
		}
	}

	if(checkvalue) {
		if(checktype == 'email' && !(/^[\-\.\w]+@[\.\-\w]+(\.\w+)+$/.test(checkvalue))) {
			warning(ce, $L('email_error'));
			return false;
		} else if((checktype == 'text' || checktype == 'textarea') && checkmaxlength != '0' && mb_strlen(checkvalue) > checkmaxlength) {
			warning(ce, $L('max_length'));
			return false;
		} else if((checktype == 'number' || checktype == 'range')) {
			if(isNaN(checkvalue)) {
				warning(ce, $L('digital_error'));
				return false;
			} else if(checkmaxnum != '0' && parseInt(checkvalue) > parseInt(checkmaxnum)) {
				warning(ce, $L('max_error'));
				return false;
			} else if(checkminnum != '0' && parseInt(checkvalue) < parseInt(checkminnum)) {
				warning(ce, $L('min_error'));
				return false;
			}
		} else if(checktype == 'url' && !(/(http[s]?|ftp):\/\/[^\/\.]+?\..+\w[\/]?$/i.test(checkvalue))) {
			warning(ce, $L('http_url_error'));
			return false;
		}
		ce.innerHTML = '<i class="fico-check_right fic4 fc-v vm"></i>';
	}
	return true;
}
