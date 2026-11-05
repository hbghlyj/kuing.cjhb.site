/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */


if(isUndefined(sliderun)) {
	var sliderun = 1;

	function slide() {

		var s = new Object();
		s.slideId = Math.random();
		s.slideSpeed = slideSpeed;
		s.size = slideImgsize;
		s.imgs = slideImgs;
		s.imgLoad = new Array();
		s.imgnum = slideImgs.length;
		s.imgLinks = slideImgLinks;
		s.imgTexts = slideImgTexts;
		s.slideBorderColor = slideBorderColor;
		s.slideBgColor = slideBgColor;
		s.slideSwitchColor = slideSwitchColor;
		s.slideSwitchbgColor = slideSwitchbgColor;
		s.slideSwitchHiColor = slideSwitchHiColor;
		s.currentImg = 0;
		s.prevImg = 0;
		s.imgLoaded = 0;
		s.st = null;

		s.loadImage = function () {
			if(!s.imgnum) {
				return;
			}
			s.size[0] = parseInt(s.size[0]);
			s.size[1] = parseInt(s.size[1]);
			document.write('<div class="slideouter" id="outer_'+s.slideId+'" style="cursor:pointer;position:absolute;width:'+(s.size[0]-2)+'px;height:'+(s.size[1]-2)+'px;border:1px solid '+s.slideBorderColor+'"></div>');
			document.write('<table class="cp0" style="cursor:pointer;width:'+s.size[0]+'px;height:'+s.size[1]+'px;table-layout:fixed;overflow:hidden;background:'+s.slideBgColor+';text-align:center"><tr><td valign="middle" style="padding:0" class="slideimage" id="slide_'+s.slideId+'">');
			document.write((typeof IMGDIR == 'undefined' ? '' : '<div class="loadicon"></div>') + '<br /><span id="percent_'+s.slideId+'">0%</span></td></tr></table>');
			document.write('<div id="switch_'+s.slideId+'" style="position:absolute;margin-left:1px;margin-top:-18px"></div>');
			$('outer_' + s.slideId).onclick = s.imageLink;
			for(i = 1;i < s.imgnum;i++) {
				switchdiv = document.createElement('div');
				switchdiv.id = 'switch_' + i + '_' + s.slideId;
				switchdiv.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=70)';
				switchdiv.style.opacity = 0.7;
				switchdiv.style.styleFloat = 'left';
				switchdiv.style.cssFloat = 'left';
				switchdiv.style.cursor = 'pointer';
				switchdiv.style.width = '17px';
				switchdiv.style.height = '17px';
				switchdiv.style.overflow = 'hidden';
				switchdiv.style.fontWeight = 'bold';
				switchdiv.style.textAlign = 'center';
				switchdiv.style.fontSize = '9px';
				switchdiv.style.color = s.slideSwitchColor;
				switchdiv.style.borderRight = '1px solid ' + s.slideBorderColor;
				switchdiv.style.borderTop = '1px solid ' + s.slideBorderColor;
				switchdiv.style.backgroundColor = s.slideSwitchbgColor;
				switchdiv.className = 'slideswitch';
				switchdiv.i = i;
				switchdiv.onclick = function () {
					s.switchImage(this);
				};
				switchdiv.innerHTML = i;
				$('switch_'+s.slideId).appendChild(switchdiv);
				s.imgLoad[i] = new Image();
				s.imgLoad[i].src = s.imgs[i];
				s.imgLoad[i].onerror = function () { s.imgLoaded++; };
			}
			s.loadCheck();
		};

		s.imageLink = function () {
			window.open(s.imgLinks[s.currentImg]);
		};

		s.switchImage = function (obj) {
			s.showImage(obj.i);
			s.interval();
		};

		s.loadCheck = function () {
			for(i = 1;i < s.imgnum;i++) {
				if(s.imgLoad[i].complete && !s.imgLoad[i].status) {
					s.imgLoaded++;
					s.imgLoad[i].status = 1;
					if(s.imgLoad[i].width > s.size[0] || s.imgLoad[i].height > s.size[1]) {
						zr = s.imgLoad[i].width / s.imgLoad[i].height;
						if(zr > 1) {
							s.imgLoad[i].height = s.size[1];
							s.imgLoad[i].width = s.size[1] * zr;
						} else {
							s.imgLoad[i].width = s.size[0];
							s.imgLoad[i].height = s.size[0] / zr;
							if(s.imgLoad[i].height > s.size[1]) {
								s.imgLoad[i].height = s.size[1];
								s.imgLoad[i].width = s.size[1] * zr;
							}
						}
					}
				}
			}
			if(s.imgLoaded < s.imgnum - 1) {
				$('percent_' + s.slideId).innerHTML = (parseInt(s.imgLoad.length / s.imgnum * 100)) + '%';
				setTimeout(function () { s.loadCheck(); }, 100);
			} else {
				for(i = 1;i < s.imgnum;i++) {
					s.imgLoad[i].onclick = s.imageLink;
					s.imgLoad[i].title = s.imgTexts[i];
				}
				s.showImage();
				s.interval();
			}
		};

		s.interval = function () {
			clearInterval(s.st);
			s.st = setInterval(function () { s.showImage(); }, s.slideSpeed);
		};

		s.showImage = function (i) {
			if(!i) {
				s.currentImg++;
				s.currentImg = s.currentImg < s.imgnum ? s.currentImg : 1;
			} else {
				s.currentImg = i;
			}
			if(s.prevImg) {
				$('switch_' + s.prevImg + '_' + s.slideId).style.backgroundColor = s.slideSwitchbgColor;
			}
			$('switch_' + s.currentImg + '_' + s.slideId).style.backgroundColor = s.slideSwitchHiColor;
			$('slide_' + s.slideId).innerHTML = '';
			$('slide_' + s.slideId).appendChild(s.imgLoad[s.currentImg]);
			s.prevImg = s.currentImg;
		};

		s.loadImage();

	}
}

