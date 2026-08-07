/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

var replyreload = '', attachimgST = new Array(), zoomgroup = new Array(), zoomgroupinit = new Array();

function initPostQuoteButton() {
	const postSelector = '.t_f[id^="postmessage_"], .postmessage[id^="postmessage_"]';
	if(!document.querySelector(postSelector)) {
		return;
	}

	const portal = document.createElement('div');
	portal.className = 'sshare';
	portal.setAttribute('role', 'dialog');
	portal.setAttribute('aria-label', $L('reply'));
	portal.setAttribute('aria-hidden', 'true');

	const inner = document.createElement('div');
	inner.className = 'sshare__inner';
	const button = document.createElement('button');
	button.type = 'button';
	button.setAttribute('aria-label', $L('reply'));
	button.title = $L('reply');
	button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg><span>' + $L('reply') + '</span>';
	inner.appendChild(button);
	portal.appendChild(inner);
	document.body.appendChild(portal);

	let selectedText = '';
	let selectedPid = 0;
	let hideTimer = 0;

	const hide = () => {
		clearTimeout(hideTimer);
		portal.classList.remove('is-active');
		portal.classList.add('is-hiding');
		portal.setAttribute('aria-hidden', 'true');
		hideTimer = window.setTimeout(() => {
			portal.classList.remove('is-hiding', 'is-tacked');
		}, 200);
	};

	const postForNode = node => {
		const element = node && (node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement);
		return element ? element.closest(postSelector) : null;
	};

	const showForSelection = () => {
		const selection = window.getSelection();
		if(!selection || selection.isCollapsed || selection.rangeCount !== 1) {
			hide();
			return;
		}

		const range = selection.getRangeAt(0);
		const startPost = postForNode(range.startContainer);
		const endPost = postForNode(range.endContainer);
		const text = (typeof rangeToPlainTextWithMathJax === 'function'
			? rangeToPlainTextWithMathJax(range)
			: selection.toString()).trim();
		if(!startPost || startPost !== endPost || !text) {
			hide();
			return;
		}

		const pidMatch = startPost.id.match(/^postmessage_(\d+)$/);
		if(!pidMatch) {
			hide();
			return;
		}

		selectedText = text;
		selectedPid = Number(pidMatch[1]);
		const rect = range.getBoundingClientRect();
		portal.classList.add('is-tacked');
		portal.classList.remove('is-hiding');
		portal.setAttribute('aria-hidden', 'false');

		const portalRect = portal.getBoundingClientRect();
		const left = Math.min(
			window.scrollX + document.documentElement.clientWidth - portalRect.width - 8,
			Math.max(window.scrollX + 8, window.scrollX + rect.left + rect.width / 2 - portalRect.width / 2)
		);
		const top = Math.max(window.scrollY + 8, window.scrollY + rect.top - portalRect.height - 10);
		portal.style.left = left + 'px';
		portal.style.top = top + 'px';
		requestAnimationFrame(() => portal.classList.add('is-active'));
	};

	button.addEventListener('click', () => {
		if(!selectedPid || !selectedText) {
			return;
		}
		const url = new URL('forum.php', document.baseURI);
		url.searchParams.set('mod', 'post');
		url.searchParams.set('action', 'reply');
		url.searchParams.set('fid', typeof fid === 'undefined' ? '' : fid);
		url.searchParams.set('tid', typeof tid === 'undefined' ? '' : tid);
		url.searchParams.set('repquote', selectedPid);
		url.searchParams.set('quote', selectedText);
		showWindow('reply', url.pathname + url.search);
		hide();
	});

	document.addEventListener('mouseup', event => {
		if(portal.contains(event.target)) {
			return;
		}
		setTimeout(showForSelection);
	});
	document.addEventListener('keyup', event => {
		if(event.key === 'Escape') {
			hide();
		} else {
			showForSelection();
		}
	});
	document.addEventListener('mousedown', event => {
		if(!portal.contains(event.target)) {
			hide();
		}
	});
	window.addEventListener('blur', hide);
	window.addEventListener('resize', hide);
}

if(document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initPostQuoteButton);
} else {
	initPostQuoteButton();
}

function attachimggroup(pid) {
	if(!zoomgroupinit[pid]) {
               for (let i = 0;i < aimgcount[pid].length;i++) {
			zoomgroup['aimg_' + aimgcount[pid][i]] = pid;
		}
		zoomgroupinit[pid] = true;
	}
}

