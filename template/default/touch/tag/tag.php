<?php exit('Access Denied');?>
<!--{template common/header}-->
<!--{if $type != 'countitem'}-->
<div class="header cl">
	<div class="mz"><a href="forum.php"><i class="dm-c-left"></i></a></div>
	<h2>{lang tag}</h2>
	<div class="my"><a href="index.php"><i class="dm-house"></i></a></div>
</div>
<form class="searchform" method="get" action="misc.php">
	<input type="hidden" name="mod" value="tag">
	<div class="search flex-box">
		<input autocomplete="off" class="mtxt flex" name="name" id="scform_srchtxt" value="{echo dhtmlspecialchars($name)}" placeholder="{lang mobsearchtxt}">
		<input type="submit" value="{lang search}" class="mbtn" id="scform_submit">
	</div>
</form>
<div class="bodybox p10 cl">
	<!--{if $tagarray}-->
	<ul class="mobile-tag-list">
		<!--{loop $tagarray $tag}-->
		<li><a href="misc.php?mod=tag&id={echo (int)$tag['tagid']}"><span>{echo dhtmlspecialchars($tag['tagname'])}</span><span class="mobile-tag-count">{echo (int)$tag['related_count']}</span></a></li>
		<!--{/loop}-->
	</ul>
	<!--{else}-->
	<div class="empty-box mt10 cl"><h4>{lang no_tag}</h4></div>
	<!--{/if}-->
	$multipage
</div>
<!--{else}-->
$num
<!--{/if}-->
<!--{template common/footer}-->
