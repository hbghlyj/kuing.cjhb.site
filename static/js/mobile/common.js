var _D$ = window.$;

if (typeof _attachEvent == 'undefined') {
	window._attachEvent = function(obj, evt, func) {
		obj.addEventListener(evt, func, false);
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
		document.querySelectorAll('img').forEach(function(obj) {
			obj.addEventListener('load', function() {
				obj.dataset.zsrc = obj.src;
				if(obj.offsetWidth < 5 && obj.offsetHeight < 10 && getComputedStyle(obj).display != 'none') {
					return errhandle(obj, is_err_t);
				}
				obj.style.display = 'inline';
				obj.style.visibility = 'visible';
				if(obj.offsetWidth > window.innerWidth) {
					obj.style.width = window.innerWidth + 'px';
				}
				obj.parentElement.querySelectorAll('.loading, .error_text').forEach(function(element) {
					element.remove();
				});
			});
			obj.addEventListener('error', function() {
				obj.dataset.zsrc = obj.src;
				errhandle(obj, is_err_t);
			});
		});
	},
	errorhandle : function(obj, is_err_t) {
		if(obj.getAttribute('noerror') == 'true') {
			return;
		}
		obj.style.visibility = 'hidden';
		obj.style.display = 'none';
		var parentnode = obj.parentElement;
		parentnode.querySelectorAll('.loading').forEach(function(element) {
			element.remove();
		});
		var loading = document.createElement('div');
		loading.className = 'loading';
		loading.style.cssText = 'background:url(' + IMGDIR + '/imageloading.gif) no-repeat center center;width:' + parentnode.offsetWidth + 'px;height:' + parentnode.offsetHeight + 'px';
		parentnode.append(loading);
		var loadnums = parseInt(obj.dataset.load) || 0;
		if(loadnums < 3) {
			obj.src = obj.dataset.zsrc;
			obj.dataset.load = ++loadnums;
			return false;
		}
		if(is_err_t) {
			parentnode.querySelectorAll('.loading').forEach(function(element) {
				element.remove();
			});
			var errorText = document.createElement('div');
			errorText.className = 'error_text';
			errorText.textContent = $L('click_reload');
			errorText.addEventListener('click', function retry() {
				errorText.removeEventListener('click', retry);
				errorText.remove();
				obj.dataset.load = 0;
				parentnode.append(loading);
				obj.src = obj.dataset.zsrc;
			});
			parentnode.append(errorText);
		}
		return false;
	}
};

var POPMENU = new Object;
var popup = {
	init : function() {
		var $this = this;
		document.querySelectorAll('.popup').forEach(function(obj) {
			var pop = document.querySelector(obj.getAttribute('href'));
			if(pop && pop.hasAttribute('popup')) {
				pop.style.display = 'none';
				obj.addEventListener('click', function(e) {
					$this.open(pop);
					e.preventDefault();
				});
			}
		});
		this.maskinit();
	},
	maskinit : function() {
		var $this = this;
		var mask = document.getElementById('mask');
		mask.onclick = function() {
			$this.close();
		};
	},

	open : function(pop, type, url) {
		this.close();
		this.maskinit();
		if(typeof pop == 'string') {
			document.getElementById('ntcmsg')?.remove();
			if(type == 'alert') {
				pop = '<div class="tip"><dt>'+ pop +'</dt><dd><input class="button2" type="button" value="' + $L('confirm') + '" onclick="popup.close();"></dd></div>'
			} else if(type == 'confirm') {
				pop = '<div class="tip"><dt>'+ pop +'</dt><dd><a class="button" href="'+ url +'">' + $L('confirm') + '</a> <button onclick="popup.close();" class="button">' + $L('cancel') + '</a></dd></div>'
			}
			document.body.insertAdjacentHTML('beforeend', '<div id="ntcmsg" style="display:none;">'+ pop +'</div>');
			pop = document.getElementById('ntcmsg');
		}
		var popid = pop.id;
		var popupobj = document.getElementById(popid + '_popmenu');
		if(POPMENU[popid]) {
			popupobj.innerHTML = pop.innerHTML;
		} else {
			pop.insertAdjacentHTML('afterend', '<div class="dialogbox" id="'+ popid +'_popmenu">'+ pop.innerHTML +'</div>');
			popupobj = document.getElementById(popid + '_popmenu');
		}
		popupobj.style.width = '';
		popupobj.style.height = '';
		popupobj.style.visibility = 'hidden';
		popupobj.style.display = 'block';
		var left = (window.innerWidth - popupobj.offsetWidth) / 2;
		var top = (document.documentElement.clientHeight - popupobj.offsetHeight) / 2;
		Object.assign(popupobj.style, {position:'fixed',left:left+'px',top:top+'px',zIndex:120,opacity:1,visibility:'visible'});
		Object.assign(document.getElementById('mask').style, {display:'block',width:'100%',height:'100%',position:'fixed',top:0,left:0,background:'black',opacity:'0.2',zIndex:100});
		POPMENU[popid] = pop;
	},
	close : function() {
		document.getElementById('mask').style.display = 'none';
		Object.keys(POPMENU).forEach(function(index) {
			document.getElementById(index + '_popmenu').style.display = 'none';
		});
	}
};