function attachimgshow(pid, onlyinpost) {
	onlyinpost = !onlyinpost ? false : onlyinpost;
	const aimgs = aimgcount[pid];
	let aimgcomplete = 0;
	let loadingcount = 0;
	for(let i = 0; i < aimgs.length; i++) {
		const obj = document.getElementById('aimg_' + aimgs[i]);
		if(!obj) {
			aimgcomplete++;
			continue;
		}
		if(onlyinpost && !obj.getAttribute('inpost')) {
			aimgcomplete++; 
			continue;
		}        
		if(onlyinpost && obj.getAttribute('inpost') || !onlyinpost) {
			if(!obj.status) {
				obj.status = 1;
				if(obj.getAttribute('file')) {
					obj.src = obj.getAttribute('file');
				}
				loadingcount++;
			} else if(obj.status == 1) {
				if(obj.complete) {
					obj.status = 2;
				} else {
					loadingcount++;
				}
			} else if(obj.status == 2) {
				aimgcomplete++;
				if(obj.getAttribute('thumbImg')) {
					thumbImg(obj);
				}
			}
			if(loadingcount >= 10) {
				break;
			}
		}
	}
	if(aimgcomplete < aimgs.length) {
		setTimeout(function () {
			attachimgshow(pid, onlyinpost);
		}, 100);
	}
}

function attachimglstshow(pid, islazy, fid, showexif) {
	var s = '';
	if(fid) {
		s = ' onmouseover="showMenu({\'ctrlid\':this.id, \'pos\': \'12!\'});"';
	}
	if(typeof aimgcount == 'object' && aimgcount[pid] && $('imagelistthumb_' + pid)) {
		let imagelist = '';
		for (let i = 0; i < aimgcount[pid].length; i++) {
			const aid = aimgcount[pid][i];
			if(!$('aimg_' + aid) || $('aimg_' + aid).getAttribute('inpost') || parseInt(aid) != aid) {
				continue;
			}
			if(fid) {
				imagelist += '<div id="pattimg_' + aid + '_menu" class="tip tip_4" style="display: none;"><div class="tip_horn"></div><div class="tip_c"><a href="forum.php?mod=ajax&action=setthreadcover&aid=' + aid + '&fid=' + fid + '" class="xi2" onclick="showWindow(\'setcover' + aid + '\', this.href)">' + $L('set_cover') + '</a></div></div>';
			}
			imagelist += '<div class="pattimg">' +
				'<a id="pattimg_' + aid + '" class="pattimg_zoom" href="javascript:;"' + s + ' onclick="zoom($(\'aimg_' + aid + '\'), attachimggetsrc(\'aimg_' + aid + '\'), 0, 0, ' + (parseInt(showexif) ? 1 : 0) + ')" title="' + $L('click_zoom') + '">' + $L('click_zoom') + '</a>' +
				'<img ' + (islazy ? 'file' : 'src') + '="forum.php?mod=image&aid=' + aid + '&size=100x100&key=' + imagelistkey + '&atid=' + tid + '" width="100" height="100" /></div>';
		}
		$('imagelistthumb_' + pid).innerHTML = imagelist;
	}
}

function attachimggetsrc(img) {
	return $(img).getAttribute('zoomfile') ? $(img).getAttribute('zoomfile') : $(img).getAttribute('file');
}

function attachimglst(pid, op, islazy) {
	if(!op) {
		$('imagelist_' + pid).style.display = 'none';
		$('imagelistthumb_' + pid).style.display = '';
	} else {
		$('imagelistthumb_' + pid).style.display = 'none';
		$('imagelist_' + pid).style.display = '';
		if(islazy) {
			o = new lazyload();
			o.showImage();
		} else {
			attachimgshow(pid);
		}
	}
	doane();
}

function attachimginfo(obj, infoobj, show, event) {
	objinfo = fetchOffset(obj);
	if(show) {
		$(infoobj).style.left = objinfo['left'] + 'px';
		$(infoobj).style.top = obj.offsetHeight < 40 ? (objinfo['top'] + obj.offsetHeight) + 'px' : objinfo['top'] + 'px';
		$(infoobj).style.display = '';
	} else {
		var mousex = document.body.scrollLeft + event.clientX;
		var mousey = document.documentElement.scrollTop + event.clientY;
		if(mousex < objinfo['left'] || mousex > objinfo['left'] + objinfo['width'] || mousey < objinfo['top'] || mousey > objinfo['top'] + objinfo['height']) {
			$(infoobj).style.display = 'none';
		}
	}
}

function signature(obj) {
	if(obj.style.maxHeightIE != '') {
		var height = (obj.scrollHeight > parseInt(obj.style.maxHeightIE)) ? obj.style.maxHeightIE : obj.scrollHeight + 'px';
		if(obj.innerHTML.indexOf('<IMG ') == -1) {
			obj.style.maxHeightIE = '';
		}
		return height;
	}
}

function tagshow(event) {
	var obj = event.target;
	ajaxmenu(obj, 0, 1, 2);
}

