<?php exit('Access Denied');?>
<!DOCTYPE html>
<html>
<head>
	<title>$title</title>
	<meta charset="$charset">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="renderer" content="webkit">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="color-scheme" content="light dark">
	{template admin/rootcolor}
	<link href="{$staticurl}image/admincp/minireset.css?{$_G['style']['verhash']}" rel="stylesheet" />
	<link href="{$staticurl}image/admincp/admincppage.css?{$_G['style']['verhash']}" rel="stylesheet" />
	$pagecss
</head>
<body{if $_G['cookie']['darkmode'] == 'd'} class="st-d"{/if}>
<script type="text/JavaScript">
	var admincpfilename = {echo json_encode($basescript)}, IMGDIR = {echo json_encode($IMGDIR)}, STYLEID = {echo json_encode($STYLEID)}, VERHASH = {echo json_encode($VERHASH)}, IN_ADMINCP = true, ISFRAME = {echo !empty($frame) ? 1 : 0}, STATICURL = 'static/', SITEURL = {echo json_encode($_G['siteurl'])}, JSCACHEPATH = {echo json_encode($_G['setting']['jscachepath'])}, JSPATH = {echo json_encode($_G['setting']['jspath'])}, cookiepre = {echo json_encode($_G['config']['cookie']['cookiepre'])}, cookiedomain = {echo json_encode($_G['config']['cookie']['cookiedomain'])}, cookiepath = {echo json_encode($_G['config']['cookie']['cookiepath'])}, AVATARURL = {echo json_encode($_G['setting']['avatarbase'])}, disallowfloat = {echo json_encode($_G['setting']['disallowfloat'])};
</script>
<script src="{$_G['setting']['jspath']}common.js?{$_G['style']['verhash']}" type="text/javascript"></script>
<script src="{$_G['setting']['jspath']}admincp.js?{$_G['style']['verhash']}" type="text/javascript"></script>
<script type="text/javascript">showretheader({echo json_encode($title)}, {echo json_encode(ADMINSCRIPT.'?frames=yes&action=index&js=yes')});</script>
<div id="append_parent"></div><div id="ajaxwaitid"></div>
<div class="container" id="cpcontainer">