function mobileUploadFiles(settings) {
	Array.from(settings.files || []).forEach(function(file) {
		var formData = new FormData();
		Object.entries(settings.uploadformdata || {}).forEach(function(entry) {
			formData.append(entry[0], entry[1]);
		});
		formData.append(settings.uploadinputname || 'Filedata', file);

		var xhr = new XMLHttpRequest();
		var completed = false;
		var finish = function(success) {
			if(completed) {
				return;
			}
			completed = true;
			var callback = success ? settings.success : settings.error;
			if(typeof callback == 'function') {
				callback(success ? xhr.responseText : xhr);
			}
		};
		if(settings.uploadpercent) {
			xhr.upload.addEventListener('progress', function(event) {
				if(event.lengthComputable) {
					var progress = document.getElementById(settings.uploadpercent);
					if(progress) {
						progress.textContent = Math.ceil(event.loaded / event.total * 100) + '%';
					}
				}
			});
		}
		xhr.addEventListener('load', function() {
			finish(xhr.status >= 200 && xhr.status < 300 && xhr.responseText !== '');
		});
		['error', 'abort', 'timeout'].forEach(function(eventName) {
			xhr.addEventListener(eventName, function() {
				finish(false);
			});
		});
		xhr.open('POST', settings.uploadurl, true);
		xhr.send(formData);
	});
}

var dialog = {
	init : function() {
		document.addEventListener('click', function(event) {
			var obj = event.target.closest('.dialog');
			if(!obj) return;
			event.preventDefault();
			popup.open('<img src="' + IMGDIR + '/imageloading.gif">');
			fetch(obj.href + '&inajax=1')
			.then(function(response) {
				if(!response.ok) throw new Error(response.statusText);
				return response.text();
			})
			.then(function(text) {
				popup.open(text);
				evalscript(text);
				if(typeof window.initAllSortSel == 'function') {
					setTimeout(function() {
						window.initAllSortSel();
					}, 300);
				}
			})
			.catch(function() {
				window.location.href = obj.href;
				popup.close();
			});
		});
	},

};

var formdialog = {
	init : function() {
		document.addEventListener('click', function(event) {
			var obj = event.target.closest('.formdialog');
			if(!obj) return;
			event.preventDefault();
			popup.open('<img src="' + IMGDIR + '/imageloading.gif">');
			var formobj = obj.form;
			var body = new FormData(formobj);
			mobileRequest({
				method: 'POST',
				url: formobj.action + '&handlekey=' + formobj.id + '&inajax=1',
				data: body,
				dataType: 'html'
			})
			.then(function(text) {
				popup.open(text);
				evalscript(text);
				if(typeof window.initAllSortSel == 'function') {
					setTimeout(function() {
						window.initAllSortSel();
					}, 300);
				}
			})
			.catch(function() {
				popup.open($L('forum_submit_error'), 'alert');
			});
		});
	}
};