function parsetag(pid) {
	if(!$('postmessage_'+pid) || $('postmessage_'+pid).innerHTML.match(/<script[^\>]*?>/i)) {
		return;
	}
	var havetag = false;
	var tagfindarray = new Array();
       var str = $('postmessage_'+pid).innerHTML.replace(/(^|>)([^<]+)(?=<|$)/ig, function($1, $2, $3, $4) {
               for (const [i, tag] of tagarray.entries()) {
                       if(tag && $3.indexOf(tag) != -1) {
                               havetag = true;
                               $3 = $3.replace(tag, '<h_ ' + i + '>');
                               let tmp = $3.replace(/&[a-z]*?<h_ \d+>[a-z]*?;/ig, '');
                               if(tmp != $3) {
                                       $3 = tmp;
                               } else {
                                       tagfindarray[i] = tag;
                                       tagarray[i] = '';
                               }
                       }
               }
               return $2 + $3;
               });
		if(havetag) {
		$('postmessage_'+pid).innerHTML = str.replace(/<h_ (\d+)>/ig, function($1, $2) {
			return '<span href=\"misc.php?mod=tag&name=' + tagencarray[$2] + '\" onclick=\"tagshow(event)\" class=\"t_tag\">' + tagfindarray[$2] + '</span>';
	    	});
	}
}

function setanswer(pid, from) {
	showDialog($L('best_answer_confirm'), 'confirm', '', function () {
		$('modactions').action = 'forum.php?mod=misc&action=bestanswer&tid=' + tid + '&pid=' + pid + '&from=' + from + '&bestanswersubmit=yes';
		$('modactions').submit();
	}, 1, null, null, $L('confirm'), $L('cancel'));
}

var authort;
function showauthor(ctrlObj, menuid) {
	authort = setTimeout(function () {
		showMenu({'menuid':menuid});
		if($(menuid + '_ma').innerHTML == '') $(menuid + '_ma').innerHTML = ctrlObj.innerHTML;
	}, 500);
	if(!ctrlObj.onmouseout) {
		ctrlObj.onmouseout = function() {
			clearTimeout(authort);
		}
	}
}

function fastpostappendreply() {
	if($('fastpostrefresh') != null) {
		setcookie('fastpostrefresh', $('fastpostrefresh').checked ? 1 : 0, 2592000);
		if($('fastpostrefresh').checked) {
			location.href = 'forum.php?mod=redirect&tid='+tid+'&goto=lastpost&random=' + Math.random() + '#lastpost';
			return;
		}
	}
	$('fastpostsubmit').disabled = false;
	if($('fastpostmessage')) {
		$('fastpostmessage').value = '';
	} else {
		editdoc.body.innerHTML = BROWSER.firefox ? '<br />' : '';
	}
	if($('subject')) {
		$('subject').value = '';
	}
	if($('subjectbox')) {
		$('subjectbox').style.display = 'none';
	}
	if($('subjecthide')) {
		$('subjecthide').style.display = '';
	}
	if($('fastpostform').seccodehash){
		updateseccode($('fastpostform').seccodehash.value);
		$('fastpostform').seccodeverify.value = '';
	}
	if($('fastpostform').secqaahash){
		updatesecqaa($('fastpostform').secqaahash.value);
		$('fastpostform').secanswer.value = '';
	}
	showCreditPrompt();
}

function succeedhandle_fastpost(locationhref, message, param) {
	window.onbeforeunload = null;
	var tid = param['tid'];
	var from = param['from'];
	var reply_mod = param['reply_mod'];
	if(!reply_mod) {
		fastpostappendreply();
		if(replyreload) {
			var reloadpids = replyreload.split(',');
			for(var i = 1;i < reloadpids.length;i++) {
				ajaxget('forum.php?mod=viewthread&tid=' + tid + '&viewpid=' + reloadpids[i] + '&from=' + from, 'post_' + reloadpids[i], 'ajaxwaitid', null, null, function() {
					if(typeof updateMulu == 'function') {
						updateMulu();
					}
				});
			}
		}
		$('fastpostreturn').className = '';
	} else {
		if(!message) {
			message = $L('thread_mod_notice');
		}
		$('post_new').style.display = $('fastpostmessage').value = $('fastpostreturn').className = '';
		$('fastpostreturn').innerHTML = message;
	}
	if(param['sechash']) {
		updatesecqaa(param['sechash']);
		updateseccode(param['sechash']);
	}
	if($('attach_tblheader')) {
		$('attach_tblheader').style.display = 'none';
	}
	if($('attachlist')) {
		$('attachlist').innerHTML = '';
	}
}

function errorhandle_fastpost() {
	$('fastpostsubmit').disabled = false;
}

function succeedhandle_comment(locationhref, message, param) {
	hideWindow('comment');
}

function succeedhandle_postappend(locationhref, message, param) {
	ajaxget('forum.php?mod=viewthread&tid=' + param['tid'] + '&viewpid=' + param['pid'], 'post_' + param['pid'], 'ajaxwaitid', null, null, function() {
		if(typeof cleanPostBr == 'function') {
			cleanPostBr('post_' + param['pid']);
		}
		if(typeof initCodeCopyButton == 'function') {
			initCodeCopyButton('post_' + param['pid']);
		}
		if(typeof updateMulu == 'function') {
			updateMulu();
		}
	});
	hideWindow('postappend');
}

