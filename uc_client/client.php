<?php

/*
	[UCenter] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: client.php 1179 2014-11-03 07:11:25Z hypowang $
*/

if(!defined('UC_API')) {
	exit('Access denied');
}

error_reporting(0);

define('IN_UC', TRUE);
define('UC_ROOT', substr(__FILE__, 0, -10));
require UC_ROOT.'./release/release.php';
define('UC_DATADIR', UC_ROOT.'./data/');
define('UC_DATAURL', UC_API.'/data');
define('UC_API_FUNC', ((defined('UC_CONNECT') && UC_CONNECT == 'mysql') || UC_STANDALONE) ? 'uc_api_mysql' : 'uc_api_post');
$uc_controls = array();

function uc_addslashes($string, $force = 0, $strip = FALSE) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = uc_addslashes($val, $force, $strip);
		}
	} else {
		$string = addslashes($strip ? stripslashes($string) : $string);
	}
	return $string;
}

if(!function_exists('daddslashes')) {
	function daddslashes($string, $force = 0) {
		return uc_addslashes($string, $force);
	}
}


if(!function_exists('dhtmlspecialchars')) {
	function dhtmlspecialchars($string, $flags = null) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = dhtmlspecialchars($val, $flags);
			}
		} else {
			if($flags === null) {
				$string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
				if(strpos($string, '&amp;#') !== false) {
					$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
				}
			} else {
				if(PHP_VERSION < '5.4.0') {
					$string = htmlspecialchars($string, $flags);
				} else {
					if(strtolower(CHARSET) == 'utf-8') {
						$charset = 'UTF-8';
					} else {
						$charset = 'ISO-8859-1';
					}
					$string = htmlspecialchars($string, $flags, $charset);
				}
			}
		}
		return $string;
	}
}
if(!function_exists('fsocketopen')) {
	function fsocketopen($hostname, $port = 80, &$errno = null, &$errstr = null, $timeout = 15) {
		$fp = '';
		if(function_exists('fsockopen')) {
			$fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
		} elseif(function_exists('pfsockopen')) {
			$fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
		} elseif(function_exists('stream_socket_client')) {
			$fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
		}
		return $fp;
	}
}

function uc_api_post($module, $action, $arg = array()) {
	$s = $sep = '';
	foreach($arg as $k => $v) {
		$k = urlencode($k);
		if(is_array($v)) {
			$s2 = $sep2 = '';
			foreach($v as $k2 => $v2) {
				$k2 = urlencode($k2);
				$s2 .= "$sep2{$k}[$k2]=".urlencode($v2);
				$sep2 = '&';
			}
			$s .= $sep.$s2;
		} else {
			$s .= "$sep$k=".urlencode($v);
		}
		$sep = '&';
	}
	$postdata = uc_api_requestdata($module, $action, $s);
	return uc_fopen2(UC_API.'/index.php', 500000, $postdata, '', TRUE, UC_IP, 20);
}

function uc_api_requestdata($module, $action, $arg='', $extra='') {
	$input = uc_api_input($arg, $module, $action);
	$post = "m=$module&a=$action&inajax=2&release=".UC_CLIENT_RELEASE."&input=$input&appid=".UC_APPID.$extra;
	return $post;
}

function uc_api_url($module, $action, $arg='', $extra='') {
	$url = UC_API.'/index.php?'.uc_api_requestdata($module, $action, $arg, $extra);
	return $url;
}

function uc_api_input($data, $module, $action) {
	$data = $data."&m=$module&a=$action&appid=".UC_APPID;
	$s = urlencode(uc_authcode($data.'&agent='.md5($_SERVER['HTTP_USER_AGENT'])."&time=".time(), 'ENCODE', UC_KEY));
	return $s;
}