var DISMENU = new Object;
var display = {
	init : function() {
		var $this = this;
		document.querySelectorAll('.display').forEach(function(obj) {
			var dis = document.querySelector(obj.getAttribute('href'));
			if(dis && dis.hasAttribute('display')) {
				dis.style.display = 'none';
				dis.style.zIndex = 102;
				DISMENU[dis.id] = dis;
				obj.addEventListener('click', function(e) {
					if(in_array(e.target.tagName, ['A', 'IMG', 'INPUT'])) return;
					$this.maskinit();
					if(dis.getAttribute('display') == 'true') {
						dis.style.display = 'block';
						dis.setAttribute('display', 'false');
						Object.assign(document.getElementById('mask').style, {display:'block',width:'100%',height:'100%',position:'fixed',top:0,left:0,background:'transparent',zIndex:100});
					}
					e.preventDefault();
				});
			}
		});
	},
	maskinit : function() {
		var $this = this;
		document.getElementById('mask').onclick = function() {
			$this.hide();
		};
	},
	hide : function() {
		document.getElementById('mask').style.display = 'none';
		Object.values(DISMENU).forEach(function(obj) {
			obj.style.display = 'none';
			obj.setAttribute('display', 'true');
		});
	}
};

function qSel(sel) {
	return document.querySelector(sel);
}

function qSelA(sel) {
	return document.querySelectorAll(sel);
}

function mobileRequest(options) {
	var method = (options.method || options.type || 'GET').toUpperCase();
	var headers = options.headers || {};
	var body = options.body || options.data || null;
	if(body && !(body instanceof FormData) && typeof body === 'object') {
		body = new URLSearchParams(body);
	}
	if((method === 'GET' || method === 'HEAD') && body) {
		var separator = options.url.indexOf('?') === -1 ? '?' : '&';
		options.url += separator + body.toString();
		body = null;
	} else if(body instanceof URLSearchParams) {
		headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
	}
	if(typeof window.KK_addPusherMetadata == 'function') {
		window.KK_addPusherMetadata(body, options.url);
	}
	return fetch(options.url, {method: method, headers: headers, body: body})
		.then(function(response) {
			if(!response.ok) throw new Error(response.statusText || response.status);
			return response.text();
		})
		.then(function(text) {
			return options.dataType === 'json' ? JSON.parse(text) : text;
		});
}

function mobileDom(selector, context) {
	var root = context || document;
	var elements;
	if(selector instanceof mobileDom.Collection) {
		return selector;
	}
	if(selector instanceof Element || selector === document || selector === window) {
		elements = [selector];
	} else if(typeof selector === 'string') {
		elements = Array.from(root.querySelectorAll(selector));
	} else {
		elements = Array.from(selector || []);
	}
	return new mobileDom.Collection(elements);
}

mobileDom.Collection = function(elements) {
	this.elements = elements;
	this.length = elements.length;
	for(var i = 0; i < elements.length; i++) this[i] = elements[i];
};

