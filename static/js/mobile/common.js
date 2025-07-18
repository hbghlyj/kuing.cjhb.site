var supporttouch = "ontouchend" in document;

var platform = navigator.platform;
var ua = navigator.userAgent;
var ios = /iPhone|iPad|iPod/.test(platform) && ua.indexOf( "AppleWebKit" ) > -1;
var andriod = ua.indexOf( "Android" ) > -1;

var JSLOADED = [];

var HTML5PLAYER = [];
HTML5PLAYER['apload'] = 0;
HTML5PLAYER['dpload'] = 0;
HTML5PLAYER['flvload'] = 0;

var BROWSER = {};
var USERAGENT = navigator.userAgent.toLowerCase();
browserVersion({'ie':'msie','firefox':'','chrome':'','opera':'','safari':'','mozilla':'','webkit':'','maxthon':'','qq':'qqbrowser','rv':'rv'});
if(BROWSER.safari || BROWSER.rv) {
	BROWSER.firefox = true;
}
BROWSER.opera = BROWSER.opera ? opera.version() : 0;

var page = {
	converthtml : function() {
		var prevpage = qSel('div.pg .prev') ? qSel('div.pg .prev').href : undefined;
		var nextpage = qSel('div.pg .nxt') ? qSel('div.pg .nxt').href : undefined;
		var lastpage = qSel('div.pg label span') ? (qSel('div.pg label span').innerText.replace(/[^\d]/g, '') || 0) : 0;
		var curpage = qSel('div.pg input') ? qSel('div.pg input').value : 1;
		var multipage_url = getID('multipage_url') ? getID('multipage_url').value : undefined;

		if(!lastpage) {
			prevpage = qSel('div.pg .pgb a') ? qSel('div.pg .pgb a').href : undefined;
		}

		var prevpagehref = nextpagehref = '';
		if(prevpage == undefined) {
			prevpagehref = 'javascript:;" class="grey';
		} else {
			prevpagehref = prevpage;
		}
		if(nextpage == undefined) {
			nextpagehref = 'javascript:;" class="grey';
		} else {
			nextpagehref = nextpage;
		}

		var selector = '';
		if(lastpage) {
			selector += '<a id="select_a">';
			selector += '<select id="dumppage">';
                       for(var i=1; i<=lastpage; i++) {
                               selector += '<option value="'+i+'" '+ (i == curpage ? 'selected' : '') +'>'+lng['first']+i+lng['pages']+'</option>';
                        }
                        selector += '</select>';
                        selector += '<span>'+lng['first']+curpage+lng['pages']+'</span>';
		}

		var pgobj = qSel('div.pg');
		pgobj.classList.remove('pg');
		pgobj.classList.add('page');
               pgobj.innerHTML = '<a href="'+ prevpagehref +'">'+lng['prev_page']+'</a>'+ selector +'<a href="'+ nextpagehref +'">'+lng['next_page']+'</a>';
		qSel('#dumppage').addEventListener('change', function() {
			var href = (prevpage || nextpage);
			var newhref = href.replace(/page=\d+/, 'page=' + this.value);
			if (newhref == href) {
				newhref = href.replace(/(forum|thread|article|group|blog)-(\d+)-(\d+)(-(\d+))?\.html/i, '$1-$2-' + this.value + '$4.html');
				if (newhref == href && multipage_url != undefined) {
					newhref = multipage_url.replace(/{page}/i, this.value);
				}
			}
			window.location.href = newhref;
		});
	},
};

var scrolltop = {
	obj : null,
	init : function(obj) {
		scrolltop.obj = obj;
		var pageHeight = Math.max(document.body.scrollHeight, document.body.offsetHeight);
		var screenHeight = window.innerHeight;
		var scrollType = 'bottom';
		var scrollToPos = function() {
			if(scrollType == 'bottom') {
				window.scrollTo(0, pageHeight);
			} else {
				window.scrollTo(0, 0);
			}
			scrollfunc();
		};
		var scrollfunc = function() {
			var newType;
			if(document.documentElement.scrollTop >= 50) {
				newType = 'top';
			} else {
				newType = 'bottom';
			}
			if(newType != scrollType) {
				scrollType = newType;
				if(newType == 'top') {
					obj.classList.remove('bottom');
				} else {
					obj.classList.add('bottom');
				}
			}
		};
		if(pageHeight - screenHeight < 100) {
			obj.style.display = 'none';
		} else {
			obj.addEventListener('click', scrollToPos);
			document.addEventListener('scroll', scrollfunc);
			scrollfunc();
		}
	},
};