if(typeof slideImgs != 'undefined' && typeof slideImgsize != 'undefined') {
	slide();
}

(function() {
	var tabs = document.getElementById('forum-index-tabs');
	if(!tabs) return;
	var grid = document.getElementById('forum-index-grid-panel');
	var boardPanels = document.querySelectorAll('[data-forum-index-board]');
	var myforums = document.getElementById('forum-index-myforums');
	var buttons = tabs.getElementsByTagName('button');
	var cells = grid.querySelectorAll('[data-index-tab]');
	var strip = grid.querySelector('.index-image-strip');
	var controls = grid.querySelector('.index-image-controls');
	var imageToggle = grid.querySelector('.index-image-toggle');
	var imageRing = grid.querySelector('.index-image-ring .ring-arc');
	var imageTimer;
	var imageAnimation;
	var imageInterval = 8000;
	var autoplay = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function scheduleImages() {
		clearTimeout(imageTimer);
		if(imageAnimation) imageAnimation.cancel();
		if(imageToggle) imageToggle.setAttribute('aria-pressed', autoplay ? 'false' : 'true');
		if(controls && autoplay && !document.hidden && !grid.hidden && !strip.closest('td').hidden) {
			imageAnimation = imageRing.animate([{strokeDashoffset: '100'}, {strokeDashoffset: '0'}], {duration: imageInterval, fill: 'forwards'});
			imageTimer = setTimeout(function() { stepImage(1); }, imageInterval);
		}
	}

	function currentImage() {
		var width = strip.children[0].getBoundingClientRect().width;
		var step = width + parseFloat(getComputedStyle(strip).columnGap);
		return step ? Math.min(strip.children.length - 1, Math.round(strip.scrollLeft / step)) : 0;
	}

	function stepImage(direction) {
		var count = strip.children.length;
		goToImage((currentImage() + direction + count) % count);
	}

	function goToImage(index) {
		strip.scrollTo({left: strip.children[index].offsetLeft - strip.children[0].offsetLeft, behavior: 'auto'});
		scheduleImages();
	}

	if(controls) {
		controls.querySelector('.prev').onclick = function() { stepImage(-1); };
		controls.querySelector('.next').onclick = function() { stepImage(1); };
		imageToggle.onclick = function() {
			autoplay = !autoplay;
			imageToggle.querySelector('[data-icon="pause"]').hidden = !autoplay;
			imageToggle.querySelector('[data-icon="play"]').hidden = autoplay;
			scheduleImages();
		};
		imageToggle.querySelector('[data-icon="pause"]').hidden = !autoplay;
		imageToggle.querySelector('[data-icon="play"]').hidden = autoplay;
		strip.addEventListener('scroll', scheduleImages, {passive: true});
		document.addEventListener('visibilitychange', scheduleImages);
	}

	function selectTab(tab) {
		for(var j = 0; j < buttons.length; j++) {
			var active = buttons[j].getAttribute('data-index-tab') == tab;
			buttons[j].className = buttons[j].className.replace(/\s*a/g, '') + (active ? ' a' : '');
			buttons[j].setAttribute('aria-selected', active ? 'true' : 'false');
		}
		for(var m = 0; m < boardPanels.length; m++) {
			boardPanels[m].hidden = tab != 'boards';
		}
		myforums.hidden = tab != 'myforums';
		grid.hidden = tab == 'boards' || tab == 'myforums';
		for(var k = 0; k < cells.length; k++) {
			cells[k].hidden = cells[k].getAttribute('data-index-tab') != tab;
		}
		scheduleImages();
	}

	for(var i = 0; i < buttons.length; i++) {
		buttons[i].onclick = function() {
			var tab = this.getAttribute('data-index-tab');
			if(document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				document.startViewTransition(function() { selectTab(tab); });
			} else {
				selectTab(tab);
			}
		};
	}
})();
