<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_mail.php 33961 2013-09-06 07:39:33Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

// set_time_limit(0);
function sendmail($toemail, $subject, $message = '', $from = '') {
	global $_G;
	if(preg_match("/@m\.invalid$/i", $toemail)){
		return false;
	}
	if(!is_array($_G['setting']['mail'])) {
		$_G['setting']['mail'] = dunserialize($_G['setting']['mail']);
	}
	if($_G['setting']['mail']['mailsend'] == 4) {
		$etype = explode(':', $_G['setting']['mail']['plugin']);
		$codefile = DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/mailsend/mailsend_'.$etype[1].'.php';
		include_once $codefile;
		$class = 'mailsend_'.$etype[1];
		$code = new $class();
		return $code->sendmail($toemail, $subject, $message, $from);
	}
	$_G['setting']['mail']['server'] = $_G['setting']['mail']['port'] = $_G['setting']['mail']['auth'] = $_G['setting']['mail']['from'] = $_G['setting']['mail']['auth_username'] = $_G['setting']['mail']['auth_password'] = '';
	if($_G['setting']['mail']['mailsend'] != 1) {
		$smtpnum = count($_G['setting']['mail']['smtp']);
		if($smtpnum) {
			$rid = rand(0, $smtpnum-1);
			$maildomain = explode('@', $toemail);
			foreach(array_column($_G['setting']['mail']['smtp'], 'precedence') as $smtpkey => $smtpval) {
				$ismail = in_array($maildomain[1], explode(',', $smtpval));
				if($ismail !== false) {
					$rid = $smtpkey;
					break;
				}
			}
			$smtp = $_G['setting']['mail']['smtp'][$rid];
			$_G['setting']['mail']['server'] = $smtp['server'];
			$_G['setting']['mail']['port'] = $smtp['port'];
			$_G['setting']['mail']['timeout'] = isset($smtp['timeout']) && strlen($smtp['timeout']) ? intval($smtp['timeout']) : 30;
			$_G['setting']['mail']['auth'] = $smtp['auth'] ? 1 : 0;
			$_G['setting']['mail']['from'] = $smtp['from'];
			$_G['setting']['mail']['auth_username'] = $smtp['auth_username'];
			$_G['setting']['mail']['auth_password'] = $smtp['auth_password'];
		}
	}

	ob_start();
	if(is_array($subject) && $subject['tpl']) {
		$tpl = $subject['tpl'];
		$var = $subject['var'];
		$subject = lang('email/template', $tpl.'_subject', (!empty($subject['svar']) ? $subject['svar'] : array()));
		include template('email/'.$tpl);
	} else {
		include template('email/default');
	}
	$message = ob_get_contents();
	ob_end_clean();

	$message = preg_replace("/href\=\"(?!(http|https)\:\/\/)(.+?)\"/i", 'href="'.$_G['setting']['securesiteurl'].'\\2"', $message);

	$mailusername = isset($_G['setting']['mail']['mailusername']) ? $_G['setting']['mail']['mailusername'] : 1;
	$_G['setting']['mail']['port'] = $_G['setting']['mail']['port'] ? $_G['setting']['mail']['port'] : 25;
	$_G['setting']['mail']['mailsend'] = $_G['setting']['mail']['mailsend'] ? $_G['setting']['mail']['mailsend'] : 1;

	if($_G['setting']['mail']['mailsend'] == 3) {
		$email_from = empty($from) ? $_G['setting']['adminemail'] : $from;
	} else {
		$email_from = $from == '' ? '=?'.CHARSET.'?B?'.base64_encode(DISCUZ_LANG == 'EN/' ? 'Leisure Math Forum' : $_G['setting']['sitename'])."?= <".$_G['setting']['adminemail'].">" : (preg_match('/^(.+?) \<(.+?)\>$/',$from, $mats) ? '=?'.CHARSET.'?B?'.base64_encode($mats[1])."?= <$mats[2]>" : $from);
	}

	$email_to = preg_match('/^(.+?) \<(.+?)\>$/',$toemail, $mats) ? ($mailusername ? '=?'.CHARSET.'?B?'.base64_encode($mats[1])."?= <$mats[2]>" : $mats[2]) : $toemail;

	$email_subject = '=?'.CHARSET.'?B?'.base64_encode(preg_replace("/[\r|\n]/", '', '['.(DISCUZ_LANG == 'EN/' ? 'Leisure Math Forum' : $_G['setting']['sitename']).'] '.$subject)).'?=';
	$email_message = chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message))))));
	$host = parse_url('http://'.$_SERVER['HTTP_HOST'], PHP_URL_HOST);
	$version = $_G['setting']['version'];
	if($_G['setting']['mail']['mailsend'] == 1) {
		$maildelimiter = $_G['setting']['mail']['maildelimiter'] == 1 ? "\r\n" : ($_G['setting']['mail']['maildelimiter'] == 2 ? "\r" : "\n");
		$headers = "From: $email_from{$maildelimiter}X-Mailer: Discuz! $version {$maildelimiter}MIME-Version: 1.0{$maildelimiter}Content-type: text/html; charset=".CHARSET."{$maildelimiter}Content-Transfer-Encoding: base64{$maildelimiter}";
		if(function_exists('mail') && @mail($email_to, $email_subject, $email_message, $headers)) {
			return true;
		}
		return false;

	} elseif($_G['setting']['mail']['mailsend'] == 2) {

		if(!$fp = fsocketopen($_G['setting']['mail']['server'], $_G['setting']['mail']['port'], $errno, $errstr, $_G['setting']['mail']['timeout'])) {
			runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) CONNECT - Unable to connect to the SMTP server", 0);
			return false;
		}
		stream_set_blocking($fp, true);
		stream_set_timeout($fp, $_G['setting']['mail']['timeout']);

		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != '220') {
			fputs($fp, "QUIT\r\n");
			runlog('SMTP', "{$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']} CONNECT - $lastmessage", 0);
			return false;
		}

		while(1) {
			if(substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
				break;
			}
			$lastmessage = fgets($fp, 512);
		}

		fputs($fp, ($_G['setting']['mail']['auth'] ? 'EHLO' : 'HELO')." $host\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
			fputs($fp, "QUIT\r\n");
			runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) HELO/EHLO - $lastmessage", 0);
			return false;
		}

		while(1) {
			if(substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
				break;
			}
			$lastmessage = fgets($fp, 512);
		}

		if($_G['setting']['mail']['auth']) {
			fputs($fp, "AUTH LOGIN\r\n");
			$lastmessage = fgets($fp, 512);
			if(substr($lastmessage, 0, 3) != 334) {
				fputs($fp, "QUIT\r\n");
				runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) AUTH LOGIN - $lastmessage", 0);
				return false;
			}

			fputs($fp, base64_encode($_G['setting']['mail']['auth_username'])."\r\n");
			$lastmessage = fgets($fp, 512);
			if(substr($lastmessage, 0, 3) != 334) {
				fputs($fp, "QUIT\r\n");
				runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) USERNAME - $lastmessage", 0);
				return false;
			}

			fputs($fp, base64_encode($_G['setting']['mail']['auth_password'])."\r\n");
			$lastmessage = fgets($fp, 512);
			if(substr($lastmessage, 0, 3) != 235) {
				fputs($fp, "QUIT\r\n");
				runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) PASSWORD - $lastmessage", 0);
				return false;
			}

			$email_from = $_G['setting']['mail']['from'];
		}

		fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 250) {
			fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
			$lastmessage = fgets($fp, 512);
			if(substr($lastmessage, 0, 3) != 250) {
				fputs($fp, "QUIT\r\n");
				runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) MAIL FROM - $lastmessage", 0);
				return false;
			}
		}

		fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail).">\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 250) {
			fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail).">\r\n");
			$lastmessage = fgets($fp, 512);
			fputs($fp, "QUIT\r\n");
			runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) RCPT TO - $lastmessage", 0);
			return false;
		}

		fputs($fp, "DATA\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 354) {
			fputs($fp, "QUIT\r\n");
			runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) DATA - $lastmessage", 0);
			return false;
		}

		$timeoffset = $_G['setting']['timeoffset'];
		if(function_exists('date_default_timezone_set')) {
			@date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
		}

		$maildomain = substr(strrchr($email_from, "@"), 1);
		$maildelimiter = "\r\n";
		$headers = "From: $email_from{$maildelimiter}X-Mailer: Discuz! $version {$maildelimiter}MIME-Version: 1.0{$maildelimiter}Content-type: text/html; charset=".CHARSET."{$maildelimiter}Content-Transfer-Encoding: base64{$maildelimiter}";
		$headers .= 'Message-ID: <'.date('YmdHs').'.'.substr(md5($email_message.microtime()), 0, 6).rand(100000, 999999).'@'.$maildomain.">{$maildelimiter}";

		fputs($fp, "Date: ".date('r')."\r\n");
		fputs($fp, "To: ".$email_to."\r\n");
		fputs($fp, "Subject: ".$email_subject."\r\n");
		fputs($fp, $headers."\r\n");
		fputs($fp, "\r\n\r\n");
		fputs($fp, "$email_message\r\n.\r\n");
		$lastmessage = fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 250) {
			fputs($fp, "QUIT\r\n");
			runlog('SMTP', "({$_G['setting']['mail']['server']}:{$_G['setting']['mail']['port']}) END - $lastmessage", 0);
			return false;
		}
		fputs($fp, "QUIT\r\n");

		return true;

	} elseif($_G['setting']['mail']['mailsend'] == 3) {

		ini_set('SMTP', $_G['setting']['mail']['server']);
		ini_set('smtp_port', $_G['setting']['mail']['port']);
		ini_set('sendmail_from', $email_from);

		$maildelimiter = "\r\n";
		$headers = "From: $email_from{$maildelimiter}X-Mailer: Discuz! $version {$maildelimiter}MIME-Version: 1.0{$maildelimiter}Content-type: text/html; charset=".CHARSET."{$maildelimiter}Content-Transfer-Encoding: base64{$maildelimiter}";

		if(function_exists('mail') && @mail($email_to, $email_subject, $email_message, $headers)) {
			return true;
		}
		return false;
	}
}