var img = {
	init : function(is_err_t) {
		var errhandle = this.errorhandle;
		$('img').on('load', function() {
			var obj = $(this);
			obj.attr('zsrc', obj.attr('src'));
			if(obj.width() < 5 && obj.height() < 10 && obj.css('display') != 'none') {
				return errhandle(obj, is_err_t);
			}
			obj.css('display', 'inline');
			obj.css('visibility', 'visible');
			if(obj.width() > window.innerWidth) {
				obj.css('width', window.innerWidth);
			}
			obj.parent().find('.loading').remove();
			obj.parent().find('.error_text').remove();
		})
		.on('error', function() {
			var obj = $(this);
			obj.attr('zsrc', obj.attr('src'));
			errhandle(obj, is_err_t);
		});
	},
	errorhandle : function(obj, is_err_t) {
		if(obj.attr('noerror') == 'true') {
			return;
		}
		obj.css('visibility', 'hidden');
		obj.css('display', 'none');
		var parentnode = obj.parent();
		parentnode.find('.loading').remove();
		parentnode.append('<div class="loading" style="background:url('+ IMGDIR +'/imageloading.gif) no-repeat center center;width:'+parentnode.width()+'px;height:'+parentnode.height()+'px"></div>');
		var loadnums = parseInt(obj.attr('load')) || 0;
		if(loadnums < 3) {
			obj.attr('src', obj.attr('zsrc'));
			obj.attr('load', ++loadnums);
			return false;
		}
		if(is_err_t) {
			var parentnode = obj.parent();
			parentnode.find('.loading').remove();
                       parentnode.append('<div class="error_text">'+lng['click_to_reload']+'</div>');
			parentnode.find('.error_text').one('click', function() {
				obj.attr('load', 0).find('.error_text').remove();
				parentnode.append('<div class="loading" style="background:url('+ IMGDIR +'/imageloading.gif) no-repeat center center;width:'+parentnode.width()+'px;height:'+parentnode.height()+'px"></div>');
				obj.attr('src', obj.attr('zsrc'));
			});
		}
		return false;
	}
};

var POPMENU = new Object;
var popup = {
	init : function() {
		var $this = this;
		$('.popup').each(function(index, obj) {
			obj = $(obj);
			var pop = $(obj.attr('href'));
			if(pop && pop.attr('popup')) {
				pop.css({'display':'none'});
				obj.on('click', function(e) {
					$this.open(pop);
					return false;
				});
			}
		});
		this.maskinit();
	},
	maskinit : function() {
		var $this = this;
		$('#mask').off().on('click', function() {
			$this.close();
		});
	},

	open : function(pop, type, url) {
		this.close();
		this.maskinit();
		if(typeof pop == 'string') {
			$('#ntcmsg').remove();
			if(type == 'alert') {
                               pop = '<div class="tip"><dt>'+ pop +'</dt><dd><input class="button2" type="button" value="'+lng['submit']+'" onclick="popup.close();"></dd></div>'
			} else if(type == 'confirm') {
                               pop = '<div class="tip"><dt>'+ pop +'</dt><dd><a class="button" href="'+ url +'">'+lng['submit']+'</a> <button onclick="popup.close();" class="button">'+lng['cancel']+'</button></dd></div>'
			}
			$('body').append('<div id="ntcmsg" style="display:none;">'+ pop +'</div>');
			pop = $('#ntcmsg');
		}
		if(POPMENU[pop.attr('id')]) {
			$('#' + pop.attr('id') + '_popmenu').html(pop.html()).css({'height':pop.height()+'px', 'width':pop.width()+'px'});
		} else {
			pop.parent().append('<div class="dialogbox" id="'+ pop.attr('id') +'_popmenu" style="height:'+ pop.height() +'px;width:'+ pop.width() +'px;">'+ pop.html() +'</div>');
		}
		var popupobj = $('#' + pop.attr('id') + '_popmenu');
		var left = (window.innerWidth - popupobj.width()) / 2;
		var top = (document.documentElement.clientHeight - popupobj.height()) / 2;
		popupobj.css({'display':'block','position':'fixed','left':left,'top':top,'z-index':120,'opacity':1});
		$('#mask').css({'display':'block','width':'100%','height':'100%','position':'fixed','top':'0','left':'0','background':'black','opacity':'0.2','z-index':'100'});
		POPMENU[pop.attr('id')] = pop;
	},
	close : function() {
		$('#mask').css('display', 'none');
		$.each(POPMENU, function(index, obj) {
			$('#' + index + '_popmenu').css('display','none');
		});
	}
};