function uc_api_mysql($model, $action, $args=array()) {
	global $uc_controls;
	if(empty($uc_controls[$model])) {
		include_once UC_ROOT.'./lib/dbi.class.php';
		include_once UC_ROOT.'./model/base.php';
		include_once UC_ROOT."./control/$model.php";
		$modelname = $model.'control';
		$uc_controls[$model] = new $modelname();
	}
	if($action[0] != '_') {
		$args = uc_addslashes($args, 1, TRUE);
		$action = 'on'.$action;
		$uc_controls[$model]->input = $args;
		return $uc_controls[$model]->$action($args);
	} else {
		return '';
	}
}

function uc_serialize($arr, $htmlon = 0) {
	include_once UC_ROOT.'./lib/xml.class.php';
	return xml_serialize($arr, $htmlon);
}

function uc_unserialize($s) {
	include_once UC_ROOT.'./lib/xml.class.php';
	return xml_unserialize($s);
}

function uc_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	// 动态密钥长度, 通过动态密钥可以让相同的 string 和 key 生成不同的密文, 提高安全性
	$ckey_length = 4;

	$key = md5($key ? $key : UC_KEY);
	// a参与加解密, b参与数据验证, c进行密文随机变换
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	// 参与运算的密钥组
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	// 前 10 位用于保存时间戳验证数据有效性, 10 - 26位保存 $keyb , 解密时通过其验证数据完整性
	// 如果是解码的话会从第 $ckey_length 位开始, 因为密文前 $ckey_length 位保存动态密匙以保证解密正确
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	// 产生密钥簿
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	// 打乱密钥簿, 增加随机性
	// 类似 AES 算法中的 SubBytes 步骤
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	// 从密钥簿得出密钥进行异或，再转成字符 
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		// 这里按照算法对数据进行验证, 保证数据有效性和完整性
		// $result 01 - 10 位是时间, 如果小于当前时间或为 0 则通过
		// $result 10 - 26 位是加密时的 $keyb , 需要和入参的 $keyb 做比对
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) === substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		// 把动态密钥保存在密文里, 并用 base64 编码保证传输时不被破坏
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

function uc_fopen2($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype  = 'URLENCODE', $allowcurl = TRUE) {
	$__times__ = isset($_GET['__times__']) ? intval($_GET['__times__']) + 1 : 1;
	if($__times__ > 2) {
		return '';
	}
	$url .= (strpos($url, '?') === FALSE ? '?' : '&')."__times__=$__times__";
	return uc_fopen($url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block, $encodetype, $allowcurl);
}

