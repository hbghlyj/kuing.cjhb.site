<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: discuz_application.php 36342 2017-01-09 01:15:30Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class discuz_application extends discuz_base{


	var $mem = null;

	var $session = null;

	var $config = array();

	var $var = array();

	var $cachelist = array();

	var $init_db = true;
	var $init_setting = true;
	var $init_user = true;
	var $init_session = true;
	var $init_cron = true;
	var $init_misc = true;
	var $init_mobile = true;

	var $initated = false;

	var $superglobal = array(
		'GLOBALS' => 1,
		'_GET' => 1,
		'_POST' => 1,
		'_REQUEST' => 1,
		'_COOKIE' => 1,
		'_SERVER' => 1,
		'_ENV' => 1,
		'_FILES' => 1,
	);

	static function &instance() {
		static $object;
		if(empty($object)) {
			$object = new self();
		}
		return $object;
	}

	public function __construct() {
		$this->_init_cnf();
		$this->_init_env();
		$this->_init_config();
		$this->_init_input();
		$this->_init_output();
	}

	public function init() {
		if(!$this->initated) {
			$this->_init_db();
			$this->_init_setting();
			$this->_init_user();
			$this->_init_session();
			$this->_init_mobile();
			$this->_init_cron();
			$this->_init_misc();
		}
		$this->initated = true;
	}

	private function _init_env() {

		error_reporting(E_ERROR);

		define('ICONV_ENABLE', function_exists('iconv'));
		define('MB_ENABLE', function_exists('mb_convert_encoding'));
		define('EXT_OBGZIP', function_exists('ob_gzhandler'));

		define('TIMESTAMP', time());
		$this->timezone_set();

		if(!defined('DISCUZ_CORE_FUNCTION') && !@include(DISCUZ_ROOT.'./source/function/function_core.php')) {
			exit('function_core.php is missing');
		}

		if(function_exists('ini_get')) {
			$memorylimit = @ini_get('memory_limit');
			if($memorylimit && return_bytes($memorylimit) < 33554432 && function_exists('ini_set')) {
				ini_set('memory_limit', '128m');
			}
		}

		foreach ($GLOBALS as $key => $value) {
			if (!isset($this->superglobal[$key])) {
				$GLOBALS[$key] = null; unset($GLOBALS[$key]);
			}
		}

		if(!defined('APPTYPEID')) {
			define('APPTYPEID', 0);
		}

		if(!defined('CURSCRIPT')) {
			define('CURSCRIPT', null);
		}

		global $_G;
		$_G = array(
			'uid' => 0,
			'username' => '',
			'adminid' => 0,
			'groupid' => 1,
			'sid' => '',
			'formhash' => '',
			'connectguest' => 0,
			'timestamp' => TIMESTAMP,
			'starttime' => microtime(true),
			'clientip' => $this->_get_client_ip(),
			'remoteport' => $_SERVER['REMOTE_PORT'],
			'referer' => '',
			'charset' => '',
			'gzipcompress' => '',
			'authkey' => '',
			'timenow' => array(),
			'widthauto' => 0,
			'disabledwidthauto' => 0,

			'PHP_SELF' => '',
			'siteurl' => '',
			'siteroot' => '',
			'siteport' => '',

			'pluginrunlist' => !defined('PLUGINRUNLIST') ? array() : explode(',', PLUGINRUNLIST),

			'config' => & $this->config,
			'setting' => array(),
			'member' => array(),
			'group' => array(),
			'cookie' => array(),
			'style' => array(),
			'cache' => array(),
			'session' => array(),
			'lang' => array(),

			'fid' => 0,
			'tid' => 0,
			'forum' => array(),
			'thread' => array(),
			'rssauth' => '',

			'home' => array(),
			'space' => array(),

			'block' => array(),
			'article' => array(),

			'action' => array(
				'action' => APPTYPEID,
				'fid' => 0,
				'tid' => 0,
			),

			'mobile' => '',
			'notice_structure' => array(
				'mypost' => array('post','rate','pcomment','activity','reward','goods','at'),
				'interactive' => array('poke','friend','wall','comment','click','sharenotice'),
				'system' => array('system','credit','group','verify','magic','task','show','group','pusearticle','mod_member','blog','article'),
				'manage' => array('mod_member','report','pmreport'),
				'app' => array(),
			),
			'mobiletpl' => array('1' => 'touch', '2' => 'touch', '3' => 'touch', 'yes' => 'touch'),
		);
		$_G['PHP_SELF'] = dhtmlspecialchars($this->_get_script_url());
		$_G['basescript'] = CURSCRIPT;
		$_G['basefilename'] = basename($_G['PHP_SELF']);
		$sitepath = substr($_G['PHP_SELF'], 0, strrpos($_G['PHP_SELF'], '/'));
		if(defined('IN_API')) {
			$sitepath = preg_replace("/\/api\/?.*?$/i", '', $sitepath);
		} elseif(defined('IN_ARCHIVER')) {
			$sitepath = preg_replace("/\/archiver/i", '', $sitepath);
		}
		if(defined('IN_NEWMOBILE')) {
			$sitepath = preg_replace("/\/m/i", '', $sitepath);
		}
		$_G['isHTTPS'] = $this->_is_https();
		$_G['scheme'] = 'http'.($_G['isHTTPS'] ? 's' : '');
		$_G['siteurl'] = dhtmlspecialchars($_G['scheme'].'://'.$_SERVER['HTTP_HOST'].$sitepath.'/');

		$url = parse_url($_G['siteurl']);
		$_G['siteroot'] = isset($url['path']) ? $url['path'] : '';
		$_G['siteport'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ':'.$_SERVER['SERVER_PORT'];

		if(defined('SUB_DIR')) {
			$_G['siteurl'] = str_replace(SUB_DIR, '/', $_G['siteurl']);
			$_G['siteroot'] = str_replace(SUB_DIR, '/', $_G['siteroot']);
		}

		$this->var = & $_G;

	}

	private function _get_script_url() {
		if(!isset($this->var['PHP_SELF'])){
			$scriptName = basename($_SERVER['SCRIPT_FILENAME']);
			if(basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
				$this->var['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
			} else if(basename($_SERVER['PHP_SELF']) === $scriptName) {
				$this->var['PHP_SELF'] = $_SERVER['PHP_SELF'];
			} else if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
				$this->var['PHP_SELF'] = $_SERVER['ORIG_SCRIPT_NAME'];
			} else if(($pos = strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false) {
				$this->var['PHP_SELF'] = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
			} else if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0) {
				$this->var['PHP_SELF'] = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
				$this->var['PHP_SELF'][0] != '/' && $this->var['PHP_SELF'] = '/'.$this->var['PHP_SELF'];
			} else {
				system_error('request_tainting');
			}
		}
		return $this->var['PHP_SELF'];
	}

	private function _init_input() {
		if (isset($_GET['GLOBALS']) ||isset($_POST['GLOBALS']) ||  isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS'])) {
			system_error('request_tainting');
		}

		$prelength = strlen($this->config['cookie']['cookiepre']);
		foreach($_COOKIE as $key => $val) {
			if(substr($key, 0, $prelength) == $this->config['cookie']['cookiepre']) {
				$this->var['cookie'][substr($key, $prelength)] = $val;
			}
		}


		if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
			$_GET = array_merge($_GET, $_POST);
		}

		if(isset($_GET['page'])) {
			$_GET['page'] = rawurlencode($_GET['page']);
		}

		if(!(!empty($_GET['handlekey']) && preg_match('/^\w+$/', $_GET['handlekey']))) {
			unset($_GET['handlekey']);
		}

		if(!empty($this->var['config']['input']['compatible']) && !defined('DISCUZ_DEPRECATED')) {
			foreach($_GET as $k => $v) {
				$this->var['gp_'.$k] = daddslashes($v);
			}
		}

		$this->var['mod'] = empty($_GET['mod']) ? '' : dhtmlspecialchars($_GET['mod']);
		$this->var['inajax'] = empty($_GET['inajax']) ? 0 : (empty($this->var['config']['output']['ajaxvalidate']) ? 1 : ($_SERVER['REQUEST_METHOD'] == 'GET' && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || $_SERVER['REQUEST_METHOD'] == 'POST' ? 1 : 0));
		$this->var['page'] = empty($_GET['page']) ? 1 : max(1, intval($_GET['page']));
		$this->var['sid'] = $this->var['cookie']['sid'] = isset($this->var['cookie']['sid']) ? dhtmlspecialchars($this->var['cookie']['sid']) : '';

		if(empty($this->var['cookie']['saltkey'])) {
			$this->var['cookie']['saltkey'] = random(8);
			dsetcookie('saltkey', $this->var['cookie']['saltkey'], 86400 * 30, 1, 1);
		}
		$this->var['authkey'] = md5($this->var['config']['security']['authkey'].$this->var['cookie']['saltkey']);

	}

	private function _init_cnf() {

		$_config = array();
		@include DISCUZ_ROOT.'./config/config_global.php';
		if(empty($_config)) {
			if(!file_exists(DISCUZ_ROOT.'./data/install.lock')) {
				header('location: install/');
				exit;
			} else {
				system_error('config_notfound');
			}
		}

		$this->config = & $_config;

	}

	private function _init_config() {

		if(empty($this->var['config']['security']['authkey'])) {
			$this->var['config']['security']['authkey'] = md5($this->var['config']['cookie']['cookiepre'].$this->var['config']['db'][1]['dbname']);
		}

		if(empty($this->var['config']['debug']) || !file_exists(libfile('function/debug'))) {
			define('DISCUZ_DEBUG', false);
			error_reporting(0);
		} elseif($this->var['config']['debug'] === 1 || $this->var['config']['debug'] === 2 || !empty($_REQUEST['debug']) && $_REQUEST['debug'] === $this->var['config']['debug']) {
			define('DISCUZ_DEBUG', true);
			error_reporting(E_ERROR);
			if($this->var['config']['debug'] === 2) {
				error_reporting(E_ALL);
			}
		} else {
			define('DISCUZ_DEBUG', false);
			error_reporting(0);
		}

		if(!empty($this->var['config']['deprecated'])) {
			define('DISCUZ_DEPRECATED', $this->var['config']['deprecated']);
		}

		define('STATICURL', !empty($this->var['config']['output']['staticurl']) ? $this->var['config']['output']['staticurl'] : 'static/');
		$this->var['staticurl'] = STATICURL;

		if(substr($this->var['config']['cookie']['cookiepath'], 0, 1) != '/') {
			$this->var['config']['cookie']['cookiepath'] = '/'.$this->var['config']['cookie']['cookiepath'];
		}
		$this->var['config']['cookie']['cookiepre'] = $this->var['config']['cookie']['cookiepre'].substr(md5($this->var['config']['cookie']['cookiepath'].'|'.$this->var['config']['cookie']['cookiedomain']), 0, 4).'_';


	}

	private function _init_output() {


		if($this->config['security']['attackevasive'] && (!defined('CURSCRIPT') || !in_array($this->var['mod'], array('seccode', 'secqaa', 'swfupload')) && !defined('DISABLEDEFENSE'))) {
			require_once libfile('misc/security', 'include');
		}

		if(!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
			$this->config['output']['gzip'] = false;
		}

		$allowgzip = $this->config['output']['gzip'] && empty($this->var['inajax']) && $this->var['mod'] != 'attachment' && EXT_OBGZIP;
		setglobal('gzipcompress', $allowgzip);

		if(!ob_start($allowgzip ? 'ob_gzhandler' : null)) {
			ob_start();
		}

		setglobal('charset', $this->config['output']['charset']);
		define('CHARSET', $this->config['output']['charset']);
		if($this->config['output']['forceheader']) {
			@header('Content-Type: text/html; charset='.CHARSET);
		}

		if($this->var['isHTTPS'] && isset($this->config['output']['upgradeinsecure']) && $this->config['output']['upgradeinsecure']) {
			@header('Content-Security-Policy: upgrade-insecure-requests');
		}

	}

	public function reject_robot() {
		if(IS_ROBOT){
			exit(header("HTTP/1.1 403 Forbidden"));
		}
	}

	private function _xss_check() {

		// static $check = array('"', '>', '<', '\'', '(', ')', 'CONTENT-TRANSFER-ENCODING');

		if(isset($_GET['formhash']) && $_GET['formhash'] !== formhash()) {
			if(defined('CURMODULE') && constant('CURMODULE') == 'logging' && isset($_GET['action']) && $_GET['action'] == 'logout') {
				header("HTTP/1.1 302 Found");
				header("Location: index.php");
				exit();
			} else {
				system_error('request_tainting');
			}
		}

		if($_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$temp = $_SERVER['REQUEST_URI'];
		} elseif(empty ($_GET['formhash'])) {
			$temp = $_SERVER['REQUEST_URI'].http_build_query($_POST);
		} else {
			$temp = '';
		}

		if(!empty($temp)) {
			$temp = strtoupper(urldecode(urldecode($temp)));
			foreach ($check as $str) {
				if(strpos($temp, $str) !== false) {
					system_error('request_tainting');
				}
			}
		}

		return true;
	}

	private function _is_https() {
		if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
			return true;
		}
		if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
			return true;
		}
		if(isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https') {
			return true;
		}
		if(isset($_SERVER['HTTP_FROM_HTTPS']) && strtolower($_SERVER['HTTP_FROM_HTTPS']) != 'off') {
			return true;
		}
		if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
			return true;
		}
		return false;
	}

	private function _get_client_ip() {
		$ip = $_SERVER['REMOTE_ADDR'];
		if (!array_key_exists('security', $this->config) || !$this->config['security']['onlyremoteaddr']) {
			if (array_key_exists('ipgetter', $this->config) && !empty($this->config['ipgetter']['setting'])) {
				$s = empty($this->config['ipgetter'][$this->config['ipgetter']['setting']]) ? array() : $this->config['ipgetter'][$this->config['ipgetter']['setting']];
				$c = 'ip_getter_'.$this->config['ipgetter']['setting'];
				$r = $c::get($s);
				$ip = ip::validate_ip($r) ? $r : $ip;
			} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && ip::validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ",") > 0) {
					$exp = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
					$ip = ip::validate_ip(trim($exp[0])) ? $exp[0] : $ip;
				} else {
					$ip = ip::validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
				}
			}
		}
		return $ip;
	}

	private function _init_db() {
		if($this->init_db) {
			$driver = 'db_driver_mysqli';
			if(getglobal('config/db/slave')) {
				$driver = 'db_driver_mysqli_slave';
			}
			DB::init($driver, $this->config['db']);
		}
	}

	private function _init_session() {
		if (empty($_SERVER['HTTP_USER_AGENT']) || strlen($_SERVER['HTTP_USER_AGENT']) < 2) {
			define('IS_ROBOT', 'UnusualUserAgentHeader');
		} else {
			// https://networksdb.io/ip-addresses-of/alibaba-cloud-singapore-private-ltd
			static $cidrs_array = array("Alibaba Cloud Singapore" => ['43.0.0.0/9','8.128.0.0/10','8.208.0.0/12','161.117.0.0/16','170.33.0.0/16','149.129.0.0/16','103.206.40.0/22','14.1.112.0/22','185.218.176.0/22'],
			// https://www.ip2location.com/as45102
			"Alibaba CN Net" => ['5.181.224.0/23','8.208.0.0/17','8.208.128.0/21','8.208.140.0/22','8.208.144.0/20','8.208.160.0/19','8.208.192.0/18','8.209.0.0/19','8.209.36.0/22','8.209.40.0/21','8.209.48.0/20','8.209.64.0/19','8.209.96.0/20','8.209.112.0/21','8.209.120.0/23','8.209.123.0/24','8.209.124.0/22','8.209.128.0/17','8.210.0.0/16','8.211.0.0/17','8.211.128.0/18','8.211.192.0/19','8.211.224.0/22','8.211.232.0/21','8.211.240.0/20','8.212.0.0/18','8.212.64.0/20','8.212.80.0/21','8.212.88.0/22','8.212.92.0/24','8.212.94.0/23','8.212.96.0/22','8.212.104.0/21','8.212.112.0/20','8.212.128.0/19','8.212.160.0/20','8.212.176.0/21','8.212.188.0/22','8.212.192.0/18','8.213.0.0/17','8.213.128.0/19','8.213.160.0/21','8.213.176.0/20','8.213.192.0/18','8.214.0.0/17','8.214.128.0/19','8.214.164.0/22','8.214.168.0/21','8.214.176.0/20','8.214.192.0/18','8.215.0.0/17','8.215.128.0/19','8.215.160.0/22','8.215.168.0/21','8.215.176.0/20','8.215.192.0/18','8.216.0.0/18','8.216.64.0/21','8.216.72.0/22','8.216.80.0/20','8.216.96.0/19','8.216.128.0/17','8.217.0.0/16','8.218.0.0/15','8.220.64.0/19','8.220.96.0/20','8.220.112.0/21','8.220.120.0/22','8.220.128.0/19','8.220.160.0/21','8.220.171.0/24','8.220.172.0/22','8.220.176.0/20','8.220.192.0/19','8.220.224.0/21','8.220.232.0/22','8.220.240.0/21','8.221.0.0/17','8.221.128.0/20','8.221.144.0/21','8.221.156.0/22','8.221.160.0/19','8.221.192.0/18','8.222.0.0/15','14.1.112.0/22','43.91.0.0/16','43.96.3.0/24','43.96.4.0/23','43.96.7.0/24','43.96.8.0/24','43.96.10.0/23','43.96.20.0/22','43.96.24.0/23','43.96.32.0/22','43.96.40.0/24','43.96.66.0/23','43.96.68.0/22','43.96.72.0/22','43.96.80.0/23','43.96.85.0/24','43.96.88.0/24','43.96.96.0/24','43.96.100.0/23','43.96.102.0/24','43.98.0.0/15','43.100.0.0/15','43.104.0.0/15','43.108.0.0/17','45.196.28.0/24','45.199.179.0/24','47.52.0.0/16','47.56.0.0/16','47.57.0.0/17','47.57.128.0/18','47.57.192.0/22','47.57.196.0/24','47.57.198.0/23','47.57.200.0/22','47.57.204.0/23','47.57.206.0/24','47.57.208.0/20','47.57.224.0/19','47.74.0.0/15','47.76.0.0/16','47.77.0.0/20','47.77.16.0/21','47.77.24.0/22','47.77.32.0/19','47.77.64.0/19','47.77.96.0/20','47.77.128.0/17','47.78.0.0/16','47.79.0.0/17','47.79.128.0/19','47.79.192.0/18','47.80.0.0/15','47.82.0.0/17','47.83.0.0/16','47.84.0.0/15','47.86.0.0/16','47.87.0.0/20','47.87.16.0/21','47.87.25.0/24','47.87.26.0/23','47.87.29.0/24','47.87.30.0/23','47.87.32.0/19','47.87.64.0/21','47.87.80.0/20','47.87.96.0/19','47.87.128.0/20','47.87.144.0/21','47.87.156.0/22','47.87.160.0/19','47.87.192.0/19','47.87.224.0/21','47.87.232.0/22','47.88.0.0/16','47.89.0.0/18','47.89.72.0/21','47.89.80.0/22','47.89.84.0/24','47.89.88.0/23','47.89.90.0/24','47.89.92.0/22','47.89.96.0/20','47.89.122.0/23','47.89.124.0/23','47.89.128.0/17','47.90.0.0/17','47.90.128.0/19','47.90.160.0/21','47.90.168.0/22','47.90.176.0/20','47.90.192.0/18','47.91.0.0/16','47.235.0.0/21','47.235.8.0/22','47.235.12.0/23','47.235.16.0/24','47.235.18.0/23','47.235.20.0/22','47.235.24.0/23','47.235.27.0/24','47.235.28.0/22','47.236.0.0/17','47.236.128.0/18','47.236.192.0/20','47.236.208.0/21','47.236.220.0/22','47.236.224.0/19','47.237.0.0/17','47.237.128.0/18','47.237.192.0/19','47.237.224.0/20','47.237.240.0/21','47.237.249.0/24','47.237.250.0/23','47.237.252.0/22','47.238.0.0/20','47.238.16.0/21','47.238.28.0/22','47.238.32.0/19','47.238.64.0/18','47.238.128.0/17','47.239.0.0/16','47.240.0.0/14','47.244.0.0/16','47.245.0.0/17','47.245.128.0/20','47.245.144.0/21','47.245.152.0/22','47.245.160.0/19','47.245.192.0/20','47.245.208.0/21','47.245.216.0/22','47.245.224.0/19','47.246.32.0/22','47.246.66.0/23','47.246.68.0/23','47.246.72.0/21','47.246.80.0/24','47.246.82.0/23','47.246.84.0/22','47.246.88.0/23','47.246.91.0/24','47.246.92.0/23','47.246.96.0/22','47.246.101.0/24','47.246.102.0/23','47.246.104.0/21','47.246.120.0/24','47.246.122.0/23','47.246.124.0/23','47.246.128.0/23','47.246.131.0/24','47.246.132.0/23','47.246.135.0/24','47.246.136.0/21','47.246.144.0/22','47.246.150.0/23','47.246.152.0/22','47.246.157.0/24','47.246.158.0/23','47.246.160.0/23','47.246.162.0/24','47.246.164.0/22','47.246.168.0/21','47.246.176.0/20','47.246.192.0/21','47.250.0.0/18','47.250.68.0/22','47.250.72.0/21','47.250.80.0/20','47.250.96.0/21','47.250.108.0/22','47.250.112.0/20','47.250.128.0/17','47.251.0.0/17','47.251.132.0/22','47.251.136.0/21','47.251.144.0/20','47.251.160.0/19','47.251.196.0/22','47.251.200.0/21','47.251.208.0/20','47.251.224.0/19','47.252.0.0/16','47.253.0.0/18','47.253.64.0/19','47.253.96.0/20','47.253.112.0/21','47.253.120.0/22','47.253.128.0/17','47.254.0.0/16','59.82.136.0/23','103.81.187.0/24','110.76.21.0/24','110.76.23.0/24','116.251.64.0/18','139.95.0.0/20','139.95.16.0/22','139.95.64.0/24','140.205.1.0/24','140.205.122.0/24','147.139.0.0/17','147.139.128.0/18','147.139.192.0/19','147.139.224.0/20','147.139.240.0/21','147.139.248.0/22','149.129.0.0/20','149.129.16.0/23','149.129.32.0/19','149.129.64.0/18','149.129.192.0/18','156.227.20.0/24','156.236.12.0/24','156.236.17.0/24','156.240.77.0/24','156.245.1.0/24','161.117.0.0/16','170.33.32.0/22','170.33.64.0/23','170.33.66.0/24','170.33.68.0/23','170.33.80.0/22','170.33.84.0/23','170.33.86.0/24','170.33.104.0/22','170.33.136.0/23','170.33.138.0/24','198.11.128.0/20','198.11.145.0/24','198.11.146.0/23','198.11.148.0/22','198.11.152.0/21','198.11.160.0/19','202.144.199.0/24','203.107.64.0/22','203.107.68.0/24','205.204.96.0/19','223.5.5.0/24','223.6.6.0/24'],
			// https://www.ip2location.com/as7941
			"Internet Archive" => ['207.241.224.0/20','207.241.224.0/24','207.241.234.0/24','207.241.237.0/24','208.70.24.0/21', '2620:0:9c0::/48']);
			foreach($cidrs_array as $user_agent_keyword => $cidrs) {
				if (ip::check_ip($this->var['clientip'], $cidrs)) {
					DB::query('UPDATE common_robot_user_agents SET last_seen_at = UNIX_TIMESTAMP(), seen_count = seen_count + 1, category = ' . DB::quote($_SERVER['HTTP_X_KNOWN_BOT']).' WHERE user_agent_keyword = ' . DB::quote($user_agent_keyword));
					define('IS_ROBOT', value: $user_agent_keyword . "\t");
					break;
				}
			}
			if(!defined('IS_ROBOT')) {
				$robot_entry = DB::fetch_first("SELECT id,first_seen_at,user_agent_keyword FROM common_robot_user_agents WHERE %s LIKE CONCAT(%s, user_agent_keyword, %s) ORDER BY LENGTH(user_agent_keyword) DESC, seen_count DESC LIMIT 1",array($_SERVER['HTTP_USER_AGENT'], '%', '%'));
				// Check HTTP_X_KNOWN_BOT header (e.g., from Cloudflare)
				if (isset($_SERVER['HTTP_X_KNOWN_BOT'])) {
					if ($robot_entry) {
						DB::query('UPDATE common_robot_user_agents SET last_seen_at = UNIX_TIMESTAMP(), seen_count = seen_count + 1, category = ' . DB::quote($_SERVER['HTTP_X_KNOWN_BOT']) .
						($robot_entry['first_seen_at'] ? '' : ', first_seen_at = UNIX_TIMESTAMP()').
						' WHERE id = ' . $robot_entry['id']);
						define('IS_ROBOT', $robot_entry['user_agent_keyword'] . "\t");
					} else {
						DB::insert('common_robot_user_agents', array(
							'user_agent_keyword' => $_SERVER['HTTP_USER_AGENT'],
							'category' => $_SERVER['HTTP_X_KNOWN_BOT'],
							'first_seen_at' => TIMESTAMP,
							'last_seen_at' => TIMESTAMP,
							'seen_count' => 1
						), false, true); // silent insert, true for replace/ignore based on unique key
						define('IS_ROBOT', $_SERVER['HTTP_X_KNOWN_BOT'] . "\t");
					}
				} elseif ($robot_entry) {
					DB::query('UPDATE common_robot_user_agents SET last_seen_at=UNIX_TIMESTAMP(), seen_count = seen_count + 1'.
					($robot_entry['first_seen_at'] ? '' : ', first_seen_at = UNIX_TIMESTAMP()').
					' WHERE id = '.$robot_entry['id']);
					define('IS_ROBOT', $robot_entry['user_agent_keyword'] . "\t");
				} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'http://') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'https://') !== false) {
					// If it reaches here, it means it has a URL and wasn't caught by specific rules.
					DB::insert('common_robot_user_agents', array(
						'user_agent_keyword' => $_SERVER['HTTP_USER_AGENT'],
						'category' => 'URL in User Agent',
						'first_seen_at' => TIMESTAMP,
						'last_seen_at' => TIMESTAMP,
						'seen_count' => 1
					), false, true); // silent insert
					define('IS_ROBOT', $_SERVER['HTTP_USER_AGENT'] . "\t");
				}elseif(empty($_SERVER['HTTP_SEC_FETCH_MODE'])) {
					define('IS_ROBOT', 'UnusualSecFetchModeHeader');
				}elseif(strpos($_SERVER['HTTP_ACCEPT'], '*/*') === false || $_SERVER['HTTP_SEC_FETCH_MODE'] != 'cors' && $_SERVER['HTTP_SEC_FETCH_MODE'] != 'no-cors' && stripos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) {
					define('IS_ROBOT', 'UnusualAcceptHeader');
				}elseif(strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) < 2){
					define('IS_ROBOT', 'UnusualAcceptLanguageHeader');
				}elseif(strlen($_SERVER['HTTP_ACCEPT_ENCODING']) < 2){
					define('IS_ROBOT', 'UnusualAcceptEncodingHeader');
				}else{
					define('IS_ROBOT', false);
				}
			}
		}
		$sessionclose = !empty($this->var['setting']['sessionclose']);
		$this->session = $sessionclose ? new discuz_session_close() : new discuz_session();

		if($this->init_session)	{
			if($this->var['uid']==0) {
				function getFlagEmoji($countryCode) {
					// Validate input: must be exactly 2 letters
					if (strlen($countryCode) !== 2) {
						return null;
					}
					// Handle Cloudflare special https://developers.cloudflare.com/fundamentals/reference/http-headers/#cf-ipcountry
					if ($countryCode === 'XX') {
						return '🌐'; // Globe emoji for unspecified country
					} elseif ($countryCode === 'T1') {
						return '🧅'; // Onion emoji for Tor network
					}
					// The regional indicator symbols start at the Unicode codepoint U+1F1E6
					$baseCodePoint = 0x1F1E6;
					// Calculate the code points for each of the two letters
					$firstLetterCodePoint = $baseCodePoint + (ord($countryCode[0]) - ord('A'));
					$secondLetterCodePoint = $baseCodePoint + (ord($countryCode[1]) - ord('A'));
					// Convert the code points into UTF-8 characters
					$firstEmoji = mb_chr($firstLetterCodePoint, 'UTF-8');
					$secondEmoji = mb_chr($secondLetterCodePoint, 'UTF-8');
					return $firstEmoji . $secondEmoji;
				}
				$this->session->init($this->var['cookie']['sid'], $this->var['clientip'], 0);
				if(IS_ROBOT){
					$this->var['member']['groupid'] = 8;
					$this->var['member']['username'] = IS_ROBOT;
				}
				$this->var['member']['username'] .= getFlagEmoji($_SERVER["HTTP_CF_IPCOUNTRY"]) . $_SERVER["HTTP_CF_IPCITY"] . ((strpos($this->var['member']['username'], "\n") === false&&isset($_SERVER["HTTP_REFERER"])) ? "\n" . $_SERVER["HTTP_REFERER"] : '');
			}else{
				define('IS_ROBOT', false);
				$this->session->init($this->var['cookie']['sid'], $this->var['clientip'], $this->var['uid']);
			}
			$this->var['sid'] = $this->session->sid;
			$this->var['session'] = $this->session->var;

			if(isset($this->var['sid']) && $this->var['sid'] !== $this->var['cookie']['sid']) {
				dsetcookie('sid', $this->var['sid'], 86400);
			}

			if(dstrpos($_SERVER['HTTP_USER_AGENT'],array('MQQBrowser',' qq','MicroMessenger'))) {
				include(DISCUZ_ROOT.'kk/MicroMessenger.php');
				exit;
			}
			if(ip::checkbanned($this->var['clientip'])) {
				$this->session->set('groupid', 6);
			}

			if($this->session->get('groupid') == 6) {
				$this->var['member']['groupid'] = 6;
				if(!defined('IN_MOBILE_API')) {
					sysmessage('user_banned');
				} else {
					mobile_core::result(array('error' => 'user_banned'));
				}
			}

			if($this->var['uid'] && !$sessionclose && ($this->session->isnew || ($this->session->get('lastactivity') + 600) < TIMESTAMP)) {
				$this->session->set('lastactivity', TIMESTAMP);
				if($this->session->isnew) {
					if($this->var['member']['lastip'] && $this->var['member']['lastvisit']) {
						dsetcookie('lip', $this->var['member']['lastip'].','.$this->var['member']['lastvisit']);
					}
					C::t('common_member_status')->update($this->var['uid'], array('lastip' => $this->var['clientip'], 'port' => $this->var['remoteport'], 'lastvisit' => TIMESTAMP));
				}
			}

		}
	}

	private function _init_user() {
		if($this->init_user) {
			if($auth = getglobal('auth', 'cookie')) {
				$auth = daddslashes(explode("\t", authcode($auth, 'DECODE')));
			}
			list($discuz_pw, $discuz_uid) = empty($auth) || count($auth) < 2 ? array('', '') : $auth;

			if($discuz_uid) {
				$user = getuserbyuid($discuz_uid, 1);
			}

			if(!empty($user) && $user['password'] == $discuz_pw) {
				if(isset($user['_inarchive'])) {
					C::t('common_member_archive')->move_to_master($discuz_uid);
				}
				$this->var['member'] = $user;
			} else {
				$user = array();
				$this->_init_guest();
			}

			if($user && $user['groupexpiry'] > 0 && $user['groupexpiry'] < TIMESTAMP) {
				$memberfieldforum = C::t('common_member_field_forum')->fetch($discuz_uid);
				$groupterms = dunserialize($memberfieldforum['groupterms']);
				if(!empty($groupterms['main'])) {
					if($groupterms['main']['groupid']) {
						$user['groupid'] = $groupterms['main']['groupid'];
					} else {
						$groupnew = C::t('common_usergroup')->fetch_by_credits($user['credits']);
						$user['groupid'] = $groupnew['groupid'];
					}
					$user['adminid'] = $groupterms['main']['adminid'];
					C::t("common_member")->update($user['uid'], array('groupexpiry'=> 0, 'groupid' => $user['groupid'], 'adminid' => $user['adminid']));
					unset($groupterms['main'], $groupterms['ext'][$this->var['member']['groupid']]);
					$this->var['member'] = $user;
					C::t('common_member_field_forum')->update($discuz_uid, array('groupterms' => serialize($groupterms)));
				} elseif((getgpc('mod') != 'spacecp' || CURSCRIPT != 'home') && CURSCRIPT != 'member') {
					dheader('location: home.php?mod=spacecp&ac=usergroup&do=expiry');
				}
			}

			if($user && $user['freeze'] && (getgpc('mod') != 'spacecp' && getgpc('mod') != 'misc'  || CURSCRIPT != 'home') && CURSCRIPT != 'member' && CURSCRIPT != 'misc') {
				dheader('location: home.php?mod=spacecp&ac=profile&op=password');
			}

			$this->cachelist[] = 'usergroup_'.$this->var['member']['groupid'];
			if($user && $user['adminid'] > 0 && $user['groupid'] != $user['adminid']) {
				$this->cachelist[] = 'admingroup_'.$this->var['member']['adminid'];
			}

		} else {
			$this->_init_guest();
		}
		setglobal('groupid', getglobal('groupid', 'member'));
		!empty($this->cachelist) && loadcache($this->cachelist);

		if($this->var['member'] && $this->var['group']['radminid'] == 0 && $this->var['member']['adminid'] > 0 && $this->var['member']['groupid'] != $this->var['member']['adminid'] && !empty($this->var['cache']['admingroup_'.$this->var['member']['adminid']])) {
			$this->var['group'] = array_merge($this->var['group'], $this->var['cache']['admingroup_'.$this->var['member']['adminid']]);
		}

		if(!empty($this->var['group']['allowmakehtml']) && isset($_GET['_makehtml'])) {
			$this->var['makehtml'] = 1;
			$this->_init_guest();
			loadcache(array('usergroup_7'));
			$this->var['group'] = $this->var['cache']['usergroup_7'];
			unset($this->var['inajax']);
		}

		if(empty($this->var['cookie']['lastvisit'])) {
			$this->var['member']['lastvisit'] = TIMESTAMP - 3600;
			dsetcookie('lastvisit', TIMESTAMP - 3600, 86400 * 30);
		} else {
			$this->var['member']['lastvisit'] = $this->var['cookie']['lastvisit'];
		}

		setglobal('uid', getglobal('uid', 'member'));
		setglobal('username', getglobal('username', 'member'));
		setglobal('adminid', getglobal('adminid', 'member'));
		setglobal('groupid', getglobal('groupid', 'member'));
		if(!empty($this->var['member']['newprompt'])) {
			$this->var['member']['newprompt_num'] = C::t('common_member_newprompt')->fetch($this->var['member']['uid']);
			$this->var['member']['newprompt_num'] = dunserialize($this->var['member']['newprompt_num']['data']);
			$this->var['member']['category_num'] = helper_notification::get_categorynum($this->var['member']['newprompt_num']);
		}

	}

	private function _init_guest() {
		$username = '';
		$groupid = 7;
		if(!empty($this->var['cookie']['con_auth_hash']) && ($openid = authcode($this->var['cookie']['con_auth_hash']))) {
			$this->var['connectguest'] = 1;
			$username = 'QQ_'.substr($openid, -6);
			$this->var['setting']['cacheindexlife'] = 0;
			$this->var['setting']['cachethreadlife'] = 0;
			$groupid = $this->var['setting']['connect']['guest_groupid'] ? $this->var['setting']['connect']['guest_groupid'] : $this->var['setting']['newusergroupid'];
		}
		setglobal('member', array( 'uid' => 0, 'username' => $username, 'adminid' => 0, 'groupid' => $groupid, 'credits' => 0, 'timeoffset' => 9999));
	}

	private function _init_cron() {
		$ext = empty($this->config['remote']['on']) || empty($this->config['remote']['cron']) || APPTYPEID == 200;
		if($this->init_cron && $this->init_setting && $ext) {
			if($this->var['cache']['cronnextrun'] <= TIMESTAMP) {
				discuz_cron::run();
			}
		}
	}

	private function _init_misc() {

		if($this->config['security']['urlxssdefend'] && !defined('DISABLEXSSCHECK')) {
			$this->_xss_check();
		}

		if(!$this->init_misc) {
			return false;
		}
		lang('core');

		if($this->init_setting && $this->init_user) {
			if(!isset($this->var['member']['timeoffset']) || $this->var['member']['timeoffset'] == 9999 || $this->var['member']['timeoffset'] === '') {
				$this->var['member']['timeoffset'] = $this->var['setting']['timeoffset'];
			}
		}

		$timeoffset = $this->init_setting ? $this->var['member']['timeoffset'] : $this->var['setting']['timeoffset'];
		$this->var['timenow'] = array(
			'time' => dgmdate(TIMESTAMP),
			'offset' => $timeoffset >= 0 ? ($timeoffset == 0 ? '' : '+'.$timeoffset) : $timeoffset
		);
		$this->timezone_set($timeoffset);

		$this->var['formhash'] = formhash();
		define('FORMHASH', $this->var['formhash']);

		if($this->init_user) {
			$allowvisitflag = in_array(CURSCRIPT, array('member')) || defined('ALLOWGUEST') && ALLOWGUEST;
			if($this->var['group'] && isset($this->var['group']['allowvisit']) && !$this->var['group']['allowvisit']) {
				if($this->var['uid'] && !$allowvisitflag) {
					if(!defined('IN_MOBILE_API')) {
						($this->var['member']['groupexpiry'] > 0) ? showmessage('user_banned_has_expiry', '', array('expiry' => dgmdate($this->var['member']['groupexpiry'], 'Y-m-d H:i:s'))) : showmessage('user_banned');
					} else {
						($this->var['member']['groupexpiry'] > 0) ? mobile_core::result(array('error' => 'user_banned_has_expiry')) : mobile_core::result(array('error' => 'user_banned'));
					}
				} elseif((!defined('ALLOWGUEST') || !ALLOWGUEST) && !in_array(CURSCRIPT, array('member', 'api'))) {
					if(defined('IN_ARCHIVER')) {
						dheader('location: ../member.php?mod=logging&action=login&referer='.rawurlencode($this->var['siteurl']."archiver/".$this->var['basefilename'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '')));
					} else if(!defined('IN_MOBILE_API')) {
						dheader('location: member.php?mod=logging&action=login&referer='.rawurlencode($this->var['siteurl'].$this->var['basefilename'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '')));
					} else {
						mobile_core::result(array('error' => 'to_login'));
					}
				}
			}
			if(isset($this->var['member']['status']) && $this->var['member']['status'] == -1 && !$allowvisitflag) {
				if(!defined('IN_MOBILE_API')) {
					showmessage('user_banned');
				} else {
					mobile_core::result(array('error' => 'user_banned'));
				}
			}
		}

		if($this->var['setting']['ipaccess'] && !ipaccess($this->var['clientip'], $this->var['setting']['ipaccess'])) {
			if(!defined('IN_MOBILE_API')) {
				showmessage('user_banned');
			} else {
				mobile_core::result(array('error' => 'user_banned'));
			}
		}

		if($this->var['setting']['bbclosed']) {
			if($this->var['uid'] && ($this->var['group']['allowvisit'] == 2 || $this->var['groupid'] == 1)) {
			} elseif(in_array(CURSCRIPT, array('admin', 'member', 'api')) || defined('ALLOWGUEST') && ALLOWGUEST) {
			} else {
				$closedreason = C::t('common_setting')->fetch_setting('closedreason');
				$closedreason = str_replace(':', '&#58;', $closedreason);
				if(!defined('IN_MOBILE_API')) {
					showmessage($closedreason ? $closedreason : 'board_closed', NULL, array('adminemail' => $this->var['setting']['adminemail']), array('login' => 1));
				} else {
					mobile_core::result(array('error' => $closedreason ? $closedreason : 'board_closed'));
				}
			}
		}

		if(CURSCRIPT != 'admin' && !(in_array($this->var['mod'], array('logging', 'seccode')))) {
			periodscheck('visitbanperiods');
		}

		if(defined('IN_MOBILE')) {
			$this->var['tpp'] = $this->var['setting']['mobile']['forum']['topicperpage'] ? intval($this->var['setting']['mobile']['forum']['topicperpage']) : ($this->var['setting']['topicperpage'] ? intval($this->var['setting']['topicperpage']) : 20);
			$this->var['ppp'] = $this->var['setting']['mobile']['forum']['postperpage'] ? intval($this->var['setting']['mobile']['forum']['postperpage']) : ($this->var['setting']['postperpage'] ? intval($this->var['setting']['postperpage']) : 10);
		} else {
			$this->var['tpp'] = $this->var['setting']['topicperpage'] ? intval($this->var['setting']['topicperpage']) : 20;
			$this->var['ppp'] = $this->var['setting']['postperpage'] ? intval($this->var['setting']['postperpage']) : 10;
		}

		if($this->var['setting']['nocacheheaders']) {
			@header("Expires: -1");
			@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
			@header("Pragma: no-cache");
		}

		if($this->session->isnew && $this->var['uid']) {
			updatecreditbyaction('daylogin', $this->var['uid']);

			include_once libfile('function/stat');
			updatestat('login', 1);
			if(defined('IN_MOBILE')) {
				updatestat('mobilelogin', 1);
			}
			if($this->var['setting']['connect']['allow'] && $this->var['member']['conisbind']) {
				updatestat('connectlogin', 1);
			}
		}
		if(isset($this->var['member']['conisbind']) && $this->var['member']['conisbind'] && $this->var['setting'] && $this->var['setting']['connect']['newbiespan'] !== '') {
			$this->var['setting']['newbiespan'] = $this->var['setting']['connect']['newbiespan'];
		}

		$lastact = TIMESTAMP."\t".dhtmlspecialchars(basename($this->var['PHP_SELF']))."\t".dhtmlspecialchars($this->var['mod']);
		dsetcookie('lastact', $lastact, 86400);
		setglobal('currenturl_encode', base64_encode($this->var['scheme'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));

		if((!empty($_GET['fromuid']) || !empty($_GET['fromuser'])) && ($this->var['setting']['creditspolicy']['promotion_visit'] || $this->var['setting']['creditspolicy']['promotion_register'])) {
			require_once libfile('misc/promotion', 'include');
		}

		$this->var['seokeywords'] = !empty($this->var['setting']['seokeywords'][CURSCRIPT]) ? $this->var['setting']['seokeywords'][CURSCRIPT] : '';
		$this->var['seodescription'] = !empty($this->var['setting']['seodescription'][CURSCRIPT]) ? $this->var['setting']['seodescription'][CURSCRIPT] : '';

	}

	private function _init_setting() {
		if($this->init_setting) {
			if(empty($this->var['setting'])) {
				$this->cachelist[] = 'setting';
			}

			if(empty($this->var['style'])) {
				$this->cachelist[] = 'style_default';
			}

			if(!isset($this->var['cache']['cronnextrun'])) {
				$this->cachelist[] = 'cronnextrun';
			}
		}

		!empty($this->cachelist) && loadcache($this->cachelist);

		if(!is_array($this->var['setting']) && !is_a($this->var['setting'], 'memory_setting_array')) {
			$this->var['setting'] = array();
		}

	}

	public function _init_style() {
		if(defined('IN_MOBILE')) {
			$mobile = max(1, intval(IN_MOBILE));
			if($mobile && $this->var['setting']['styleid'.$mobile]) {
				$styleid = $this->var['setting']['styleid'.$mobile];
			}
		} else {
			$styleid = !empty($this->var['cookie']['styleid']) ? $this->var['cookie']['styleid'] : 0;

			if(intval(!empty($this->var['forum']['styleid']))) {
				$this->var['cache']['style_default']['styleid'] = $styleid = $this->var['forum']['styleid'];
			} elseif(intval(!empty($this->var['category']['styleid']))) {
				$this->var['cache']['style_default']['styleid'] = $styleid = $this->var['category']['styleid'];
			}
		}

		if(defined('IN_NEWMOBILE') && $this->var['setting']['mobile']['allowmnew'] && $this->var['setting']['styleid2']) {
			$styleid = $this->var['setting']['styleid2'];
		}

		$styleid = intval($styleid);

		if($styleid && $styleid != $this->var['setting']['styleid']) {
			loadcache('style_'.$styleid);
			if($this->var['cache']['style_'.$styleid]) {
				$this->var['style'] = $this->var['cache']['style_'.$styleid];
			}
		}

		define('IMGDIR', $this->var['style']['imgdir']);
		define('STYLEID', $this->var['style']['styleid']);
		define('VERHASH', $this->var['style']['verhash']);
		define('TPLDIR', $this->var['style']['tpldir']);
		define('TEMPLATEID', $this->var['style']['templateid']);
	}

	private function _init_mobile() {
		if(!$this->init_mobile) {
			if(!defined('HOOKTYPE')) {
				define('HOOKTYPE', 'hookscript');
			}
			return false;
		}

		if(!$this->var['setting'] || !$this->var['setting']['mobile']['allowmobile'] || !is_array($this->var['setting']['mobile'])) {
			$nomobile = true;
		}

		if(getgpc('forcemobile')) {
			dsetcookie('dismobilemessage', '1', 3600);
		}

		$mobile = getgpc('mobile');
		$mobileflag = isset($this->var['mobiletpl'][$mobile]);
		if($mobile == 'no' || IS_ROBOT) {
			dsetcookie('mobile', 'no', 31536000);
			$nomobile = true;
		} elseif(isset($this->var['cookie']['mobile']) && $this->var['cookie']['mobile'] == 'no' && $mobileflag) {
			dsetcookie('mobile', '');
		} elseif(isset($this->var['cookie']['mobile']) && $this->var['cookie']['mobile'] == 'no') {
			$nomobile = true;
		} elseif(!$mobile && !checkmobile()) {
			$nomobile = true;
		}

		if($nomobile || (!$this->var['setting']['mobile']['mobileforward'] && !$mobileflag)) {
			if(!defined('HOOKTYPE')) {
				define('HOOKTYPE', 'hookscript');
			}
			if(!empty($this->var['setting']['domain']['app']['mobile']) && $_SERVER['HTTP_HOST'] == $this->var['setting']['domain']['app']['mobile'] && !empty($this->var['setting']['domain']['app']['default'])) {
				dheader('Location:'.$this->var['scheme'].'://'.$this->var['setting']['domain']['app']['default'].$_SERVER['REQUEST_URI']);
				return false;
			} else {
				return false;
			}
		}

		if($mobile !== '2' && $mobile !== '3' && empty($this->var['setting']['mobile']['legacy'])) {
			$mobile = '2';
		}
		define('IN_MOBILE', isset($this->var['mobiletpl'][$mobile]) ? $mobile : '2');
		if(!defined('HOOKTYPE')) {
			define('HOOKTYPE', 'hookscriptmobile');
		}
		setglobal('gzipcompress', 0);

		$arr = array();
		foreach(array_keys($this->var['mobiletpl']) as $mobiletype) {
			$arr[] = '&mobile='.$mobiletype;
			$arr[] = 'mobile='.$mobiletype;
		}

		parse_str($_SERVER['QUERY_STRING'], $query);
		$query['mobile'] = 'no';
		unset($query['simpletype']);
		$query_sting_tmp = http_build_query($query);
		$this->var['setting']['mobile']['nomobileurl'] = ($this->var['setting']['domain']['app']['forum'] ? $this->var['scheme'].'://'.$this->var['setting']['domain']['app']['forum'].'/' : $this->var['siteurl']).$this->var['basefilename'].'?'.$query_sting_tmp;

		$this->var['setting']['lazyload'] = 0;

		if('utf-8' != CHARSET) {
			if(strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
				foreach($_POST AS $pk => $pv) {
					if(!is_numeric($pv)) {
						$_GET[$pk] = $_POST[$pk] = $this->mobile_iconv_recurrence($pv);
						if(!empty($this->var['config']['input']['compatible'])) {
							$this->var['gp_'.$pk] = daddslashes($_GET[$pk]);
						}
					}
				}
			}
		}


		if(!$this->var['setting']['mobile']['mobilesimpletype']) {
			$this->var['setting']['imagemaxwidth'] = 224;
		}

		$this->var['setting']['regstatus'] = $this->var['setting']['mobile']['mobileregister'] ? $this->var['setting']['regstatus'] : 0 ;
		$this->var['setting']['avatarmethod'] = 0;
		ob_start();
	}

	public function timezone_set($timeoffset = 0) {
		if(function_exists('date_default_timezone_set')) {
			@date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
		}
	}

       public function mobile_iconv_recurrence($value) {
		if(is_array($value)) {
			foreach($value AS $key => $val) {
				$value[$key] = $this->mobile_iconv_recurrence($val);
			}
		} else {
			$value = diconv($value, 'utf-8', CHARSET);
		}
		return $value;
	}
}

?>