var dialog = {
	init : function() {
		$(document).on('click', '.dialog', function() {
			var obj = $(this);
			popup.open('<img src="' + IMGDIR + '/imageloading.gif">');
			$.ajax({
				type : 'GET',
				url : obj.attr('href') + '&inajax=1',
				dataType : 'xml'
			})
			.success(function(s) {
				popup.open(s.lastChild.firstChild.nodeValue);
				evalscript(s.lastChild.firstChild.nodeValue);
			})
			.error(function() {
				window.location.href = obj.attr('href');
				popup.close();
			});
			return false;
		});
	},

};

var formdialog = {
	init : function() {
		$(document).on('click', '.formdialog', function() {
			popup.open('<img src="' + IMGDIR + '/imageloading.gif">');
			var obj = $(this);
			var formobj = $(this.form);
			var isFormData = formobj.find("input[type='file']").length > 0;
			$.ajax({
				type:'POST',
				url:formobj.attr('action') + '&handlekey='+ formobj.attr('id') +'&inajax=1',
				data:isFormData ? new FormData(formobj[0]) : formobj.serialize(),
				dataType:'xml',
				processData:isFormData ? false : true,
				contentType:isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8'
			})
			.success(function(s) {
				popup.open(s.lastChild.firstChild.nodeValue);
				evalscript(s.lastChild.firstChild.nodeValue);
			})
			.error(function() {
                               popup.open(lng['form_submit_error'], 'alert');
			});
			return false;
		});
	}
};

var DISMENU = new Object;
var display = {
	init : function() {
		var $this = this;
		$('.display').each(function(index, obj) {
			obj = $(obj);
			var dis = $(obj.attr('href'));
			if(dis && dis.attr('display')) {
				dis.css({'display':'none'});
				dis.css({'z-index':'102'});
				DISMENU[dis.attr('id')] = dis;
				obj.on('click', function(e) {
					if(in_array(e.target.tagName, ['A', 'IMG', 'INPUT'])) return;
					$this.maskinit();
					if(dis.attr('display') == 'true') {
						dis.css('display', 'block');
						dis.attr('display', 'false');
						$('#mask').css({'display':'block','width':'100%','height':'100%','position':'fixed','top':'0','left':'0','background':'transparent','z-index':'100'});
					}
					return false;
				});
			}
		});
	},
	maskinit : function() {
		var $this = this;
		$('#mask').off().on('touchstart', function() {
			$this.hide();
		});
	},
	hide : function() {
		$('#mask').css('display', 'none');
		$.each(DISMENU, function(index, obj) {
			obj.css('display', 'none');
			obj.attr('display', 'true');
		});
	}
};

function getID(id) {
	return !id ? null : document.getElementById(id);
}

function qSel(sel) {
	return document.querySelector(sel);
}

function qSelA(sel) {
	return document.querySelectorAll(sel);
}

function mygetnativeevent(event) {

	while(event && typeof event.originalEvent !== "undefined") {
		event = event.originalEvent;
	}
	return event;
}