function recommendupdate(n) {
	var objv = n > 0 ? $('recommendv_add') : $('recommendv_subtract');
	if(objv) {
		objv.style.display = '';
		objv.innerHTML = parseInt(objv.innerHTML ? objv.innerHTML : 0) + 1;
	}
	setTimeout(function () {
		var count = $('recommentc');
		var panel = $('recommentv');
		if(count) {
			count.innerHTML = parseInt(count.innerHTML) + n;
		}
		if(panel) {
			panel.style.display = 'none';
		}
	}, 1000);
}

function postreviewupdate(pid, n, username) {
	var objv = n > 0 ? $('review_support_'+pid) : $('review_against_'+pid);
	objv.innerHTML = parseInt(objv.innerHTML ? objv.innerHTML : 0) + 1;
	if(username) {
		objv.parentNode.title = (objv.parentNode.title || '') + username + '\n';
	}
}

function postreviewcancel(pid, n, username) {
	var objv = n > 0 ? $('review_support_'+pid) : $('review_against_'+pid);
	objv.innerHTML = parseInt(objv.innerHTML ? objv.innerHTML : 0) - 1;
	if(username) {
		objv.parentNode.title = (objv.parentNode.title || '').replace(username + '\n', '');
	}
}

function favoriteupdate() {
	var obj = $('favoritenumber');
	if(obj) {
		obj.style.display = '';
		obj.innerHTML = parseInt(obj.innerHTML || 0) + 1;
	}
	var favorite = $('k_favorite');
	if(favorite) {
		favorite.classList.add('active');
		var icon = favorite.querySelector('.fico-star');
		if(icon) {
			icon.classList.add('fav-has-count');
		}
	}
}

function switchrecommendv() {
	display('recommendv');
	display('recommendav');
}

function updateMuluSelect(postObj, pid) {
	if(typeof MULUSELECT === 'undefined' || !MULUSELECT || MULUSELECT.querySelector('option[value="post_' + pid + '"]')) {
		return;
	}
	var floorObj = postObj.querySelector('.pi strong a') || postObj.querySelector('.pi strong');
	var authorObj = postObj.querySelector('.authi a.xw1') || postObj.querySelector('.authi a');
	var floorText = '';
	if(floorObj) {
		var floorClone = floorObj.cloneNode(true);
		floorClone.querySelectorAll('[aria-hidden="true"]').forEach(function(icon) {
			icon.remove();
		});
		floorText = floorClone.textContent.replace('#', '').trim();
	}
	var authorText = authorObj ? authorObj.textContent.trim() : '';
	var optionText = (floorText + (authorText ? ' ' + authorText : '')).trim();
	MULUSELECT.options.add(new Option(optionText, 'post_' + pid));
	MULUSELECT.size = MULUSELECT.options.length;
	if(MULUSELECT.firstChild && MULUSELECT.lastChild) {
		MULUSELECT.style.height = MULUSELECT.lastChild.offsetHeight + MULUSELECT.lastChild.offsetTop - MULUSELECT.firstChild.offsetTop + 'px';
	}
}

function appendreply(pid) {
	const postNew = $('post_new');
	const postId = `post_${pid}`;
	if(!postNew || $(postId) || !$('postlist') || !$('postlistreply')) {
		return;
	}
	postNew.style.display = '';
	$('postlist').appendChild(postNew);
	postNew.id = postId;
	if(typeof updateMulu == 'function') {
		updateMulu();
	} else {
		updateMuluSelect(postNew, pid);
	}
	if(typeof MathJax !== 'undefined' && typeof MathJax.typesetPromise === 'function') {
		MathJax.typesetPromise([postNew]);
	}
	newpos = fetchOffset(postNew);
	document.documentElement.scrollTop = newpos['top'];
	div = document.createElement('div');
	div.id = 'post_new';
	div.style.display = 'none';
	div.className = '';
	$('postlistreply').appendChild(div);
	if($('postform')) {
		$('postform').replysubmit.disabled = false;
	}
	showCreditPrompt();
}

function poll_checkbox(obj) {
	if(obj.checked) {
		p++;
		for (var i = 0; i < $('poll').elements.length; i++) {
			var e = $('poll').elements[i];
			if(p == max_obj) {
				if(e.name.match('pollanswers') && !e.checked) {
					e.disabled = true;
				}
			}
		}
	} else {
		p--;
		for (var i = 0; i < $('poll').elements.length; i++) {
			var e = $('poll').elements[i];
			if(e.name.match('pollanswers') && e.disabled) {
				e.disabled = false;
			}
		}
	}
	$('pollsubmit').disabled = p <= max_obj && p > 0 ? false : true;
}

