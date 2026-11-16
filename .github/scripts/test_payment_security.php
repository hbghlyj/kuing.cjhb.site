<?php
// Exercise the real payment coordinator without a database or external gateway.
define('IN_DISCUZ', true);
define('TIMESTAMP', 1700000000);
define('ADMINSCRIPT', 'admin.php');
define('DISCUZ_DATA', 'data/');
chdir(__DIR__.'/../..');
set_error_handler(function($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});
function check_payment($condition, $message) {
	if(!$condition) throw new RuntimeException($message);
}
function lang($file, $key) { return $key; }
function libfile($file) { return __FILE__; }
function getuserbyuid($uid) { return ['uid' => $uid]; }
function logger($type, $member, $uid, $data) { $GLOBALS['payment_logs'][] = $data; }
function savecache($key, $value) { $GLOBALS['payment_cache'][$key] = $value; }
class table_common_payment_order {
	static $order = ['id' => 1, 'uid' => 2, 'out_biz_no' => 'order-1', 'channel' => 'wechat', 'status' => 0];
	static function t() { return new self; }
	function fetch_by_biz_no($number) { return self::$order; }
	function fetch($id) { return self::$order; }
}
class table_common_setting {
	static $on = false;
	static function t() { return new self; }
	function fetch_all_setting($keys, $decode) {
		return ['ec_wechat' => ['on' => self::$on], 'ec_alipay' => ['on' => 0], 'ec_qpay' => ['on' => 0], 'payment_channels' => []];
	}
}
class table_common_cron {
	static $row = [];
	static $inserts = 0;
	static function t() { return new self; }
	function get_cronid_by_filename($filename) { return self::$row ? 1 : 0; }
	function insert($row) { self::$row = $row; self::$inserts++; }
	function fetch($id) { return self::$row; }
	function update($id, $data) { self::$row = array_merge(self::$row, $data); }
}
$_G = ['setting' => ['log' => ['pmt' => 1]], 'timestamp' => TIMESTAMP, 'clientip' => '127.0.0.1', 'remoteport' => 0];
require 'source/class/class_payment.php';
$GLOBALS['payment_logs'] = [];
check_payment(payment::finish_order_notify('qpay', 'order-1', 'trade-1', TIMESTAMP), 'Mismatch callback was not acknowledged');
check_payment(table_common_payment_order::$order['status'] === 0, 'Mismatch completed an order');
check_payment($GLOBALS['payment_logs'][0]['error'] === 50011, 'Mismatch callback was not logged');
check_payment(payment::query_order('qpay', 1)['code'] === 500, 'Active lookup accepted another channel');
$filter = new ReflectionMethod(payment::class, 'notifylog_filter');
$encoded = $filter->invoke(null, ['html' => '<script>"&', 'long' => str_repeat('x', 900), 'invalid' => "\xff"]);
$decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
check_payment(!str_contains($encoded, '<script>') && $decoded['html'] === '<script>"&', 'Log HTML was not safely encoded');
check_payment(strlen($decoded['long']) === 503, 'Callback log field was not bounded');
check_payment(payment::cron_sync() === false && table_common_cron::$inserts === 0, 'Disabled channels created a cron');
table_common_setting::$on = true;
payment::cron_sync();
payment::cron_sync();
check_payment(table_common_cron::$inserts === 1 && table_common_cron::$row['available'] === 1, 'Reconciliation cron was duplicated');
check_payment($GLOBALS['payment_cache']['cronnextrun'] === TIMESTAMP, 'New cron did not invalidate schedule');
table_common_setting::$on = false;
payment::cron_sync();
check_payment(table_common_cron::$row['available'] === 0, 'Disabled channels left reconciliation enabled');
table_common_setting::$on = true;
payment::cron_sync();
check_payment(table_common_cron::$inserts === 1 && table_common_cron::$row['available'] === 1, 'Re-enabled channels duplicated cron');
require 'source/class/pay/pay_base.php';
require 'source/class/pay/pay_wechat.php';
require 'source/class/pay/pay_qpay.php';
foreach(['pay_wechat' => 'wechat_sign_verify', 'pay_qpay' => 'qpay_sign_verify'] as $class => $verify) {
	$gateway = (new ReflectionClass($class))->newInstanceWithoutConstructor();
	$gateway->settings = ['on' => 1, 'v1_key' => '', 'ec_wechat_version' => 0];
	check_payment($gateway->enabled(), $class.' enable accessor failed');
	check_payment($gateway->$verify()['code'] === 50009, $class.' accepted a missing signing key');
	$parse = new ReflectionMethod($class, $class === 'pay_wechat' ? 'wechat_x2o' : 'qpay_x2o');
	check_payment($parse->invoke($gateway, '<xml><result_code>SUCCESS</result_code></xml>')['result_code'] === 'SUCCESS', $class.' rejected valid XML');
	check_payment($parse->invoke($gateway, '<xml>') === [], $class.' accepted malformed XML');
	check_payment($parse->invoke($gateway, '<!DOCTYPE xml [<!ENTITY x SYSTEM "file:///etc/passwd">]><xml>&x;</xml>') === [], $class.' accepted a document type');
	$gateway->settings['on'] = 0;
	check_payment(!$gateway->enabled(), $class.' ignored disabled setting');
}
foreach(['EN', 'SC', 'TC'] as $locale) {
	$_G['siteurl'] = 'https://example.test/';
	$_G['setting']['navs'] = [3 => ['navname' => 'Space'], 4 => ['navname' => 'Group']];
	$lang = [];
	require 'source/i18n/'.$locale.'/lang_admincp.php';
	foreach(['ec_payment_check_cron', 'ec_wechat_v1_key_required', 'ec_wechat_v3_public_key_id', 'ec_wechat_v3_public_key'] as $key) {
		check_payment(!empty($lang[$key]), $locale.' missing '.$key);
	}
	$lang = [];
	require 'source/i18n/'.$locale.'/lang_admincp_msg.php';
	foreach([0, 50000, 50005, 50006, 50007, 50008, 50009, 50010, 50011] as $code) {
		check_payment(!empty($lang['payment_error_'.$code]), $locale.' missing payment error '.$code);
	}
}
echo "Payment channel binding, callback keys, safe logs, cron lifecycle and translations passed\n";