function evalscript(s) {
	if(s.indexOf('<script') == -1) return s;
	var p = /<script[^\>]*?>([^\x00]*?)<\/script>/ig;
	var arr = [];
	while(arr = p.exec(s)) {
		var p1 = /<script[^\>]*?src=\"([^\>]*?)\"[^\>]*?(reload=\"1\")?(?:charset=\"([\w\-]+?)\")?><\/script>/i;
		var arr1 = [];
		arr1 = p1.exec(arr[0]);
		if(arr1) {
			appendscript(arr1[1], '', arr1[2], arr1[3]);
		} else {
			p1 = /<script(.*?)>([^\x00]+?)<\/script>/i;
			arr1 = p1.exec(arr[0]);
			appendscript('', arr1[2], arr1[1].indexOf('reload=') != -1);
		}
	}
	return s;
}

var safescripts = {}, evalscripts = [];

function appendscript(src, text, reload, charset) {
	var id = hash(src + text);
	if(!reload && in_array(id, evalscripts)) return;
	if(reload && getID(id)) {
		getID(id).parentNode.removeChild(getID(id));
	}

	evalscripts.push(id);
	var scriptNode = document.createElement("script");
	scriptNode.type = "text/javascript";
	scriptNode.id = id;
	scriptNode.charset = charset ? charset : (!document.charset ? document.characterSet : document.charset);
	try {
		if(src) {
			scriptNode.src = src;
			scriptNode.onloadDone = false;
			scriptNode.onload = function () {
				scriptNode.onloadDone = true;
				JSLOADED[src] = 1;
			};
			scriptNode.onreadystatechange = function () {
				if((scriptNode.readyState == 'loaded' || scriptNode.readyState == 'complete') && !scriptNode.onloadDone) {
					scriptNode.onloadDone = true;
					JSLOADED[src] = 1;
				}
			};
		} else if(text){
			scriptNode.text = text;
		}
		document.getElementsByTagName('head')[0].appendChild(scriptNode);
	} catch(e) {}
}

function hash(string, length) {
	var length = length ? length : 32;
	var start = 0;
	var i = 0;
	var result = '';
	filllen = length - string.length % length;
	for(i = 0; i < filllen; i++){
		string += "0";
	}
	while(start < string.length) {
		result = stringxor(result, string.substr(start, length));
		start += length;
	}
	return result;
}

function stringxor(s1, s2) {
	var s = '';
	var hash = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var max = Math.max(s1.length, s2.length);
	for(var i=0; i<max; i++) {
		var k = s1.charCodeAt(i) ^ s2.charCodeAt(i);
		s += hash.charAt(k % 52);
	}
	return s;
}

function in_array(needle, haystack) {
	if(typeof needle == 'string' || typeof needle == 'number') {
		for(var i in haystack) {
			if(haystack[i] == needle) {
					return true;
			}
		}
	}
	return false;
}

function isUndefined(variable) {
	return typeof variable == 'undefined' ? true : false;
}

function setcookie(cookieName, cookieValue, seconds, path, domain, secure) {
	if(cookieValue == '' || seconds < 0) {
		cookieValue = '';
		seconds = -2592000;
	}
	if(seconds) {
		var expires = new Date();
		expires.setTime(expires.getTime() + seconds * 1000);
	}
	domain = !domain ? cookiedomain : domain;
	path = !path ? cookiepath : path;
	document.cookie = escape(cookiepre + cookieName) + '=' + escape(cookieValue)
		+ (expires ? '; expires=' + expires.toGMTString() : '')
		+ (path ? '; path=' + path : '/')
		+ (domain ? '; domain=' + domain : '')
		+ (secure ? '; secure' : '');
}

function getcookie(name, nounescape) {
	name = cookiepre + name;
	var cookie_start = document.cookie.indexOf(name);
	var cookie_end = document.cookie.indexOf(";", cookie_start);
	if(cookie_start == -1) {
		return '';
	} else {
		var v = document.cookie.substring(cookie_start + name.length + 1, (cookie_end > cookie_start ? cookie_end : document.cookie.length));
		return !nounescape ? unescape(v) : v;
	}
}

