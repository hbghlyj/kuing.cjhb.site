<?php exit('Access Denied');?>
<!--{if empty($_G['uploadjs'])}-->
	<link rel="stylesheet" type="text/css" href="{STATICURL}js/webuploader/webuploader.css?{VERHASH}">
	<script src="{STATICURL}js/webuploader/webuploader.js?{VERHASH}"></script>
	<script type="text/javascript" src="{$_G[setting][jspath]}discuz_uploader.js?{VERHASH}"></script>
	{eval $_G['uploadjs'] = 1;}
<!--{/if}-->
