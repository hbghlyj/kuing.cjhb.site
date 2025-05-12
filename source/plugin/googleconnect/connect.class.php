<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_googleconnect {
	
	function __construct() {
		global $_G;
		$_G['setting']['pluginhooks']['register_logging_method'] = '<script src="https://accounts.google.com/gsi/client" async></script>
		<div id="g_id_onload"
			data-client_id="'.$_G['setting']['connectappid'].'"
			data-login_uri="connect.php?mod=login&op=callback">
		</div>
		<div class="g_id_signin"
			data-type="standard"
			data-size="large"
			data-theme="outline"
			data-text="sign_in_with"
			data-shape="rectangular"
			data-logo_alignment="left">
		</div>';
	}
}