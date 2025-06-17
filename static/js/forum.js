/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forum.js 33824 2013-08-19 08:26:11Z nemohou $
*/
{
const ESCAPE_CHAR = '\u000E'; // Shift Out (SO)
const ESCAPE_SEQ_FOR_ESCAPE_CHAR = ESCAPE_CHAR + '\u0000'; // SO + NUL
const ESCAPE_SEQ_FOR_DOUBLE_TAB = ESCAPE_CHAR + '\u0002';  // SO + STX
const ESCAPE_SEQ_FOR_SINGLE_TAB = ESCAPE_CHAR + '\u0001';  // SO + SOH

const SINGLE_TAB = '\t';
const DOUBLE_TAB = '\t\t';

/**
 * Escapes special characters in a string to prevent conflicts with delimiters.
 * Order of replacement is important:
 * 1. Escape the escape character itself.
 * 2. Escape the longest delimiter sequence.
 * 3. Escape the shorter delimiter sequence.
 */
function escapeDataString(str) {
    if (typeof str !== 'string') return str;
    let escapedStr = str;
    // Escape the escape character itself first
    escapedStr = escapedStr.replace(new RegExp(ESCAPE_CHAR.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'g'), ESCAPE_SEQ_FOR_ESCAPE_CHAR);
    // Escape double tabs next
    escapedStr = escapedStr.replace(new RegExp(DOUBLE_TAB.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'g'), ESCAPE_SEQ_FOR_DOUBLE_TAB);
    // Escape single tabs last
    escapedStr = escapedStr.replace(new RegExp(SINGLE_TAB.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'g'), ESCAPE_SEQ_FOR_SINGLE_TAB);
    return escapedStr;
}

/**
 * Unescapes special characters in a string retrieved from storage.
 * Order of replacement is important (reverse of escaping specific sequences):
 * 1. Unescape single tab sequences.
 * 2. Unescape double tab sequences.
 * 3. Unescape the escape character itself.
 */
function unescapeDataString(str) {
    if (typeof str !== 'string') return str;
    let unescapedStr = str;
    // Unescape single tab sequences
    unescapedStr = unescapedStr.replace(new RegExp(ESCAPE_SEQ_FOR_SINGLE_TAB.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'g'), SINGLE_TAB);
    // Unescape double tab sequences
    unescapedStr = unescapedStr.replace(new RegExp(ESCAPE_SEQ_FOR_DOUBLE_TAB.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'g'), DOUBLE_TAB);
    // Unescape the escape character itself
    unescapedStr = unescapedStr.replace(new RegExp(ESCAPE_SEQ_FOR_ESCAPE_CHAR.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'g'), ESCAPE_CHAR);
    return unescapedStr;
}
function saveData(ignoreempty) {
	var ignoreempty = isUndefined(ignoreempty) ? 0 : ignoreempty;
	var obj = $('postform') && (($('fwin_newthread') && $('fwin_newthread').style.display == '') || ($('fwin_reply') && $('fwin_reply').style.display == '') || ($('fwin_edit') && $('fwin_edit').style.display == '')) ? $('postform') : ($('fastpostform') ? $('fastpostform') : $('postform'));
	if(!obj) return;
	var bbcode = (typeof wysiwyg != 'undefined' && wysiwyg == 1) ? html2bbcode(editdoc.body.innerHTML) : obj.message.value;
	if(typeof isfirstpost != 'undefined') {
		if(typeof wysiwyg != 'undefined' && wysiwyg == 1) {
			var messageisnull = trim(bbcode) === '';
		} else {
			var messageisnull = bbcode === '';
		}
		if(isfirstpost && (messageisnull && $('postform').subject.value === '')) {
			return;
		}
		if(!isfirstpost && messageisnull) {
			return;
		}
	}
	var data = subject = message = '';
	for(var i = 0; i < obj.elements.length; i++) {
		var el = obj.elements[i];
		if(el.name != '' && (el.tagName == 'SELECT' || el.tagName == 'TEXTAREA' || el.tagName == 'INPUT' && (el.type == 'text' || el.type == 'checkbox' || el.type == 'radio' || el.type == 'hidden' || el.type == 'select')) && el.name.substr(0, 6) != 'attach') {
			var elvalue = el.value; // This is the raw value from the form element
			if(el.name == 'subject') {
				subject = trim(elvalue);
			} else if(el.name == 'message') {
				if(typeof wysiwyg != 'undefined' && wysiwyg == 1) {
					elvalue = bbcode; // Use bbcode if wysiwyg editor
				}
				message = trim(elvalue);
			}
			// Note: 'elvalue' at this point is the actual string content for the field.
			// It might have been updated (e.g., if it was 'message' and wysiwyg was on).

			if((el.type == 'checkbox' || el.type == 'radio') && !el.checked) {
				continue;
			} else if(el.tagName == 'SELECT') {
				// elvalue is already el.value
			} else if(el.type == 'hidden') {
				if(el.id) {
					eval('var check = typeof ' + el.id + '_upload == \'function\'');
					if(check) {
						// elvalue is el.value (which is aid)
						if($(el.id + '_url')) {
							// Append URL, separated by char(1)
							elvalue += String.fromCharCode(1) + $(el.id + '_url').value;
						}
					} else {
						continue;
					}
				} else {
					continue;
				}
			}

			// Before concatenating, escape the elvalue
			var value_to_check_for_emptiness = elvalue; // Use original value for emptiness check logic
			var value_to_serialize = escapeDataString(elvalue); // Escape the value for storage

			if(trim(value_to_check_for_emptiness)) { // Check original value's emptiness
				data += el.name + String.fromCharCode(9) + el.tagName + String.fromCharCode(9) + el.type + String.fromCharCode(9) + value_to_serialize + String.fromCharCode(9, 9);
			}
		}
	}

	if(!subject && !message && !ignoreempty) { // This logic uses 'subject' and 'message' which are trimmed versions. It's likely fine.
		return;
	}

	saveUserdata('forum_'+discuz_uid, data);
}
function loadData(quiet, formobj) {

	var evalevent = function (obj) {
		var script = obj.parentNode.innerHTML;
		var re = /onclick="(.+?)["|>]/ig;
		var matches = re.exec(script);
		if(matches != null) {
			matches[1] = matches[1].replace(/this\./ig, 'obj.');
			eval(matches[1]);
		}
	};

	var data = '';
	data = loadUserdata('forum_'+discuz_uid);
	var formobj = !formobj ? $('postform') : formobj;

	if(in_array((data = trim(data)), ['', 'null', 'false', null, false])) {
                if(!quiet) {
                        showDialog(lng['no_data_recover'], 'notice');
                }
		return;
	}

        if(!quiet && !confirm(lng['content_overwrite'])) {
                return;
        }

	var records = data.split(/\x09\x09/); // Changed variable name for clarity
	for(var i = 0; i < formobj.elements.length; i++) {
		var el = formobj.elements[i];
		if(el.name != '' && (el.tagName == 'SELECT' || el.tagName == 'TEXTAREA' || el.tagName == 'INPUT' && (el.type == 'text' || el.type == 'checkbox' || el.type == 'radio' || el.type == 'hidden'))) {
			for(var j = 0; j < records.length; j++) { // Iterate over records
				if (records[j] === '') continue; // Skip empty strings that might result from trailing delimiters
				var ele = records[j].split(/\x09/); // name, tagName, type, value
				if(ele[0] == el.name) {
					var escaped_elvalue = !isUndefined(ele[3]) ? ele[3] : '';
					var elvalue = unescapeDataString(escaped_elvalue); // Unescape the value

					// Now proceed with the original logic using the unescaped elvalue
					if(ele[1] == 'INPUT') {
						if(ele[2] == 'text') {
							el.value = elvalue;
						} else if((ele[2] == 'checkbox' || ele[2] == 'radio') && elvalue == el.value) { // Compare unescaped value
							el.checked = true;
							evalevent(el);
						} else if(ele[2] == 'hidden') {
							eval('var check = typeof ' + el.id + '_upload == \'function\'');
							if(check) {
								var v = elvalue.split(/\x01/); // Original separator for hidden field data
								el.value = v[0];
								if(el.value) {
									if($(el.id + '_url') && v[1]) {
										$(el.id + '_url').value = v[1];
									}
									// Ensure eval arguments are properly quoted if they can contain special chars
									eval(el.id + '_upload(\'' + (v[0] ? v[0].replace(/'/g, "\\'") : '') + '\', \'' + (v[1] ? v[1].replace(/'/g, "\\'") : '') + '\')');
									if($('unused' + v[0])) {
										var attachtype = $('unused' + v[0]).parentNode.parentNode.parentNode.parentNode.id.substr(11);
										$('unused' + v[0]).parentNode.parentNode.outerHTML = '';
										$('unusednum_' + attachtype).innerHTML = parseInt($('unusednum_' + attachtype).innerHTML) - 1;
										if($('unusednum_' + attachtype).innerHTML == 0 && $('attachnotice_' + attachtype)) {
											$('attachnotice_' + attachtype).style.display = 'none';
										}
									}
								}
							}

						}
					} else if(ele[1] == 'TEXTAREA') {
						if(ele[0] == 'message') { // Check original name
							if(!wysiwyg) {
								// Assuming 'textobj' is 'el' or similar for message textarea
								el.value = elvalue;
							} else {
								editdoc.body.innerHTML = bbcode2html(elvalue);
							}
						} else {
							el.value = elvalue;
						}
					} else if(ele[1] == 'SELECT') {
						if($(el.id + '_ctrl_menu')) {
							var lis = $(el.id + '_ctrl_menu').getElementsByTagName('li');
							for(var k = 0; k < lis.length; k++) {
								if(elvalue == lis[k].k_value) { // Compare unescaped value
									lis[k].onclick();
									break;
								}
							}
						} else {
							for(var k = 0; k < el.options.length; k++) {
								if(elvalue == el.options[k].value) { // Compare unescaped value
									el.options[k].selected = true;
									break;
								}
							}
						}
					}
					break;
				}
			}
		}
	}
	if($('rstnotice')) {
		$('rstnotice').style.display = 'none';
	}
	extraCheckall();
}
}

function fastUload() {
	appendscript(JSPATH + 'forum_post.js?' + VERHASH);
	safescript('forum_post_js', function () { uploadWindow(function (aid, url) {updatefastpostattach(aid, url)}, 'file') }, 100, 50);
}

function switchAdvanceMode(url) {
	var obj = $('postform') && (($('fwin_newthread') && $('fwin_newthread').style.display == '') || ($('fwin_reply') && $('fwin_reply').style.display == '') || ($('fwin_edit') && $('fwin_edit').style.display == '')) ? $('postform') : ($('fastpostform') ? $('fastpostform') : $('postform'));
	if(obj && obj.message.value != '') {
		saveData();
		url += (url.indexOf('?') != -1 ? '&' : '?') + 'cedit=yes';
	}
	location.href = url;
	return false;
}

function sidebar_collapse(lang) {
	if(lang[0]) {
		toggle_collapse('sidebar', null, null, lang);
		$('wrap').className = $('wrap').className == 'wrap with_side s_clear' ? 'wrap s_clear' : 'wrap with_side s_clear';
	} else {
		var collapsed = getcookie('collapse');
		collapsed = updatestring(collapsed, 'sidebar', 1);
		setcookie('collapse', collapsed, (collapsed ? 2592000 : -2592000));
		location.reload();
	}
}

function keyPageScroll(e, prev, next, url, page) {
	if(loadUserdata('is_blindman')) {
		return true;
	}
	e = e ? e : window.event;
	var tagname = BROWSER.ie ? e.srcElement.tagName : e.target.tagName;
	if(tagname == 'INPUT' || tagname == 'TEXTAREA') return;
	actualCode = e.keyCode ? e.keyCode : e.charCode;
	if(next && actualCode == 39 && !(e.shiftKey) && !(e.altKey)) {
		window.location = url + '&page=' + (page + 1);
	}
	if(prev && actualCode == 37 && !(e.shiftKey) && !(e.altKey)) {
		window.location = url + '&page=' + (page - 1);
	}
}

function announcement() {
    var ann = new Object();
    ann.anndelay = 3000;
    ann.annst = 0;
    ann.annstop = 0;
    ann.annrowcount = 0;
    ann.anncount = 0;
    ann.annScrollTopBegin = 0;
    ann.annlis = $('anc').getElementsByTagName("li");
    ann.annrows = new Array();
    ann.announcementScroll = function() {
        if (this.annstop) {
            this.annst = setTimeout(function() {
                ann.announcementScroll();
            }, this.anndelay);
            return;
        }
        if (!this.annst) {
            var lasttop = -1;
            for (i = 0; i < this.annlis.length; i++) {
                if (lasttop != this.annlis[i].offsetTop) {
                    this.annrows[this.annrowcount] = this.annlis[i].offsetTop - this.annlis[0].offsetTop;
                    this.annrowcount++;
                }
                lasttop = this.annlis[i].offsetTop;
            }
            if (this.annrows.length == 1) {
                $('an').onmouseover = $('an').onmouseout = null;
            } else {
                $('ancl').innerHTML += $('ancl').innerHTML;
                this.annst = setTimeout(function() {
                    ann.announcementScroll();
                }, this.anndelay);
                $('an').onmouseover = function() {
                    ann.annstop = 1;
                };
                $('an').onmouseout = function() {
                    ann.annstop = 0;
                };
            }
            this.annrowcount = 1;
            return;
        }
        if (this.annrowcount >= this.annrows.length) {
            $('anc').scrollTop = 0;
            this.annrowcount = 1;
            this.annst = setTimeout(function() {
                ann.announcementScroll();
            }, this.anndelay);
        } else {
            this.anncount = 0;
            this.annScrollTopBegin = $('anc').scrollTop;
            this.announcementScrollnext(this.annrows[this.annrowcount]);
        }
    }
    ;
    ann.announcementScrollnext = function(targetTop) {
        $('anc').scrollTop = this.annScrollTopBegin + this.anncount;
        this.anncount++;
        if ($('anc').scrollTop < targetTop) {
            this.annst = setTimeout(function() {
                ann.announcementScrollnext(targetTop);
            }, 10);
        } else {
            this.annrowcount++;
            this.annst = setTimeout(function() {
                ann.announcementScroll();
            }, this.anndelay);
        }
    }
    ;
    ann.announcementScroll();
}

function removeindexheats() {
/*vot*/ return confirm(lng['del_thread_sure']);
}

function showTypes(id, mod) {
	var o = $(id);
	if(!o) return false;
	var s = o.className;
	mod = isUndefined(mod) ? 1 : mod;
	var baseh = o.getElementsByTagName('li')[0].offsetHeight * 2;
        var tmph = o.offsetHeight;
/*vot*/ var lang = [lng['expand'], lng['collapse']];
        var cls = ['unfold', 'fold'];
	if(tmph > baseh) {
		var octrl = document.createElement('li');
		octrl.className = cls[mod];
		octrl.innerHTML = lang[mod];

		o.insertBefore(octrl, o.firstChild);
		o.className = s + ' cttp';
		mod && (o.style.height = 'auto');

		octrl.onclick = function () {
			if(this.className == cls[0]) {
				o.style.height = 'auto';
				this.className = cls[1];
				this.innerHTML = lang[1];
			} else {
				o.style.height = '';
				this.className = cls[0];
				this.innerHTML = lang[0];
			}
		}
	}
}

var postpt = 0;
function fastpostvalidate(theform, noajaxpost) {
	if(postpt) {
		return false;
	}
	postpt = 1;
	setTimeout(function() {postpt = 0}, 2000);
	noajaxpost = !noajaxpost ? 0 : noajaxpost;
	s = '';
	if(typeof fastpostvalidateextra == 'function') {
		var v = fastpostvalidateextra();
		if(!v) {
			return false;
		}
	}
        if(theform.message.value == '' || theform.subject.value == '') {
/*vot*/         s = lng['enter_content'];
                theform.message.focus();
        } else if(dstrlen(theform.subject.value) > 255) {
/*vot*/         s = lng['title_long'];
                theform.subject.focus();
        }
        if(!disablepostctrl && dstrlen(trim(theform.subject.value)) && ((postminsubjectchars != 0 && dstrlen(theform.subject.value) < postminsubjectchars) || (postminsubjectchars != 0 && dstrlen(theform.subject.value) > postmaxsubjectchars))) {
/*vot*/         showError(lng['thread_title_length_invalid'] + '\n\n' + lng['current_length'] + ': ' + dstrlen(theform.subject.value) + lng['characters'] + '\n' + lng['system_limit'] + ': ' + postminsubjectchars + lng['up_to'] + postmaxsubjectchars + lng['characters']);
                return false;
        }
        if(!disablepostctrl && ((postminchars != 0 && mb_strlen(theform.message.value) < postminchars) || (postmaxchars != 0 && mb_strlen(theform.message.value) > postmaxchars))) {
/*vot*/         s = lng['content_long'] + lng['current_length'] + ': ' + mb_strlen(theform.message.value) + ' ' + lng['bytes']+'\n'+lng['system_limit']+': ' + postminchars + lng['up_to'] + postmaxchars + ' ' + lng['bytes'];
        }
	if(s) {
		showError(s);
		doane();
		$('fastpostsubmit').disabled = false;
		return false;
	}
	$('fastpostsubmit').disabled = true;
	theform.message.value = theform.message.value.replace(/([^>=\]"'\/]|^)((((https?|ftp):\/\/)|www\.)([\w\-]+\.)*[\w\-\u4e00-\u9fa5]+\.([\.a-zA-Z0-9]+|\u4E2D\u56FD|\u7F51\u7EDC|\u516C\u53F8)((\?|\/|:)+[\w\.\/=\?%\-&~`@':+!]*)+\.(jpg|gif|png|bmp))/ig, '$1[img]$2[/img]');
	theform.message.value = parseurl(theform.message.value);
	if(!noajaxpost) {
		ajaxpost('fastpostform', 'fastpostreturn', 'fastpostreturn', 'onerror', $('fastpostsubmit'));
		return false;
	} else {
		return true;
	}
}

function checkpostrule(showid, extra) {
	var x = new Ajax();
	x.get('forum.php?mod=ajax&action=checkpostrule&inajax=yes&' + extra, function(s) {
		ajaxinnerhtml($(showid), s);evalscript(s);
	});
}

function updatefastpostattach(aid, url) {
	ajaxget('forum.php?mod=ajax&action=attachlist&posttime=' + $('posttime').value + (!fid ? '' : '&fid=' + fid), 'attachlist');
	$('attach_tblheader').style.display = '';
}

function succeedhandle_fastnewpost(locationhref, message, param) {
	location.href = locationhref;
}

function errorhandle_fastnewpost() {
	$('fastpostsubmit').disabled = false;
}

function atarget(obj) {
	obj.target = getcookie('atarget') > 0 ? '_blank' : '';
}

function setatarget(v) {
	$('atarget').className = 'y atarget_' + v;
	$('atarget').onclick = function() {setatarget(v == 1 ? -1 : 1);};
	setcookie('atarget', v, 2592000);
}

var checkForumcount = 0, checkForumtimeout = 30000, checkForumnew_handle;
function checkForumnew(fid, lasttime) {
	var timeout = checkForumtimeout;
	var x = new Ajax();
	x.get('forum.php?mod=ajax&action=forumchecknew&fid=' + fid + '&time=' + lasttime + '&inajax=yes', function(s){
		if(s > 0) {
			var table = $('separatorline').parentNode;
			if(!isUndefined(checkForumnew_handle)) {
				clearTimeout(checkForumnew_handle);
			}
			removetbodyrow(table, 'forumnewshow');
			var colspan = table.getElementsByTagName('tbody')[0].rows[0].children.length;
/*vot*/                 var checknew = {'tid':'', 'thread':{'common':{'className':'', 'val':'<a href="javascript:void(0);" onclick="ajaxget(\'forum.php?mod=ajax&action=forumchecknew&fid=' + fid+ '&time='+lasttime+'&uncheck=1&inajax=yes\', \'forumnew\');">'+lng['new_reply_exists'], 'colspan': colspan }}};
			addtbodyrow(table, ['tbody'], ['forumnewshow'], 'separatorline', checknew);
		} else {
			if(checkForumcount < 50) {
				if(checkForumcount > 0) {
					var multiple =  Math.ceil(50 / checkForumcount);
					if(multiple < 5) {
						timeout = checkForumtimeout * (5 - multiple + 1);
					}
				}
				checkForumnew_handle = setTimeout(function () {checkForumnew(fid, lasttime);}, timeout);
			}
		}
		checkForumcount++;
	});

}
function checkForumnew_btn(fid) {
	if(isUndefined(fid)) return;
	ajaxget('forum.php?mod=ajax&action=forumchecknew&fid=' + fid+ '&time='+lasttime+'&uncheck=2&inajax=yes', 'forumnew', 'ajaxwaitid');
	lasttime = parseInt(Date.parse(new Date()) / 1000);
}

function display_blocked_thread() {
	var table = $('threadlisttableid');
	if(!table) {
		return;
	}
	var tbodys = table.getElementsByTagName('tbody');
	for(i = 0;i < tbodys.length;i++) {
		var tbody = tbodys[i];
		if(tbody.style.display == 'none') {
			table.appendChild(tbody);
			tbody.style.display = '';
		}
	}
	$('hiddenthread').style.display = 'none';
}

function addtbodyrow(table, insertID, changename, separatorid, jsonval) {
	if(isUndefined(table) || isUndefined(insertID[0])) {
		return;
	}

	var insertobj = document.createElement(insertID[0]);
	var thread = jsonval.thread;
	var tid = !isUndefined(jsonval.tid) ? jsonval.tid : '' ;

	if(!isUndefined(changename[1])) {
		removetbodyrow(table, changename[1] + tid);
	}

	insertobj.id = changename[0] + tid;
	if(!isUndefined(insertID[1])) {
		insertobj.className = insertID[1];
	}
	if($(separatorid)) {
		table.insertBefore(insertobj, $(separatorid).nextSibling);
	} else {
		table.insertBefore(insertobj, table.firstChild);
	}
	var newTH = insertobj.insertRow(-1);
	for(var value in thread) {
		if(value != 0) {
			var cell = newTH.insertCell(-1);
			if(isUndefined(thread[value]['val'])) {
				cell.innerHTML = thread[value];
			} else {
				cell.innerHTML = thread[value]['val'];
			}
			if(!isUndefined(thread[value]['className'])) {
				cell.className = thread[value]['className'];
			}
			if(!isUndefined(thread[value]['colspan'])) {
				cell.colSpan = thread[value]['colspan'];
			}
		}
	}

	if(!isUndefined(insertID[2])) {
		_attachEvent(insertobj, insertID[2], function() {insertobj.className = '';});
	}
}
function removetbodyrow(from, objid) {
	if(!isUndefined(from) && $(objid)) {
		from.removeChild($(objid));
	}
}

function leftside(id) {
	$(id).className = $(id).className == 'a' ? '' : 'a';
	if(id == 'lf_fav') {
		setcookie('leftsidefav', $(id).className == 'a' ? 0 : 1, 2592000);
	}
}
var DTimers = new Array();
var DItemIDs = new Array();
var DTimers_exists = false;
function settimer(timer, itemid) {
	if(timer && itemid) {
		DTimers.push(timer);
		DItemIDs.push(itemid);
	}
	if(!DTimers_exists) {
		setTimeout("showtime()", 1000);
		DTimers_exists = true;
	}
}
function showtime() {
	for(i=0; i<=DTimers.length; i++) {
		if(DItemIDs[i]) {
                        if(DTimers[i] == 0) {
/*vot*/                         $(DItemIDs[i]).innerHTML = lng['finished'];
                                DItemIDs[i] = '';
                                continue;
                        }
			var timestr = '';
			var timer_day = Math.floor(DTimers[i] / 86400);
			var timer_hour = Math.floor((DTimers[i] % 86400) / 3600);
			var timer_minute = Math.floor(((DTimers[i] % 86400) % 3600) / 60);
			var timer_second = (((DTimers[i] % 86400) % 3600) % 60);
                        if(timer_day > 0) {
/*vot*/                         timestr += timer_day + lng['days_num'];
                        }
                        if(timer_hour > 0) {
/*vot*/                         timestr += timer_hour + lng['hours_num'];
                        }
                        if(timer_minute > 0) {
/*vot*/                         timestr += timer_minute + lng['minutes_num'];
                        }
                        if(timer_second > 0) {
/*vot*/                         timestr += timer_second + lng['seconds'];
                        }
			DTimers[i] = DTimers[i] - 1;
			$(DItemIDs[i]).innerHTML = timestr;
		}
	}
	setTimeout("showtime()", 1000);
}
function fixed_top_nv(eleid, disbind) {
	this.nv = eleid && $(eleid) || $('nv');
	this.openflag = this.nv && BROWSER.ie != 6;
	this.nvdata = {};
	this.init = function (disattachevent) {
		if(this.openflag) {
			if(!disattachevent) {
				var obj = this;
				_attachEvent(window, 'resize', function(){obj.reset();obj.init(1);obj.run();});
				var switchwidth = $('switchwidth');
				if(switchwidth) {
					_attachEvent(switchwidth, 'click', function(){obj.reset();obj.openflag=false;});
				}
			}

			var next = this.nv;
			try {
				if(this.nv.parentNode.id.substr(-3) != '_ph') {
					var nvparent = document.createElement('div');
					nvparent.id = this.nv.id + '_ph';
					this.nv.parentNode.insertBefore(nvparent,this.nv);
					nvparent.appendChild(this.nv);
				}
				this.nvdata.next = this.nv.parentNode;
				this.nvdata.height = parseInt(this.nv.offsetHeight, 10);
				this.nvdata.width = parseInt(this.nv.offsetWidth, 10);
				this.nvdata.left = this.nv.getBoundingClientRect().left - document.documentElement.clientLeft;
				this.nvdata.position = this.nv.style.position;
				this.nvdata.opacity = this.nv.style.opacity;
			} catch (e) {
				this.nvdata.next = null;
			}
		}
	};

	this.run = function () {
		var fixedheight = 0;
		if(this.openflag && this.nvdata.next){
			var nvnexttop = document.body.scrollTop || document.documentElement.scrollTop;
			var dofixed = nvnexttop !== 0 && document.documentElement.clientHeight >= 15 && this.nvdata.next.getBoundingClientRect().top < 0;
			if(dofixed) {
				if(this.nv.style.position != 'fixed') {
					this.nv.style.borderLeftWidth = '0';
					this.nv.style.borderRightWidth = '0';
					this.nv.style.height = this.nvdata.height + 'px';
					this.nv.style.width = this.nvdata.width + 'px';
					this.nv.style.top = '0';
					this.nv.style.left = this.nvdata.left + 'px';
					this.nv.style.position = 'fixed';
					this.nv.style.zIndex = '199';
					this.nv.style.opacity = 0.85;
					this.nv.parentNode.style.height = this.nvdata.height + 'px';
				}
			} else {
				if(this.nv.style.position != this.nvdata.position) {
					this.reset();
				}
			}
			if(this.nv.style.position == 'fixed') {
				fixedheight = this.nvdata.height;
			}
		}
		return fixedheight;
	};
	this.reset = function () {
		if(this.nv) {
			this.nv.style.position = this.nvdata.position;
			this.nv.style.borderLeftWidth = '';
			this.nv.style.borderRightWidth = '';
			this.nv.style.height = '';
			this.nv.style.width = '';
			this.nv.style.opacity = this.nvdata.opacity;
			this.nv.parentNode.style.height = '';
		}
	};
	if(!disbind && this.openflag) {
		this.init();
		_attachEvent(window, 'scroll', this.run);
	}
}
var previewTbody = null, previewTid = null, previewDiv = null;
function previewThread(tid, tbody) {
	if(!$('threadPreviewTR_'+tid)) {
		appendscript(JSPATH + 'forum_viewthread.js?' + VERHASH);

		newTr = document.createElement('tr');
		newTr.id = 'threadPreviewTR_'+tid;
		newTr.className = 'threadpre';
		$(tbody).appendChild(newTr);
		newTd = document.createElement('td');
		newTd.colSpan = listcolspan;
		newTd.className = 'threadpretd';
		newTr.appendChild(newTd);
		newTr.style.display = 'none';

		previewTbody = tbody;
		previewTid = tid;

		if(BROWSER.ie) {
			previewDiv = document.createElement('div');
			previewDiv.id = 'threadPreview_'+tid;
			previewDiv.style.id = 'none';
			var x = Ajax();
			x.get('forum.php?mod=viewthread&tid='+tid+'&inajax=1&from=preview', function(ret) {
				var evaled = false;
				if(ret.indexOf('ajaxerror') != -1) {
					evalscript(ret);
					evaled = true;
				}
				previewDiv.innerHTML = ret;
				newTd.appendChild(previewDiv);
				if(!evaled) evalscript(ret);
				newTr.style.display = '';
			});
		} else {
			newTd.innerHTML += '<div id="threadPreview_'+tid+'"></div>';
			ajaxget('forum.php?mod=viewthread&tid='+tid+'&from=preview', 'threadPreview_'+tid, null, null, null, function() {MathJax.typesetPromise([newTd]);newTr.style.display = '';});
		}
	} else {
		$(tbody).removeChild($('threadPreviewTR_'+tid));
		previewTbody = previewTid = null;
	}
}

function hideStickThread(tid) {
	var pre = 'stickthread_';
	var tids = (new Function("return ("+(loadUserdata('sticktids') || '[]')+")"))();
	var format = function (data) {
		var str = '{';
		for (var i in data) {
			if(data[i] instanceof Array) {
				str += i + ':' + '[';
				for (var j = data[i].length - 1; j >= 0; j--) {
					str += data[i][j] + ',';
				};
				str = str.substr(0, str.length -1);
				str += '],';
			}
		}
		str = str.substr(0, str.length -1);
		str += '}';
		return str;
	};
	if(!tid) {
		if(tids.length > 0) {
			for (var i = tids.length - 1; i >= 0; i--) {
				var ele = $(pre+tids[i]);
				if(ele) {
					ele.parentNode.removeChild(ele);
				}
			};
		}
	} else {
		var eletbody = $(pre+tid);
		if(eletbody) {
			eletbody.parentNode.removeChild(eletbody);
			tids.push(tid);
			saveUserdata('sticktids', '['+tids.join(',')+']');
		}
	}
	var clearstickthread = $('clearstickthread');
	if(clearstickthread) {
		if(tids.length > 0) {
			$('clearstickthread').style.display = '';
		} else {
			$('clearstickthread').style.display = 'none';
		}
	}
	var separatorline = $('separatorline');
	if(separatorline) {
		try {
			if(typeof separatorline.previousElementSibling === 'undefined') {
				var findele = separatorline.previousSibling;
				while(findele && findele.nodeType != 1){
					findele = findele.previousSibling;
				}
				if(findele === null) {
					separatorline.parentNode.removeChild(separatorline);
				}
			} else {
				if(separatorline.previousElementSibling === null) {
					separatorline.parentNode.removeChild(separatorline);
				}
			}
		} catch(e) {
		}
	}
}
function viewhot() {
	var obj = $('hottime');
	window.location.href = "forum.php?mod=forumdisplay&filter=hot&fid="+obj.getAttribute('fid')+"&time="+obj.value;
}
function clearStickThread () {
	saveUserdata('sticktids', '[]');
	location.reload();
}