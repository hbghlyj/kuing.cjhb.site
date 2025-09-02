<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: helper_sysmessage.php 32459 2013-01-22 02:01:02Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class helper_sysmessage {

	public static function show($message, $title = '', $msgvar = array()) {
		if(function_exists('lang')) {
			$message = lang('message', $message, $msgvar);
			$title = $title ? lang('message', $title) : lang('error', 'System Message');
		} else {
			$title = $title ? $title : 'System Message';
		}
		$charset = CHARSET;
		echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="$charset" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>$title</title>
</head>
<body>
	<table cellpadding="20" cellspacing="0" border="0" width="80%" align="center" style="color: red;">
		<tr>
			<td bgcolor="#EBEBEB" align="center" style="font-size:24px;">
				$title
			</td>
		</tr>
		<tr>
			<td bgcolor="#EBEBEB" style="text-align:left; font-size:16px;">
				$message
			</td>
		</tr>
	</table>
</body>
</html>
EOT;
		die();
	}

}
