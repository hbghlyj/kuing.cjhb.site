<?php echo '';exit;?>
<!--{subtemplate common/header_common}-->
	{cells common/header/meta}
	{cells common/header/css}
	{cells common/header/js}
</head>

<body id="nv_{$_G[basescript]}" class="pg_{CURMODULE} dz_pg_{CURMODULE}	dz_tbnvb {if $_G['basescript'] === 'portal' && CURMODULE === 'list' && !empty($cat)} {$cat['bodycss']}{/if} discuzx5" onkeydown="if(event.keyCode==27) return false;">
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
			<script>setTimeout(setShortcut, 2000);</script>
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
			<span class="pg" id="recentthreads_wrap" style="display:none"><a href="javascript:;" id="recentthreads" onmouseover="showMenu({'ctrlid':this.id,'pos':'34'})">{lang viewed_threads}</a></span>
			<div id="recentthreads_menu" class="p_pop h_pop navs_menu" style="display:none">
				<ul id="v_threads"></ul>
			</div>
			<script>
			(function() {
				var storageKey = 'kuing-recent-threads-v1';
				var currentThread = <!--{if CURMODULE == 'viewthread' && !empty($_G['tid'])}-->{tid:$_G['tid'],title:<!--{echo json_encode($_G['forum_thread']['subject'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)}-->}<!--{else}-->null<!--{/if}-->;
				var wrap = document.getElementById('recentthreads_wrap');
				var list = document.getElementById('v_threads');
				if(!wrap || !list || !window.localStorage) return;
				var items = [];
				try { items = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch(e) {}
				if(currentThread && currentThread.tid && currentThread.title) {
					items = items.filter(function(item) { return item.tid != currentThread.tid; });
					items.unshift({tid: currentThread.tid, title: currentThread.title});
					try { localStorage.setItem(storageKey, JSON.stringify(items.slice(0, 8))); } catch(e) {}
				}
				items.slice(0, 5).forEach(function(item) {
					var li = document.createElement('li');
					var link = document.createElement('a');
					link.href = 'forum.php?mod=viewthread&tid=' + encodeURIComponent(item.tid);
					link.title = item.title;
					link.textContent = item.title;
					li.appendChild(link);
					list.appendChild(li);
				});
				if(list.children.length) wrap.style.display = '';
			})();
			</script>
			<!--{if CURMODULE == 'index' && $_G['uid'] && $_G['setting']['forumstatus']}-->
				<span class="pg"><a href="home.php?mod=space&do=thread&view=me">{lang my_posts}</a></span>
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
    
		