function itemdisable(i) {
	if($('itemt_' + i).className == 'z') {
		$('itemt_' + i).className = 'z xg1';
		$('itemc_' + i).value = '';
		itemset(i);
	} else {
		$('itemt_' + i).className = 'z';
		$('itemc_' + i).value = $('itemc_' + i).value > 0 ? $('itemc_' + i).value : 0;
	}
}
function itemop(i, v) {
	$('item_' + i).className = 'z cmstar cmstv_' + v;
}
function itemclk(i, v) {
	$('itemc_' + i).value = v;
	$('itemt_' + i).className = 'z';
}
function itemset(i) {
	var v = $('itemc_' + i).value;
	v = v ? v : 0;
	$('item_' + i).className = 'z cmstar cmstv_' + v;
}

function checkmgcmn(id) {
	if($('mgc_' + id) && !$('mgc_' + id + '_menu').getElementsByTagName('li').length) {
		$('mgc_' + id).innerHTML = '';
		$('mgc_' + id).style.display = 'none';
	}
}

function toggleRatelogCollapse(tarId, ctrlObj) {
	if($(tarId).className == 'rate') {
		$(tarId).className = 'rate rate_collapse';
		setcookie('ratecollapse', 1, 2592000);
		ctrlObj.innerHTML = $L('unfold');
	} else {
		$(tarId).className = 'rate';
		setcookie('ratecollapse', 0, -2592000);
		ctrlObj.innerHTML = $L('fold');
	}
}

function copyThreadUrl(obj, bbname) {
	setCopy(obj.href, '<span class="chain-icon" aria-hidden="true">🔗</span> ' + $L('copy_thread_notice'));
	return false;
}

function lazyload(className) {
	var obj = this;
	lazyload.className = className;
	this.getOffset = function (el, isLeft) {
		var  retValue  = 0 ;
		while  (el != null ) {
			retValue  +=  el["offset" + (isLeft ? "Left" : "Top" )];
			el = el.offsetParent;
		}
		return  retValue;
	};
	this.initImages = function (ele) {
		lazyload.imgs = [];
		var eles = lazyload.className ? $C(lazyload.className, ele) : [document.body];
		for (var i = 0; i < eles.length; i++) {
			var imgs = eles[i].getElementsByTagName('IMG');
			for(var j = 0; j < imgs.length; j++) {
				if(imgs[j].getAttribute('file') && !imgs[j].getAttribute('lazyloaded')) {
					if(this.getOffset(imgs[j]) > document.documentElement.clientHeight) {
						lazyload.imgs.push(imgs[j]);
					} else {
						imgs[j].onload = function(){thumbImg(this);};
						imgs[j].setAttribute('src', imgs[j].getAttribute('file'));
						imgs[j].setAttribute('lazyloaded', 'true');
					}
				}
			}
		}
	};
	this.showImage = function() {
		this.initImages();
		if(!lazyload.imgs.length) return false;
		var imgs = [];
		var scrollTop = Math.max(document.documentElement.scrollTop , document.body.scrollTop);
		for (var i=0; i<lazyload.imgs.length; i++) {
			var img = lazyload.imgs[i];
			var offsetTop = this.getOffset(img);
			if (!img.getAttribute('lazyloaded') && offsetTop > document.documentElement.clientHeight && (offsetTop  - scrollTop < document.documentElement.clientHeight)) {
				var dom = document.createElement('div');
				var width = img.getAttribute('width') ? img.getAttribute('width') : 100;
				var height = img.getAttribute('height') ? img.getAttribute('height') : 100;
				dom.innerHTML = '<div style="width: '+width+'px; height: '+height+'px;background: url('+IMGDIR + '/loading.gif) no-repeat center center;"></div>';
				img.parentNode.insertBefore(dom.childNodes[0], img);
				img.onload = function () {
					if(!this.getAttribute('_load')) {
						this.setAttribute('_load', 1);
						this.style.width = this.style.height = '';
						this.parentNode.removeChild(this.previousSibling);
						if(this.getAttribute('lazyloadthumb')) {
							thumbImg(this);
						}
					}
				};
				img.style.width = img.style.height = '1px';
				img.setAttribute('src', img.getAttribute('file') ? img.getAttribute('file') : img.getAttribute('src'));
				img.setAttribute('lazyloaded', true);
			} else {
				imgs.push(img);
			}
		}
		lazyload.imgs = imgs;
		return true;
	};
	this.showImage();
	_attachEvent(window, 'scroll', function(){obj.showImage();});
}
function update_collection(){
	var obj = $('collectionnumber');
	sum = 1;
	obj.style.display = '';
	obj.innerText = parseInt(obj.innerText)+sum;
}
function display_blocked_post() {
	var movehiddendiv = (!$('hiddenposts').innerHTML) ? true : false;
	for (var i = 0; i < blockedPIDs.length; i++) {
		if(movehiddendiv) {
			$('hiddenposts').appendChild($("post_"+blockedPIDs[i]));
		}
		display("post_"+blockedPIDs[i]);
	}
	var postlistreply = $('postlistreply').innerHTML;
	$('hiddenpoststip').parentNode.removeChild($('postlistreply'));
	$('hiddenpoststip').parentNode.removeChild($('hiddenpoststip'));
	$('hiddenposts').innerHTML+='<div id="postlistreply" class="pl">'+postlistreply+'</div>';
}

