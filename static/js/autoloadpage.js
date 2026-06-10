/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

(function() {

	var autopbn = $('autopbn');
	var nextpageurl = autopbn.getAttribute('rel').valueOf();
	var curpage = parseInt(autopbn.getAttribute('curpage').valueOf());
	var totalpage = parseInt(autopbn.getAttribute('totalpage').valueOf());
	var picstyle = parseInt(autopbn.getAttribute('picstyle').valueOf());
	var forumdefstyle = parseInt(autopbn.getAttribute('forumdefstyle').valueOf());
	picstyle = picstyle && !forumdefstyle;

	var loadstatus = 0;

	autopbn.onclick = function() {
		var oldloadstatus = loadstatus;
		loadstatus = 2;
		autopbn.innerHTML = $L('loading');
		getnextpagecontent();
		loadstatus = oldloadstatus;
	};

	function getnextpagecontent() {

		if(curpage + 1 > totalpage) {
			window.onscroll = null;
			autopbn.style.display = 'none';
			return;
		}
		curpage++;
		var url = nextpageurl + '&t=' + parseInt((+new Date()/1000)/(Math.random()*1000));
		var x = new Ajax('HTML');
		x.get(url, function (s) {
			s = s.replace(/\n|\r/g, ' ');
			if(s.indexOf("id=\"autopbn\"") == -1) {
				$("autopbn").style.display = "none";
				window.onscroll = null;
			}

			if(!picstyle) {
				var tableobj = $('threadlisttableid');
				if(tableobj.tagName.toLowerCase() == 'table') {
					var nexts = s.match(/\<tbody id="normalthread_(\d+)"\>(.+?)\<\/tbody>/g) || [];
					for(i in nexts) {
						if(i == 'index' || i == 'lastIndex') {
							continue;
						}
						var insertid = nexts[i].match(/<tbody id="normalthread_(\d+)"\>/);
						if(!$('normalthread_' + insertid[1])) {

							var newbody = document.createElement('tbody');
							tableobj.appendChild(newbody);
							var div = document.createElement('div');
							div.innerHTML = '<table>' + nexts[i] + '</table>';
							tableobj.replaceChild(div.childNodes[0].childNodes[0], tableobj.lastChild);
							MathJax.typesetPromise([tableobj.lastChild]);
						}
					}
				} else {
					var div = document.createElement('div');
					div.innerHTML = s;
					var nexts = div.getElementsByTagName('li');
					var newthreads = [];
					for(var i = 0; i < nexts.length; i++) {
						if(/^normalthread_\d+$/.test(nexts[i].id) && !$(nexts[i].id)) {
							newthreads.push(nexts[i]);
						}
					}
					for(var i = 0; i < newthreads.length; i++) {
						tableobj.appendChild(newthreads[i]);
						MathJax.typesetPromise([tableobj.lastChild]);
					}
				}
			} else {
				var nexts = s.match(/\<li style="width:\d+px;" id="picstylethread_(\d+)"\>(.+?)\<\/li\>/g);
				for(i in nexts) {
					var insertid = nexts[i].match(/id="picstylethread_(\d+)"\>/);
					if(!$('picstylethread_' + insertid[1])) {
						$('threadlist_picstyle').innerHTML += nexts[i];
					}
				}
			}
			var pageinfo = s.match(/\<span id="fd_page_bottom"\>(.+?)\<\/span\>/);
			nextpageurl = nextpageurl.replace(/&page=\d+/, '&page=' + (curpage + 1));

			$('fd_page_bottom').innerHTML = pageinfo[1];
			var pageinfo = s.match(/\<span id="fd_page_top"\>(.+?)\<\/span\>/);
			$('fd_page_top').innerHTML = pageinfo[1];			
			autopbn.style.display = 'none';
			if (curpage + 1 <= totalpage) {
				autopbn.innerHTML = $L('next_page') + ' &raquo;';
				setTimeout(function () {
					autopbn.style.display = 'block';
				}, 100);
			}
			loadstatus = 0;
		});
	}

})();