mobileDom.Collection.prototype.each = function(callback) {
	this.elements.forEach(function(element, index) { callback.call(element, index, element); });
	return this;
};
mobileDom.Collection.prototype.on = function(events, selector, callback) {
	if(typeof selector === 'function') {
		callback = selector;
		selector = null;
	}
	return this.each(function() {
		events.split(/\s+/).forEach(function(eventName) {
			this.addEventListener(eventName, function(event) {
				var target = selector ? event.target.closest(selector) : this;
				if(target && (!selector || this.contains(target))) callback.call(target, event);
			}.bind(this));
		}, this);
	});
};
mobileDom.Collection.prototype.css = function(property, value) {
	if(typeof property === 'string' && value === undefined) {
		return this[0] ? (property.indexOf('-') !== -1 ? getComputedStyle(this[0]).getPropertyValue(property) : getComputedStyle(this[0])[property]) : undefined;
	}
	return this.each(function() {
		if(typeof property === 'string') {
			if(property.indexOf('-') !== -1) this.style.setProperty(property, value);
			else this.style[property] = value;
		} else {
			Object.keys(property).forEach(function(name) {
				if(name.indexOf('-') !== -1) this.style.setProperty(name, property[name]);
				else this.style[name] = property[name];
			}, this);
		}
	});
};
mobileDom.Collection.prototype.attr = function(name, value) {
	if(value === undefined) return this[0] ? this[0].getAttribute(name) : undefined;
	return this.each(function() {
		if(value === false || value === null) {
			this.removeAttribute(name);
			if(name in this && typeof this[name] === 'boolean') this[name] = false;
		} else {
			this.setAttribute(name, value);
			if(name in this && typeof this[name] === 'boolean') this[name] = true;
		}
	});
};
mobileDom.Collection.prototype.prop = function(name, value) {
	if(value === undefined) return this[0] ? this[0][name] : undefined;
	return this.each(function() { this[name] = value; });
};
mobileDom.Collection.prototype.val = function(value) {
	if(value === undefined) return this[0] ? this[0].value : undefined;
	return this.each(function() { this.value = value; });
};
mobileDom.Collection.prototype.html = function(value) {
	if(value === undefined) return this[0] ? this[0].innerHTML : undefined;
	return this.each(function() { this.innerHTML = value; });
};
mobileDom.Collection.prototype.text = function(value) {
	if(value === undefined) return this[0] ? this[0].textContent : undefined;
	return this.each(function() { this.textContent = value; });
};
mobileDom.Collection.prototype.append = function(value) {
	return this.each(function() {
		if(typeof value === 'string') this.insertAdjacentHTML('beforeend', value);
		else this.append(value.cloneNode ? value.cloneNode(true) : value);
	});
};
mobileDom.Collection.prototype.remove = function() { return this.each(function() { this.remove(); }); };
mobileDom.Collection.prototype.show = function() { return this.css('display', ''); };
mobileDom.Collection.prototype.hide = function() { return this.css('display', 'none'); };
mobileDom.Collection.prototype.addClass = function(name) { return this.each(function() { this.classList.add.apply(this.classList, name.split(/\s+/)); }); };
mobileDom.Collection.prototype.removeClass = function(name) { return this.each(function() { this.classList.remove.apply(this.classList, name.split(/\s+/)); }); };
mobileDom.Collection.prototype.toggleClass = function(name, force) { return this.each(function() { this.classList.toggle(name, force); }); };
mobileDom.Collection.prototype.hasClass = function(name) { return !!this[0] && this[0].classList.contains(name); };
mobileDom.Collection.prototype.click = function(callback) { return callback ? this.on('click', callback) : this.each(function() { this.click(); }); };
mobileDom.Collection.prototype.focus = function() { return this.each(function() { this.focus(); }); };
mobileDom.Collection.prototype.children = function(selector) {
	return mobileDom(Array.from(this[0] ? this[0].children : []).filter(function(element) { return !selector || element.matches(selector); }));
};
mobileDom.Collection.prototype.find = function(selector) { return mobileDom(selector, this[0] || document); };
mobileDom.Collection.prototype.closest = function(selector) { return mobileDom(this[0] ? this[0].closest(selector) : []); };
mobileDom.Collection.prototype.eq = function(index) { return mobileDom(this.elements[index < 0 ? this.length + index : index] || []); };
mobileDom.Collection.prototype.siblings = function(selector) {
	if(!this[0] || !this[0].parentElement) return mobileDom([]);
	return mobileDom(Array.from(this[0].parentElement.children).filter(function(element) { return element !== this[0] && (!selector || element.matches(selector)); }, this));
};
mobileDom.Collection.prototype.data = function(name, value) {
	if(value === undefined) return this[0] ? this[0].dataset[name] : undefined;
	return this.each(function() { this.dataset[name] = value; });
};
mobileDom.Collection.prototype.width = function() {
	var element = this[0];
	if(!element) return 0;
	if(element === window) return window.innerWidth;
	if(element === document) {
		var body = document.body;
		var root = document.documentElement;
		return Math.max(root.clientWidth, root.scrollWidth, body ? body.clientWidth : 0, body ? body.scrollWidth : 0);
	}
	return typeof element.getBoundingClientRect === 'function' ? element.getBoundingClientRect().width : 0;
};
mobileDom.Collection.prototype.offset = function() {
	var element = this[0];
	if(!element || element === window || element === document || typeof element.getBoundingClientRect !== 'function') {
		return {left: 0, top: 0};
	}
	var rect = element.getBoundingClientRect();
	return {left: rect.left + window.scrollX, top: rect.top + window.scrollY};
};
mobileDom.Collection.prototype.index = function() {
	return this[0] && this[0].parentElement ? Array.prototype.indexOf.call(this[0].parentElement.children, this[0]) : -1;
};
mobileDom.Collection.prototype.height = function() {
	var element = this[0];
	if(!element) return 0;
	if(element === window) return window.innerHeight;
	if(element === document) {
		var body = document.body;
		var root = document.documentElement;
		return Math.max(root.clientHeight, root.scrollHeight, body ? body.clientHeight : 0, body ? body.scrollHeight : 0);
	}
	return typeof element.getBoundingClientRect === 'function' ? element.getBoundingClientRect().height : 0;
};
mobileDom.Collection.prototype.scrollTop = function(value) {
	if(value === undefined) return this[0] === document || this[0] === window ? window.scrollY : (this[0] ? this[0].scrollTop : 0);
	return this.each(function() {
		if(this === document || this === window) window.scrollTo(0, value);
		else this.scrollTop = value;
	});
};
mobileDom.Collection.prototype.ready = function(callback) {
	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback, {once: true});
	else callback();
	return this;
};

