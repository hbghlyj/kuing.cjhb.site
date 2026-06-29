var _J$ = jQuery.noConflict();
window.$ = function () {
	if (arguments.length === 1 && typeof arguments[0] === 'string') {
		if (arguments[0] === '') {
			return null;
		}
		if (arguments[0].match(/^[a-zA-Z_][a-zA-Z0-9-_]*$/) &&
		    !isNativeHtmlTag(arguments[0])) {
			return _D$.apply(null, arguments);
		}
	}
	return _J$.apply(null, arguments);
};

function jqueryProperty() {
	for (var _prop in _J$) {
		if (_J$.hasOwnProperty(_prop) && typeof _J$[_prop] === 'function') {
			$[_prop] = _J$[_prop];
		}
	}
}

function isNativeHtmlTag(tag) {
	if (!tag || typeof tag !== 'string') return false;
	var t = tag.trim().toLowerCase();
	var el = document.createElement(t);
	return el instanceof window.HTMLUnknownElement === false;
}

jqueryProperty();

if (typeof _attachEvent == 'undefined') {
	window._attachEvent = function(obj, evt, func, eventobj) {
		eventobj = eventobj || obj;
		if(obj.addEventListener) {
			obj.addEventListener(evt, func, false);
		} else if(eventobj.attachEvent) {
			obj.attachEvent('on' + evt, func);
		}
	};
}