function uc_fopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype  = 'URLENCODE', $allowcurl = TRUE) {
	$return = '';
	$matches = parse_url($url);
	$scheme = strtolower($matches['scheme']);
	$host = $matches['host'];
	$path = !empty($matches['path']) ? $matches['path'].(!empty($matches['query']) ? '?'.$matches['query'] : '') : '/';
	$port = !empty($matches['port']) ? $matches['port'] : ($scheme == 'https' ? 443 : 80);

	if(function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
		$ch = curl_init();
		$ip && curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: ".$host));
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		// 在提供 IP 地址的同时, 当请求主机名并非一个合法 IP 地址, 且 PHP 版本 >= 5.5.0 时, 使用 CURLOPT_RESOLVE 设置固定的 IP 地址与域名关系
		// 在不支持的 PHP 版本下, 继续采用原有不支持 SNI 的流程
		if(!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP) && version_compare(PHP_VERSION, '5.5.0', 'ge')) {
			curl_setopt($ch, CURLOPT_RESOLVE, array("$host:$port:$ip"));
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.$host.':'.$port.$path);
		} else {
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.($ip ? $ip : $host).':'.$port.$path);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if($encodetype == 'URLENCODE') {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			} else {
				parse_str($post, $postarray);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postarray);
			}
		}
		if($cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		$status = curl_getinfo($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if($errno || $status['http_code'] != 200) {
			return;
		} else {
			return !$limit ? $data : substr($data, 0, $limit);
		}
	}

	if($post) {
		$out = "POST $path HTTP/1.0\r\n";
		$header = "Accept: */*\r\n";
		$header .= "Accept-Language: zh-cn\r\n";
		if($allowcurl) {
			$encodetype = 'URLENCODE';
		}
		$boundary = $encodetype == 'URLENCODE' ? '' : '; boundary='.trim(substr(trim($post), 2, strpos(trim($post), "\n") - 2));
		$header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data$boundary\r\n";
		$header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
		$header .= "Host: $host:$port\r\n";
		$header .= 'Content-Length: '.strlen($post)."\r\n";
		$header .= "Connection: Close\r\n";
		$header .= "Cache-Control: no-cache\r\n";
		$header .= "Cookie: $cookie\r\n\r\n";
		$out .= $header.$post;
	} else {
		$out = "GET $path HTTP/1.0\r\n";
		$header = "Accept: */*\r\n";
		$header .= "Accept-Language: zh-cn\r\n";
		$header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
		$header .= "Host: $host:$port\r\n";
		$header .= "Connection: Close\r\n";
		$header .= "Cookie: $cookie\r\n\r\n";
		$out .= $header;
	}

	$fpflag = 0;
	$context = array();
	if($scheme == 'https') {
		$context['ssl'] = array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'peer_name' => $host
		);
		if(version_compare(PHP_VERSION, '5.6.0', '<')) {
			$context['ssl']['SNI_enabled'] = true;
			$context['ssl']['SNI_server_name'] = $host;
		}
	}
	if(ini_get('allow_url_fopen')) {
		$context['http'] = array(
			'method' => $post ? 'POST' : 'GET',
			'header' => $header,
			'timeout' => $timeout
		);
		if($post) {
			$context['http']['content'] = $post;
		}
		$context = stream_context_create($context);
		$fp = @fopen($scheme.'://'.($ip ? $ip : $host).':'.$port.$path, 'b', false, $context);
		$fpflag = 1;
	} elseif(function_exists('stream_socket_client')) {
		$context = stream_context_create($context);
		$fp = @stream_socket_client(($scheme == 'https' ? 'ssl://' : '').($ip ? $ip : $host).':'.$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
	} else {
		$fp = @fsocketopen(($scheme == 'https' ? 'ssl://' : '').($scheme == 'https' ? $host : ($ip ? $ip : $host)), $port, $errno, $errstr, $timeout);
	}

	if(!$fp) {
		return '';
	} else {
		stream_set_blocking($fp, $block);
		stream_set_timeout($fp, $timeout);
		if(!$fpflag) {
			@fwrite($fp, $out);
		}
		$status = stream_get_meta_data($fp);
		if(!$status['timed_out']) {
			while (!feof($fp) && !$fpflag) {
				if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
					break;
				}
			}

			$stop = false;
			while(!feof($fp) && !$stop) {
				$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
				$return .= $data;
				if($limit) {
					$limit -= strlen($data);
					$stop = $limit <= 0;
				}
			}
		}
		@fclose($fp);
		return $return;
	}
}