function sendmail_cron($toemail, $subject, $message) {
	global $_G;
	if(preg_match("/@m\.invalid$/i", $toemail)){
		return false;
	}
	$toemail = addslashes($toemail);

	$value = C::t('common_mailcron')->fetch_all_by_email($toemail, 0, 1);
	$value = $value[0];
	if($value) {
		$cid = $value['cid'];
	} else {
		$cid = C::t('common_mailcron')->insert(array('email' => $toemail), true);
	}
	$message = preg_replace("/href\=\"(?!(http|https)\:\/\/)(.+?)\"/i", 'href="'.$_G['setting']['securesiteurl'].'\\1"', $message);
	$setarr = array(
		'cid' => $cid,
		'subject' => $subject,
		'message' => $message,
		'dateline' => $_G['timestamp']
	);
	C::t('common_mailqueue')->insert($setarr);

	return true;
}

function sendmail_touser($touid, $subject, $message, $mailtype='') {
	global $_G;

	if(empty($_G['setting']['sendmailday'])) return false;

	require_once libfile('function/home');
	$tospace = getuserbyuid($touid);
	if(empty($tospace['email'])) return false;

	space_merge($tospace, 'field_home');
	space_merge($tospace, 'status');

	$acceptemail = $tospace['acceptemail'];
	if(!empty($acceptemail[$mailtype]) && $_G['timestamp'] - $tospace['lastvisit'] > $_G['setting']['sendmailday']*86400) {
		if(empty($tospace['lastsendmail'])) {
			$tospace['lastsendmail'] = $_G['timestamp'];
		}
		$sendtime = $tospace['lastsendmail'] + $acceptemail['frequency'];

		$value = C::t('common_mailcron')->fetch_all_by_touid($touid, 0, 1);
		$value = $value[0];
		if($value) {
			$cid = $value['cid'];
			if($value['sendtime'] < $sendtime) $sendtime = $value['sendtime'];
			C::t('common_mailcron')->update($cid, array('email' => $tospace['email'], 'sendtime' => $sendtime));
		} else {
			$cid = C::t('common_mailcron')->insert(array(
				'touid' => $touid,
				'email' => $tospace['email'],
				'sendtime' => $sendtime,
			), true);
		}
		$message = preg_replace("/href\=\"(?!(http|https)\:\/\/)(.+?)\"/i", 'href="'.$_G['setting']['securesiteurl'].'\\1"', $message);
		$setarr = array(
			'cid' => $cid,
			'subject' => $subject,
			'message' => $message,
			'dateline' => $_G['timestamp']
		);
		C::t('common_mailqueue')->insert($setarr);
		return true;
	}
	return false;
}

?>