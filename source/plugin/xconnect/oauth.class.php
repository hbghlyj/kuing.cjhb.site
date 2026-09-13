<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_xconnect {
	private function enabled() {
		global $_G;
		return !empty($_G['setting']['xconnect_allow'])
			&& !empty($_G['setting']['xconnect_clientid'])
			&& !empty($_G['setting']['xconnect_clientsecret']);
	}

	private function link() {
		if(!$this->enabled()) {
			return '';
		}
		$url = 'plugin.php?id=xconnect:oauth&op=init&referer='.rawurlencode(dreferer());
		$text = lang('plugin/xconnect', 'xconnect_login_button');
		$icon = '<svg class="vm" aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" style="margin-right:6px;vertical-align:text-bottom;fill:currentColor;"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>';
		return '<a href="'.$url.'" class="pn vm x-login"><span>'.$icon.$text.'</span></a>';
	}

	public function global_login_extra() {
		global $_G;
		$link = $this->link();
		if($link) {
			$_G['setting']['pluginhooks']['logging_method'] = ($_G['setting']['pluginhooks']['logging_method'] ?? '').$link;
			$_G['setting']['pluginhooks']['register_logging_method'] = ($_G['setting']['pluginhooks']['register_logging_method'] ?? '').$link;
		}
		return '';
	}

	public function logging_method() {
		return $this->link();
	}

	public function register_logging_method() {
		return $this->link();
	}
}

