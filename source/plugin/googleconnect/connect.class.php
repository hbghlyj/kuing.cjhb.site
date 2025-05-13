<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
class plugin_googleconnect {
	public function global_login_extra() {
		global $_G;
		$_G['setting']['pluginhooks']['logging_method'] = $_G['setting']['pluginhooks']['register_logging_method'] = '<div class="g_id_signin"
data-type="standard"
data-shape="pill"
data-theme="filled_blue"
data-text="continue_with"
data-size="large"
data-logo_alignment="left"
     data-width="40">
</div>';
	$_G['setting']['pluginhooks']['logging_method'] .= '<script src="https://accounts.google.com/gsi/client" async></script>';
		return '<script src="https://accounts.google.com/gsi/client" async></script>
		<div id="g_id_onload"
			data-client_id="'.$_G['setting']['connectappid'].'"
			data-context="signin"
			data-login_uri="connect.php?mod=login&op=callback"
			data-auto_select="true"
			data-itp_support="true">
		</div>';
	}
}