function show_threadpage(pid, current, maxpage, ispreview, modthreadkey) {
	if(!$('threadpage') || typeof tid == 'undefined') {
		return;
	};
	var clickvalue = function (page, modthreadkey) {
		return 'ajaxget(\'forum.php?mod=viewthread&tid=' + tid + '&viewpid=' + pid + '&cp=' + page + (modthreadkey ? ('&modthreadkey=' + modthreadkey) : '') + (ispreview ? '&from=preview' : '') + '\', \'post_' + pid + '\', \'ajaxwaitid\');';
	};
	var pstart = current - 1;
	pstart = pstart < 1 ? 1 : pstart;
	var pend = current + 1;
	pend = pend > maxpage ? maxpage : pend;
	var s = '<div class="cm pgs mtm mbm cl"><div class="pg">';
	if(pstart > 1) {
		s += '<a href="javascript:;" onclick="' + clickvalue(1, modthreadkey) + '">1 ...</a>';
	}
	for(i = pstart;i <= pend;i++) {
		s += i == current ? '<strong>' + i + '</strong>' : '<a href="javascript:;" onclick="' + clickvalue(i, modthreadkey)+ '">' + i + '</a>';
	}
	if(pend < maxpage) {
		s += '<a href="javascript:;" onclick="' + clickvalue(maxpage, modthreadkey)+ '">... ' + maxpage + '</a>';
	}
	if(current < maxpage) {
		s += '<a href="javascript:;" onclick="' + clickvalue(current + 1, modthreadkey) + '" class="nxt">' + $L('next_page') + '</a>';
	}
	s += '<a href="javascript:;" onclick="' + clickvalue('all', modthreadkey) + '">' + $L('view_all') + '</a>';
	s += '</div></div>';
	$('threadpage').innerHTML = s;
}

var show_threadindex_data = '';
function show_threadindex(pid, ispreview) {
	if(!show_threadindex_data) {
		var s = '<div class="tindex"><h3>' + $L('dir') + '</h3><ul>';
		for (const o of $('threadindex').childNodes) {
			if(o.tagName == 'A') {
				var sub = o.getAttribute('sub').length * 2;
				o.href = "javascript:;";
				if(o.getAttribute('page')) {
					s += '<li style="margin-left:' + sub + 'em" onclick="ajaxget(\'forum.php?mod=viewthread&threadindex=yes&tid=' + tid + '&viewpid=' + pid + '&cp=' + o.getAttribute('page') + (ispreview ? '&from=preview' : '') + '\', \'post_' + pid + '\', \'ajaxwaitid\')">' + o.innerHTML + '</li>';
				} else if(o.getAttribute('tid') && o.getAttribute('pid')) {
					s += '<li style="margin-left:' + sub + 'em" onclick="ajaxget(\'forum.php?mod=viewthread&threadindex=yes&tid=' + o.getAttribute('tid') + '&viewpid=' + o.getAttribute('pid') + (ispreview ? '&from=preview' : '') + '\', \'post_' + pid + '\', \'ajaxwaitid\')">' + o.innerHTML + '</li>';
				}
			}
		}
		s += '</ul></div>';
		$('threadindex').innerHTML = s;
		show_threadindex_data = s;
	} else {
		$('threadindex').innerHTML = show_threadindex_data;
	}
}
function ctrlLeftInfo(sli_staticnum) {
	var sli = $('scrollleftinfo');
	var postlist_bottom = parseInt($('postlist').getBoundingClientRect().bottom);
	var sli_bottom = parseInt(sli.getBoundingClientRect().bottom);
	if(postlist_bottom < sli_staticnum && postlist_bottom != sli_bottom) {
		sli.style.top = (postlist_bottom - sli.offsetHeight - 5)+'px';
	} else{
		sli.style.top = 'auto';
	}
}

