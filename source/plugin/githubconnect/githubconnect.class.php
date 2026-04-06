<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_githubconnect {
	private function enabled() {
		global $_G;
		return !empty($_G['setting']['githubconnect_allow'])
			&& !empty($_G['setting']['githubconnect_clientid'])
			&& !empty($_G['setting']['githubconnect_clientsecret']);
	}

	private function link($compact = false) {
		global $_G;
		if(!$this->enabled()) {
			return '';
		}
		$url = 'plugin.php?id=githubconnect:oauth&op=init&referer='.rawurlencode(dreferer());
		$text = lang('plugin/githubconnect', 'githubconnect_login_button');
		if($compact) {
			return '<div class="y pns"><a href="'.$url.'" class="pn"><em>'.$text.'</em></a></div>';
		}
		return '<a href="'.$url.'" class="pn vm"><span>'.$text.'</span></a>';
	}

	public function global_login_extra() {
		return $this->link(true);
	}

	public function logging_method() {
		return $this->link(false);
	}

	public function register_logging_method() {
		return $this->link(false);
	}
}