function browserVersion(types) {
	var other = 1;
	for(i in types) {
		var v = types[i] ? types[i] : i;
		if(USERAGENT.indexOf(v) != -1) {
			var re = new RegExp(v + '(\\/|\\s|:)([\\d\\.]+)', 'ig');
			var matches = re.exec(USERAGENT);
			var ver = matches != null ? matches[2] : 0;
			other = ver !== 0 && v != 'mozilla' ? 0 : other;
		} else {
			var ver = 0;
		}
		eval('BROWSER.' + i + '= ver');
	}
	BROWSER.other = other;
}


function appendstyle(url) {
	var link = document.createElement('link');
	link.type = 'text/css';
	link.rel = 'stylesheet';
	link.href = url;
	var head = document.getElementsByTagName('head')[0];
	head.appendChild(link);
}

function detectHtml5Support() {
	return document.createElement("Canvas").getContext;
}

function detectPlayer(randomid, ext, src, width, height) {
	var h5_support = new Array('aac', 'flac', 'mp3', 'm4a', 'wav', 'flv', 'mp4', 'm4v', '3gp', 'ogv', 'ogg', 'weba', 'webm');
	var trad_support = new Array('mp3', 'wma', 'mid', 'wav', 'ra', 'ram', 'rm', 'rmvb', 'asf', 'asx', 'wmv', 'avi', 'mpg', 'mpeg', 'mov');
	if (in_array(ext, h5_support) && detectHtml5Support()) {
		html5Player(randomid, ext, src, width, height);
	} else if (in_array(ext, trad_support)) {
		tradionalPlayer(randomid, ext, src, width, height);
	} else {
		document.getElementById(randomid).style.width = '100%';
		document.getElementById(randomid).style.height = height + 'px';
	}
}

function tradionalPlayer(randomid, ext, src, width, height) {
	switch(ext) {
		case 'mp3':
		case 'wma':
		case 'mid':
		case 'wav':
			height = 64;
			html = '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="' + width + '" height="' + height + '"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="' + src + '" /><embed src="' + src + '" autostart="0" type="application/x-mplayer2" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		case 'ra':
		case 'ram':
			height = 32;
			html = '<object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="' + width + '" height="' + height + '"><param name="autostart" value="0" /><param name="src" value="' + src + '" /><param name="controls" value="controlpanel" /><param name="console" value="' + randomid + '_" /><embed src="' + src + '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="ControlPanel" console="' + randomid + '_" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		case 'rm':
		case 'rmvb':
			html = '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="' + width + '" height="' + height + '"><param name="autostart" value="0" /><param name="src" value="' + src + '" /><param name="controls" value="imagewindow" /><param name="console" value="' + randomid + '_" /><embed src="' + src + '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="imagewindow" console="' + randomid + '_" width="' + width + '" height="' + height + '"></embed></object><br /><object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="' + width + '" height="32"><param name="src" value="' + src +'" /><param name="controls" value="controlpanel" /><param name="console" value="' + randomid + '_" /><embed src="' + src + '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="controlpanel" console="' + randomid + '_" width="' + width + '" height="32"></embed></object>';
			break;
		case 'asf':
		case 'asx':
		case 'wmv':
		case 'avi':
		case 'mpg':
		case 'mpeg':
			html = '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="' + width + '" height="' + height + '"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="' + src + '" /><embed src="' + src + '" autostart="0" type="application/x-mplayer2" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		case 'mov':
			html = '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="' + width + '" height="' + height + '"><param name="autostart" value="false" /><param name="src" value="' + src + '" /><embed src="' + src + '" autostart="false" type="video/quicktime" controller="true" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		default:
			break;
	}
	document.getElementById(randomid).style.width = '100%';
	document.getElementById(randomid).style.height = height + 'px';
	document.getElementById(randomid + '_container').innerHTML = html;
}

