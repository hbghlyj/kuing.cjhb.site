<?php echo '';exit;?>
<!DOCTYPE html>
<html>
	<head>
	<title><!--{if !empty($navtitle)}-->$navtitle - <!--{/if}--><!--{if empty($nobbname)}--> $_G['setting']['bbname']<!--{/if}--></title>
	$_G['setting']['seohead']
	<!--{eval $is_windows_chrome = strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false;}-->
	<!--{eval $is_old_chrome = preg_match('/Windows 7|Windows 8|Windows NT 6|Windows NT 10\.0.*?Chrome\/10[0-9]/', $_SERVER['HTTP_USER_AGENT']);}-->
	<!--{if $is_old_chrome}--><link href="https://fonts.googleapis.com/css2?family=Noto+Colr+Emoji+Glyf" rel="stylesheet"><!--{/if}-->
	<style id="en_font_style">
:root {
--common-font: 'TeX Gyre Schola';
text-autospace: normal;
}
	@font-face {
font-family: 'TeX Gyre Schola';
src: url('/static/webfont/texgyreschola-regular.otf') format('opentype');
font-weight: normal;
font-style: normal;
	}
	@font-face {
font-family: 'TeX Gyre Schola';
src: url('/static/webfont/texgyreschola-bold.otf') format('opentype');
font-weight: bold;
font-style: normal;
	}
	@font-face {
font-family: 'TeX Gyre Schola';
src: url('/static/webfont/texgyreschola-italic.otf') format('opentype');
font-weight: normal;
font-style: italic;
	}
	@font-face {
font-family: 'TeX Gyre Schola';
src: url('/static/webfont/texgyreschola-bolditalic.otf') format('opentype');
font-weight: bold;
font-style: italic;
	}
	</style>
	<style id="zh_font_style">
	@font-face {
	font-family: zh;
	size-adjust: 90%;
	src: local('Noto Serif CJK SC'),local('Noto Sans CJK SC'),local('Noto Serif SC'),local('Noto Sans SC'),
	local('PingFang SC'),
	local('Songti SC'),
	local('DengXian'),local('SimSun'),
	local('STSong'),
	local('MingLiU'),
	local('PMingLiU'),
	local('Songti TC');
	}
	</style>
	<!--{if $is_windows_chrome && !$is_old_chrome}-->
	<style>
	@font-face {
		font-family: "Twemoji Country Flags";
		unicode-range: U+1F1E6-1F1FF, U+1F3F4, U+E0062-E0063, U+E0065, U+E0067, U+E006C, U+E006E, U+E0073-E0074, U+E0077, U+E007F;
		src: url('https://unpkg.com/country-flag-emoji-polyfill/dist/TwemojiCountryFlags.woff2') format('woff2');
		font-display: swap;
	};
	</style>
	<!--{/if}-->
	<!--{hook/global_meta}-->
	<!--{eval include './kk/mathjax.php';}-->
	<!--{csstemplate}-->
	<script type="text/javascript">var STYLEID = '{STYLEID}', STATICURL = '{STATICURL}', IMGDIR = '{IMGDIR}', VERHASH = '{VERHASH}', FORMHASH = '{FORMHASH}', charset = '{CHARSET}', discuz_uid = '$_G[uid]', cookiepre = '{$_G[config][cookie][cookiepre]}', cookiedomain = '{$_G[config][cookie][cookiedomain]}', cookiepath = '{$_G[config][cookie][cookiepath]}', showusercard = '{$_G[setting][showusercard]}', attackevasive = '{$_G[config][security][attackevasive]}', disallowfloat = '{$_G[setting][disallowfloat]}', creditnotice = '<!--{if $_G['setting']['creditnotice']}-->$_G['setting']['creditnames']<!--{/if}-->', defaultstyle = '$_G[style][defaultextstyle]', REPORTURL = '$_G[currenturl_encode]', SITEURL = '$_G[siteurl]', JSPATH = '$_G[setting][jspath]', CSSPATH = '$_G[setting][csspath]', DYNAMICURL = '{$_G[dynamicurl] or ''}', DISCUZ_I18N = '{echo currentlang();}', AVATARURL = '$_G[setting][avatarbase]';</script>
	<script type="text/javascript" src="{$_G[setting][jspath]}common.js?{VERHASH}"></script>
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
	<!--{if $_G['style']['bottom_bgc']}-->
	.dz_footc{background: $_G['style']['bottom_bgc'];}
	<!--{/if}-->
	<!--{if $_G['style']['bottom_dark'] && $_G['style']['bottom_bgc']}-->
	.dz_footc a,.dz_footc_nav .pipe{color: var(--dz-bgfglass);}
	.dz_footc_copy{color: var(--dz-bgfglass);}
	.dz_footc_nav{border-bottom: 1px solid var(--dz-bgfglass);}
	.dz_footc_dico{background: none;}
	<!--{/if}-->
	<!--{if $_G['style']['viewthread_fastpost'] == 3}-->
	#f_pst{display: none;}
	<!--{/if}-->
	</style>	
