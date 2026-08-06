<?php echo '';exit;?>
<!DOCTYPE html>
<html lang="{echo lang_attr();}">
	<head>
	<title><!--{if !empty($navtitle)}-->$navtitle - <!--{/if}--><!--{if empty($nobbname)}--> $_G['setting']['bbname']<!--{/if}--></title>
	$_G['setting']['seohead']
	<style>
	:root { text-autospace: normal; font-variant-east-asian: <!--{if $_G['i18n'] == 'TC'}-->traditional<!--{else}-->simplified<!--{/if}--> proportional-width !important; }
	</style>
	<!--{hook/global_meta}-->
	<!--{eval include './kk/mathjax.php';}-->
	<!--{csstemplate}-->
	<script type="text/javascript">var STYLEID = '{STYLEID}', STATICURL = '{STATICURL}', IMGDIR = '{IMGDIR}', VERHASH = '{VERHASH}', FORMHASH = '{FORMHASH}', charset = '{CHARSET}', discuz_uid = '$_G[uid]', cookiepre = '{$_G[config][cookie][cookiepre]}', cookiedomain = '{$_G[config][cookie][cookiedomain]}', cookiepath = '{$_G[config][cookie][cookiepath]}', showusercard = '{$_G[setting][showusercard]}', attackevasive = '{$_G[config][security][attackevasive]}', disallowfloat = '{$_G[setting][disallowfloat]}', creditnotice = '<!--{if $_G['setting']['creditnotice']}-->$_G['setting']['creditnames']<!--{/if}-->', defaultstyle = '$_G[style][defaultextstyle]', REPORTURL = '$_G[currenturl_encode]', SITEURL = '$_G[siteurl]', JSCACHEPATH = '{$_G[setting][jscachepath]}', JSPATH = '$_G[setting][jspath]', CSSPATH = '$_G[setting][csspath]', DYNAMICURL = '{$_G[dynamicurl] or ''}', DISCUZ_I18N = '{echo currentlang();}', AVATARURL = '$_G[setting][avatarbase]';</script>
	<script type="text/javascript">
		if(typeof $ !== 'function') { window.$ = function(id) { return typeof id === 'string' ? document.getElementById(id) : id; }; }
		if(typeof _attachEvent !== 'function') { window._attachEvent = function(target, event, handler) { if(target && target.addEventListener) { target.addEventListener(event, handler, false); } else if(target && target.attachEvent) { target.attachEvent('on' + event, handler); } }; }
		if(typeof updatesecqaa !== 'function') { window.updatesecqaa = function() {}; }
		if(typeof updateseccode !== 'function') { window.updateseccode = function() {}; }
		if(typeof addFormEvent !== 'function') { window.addFormEvent = function() {}; }
		if(typeof checkBlind !== 'function') { window.checkBlind = function() {}; }
		if(typeof fetchOffset !== 'function') { window.fetchOffset = function(obj) { obj = typeof obj === 'string' ? document.getElementById(obj) : obj; if(!obj) return { left: 0, top: 0 }; var rect = obj.getBoundingClientRect(); return { left: rect.left + window.scrollX, top: rect.top + window.scrollY }; }; }
	</script>
	<script type="text/javascript" src="{echo $_G['setting']['jscachepath'] ?: 'data/cache/';}lang_{echo currentlang();}.js?{VERHASH}"></script>
	<script type="text/javascript" src="/static/js/common.js?{VERHASH}"></script>
	<!--{if empty($_GET['diy'])}--><!--{eval $_GET['diy'] = '';}--><!--{/if}-->
	<!--{if !isset($topic)}--><!--{eval $topic = array();}--><!--{/if}-->
	<style>
	<!--{if !$_G['style']['top_nav_widthauto']}-->
	.dz_btm_layer .dz_layer_nav{width: 609px;}
	.dz_btm_layer .header-searcher .search-input,
	.dz_btm_layer .header-searcher .ais-SearchBox-input,
	.dz_btm_layer .header-searcher #algolia-search-box:empty{width: 200px !important;}
	<!--{/if}-->
	<!--{if $_G['style']['top_nav_bgc']}-->
	.dz_layer_top{background: $_G['style']['top_nav_bgc'];}
	<!--{/if}-->
	<!--{if $_G['style']['top_nav_dark'] && $_G['style']['top_nav_bgc']}-->
	.dzlogo {display:inline-block;width:140px;height:36px;background:url({STYLEIMGDIR}/logo_hei.png) no-repeat 0 50%;background-size:auto 36px}
	.dzlogo img {display:none}
	.dz_layer_nav ul li a{color: var(--dz-ff);}
	.dz_layer_nav ul li.a a,.dz_layer_nav ul li a:hover{color: var(--dz-ff);}
	.dz_layer_nav ul li a:before{background: var(--dz-bgf);}
	.dz_layer_nav ul li a::after{background: var(--dz-bgf);}
	.header-notice .notice-icon .dzicon {color: var(--dz-ff);}
	.header-searcher input:focus{border-color: var(--dz-bgfglass);}
	.header-notice .notice-icon:hover, .header-notice.open .notice-icon {background: var(--dz-bgfglass);}
	.dz_menumore::after{color: var(--dz-ff);}
	<!--{/if}-->
	<!--{if $_G['style']['bottom_dark'] && $_G['style']['bottom_bgc']}-->
	.dz_footc_dico{background: none;}
	<!--{/if}-->
	<!--{if $_G['style']['viewthread_fastpost'] == 3}-->
	#f_pst{display: none;}
	<!--{/if}-->
	</style>	