function html5Player(randomid, ext, src, width, height) {
	switch (ext) {
		case 'aac':
		case 'flac':
		case 'mp3':
		case 'm4a':
		case 'wav':
		case 'ogg':
			height = 66;
			if(!HTML5PLAYER['apload']) {
				appendstyle(STATICURL + 'js/player/aplayer.min.css');
				appendscript(STATICURL + 'js/player/aplayer.min.js');
				HTML5PLAYER['apload'] = 1;
			}
			html5APlayer(randomid, ext, src, width, height);
			break;
		case 'flv':
			if(!HTML5PLAYER['flvload']) {
				appendscript(STATICURL + 'js/player/flv.min.js');
				HTML5PLAYER['flvload'] = 1;
			}
		case 'mp4':
		case 'm4v':
		case '3gp':
		case 'ogv':
		case 'webm':
			if(!HTML5PLAYER['dpload']) {
				appendstyle(STATICURL + 'js/player/dplayer.min.css');
				appendscript(STATICURL + 'js/player/dplayer.min.js');
				HTML5PLAYER['dpload'] = 1;
			}
			html5DPlayer(randomid, ext, src, width, height);
			break;
		default:
			break;
	}
	document.getElementById(randomid).style.width = '100%';
}

function html5APlayer(randomid, ext, src, width, height) {
	if (JSLOADED[STATICURL + 'js/player/aplayer.min.js']) {
		window[randomid] = new APlayer({
			container: document.getElementById(randomid + '_container'),
			mini: false,
			autoplay: false,
			loop: 'all',
			preload: 'none',
			volume: 1,
			mutex: true,
			listFolded: true,
			audio: [{
				name: ' ',
				artist: ' ',
				url: src,
			}]
		});
	} else {
		setTimeout(function () {
			html5APlayer(randomid, ext, src, width, height);
		}, 50);
	}
}

function html5DPlayer(randomid, ext, src, width, height) {
	if (JSLOADED[STATICURL + 'js/player/dplayer.min.js'] && (ext != 'flv' || JSLOADED[STATICURL + 'js/player/flv.min.js'])) {
		window[randomid] = new DPlayer({
			container: document.getElementById(randomid + '_container'),
			autoplay: false,
			loop: true,
			screenshot: false,
			hotkey: true,
			preload: 'none',
			volume: 1,
			mutex: true,
			listFolded: true,
			video: {
				url: src,
			}
		});
	} else {
		setTimeout(function () {
			html5DPlayer(randomid, ext, src, width, height);
		}, 50);
	}
}

$(document).ready(function() {

	if(qSel('div.pg')) {
		page.converthtml();
	}
	if(qSel('.scrolltop')) {
		scrolltop.init(qSel('.scrolltop'));
	}
	if($('img').length > 0) {
		img.init(1);
	}
	if($('.popup').length > 0) {
		popup.init();
	}
	if($('.display').length > 0) {
		display.init();
	}
	dialog.init();
	formdialog.init();
});

function ajaxget(url, showid, waitid, loading, display, recall) {
	var url = url + '&inajax=1&ajaxtarget=' + showid;
	$.ajax({
		type : 'GET',
		url : url,
		dataType : 'xml',
	}).success(function(s) {
		$('#'+showid).html(s.lastChild.firstChild.nodeValue);
		$("[ajaxtarget]").off('click').on('click', function(e) {
			var id = $(this);
			ajaxget(id.attr('href'), id.attr('ajaxtarget'));
			return false;
		});
	});
	return false;
}

function getHost(url) {
	var host = "null";
	if(typeof url == "undefined"|| null == url) {
		url = window.location.href;
	}
	var regex = /^\w+\:\/\/([^\/]*).*/;
	var match = url.match(regex);
	if(typeof match != "undefined" && null != match) {
		host = match[1];
	}
	return host;
}

