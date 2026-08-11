<?php echo '';exit;?>
<!--{subtemplate common/header_common}-->
	{cells common/header/meta}
	{cells common/header/css}
	{cells common/header/js}
</head>

<body id="nv_{$_G[basescript]}" class="pg_{CURMODULE} dz_pg_{CURMODULE}	dz_tbnvb {if $_G['basescript'] === 'portal' && CURMODULE === 'list' && !empty($cat)} {$cat['bodycss']}{/if} discuzx5" onkeydown="if(event.keyCode==27) return false;">
<script type="text/javascript">
(function() {
	function newSVGElem(type, attrs) {
		var el = document.createElementNS("http://www.w3.org/2000/svg", type);
		for(var k in attrs) { el.setAttribute(k, attrs[k]); }
		return el;
	}
	var size = 1024;
	var freqX = 4 / size;
	var freqY = 8 / size;
	var seed = Math.floor(Math.random() * 255);

	var oSvg = newSVGElem("svg", { xmlns: "http://www.w3.org/2000/svg", width: size, height: size, viewBox: "0 0 " + size + " " + size });
	var oDefs = newSVGElem("defs");
	var oFilter = newSVGElem("filter", { id: "seamless", x: "0", y: "0", width: "100%", height: "100%" });
	oFilter.appendChild(newSVGElem("feTurbulence", { type: "fractalNoise", baseFrequency: freqX + " " + freqY, numOctaves: "5", seed: seed, stitchTiles: "stitch" }));
	oFilter.appendChild(newSVGElem("feColorMatrix", { type: "matrix", values: "0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 7 -3.5" }));
	oDefs.appendChild(oFilter);
	oSvg.appendChild(oDefs);
	oSvg.appendChild(newSVGElem("rect", { width: "100%", height: "100%", fill: "transparent" }));
	oSvg.appendChild(newSVGElem("rect", { width: "100%", height: "100%", fill: "white", filter: "url(#seamless)", opacity: "0.8" }));
	var svgString = (new XMLSerializer()).serializeToString(oSvg);
	var svgDataUrl = "data:image/svg+xml;base64," + btoa(svgString);

	var img = new Image();
	img.onload = function() {
		try {
			var canvas = document.createElement('canvas');
			canvas.width = size;
			canvas.height = size;
			var ctx = canvas.getContext('2d');
			ctx.drawImage(img, 0, 0);
			var pngDataUrl = canvas.toDataURL('image/png');
			document.body.style.setProperty('--cloud-bg', 'url("' + pngDataUrl + '")');
		} catch(e) {
			document.body.style.setProperty('--cloud-bg', 'url("' + svgDataUrl + '")');
		}
	};
	img.onerror = function() {
		document.body.style.setProperty('--cloud-bg', 'url("' + svgDataUrl + '")');
	};
	img.src = svgDataUrl;

	var oceanSvg = newSVGElem("svg", { xmlns: "http://www.w3.org/2000/svg", width: size, height: size, viewBox: "0 0 " + size + " " + size });
	oceanSvg.innerHTML = '<defs>' +
		'<linearGradient id="sea" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="1024"><stop stop-color="#c3d9e6"/><stop offset=".14" stop-color="#a2c5da"/><stop offset=".38" stop-color="#6598b8"/><stop offset=".68" stop-color="#33607e"/><stop offset="1" stop-color="#16364f"/></linearGradient>' +
		'<linearGradient id="refl" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="360"><stop stop-color="#fff" stop-opacity=".18"/><stop offset=".6" stop-color="#eaf4f8" stop-opacity=".07"/><stop offset="1" stop-color="#eaf4f8" stop-opacity="0"/></linearGradient>' +
		'<linearGradient id="farMask" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" y2="360"><stop stop-color="#fff"/><stop offset=".55" stop-color="#fff" stop-opacity=".45"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>' +
		'<linearGradient id="nearMask" gradientUnits="userSpaceOnUse" x1="0" y1="360" x2="0" y2="1024"><stop stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#fff"/></linearGradient>' +
		'<mask id="mFar"><rect x="-40" y="0" width="1104" height="360" fill="url(#farMask)"/></mask><mask id="mNear"><rect x="-40" y="360" width="1104" height="664" fill="url(#nearMask)"/></mask>' +
		'<filter id="sway" x="-8%" y="-10%" width="116%" height="120%"><feTurbulence type="fractalNoise" baseFrequency=".003 .018" numOctaves="1" seed="' + (seed + 17) + '" result="n"/><feDisplacementMap in="SourceGraphic" in2="n" scale="15" xChannelSelector="R" yChannelSelector="G"/></filter>' +
		'<filter id="swell" x="-4%" y="-4%" width="108%" height="108%"><feTurbulence type="fractalNoise" baseFrequency=".0025 .009" numOctaves="2" seed="' + (seed + 23) + '" result="n"/><feGaussianBlur in="n" stdDeviation="2" result="b"/><feDiffuseLighting in="b" surfaceScale="3" diffuseConstant=".9" lighting-color="#fff" result="l"><feDistantLight azimuth="300" elevation="55"/></feDiffuseLighting><feComposite in="l" in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="ripFar" x="-4%" y="-4%" width="108%" height="108%"><feTurbulence type="fractalNoise" baseFrequency=".014 .12" numOctaves="2" seed="' + (seed + 31) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".35" result="b"/><feDiffuseLighting in="b" surfaceScale="1.3" diffuseConstant=".85" lighting-color="#f4faff"><feDistantLight azimuth="300" elevation="60"/></feDiffuseLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="ripNear" x="-4%" y="-4%" width="108%" height="108%"><feTurbulence type="fractalNoise" baseFrequency=".009 .06" numOctaves="2" seed="' + (seed + 33) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".5" result="b"/><feDiffuseLighting in="b" surfaceScale="2.4" diffuseConstant=".95" lighting-color="#f4faff"><feDistantLight azimuth="300" elevation="55"/></feDiffuseLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="specFar" x="-4%" y="-5%" width="108%" height="112%"><feTurbulence type="fractalNoise" baseFrequency=".010 .09" numOctaves="2" seed="' + (seed + 42) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".5" result="b"/><feSpecularLighting in="b" surfaceScale="1.6" specularConstant="1.15" specularExponent="16" lighting-color="#f0f8ff"><feDistantLight azimuth="300" elevation="58"/></feSpecularLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
		'<filter id="specNear" x="-4%" y="-5%" width="108%" height="112%"><feTurbulence type="fractalNoise" baseFrequency=".006 .05" numOctaves="2" seed="' + (seed + 77) + '" result="n"/><feGaussianBlur in="n" stdDeviation=".7" result="b"/><feSpecularLighting in="b" surfaceScale="2.6" specularConstant="1.05" specularExponent="13" lighting-color="#f2f9ff"><feDistantLight azimuth="300" elevation="52"/></feSpecularLighting><feComposite in2="SourceAlpha" operator="in"/></filter>' +
	'</defs><g filter="url(#sway)"><rect x="-60" y="-60" width="1144" height="1144" fill="url(#sea)"/><rect x="-60" y="0" width="1144" height="360" fill="url(#refl)"/></g>' +
	'<rect x="-40" y="0" width="1104" height="1024" fill="#d9f2f8" filter="url(#swell)" opacity=".35" style="mix-blend-mode:soft-light"/>' +
	'<rect x="-40" y="0" width="1104" height="360" fill="#f4faff" filter="url(#ripFar)" mask="url(#mFar)" opacity=".28" style="mix-blend-mode:overlay"/><rect x="-40" y="360" width="1104" height="664" fill="#f4faff" filter="url(#ripNear)" mask="url(#mNear)" opacity=".24" style="mix-blend-mode:overlay"/>' +
	'<rect x="-40" y="0" width="1104" height="360" fill="#f0f8ff" filter="url(#specFar)" mask="url(#mFar)" opacity=".32" style="mix-blend-mode:screen"/><rect x="-40" y="360" width="1104" height="664" fill="#f2f9ff" filter="url(#specNear)" mask="url(#mNear)" opacity=".18" style="mix-blend-mode:screen"/>' +
	'<rect x="0" y="0" width="1024" height="18" fill="#fff" opacity=".12"/>';
	var oceanString = (new XMLSerializer()).serializeToString(oceanSvg);
	document.body.style.setProperty('--ocean-bg', 'url("data:image/svg+xml;base64,' + btoa(oceanString) + '")');
})();
</script>
	<a class="dz-skip-link" href="#wp">{lang skip_to_content}</a>
	<div id="append_parent"></div><div id="ajaxwaitid"></div>
	<!--{if $_GET['diy'] == 'yes' && check_diy_perm($topic)}-->
		<!--{template common/header_diy}-->
	<!--{/if}-->
	<!--{if check_diy_perm($topic)}-->
		<!--{template common/header_diynav}-->
	<!--{/if}-->
	<!--{if CURMODULE == 'topic' && $topic && empty($topic['useheader']) && check_diy_perm($topic)}-->
		$diynav
	<!--{/if}-->
	<!--{if empty($topic) || $topic['useheader']}-->
		<!--{if $_G['setting']['mobile']['allowmobile'] && (!$_G['setting']['cacheindexlife'] && !$_G['setting']['cachethreadon'] || $_G['uid']) && ($_GET['diy'] != 'yes' || !$_GET['inajax']) && ($_G['mobile'] != '' && $_G['cookie']['mobile'] == '')}-->
			<div class="xi1 bm bm_c">
			    {lang your_mobile_browser}<a href="javascript:;" onclick="setcookie('mobile', '2', 31536000);location.reload();return false;">{lang go_to_mobile}</a> <span class="xg1">|</span> <a href="javascript:;" onclick="setcookie('mobile', 'no', 31536000);location.reload();return false;">{lang to_be_continue}</a>
			</div>
		<!--{/if}-->
		<!--{if $_G['setting']['shortcut'] && $_G['member'][credits] >= $_G['setting']['shortcut']}-->
			<div id="shortcut">
				<span><a href="javascript:;" id="shortcutcloseid" title="{lang close}">{lang close}</a></span>
				{lang shortcut_notice}
				<a href="javascript:;" id="shortcuttip">{lang shortcut_add}</a>

			</div>
			<script type="text/javascript">setTimeout(setShortcut, 2000);</script>
		<!--{/if}-->
		<div id="toptb" class="cl" style="display:none;">
			<!--{hook/global_cpnav_top}-->
			<div class="wp">
				<div class="z">
					<!--{loop $_G['setting']['topnavs'][0] $nav}-->
						<!--{if is_array($nav) && $nav['available'] && (!$nav['level'] || ($nav['level'] == 1 && $_G['uid']) || ($nav['level'] == 2 && $_G['adminid'] > 0) || ($nav['level'] == 3 && $_G['adminid'] == 1))}-->$nav[code]<!--{/if}-->
					<!--{/loop}-->
					<!--{hook/global_cpnav_extra1}-->
				</div>
				<div class="y">
					<!--{hook/global_cpnav_extra2}-->
				</div>
				<div class="clear"></div>
			</div>
		</div>
		
		<!--{if !IS_ROBOT}-->
			<!--{if $_G['uid'] && !empty($_G['style']['extstyle'])}-->
				<div id="sslct_menu" class="cl p_pop" style="display: none;">
					<!--{if empty($_G['style']['defaultextstyle'])}--><span class="sslct_btn" onClick="extstyle('')" title="{lang default}"><i></i></span><!--{/if}-->
					<!--{loop $_G['style']['extstyle'] $extstyle}-->
						<span class="sslct_btn" onClick="extstyle('$extstyle[0]')" title="$extstyle[1]"><i style='background:$extstyle[2]'></i></span>
					<!--{/loop}-->
				</div>
			<!--{/if}-->
			<!--{if $_G['uid']}-->
				<ul id="myitem_menu" class="p_pop" style="display: none;">
					<!--{if $_G['setting']['forumstatus']}--><li><a href="home.php?mod=space&do=thread&view=me">{lang mypost}</a></li><!--{/if}-->
					<!--{if $_G['setting']['favoritestatus']}--><li><a href="home.php?mod=space&do=favorite&view=me">{lang favorite}</a></li><!--{/if}-->
					<!--{if $_G['setting']['friendstatus']}--><li><a href="home.php?mod=space&do=friend">{lang friends}</a></li><!--{/if}-->
					<!--{if $_G['setting']['followerstatus']}-->
						<li><a href="home.php?mod=follow&do=follower">{lang follower}</a></li>
						<li><a href="home.php?mod=follow&do=following">{lang following}</a></li>
					<!--{/if}-->
					<!--{hook/global_myitem_extra}-->
				</ul>
			<!--{/if}-->
			<!--{subtemplate common/header_qmenu}-->
		<!--{/if}-->

		<!--{ad/headerbanner/wp a_h}-->

		<div id="hd">
		<div class="wp">
			<!--{if !empty($_G['cookie']['recentthreads'])}-->
				<!--{eval $recenttids = array_slice(array_values(array_diff(array_filter(array_map('intval', explode(',', $_G['cookie']['recentthreads']))), [intval($_G['tid'] ?? 0)])), 0, 5);}-->
				<!--{eval $recentthreadlist = $recenttids ? table_forum_thread::t()->fetch_all_by_tid($recenttids) : [];}-->
				<!--{if $recentthreadlist}-->
				<span class="pg"><a href="javascript:;" id="recentthreads" onmouseover="showMenu({'ctrlid':this.id,'pos':'34'})">{lang viewed_threads}</a></span>
				<div id="recentthreads_menu" class="p_pop h_pop navs_menu" style="display: none;">
					<ul id="v_threads">
								<!--{loop $recenttids $rtid}-->
						<!--{if $recentthreadlist[$rtid]}--><li><a href="forum.php?mod=viewthread&tid=$rtid" title="{$recentthreadlist[$rtid]['subject']}">{$recentthreadlist[$rtid]['subject']}</a></li><!--{/if}-->
								<!--{/loop}-->
					</ul>
				</div>
				<!--{/if}-->
			<!--{/if}-->
			<!--{if $_G['setting']['visitedforums']}-->
				<!--{eval require_once libfile('function/forumlist'); empty($_G['cache']['forums']) && loadcache('forums'); $visitedforumsmenu = visitedforums();}-->
				<!--{if $visitedforumsmenu}-->
				<span class="pg"><a href="javascript:;" id="visitedforums" onmouseover="showMenu({'ctrlid':this.id,'pos':'34'})">{lang viewed_forums}</a></span>
				<div id="visitedforums_menu" class="p_pop h_pop navs_menu" style="display: none;">
					<ul id="v_forums">
						$visitedforumsmenu
					</ul>
				</div>
				<!--{/if}-->
			<!--{/if}-->
			<!--{template common/header_userstatus}-->
		</div>
		</div>
		<div class="wp" id="hds">
			<!--{if !empty($_G['setting']['plugins']['jsmenu'])}-->
			<ul class="p_pop h_pop" id="plugin_menu" style="display: none">
				<!--{loop $_G['setting']['plugins']['jsmenu'] $module}-->
				<!--{if !$module['adminid'] || ($module['adminid'] && $_G['adminid'] > 0 && $module['adminid'] >= $_G['adminid'])}-->
				<li>$module[url]</li>
				<!--{/if}-->
				<!--{/loop}-->
			</ul>
			<!--{/if}-->
			$_G[setting][menunavs]
			<div id="mu" class="cl">
				<!--{if $_G['setting']['subnavs']}-->
				<!--{loop $_G[setting][subnavs] $navid $subnav}-->
				<!--{if $_G['setting']['navsubhover'] || $mnid == $navid}-->
				<ul class="cl {if $mnid == $navid}current{/if}" id="snav_$navid" style="display:{if $mnid != $navid}none{/if}">
					$subnav
				</ul>
				<!--{/if}-->
				<!--{/loop}-->
				<!--{/if}-->
			</div>
			<!--{ad/subnavbanner/a_mu}-->
			<!--{if $n == 9999}-->
			<!--{eval $n=0;}-->
			<ul id="top_menumore_menu" class="p_pop" style="display:none">
				<!--{loop $_G['setting']['navs'] $nav}-->
					<!--{if is_array($nav) && $nav['available'] && (!$nav['level'] || ($nav['level'] == 1 && $_G['uid']) || ($nav['level'] == 2 && $_G['adminid'] > 0) || ($nav['level'] == 3 && $_G['adminid'] == 1))}-->
						<!--{eval $n++;}-->
						<!--{if $n <= $nn}--><!--{eval continue;}--><!--{/if}-->	
						<!--{if is_array($nav) && $nav['available'] && (!$nav['level'] || ($nav['level'] == 1 && $_G['uid']) || ($nav['level'] == 2 && $_G['adminid'] > 0) || ($nav['level'] == 3 && $_G['adminid'] == 1))}--><li {if $mnid == $nav[navid] || substr($_SERVER['REQUEST_URI'], 1) == str_replace('./', '', $nav[filename])}class="a" {/if}$nav[nav]></li><!--{/if}-->
					<!--{/if}-->
				<!--{/loop}-->
			</ul>
			<!--{/if}-->
		
			<!--{subtemplate common/pubsearchform}-->
		</div>

		<!--{hook/global_header}-->
	<!--{/if}-->

	<div id="wp" class="wp" tabindex="-1">
    
		