function fixed_avatar(pids, fixednv) {
	var fixedtopnv = fixednv ? new fixed_top_nv('nv', true) : false;
	if(fixednv) {
		fixedtopnv.init();
	}
	function fixedavatar(e) {
		var avatartop = fixednv ? fixedtopnv.run() : 0;
		for(var i = 0; i < pids.length; i++) {
			var pid = pids[i];
			var posttable = $('pid'+pid);
			var postavatar = $('favatar'+pid);
			if(!$('favatar'+pid)) {
				return;
			}
			var nextpost = $('_postposition'+pid);
			if(!postavatar || !nextpost || posttable.offsetHeight - 100 < postavatar.offsetHeight) {
				if(postavatar.style.position == 'fixed') {
					postavatar.style.position = '';
				}
				continue;
			}
			var avatarstyle = postavatar.style;
			posttabletop = parseInt(posttable.getBoundingClientRect().top);
			nextposttop = parseInt(nextpost.getBoundingClientRect().top);
			if(nextposttop > avatartop && nextposttop <= postavatar.offsetHeight + avatartop) {
				if(avatarstyle.position != 'absolute') {
					postavatar.parentNode.style.position = 'relative';
					avatarstyle.top = '';
					avatarstyle.bottom = '0px';
					avatarstyle.position = 'absolute';
				}
			} else if(posttabletop < avatartop && nextposttop > avatartop) {
					if(postavatar.parentNode.style.position != '') {
						postavatar.parentNode.style.position = '';
					}
					if(avatarstyle.position != 'fixed' || parseInt(avatarstyle.top) != avatartop) {
						avatarstyle.bottom = '';
						avatarstyle.top = avatartop + 'px';
						avatarstyle.position = 'fixed';
					}
			} else if(avatarstyle.position != '') {
				avatarstyle.position = '';
			}
		}
	}
	_attachEvent(window, 'load', function(){_attachEvent(window, 'scroll', fixedavatar);});
}

function submitpostpw(pid, tid) {
	var obj = $('postpw_' + pid);
	appendscript(JSPATH + 'md5.js?' + VERHASH);
	safescript('md5_js', function () {
		setcookie('postpw_' + pid, hex_md5(obj.value));
		if(!tid) {
			location.href = location.href;
		} else {
			location.href = 'forum.php?mod=viewthread&tid='+tid;
		}
	}, 100, 50);
}


function changecontentdivid(tid) {
	if($('postlistreply')) {
		objtid = $('postlistreply').getAttribute('tid');
		if(objtid == tid) {
			return;
		}
		$('postlistreply').id = 'postlistreply_'+objtid;
		postnewdiv = $('postlistreply_'+objtid).childNodes;
		postnewdiv[postnewdiv.length-1].id = 'post_new_'+objtid;
	}
	$('postlistreply_'+tid).id = 'postlistreply';
	postnewdiv = $('postlistreply').childNodes;
	postnewdiv[postnewdiv.length-1].id = 'post_new';
}

function showmobilebbs(obj) {
	var content = '<h3 class="flb" style="cursor:move;"><em>' + $L('download_mobilebbs') + '</em><span><a href="javascript:;" class="flbc" onclick="hideWindow(\'mobilebbs\')" title="{lang close}">{lang close}</a></span></h3><div class="c"><h4>' + $L('download_mobilebbs_tip_1') + '</h4><p class="mtm mbm vm"><span class="code_bg"><img src="'+ STATICURL +'image/common/zslt_andriod.png" alt="" /></span><img src="'+ STATICURL +'image/common/andriod.png" alt="' + $L('download_mobilebbs_tip_2') + '" /></p><h4>' + $L('download_mobilebbs_tip_3') + '</h4><p class="mtm mbm vm"><span class="code_bg"><img src="'+ STATICURL +'image/common/zslt_ios.png" alt="" /></span><img src="'+ STATICURL +'image/common/ios.png" alt="' + $L('download_mobilebbs_tip_4') + '" /></p></div>';
	showWindow('mobilebbs', content, 'html');
}

function succeedhandle_vfastpost(url, message, param) {
	$('vmessage').value = '';
	succeedhandle_fastpost(url, message, param);
	showCreditPrompt();
}

function vmessage() {
	var vf_tips = '#' + $L('here_fast_reply') + '#';
	$('vmessage').value = vf_tips;
	$('vmessage').style.color = '#CDCDCD';
	$('vmessage').onclick = function() {
		if($('vmessage').value==vf_tips) {
			$('vmessage').value='';
			$('vmessage').style.color="#000";
		}
	};
	$('vmessage').onblur = function() {
		if(!$('vmessage').value) {
			$('vmessage').value=vf_tips;
			$('vmessage').style.color="#CDCDCD";
		}
	};
	$('vreplysubmit').onclick = function() {
		if($('vmessage').value == vf_tips) {
			return false;
		}
	};
	$('vmessage').onfocus = function() {
		ajaxget('forum.php?mod=ajax&action=checkpostrule&ac=reply', 'vfastpostseccheck');
		$('vmessage').onfocus = null;
	};
}