function hostconvert(url) {
	if(!url.match(/^https?:\/\//)) url = SITEURL + url;
	var url_host = getHost(url);
	var cur_host = getHost().toLowerCase();
	if(url_host && cur_host != url_host) {
		url = url.replace(url_host, cur_host);
	}
	return url;
}

function Ajax(recvType, waitId) {
	var aj = new Object();
       aj.loading = lng['wait_please'];
	aj.recvType = recvType ? recvType : 'XML';
	aj.waitId = waitId ? $(waitId) : null;
	aj.resultHandle = null;
	aj.sendString = '';
	aj.targetUrl = '';
	aj.setLoading = function(loading) {
		if(typeof loading !== 'undefined' && loading !== null) aj.loading = loading;
	};
	aj.setRecvType = function(recvtype) {
		aj.recvType = recvtype;
	};
	aj.setWaitId = function(waitid) {
		aj.waitId = typeof waitid == 'object' ? waitid : $(waitid);
	};
	aj.createXMLHttpRequest = function() {
		var request = false;
		if(window.XMLHttpRequest) {
			request = new XMLHttpRequest();
			if(request.overrideMimeType) {
				request.overrideMimeType('text/xml');
			}
		} else if(window.ActiveXObject) {
			var versions = ['Microsoft.XMLHTTP', 'MSXML.XMLHTTP', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP.7.0', 'Msxml2.XMLHTTP.6.0', 'Msxml2.XMLHTTP.5.0', 'Msxml2.XMLHTTP.4.0', 'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP'];
			for(var i=0; i<versions.length; i++) {
				try {
					request = new ActiveXObject(versions[i]);
					if(request) {
						return request;
					}
				} catch(e) {}
			}
		}
		return request;
	};
	aj.XMLHttpRequest = aj.createXMLHttpRequest();
	aj.showLoading = function() {
		if(aj.waitId && (aj.XMLHttpRequest.readyState != 4 || aj.XMLHttpRequest.status != 200)) {
			aj.waitId.style.display = '';
			aj.waitId.innerHTML = '<span><div class="loadicon vm"></div> ' + aj.loading + '</span>';
		}
	};
	aj.processHandle = function() {
		if(aj.XMLHttpRequest.readyState == 4 && aj.XMLHttpRequest.status == 200) {
			if(aj.waitId) {
				aj.waitId.style.display = 'none';
			}
			if(aj.recvType == 'HTML') {
				aj.resultHandle(aj.XMLHttpRequest.responseText, aj);
			} else if(aj.recvType == 'XML') {
				if(!aj.XMLHttpRequest.responseXML || !aj.XMLHttpRequest.responseXML.lastChild || aj.XMLHttpRequest.responseXML.lastChild.localName == 'parsererror') {
					aj.resultHandle('' , aj);
				} else {
					aj.resultHandle(aj.XMLHttpRequest.responseXML.lastChild.firstChild.nodeValue, aj);
				}
			} else if(aj.recvType == 'JSON') {
				var s = null;
				try {
					s = (new Function("return ("+aj.XMLHttpRequest.responseText+")"))();
				} catch (e) {
					s = null;
				}
				aj.resultHandle(s, aj);
			}
		}
	};
	aj.get = function(targetUrl, resultHandle) {
		targetUrl = hostconvert(targetUrl);
		setTimeout(function(){aj.showLoading()}, 250);
		aj.targetUrl = targetUrl;
		aj.XMLHttpRequest.onreadystatechange = aj.processHandle;
		aj.resultHandle = resultHandle;
		var attackevasive = isUndefined(attackevasive) ? 0 : attackevasive;
		if(window.XMLHttpRequest) {
			aj.XMLHttpRequest.open('GET', aj.targetUrl);
			aj.XMLHttpRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			aj.XMLHttpRequest.send(null);
		} else {
			aj.XMLHttpRequest.open("GET", targetUrl, true);
			aj.XMLHttpRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			aj.XMLHttpRequest.send();
		}
	};
	aj.post = function(targetUrl, sendString, resultHandle) {
		targetUrl = hostconvert(targetUrl);
		setTimeout(function(){aj.showLoading()}, 250);
		aj.targetUrl = targetUrl;
		aj.sendString = sendString;
		aj.XMLHttpRequest.onreadystatechange = aj.processHandle;
		aj.resultHandle = resultHandle;
		aj.XMLHttpRequest.open('POST', targetUrl);
		aj.XMLHttpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		aj.XMLHttpRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		aj.XMLHttpRequest.send(aj.sendString);
	};
	aj.getJSON = function(targetUrl, resultHandle) {
		aj.setRecvType('JSON');
		aj.get(targetUrl+'&ajaxdata=json', resultHandle);
	};
	aj.getHTML = function(targetUrl, resultHandle) {
		aj.setRecvType('HTML');
		aj.get(targetUrl+'&ajaxdata=html', resultHandle);
	};
	return aj;
}

function portal_flowlazyload() {
	var obj = this;
	var times = 0;
	var processing = false;
	this.getOffset = function (el, isLeft) {
		var retValue = 0 ;
		while (el != null) {
			retValue += el["offset" + (isLeft ? "Left" : "Top" )];
			el = el.offsetParent;
		}
		return retValue;
	};
	this.attachEvent = function (obj, evt, func, eventobj) {
		eventobj = !eventobj ? obj : eventobj;
		if(obj.addEventListener) {
			obj.addEventListener(evt, func, false);
		} else if(eventobj.attachEvent) {
			obj.attachEvent('on' + evt, func);
		}
	};
	this.removeElement = function (_element) {
		var _parentElement = _element.parentNode;
		if(_parentElement) {
			_parentElement.removeChild(_element);
		}
	};
	this.showNextPage = function() {
		var scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
		var offsetTop = this.getOffset(document.getElementsByClassName('page')[0]);
		// 没有在进行的 Ajax 翻页或者 Ajax 翻页少于 10 次才翻页, 为了避免重复请求以及无限下拉导致的 DOM 问题
		// Todo: 大数据量站点测试下拉刷新合理范围, 适度放宽限制
		if (!processing && times <= 9 && offsetTop > document.documentElement.clientHeight && (offsetTop - scrollTop < document.documentElement.clientHeight)) {
			processing = true;
			times++;
			var x = new Ajax();
			x.get('portal.php?mod=index&page=' + ++flowpage + '&inajax=1', function(s) {
				if(s.indexOf(mobnodata) !== -1) {
					var infoli = s.match(/<li>([\w\W]+)<\/li>/g);
					var pgdiv = s.match(/<div class="pg">([\w\W]+)<\/div>/g);
					if (infoli !== null && pgdiv !== null) {
						document.getElementsByClassName('wzlist')[0].insertAdjacentHTML('beforeend', infoli);
						document.getElementsByClassName('page')[0].insertAdjacentHTML('afterend', pgdiv);
						obj.removeElement(document.getElementsByClassName('page')[0]);
						page.converthtml();
						processing = false;
					}
				}
			});
		}
	};
	this.attachEvent(window, 'scroll', function(){obj.showNextPage();});
}

function explode(sep, string) {
	return string.split(sep);
}

function setCopy(text, msg) {
	var cp = document.createElement('textarea');
	cp.style.fontSize = '12pt';
	cp.style.border = '0';
	cp.style.padding = '0';
	cp.style.margin = '0';
	cp.style.position = 'absolute';
	cp.style.left = '-9999px';
	var yPosition = window.pageYOffset || document.documentElement.scrollTop;
	cp.style.top = yPosition + 'px';
	cp.setAttribute('readonly', '');
	text = text.replace(/[\xA0]/g, ' ');
	cp.value = text;
	document.getElementById('append_parent').appendChild(cp);
	cp.select();
	cp.setSelectionRange(0, cp.value.length);
	try {
		var success = document.execCommand('copy', false, null);
	} catch(e) {
		var success = false;
	}
	document.getElementById('append_parent').removeChild(cp);

	if (success) {
		if (msg) {
			popup.open(msg, 'alert');
		}
	} else if (BROWSER.ie) {
		var r = clipboardData.setData('Text', text);
		if (r) {
			if (msg) {
				popup.open(msg, 'alert');
			}
		} else {
                       popup.open(lng['copy_failed'], 'alert');
		}
	} else {
               popup.open(lng['copy_failed'], 'alert');
	}
}

function copycode(obj) {
       setCopy(obj.textContent, lng['code_clipboard']);
}

function setanswer(tid, pid, from, formhash){
       popup.open(lng['best_answer_sure'],'confirm','forum.php?mod=misc&action=bestanswer&tid=' + tid + '&pid=' + pid + '&from=' + from + '&bestanswersubmit=yes&formhash='+formhash)
}

function submitpostpw(pid, tid) {
       var obj = document.getElementById('postpw_' + pid);
       setcookie('postpw_' + pid, hex_md5(obj.value));
       if(!tid) {
               location.href = location.href;
       } else {
               location.href = 'forum.php?mod=viewthread&tid='+tid;
       }
}