/* Keep legacy bare-ID calls working while selector calls use native mobileDom. */
function isNativeHtmlTag(tag) {
	return typeof tag === 'string' && document.createElement(tag).constructor !== window.HTMLUnknownElement;
}

window.$ = function(selector, context) {
	if(typeof selector === 'string' && /^[a-zA-Z_][a-zA-Z0-9_-]*$/.test(selector) && !isNativeHtmlTag(selector)) {
		return _D$(selector);
	}
	return mobileDom(selector, context);
};
window.$.trim = function(value) { return String(value || '').trim(); };
window.$.each = function(collection, callback) {
	Array.from(collection instanceof mobileDom.Collection ? collection.elements : collection).forEach(function(item, index) {
		callback.call(item, index, item);
	});
};

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
	popup.open(document.getElementById('imgzoom_pop'));

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

document.addEventListener('DOMContentLoaded', function() {

	if(qSel('div.pg')) {
		page.converthtml();
	}
	if(qSel('.scrolltop')) {
		scrolltop.init(qSel('.scrolltop'));
	}
	if(document.images.length > 0) {
		img.init(1);
	}
	if(document.querySelector('.popup')) {
		popup.init();
	}
	if(document.querySelector('.display')) {
		display.init();
	}
	dialog.init();
	formdialog.init();

	document.addEventListener('click', function(event) {
		var target = event.target.closest('img[zoomfile]');
		if(!target) return;
		zoom(target);
		event.preventDefault();
	});
});

function ajaxget(url, showid, waitid, loading, display, recall) {
	var url = url + '&inajax=1&ajaxtarget=' + showid;
	fetch(url)
	.then(function(response) {
		if(!response.ok) throw new Error(response.statusText);
		return response.text();
	})
	.then(function(text) {
		document.getElementById(showid).innerHTML = text;
		document.querySelectorAll('[ajaxtarget]').forEach(function(element) {
			element.ontouchstart = function(e) {
				ajaxget(element.getAttribute('href'), element.getAttribute('ajaxtarget'));
				e.preventDefault();
			};
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
	this.attachEvent = function (obj, evt, func) {
		obj.addEventListener(evt, func, false);
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
			success = true;
			if (msg) {
				popup.open(msg, 'alert');
			}
		} else {
			popup.open($L('copy_failed2'), 'alert');
		}
	} else {
		popup.open($L('copy_failed2'), 'alert');
	}
	return success;
}

function copycode(obj) {
	if(setCopy(obj.textContent)) {
		copycodeIcon(obj);
	}
}

function copycodeIcon(obj) {
	var em = obj && obj.nextElementSibling;
	if(!em || em.tagName.toLowerCase() != 'em') return;
	clearTimeout(em._copyTimer);
	var check = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>';
	var copy = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path></svg>';
	em.innerHTML = check;
	em._copyTimer = setTimeout(function() {
		em.innerHTML = copy;
	}, 1000);
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
                }
            })
            .catch(() => {});
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
		selectState.popup.querySelector('.discuz-select-title').textContent = select.getAttribute('data-title') || mobileSelectLang('select', '请选择');
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