function uc_app_ls() {
	$return = call_user_func(UC_API_FUNC, 'app', 'ls', array());
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_feed_add($icon, $uid, $username, $title_template='', $title_data='', $body_template='', $body_data='', $body_general='', $target_ids='', $images = array()) {
	return call_user_func(UC_API_FUNC, 'feed', 'add',
		array(  'icon'=>$icon,
			'appid'=>UC_APPID,
			'uid'=>$uid,
			'username'=>$username,
			'title_template'=>$title_template,
			'title_data'=>$title_data,
			'body_template'=>$body_template,
			'body_data'=>$body_data,
			'body_general'=>$body_general,
			'target_ids'=>$target_ids,
			'image_1'=>$images[0]['url'],
			'image_1_link'=>$images[0]['link'],
			'image_2'=>$images[1]['url'],
			'image_2_link'=>$images[1]['link'],
			'image_3'=>$images[2]['url'],
			'image_3_link'=>$images[2]['link'],
			'image_4'=>$images[3]['url'],
			'image_4_link'=>$images[3]['link']
		)
	);
}

function uc_feed_get($limit = 100, $delete = TRUE) {
	$return = call_user_func(UC_API_FUNC, 'feed', 'get', array('limit'=>$limit, 'delete'=>$delete));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_friend_add($uid, $friendid, $comment='') {
	return call_user_func(UC_API_FUNC, 'friend', 'add', array('uid'=>$uid, 'friendid'=>$friendid, 'comment'=>$comment));
}

function uc_friend_delete($uid, $friendids) {
	return call_user_func(UC_API_FUNC, 'friend', 'delete', array('uid'=>$uid, 'friendids'=>$friendids));
}

function uc_friend_totalnum($uid, $direction = 0) {
	return call_user_func(UC_API_FUNC, 'friend', 'totalnum', array('uid'=>$uid, 'direction'=>$direction));
}

function uc_friend_ls($uid, $page = 1, $pagesize = 10, $totalnum = 10, $direction = 0) {
	$return = call_user_func(UC_API_FUNC, 'friend', 'ls', array('uid'=>$uid, 'page'=>$page, 'pagesize'=>$pagesize, 'totalnum'=>$totalnum, 'direction'=>$direction));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_user_register($username, $password, $email, $questionid = '', $answer = '', $regip = '') {
	return call_user_func(UC_API_FUNC, 'user', 'register', array('username'=>$username, 'password'=>$password, 'email'=>$email, 'questionid'=>$questionid, 'answer'=>$answer, 'regip' => $regip));
}

function uc_user_login($username, $password, $isuid = 0, $checkques = 0, $questionid = '', $answer = '', $ip = '', $nolog = 0) {
	$isuid = intval($isuid);
	$return = call_user_func(UC_API_FUNC, 'user', 'login', array('username'=>$username, 'password'=>$password, 'isuid'=>$isuid, 'checkques'=>$checkques, 'questionid'=>$questionid, 'answer'=>$answer, 'ip' => $ip, 'nolog' => $nolog));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_user_synlogin($uid) {
	if(UC_STANDALONE) {
		return '';
	}
	$uid = intval($uid);
	if(@include UC_ROOT.'./data/cache/apps.php') {
		if(count($_CACHE['apps']) > 1) {
			$return = uc_api_post('user', 'synlogin', array('uid'=>$uid));
		} else {
			$return = '';
		}
	}
	return $return;
}

function uc_user_synlogout() {
	if(UC_STANDALONE) {
		return '';
	}
	if(@include UC_ROOT.'./data/cache/apps.php') {
		if(count($_CACHE['apps']) > 1) {
			$return = uc_api_post('user', 'synlogout', array());
		} else {
			$return = '';
		}
	}
	return $return;
}

function uc_user_edit($username, $oldpw, $newpw, $email, $ignoreoldpw = 0, $questionid = '', $answer = '', $secmobicc = '', $secmobile = '') {
	return call_user_func(UC_API_FUNC, 'user', 'edit', array('username'=>$username, 'oldpw'=>$oldpw, 'newpw'=>$newpw, 'email'=>$email, 'ignoreoldpw'=>$ignoreoldpw, 'questionid'=>$questionid, 'answer'=>$answer, 'secmobicc'=>$secmobicc, 'secmobile'=>$secmobile));
}

function uc_user_delete($uid) {
	return call_user_func(UC_API_FUNC, 'user', 'delete', array('uid'=>$uid, 'action'=>'delete'));
}

function uc_user_deleteavatar($uid) {
	if(UC_STANDALONE) {
		@include_once UC_ROOT.'./extend_client.php';
		uc_note_handler::loadavatarpath();
		uc_api_mysql('user', 'deleteavatar', array('uid'=>$uid));
	} else {
		uc_api_post('user', 'deleteavatar', array('uid'=>$uid));
	}
}

function uc_user_checkname($username) {
	return call_user_func(UC_API_FUNC, 'user', 'check_username', array('username'=>$username));
}

function uc_user_checkemail($email) {
	return call_user_func(UC_API_FUNC, 'user', 'check_email', array('email'=>$email));
}

function uc_user_checksecmobile($secmobicc, $secmobile) {
	return call_user_func(UC_API_FUNC, 'user', 'check_secmobile', array('secmobicc'=>$secmobicc, 'secmobile'=>$secmobile));
}

function uc_user_addprotected($username, $admin='') {
	return call_user_func(UC_API_FUNC, 'user', 'addprotected', array('username'=>$username, 'admin'=>$admin));
}

function uc_user_deleteprotected($username) {
	return call_user_func(UC_API_FUNC, 'user', 'deleteprotected', array('username'=>$username));
}

function uc_user_getprotected() {
	$return = call_user_func(UC_API_FUNC, 'user', 'getprotected', array('1'=>1));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_get_user($username, $isuid=0) {
	$return = call_user_func(UC_API_FUNC, 'user', 'get_user', array('username'=>$username, 'isuid'=>$isuid));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_user_chgusername($uid, $newusername) {
	return call_user_func(UC_API_FUNC, 'user', 'chgusername', array('uid'=>$uid, 'newusername'=>$newusername));
}

function uc_user_merge($oldusername, $newusername, $uid, $password, $email) {
	return call_user_func(UC_API_FUNC, 'user', 'merge', array('oldusername'=>$oldusername, 'newusername'=>$newusername, 'uid'=>$uid, 'password'=>$password, 'email'=>$email));
}

function uc_user_merge_remove($username) {
	return call_user_func(UC_API_FUNC, 'user', 'merge_remove', array('username'=>$username));
}

function uc_user_getcredit($appid, $uid, $credit) {
	return uc_api_post('user', 'getcredit', array('appid'=>$appid, 'uid'=>$uid, 'credit'=>$credit));
}


function uc_user_logincheck($username, $ip) {
	return call_user_func(UC_API_FUNC, 'user', 'logincheck', array('username' => $username, 'ip' => $ip));
}

function uc_pm_location($uid, $newpm = 0) {
	$apiurl = uc_api_url('pm_client', 'ls', "uid=$uid&frontend=1", ($newpm ? '&folder=newbox' : ''));
	@header("Expires: 0");
	@header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
	@header("Pragma: no-cache");
	@header("location: $apiurl");
}

function uc_pm_checknew($uid, $more = 0) {
	$return = call_user_func(UC_API_FUNC, 'pm', 'check_newpm', array('uid'=>$uid, 'more'=>$more));
	return (!$more || UC_CONNECT == 'mysql') ? $return : uc_unserialize($return);
}

function uc_pm_send($fromuid, $msgto, $subject, $message, $instantly = 1, $replypmid = 0, $isusername = 0, $type = 0) {
	if($instantly) {
		$replypmid = @is_numeric($replypmid) ? $replypmid : 0;
		return call_user_func(UC_API_FUNC, 'pm', 'sendpm', array('fromuid'=>$fromuid, 'msgto'=>$msgto, 'subject'=>$subject, 'message'=>$message, 'replypmid'=>$replypmid, 'isusername'=>$isusername, 'type' => $type));
	} else {
		$fromuid = intval($fromuid);
		$subject = rawurlencode($subject);
		$msgto = rawurlencode($msgto);
		$message = rawurlencode($message);
		$replypmid = @is_numeric($replypmid) ? $replypmid : 0;
		$replyadd = $replypmid ? "&pmid=$replypmid&do=reply" : '';
		$apiurl = uc_api_url('pm_client', 'send', "uid=$fromuid", "&msgto=$msgto&subject=$subject&message=$message$replyadd");
		@header("Expires: 0");
		@header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
		@header("Pragma: no-cache");
		@header("location: ".$apiurl);
	}
}

function uc_pm_delete($uid, $folder, $pmids) {
	return call_user_func(UC_API_FUNC, 'pm', 'delete', array('uid'=>$uid, 'pmids'=>$pmids));
}

function uc_pm_deleteuser($uid, $touids) {
	return call_user_func(UC_API_FUNC, 'pm', 'deleteuser', array('uid'=>$uid, 'touids'=>$touids));
}

function uc_pm_deletechat($uid, $plids, $type = 0) {
	return call_user_func(UC_API_FUNC, 'pm', 'deletechat', array('uid'=>$uid, 'plids'=>$plids, 'type'=>$type));
}

function uc_pm_readstatus($uid, $uids, $plids = array(), $status = 0) {
	return call_user_func(UC_API_FUNC, 'pm', 'readstatus', array('uid'=>$uid, 'uids'=>$uids, 'plids'=>$plids, 'status'=>$status));
}

function uc_pm_list($uid, $page = 1, $pagesize = 10, $folder = 'inbox', $filter = 'newpm', $msglen = 0) {
	$uid = intval($uid);
	$page = intval($page);
	$pagesize = intval($pagesize);
	$return = call_user_func(UC_API_FUNC, 'pm', 'ls', array('uid'=>$uid, 'page'=>$page, 'pagesize'=>$pagesize, 'filter'=>$filter, 'msglen'=>$msglen));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_pm_ignore($uid) {
	$uid = intval($uid);
	return call_user_func(UC_API_FUNC, 'pm', 'ignore', array('uid'=>$uid));
}

function uc_pm_view($uid, $pmid = 0, $touid = 0, $daterange = 1, $page = 0, $pagesize = 10, $type = 0, $isplid = 0) {
	$uid = intval($uid);
	$touid = intval($touid);
	$page = intval($page);
	$pagesize = intval($pagesize);
	$pmid = @is_numeric($pmid) ? $pmid : 0;
	$return = call_user_func(UC_API_FUNC, 'pm', 'view', array('uid'=>$uid, 'pmid'=>$pmid, 'touid'=>$touid, 'daterange'=>$daterange, 'page' => $page, 'pagesize' => $pagesize, 'type'=>$type, 'isplid'=>$isplid));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_pm_view_num($uid, $touid, $isplid) {
	$uid = intval($uid);
	$touid = intval($touid);
	$isplid = intval($isplid);
	return call_user_func(UC_API_FUNC, 'pm', 'viewnum', array('uid' => $uid, 'touid' => $touid, 'isplid' => $isplid));
}

function uc_pm_viewnode($uid, $type, $pmid) {
	$uid = intval($uid);
	$type = intval($type);
	$pmid = @is_numeric($pmid) ? $pmid : 0;
	$return = call_user_func(UC_API_FUNC, 'pm', 'viewnode', array('uid'=>$uid, 'type'=>$type, 'pmid'=>$pmid));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_pm_chatpmmemberlist($uid, $plid = 0) {
	$uid = intval($uid);
	$plid = intval($plid);
	$return = call_user_func(UC_API_FUNC, 'pm', 'chatpmmemberlist', array('uid'=>$uid, 'plid'=>$plid));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_pm_kickchatpm($plid, $uid, $touid) {
	$uid = intval($uid);
	$plid = intval($plid);
	$touid = intval($touid);
	return call_user_func(UC_API_FUNC, 'pm', 'kickchatpm', array('uid'=>$uid, 'plid'=>$plid, 'touid'=>$touid));
}

function uc_pm_appendchatpm($plid, $uid, $touid) {
	$uid = intval($uid);
	$plid = intval($plid);
	$touid = intval($touid);
	return call_user_func(UC_API_FUNC, 'pm', 'appendchatpm', array('uid'=>$uid, 'plid'=>$plid, 'touid'=>$touid));
}

function uc_pm_blackls_get($uid) {
	$uid = intval($uid);
	return call_user_func(UC_API_FUNC, 'pm', 'blackls_get', array('uid'=>$uid));
}

function uc_pm_blackls_set($uid, $blackls) {
	$uid = intval($uid);
	return call_user_func(UC_API_FUNC, 'pm', 'blackls_set', array('uid'=>$uid, 'blackls'=>$blackls));
}

function uc_pm_blackls_add($uid, $username) {
	$uid = intval($uid);
	return call_user_func(UC_API_FUNC, 'pm', 'blackls_add', array('uid'=>$uid, 'username'=>$username));
}

function uc_pm_blackls_delete($uid, $username) {
	$uid = intval($uid);
	return call_user_func(UC_API_FUNC, 'pm', 'blackls_delete', array('uid'=>$uid, 'username'=>$username));
}

function uc_domain_ls() {
	$return = call_user_func(UC_API_FUNC, 'domain', 'ls', array('1'=>1));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_credit_exchange_request($uid, $from, $to, $toappid, $amount) {
	$uid = intval($uid);
	$from = intval($from);
	$toappid = intval($toappid);
	$to = intval($to);
	$amount = intval($amount);
	return uc_api_post('credit', 'request', array('uid'=>$uid, 'from'=>$from, 'to'=>$to, 'toappid'=>$toappid, 'amount'=>$amount));
}

function uc_tag_get($tagname, $nums = 0) {
	$return = call_user_func(UC_API_FUNC, 'tag', 'gettag', array('tagname'=>$tagname, 'nums'=>$nums));
	return UC_CONNECT == 'mysql' ? $return : uc_unserialize($return);
}

function uc_avatar($uid, $type = 'virtual', $returnhtml = 1) {
	$uid = intval($uid);
	$uc_input = uc_api_input("uid=$uid&frontend=1", "user", "rectavatar");
	$avatarpath = UC_STANDALONE ? UC_AVTAPI : UC_API;
       $uc_avatarhtml5 = UC_API.'/index.php?m=user&a=camera&width=450&height=253&appid='.UC_APPID.'&input='.$uc_input.'&agent='.md5($_SERVER['HTTP_USER_AGENT']).'&ucapi='.urlencode(UC_API).'&avatartype='.$type.'&uploadSize=2048';
       $uc_avatarstl = $avatarpath.'/index.php?m=user&inajax=1&a=rectavatar&appid='.UC_APPID.'&input='.$uc_input.'&agent='.md5($_SERVER['HTTP_USER_AGENT']).'&avatartype='.$type.'&base64=yes';
       if($returnhtml) {
               return '<iframe src="' . $uc_avatarhtml5 . '" width="450" marginwidth="0" height="253" marginheight="0" scrolling="no" frameborder="0" id="mycamera" name="mycamera" align="middle"></iframe>';
       } else {
               return array(
                       'width', '450',
                       'height', '253',
                       'src', $uc_avatarhtml5,
                       'stl_src', $uc_avatarstl
                );
       }
}

function uc_rectavatar($uid) {
	return uc_api_mysql('user', 'rectavatar', array('uid' => $uid));
}

function uc_mail_queue($uids, $emails, $subject, $message, $frommail = '', $charset = 'gbk', $htmlon = FALSE, $level = 1) {
	return call_user_func(UC_API_FUNC, 'mail', 'add', array('uids' => $uids, 'emails' => $emails, 'subject' => $subject, 'message' => $message, 'frommail' => $frommail, 'charset' => $charset, 'htmlon' => $htmlon, 'level' => $level));
}

function uc_check_avatar($uid, $size = 'middle', $type = 'virtual') {
	if(UC_STANDALONE && @include UC_ROOT.'./extend_client.php') {
		$uc_chk = new uc_note_handler();
		$res = $uc_chk->checkavatar(array('uid' => $uid, 'size' => $size, 'type' => $type), array());
	} else {
		$url = UC_API."/avatar.php?uid=$uid&size=$size&type=$type&check_file_exists=1";
		$res = uc_fopen2($url, 500000, '', '', TRUE, UC_IP, 20);
	}
	if($res == 1) {
		return 1;
	} else {
		return 0;
	}
}

function uc_check_version() {
	$return = uc_api_post('version', 'check', array());
	$data = uc_unserialize($return);
	return is_array($data) ? $data : $return;
}

?>