function delcomment(id, pid) {
	const formhash = document.querySelector('input[name="formhash"]')?.value;
	fetch('forum.php?mod=topicadmin&action=delcomment&modsubmit=yes&infloat=yes&modclick=yes&inajax=1', {
		headers: {'content-type': 'application/x-www-form-urlencoded'},
		body: `formhash=${formhash}&fid=${fid}&tid=${tid}&handlekey=mods&topiclist=${id}`,
		method: 'POST'
	}).then(() => ajaxget(`forum.php?mod=misc&action=commentmore&tid=${tid}&pid=${pid}`, `comment_${pid}`));
}
function bumpthread() {
	const formhash = document.querySelector('input[name="formhash"]')?.value;
	fetch('forum.php?mod=topicadmin&action=moderate&optgroup=3&modsubmit=yes&infloat=yes&inajax=1', {
		"headers": {"content-type": "application/x-www-form-urlencoded"},
		"body": `fid=${fid}&moderate%5B%5D=${tid}&redirect=1&operations%5B%5D=bump&formhash=${formhash}&handlekey=mods`,
		"method": "POST"
		}).then(response => {
			response.text().then(text => {
				if (text.includes('succeedhandle_mods')) {
					showDialog('提升成功', 'right', '提升成功', 'window.location.reload();');
				} else {
					showDialog(text.match(/errorhandle_mods\('([^']+)'/)[1], 'error', '提升失败');
				}
			});
		});
}

//===支持tikz + asymptote
function show_tikz_window(code){
	showDialog('<div class="tikz-dialog"><textarea class="tikzta" readonly></textarea></div>', 'info', 'TikZ');
	var dialog = $('fwin_dialog');
	var textarea = dialog ? dialog.querySelector('textarea.tikzta') : null;
	if (textarea) textarea.value = code.replace(/\u00a0/g, ' ');
}
window.show_tikz_window = show_tikz_window;

//===去br等
function cleanPostBr(target) {
	const posts = new Set();
	if (!target || target === document) {
		document.querySelectorAll('.t_f, .postmessage, .message').forEach(p => posts.add(p));
	} else {
		let root = typeof target === 'string' ? document.getElementById(target) : target;
		if (root) {
			let el = root.nodeType === Node.ELEMENT_NODE ? root : root.parentElement;
			if (el) {
				let container = el.closest ? el.closest('.t_f, .postmessage, .message') : null;
				if (container) posts.add(container);
				if (el.querySelectorAll) {
					if (el.matches && el.matches('.t_f, .postmessage, .message')) posts.add(el);
					el.querySelectorAll('.t_f, .postmessage, .message').forEach(p => posts.add(p));
				}
			}
		}
	}
	posts.forEach(post => {
		post.querySelectorAll('br').forEach(br => {
			if (br.nextSibling && br.nextSibling.nodeType === Node.TEXT_NODE) {
				br.nextSibling.nodeValue = br.nextSibling.nodeValue.replace(/^\n/, '');
			}
			if (br.previousSibling && br.previousSibling.nodeType === Node.TEXT_NODE) {
				if (/(\\\]|\\end\{(align|gather|equation|eqnarray|multline)\*?\}|\$\$)( |&nbsp;)*$/.test(br.previousSibling.nodeValue)) {
					br.previousSibling.nodeValue = br.previousSibling.nodeValue.replace(/( |&nbsp;)*$/, '');
					br.remove();
				}
			}
			else if (br.previousSibling && br.previousSibling.nodeType === Node.ELEMENT_NODE && br.previousSibling.matches('div.quote,div.blockcode')) {
				br.remove();
			}
		});
	});
}
window.cleanPostBr = cleanPostBr;

function initCodeCopyButton(root) {
	const container = typeof root === 'string' ? document.getElementById(root) : (root || document);
	if (!container) {
		return;
	}
	const blocks = container.querySelectorAll('div.blockcode');
	for (const block of blocks) {
		if (block.querySelector('em[onclick^="copycode"]')) {
			continue;
		}
		const pre = block.querySelector('pre');
		if (!pre) {
			continue;
		}
		if (!pre.id) {
			pre.id = 'code_' + Math.random().toString(36).slice(2, 10);
		}
		const label = $L('copy_to_clipboard');
		const button = document.createElement('em');
		button.title = label;
		button.setAttribute('aria-label', label);
		button.onclick = function() {
			copycode($(pre.id));
		};
		button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg>';
		block.appendChild(button);
	}
}
window.initCodeCopyButton = initCodeCopyButton;

function initViewthreadEnhancements(root) {
	cleanPostBr(root);
	initCodeCopyButton(root);
}
window.initViewthreadEnhancements = initViewthreadEnhancements;

//一次性清理冗余br，避免与MathJax排版互相干扰。
function initJumpCallout() {
	var hash = window.location.hash || '';
	if(!hash) {
		return;
	}
	var target = null;
	if(hash === '#lastpost') {
		var anchor = document.querySelector('a[name="lastpost"]');
		target = anchor && anchor.closest ? anchor.closest('table[id^="pid"]') : null;
	} else {
		var m = hash.match(/^#pid(\d+)$/);
		if(!m) {
			return;
		}
		target = document.getElementById('pid' + m[1]);
	}
	if(!target || !target.id) {
		return;
	}
	var callout = document.getElementById('ntc_jp_' + target.id);
	if(!callout) {
		return;
	}
	callout.style.display = '';
}
window.initJumpCallout = initJumpCallout;

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		initViewthreadEnhancements(document);
		initJumpCallout();
	});
} else {
	initViewthreadEnhancements(document);
	initJumpCallout();
}
