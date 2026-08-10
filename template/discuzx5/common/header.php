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
	var seed = Math.floor(Math.random() * 255);
	var width = 1024, height = 1024;
	var oSvg = newSVGElem("svg", { xmlns: "http://www.w3.org/2000/svg", width: width, height: height, viewBox: "0 0 " + width + " " + height });
	var oDefs = newSVGElem("defs");
	var oFilter = newSVGElem("filter", { id: "less_smeared_cloud_filter" });
	oFilter.appendChild(newSVGElem("feTurbulence", { type: "fractalNoise", baseFrequency: "0.008 0.04", numOctaves: "4", seed: seed, result: "noise" }));
	oFilter.appendChild(newSVGElem("feColorMatrix", { type: "matrix", values: "0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 5 -2.5", result: "cloudAlpha" }));
	oDefs.appendChild(oFilter);
	oSvg.appendChild(oDefs);
	oSvg.appendChild(newSVGElem("rect", { width: "100%", height: "100%", fill: "#87CEEB" }));
	oSvg.appendChild(newSVGElem("rect", { width: "100%", height: "100%", fill: "white", filter: "url(#less_smeared_cloud_filter)", opacity: "0.8" }));
	var svgString = (new XMLSerializer()).serializeToString(oSvg);
	var svgDataUrl = "data:image/svg+xml;base64," + btoa(svgString);
	document.body.style.backgroundImage = 'url("' + svgDataUrl + '")';
})();
</script>
	<a class="dz-skip-link" href="#wp">{lang skip_to_content}</a>
	<style>.dz-skip-link{position:absolute;top:-100px;left:50%;transform:translateX(-50%);z-index:9999;padding:8px 16px;background:var(--dz-nvbg,#333);color:var(--dz-ff,#fff);font-size:14px;border-radius:0 0 var(--dz-radius-m,6px) var(--dz-radius-m,6px);box-shadow:var(--dz-shadow,0 2px 8px rgba(0,0,0,.15));transition:top .2s ease-in-out}.dz-skip-link:focus,.dz-skip-link:focus-visible{top:0}</style>
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
			<!--{if $_G['uid']}-->
			<ul id="myprompt_menu" class="p_pop" style="display: none;">
				<!--{if !empty($_G['setting']['pmstatus'])}-->
					<li><a href="home.php?mod=space&do=pm" id="pm_ntc" style="background-repeat: no-repeat; background-position: 0 50%;"><em class="prompt_news{if empty($_G[member][newpm])}_0{/if}"></em>{lang pm_center}</a></li>
				<!--{/if}-->
				<li><a href="home.php?mod=follow&do=follower"><em class="prompt_follower{if empty($_G[member][newprompt_num][follower])}_0{/if}"></em><!--{lang notice_interactive_follower}-->{if !empty($_G[member][newprompt_num][follower])}($_G[member][newprompt_num][follower]){/if}</a></li>
				<!--{if !empty($_G[member][newprompt]) && !empty($_G[member][newprompt_num][follow])}-->
					<li><a href="home.php?mod=follow"><em class="prompt_concern"></em><!--{lang notice_interactive_follow}-->($_G[member][newprompt_num][follow])</a></li>
				<!--{/if}-->
				<!--{if $_G[member][newprompt]}-->
					<!--{loop $_G['member']['category_num'] $key $val}-->
						<li><a href="home.php?mod=space&do=notice&view=$key"><em class="notice_$key"></em><!--{echo lang('template', 'notice_'.$key)}-->(<span class="rq">$val</span>)</a></li>
					<!--{/loop}-->
				<!--{/if}-->
				<!--{if empty($_G['cookie']['ignore_notice'])}-->
				<li class="ignore_noticeli"><a href="javascript:;" onClick="setcookie('ignore_notice', 1);hideMenu('myprompt_menu')" title="{lang temporarily_to_remind}"><em class="ignore_notice"></em></a></li>
				<!--{/if}-->
				</ul>
			<!--{/if}-->
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
    
		
