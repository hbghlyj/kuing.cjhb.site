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

	private function link() {
		if(!$this->enabled()) {
			return '';
		}
		$url = 'plugin.php?id=githubconnect:oauth&op=init&referer='.rawurlencode(dreferer());
		$text = lang('plugin/githubconnect', 'githubconnect_login_button');
		return '<a href="'.$url.'" class="pn vm"><span>'.$text.'</span></a>';
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