var platform = navigator.platform;
var ua = navigator.userAgent;
var ios = /iPhone|iPad|iPod/.test(platform) && ua.indexOf( "AppleWebKit" ) > -1;
var andriod = ua.indexOf( "Android" ) > -1;

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
				selector += '<option value="' + i + '" ' + (i == curpage ? 'selected' : '') + '>' + $L('page_number', [i]) + '</option>';
			}
			selector += '</select>';
			selector += '<span>' + $L('page_number', [curpage]) + '</span>';
		}

		var pgobj = qSel('div.pg');
		pgobj.classList.remove('pg');
		pgobj.classList.add('page');
		pgobj.innerHTML = '<a href="'+ prevpagehref +'">' + $L('page_prev') + '</a>'+ selector +'<a href="'+ nextpagehref +'">' + $L('page_next') + '</a>';
		qSel('#dumppage').addEventListener('change', function() {
			window.location.href = multipage_url + 'page=' + this.value;
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
			parentnode.append('<div class="error_text">' + $L('click_reload') + '</div>');
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
				pop = '<div class="tip"><dt>'+ pop +'</dt><dd><input class="button2" type="button" value="' + $L('confirm') + '" onclick="popup.close();"></dd></div>'
			} else if(type == 'confirm') {
				pop = '<div class="tip"><dt>'+ pop +'</dt><dd><a class="button" href="'+ url +'">' + $L('confirm') + '</a> <button onclick="popup.close();" class="button">' + $L('cancel') + '</a></dd></div>'
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
				if(typeof window.initAllSortSel == 'function') {
					setTimeout(function() {
						window.initAllSortSel();
					}, 300);
				}
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
				if(typeof window.initAllSortSel == 'function') {
					setTimeout(function() {
						window.initAllSortSel();
					}, 300);
				}
			})
			.error(function() {
				popup.open($L('forum_submit_error'), 'alert');
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
		$('#mask').off().on('click', function() {
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

function getFirstFrame(file, callback) {
	const video = document.createElement('video');
	video.preload = 'metadata';
	video.muted = true;
	video.playsInline = true;
	video.crossOrigin = 'anonymous';

	const canvas = document.createElement('canvas');
	const ctx = canvas.getContext('2d');

	video.onloadedmetadata = () => {
		video.currentTime = 0;
	};

	video.onseeked = () => {
		canvas.width = video.videoWidth;
		canvas.height = video.videoHeight;
		ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
		const dataURL = canvas.toDataURL('image/png');
		callback(dataURL);
	};

	video.src = URL.createObjectURL(file);
}

function addImageZoomStyles() {
	if(document.getElementById('imgzoom_styles')) return;
	
	var style = document.createElement('style');
	style.id = 'imgzoom_styles';
	style.textContent = `
	.imgzoom_pop {
		display: none;
	}
	.imgzoom_dialog {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: rgba(0, 0, 0, 0.98);
		z-index: 999999;
		/* 防止页面缩放 */
		touch-action: none;
	}
	/* 确保自动创建的弹窗容器也有足够高的z-index */
	#imgzoom_pop_popmenu {
		z-index: 999999 !important;
	}
	.imgzoom_content {
		position: absolute;
		top: 0;
		bottom: 104px;
		left: 0;
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: auto;
		padding: 20px;
		box-sizing: border-box;
	}
	.imgzoom_footer {
		position: absolute;
		bottom: 60px;
		left: 0;
		width: 100%;
		height: 44px;
		background: rgba(0, 0, 0, 0.8);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 20px;
	}
	.imgzoom_rotate, .imgzoom_opennew, .imgzoom_closebtn {
		color: #fff;
		font-size: 14px;
		cursor: pointer;
		padding: 8px 16px;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 4px;
		text-decoration: none;
	}
	.imgzoom_rotate:active, .imgzoom_opennew:active, .imgzoom_closebtn:active {
		background: rgba(255, 255, 255, 0.3);
	}
	#imgzoom_img {
		max-width: 100%;
		max-height: 100%;
		transition: transform 0.1s ease;
	}
	#mask {
		cursor: pointer;
		z-index: 999998;
		background: rgba(0, 0, 0, 0.98);
	}
	`;
	document.head.appendChild(style);
}

var currentZoomImgUrl = '';

function openImageInNewWindow() {
	window.open(currentZoomImgUrl, '_blank');
	popup.close();
}

function zoom(imgObj, zoomfile, nocover, pn, showexif) {
	addImageZoomStyles();
	
	var imgUrl = zoomfile || imgObj.getAttribute('zoomfile') || imgObj.src;
	if(!imgUrl) return;

	currentZoomImgUrl = imgUrl;

	var zoomHtml = '<div id="imgzoom_pop" class="imgzoom_pop" popup="true" style="display:none;">' 
		+ '<div class="imgzoom_dialog">' 
		+ '<div class="imgzoom_content">' 
		+ '<img id="imgzoom_img" src="' + imgUrl + '" style="transform-origin: center center; max-width: 100%; max-height: 100%; transform: scale(1) rotate(0deg);" />' 
		+ '</div>' 
		+ '<div class="imgzoom_footer f_f">' 
		+ '<span class="imgzoom_rotate" ontouchend="rotateImage(); return false;">'+$L('img_roate')+'</span>'
		+ '<span class="imgzoom_opennew" ontouchend="openImageInNewWindow(); return false;">'+$L('open_newwindow')+'</span>'
		+ '<span class="imgzoom_closebtn" ontouchend="closeImageZoom();">'+$L('close')+'</span>'
		+ '</div>' 
		+ '</div>' 
		+ '</div>';

	var zoomContainer = document.getElementById('imgzoom_pop');
	if(zoomContainer) {
		zoomContainer.parentNode.removeChild(zoomContainer);
	}
	document.body.insertAdjacentHTML('beforeend', zoomHtml);
	popup.open($('#imgzoom_pop'));

	setTimeout(function() {
		var actualImg = document.querySelector('#imgzoom_pop_popmenu #imgzoom_img');
		if(actualImg) {
			if(!actualImg.style.transform) {
				actualImg.style.transform = 'scale(1) rotate(0deg)';
			}

			initImageZoomRotate();
		}
	}, 0);
}

function closeImageZoom() {
	var e = window.event || arguments.callee.caller.arguments[0];
	if(e) {
		e.stopPropagation();
		e.preventDefault();
	}

	popup.close();

	setTimeout(function() {
	}, 100);
	
	return false;
}

function initImageZoomRotate() {
	var img = document.querySelector('#imgzoom_pop_popmenu #imgzoom_img') || document.getElementById('imgzoom_img');
	if(!img) return;

	if(!img.style.transform) {
		img.style.transform = 'scale(1) rotate(0deg)';
	}
	
	var scale = 1;
	var rotate = 0;
	var startScale = 1;
	var startRotate = 0;

	var newImg = img.cloneNode(true);
	img.parentNode.replaceChild(newImg, img);
	img = newImg;

	img.addEventListener('touchstart', function(e) {
		if(e.touches.length === 2) {
			startScale = scale;
			startRotate = rotate;
		}
	}, { passive: true });
	
	img.addEventListener('touchmove', function(e) {
		if(e.touches.length === 2) {
			var dist1 = Math.hypot(
				e.touches[0].clientX - e.touches[1].clientX,
				e.touches[0].clientY - e.touches[1].clientY
			);
			var dist2 = Math.hypot(
				e.touches[0].pageX - e.touches[1].pageX,
				e.touches[0].pageY - e.touches[1].pageY
			);
			scale = startScale * (dist2 / dist1);

			var angle1 = Math.atan2(
				e.touches[0].clientY - e.touches[1].clientY,
				e.touches[0].clientX - e.touches[1].clientX
			);
			var angle2 = Math.atan2(
				e.touches[0].pageY - e.touches[1].pageY,
				e.touches[0].pageX - e.touches[1].pageX
			);
			rotate = startRotate + (angle2 - angle1) * (180 / Math.PI);

			img.style.transform = 'scale(' + scale + ') rotate(' + rotate + 'deg)';
		}
	}, { passive: false });
}

function rotateImage() {
	var img = document.querySelector('#imgzoom_pop_popmenu #imgzoom_img') || document.getElementById('imgzoom_img');
	if(!img) return;

	var currentTransform = img.style.transform || 'scale(1) rotate(0deg)';

	var scaleMatch = currentTransform.match(/scale\(([\d.]+)\)/);
	var rotateMatch = currentTransform.match(/rotate\(([\d.]+)deg\)/);
	
	var scale = scaleMatch ? parseFloat(scaleMatch[1]) : 1;
	var currentRotate = rotateMatch ? parseFloat(rotateMatch[1]) : 0;

	var newRotate = currentRotate + 90;

	img.style.transform = 'scale(' + scale + ') rotate(' + newRotate + 'deg)';
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

	$(document).on('click', 'img[zoomfile]', function() {
		zoom(this);
		return false;
	});
});

function ajaxget(url, showid, waitid, loading, display, recall) {
	var url = url + '&inajax=1&ajaxtarget=' + showid;
	$.ajax({
		type : 'GET',
		url : url,
		dataType : 'xml',
	}).success(function(s) {
		$('#'+showid).html(s.lastChild.firstChild.nodeValue);
		$("[ajaxtarget]").off('touchstart').on('touchstart', function(e) {
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
			popup.open($L('copy_failed2'), 'alert');
		}
	} else {
		popup.open($L('copy_failed2'), 'alert');
	}
}

function copycode(obj) {
	setCopy(obj.textContent, $L('copy_clipboard_success'));
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

var mobileDiy = {
    setPos: function () {
        var len = this.moveableArea.length;
        var cssStr = '';
        for (var i = 0; i < len; i++) {
            var el = this.moveableArea[i];
            if (el == null || typeof el == 'undefined') return false;
            var id = el.id;
            var s = parent.$(id).innerHTML;
            s = s.replace(/<div class="edit.+?<\/div>/gi, '');
            s = s.replace(/<div class="block-name.+?<\/div>/gi, '');
            el.innerHTML = s;
            if(parent.spaceDiy) {
                cssStr += parent.spaceDiy.getSpacecssStr('#' + parent.$(id).childNodes[0].id);
            }
        }
        if(cssStr) {
            document.getElementById('diy_style').innerHTML = cssStr;
        }
    },
    init: function (tpldir, tplfile, diysign) {
        this.moveableArea = $C('area', document.body, 'div');
        var divs = "";
        var len = this.moveableArea.length;
        for (var i = 0; i < len; i++) {
            var el = this.moveableArea[i];
            if (el == null || typeof el == 'undefined') return false;
            divs += el.outerHTML;
            el.innerHTML = '';
            var id = el.id;
            setInterval(function () {
                mobileDiy.setPos();
            }, 2000);
        }
        parent.$('panel').innerHTML = divs;

        if(parent.$('diy_style') && document.getElementById('diy_style')) {
            parent.$('diy_style').innerHTML = document.getElementById('diy_style').innerHTML;
        }
        if(parent.$('diyform')) {
            parent.$('diyform').template.value = tplfile;
            parent.$('diyform').tpldirectory.value = tpldir;
            parent.$('diyform').diysign.value = diysign;
            parent.$('preview_title').innerHTML = document.title;
        }
        parent.start_diy();
    },

}

function filterTextNode(list) {
	var newlist = [];
	for(var i=0; i<list.length; i++) {
		if (list[i].nodeType == 1) {
			newlist.push(list[i]);
		}
	}
	return newlist;
}
function footlink() {
	var mfootlink = document.querySelectorAll("#mfoot a");
	for (var i = 0; i < mfootlink.length; i++) {
		mfootlink[i].setAttribute("i", i);
		mfootlink[i].onclick = function() {
			setcookie('mfootlink', this.getAttribute("i"));
		}
		if(mlast !== '' && mlast != i && mfootlink[i].classList.contains('mon')) {
			mfootlink[i].classList.remove('mon');
		}
	};
	if(mlast !== '' && mfootlink[mlast]) {
		mfootlink[mlast].classList.add("mon");
	}

	if(ios) {
		document.querySelectorAll('.foot a.mon span.foot-ico img').forEach(function (obj) {
			obj.style.transform = 'translateX(-200px) translateZ(0px)';
		});
		document.querySelectorAll('.foot a.foot-post span.foot-ico img').forEach(function (obj) {
			obj.style.transform = 'translateX(-200px) translateZ(0px)';
		});
	}
}

function initdhnav(containerSelector = '#dhnavs_li', activeClass = 'mon', customOptions = {}) {
    const container = document.querySelector(containerSelector);
    if (!container) {
        console.warn('Swiper容器不存在:', containerSelector);
        return null;
    }

    const activeElement = container.querySelector('.' + activeClass);
    let initialSlide = 0;

    if (activeElement) {
        const rect = activeElement.getBoundingClientRect();
        const elementLeft = rect.left;
        const elementWidth = activeElement.offsetWidth;
        const windowWidth = window.innerWidth;

        const siblings = Array.from(container.getElementsByClassName(activeClass));
        const elementIndex = siblings.indexOf(activeElement);

        initialSlide = (elementLeft + elementWidth >= windowWidth) ? elementIndex : 0;
    }

    const swiperOptions = {
        freeMode: true,
        slidesPerView: 'auto',
        initialSlide: initialSlide,
        onTouchMove: () => { Discuz_Touch_on = 0; },
        onTouchEnd: () => { Discuz_Touch_on = 1; },
        ...customOptions
    };

    return new Swiper(containerSelector, swiperOptions);
}
function home_passwordShow(value) {
    const spanPassword = document.getElementById('span_password');
    const tbSelectgroup = document.getElementById('tb_selectgroup');
    if(value == 4) {
        spanPassword.style.display= '';
        tbSelectgroup.style.display = 'none';
    } else if(value == 2) {
        spanPassword.style.display = 'none';
        tbSelectgroup.style.display = '';
    } else {
        spanPassword.style.display = 'none';
        tbSelectgroup.style.display = 'none';
    }
}

function home_getgroup(gid) {
    if (gid) {
        const url = `home.php?mod=spacecp&ac=privacy&inajax=1&op=getgroup&gid=${encodeURIComponent(gid)}`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text();
            })
            .then(s => {
                const targetNames = document.getElementById('target_names');
                if (targetNames) {
                    targetNames.innerHTML += s + ',';
                } else {
                    console.warn('未找到ID为target_names的元素');
                }
            })
            .catch(error => {
                console.error('请求失败:', error);
            });
    }
}

_attachEvent(window, 'load', footlink, document);
if (typeof loadAvatar == 'function') {
	_attachEvent(window, 'load', loadAvatar, document);
}

var mlast = getcookie('mfootlink');

function showmobilecalendar(event, controlid, addtime, startdate, enddate, halfhour, recall) {
	if(event) {
		event.preventDefault();
	}
	if(!controlid) {
		return;
	}

	addtime = !!addtime;
	halfhour = !!halfhour;
	startdate = startdate ? parsedate(startdate) : null;
	enddate = enddate ? parsedate(enddate) : null;

	var today = new Date();
	var current = controlid.value ? parsedate(controlid.value) : today;
	var selectedYear = current.getFullYear();
	var selectedMonth = current.getMonth();
	var selectedDay = current.getDate();
	var selectedHour = current.getHours();
	var selectedMinute = current.getMinutes();
	var activeYear = selectedYear;
	var activeMonth = selectedMonth;
	var activeDay = selectedDay;
	var dom = createMobileCalendarDom();
	var mask = dom.mask;
	var container = dom.container;
	var content = container.querySelector('.discuz-calendar-content');
	var title = container.querySelector('.discuz-calendar-title');
	var daysGrid;

	title.textContent = mobileCalendarLang(addtime ? 'select_datetime' : 'select_date', addtime ? '选择日期时间' : '选择日期');
	content.innerHTML = '';
	content.appendChild(createDateSection());
	if(addtime) {
		content.appendChild(createTimeSection());
	}

	mask.style.display = 'block';
	container.style.display = 'flex';
	setTimeout(function() {
		container.classList.add('show');
	}, 10);
	lockBodyScroll(true);

	mask.onclick = closePicker;
	container.querySelector('.discuz-calendar-cancel').onclick = closePicker;
	container.querySelector('.discuz-calendar-confirm').onclick = function() {
		var result = selectedYear + '-' + zerofill(selectedMonth + 1) + '-' + zerofill(selectedDay);
		if(addtime) {
			result += ' ' + zerofill(selectedHour) + ':' + zerofill(selectedMinute);
		}
		controlid.value = result;
		if(typeof recall == 'function') {
			recall();
		} else if(recall) {
			eval(recall);
		}
		closePicker();
	};

	updateCalendar();

	function createDateSection() {
		var dateSection = document.createElement('div');
		dateSection.className = 'discuz-calendar-date';
		dateSection.appendChild(createSwitcher('year'));
		dateSection.appendChild(createSwitcher('month'));

		var weekHeader = document.createElement('div');
		weekHeader.className = 'discuz-calendar-week';
		var weekDays = [
			mobileCalendarLang('sun', '日'),
			mobileCalendarLang('mon', '一'),
			mobileCalendarLang('tue', '二'),
			mobileCalendarLang('wed', '三'),
			mobileCalendarLang('thu', '四'),
			mobileCalendarLang('fri', '五'),
			mobileCalendarLang('sat', '六')
		];
		for(var i = 0; i < weekDays.length; i++) {
			var wd = document.createElement('span');
			wd.textContent = weekDays[i];
			weekHeader.appendChild(wd);
		}
		dateSection.appendChild(weekHeader);

		daysGrid = document.createElement('div');
		daysGrid.className = 'discuz-calendar-days';
		dateSection.appendChild(daysGrid);
		return dateSection;
	}

	function createSwitcher(type) {
		var row = document.createElement('div');
		row.className = 'discuz-calendar-switcher';

		var prev = document.createElement('a');
		prev.href = 'javascript:;';
		prev.className = 'discuz-calendar-nav';
		prev.innerHTML = '&lsaquo;';
		prev.onclick = function() {
			if(type == 'year') {
				selectedYear--;
			} else if(--selectedMonth < 0) {
				selectedMonth = 11;
				selectedYear--;
			}
			updateCalendar();
		};

		var display = document.createElement('span');
		display.className = 'discuz-calendar-' + type;

		var next = document.createElement('a');
		next.href = 'javascript:;';
		next.className = 'discuz-calendar-nav';
		next.innerHTML = '&rsaquo;';
		next.onclick = function() {
			if(type == 'year') {
				selectedYear++;
			} else if(++selectedMonth > 11) {
				selectedMonth = 0;
				selectedYear++;
			}
			updateCalendar();
		};

		row.appendChild(prev);
		row.appendChild(display);
		row.appendChild(next);
		return row;
	}

	function createTimeSection() {
		var timeSection = document.createElement('div');
		timeSection.className = 'discuz-calendar-time';

		var timeLabel = document.createElement('div');
		timeLabel.className = 'discuz-calendar-time-title';
		timeLabel.textContent = mobileCalendarLang('select_time', '选择时间');
		timeSection.appendChild(timeLabel);

		var timeRow = document.createElement('div');
		timeRow.className = 'discuz-calendar-time-row';
		timeRow.appendChild(createTimeSelect('hour', 0, 23, 1, selectedHour, function(value) {
			selectedHour = value;
		}));
		timeRow.appendChild(createTimeSelect('min', 0, 59, halfhour ? 30 : 1, selectedMinute, function(value) {
			selectedMinute = value;
		}));
		timeSection.appendChild(timeRow);
		return timeSection;
	}

	function createTimeSelect(langKey, min, max, step, selected, onchange) {
		var select = document.createElement('select');
		select.className = 'discuz-calendar-time-select';
		for(var value = min; value <= max; value += step) {
			var option = document.createElement('option');
			option.value = value;
			option.textContent = zerofill(value) + mobileCalendarLang(langKey, langKey == 'hour' ? '时' : '分');
			if(value == selected) {
				option.selected = true;
			}
			select.appendChild(option);
		}
		select.onchange = function() {
			onchange(parseInt(this.value));
		};
		return select;
	}

	function updateCalendar() {
		container.querySelector('.discuz-calendar-year').textContent = selectedYear + mobileCalendarLang('year', '年');
		container.querySelector('.discuz-calendar-month').textContent = (selectedMonth + 1) + mobileCalendarLang('month', '月');
		daysGrid.innerHTML = '';

		var firstDay = new Date(selectedYear, selectedMonth, 1);
		var startDay = firstDay.getDay();
		var daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();

		for(var i = 0; i < startDay; i++) {
			var empty = document.createElement('div');
			empty.className = 'discuz-calendar-empty';
			daysGrid.appendChild(empty);
		}

		for(var day = 1; day <= daysInMonth; day++) {
			daysGrid.appendChild(createDayCell(day));
		}
	}

	function createDayCell(day) {
		var dayCell = document.createElement('a');
		dayCell.href = 'javascript:;';
		dayCell.className = 'discuz-calendar-day';
		dayCell.textContent = day;

		var currentDate = new Date(selectedYear, selectedMonth, day);
		var isToday = currentDate.getFullYear() === today.getFullYear() && currentDate.getMonth() === today.getMonth() && currentDate.getDate() === today.getDate();
		var isSelected = day === activeDay && selectedMonth === activeMonth && selectedYear === activeYear;
		var isExpired = (enddate && currentDate.getTime() > enddate.getTime()) || (startdate && currentDate.getTime() < startdate.getTime());

		if(isSelected) {
			dayCell.classList.add('selected');
		} else if(isToday) {
			dayCell.classList.add('today');
		} else if(isExpired) {
			dayCell.classList.add('disabled');
		}

		if(!isExpired || isSelected) {
			dayCell.onclick = function() {
				selectedDay = day;
				activeYear = selectedYear;
				activeMonth = selectedMonth;
				activeDay = day;
				updateCalendar();
			};
		}
		return dayCell;
	}

	function closePicker() {
		container.classList.remove('show');
		setTimeout(function() {
			container.style.display = 'none';
			mask.style.display = 'none';
			content.innerHTML = '';
			lockBodyScroll(false);
		}, 300);
	}
}

function createMobileCalendarDom() {
	var mask = document.querySelector('.discuz-calendar-mask');
	var container = document.querySelector('.discuz-calendar-popup');
	if(mask && container) {
		return {mask: mask, container: container};
	}

	mask = document.createElement('div');
	mask.className = 'discuz-calendar-mask';

	container = document.createElement('div');
	container.className = 'discuz-calendar-popup';
	container.innerHTML = '<div class="discuz-calendar-header">'
		+ '<a href="javascript:;" class="discuz-calendar-cancel">' + mobileCalendarLang('cancel', '取消') + '</a>'
		+ '<span class="discuz-calendar-title"></span>'
		+ '<a href="javascript:;" class="discuz-calendar-confirm">' + mobileCalendarLang('confirm', '确定') + '</a>'
		+ '</div><div class="discuz-calendar-content"></div>';

	document.body.appendChild(mask);
	document.body.appendChild(container);
	return {mask: mask, container: container};
}

function mobileCalendarLang(key, fallback) {
	var value = typeof $L == 'function' ? $L(key) : key;
	return value && value != key ? value : fallback;
}

function parsedate(s) {
	var match = String(s).match(/(\d+)-(\d+)-(\d+)\s*(\d*):?(\d*)/);
	var now = new Date();
	if(!match) {
		return now;
	}
	var year = match[1] > 1899 && match[1] < 2101 ? parseInt(match[1]) : now.getFullYear();
	var month = match[2] > 0 && match[2] < 13 ? parseInt(match[2]) : now.getMonth() + 1;
	var day = match[3] > 0 && match[3] < 32 ? parseInt(match[3]) : now.getDate();
	var hour = match[4] > -1 && match[4] < 24 ? parseInt(match[4] || 0) : 0;
	var minute = match[5] > -1 && match[5] < 60 ? parseInt(match[5] || 0) : 0;
	return new Date(year, month - 1, day, hour, minute);
}

function zerofill(s) {
	s = parseInt(String(s).replace(/(^[\s0]+)|(\s+$)/g, ''), 10);
	s = isNaN(s) ? 0 : s;
	return (s < 10 ? '0' : '') + s;
}

function lockBodyScroll(lock) {
	if(lock) {
		document.documentElement.classList.add('discuz-picker-lock');
		document.body.classList.add('discuz-picker-lock');
	} else {
		document.documentElement.classList.remove('discuz-picker-lock');
		document.body.classList.remove('discuz-picker-lock');
	}
}
window.lockBodyScroll = lockBodyScroll;

(function() {
	var selectState = {
		select: null,
		value: null,
		popup: null,
		mask: null,
		wheel: null,
		openedAt: 0
	};

	function mobileSelectLang(key, fallback) {
		var value = typeof $L == 'function' ? $L(key) : key;
		return value && value != key ? value : fallback;
	}

	function getSelectedText(select) {
		if(!select || !select.options || select.selectedIndex < 0) {
			return '';
		}
		return select.options[select.selectedIndex].text;
	}

	function syncSelectDisplay(select) {
		var wrap = select && select.closest ? select.closest('.sort-sel-wrap') : null;
		var display = wrap ? wrap.querySelector('.sort-sel-show') : null;
		if(display) {
			display.textContent = getSelectedText(select);
			display.classList.toggle('empty', !select.value);
		}
	}

	function wrapSelect(select) {
		if(!select || select.dataset.discuzSelectReady == '1' || select.closest('.sort-sel-wrap')) {
			return;
		}
		select.dataset.discuzSelectReady = '1';

		var wrap = document.createElement('span');
		wrap.className = 'sort-sel-wrap';
		var display = document.createElement('a');
		display.href = 'javascript:;';
		display.className = 'sort-sel-show';

		select.parentNode.insertBefore(wrap, select);
		wrap.appendChild(select);
		wrap.appendChild(display);
		syncSelectDisplay(select);

		display.onclick = function(e) {
			e.preventDefault();
			if(select.disabled) {
				return false;
			}
			openMobileSelect(select);
			return false;
		};
		select.addEventListener('change', function() {
			syncSelectDisplay(select);
		});
	}

	function initAllSortSel(root) {
		root = root && root.querySelectorAll ? root : document;
		var selects = root.querySelectorAll('select.sort_sel');
		for(var i = 0; i < selects.length; i++) {
			wrapSelect(selects[i]);
		}
	}

	function createSelectDom() {
		if(selectState.mask && selectState.popup) {
			return;
		}
		selectState.mask = document.createElement('div');
		selectState.mask.className = 'discuz-select-mask';

		selectState.popup = document.createElement('div');
		selectState.popup.className = 'discuz-select-popup';
		selectState.popup.innerHTML = '<div class="discuz-select-header">'
			+ '<a href="javascript:;" class="discuz-select-cancel">' + mobileSelectLang('cancel', '取消') + '</a>'
			+ '<span class="discuz-select-title">' + mobileSelectLang('select', '请选择') + '</span>'
			+ '<a href="javascript:;" class="discuz-select-confirm">' + mobileSelectLang('confirm', '确定') + '</a>'
			+ '</div><div class="discuz-select-wheel"></div>';

		document.body.appendChild(selectState.mask);
		document.body.appendChild(selectState.popup);
		selectState.wheel = selectState.popup.querySelector('.discuz-select-wheel');
		selectState.mask.onclick = closeMobileSelect;
		selectState.popup.querySelector('.discuz-select-cancel').onclick = closeMobileSelect;
		selectState.popup.querySelector('.discuz-select-confirm').onclick = confirmMobileSelect;
	}

	function openMobileSelect(select) {
		createSelectDom();
		selectState.select = select;
		selectState.value = select.value;
		renderSelectOptions(select);
		selectState.mask.style.display = 'block';
		selectState.popup.style.display = 'block';
		selectState.openedAt = Date.now();
		setTimeout(function() {
			selectState.popup.classList.add('show');
			scrollSelectedOption();
		}, 10);
		lockBodyScroll(true);
	}

	function renderSelectOptions(select) {
		selectState.wheel.innerHTML = '<div class="discuz-select-spacer"></div>';
		for(var i = 0; i < select.options.length; i++) {
			var option = select.options[i];
			if(option.disabled) {
				continue;
			}
			var item = document.createElement('a');
			item.href = 'javascript:;';
			item.className = 'discuz-select-option';
			item.dataset.value = option.value;
			item.textContent = option.text;
			if(option.value == selectState.value) {
				item.classList.add('selected');
			}
			item.onclick = function(e) {
				e.preventDefault();
				selectOption(this.dataset.value);
				return false;
			};
			selectState.wheel.appendChild(item);
		}
		var spacer = document.createElement('div');
		spacer.className = 'discuz-select-spacer';
		selectState.wheel.appendChild(spacer);
	}

	function selectOption(value) {
		selectState.value = value;
		var items = selectState.wheel.querySelectorAll('.discuz-select-option');
		for(var i = 0; i < items.length; i++) {
			items[i].classList.toggle('selected', items[i].dataset.value == value);
		}
		scrollSelectedOption();
	}

	function scrollSelectedOption() {
		var item = selectState.wheel ? selectState.wheel.querySelector('.discuz-select-option.selected') : null;
		if(!item) {
			return;
		}
		var top = item.offsetTop - (selectState.wheel.clientHeight - item.offsetHeight) / 2;
		if(typeof selectState.wheel.scrollTo == 'function') {
			selectState.wheel.scrollTo({top: top, behavior: 'smooth'});
		} else {
			selectState.wheel.scrollTop = top;
		}
	}

	function confirmMobileSelect() {
		if(!selectState.select) {
			return;
		}
		selectState.select.value = selectState.value;
		syncSelectDisplay(selectState.select);
		var event;
		if(typeof Event == 'function') {
			event = new Event('change', {bubbles: true});
		} else {
			event = document.createEvent('HTMLEvents');
			event.initEvent('change', true, false);
		}
		selectState.select.dispatchEvent(event);
		closeMobileSelect();
	}

	function closeMobileSelect() {
		if(!selectState.popup || Date.now() - selectState.openedAt < 80) {
			return;
		}
		selectState.popup.classList.remove('show');
		setTimeout(function() {
			selectState.popup.style.display = 'none';
			selectState.mask.style.display = 'none';
			selectState.wheel.innerHTML = '';
			selectState.select = null;
			lockBodyScroll(false);
		}, 300);
	}

	function observeSortSelects() {
		if(typeof MutationObserver == 'undefined' || !document.body) {
			return;
		}
		var observer = new MutationObserver(function(mutations) {
			for(var i = 0; i < mutations.length; i++) {
				for(var j = 0; j < mutations[i].addedNodes.length; j++) {
					var node = mutations[i].addedNodes[j];
					if(node.nodeType == 1) {
						if(node.matches && node.matches('select.sort_sel')) {
							wrapSelect(node);
						}
						initAllSortSel(node);
					}
				}
			}
		});
		observer.observe(document.body, {childList: true, subtree: true});
	}

	function ready(fn) {
		if(document.readyState == 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	window.initAllSortSel = initAllSortSel;
	ready(function() {
		initAllSortSel();
		observeSortSelects();
	});
})();
