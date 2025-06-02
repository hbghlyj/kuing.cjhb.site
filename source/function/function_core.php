<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ') && !defined('IN_API')) {
	exit('Access Denied');
}

require_once 'function_path.php';

const DISCUZ_CORE_FUNCTION = true;

function durlencode($url) {
	static $fix = ['%21', '%2A', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D'];
	static $replacements = ['!', '*', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']'];
	return str_replace($fix, $replacements, urlencode($url));
}

function system_error($message, $show = false, $save = true, $halt = true) {
	discuz_error::system_error($message, $show, $save, $halt);
}

function updatesession() {
	return C::app()->session->updatesession();
}

function setglobal($key, $value, $group = null) {
	global $_G;
	$key = explode('/', $group === null ? $key : $group.'/'.$key);
	$p = &$_G;
	foreach($key as $k) {
		if(!isset($p[$k]) || !is_array($p[$k])) {
			$p[$k] = [];
		}
		$p = &$p[$k];
	}
	$p = $value;
	return true;
}

function getglobal($key, $group = null) {
	global $_G;
	$key = explode('/', $group === null ? $key : $group.'/'.$key);
	$v = &$_G;
	foreach($key as $k) {
		if(!isset($v[$k])) {
			return null;
		}
		$v = &$v[$k];
	}
	return $v;
}

function getgpc($k, $type = 'GP') {
	$type = strtoupper($type);
	switch($type) {
		case 'G':
			$var = &$_GET;
			break;
		case 'P':
			$var = &$_POST;
			break;
		case 'C':
			$var = &$_COOKIE;
			break;
		default:
			if(isset($_GET[$k])) {
				$var = &$_GET;
			} else {
				$var = &$_POST;
			}
			break;
	}

	return $var[$k] ?? NULL;

}

function dget($k) {
	return $_GET[$k] ?? null;
}

function dpost($k) {
	return $_POST[$k] ?? null;
}

function getuserbyuid($uid, $fetch_archive = 0) {
	static $users = [];
	if(empty($users[$uid])) {
		$users[$uid] = C::t('common_member'.($fetch_archive === 2 ? '_archive' : ''))->fetch($uid);
		if($fetch_archive === 1 && empty($users[$uid])) {
			$users[$uid] = table_common_member_archive::t()->fetch($uid);
		}
	}
	if(!isset($users[$uid]['self']) && $uid == getglobal('uid') && getglobal('uid')) {
		$users[$uid]['self'] = 1;
	}
	return $users[$uid];
}

function getuserprofile($field) {
	global $_G;
	if(isset($_G['member'][$field])) {
		return $_G['member'][$field];
	}
	static $tablefields = [
		'count' => ['extcredits1', 'extcredits2', 'extcredits3', 'extcredits4', 'extcredits5', 'extcredits6', 'extcredits7', 'extcredits8', 'friends', 'posts', 'threads', 'digestposts', 'doings', 'blogs', 'albums', 'sharings', 'attachsize', 'views', 'oltime', 'todayattachs', 'todayattachsize', 'follower', 'following', 'newfollower', 'blacklist'],
		'status' => ['regip', 'lastip', 'lastvisit', 'lastactivity', 'lastpost', 'lastsendmail', 'invisible', 'buyercredit', 'sellercredit', 'favtimes', 'sharetimes', 'profileprogress'],
		'field_forum' => ['publishfeed', 'customshow', 'customstatus', 'medals', 'sightml', 'groupterms', 'authstr', 'groups', 'attentiongroup'],
		'field_home' => ['spacename', 'spacedescription', 'domain', 'addsize', 'addfriend', 'menunum', 'theme', 'spacecss', 'blockposition', 'recentnote', 'spacenote', 'privacy', 'feedfriend', 'acceptemail', 'magicgift', 'stickblogs'],
		'profile' => ['realname', 'gender', 'birthyear', 'birthmonth', 'birthday', 'constellation', 'zodiac', 'telephone', 'mobile', 'idcardtype', 'idcard', 'address', 'zipcode', 'nationality', 'birthcountry', 'birthprovince', 'birthcity', 'residecountry', 'resideprovince', 'residecity', 'residedist', 'residecommunity', 'residesuite', 'graduateschool', 'company', 'education', 'occupation', 'position', 'revenue', 'affectivestatus', 'lookingfor', 'bloodtype', 'height', 'weight', 'alipay', 'icq', 'qq', 'yahoo', 'msn', 'taobao', 'site', 'bio', 'interest', 'field1', 'field2', 'field3', 'field4', 'field5', 'field6', 'field7', 'field8', 'fields'],
		'verify' => ['verify1', 'verify2', 'verify3', 'verify4', 'verify5', 'verify6'],
	];
	$profiletable = '';
	foreach($tablefields as $table => $fields) {
		if(in_array($field, $fields)) {
			$profiletable = $table;
			break;
		}
	}
	if($profiletable) {

		if(is_array($_G['member']) && $_G['member']['uid']) {
			space_merge($_G['member'], $profiletable);
		} else {
			foreach($tablefields[$profiletable] as $k) {
				$_G['member'][$k] = '';
			}
		}
		return $_G['member'][$field];
	}
	return null;
}

function daddslashes($string, $force = 1) {
	if(is_array($string)) {
		$keys = array_keys($string);
		foreach($keys as $key) {
			$val = $string[$key];
			unset($string[$key]);
			$string[addslashes($key)] = daddslashes($val, $force);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}

function authcode_numeric($string, $operation = 'DECODE', $key = '') {
	if(!is_numeric($string)) {
		return 0;
	}
	if($operation == 'DECODE') {
		$check_mask = substr($string, -1);
		$string = substr($string, 0, -1);
	}
	static $keymap = [
		'3456789012',
		'6347890125',
		'2890345671',
		'9012345678',
		'2905678134',
		'5623789014',
		'7893412560',
		'4789632501',
		'0123894567',
		'8790123456',
	];

	$string = (string)$string;
	$key = md5($key != '' ? $key : getglobal('authkey'));
	$string_length = strlen($string);
	$key_length = strlen($key);

	$result = '';
	$box = range(0, 9);

	// 产生密钥簿
	$rndkey = [];
	for($i = 0; $i < 10; $i++) {
		$rndkey[$i] = ord($key[$i % $key_length]);
	}

	// 打乱密钥簿, 增加随机性
	// 类似 AES 算法中的 SubBytes 步骤
	for($j = $i = 0; $i < 10; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 10;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	$mask = 0;
	// 从密钥簿得出密钥进行异或，再转成字符
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 10;
		$j = ($j + $box[$a]) % 10;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$k = ($box[($box[$a] + $box[$j]) % 10]);
		if($operation == 'ENCODE') {
			$v = $keymap[$k][$string[$i]];
			$mask += intval($string[$i]) * $i;
		} else {
			$v = array_search($string[$i], str_split($keymap[$k]));
			$mask += $v * $i;
		}
		$result .= $v;
	}
	// 最后一位为校验位
	if($operation == 'ENCODE') {
		$result .= $mask % 10;
	} elseif($mask % 10 != $check_mask) {
		$result = 0;
	}
	return $result;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0, $ckey_length = 4) {
	// 动态密钥长度, 通过动态密钥可以让相同的 string 和 key 生成不同的密文, 提高安全性
	$key = md5($key != '' ? $key : getglobal('authkey'));
	// a参与加解密, b参与数据验证, c进行密文随机变换
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

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
	$rndkey = [];
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

function authcode_field($type, $string, $operation, $key = '') {
	$key = $key ?: getglobal('config/security/authkey');
	$return = match (intval($type)) {
		1 => authcode_numeric($string, $operation, $key),
		2 => authcode($string, $operation, $key, 0, 0),
		default => $string,
	};
	if($operation == 'DECODE') {
		return !empty($return) ? $return : $string;
	} else {
		return $return;
	}
}

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

function dfsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype = 'URLENCODE', $allowcurl = TRUE, $position = 0, $files = []) {
	require_once libfile('function/filesock');
	return _dfsockopen($url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block, $encodetype, $allowcurl, $position, $files);
}

function dhtmlspecialchars($string, $flags = null) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val, $flags);
		}
	} else {
		if($flags === null) {
			$string = str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $string);
		} else {
			if(strtolower(CHARSET) == 'utf-8') {
				$charset = 'UTF-8';
			} else {
				$charset = 'ISO-8859-1';
			}
			$string = htmlspecialchars($string, $flags, $charset);
		}
	}
	return $string;
}

function dexit($message = '') {
	echo $message;
	output();
	exit();
}

function dheader($string, $replace = true, $http_response_code = 0) {
	$islocation = str_starts_with(strtolower(trim($string)), 'location');
	if(defined('IN_RESTFUL') && $islocation) {
		$s = strpos(trim($string), ':');
		if($s === false) {
			exit;
		}
		$url = trim(substr($string, $s + 1));
		$v = parse_url($url);
		if($v['query']) {
			parse_str($v['query'], $v['query']);
		}
		$v['url'] = $url;
		$GLOBALS['locationUrl'] = $v;
		exit;
	}
	if(defined('IN_MOBILE') && !str_contains($string, 'mobile') && $islocation) {
		if(!str_contains($string, '?')) {
			$string = $string.'?mobile='.IN_MOBILE;
		} else {
			if(!str_contains($string, '#')) {
				$string = $string.'&mobile='.IN_MOBILE;
			} else {
				$str_arr = explode('#', $string);
				$str_arr[0] = $str_arr[0].'&mobile='.IN_MOBILE;
				$string = implode('#', $str_arr);
			}
		}
	}
	$string = str_replace(["\r", "\n"], ['', ''], $string);
	if(empty($http_response_code)) {
		@header($string, $replace);
	} else {
		@header($string, $replace, $http_response_code);
	}
	if($islocation) {
		exit();
	}
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {

	global $_G;

	$config = $_G['config']['cookie'];

	$_G['cookie'][$var] = $value;
	$var = ($prefix ? $config['cookiepre'] : '').$var;
	$_COOKIE[$var] = $value;

	if($value === '' || $life < 0) {
		$value = '';
		$life = -1;
	}

	if(defined('IN_MOBILE')) {
		$httponly = false;
	}

	$life = $life > 0 ? getglobal('timestamp') + $life : ($life < 0 ? getglobal('timestamp') - 31536000 : 0);
	$secure = $_G['isHTTPS'];
	setcookie($var, $value, $life, $config['cookiepath'], $config['cookiedomain'], $secure, $httponly);
}

function getcookie($key) {
	global $_G;
	return $_G['cookie'][$key] ?? '';
}

function fileext($filename) {
	return addslashes(strtolower(substr(strrchr($filename, '.'), 1, 10)));
}

function formhash($specialadd = '') {
	global $_G;
	$hashadd = defined('IN_ADMINCP') ? 'Only For Discuz! Admin Control Panel' : '';
	return substr(md5(substr($_G['timestamp'], 0, -7).$_G['username'].$_G['uid'].$_G['authkey'].$hashadd.$specialadd), 8, 8);
}

function checkrobot($client_ip) {
	static $kw_spiders = array(
        'Googlebot','Google-Read-Aloud','Storebot-Google','Google-InspectionTool','APIs-Google','AdsBot-Google','Google-Safety','Google-Extended','GoogleOther','Google-CloudVertexBot','Baiduspider','ia_archiver','R6_FeedFetcher','NetcraftSurveyAgent','Sogou web spider','bingbot','bingpreview','msnbot','Yahoo! Slurp','facebookexternalhit','PrintfulBot','msnbot','Twitterbot','UnwindFetchor','urlresolver','Butterfly','TweetmemeBot','PaperLiBot','MJ12bot','AhrefsBot','Exabot','Ezooms','Yandex','SearchmetricsBot','picsearch','TweetedTimes Bot','QuerySeekerSpider','ShowyouBot','woriobot','merlinkbot','BazQuxBot','Kraken','SISTRIX Crawler','R6_CommentReader','magpie-crawler','GrapeshotCrawler','PercolateCrawler','MaxPointCrawler','NetSeer crawler','grokkit-crawler','SMXCrawler','PulseCrawler','Y!J-BRW','80legs','Mediapartners-Google','InAGist','Python-urllib','NING','TencentTraveler','Feedfetcher-Google','mon.itor.us','spbot','Feedly','bitlybot','ADmantX','Niki-Bot','Pinterest','python-requests','DotBot','HTTP_Request2','linkdexbot','A6-Indexer','TwitterFeed','Microsoft Office','Pingdom','BTWebClient','KatBot','SiteCheck','proximic','Sleuth','Abonti','(BOT for JCE)','Tiny Tiny RSS','newsblur','updown_tester','linkdex','searchmetrics','genieo','majestic12','spinn3r','profound','domainappender','VegeBot','terrykyleseoagency.com','CommonCrawler Node','AdlesseBot','libwww-perl','rogerbot-crawler','ltx71','Qwantify','Traackr.com','Re-Animator Bot','Pcore-HTTP','BoardReader','omgili','okhttp','CCBot','Java/1.8','semrush.com','feedbot','CommonCrawler','ibwww-perl','rogerbot','MegaIndex','BLEXBot','FlipboardProxy','techinfo@ubermetrics-technologies.com','trendictionbot','Mediatoolkitbot','trendiction','ubermetrics','ScooperBot','TrendsmapResolver','Nuzzel','Go-http-client','Applebot','LivelapBot','GroupHigh','SemrushBot','commoncrawl','istellabot','DomainCrawler','cs.daum.net','StormCrawler','GarlikCrawler','The Knowledge AI','getstream.io/winds','YisouSpider','archive.org_bot','semantic-visions.com','SearchBot','360Spider','linkfluence.com','glutenfreepleasure.com','Gluten Free Crawler','YaK/1.0','Cliqzbot','app.hypefactors.com','axios','webdatastats.com','schmorp.de','SEOkicks','DuckDuckBot','Barkrowler','ZoominfoBot','Linguee Bot','Mail.RU_Bot','OnalyticaBot','admantx-adform','Zombiebot','Nutch','SemanticScholarBot','Jetslide','scalaj-http','XoviBot','sysomos.com','PocketParser','newspaper','serpstatbot','MetaJobBot','SeznamBot/3.2','VelenPublicWebCrawler/1.0','WordPress.com mShots','adscanner','BacklinkCrawler','netEstate NE Crawler','Astute SRM','GigablastOpenSource/1.0','DomainStatsBot','Winds: Open Source RSS & Podcast','dlvr.it','BehloolBot','7Siters','AwarioSmartBot','Apache-HttpClient/5','Seekport Crawler','AHC/2.1','eCairn-Grabber','mediawords bot','PHP-Curl-Class','Scrapy','curl/7','Blackboard','NetNewsWire','node-fetch','admantx','metadataparser','Domains Project','SerendeputyBot','Moreover','DuckDuckGo' ,'monitoring-plugins','Selfoss','Adsbot','acebookexternalhit','SpiderLing','Cocolyzebot','TTD-Content','superfeedr','Twingly','Google-Apps-Scrip','LinkpadBot','CensysInspect','Reeder','tweetedtimes','Amazonbot','MauiBot','Symfony BrowserKit','DataForSeoBot','GoogleProducer','TinEye-bot-live','sindresorhus/got','CriteoBot','Down/5','Yahoo Ad monitoring','MetaInspector','PetalBot','MetadataScraper','Cloudflare SpeedTest','aiohttp','AppEngine-Google','heritrix','sqlmap','Buck','wp_is_mobile','01h4x.com','404checker','404enemy','AIBOT','ALittle Client','ASPSeek','Aboundex','Acunetix','AfD-Verbotsverfahren','AiHitBot','Aipbot','Alexibot','AllSubmitter','Alligator','AlphaBot','Anarchie','Anarchy','Anarchy99','Ankit','Anthill','Apexoo','Aspiegel','Asterias','Atomseobot','Attach','AwarioRssBot','BBBike','BDCbot','BDFetch','BackDoorBot','BackStreet','BackWeb','Backlink-Ceck','Badass','Bandit','BatchFTP','Battleztar Bazinga','BetaBot','Bigfoot','Bitacle','BlackWidow','Black Hole','Blow','BlowFish','Boardreader','Bolt','BotALot','Brandprotect','Brandwatch','Buddy','BuiltBotTough','BuiltWith','Bullseye','BunnySlippers','BuzzSumo','CATExplorador','CODE87','CSHttp','Calculon','CazoodleBot','Cegbfeieh','CheTeam','CheeseBot','CherryPicker','ChinaClaw','Chlooe','Citoid','Claritybot','Cloud mapping','Cogentbot','Collector','Copier','CopyRightCheck','Copyscape','Cosmos','Craftbot','Crawling at Home Project','CrazyWebCrawler','Crescent','CrunchBot','Curious','Custo','CyotekWebCopy','DBLBot','DIIbot','DSearch','DTS Agent','DataCha0s','DatabaseDriverMysqli','Demon','Deusu','Devil','Digincore','DigitalPebble','Dirbuster','Disco','Discobot','Discoverybot','Dispatch','DittoSpyder','DnBCrawler-Analytics','DnyzBot','DomCopBot','DomainAppender','DomainSigmaCrawler','Dotbot','Download Wonder','Dragonfly','Drip','ECCP/1.0','EMail Siphon','EMail Wolf','EasyDL','Ebingbong','Ecxi','EirGrabber','EroCrawler','Evil','Express WebPictures','ExtLinksBot','Extractor','ExtractorPro','Extreme Picture Finder','EyeNetIE','FDM','FHscan','Fimap','FlashGet','Flunky','Foobot','Freeuploader','FrontPage','Fuzz','FyberSpider','Fyrebot','G-i-g-a-b-o-t','GT::WWW','GalaxyBot','Genieo','GermCrawler','GetRight','GetWeb','Getintent','Gigabot','Go!Zilla','Go-Ahead-Got-It','GoZilla','Gotit','GrabNet','Grabber','Grafula','GrapeFX','GridBot','HEADMasterSEO','HMView','HTMLparser','HTTP::Lite','HTTrack','Haansoft','HaosouSpider','Harvest','Havij','Hloader','HonoluluBot','Humanlinks','HybridBot','IDBTE4M','IDBot','IRLbot','Iblog','Id-search','IlseBot','Image Fetch','Image Sucker','IndeedBot','Indy Library','InfoNaviRobot','InfoTekies','Intelliseek','InterGET','InternetSeer','Internet Ninja','Iria','Iskanie','IstellaBot','JOC Web Spider','JamesBOT','Jbrofuzz','JennyBot','JetCar','Jetty','JikeSpider','Joomla','Jorgee','JustView','Jyxobot','Kenjin Spider','Keybot Translation-Search-Machine','Keyword Density','Kinza','Kozmosbot','LNSpiderguy','LWP::Simple','Lanshanbot','Larbin','Leap','LeechFTP','LeechGet','LexiBot','Lftp','LibWeb','Libwhisker','LieBaoFast','Lightspeedsystems','Likse','LinkScan','LinkWalker','Linkbot','LinkextractorPro','LinksManager','LinqiaMetadataDownloaderBot','LinqiaRSSBot','LinqiaScrapeBot','Lipperhey','Lipperhey Spider','Litemage_walker','Lmspider','MFC_Tear_Sample','MIDown tool','MIIxpc','MQQBrowser','MSFrontPage','MSIECrawler','MTRobot','Mag-Net','Magnet','Majestic-SEO','Majestic12','Majestic SEO','MarkMonitor','MarkWatch','Mass Downloader','Masscan','Mata Hari','Mb2345Browser','MeanPath Bot','Meanpathbot','metauri','Microsoft Data Access','Microsoft URL Control','Minefield','Mister PiX','Moblie Safari','Mojeek','Mojolicious','MolokaiBot','Morfeus Fucking Scanner','Mozlila','Mr.4x3','Msrabot','Musobot','NICErsPRO','NPbot','Name Intelligence','Nameprotect','Navroad','NearSite','Needle','Nessus','NetAnts','NetLyzer','NetMechanic','NetSpider','NetZIP','Net Vampire','Netcraft','Nettrack','Netvibes','Nibbler','Niki-bot','Nikto','NimbleCrawler','Nimbostratus','Ninja','Nmap','Nuclei','Octopus','Offline Explorer','Offline Navigator','OnCrawl','OpenLinkProfiler','OpenVAS','Openfind','Openvas','OrangeBot','OrangeSpider','OutclicksBot','OutfoxBot','PECL::HTTP','PHPCrawl','POE-Component-Client-HTTP','PageAnalyzer','PageGrabber','PageScorer','PageThing.com','Page Analyzer','Pandalytics','Panscient','Papa Foto','Pavuk','PeoplePal','Petalbot','Pi-Monster','Picscout','Picsearch','PictureFinder','Piepmatz','Pimonster','Pixray','PleaseCrawl','Pockey','ProPowerBot','ProWebWalker','Probethenet','Psbot','Pu_iN','Pump','PxBroker','PyCurl','QueryN Metasearch','Quick-Crawler','RSSingBot','RankActive','RankActiveLinkBot','RankFlex','RankingBot','RankingBot2','Rankivabot','RankurBot','Re-re','ReGet','RealDownload','Reaper','RebelMouse','Recorder','RedesScrapy','RepoMonkey','Ripper','RocketCrawler','Rogerbot','SBIder','SEOlyticsCrawler','SEOprofiler','SEOstats','SISTRIX','SMTBot','SalesIntelligent','ScanAlert','Scanbot','ScoutJet','Screaming','ScreenerBot','ScrepyBot','Searchestate','Seekport','SemanticJuice','Semrush','SentiBot','SeoSiteCheckup','SeobilityBot','Seomoz','Shodan','Siphon','SiteCheckerBotCrawler','SiteExplorer','SiteLockSpider','SiteSnagger','SiteSucker','Site Sucker','Sitebeam','Siteimprove','Sitevigil','SlySearch','SmartDownload','Snake','Snapbot','Snoopy','SocialRankIOBot','Sociscraper','Sosospider','Sottopop','SpaceBison','Spammen','SpankBot','Spanner','Spbot','SputnikBot','Sqlmap','Sqlworm','Sqworm','Steeler','Stripper','Sucker','Sucuri','SuperBot','SuperHTTP','Surfbot','SurveyBot','Suzuran','Swiftbot','Szukacz','T0PHackTeam','T8Abot','Teleport','TeleportPro','Telesoft','Telesphoreo','Telesphorep','TheNomad','The Intraformant','Thumbor','TightTwatBot','Titan','Toata','Toweyabot','Tracemyfile','Trendiction','Trendictionbot','True_Robot','Turingos','Turnitin','TurnitinBot','TwengaBot','Twice','Typhoeus','URLy.Warning','URLy Warning','UnisterBot','Upflow','V-BOT','VB Project','VCI','Vacuum','Vagabondo','VelenPublicWebCrawler','VeriCiteCrawler','VidibleScraper','Virusdie','VoidEYE','Voil','Voltron','WASALive-Bot','WEBDAV','WISENutbot','WPScan','WWW-Collector-E','WWW-Mechanize','WWW::Mechanize','WWWOFFLE','Wallpapers','Wallpapers/3.0','WallpapersHD','WeSEE','WebAuto','WebBandit','WebCollage','WebCopier','WebEnhancer','WebFetch','WebFuck','WebGo IS','WebImageCollector','WebLeacher','WebPix','WebReaper','WebSauger','WebStripper','WebSucker','WebWhacker','WebZIP','Web Auto','Web Collage','Web Enhancer','Web Fetch','Web Fuck','Web Pix','Web Sauger','Web Sucker','Webalta','WebmasterWorldForumBot','Webshag','WebsiteExtractor','WebsiteQuester','Website Quester','Webster','Whack','Whacker','Whatweb','Who.is Bot','Widow','WinHTTrack','WiseGuys Robot','Wonderbot','Woobot','Wotbox','Wprecon','Xaldon WebSpider','Xaldon_WebSpider','Xenu','YoudaoBot','Zade','Zauba','Zermelo','Zeus','Zitebot','ZmEu','ZoomBot','ZumBot','ZyBorg','arquivo-web-crawler','arquivo.pt','autoemailspider','backlink-check','cah.io.community','check1.exe','clark-crawler','coccocbot','cognitiveseo','com.plumanalytics','crawl.sogou.com','crawler.feedback','crawler4j','dataforseo.com','demandbase-bot','domainsproject.org','eCatch','evc-batch','facebookscraper','gopher','instabid','internetVista monitor','ips-agent','isitwp.com','iubenda-radar','lwp-request','lwp-trivial','meanpathbot','mediawords','muhstik-scan','oBot','page scorer','pcBrowser','plumanalytics','polaris version','probe-image-size','ripz','s1z.ru','satoristudio.net','scan.lol','seobility','seocompany.store','seoscanners','seostar','sexsearcher','sitechecker.pro','siteripz','sogouspider','sp_auditbot','spyfu','sysscan','tAkeOut','trendiction.com','trendiction.de','ubermetrics-technologies.com','voyagerx.com','webgains-bot','webmeup-crawler','webpros.com','webprosbot','x09Mozilla','x22Mozilla','xpymep1.exe','zauba.io','zgrab','petalsearch','protopage','Miniflux','Feeder','Semanticbot' ,'ImageFetcher','Mastodon' ,'Neevabot','Pleroma','Akkoma','koyu.space','Embedly','Mjukisbyxor','Giant Rhubarb','GozleBot','Friendica','WhatsApp','XenForo','Yeti','MuckRack','PhxBot','Bytespider','GPTBot','SummalyBot','LinkedInBot','SpiderWeb','SpaceCowboys','LCC','Paqlebot','imagesift','ows.eu','SeznamBot','SeznamHomepage','ChatGPT','anthropic','Claude-Web','cohere-ai','Diffbot','FacebookBot','ImagesiftBot','PerplexityBot','Omigili','yacybot','RepoLookoutBot','StractBot','IABot','rss-is-dead','Slackbot','WellKnownBot','ArchiveBot','Sogou','iaskspider','Qwantbot','keys-so-bot','ev-crawler','InternetMeasurement','meta-externalagent','AOLBuild','PetalBot','Teoma','Lycos','ZumBot','wpbot','Algolia Crawler');
	if(isset($_SERVER['HTTP_VIA'])){
		if($kw = dstrpos($_SERVER['HTTP_VIA'], $kw_spiders, 1)) {
			return $kw;
		}
		if(str_contains($_SERVER['HTTP_VIA'], 'https://')) {
			return $_SERVER['HTTP_VIA'];
		}
	}
	if($kw = dstrpos($_SERVER['HTTP_USER_AGENT'], $kw_spiders, 1)) {
		return $kw;
	}
	if(str_contains($_SERVER['HTTP_USER_AGENT'], 'https://')) {
		return $_SERVER['HTTP_USER_AGENT'];
	}
	// Check if client IP is in Alibaba Cloud Singapore/US CIDR ranges
	function _check_ip_in_cidrs($client_ip_long, $cidrs, $region_name) {
		if ($client_ip_long === false) {
			return null;
		}
		foreach ($cidrs as $cidr) {
			list($subnet, $mask) = explode('/', $cidr);
			$subnet_long = ip2long($subnet);
			if ($subnet_long !== false) {
				$mask_long = ~((1 << (32 - (int)$mask)) - 1);
				if (($client_ip_long & $mask_long) == ($subnet_long & $mask_long)) {
					return $region_name;
				}
			}
		}
		return null;
	}
	// https://networksdb.io/ip-addresses-of/alibaba-cloud-singapore-private-ltd
	static $alibaba_cloud_singapore_cidrs = ['43.0.0.0/9','8.128.0.0/10','8.208.0.0/12','161.117.0.0/16','170.33.0.0/16','149.129.0.0/16','103.206.40.0/22','14.1.112.0/22','185.218.176.0/22'];
	$client_ip_long = ip2long($client_ip);
    $region_check = _check_ip_in_cidrs($client_ip_long, $alibaba_cloud_singapore_cidrs, 'Alibaba Cloud Singapore');
    if ($region_check) {
        return $region_check;
    }
	// https://www.ip2location.com/as45102
	static $alibaba_cn_net_cidrs = ['5.181.224.0/23','8.208.0.0/17','8.208.128.0/21','8.208.140.0/22','8.208.144.0/20','8.208.160.0/19','8.208.192.0/18','8.209.0.0/19','8.209.36.0/22','8.209.40.0/21','8.209.48.0/20','8.209.64.0/19','8.209.96.0/20','8.209.112.0/21','8.209.120.0/23','8.209.123.0/24','8.209.124.0/22','8.209.128.0/17','8.210.0.0/16','8.211.0.0/17','8.211.128.0/18','8.211.192.0/19','8.211.224.0/22','8.211.232.0/21','8.211.240.0/20','8.212.0.0/18','8.212.64.0/20','8.212.80.0/21','8.212.88.0/22','8.212.92.0/24','8.212.94.0/23','8.212.96.0/22','8.212.104.0/21','8.212.112.0/20','8.212.128.0/19','8.212.160.0/20','8.212.176.0/21','8.212.188.0/22','8.212.192.0/18','8.213.0.0/17','8.213.128.0/19','8.213.160.0/21','8.213.176.0/20','8.213.192.0/18','8.214.0.0/17','8.214.128.0/19','8.214.164.0/22','8.214.168.0/21','8.214.176.0/20','8.214.192.0/18','8.215.0.0/17','8.215.128.0/19','8.215.160.0/22','8.215.168.0/21','8.215.176.0/20','8.215.192.0/18','8.216.0.0/18','8.216.64.0/21','8.216.72.0/22','8.216.80.0/20','8.216.96.0/19','8.216.128.0/17','8.217.0.0/16','8.218.0.0/15','8.220.64.0/19','8.220.96.0/20','8.220.112.0/21','8.220.120.0/22','8.220.128.0/19','8.220.160.0/21','8.220.171.0/24','8.220.172.0/22','8.220.176.0/20','8.220.192.0/19','8.220.224.0/21','8.220.232.0/22','8.220.240.0/21','8.221.0.0/17','8.221.128.0/20','8.221.144.0/21','8.221.156.0/22','8.221.160.0/19','8.221.192.0/18','8.222.0.0/15','14.1.112.0/22','43.91.0.0/16','43.96.3.0/24','43.96.4.0/23','43.96.7.0/24','43.96.8.0/24','43.96.10.0/23','43.96.20.0/22','43.96.24.0/23','43.96.32.0/22','43.96.40.0/24','43.96.66.0/23','43.96.68.0/22','43.96.72.0/22','43.96.80.0/23','43.96.85.0/24','43.96.88.0/24','43.96.96.0/24','43.96.100.0/23','43.96.102.0/24','43.98.0.0/15','43.100.0.0/15','43.104.0.0/15','43.108.0.0/17','45.196.28.0/24','45.199.179.0/24','47.52.0.0/16','47.56.0.0/16','47.57.0.0/17','47.57.128.0/18','47.57.192.0/22','47.57.196.0/24','47.57.198.0/23','47.57.200.0/22','47.57.204.0/23','47.57.206.0/24','47.57.208.0/20','47.57.224.0/19','47.74.0.0/15','47.76.0.0/16','47.77.0.0/20','47.77.16.0/21','47.77.24.0/22','47.77.32.0/19','47.77.64.0/19','47.77.96.0/20','47.77.128.0/17','47.78.0.0/16','47.79.0.0/17','47.79.128.0/19','47.79.192.0/18','47.80.0.0/15','47.82.0.0/17','47.83.0.0/16','47.84.0.0/15','47.86.0.0/16','47.87.0.0/20','47.87.16.0/21','47.87.25.0/24','47.87.26.0/23','47.87.29.0/24','47.87.30.0/23','47.87.32.0/19','47.87.64.0/21','47.87.80.0/20','47.87.96.0/19','47.87.128.0/20','47.87.144.0/21','47.87.156.0/22','47.87.160.0/19','47.87.192.0/19','47.87.224.0/21','47.87.232.0/22','47.88.0.0/16','47.89.0.0/18','47.89.72.0/21','47.89.80.0/22','47.89.84.0/24','47.89.88.0/23','47.89.90.0/24','47.89.92.0/22','47.89.96.0/20','47.89.122.0/23','47.89.124.0/23','47.89.128.0/17','47.90.0.0/17','47.90.128.0/19','47.90.160.0/21','47.90.168.0/22','47.90.176.0/20','47.90.192.0/18','47.91.0.0/16','47.235.0.0/21','47.235.8.0/22','47.235.12.0/23','47.235.16.0/24','47.235.18.0/23','47.235.20.0/22','47.235.24.0/23','47.235.27.0/24','47.235.28.0/22','47.236.0.0/17','47.236.128.0/18','47.236.192.0/20','47.236.208.0/21','47.236.220.0/22','47.236.224.0/19','47.237.0.0/17','47.237.128.0/18','47.237.192.0/19','47.237.224.0/20','47.237.240.0/21','47.237.249.0/24','47.237.250.0/23','47.237.252.0/22','47.238.0.0/20','47.238.16.0/21','47.238.28.0/22','47.238.32.0/19','47.238.64.0/18','47.238.128.0/17','47.239.0.0/16','47.240.0.0/14','47.244.0.0/16','47.245.0.0/17','47.245.128.0/20','47.245.144.0/21','47.245.152.0/22','47.245.160.0/19','47.245.192.0/20','47.245.208.0/21','47.245.216.0/22','47.245.224.0/19','47.246.32.0/22','47.246.66.0/23','47.246.68.0/23','47.246.72.0/21','47.246.80.0/24','47.246.82.0/23','47.246.84.0/22','47.246.88.0/23','47.246.91.0/24','47.246.92.0/23','47.246.96.0/22','47.246.101.0/24','47.246.102.0/23','47.246.104.0/21','47.246.120.0/24','47.246.122.0/23','47.246.124.0/23','47.246.128.0/23','47.246.131.0/24','47.246.132.0/23','47.246.135.0/24','47.246.136.0/21','47.246.144.0/22','47.246.150.0/23','47.246.152.0/22','47.246.157.0/24','47.246.158.0/23','47.246.160.0/23','47.246.162.0/24','47.246.164.0/22','47.246.168.0/21','47.246.176.0/20','47.246.192.0/21','47.250.0.0/18','47.250.68.0/22','47.250.72.0/21','47.250.80.0/20','47.250.96.0/21','47.250.108.0/22','47.250.112.0/20','47.250.128.0/17','47.251.0.0/17','47.251.132.0/22','47.251.136.0/21','47.251.144.0/20','47.251.160.0/19','47.251.196.0/22','47.251.200.0/21','47.251.208.0/20','47.251.224.0/19','47.252.0.0/16','47.253.0.0/18','47.253.64.0/19','47.253.96.0/20','47.253.112.0/21','47.253.120.0/22','47.253.128.0/17','47.254.0.0/16','59.82.136.0/23','103.81.187.0/24','110.76.21.0/24','110.76.23.0/24','116.251.64.0/18','139.95.0.0/20','139.95.16.0/22','139.95.64.0/24','140.205.1.0/24','140.205.122.0/24','147.139.0.0/17','147.139.128.0/18','147.139.192.0/19','147.139.224.0/20','147.139.240.0/21','147.139.248.0/22','149.129.0.0/20','149.129.16.0/23','149.129.32.0/19','149.129.64.0/18','149.129.192.0/18','156.227.20.0/24','156.236.12.0/24','156.236.17.0/24','156.240.77.0/24','156.245.1.0/24','161.117.0.0/16','170.33.32.0/22','170.33.64.0/23','170.33.66.0/24','170.33.68.0/23','170.33.80.0/22','170.33.84.0/23','170.33.86.0/24','170.33.104.0/22','170.33.136.0/23','170.33.138.0/24','198.11.128.0/20','198.11.145.0/24','198.11.146.0/23','198.11.148.0/22','198.11.152.0/21','198.11.160.0/19','202.144.199.0/24','203.107.64.0/22','203.107.68.0/24','205.204.96.0/19','223.5.5.0/24','223.6.6.0/24'];	
    $region_check = _check_ip_in_cidrs($client_ip_long, $alibaba_cn_net_cidrs, 'ALIBABA-CN-NET');
    if ($region_check) {
        return $region_check;
    }
	// https://serverfault.com/questions/1074478/what-does-this-webserver-log-entry-tell-me
	if(!empty($_SERVER['HTTP_HOST']) && !empty($_G['setting']['siteurl']) && $_SERVER['HTTP_HOST'] != $_G['setting']['siteurl']) {
		return 'ProxyAbuse '. $_SERVER['HTTP_HOST'];
	}
	// Check for missing or unusual headers
	foreach (array('ACCEPT_LANGUAGE', 'ACCEPT_ENCODING', 'USER_AGENT', 'ACCEPT') as $header) {
		if (strlen($_SERVER['HTTP_'.$header]) < 2) {
			return 'Missing ' . $header;
		}
	}
	// Check for unusual Accept header (e.g., very short or missing common types like text/html or */*)
	if(strlen($_SERVER['HTTP_ACCEPT']) < 10 || (stripos($_SERVER['HTTP_ACCEPT'], 'text/html') === false && stripos($_SERVER['HTTP_ACCEPT'], '*/*') === false)) {
		return 'UnusualAcceptHeader';
	}
}
function checkmobile() {
    global $_G;
	if(empty($_SERVER['HTTP_USER_AGENT'])) {
        return $_G['mobile'] = false;
    }
    return $_G['mobile'] = preg_match('/android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos/i', $_SERVER['HTTP_USER_AGENT']) ? true : false;
}

function dstrpos($string, $arr, $returnvalue = false) {
	if(empty($string)) return false;
	foreach((array)$arr as $v) {
		if(str_contains($string, $v)) {
			$return = $returnvalue ? $v : true;
			return $return;
		}
	}
	return false;
}

function isemail($email) {
	return strlen($email) > 6 && strlen($email) <= 255 && preg_match('/^([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-]+[.][A-Za-z0-9\-.]+)$/', $email);
}

function quescrypt($questionid, $answer) {
	return $questionid > 0 && $answer != '' ? substr(md5($answer.md5($questionid)), 16, 8) : '';
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	if($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function secrandom($length, $numeric = 0, $strong = false) {
	// Thank you @popcorner for your strong support for the enhanced security of the function.
	$chars = $numeric ? ['A', 'B', '+', '/', '='] : ['+', '/', '='];
	$num_find = str_split('CDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
	$num_repl = str_split('01234567890123456789012345678901234567890123456789');
	$isstrong = false;
	if(function_exists('random_bytes')) {
		$isstrong = true;
		$random_bytes = function($length) {
			return random_bytes($length);
		};
	} elseif(extension_loaded('openssl') && function_exists('openssl_random_pseudo_bytes')) {
		// for lower than PHP 7.0, Please Upgrade ASAP.
		// openssl_random_pseudo_bytes() does not appear to cryptographically secure
		// https://github.com/paragonie/random_compat/issues/5
		$isstrong = true;
		$random_bytes = function($length) {
			$rand = openssl_random_pseudo_bytes($length, $secure);
			if($secure === true) {
				return $rand;
			} else {
				return false;
			}
		};
	}
	if(!$isstrong) {
		return $strong ? false : random($length, $numeric);
	}
	$retry_times = 0;
	$return = '';
	while($retry_times < 128) {
		$getlen = $length - strlen($return); // 33% extra bytes
		$bytes = $random_bytes(max($getlen, 12));
		if($bytes === false) {
			return false;
		}
		$bytes = str_replace($chars, '', base64_encode($bytes));
		$return .= substr($bytes, 0, $getlen);
		if(strlen($return) == $length) {
			return $numeric ? str_replace($num_find, $num_repl, $return) : $return;
		}
		$retry_times++;
	}
}

function strexists($string, $find) {
	return !(!str_contains($string, $find));
}

function avatar($uid, $size = 'middle', $returnsrc = 0, $real = FALSE, $static = FALSE, $ucenterurl = '', $class = '', $extra = '', $random = 0, $avatarapi = false, $datasrc = true) {
	global $_G;
	if(!empty($_G['setting']['plugins']['func'][HOOKTYPE]['avatar']) && !defined('IN_ADMINCP')) {
		$_G['hookavatar'] = '';
		$param = func_get_args();
		hookscript('avatar', 'global', 'funcs', ['param' => $param], 'avatar');
		if($_G['hookavatar']) {
			return $_G['hookavatar'];
		}
	}
	if(is_array($returnsrc)) {
		isset($returnsrc['random']) && $random = $returnsrc['random'];
		isset($returnsrc['extra']) && $extra = $returnsrc['extra'];
		isset($returnsrc['class']) && $class = $returnsrc['class'];
		isset($returnsrc['ucenterurl']) && $ucenterurl = $returnsrc['ucenterurl'];
		isset($returnsrc['static']) && $static = $returnsrc['static'];
		isset($returnsrc['real']) && $real = $returnsrc['real'];
		$returnsrc = $returnsrc['returnsrc'] ?? 0;
	}
	static $staticavatar;
	if($staticavatar === null) {
		$staticavatar = $_G['setting']['avatarmethod'];
	}
	$uid = abs(intval($uid));
	if(!$returnsrc) {
		$class = trim($class.' user_avatar');
	}

	if($staticavatar == 2 && !$returnsrc && !$real) {
		return '<img data-uid="'.$uid.'" data-size="'.$size.'"'.($random ? ' data-random="'.rand(1000, 9999).'"' : '').' class="_avt'.($class ? ' '.$class : '').'"'.($extra ? ' '.$extra : '').' />';
	}
	static $avtstatus;
	if($avtstatus === null) {
		$avtstatus = [];
	}
	$dynavt = intval($_G['setting']['dynavt']);

	$ossavatar = false;
	if(!empty($_G['setting']['ftp']['on']) && $_G['setting']['ftp']['on'] == 2 && $_G['setting']['oss']['oss_avatar']) {//企飞版
		$avatarurl = $_G['setting']['ftp']['attachurl'].'avatar';
		$staticavatar = 1;
		$ossavatar = true;
	} else {
		$ucenterurl = empty($ucenterurl) ? $_G['setting']['ucenterurl'] : $ucenterurl;
		$avatarurl = empty($_G['setting']['avatarurl']) ? $ucenterurl.'/data/avatar' : $_G['setting']['avatarurl'];
	}
	$size = in_array($size, ['big', 'middle', 'small']) ? $size : 'middle';
	$rawuid = $uid;
	$src = $datasrc ? 'data-src' : 'src';
	$defaultclass = $datasrc ? '_avt' : '';
	if(!$staticavatar && !$static && $ucenterurl != '.' || $avatarapi) {
		$trandom = '';
		if($random == 1) {
			$trandom = '&random=1';
		} elseif($dynavt == 2 || ($dynavt == 1 && $uid == $_G['uid']) || $random == 2) {
			$trandom = '&ts=1';
		}
		if($avatarapi) {
			$url = $_G['siteurl'].'avatar/';
		} else {
			if($avatarurl != $ucenterurl.'/data/avatar') {
				$ucenterurl = $avatarurl;
			}
			$url = $ucenterurl.'/avatar.php';
		}
		return $returnsrc ? $url.'?uid='.$uid.'&size='.$size.($real ? '&type=real' : '').$trandom : '<img '.$src.'="'.$url.'?uid='.$uid.'&size='.$size.($real ? '&type=real' : '').$trandom.'" class="'.$defaultclass.($class ? ' '.$class : '').'"'.($extra ? ' '.$extra : '').'>';
	} else {
		$uid = sprintf('%09d', $uid);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		$filepath = $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).($real ? '_real' : '').'_avatar_'.$size.'.jpg';
		$file = $avatarurl.'/'.$filepath;
		$noavt = $avatarurl.'/noavatar.'.(!empty($_G['setting']['avatar_default']) ? $_G['setting']['avatar_default'] : 'svg');
		$trandom = '';
		$avtexist = -1;
		if(!$staticavatar && !$static) {
			$avatar_file = DISCUZ_ROOT.$_G['setting']['avatarpath'].$filepath;
			if(isset($avtstatus[$rawuid])) {
				$avtexist = $avtstatus[$rawuid][0];
			} else {
				$avtexist = file_exists($avatar_file) ? 1 : 0;
				$avtstatus[$rawuid][0] = $avtexist;
			}
			if($avtexist) {
				if($dynavt == 2 || ($dynavt == 1 && $rawuid && $rawuid == $_G['uid']) || $random == 2) {
					if(empty($avtstatus[$rawuid][1])) {
						$avtstatus[$rawuid][1] = filemtime($avatar_file);
					}
					$trandom = '?ts='.$avtstatus[$rawuid][1];
				}
			} else {
				$file = $noavt;
			}
		}
		if($random == 1 && $avtexist != 0) {
			$trandom = '?random='.rand(1000, 9999);
		} elseif($ossavatar && ($dynavt == 2 || ($dynavt == 1 && $rawuid && $rawuid == $_G['uid']) || $random == 2)) {
			$trandom = '?ts='.TIMESTAMP;
		}
		if($trandom) {
			$file = $file.$trandom;
		}
		return $returnsrc ? $file : '<img '.$src.'="'.$file.'" class="'.$defaultclass.($class ? ' '.$class : '').'"'.($extra ? ' '.$extra : '').'>';
	}
}

function i18n($cmd, $langkey = '', $path = '') {
	return i18n::cmd($cmd, $langkey, $path);
}

function mylang($langvar = null, $vars = [], $default = null) {
	return lang('my', $langvar, $vars, $default);
}

function lang($file, $langvar = null, $vars = [], $default = null) {
	global $_G;
	$fileinput = $file;
	$list = explode('/', $file);
	$path = $list[0];
	$file = $list[1] ?? false;
	if(!$file) {
		$file = $path;
		$path = '';
	}
	if(str_contains($file, ':')) {
		$path = 'plugin';
		[$file] = explode(':', $file);
	}

	$lang = [];
	if($path != 'plugin') {
		$key = $path == '' ? $file : $path.'_'.$file;
		if(!isset($_G['lang'][$key])) {
			$f = ($path == '' ? '' : $path.'/').'lang_'.$file.'.php';
			$lang = i18n::getLang($f);
			if(!empty($_G['i18n']) && file_exists($loadfile = MITFRAME_APP(MITFRAME_APP).'/i18n/'.$_G['i18n'].'/'.$f)) {
				include $loadfile;
			} elseif(file_exists($loadfile = MITFRAME_APP(MITFRAME_APP).'/i18n/'.currentlang().'/'.$f)) {
				include $loadfile;
			}
			$_G['lang'][$key] = (array)$lang;
		}
		if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
			$f = 'touch/lang_template.php';
			$lang = i18n::getLang($f);
			if(!empty($_G['i18n']) && file_exists($loadfile = MITFRAME_APP(MITFRAME_APP).'/i18n/'.$_G['i18n'].'/'.$f)) {
				include $loadfile;
			} elseif(file_exists($loadfile = MITFRAME_APP(MITFRAME_APP).'/i18n/'.currentlang().'/'.$f)) {
				include $loadfile;
			}
			$_G['lang'][$key] = array_merge((array)$_G['lang'][$key], (array)$lang);
		}
		if($file != 'error' && !isset($_G['cache']['pluginlanguage_system'])) {
			loadcache('pluginlanguage_system');
		}
		if(!isset($_G['hooklang'][$fileinput])) {
			if(isset($_G['cache']['pluginlanguage_system'][$fileinput]) && is_array($_G['cache']['pluginlanguage_system'][$fileinput])) {
				$_G['lang'][$key] = array_merge((array)$_G['lang'][$key], (array)$_G['cache']['pluginlanguage_system'][$fileinput]);
			}
			$_G['hooklang'][$fileinput] = true;
		}
		$returnvalue = &$_G['lang'];
	} else {
		if(empty($_G['config']['plugindeveloper']) && empty($_G['i18n'])) {
			loadcache('pluginlanguage_script');
		} elseif(!isset($_G['cache']['pluginlanguage_script'][$file]) && preg_match('/^[a-z]+[a-z0-9_]*$/i', $file)) {
			if(!empty($_G['i18n']) && file_exists($loadfile = DISCUZ_PLUGIN($file).'/i18n/'.$_G['i18n'].'/lang_plugin.php')) {
				@include $loadfile;
				$_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
			} elseif(file_exists($loadfile = DISCUZ_PLUGIN($file).'/i18n/'.currentlang().'/lang_plugin.php')) {
				@include $loadfile;
				$_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
			} elseif(@include(DISCUZ_DATA.'./plugindata/'.$file.'.lang.php')) {
				$_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
			} else {
				loadcache('pluginlanguage_script');
			}
		}
		$returnvalue = &$_G['cache']['pluginlanguage_script'];
		!is_array($returnvalue) && $returnvalue = [];
		$key = &$file;
	}
	$return = $langvar !== null ? ($returnvalue[$key][$langvar] ?? null) : (is_array($returnvalue[$key]) ? $returnvalue[$key] : []);
	$return = $return === null ? ($default !== null ? $default : ($path != 'plugin' ? '' : $file.':').$langvar) : $return;
	$searchs = $replaces = [];
	if($vars && is_array($vars)) {
		foreach($vars as $k => $v) {
			$searchs[] = '{'.$k.'}';
			$replaces[] = $v;
		}
	}
	if(is_string($return) && str_contains($return, '{_G/')) {
		preg_match_all('/\{_G\/(.+?)\}/', $return, $gvar);
		foreach($gvar[0] as $k => $v) {
			$searchs[] = (string)$v;
			$replaces[] = getglobal($gvar[1][$k]);
		}
	}
	if($searchs || $replaces) {
		$return = str_replace($searchs, $replaces, $return);
	}
	return $return;
}

function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $cachefile, $tpldir, $file) {
	static $tplrefresh, $timestamp, $targettplname;
	if($tplrefresh === null) {
		$tplrefresh = getglobal('config/output/tplrefresh');
		$timestamp = getglobal('timestamp');
	}

	if(empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($timestamp % $tplrefresh))) {
		if(empty($timecompare) || tplfile::filemtime($subtpl) > $timecompare) {
			require_once DISCUZ_ROOT.'/source/class/class_template.php';
			$template = new template();
			$template->parse_template($maintpl, $templateid, $tpldir, $file, $cachefile);
			if($targettplname === null) {
				$targettplname = getglobal('style/tplfile');
				if(!empty($targettplname)) {
					include_once libfile('function/block');
					$targettplname = strtr($targettplname, ':', '_');
					update_template_block($targettplname, getglobal('style/tpldirectory'), $template->blocks);
				}
				$targettplname = true;
			}
			return TRUE;
		}
	}
	return FALSE;
}

function _checkDiyTpl($diypath, $file) {
	global $_G;
	if(defined('IN_MOBILE') && constant('IN_MOBILE') == 2) {
		$file = $_G['mobiletpl'][IN_MOBILE].'/'.$file;
	}
	if(file_exists($diypath.$file.'.htm')) {
		return true;
	}
	updatediytemplate($file, $_G['style']['tpldirectory']);
	return file_exists($diypath.$file.'.htm');
}

function apptemplate($file) {
	return template($file, 0, 'source/app/'.MITFRAME_APP.'/template');
}

function template($file, $templateid = 0, $tpldir = '', $gettplfile = 0, $primaltpl = '') {
	global $_G;

	if(!defined('CURMODULE')) {
		define('CURMODULE', '');
	}
	if(!defined('HOOKTYPE')) {
		define('HOOKTYPE', !defined('IN_MOBILE') ? 'hookscript' : 'hookscriptmobile');
	}
	if(!empty($_G['setting']['plugins']['func'][HOOKTYPE]['template'])) {
		$param = func_get_args();
		$hookreturn = hookscript('template', 'global', 'funcs', ['param' => $param, 'caller' => 'template'], 'template');
		if($hookreturn) {
			return $hookreturn;
		}
	}

	if(str_starts_with($tpldir, 'source/plugin/')) {
		$tpldir = DISCUZ_PLUGIN(substr($tpldir, 14));
		$tpldir = str_replace(DISCUZ_ROOT, '', $tpldir);
	}

	static $_init_style = false;
	if($_init_style === false) {
		C::app()->_init_style();
		$_init_style = true;
	}
	$oldfile = $file;
	if(str_contains($file, ':')) {
		$clonefile = '';
		[$templateid, $file, $clonefile] = explode(':', $file.'::');
		$oldfile = $file;
		$file = empty($clonefile) ? $file : $file.'_'.$clonefile;
		if($templateid == 'diy') {
			$indiy = false;
			$_G['style']['tpldirectory'] = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
			$_G['style']['prefile'] = '';
			$diypath = DISCUZ_DATA.'./diy/'.$_G['style']['tpldirectory'].'/'; //DIY模板文件目录
			$preend = '_diy_preview';
			$_GET['preview'] = !empty($_GET['preview']) ? $_GET['preview'] : '';
			$curtplname = $oldfile;
			$basescript = $_G['mod'] == 'viewthread' && !empty($_G['thread']) ? 'forum' : $_G['basescript'];
			if(isset($_G['cache']['diytemplatename'.$basescript])) {
				$diytemplatename = &$_G['cache']['diytemplatename'.$basescript];
			} else {
				if(!isset($_G['cache']['diytemplatename'])) {
					loadcache('diytemplatename');
				}
				$diytemplatename = &$_G['cache']['diytemplatename'];
			}
			$tplsavemod = 0;
			if(isset($diytemplatename[$file]) && _checkDiyTpl($diypath, $file) && ($tplsavemod = 1) || empty($_G['forum']['styleid']) && ($file = $primaltpl ? $primaltpl : $oldfile) && isset($diytemplatename[$file]) && _checkDiyTpl($diypath, $file)) {
				$tpldir = 'data/diy/'.$_G['style']['tpldirectory'].'/';
				!$gettplfile && $_G['style']['tplsavemod'] = $tplsavemod;
				$curtplname = $file;
				if(isset($_GET['diy']) && $_GET['diy'] == 'yes' || isset($_GET['diy']) && $_GET['preview'] == 'yes') { //DIY模式或预览模式下做以下判断
					$flag = file_exists($diypath.$file.$preend.'.htm');
					if($_GET['preview'] == 'yes') {
						$file .= $flag ? $preend : '';
					} else {
						$_G['style']['prefile'] = $flag ? 1 : '';
					}
				}
				$indiy = true;
			} else {
				$file = $primaltpl ? $primaltpl : $oldfile;
			}
			$tplrefresh = $_G['config']['output']['tplrefresh'];
			if($indiy && ($tplrefresh == 1 || ($tplrefresh > 1 && !($_G['timestamp'] % $tplrefresh))) && filemtime($diypath.$file.'.htm') < tplfile::filemtime(DISCUZ_ROOT.$_G['style']['tpldirectory'].'/'.($primaltpl ? $primaltpl : $oldfile).'.php')) {
				if(!updatediytemplate($file, $_G['style']['tpldirectory'])) {
					unlink($diypath.$file.'.htm');
					$tpldir = '';
				}
			}

			if(!$gettplfile && empty($_G['style']['tplfile'])) {
				$_G['style']['tplfile'] = empty($clonefile) ? $curtplname : $oldfile.':'.$clonefile;
			}

			$_G['style']['prefile'] = !empty($_GET['preview']) && $_GET['preview'] == 'yes' ? '' : $_G['style']['prefile'];

		} else {
			$tpldir = DISCUZ_PLUGIN($templateid).'/template';
		}
	}

	$file .= !empty($_G['inajax']) && ($file == 'common/header' || $file == 'common/footer') ? '_ajax' : '';
	$tpldir = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
	$templateid = $templateid ? $templateid : (defined('TEMPLATEID') ? TEMPLATEID : '');
	$filebak = $file;

	if((constant('HOOKTYPE') == 'hookscriptmobile' && defined('IN_MOBILE') && !defined('TPL_DEFAULT') && !str_contains($file, $_G['mobiletpl'][IN_MOBILE].'/') || (isset($_G['forcemobilemessage']) && $_G['forcemobilemessage'])) || defined('IN_PREVIEW')) {
		if(defined('IN_MOBILE') && constant('IN_MOBILE') == 2) {
			$oldfile .= !empty($_G['inajax']) && ($oldfile == 'common/header' || $oldfile == 'common/footer') ? '_ajax' : '';
		}
		$file = $_G['mobiletpl'][IN_MOBILE].'/'.$oldfile;
	}

	if(str_starts_with($tpldir, 'source/app/')) {
		$tplfile = DISCUZ_ROOT.$tpldir.'/'.$file.'.php';
		$inApp = true;
	} else {
		if(!$tpldir) {
			$tpldir = './template/default';
		}
		$tplfile = DISCUZ_TEMPLATE($tpldir).'/'.$file.'.htm';
		if(!tplfile::file_exists($tplfile)) {
			$tplfile = DISCUZ_TEMPLATE($tpldir).'/'.$file.'.php';
		}
	}

	$file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.$_G['basescript'].'_'.CURMODULE;

	if((constant('HOOKTYPE') == 'hookscriptmobile' && defined('IN_MOBILE') && !defined('TPL_DEFAULT')) || defined('IN_PREVIEW')) {
		if(strpos($tpldir, 'plugin')) {
			if(!tplfile::file_exists($tpldir.'/'.$file.'.htm') && !tplfile::file_exists($tpldir.'/'.$file.'.php')) {
				$url = $_SERVER['REQUEST_URI'].(strexists($_SERVER['REQUEST_URI'], '?') ? '&' : '?').'mobile=no';
				showmessage('mobile_template_no_found', '', ['url' => $url]);
			} else {
				$mobiletplfile = $tpldir.'/'.$file.'.htm';
				if(!tplfile::file_exists($mobiletplfile)) {
					$mobiletplfile = $tpldir.'/'.$file.'.php';
				}
			}
		}
		empty($mobiletplfile) && $mobiletplfile = $file.'.htm';
		if(!empty($inApp)) {
		} elseif(strpos($tpldir, 'plugin') && (tplfile::file_exists(DISCUZ_TEMPLATE($mobiletplfile)) || tplfile::file_exists(substr(DISCUZ_TEMPLATE($mobiletplfile), 0, -4).'.php'))) {
			$tplfile = $mobiletplfile;
		} elseif(!$clonefile && !tplfile::file_exists(DISCUZ_TEMPLATE($tpldir.'/'.$mobiletplfile)) &&
			!tplfile::file_exists(substr(DISCUZ_TEMPLATE($tpldir.'/'.$mobiletplfile), 0, -4).'.php') &&
			!tplfile::file_exists(DISCUZ_TEMPLATE(TPLDIR.'/'.$mobiletplfile)) &&
			!tplfile::file_exists(substr(DISCUZ_TEMPLATE(TPLDIR.'/'.$mobiletplfile), 0, -4).'.php')) {
			if(str_starts_with($file, $_G['mobiletpl'][IN_MOBILE].'/email/')) {
				$tplfile = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $tplfile);
				$file = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $file);
			}
			$mobiletplfile = DISCUZ_TEMPLATE('./template/default/'.$file.'.php');
			if(!tplfile::file_exists($mobiletplfile) && !$_G['forcemobilemessage']) {
				$tplfile = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $tplfile);
				$file = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $file);
				define('TPL_DEFAULT', true);
				define('TPL_DEFAULT_FILE', $mobiletplfile);
			} else {
				$tplfile = $mobiletplfile;
			}
		} else {
			if(!empty($clonefile) && tplfile::file_exists($diypath.$file.'_'.$clonefile.'.htm')) {
				$file .= '_'.$clonefile;
				$tplfile = $diypath.$file.'.htm';
			} elseif(!tplfile::file_exists($diypath.$mobiletplfile)) {
				$tplfile = DISCUZ_TEMPLATE($tpldir.'/'.$mobiletplfile);
				if(!tplfile::file_exists($tplfile)) {
					$tplfile = DISCUZ_TEMPLATE(TPLDIR.'/'.$mobiletplfile);
				}
			} else {
				$tplfile = $diypath.$mobiletplfile;
			}
		}
	}
	$i18n = $_G['i18n'] ? '_'.$_G['i18n'] : '';
	$cachefile = './template/'.(defined('STYLEID') ? STYLEID.'_' : '_').$templateid.'_'.str_replace('/', '_', $file).$i18n.'.tpl.php';
	if($templateid != 1 && !tplfile::file_exists($tplfile) && !tplfile::file_exists(substr($tplfile, 0, -4).'.php')
		&& !tplfile::file_exists(($tplfile = $tpldir.'/'.$filebak.'.htm'))) {
		$tplfile = DISCUZ_TEMPLATE('./template/default/'.$filebak.'.php');
	}
	if($gettplfile) {
		return $tplfile;
	}
	checktplrefresh($tplfile, $tplfile, tplfile::filemtime(DISCUZ_DATA.$cachefile), $templateid, $cachefile, $tpldir, $file);
	return DISCUZ_DATA.$cachefile;
}

function dsign($str, $length = 16) {
	return substr(md5($str.getglobal('config/security/authkey')), 0, ($length ? max(8, $length) : 16));
}

function modauthkey($id) {
	return md5(getglobal('username').getglobal('uid').getglobal('authkey').substr(TIMESTAMP, 0, -7).$id);
}

function getcurrentnav() {
	global $_G;
	if(!empty($_G['mnid'])) {
		return $_G['mnid'];
	}
	$mnid = '';
	$_G['basefilename'] = $_G['basefilename'] == $_G['basescript'] ? $_G['basefilename'] : $_G['basescript'].'.php';
	if(isset($_G['setting']['navmns']['index.php']) && !empty($_GET['app'])) {
		foreach($_G['setting']['navmns']['index.php'] as $navmn) {
			if($navmn[0] == array_intersect_assoc($navmn[0], $_GET)) {
				$mnid = $navmn[1];
				break;
			}
		}
	}

	if(!$mnid && isset($_G['setting']['navmns'][$_G['basefilename']])) {
		if($_G['basefilename'] == 'home.php' && $_GET['mod'] == 'space' && (empty($_GET['do']) || in_array($_GET['do'], ['follow', 'view']))) {
			$_GET['mod'] = 'follow';
		}
		foreach($_G['setting']['navmns'][$_G['basefilename']] as $navmn) {
			if($navmn[0] == array_intersect_assoc($navmn[0], $_GET) || (isset($_GET['gid']) && $navmn[0]['mod'] == 'forumdisplay' && $navmn[0]['fid'] == $_GET['gid']) || ($navmn[0]['mod'] == 'space' && $_GET['mod'] == 'spacecp' && ($navmn[0]['do'] == $_GET['ac'] || $navmn[0]['do'] == 'album' && $_GET['ac'] == 'upload'))) {
				$mnid = $navmn[1];
				break;
			}
		}

	}
	if(!$mnid && isset($_G['setting']['navdms'])) {
		foreach($_G['setting']['navdms'] as $navdm => $navid) {
			if(str_contains(strtolower($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), $navdm) && !str_contains(strtolower($_SERVER['HTTP_HOST']), $navdm)) {
				$mnid = $navid;
				break;
			}
		}
	}
	if(!$mnid && isset($_G['setting']['navmn'][$_G['basefilename']])) {
		$mnid = $_G['setting']['navmn'][$_G['basefilename']];
	}
	return $mnid;
}

function loaducenter() {
	require_once DISCUZ_ROOT.'./config/config_ucenter.php';
	require_once DISCUZ_ROOT.'./source/class/uc/client.php';
}

function loadwitframe() {
	require_once DISCUZ_ROOT.'./source/class/witframe/core.php';
}

function loadcache($cachenames, $force = false) {
	global $_G;
	static $loadedcache = [];
	$cachenames = is_array($cachenames) ? $cachenames : [$cachenames];
	$caches = [];
	foreach($cachenames as $k) {
		if(!isset($loadedcache[$k]) || $force) {
			$caches[] = $k;
			$loadedcache[$k] = true;
		}
	}

	if(!empty($caches)) {
		$cachedata = table_common_syscache::t()->fetch_all_syscache($caches, $force);
		foreach($cachedata as $cname => $data) {
			if(DISCUZ_LANG == 'EN/') {
				if($cname == 'onlinelist'){
					$data['legend'] = $data['legend_en'];
				}elseif($cname == 'forums'){
					foreach($data as &$value) {
						$value['name'] = $value['name_en'];
					}
				}elseif(str_starts_with($cname, 'usergroup_')) {
					$data['grouptitle'] = $data['grouptitle_en'];
				}elseif($cname == 'usergroups') {
					foreach($data as &$value) {
						$value['grouptitle'] = $value['grouptitle_en'];
					}
				}elseif($cname == 'setting') {
					$data['bbname'] = $data['sitename'] = 'Discuz Math Forum';
					$data['navs'][2]['navname'] = 'Forum';
					foreach($data['navs'] as $key => &$value) {
						$value['nav'] = preg_replace(
							'/<a([^>]*?)title="([^"]*?)"([^>]*?)>.*?<\/a/i',
							'<a$1title="$2"$3>$2</a',
							$value['nav']
						);
					}
					$data['menunavs'] = preg_replace(
						'/<a([^>]*?)title="([^"]*?)"([^>]*?)>.*?<\/a/i',
						'<a$1title="$2"$3>$2</a',
						$data['menunavs']
					);
					foreach($data['footernavs'] as $key => &$value) {
						$value['code'] = preg_replace(
							'/<a([^>]*?)>.*?<\/a>/i',
							'<a$1>'.$value['navname'].'</a>',
							$value['code']
						);
					}
				}
			}
			if($cname == 'setting') {
				$_G['setting'] = $data;
			} elseif($cname == 'usergroup_'.$_G['groupid']) {
				$_G['cache'][$cname] = $_G['group'] = $data;
			} elseif($cname == 'style_default') {
				$_G['cache'][$cname] = $_G['style'] = $data;
			} elseif($cname == 'grouplevels') {
				$_G['grouplevels'] = $data;
			} else {
				$_G['cache'][$cname] = $data;
			}
		}
	}
	return true;
}

function dgmdate($timestamp, $format = 'dt', $timeoffset = 9999, $uformat = '') {
	global $_G;
	$format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
	static $dformat, $tformat, $dtformat, $offset, $lang;
	if($dformat === null) {
		$dformat = getglobal('setting/dateformat');
		$tformat = getglobal('setting/timeformat');
		$dtformat = $dformat.' '.$tformat;
		$offset = getglobal('member/timeoffset');
		$sysoffset = getglobal('setting/timeoffset');
		$offset = $offset == 9999 ? ($sysoffset ? $sysoffset : 0) : $offset;
		$lang = lang('core', 'date');
	}
	$timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
	$timeoffset = intval($timeoffset);
	$timestamp += $timeoffset * 3600;
	$format = empty($format) || $format == 'dt' || IS_ROBOT ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
	if($format == 'u') {
		$todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
		$s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
		$time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
		if($timestamp >= $todaytimestamp) {
			if($time > 3600) {
				$_v = intval($time / 3600);
				$return = $_v.'&nbsp;'.($_v > 1 ? $lang['hours'] : $lang['hour']).$lang['before'];
			} elseif($time > 1800) {
				$return = $lang['half'].$lang['hour'].$lang['before'];
			} elseif($time > 60) {
				$_v = intval($time / 60);
				$return = $_v.'&nbsp;'.($_v > 1 ? $lang['mins'] : $lang['min']).$lang['before'];
			} elseif($time > 0) {
				$return = $time.'&nbsp;'.($time > 1 ? $lang['secs'] : $lang['sec']).$lang['before'];
			} elseif($time == 0) {
				$return = $lang['now'];
			} else {
				$return = $s;
			}
		} elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
			if($days == 0) {
				$return = $lang['yday'].'&nbsp;'.gmdate($tformat, $timestamp);
			} elseif($days == 1) {
				$return = $lang['byday'].'&nbsp;'.gmdate($tformat, $timestamp);
			} else {
				$return = ($days + 1).'&nbsp;'.$lang['day'].$lang['before'];
			}
		} else {
			$return = $s;
		}
		return $return;
	} else {
		return gmdate($format, $timestamp);
	}
}

function dmktime($date) {
	if(strpos($date, '-')) {
		if(strpos($date, ' ')) {
			$_time = explode(' ', $date);
			$time = explode('-', $_time[0]);
			$time2 = explode(':', $_time[1]);
		} else {
			$time = explode('-', $date);
			$time2 = [0, 0, 0];
		}

		return mktime(intval($time2[0]), intval($time2[1]), intval($time2[2]), intval($time[1]), intval($time[2]), intval($time[0]));
	}
	return 0;
}

function dnumber($number) {
	return abs((int)$number) > 10000 ? '<span title="'.$number.'">'.intval($number / 10000).lang('core', '10k').'</span>' : $number;
}

function savecache($cachename, $data) {
	table_common_syscache::t()->insert_syscache($cachename, $data);
}

function save_syscache($cachename, $data) {
	savecache($cachename, $data);
}

function block_get($parameter) {
	include_once libfile('function/block');
	block_get_batch($parameter);
}

function block_display($bid) {
	include_once libfile('function/block');
	block_display_batch($bid);
}

function dimplode($array) {
	if(!empty($array)) {
		$array = array_map('addslashes', $array);
		return "'".implode("','", is_array($array) ? $array : [$array])."'";
	} else {
		return 0;
	}
}

function appfile($filename, $app = '') {
	$app = $app ?: MITFRAME_APP;
	$p = strpos($filename, '/');
	if($p === false) {
		return '';
	}
	$folder = substr($filename, 0, $p);

	$apppath = '/source/app/'.$app;
	$path = $apppath.'/'.$filename;

	return preg_match('/^[\w\d\/_]+$/i', $path) ? realpath(DISCUZ_ROOT.$path.'.php') : false;
}

function libfile($libname, $folder = '') {
	$isPlugin = false;
	if(str_starts_with($folder, 'plugin/')) {
		$libpath = DISCUZ_PLUGIN(substr($folder, 7));
		$isPlugin = true;
	} else {
		$libpath = '/source/'.$folder;
	}
	if(str_contains($libname, '/')) {
		[$pre, $name] = explode('/', $libname);
		$path = "{$libpath}/{$pre}/{$pre}_{$name}";
	} else {
		$path = "{$libpath}/{$libname}";
	}
	if($isPlugin) {
		return file_exists($path.'.php') ? realpath($path.'.php') : false;
	}
	return preg_match('/^[\w\d\/_]+$/i', $path) ? realpath(DISCUZ_ROOT.$path.'.php') : false;
}

function childfile($childname, $path = null, $allowplugin = true) {
	if(!preg_match('/^[\w\/]+$/', $childname) || $path && !preg_match('/^[\w\/]+$/', $path)) {
		return '';
	}
	global $_G;
	if(!$path) {
		$basescript = $_G['basescript'] != 'group' ? $_G['basescript'] : 'forum';
		$path = $basescript.'/'.(defined('CURMODULE') ? CURMODULE.'/' : '');
	} else {
		$path = $path.'/';
	}
	$v = $path.$childname;
	if(!empty($_G['setting']['child'][$v])) {
		return DISCUZ_ROOT.'./source/child/'.$_G['setting']['child'][$v].'.php';
	}
	if($allowplugin && $path != 'admin/' && !empty($_G['setting']['plugins']['child'][$v]) &&
		!empty($_G['setting']['plugins']['available']) && in_array($_G['setting']['plugins']['child'][$v]['plugin'], $_G['setting']['plugins']['available'])) {
		return DISCUZ_PLUGIN($_G['setting']['plugins']['child'][$v]['plugin']).'/child/'.$_G['setting']['plugins']['child'][$v]['file'].'.php';
	}
	$p = strpos($v, '/');
	$app = substr($v, 0, $p);
	$f = substr($v, $p + 1);
	if($app == 'global') {
		return realpath(DISCUZ_ROOT.'./source/child/'.$f.'.php');
	}
	return appfile('child/'.$f, $app);
}

function dstrlen($str) {
	if(strtolower(CHARSET) != 'utf-8') {
		return strlen($str);
	}
	$count = 0;
	for($i = 0; $i < strlen($str); $i++) {
		$value = ord($str[$i]);
		if($value > 127) {
			$count++;
			if($value >= 192 && $value <= 223) $i++;
			elseif($value >= 224 && $value <= 239) $i = $i + 2;
			elseif($value >= 240 && $value <= 247) $i = $i + 3;
		}
		$count++;
	}
	return $count;
}

function cutstr($string, $length, $dot = ' ...') {
	if(strlen($string) <= $length) {
		return $string;
	}

	$pre = chr(1);
	$end = chr(1);
	$string = str_replace(['&amp;', '&quot;', '&lt;', '&gt;'], [$pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end], $string);

	$strcut = '';
	if(strtolower(CHARSET) == 'utf-8') {

		$n = $tn = $noc = 0;
		while($n < strlen($string)) {

			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1;
				$n++;
				$noc++;
			} elseif(194 <= $t && $t <= 223) {
				$tn = 2;
				$n += 2;
				$noc += 2;
			} elseif(224 <= $t && $t <= 239) {
				$tn = 3;
				$n += 3;
				$noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$tn = 4;
				$n += 4;
				$noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$tn = 5;
				$n += 5;
				$noc += 2;
			} elseif($t == 252 || $t == 253) {
				$tn = 6;
				$n += 6;
				$noc += 2;
			} else {
				$n++;
			}

			if($noc >= $length) {
				break;
			}

		}
		if($noc > $length) {
			$n -= $tn;
		}

		$strcut = substr($string, 0, $n);

	} else {
		$_length = $length - 1;
		for($i = 0; $i < $length; $i++) {
			if(ord($string[$i]) <= 127) {
				$strcut .= $string[$i];
			} else if($i < $_length) {
				$strcut .= $string[$i].$string[++$i];
			}
		}
	}

	$strcut = str_replace([$pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end], ['&amp;', '&quot;', '&lt;', '&gt;'], $strcut);

	$pos = strrpos($strcut, chr(1));
	if($pos !== false) {
		$strcut = substr($strcut, 0, $pos);
	}
	return $strcut.$dot;
}

function dstripslashes($string) {
	if(empty($string)) return $string;
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}

function aidencode($aid, $type = 0, $tid = 0) {
	global $_G;
	$s = !$type ? $aid.'|'.substr(md5($aid.md5($_G['config']['security']['authkey']).TIMESTAMP.$_G['uid']), 0, 8).'|'.TIMESTAMP.'|'.$_G['uid'].'|'.$tid : $aid.'|'.md5($aid.md5($_G['config']['security']['authkey']).TIMESTAMP).'|'.TIMESTAMP;
	return rawurlencode(base64_encode($s));
}

function getforumimg($aid, $nocache = 0, $w = 140, $h = 140, $type = '') {
	global $_G;
	$key = dsign($aid.'|'.$w.'|'.$h);
	return 'forum.php?mod=image&aid='.$aid.'&size='.$w.'x'.$h.'&key='.rawurlencode($key).($nocache ? '&nocache=yes' : '').($type ? '&type='.$type : '');
}

function getdiscuzimg($module, $aid, $nocache = 0, $w = 140, $h = 140, $type = '') {
	global $_G;
	$key = dsign($module.'|'.$aid.'|'.$w.'|'.$h);
	return 'misc.php?mod=image&module='.$module.'&aid='.$aid.'&size='.$w.'x'.$h.'&key='.rawurlencode($key).($nocache ? '&nocache=yes' : '').($type ? '&type='.$type : '');
}

function rewriterulecheck($type = '') {
	global $_G;

	if($type) {
		return is_array($_G['setting']['rewritestatus']) && in_array($type, $_G['setting']['rewritestatus']);
	} else {
		return $_G['setting']['rewritestatus'];
	}
}

function rewriteoutput($type, $returntype, $host) {
	global $_G;
	$fextra = '';
	if($type == 'forum_forumdisplay') {
		[, , , $fid, $page, $extra] = func_get_args();
		$r = [
			'{fid}' => empty($_G['setting']['forumkeys'][$fid]) ? $fid : $_G['setting']['forumkeys'][$fid],
			'{page}' => $page ? $page : 1,
		];
	} elseif($type == 'forum_viewthread') {
		[, , , $tid, $page, $prevpage, $extra] = func_get_args();
		$r = [
			'{tid}' => $tid,
			'{page}' => $page ? $page : 1,
			'{prevpage}' => $prevpage && !IS_ROBOT ? $prevpage : 1,
		];
	} elseif($type == 'home_space') {
		[, , , $uid, $username, $extra] = func_get_args();
		$_G['setting']['rewritecompatible'] && $username = rawurlencode($username);
		$r = [
			'{user}' => $uid ? 'uid' : 'username',
			'{value}' => $uid ? $uid : $username,
		];
	} elseif($type == 'home_blog') {
		[, , , $uid, $blogid, $extra] = func_get_args();
		$r = [
			'{uid}' => $uid,
			'{blogid}' => $blogid,
		];
	} elseif($type == 'group_group') {
		[, , , $fid, $page, $extra] = func_get_args();
		$r = [
			'{fid}' => $fid,
			'{page}' => $page ? $page : 1,
		];
	} elseif($type == 'portal_topic') {
		[, , , $name, $extra] = func_get_args();
		$r = [
			'{name}' => $name,
		];
	} elseif($type == 'portal_article') {
		[, , , $id, $page, $extra] = func_get_args();
		$r = [
			'{id}' => $id,
			'{page}' => $page ? $page : 1,
		];
	} elseif($type == 'forum_archiver') {
		[, , $action, $value, $page, $extra] = func_get_args();
		$host = '';
		$r = [
			'{action}' => $action,
			'{value}' => $value,
		];
		if($page) {
			$fextra = '?page='.$page;
		}
	} elseif($type == 'plugin') {
		[, , $pluginid, $module, , $param, $extra] = func_get_args();
		$host = '';
		$r = [
			'{pluginid}' => $pluginid,
			'{module}' => $module,
		];
		if($param) {
			$fextra = '?'.$param;
		}
	}
	$href = str_replace(array_keys($r), $r, $_G['setting']['rewriterule'][$type]).$fextra;
	if(!$returntype) {
		return '<a href="'.$host.$href.'"'.(!empty($extra) ? stripslashes($extra) : '').'>';
	} else {
		return $host.$href;
	}
}

function mobilereplace($file, $replace) {
	return helper_mobile::mobilereplace($file, $replace);
}

function mobileoutput() {
	helper_mobile::mobileoutput();
}

function output() {

	global $_G;


	if(defined('DISCUZ_OUTPUTED')) {
		return;
	} else {
		define('DISCUZ_OUTPUTED', 1);
	}

	if(!empty($_G['blockupdate'])) {
		block_updatecache($_G['blockupdate']['bid']);
	}

	if(defined('IN_MOBILE')) {
		mobileoutput();
	}
       $domainApp = [];
       if (isset($_G['setting']['domain']['app']) && is_array($_G['setting']['domain']['app'])) {
               $domainApp = $_G['setting']['domain']['app'];
       }
       $havedomain = implode('', $domainApp);
	if($_G['setting']['rewritestatus'] || !empty($havedomain)) {
		$content = ob_get_contents();
		$content = output_replace($content);


		ob_end_clean();
		$_G['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();

		echo $content;
	}

	if(isset($_G['makehtml'])) {
		helper_makehtml::make_html();
	}

	if($_G['setting']['ftp']['connid']) {
		@ftp_close($_G['setting']['ftp']['connid']);
	}
	$_G['setting']['ftp'] = [];

	if(defined('CACHE_FILE') && CACHE_FILE && !defined('CACHE_FORBIDDEN') && !defined('IN_MOBILE') && !IS_ROBOT && !checkmobile()) {
		if(diskfreespace(DISCUZ_ROOT.'./'.$_G['setting']['cachethreaddir']) > 1000000) {
			$content = empty($content) ? ob_get_contents() : $content;
			$temp_md5 = md5(substr($_G['timestamp'], 0, -3).substr($_G['config']['security']['authkey'], 3, -3));
			$temp_formhash = substr($temp_md5, 8, 8);
			$content = preg_replace('/(name=[\'|\"]formhash[\'|\"] value=[\'\"]|formhash=)('.constant('FORMHASH').')/ismU', '${1}'.$temp_formhash, $content);
			//避免siteurl伪造被缓存
			$temp_siteurl = 'siteurl_'.substr($temp_md5, 16, 8);
			$content = preg_replace('/("|\')('.preg_quote($_G['siteurl'], '/').')/ismU', '${1}'.$temp_siteurl, $content);
			$content = empty($content) ? ob_get_contents() : $content;
			file_put_contents(CACHE_FILE, $content, LOCK_EX);
			chmod(CACHE_FILE, 0777);
		}
	}

	if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include_once(libfile('function/debug'))) {
		function_exists('debugmessage') && debugmessage();
	}
}

function output_replace($content) {
	global $_G;
	if(defined('IN_MODCP') || defined('IN_ADMINCP')) return $content;
	if(!empty($_G['setting']['output']['str']['search'])) {
		if(empty($_G['setting']['domain']['app']['default'])) {
			$_G['setting']['output']['str']['replace'] = str_replace('{CURHOST}', $_G['siteurl'], $_G['setting']['output']['str']['replace']);
		}
		$content = str_replace($_G['setting']['output']['str']['search'], $_G['setting']['output']['str']['replace'], $content);
	}
	if(!empty($_G['setting']['output']['preg']['search']) && (empty($_G['setting']['rewriteguest']) || IS_ROBOT)) {
		if(empty($_G['setting']['domain']['app']['default'])) {
			$_G['setting']['output']['preg']['search'] = str_replace('\{CURHOST\}', preg_quote($_G['siteurl'], '/'), $_G['setting']['output']['preg']['search']);
			$_G['setting']['output']['preg']['replace'] = str_replace('{CURHOST}', $_G['siteurl'], $_G['setting']['output']['preg']['replace']);
		}

		foreach($_G['setting']['output']['preg']['search'] as $key => $value) {
			$content = preg_replace_callback(
				$value,
				function($matches) use ($_G, $key) {
					return eval('return '.$_G['setting']['output']['preg']['replace'][$key].';');
				},
				$content
			);
		}
	}

	return $content;
}

function output_ajax() {
	global $_G;
	$s = ob_get_contents();
	ob_end_clean();
	$s = preg_replace("/([\\x01-\\x08\\x0b-\\x0c\\x0e-\\x1f])+/", ' ', $s);
	$s = str_replace([chr(0), ']]>'], [' ', ']]&gt;'], $s);
	if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include_once(libfile('function/debug'))) {
		function_exists('debugmessage') && $s .= debugmessage(1);
	}
	$domainApp = [];
	if(isset($_G['setting']['domain']['app']) && is_array($_G['setting']['domain']['app'])) {
		$domainApp = $_G['setting']['domain']['app'];
	}
	$havedomain = implode('', $domainApp);
	if($_G['setting']['rewritestatus'] || !empty($havedomain)) {
		$s = output_replace($s);
	}
	return $s;
}

function runhooks($scriptextra = '') {
	if(!defined('HOOKTYPE')) {
		define('HOOKTYPE', !defined('IN_MOBILE') ? 'hookscript' : 'hookscriptmobile');
	}
	if(defined('CURMODULE')) {
		global $_G;
		if($_G['setting']['plugins']['func'][HOOKTYPE]['common']) {
			hookscript('common', 'global', 'funcs', [], 'common');
		}
		hookscript(CURMODULE, $_G['basescript'], 'funcs', [], '', $scriptextra);
	}
}

function hookscript($script, $hscript, $type = 'funcs', $param = [], $func = '', $scriptextra = '') {
	global $_G;
	static $pluginclasses = [];
	if($hscript == 'home') {
		if($script == 'space') {
			$scriptextra = !$scriptextra ? getgpc('do') : $scriptextra;
			$script = 'space'.(!empty($scriptextra) ? '_'.$scriptextra : '');
		} elseif($script == 'spacecp') {
			$scriptextra = !$scriptextra ? getgpc('ac') : $scriptextra;
			$script .= !empty($scriptextra) ? '_'.$scriptextra : '';
		}
	}
	if(!defined('HOOKTYPE')) {
		define('HOOKTYPE', !defined('IN_MOBILE') ? 'hookscript' : 'hookscriptmobile');
	}
	if(!isset($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
		return;
	}
	if(!isset($_G['cache']['plugin'])) {
		loadcache('plugin');
	}
	foreach((array)$_G['setting'][HOOKTYPE][$hscript][$script]['module'] as $identifier => $include) {
		if($_G['pluginrunlist'] && !in_array($identifier, $_G['pluginrunlist'])) {
			continue;
		}
		$hooksadminid[$identifier] = !$_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] || ($_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] && $_G['adminid'] > 0 && $_G['setting']['hookscript'][$hscript][$script]['adminid'][$identifier] >= $_G['adminid']);
		if($hooksadminid[$identifier]) {
			@include_once DISCUZ_PLUGIN($include).'.class.php';
		}
	}
	if(isset($_G['setting'][HOOKTYPE][$hscript][$script][$type]) && is_array($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
		$_G['inhookscript'] = true;
		$funcs = !$func ? $_G['setting'][HOOKTYPE][$hscript][$script][$type] : [$func => $_G['setting'][HOOKTYPE][$hscript][$script][$type][$func]];
		foreach($funcs as $hookkey => $hookfuncs) {
			foreach($hookfuncs as $hookfunc) {
				if($hooksadminid[$hookfunc[0]]) {
					$classkey = (HOOKTYPE != 'hookscriptmobile' ? '' : 'mobile').'plugin_'.($hookfunc[0].($hscript != 'global' ? '_'.$hscript : ''));
					if(!class_exists($classkey, false)) {
						continue;
					}
					if(!isset($pluginclasses[$classkey])) {
						$pluginclasses[$classkey] = new $classkey;
					}
					if(!method_exists($pluginclasses[$classkey], $hookfunc[1])) {
						continue;
					}
					$return = call_user_func([$pluginclasses[$classkey], $hookfunc[1]], $param);

					if(str_ends_with($hookkey, '_extend') && !empty($_G['setting']['pluginhooks'][$hookkey])) {
						continue;
					}

					if(is_array($return)) {
						if(!isset($_G['setting']['pluginhooks'][$hookkey]) || is_array($_G['setting']['pluginhooks'][$hookkey])) {
							foreach($return as $k => $v) {
								$_G['setting']['pluginhooks'][$hookkey][$k] .= $v;
							}
						} else {
							foreach($return as $k => $v) {
								$_G['setting']['pluginhooks'][$hookkey][$k] = $v;
							}
						}
					} else {
						if(!(isset($_G['setting']['pluginhooks'][$hookkey]) && is_array($_G['setting']['pluginhooks'][$hookkey]))) {
							if(!isset($_G['setting']['pluginhooks'][$hookkey])) {
								$_G['setting']['pluginhooks'][$hookkey] = '';
							}
							$_G['setting']['pluginhooks'][$hookkey] .= $return;
						} else {
							foreach($_G['setting']['pluginhooks'][$hookkey] as $k => $v) {
								$_G['setting']['pluginhooks'][$hookkey][$k] .= $return;
							}
						}
					}
				}
			}
		}
	}
	$_G['inhookscript'] = false;
}

function hookscriptoutput($tplfile) {
	global $_G;
	if(!empty($_G['hookscriptoutput'])) {
		return;
	}
	hookscript('global', 'global');
	$_G['hookscriptoutput'] = true;
	if(defined('CURMODULE')) {
		$param = ['template' => $tplfile, 'message' => getglobal('hookscriptmessage'), 'values' => getglobal('hookscriptvalues')];
		hookscript(CURMODULE, $_G['basescript'], 'outputfuncs', $param);
	}
}

function pluginmodule($pluginid, $type) {
	global $_G;
	$pluginid = $pluginid ? preg_replace('/[^A-Za-z0-9_:]/', '', $pluginid) : '';
	if(!isset($_G['cache']['plugin'])) {
		loadcache('plugin');
	}
	[$identifier, $module] = explode(':', $pluginid);
	if(!is_array($_G['setting']['plugins'][$type]) || !array_key_exists($pluginid, $_G['setting']['plugins'][$type])) {
		showmessage('plugin_nonexistence');
	}
	if(!empty($_G['setting']['plugins'][$type][$pluginid]['url'])) {
		dheader('location: '.$_G['setting']['plugins'][$type][$pluginid]['url']);
	}
	$directory = $_G['setting']['plugins'][$type][$pluginid]['directory'];
	if(empty($identifier) || !preg_match('/^[a-z]+[a-z0-9_]*\/$/i', $directory) || !preg_match('/^[a-z0-9_\-]+$/i', $module)) {
		showmessage('undefined_action');
	}
	if(@!file_exists($modfile = DISCUZ_PLUGIN($directory).$module.'.inc.php')) {
		showmessage('plugin_module_nonexistence', '', ['mod' => $directory.$module.'.inc.php']);
	}
	return $modfile;
}

function updatecreditbyaction($action, $uid = 0, $extrasql = [], $needle = '', $coef = 1, $update = 1, $fid = 0) {
	$key = 'updatecreditbyaction_'.$action.'_'.$uid;
	if(discuz_process::islocked($key, 1)) {
		return;
	}
	$credit = credit::instance();
	if($extrasql) {
		$credit->extrasql = $extrasql;
	}
	$value = $credit->execrule($action, $uid, $needle, $coef, $update, $fid);
	discuz_process::unlock($key);
	return $value;
}

function checklowerlimit($action, $uid = 0, $coef = 1, $fid = 0, $returnonly = 0) {
	require_once libfile('function/credit');
	return _checklowerlimit($action, $uid, $coef, $fid, $returnonly);
}

function batchupdatecredit($action, $uids = 0, $extrasql = [], $coef = 1, $fid = 0) {
	foreach((array)$uids as $uid) {
		updatecreditbyaction($action, $uid, $extrasql, '', $coef, 1, $fid);
	}
}

function updatemembercount($uids, $dataarr = [], $checkgroup = true, $operation = '', $relatedid = 0, $ruletxt = '', $customtitle = '', $custommemo = '') {
	if(!empty($uids) && (is_array($dataarr) && $dataarr)) {
		require_once libfile('function/credit');
		return _updatemembercount($uids, $dataarr, $checkgroup, $operation, $relatedid, $ruletxt, $customtitle, $custommemo);
	}
	return true;
}

function checkusergroup($uid = 0) {
	$credit = &credit::instance();
	$credit->checkusergroup($uid);
}

function checkformulasyntax($formula, $operators, $tokens, $values = '', $funcs = []) {
	$var = implode('|', $tokens);

	if(!empty($formula)) {
		$formula = preg_replace("/($var)/", "\$\\1", $formula);
		return formula_tokenize($formula, $operators, $tokens, $values, $funcs);
	}
	return true;
}

function formula_tokenize($formula, $operators, $tokens, $values, $funcs) {
	$fexp = token_get_all('<?php '.$formula);
	$prevseg = 1; // 1左括号2右括号3变量4运算符5函数
	$isclose = 0;
	$tks = implode('|', $tokens);
	$op1 = $op2 = [];
	foreach($operators as $orts) {
		if(strlen($orts) === 1) {
			$op1[] = $orts;
		} else {
			$op2[] = $orts;
		}
	}
	foreach($fexp as $k => $val) {
		if(is_array($val)) {
			if(in_array($val[0], [T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER])) {
				// 是变量
				if(!in_array($prevseg, [1, 4])) {
					return false;
				}
				$prevseg = 3;
				if($val[0] == T_VARIABLE && !preg_match('/^\$('.$tks.')$/', $val[1])) {
					return false;
				}
				if($val[0] == T_CONSTANT_ENCAPSED_STRING && !($values && preg_match('/^'.$values.'$/', $val[1]))) {
					return false;
				}
			} elseif($val[0] == T_STRING && in_array($val[1], $funcs)) {
				// 是函数
				if(!in_array($prevseg, [1, 4])) {
					return false;
				}
				$prevseg = 5;
			} elseif($val[0] == T_WHITESPACE || ($k == 0 && $val[0] == T_OPEN_TAG)) {
				// 空格或文件头，忽略
			} elseif(in_array($val[1], $op2)) {
				// 是运算符
				if(!in_array($prevseg, [2, 3])) {
					return false;
				}
				$prevseg = 4;
			} else {
				return false;
			}
		} else {
			if($val === '(') {
				// 是左括号
				if(!in_array($prevseg, [1, 4, 5])) {
					return false;
				}
				$prevseg = 1;
				$isclose++;
			} elseif($val === ')') {
				// 是右括号
				if(!in_array($prevseg, [2, 3])) {
					return false;
				}
				$prevseg = 2;
				$isclose--;
				if($isclose < 0) {
					return false;
				}
			} elseif(in_array($val, $op1)) {
				// 是运算符
				if(!in_array($prevseg, [2, 3]) && $val !== '-') {
					return false;
				}
				$prevseg = 4;
			} else {
				return false;
			}
		}
	}
	return (in_array($prevseg, [2, 3]) && $isclose === 0);
}

function checkformulacredits($formula) {
	return checkformulasyntax(
		$formula,
		['+', '-', '*', '/'],
		['extcredits[1-8]', 'digestposts', 'posts', 'threads', 'oltime', 'friends', 'doings', 'polls', 'blogs', 'albums', 'sharings']
	);
}

function debug($var = null, $vardump = false) {
	echo '<pre>';
	$vardump = empty($var) || $vardump;
	if($vardump) {
		var_dump($var);
	} else {
		print_r($var);
	}
	exit();
}

function debuginfo() {
	global $_G;
	if(getglobal('setting/debug')) {
		$_G['debuginfo'] = [
			'time' => number_format((microtime(true) - $_G['starttime']), 6),
			'queries' => DB::object()->querynum,
			'memory' => ucwords(C::memory()->type)
		];
		if(DB::object()->slaveid) {
			$_G['debuginfo']['queries'] = 'Total '.DB::object()->querynum.', Slave '.DB::object()->slavequery;
		}
		return TRUE;
	} else {
		return FALSE;
	}
}

function getfocus_rand($module) {
	global $_G;

	if(empty($_G['setting']['focus']) || !array_key_exists($module, $_G['setting']['focus']) || !empty($_G['cookie']['nofocus_'.$module]) || !$_G['setting']['focus'][$module]) {
		return null;
	}
	loadcache('focus');
	if(empty($_G['cache']['focus']['data']) || !is_array($_G['cache']['focus']['data'])) {
		return null;
	}
	return $_G['setting']['focus'][$module][array_rand($_G['setting']['focus'][$module])];
}

function check_seccode($value, $idhash, $fromjs = 0, $modid = '', $verifyonly = false) {
	$f = childfile('check_seccode', 'global/core');
	if($f) {
		require $f;
	}
	return helper_seccheck::check_seccode($value, $idhash, $fromjs, $modid, $verifyonly);
}

function check_secqaa($value, $idhash, $verifyonly = false) {
	$f = childfile('check_secqaa', 'global/core');
	if($f) {
		require $f;
	}
	return helper_seccheck::check_secqaa($value, $idhash, $verifyonly);
}

function seccheck($rule, $param = []) {
	if(defined('DISABLE_SECCHECK')) {
		return [];
	}
	$f = childfile('seccheck', 'global/core');
	if($f) {
		require $f;
	}
	return helper_seccheck::seccheck($rule, $param);
}

function make_seccode($seccode = '') {
	$f = childfile('make_seccode', 'global/core');
	if($f) {
		require $f;
	}
	return helper_seccheck::make_seccode($seccode);
}

function make_secqaa() {
	$f = childfile('make_secqaa', 'global/core');
	if($f) {
		require $f;
	}
	return helper_seccheck::make_secqaa();
}

function adshow($parameter) {
	global $_G;
	if(getgpc('inajax') || $_G['group']['closead']) {
		return;
	}
	$return = (isset($_G['config']['plugindeveloper']) && $_G['config']['plugindeveloper'] == 2) ? '<hook>[ad '.$parameter.']</hook>' : '';
	$params = explode('/', $parameter);
	$customid = 0;
	$customc = explode('_', $params[0]);
	if($customc[0] == 'custom') {
		$params[0] = $customc[0];
		$customid = $customc[1];
	} elseif($customc[0] == 'addon') {
		loadcache('advs');
		if(!empty($_G['cache']['advs']['addons'][$params[0]])) {
			$customid = $_G['cache']['advs']['addons'][$params[0]];
		} else {
			$customid = table_common_advertisement_custom::t()->insert(['name' => $params[0]], true);
			require_once libfile('function/cache');
			updatecache('advs');
		}
		$params[0] = 'custom';
	}
	$adcontent = null;
	if(empty($_G['setting']['advtype']) || !in_array($params[0], $_G['setting']['advtype'])) {
		$adcontent = '';
	}
	if($adcontent === null) {
		loadcache('advs');
		$adids = [];
		$evalcode = &$_G['cache']['advs']['evalcode'][$params[0]];
		$parameters = &$_G['cache']['advs']['parameters'][$params[0]];
		$codes = &$_G['cache']['advs']['code'][$_G['basescript']][$params[0]];
		if(!empty($codes)) {
			foreach($codes as $adid => $code) {
				$parameter = &$parameters[$adid];
				$checked = true;
				@eval($evalcode['check']);
				if($checked) {
					$adids[] = $adid;
				}
			}
			if(!empty($adids)) {
				$adcode = $extra = '';
				@eval($evalcode['create']);
				if(empty($notag)) {
					$adcontent = '<div'.($params[1] != '' ? ' class="'.$params[1].'"' : '').$extra.'>'.$adcode.'</div>';
				} else {
					$adcontent = $adcode;
				}
			}
		}
	}
	$adfunc = 'ad_'.$params[0];
	$_G['setting']['pluginhooks'][$adfunc] = null;
	hookscript('ad', 'global', 'funcs', ['params' => $params, 'content' => $adcontent, 'customid' => $customid], $adfunc);
	if(empty($_G['setting']['hookscript']['global']['ad']['funcs'][$adfunc])) {
		hookscript('ad', $_G['basescript'], 'funcs', ['params' => $params, 'content' => $adcontent, 'customid' => $customid], $adfunc);
	}
	return $return.($_G['setting']['pluginhooks'][$adfunc] === null ? $adcontent : $_G['setting']['pluginhooks'][$adfunc]);
}

function showmessage($message, $url_forward = '', $values = [], $extraparam = [], $custom = 0) {
	require_once libfile('function/message');
	return dshowmessage($message, $url_forward, $values, $extraparam, $custom);
}

function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {
	if(!getgpc($var)) {
		return FALSE;
	} else {
		return helper_form::submitcheck($var, $allowget, $seccodecheck, $secqaacheck);
	}
}

function multi($num, $perpage, $curpage, $mpurl, $maxpages = 0, $page = 10, $autogoto = FALSE, $simple = FALSE, $jsfunc = FALSE) {
	return $num > $perpage ? helper_page::multi($num, $perpage, $curpage, $mpurl, $maxpages, $page, $autogoto, $simple, $jsfunc) : '';
}

function simplepage($num, $perpage, $curpage, $mpurl) {
	return helper_page::simplepage($num, $perpage, $curpage, $mpurl);
}

function censor($message, $modword = NULL, $return = FALSE, $modasban = TRUE) {
	$f = childfile('censor', 'global/core');
	if($f) {
		require $f;
	}
	return helper_form::censor($message, $modword, $return, $modasban);
}

function censormod($message) {
	$f = childfile('censormod', 'global/core');
	if($f) {
		require $f;
	}
	return !(getglobal('group/ignorecensor') || !$message) && helper_form::censormod($message);
}

function space_merge(&$values, $tablename, $isarchive = false) {
	global $_G;

	$uid = empty($values['uid']) ? $_G['uid'] : $values['uid'];
	$var = "member_{$uid}_{$tablename}";
	if($uid) {
		if(!isset($_G[$var])) {
			$ext = $isarchive ? '_archive' : '';
			if(($_G[$var] = C::t('common_member_'.$tablename.$ext)->fetch($uid)) !== false) {
				if($tablename == 'field_home') {
					$_G['setting']['privacy'] = empty($_G['setting']['privacy']) ? [] : (is_array($_G['setting']['privacy']) ? $_G['setting']['privacy'] : dunserialize($_G['setting']['privacy']));
					$_G[$var]['privacy'] = empty($_G[$var]['privacy']) ? [] : (is_array($_G[$var]['privacy']) ? $_G[$var]['privacy'] : dunserialize($_G[$var]['privacy']));
					foreach(['feed', 'view', 'profile'] as $pkey) {
						if(empty($_G[$var]['privacy'][$pkey]) && !isset($_G[$var]['privacy'][$pkey])) {
							$_G[$var]['privacy'][$pkey] = $_G['setting']['privacy'][$pkey] ?? [];
						}
					}
					$_G[$var]['acceptemail'] = empty($_G[$var]['acceptemail']) ? [] : dunserialize($_G[$var]['acceptemail']);
					if(empty($_G[$var]['acceptemail'])) {
						$_G[$var]['acceptemail'] = empty($_G['setting']['acceptemail']) ? [] : dunserialize($_G['setting']['acceptemail']);
					}
				}
			} else {
				C::t('common_member_'.$tablename.$ext)->insert(['uid' => $uid]);
				$_G[$var] = [];
			}
		}
		$values = array_merge($values, $_G[$var]);
	}
}

function runlog($file, $message, $halt = 0) {
	helper_log::runlog($file, $message, $halt);
}

function stripsearchkey($string) {
	$string = trim($string);
	return str_replace('*', '%', addcslashes($string, '%_'));
}

function dmkdir($dir, $mode = 0777, $makeindex = TRUE) {
	if(!is_dir($dir)) {
		dmkdir(dirname($dir), $mode, $makeindex);
		@mkdir($dir, $mode);
		if(!empty($makeindex)) {
			@touch($dir.'/index.html');
			@chmod($dir.'/index.html', 0777);
		}
	}
	return true;
}

function dreferer($default = '') {
	global $_G;

	$default = empty($default) && $_ENV['curapp'] ? $_ENV['curapp'].'.php' : '';
	$_G['referer'] = !empty($_GET['referer']) ? $_GET['referer'] : $_SERVER['HTTP_REFERER'];
	$_G['referer'] = str_ends_with($_G['referer'], '?') ? substr($_G['referer'], 0, -1) : $_G['referer'];

	if(strpos($_G['referer'], 'member.php?mod=logging')) {
		$_G['referer'] = $default;
	}

	$reurl = parse_url($_G['referer']);
	$hostwithport = $reurl['host'].(isset($reurl['port']) ? ':'.$reurl['port'] : '');

	if(!$reurl || (isset($reurl['scheme']) && !in_array(strtolower($reurl['scheme']), ['http', 'https']))) {
		$_G['referer'] = '';
	}

	if(!empty($hostwithport) && !in_array($hostwithport, [$_SERVER['HTTP_HOST'], 'www.'.$_SERVER['HTTP_HOST']]) && !in_array($_SERVER['HTTP_HOST'], [$hostwithport, 'www.'.$hostwithport])) {
		if(!in_array($hostwithport, $_G['setting']['domain']['app']) && !isset($_G['setting']['domain']['list'][$hostwithport])) {
			$domainroot = substr($hostwithport, strpos($hostwithport, '.') + 1);
			if(empty($_G['setting']['domain']['root']) || (is_array($_G['setting']['domain']['root']) && !in_array($domainroot, $_G['setting']['domain']['root']))) {
				$_G['referer'] = $_G['setting']['domain']['defaultindex'] ? $_G['setting']['domain']['defaultindex'] : 'index.php';
			}
		}
	} elseif(empty($hostwithport)) {
		$_G['referer'] = $_G['siteurl'].'./'.$_G['referer'];
	}

	$_G['referer'] = durlencode($_G['referer']);
	return $_G['referer'];
}

function ftpcmd($cmd, $arg1 = '') {
	static $ftp;
	$ftpconfig = getglobal('setting/ftp');
	if(empty($ftpconfig['on']) || empty($ftpconfig['host'])) {
		return $cmd == 'error' ? -101 : 0;
	} elseif($ftp == null) {
		$ftp = &discuz_ftp::instance();
	}
	if(!$ftp->enabled) {
		return $ftp->error();
	} elseif($ftp->enabled && !$ftp->connectid) {
		$ftp->connect();
	}
	return match ($cmd) {
		'upload' => $ftp->upload(getglobal('setting/attachdir').'/'.$arg1, $arg1),
		'delete' => $ftp->ftp_delete($arg1),
		'close' => $ftp->ftp_close(),
		'error' => $ftp->error(),
		'object' => $ftp,
		default => false,
	};

}

function ftpperm($fileext, $filesize) {
	global $_G;
	$return = false;
	if($_G['setting']['ftp']['on']) {
		if(((!$_G['setting']['ftp']['allowedexts'] && !$_G['setting']['ftp']['disallowedexts']) || ($_G['setting']['ftp']['allowedexts'] && in_array($fileext, $_G['setting']['ftp']['allowedexts'])) || ($_G['setting']['ftp']['disallowedexts'] && !in_array($fileext, $_G['setting']['ftp']['disallowedexts']) && (!$_G['setting']['ftp']['allowedexts'] || $_G['setting']['ftp']['allowedexts'] && in_array($fileext, $_G['setting']['ftp']['allowedexts'])))) && (!$_G['setting']['ftp']['minsize'] || $filesize >= $_G['setting']['ftp']['minsize'] * 1024)) {
			$return = true;
		}
	}
	return $return;
}

function diconv($str, $in_charset, $out_charset = CHARSET, $ForceTable = FALSE) {
	global $_G;

	$in_charset = strtoupper($in_charset);
	$out_charset = strtoupper($out_charset);

	if(empty($str) || $in_charset == $out_charset) {
		return $str;
	}

	$out = '';

	if(!$ForceTable) {
		if(function_exists('iconv')) {
			$out = iconv($in_charset, $out_charset.'//IGNORE', $str);
		} elseif(function_exists('mb_convert_encoding')) {
			$out = mb_convert_encoding($str, $out_charset, $in_charset);
		}
	}

	if($out == '') {
		$chinese = new Chinese($in_charset, $out_charset, true);
		$out = $chinese->Convert($str);
	}

	return $out;
}

function widthauto() {
	global $_G;
	if($_G['disabledwidthauto']) {
		return 0;
	}
	if(!empty($_G['widthauto'])) {
		return $_G['widthauto'] > 0 ? 1 : 0;
	}
	if($_G['setting']['switchwidthauto'] && !empty($_G['cookie']['widthauto'])) {
		return $_G['cookie']['widthauto'] > 0 ? 1 : 0;
	} else {
		return $_G['setting']['allowwidthauto'] ? 0 : 1;
	}
}

function renum($array) {
	$newnums = $nums = [];
	foreach($array as $id => $num) {
		$newnums[$num][] = $id;
		$nums[$num] = $num;
	}
	return [$nums, $newnums];
}

function sizecount($size) {
	if($size >= 1073741824) {
		$size = round($size / 1073741824 * 100) / 100 .' GB';
	} elseif($size >= 1048576) {
		$size = round($size / 1048576 * 100) / 100 .' MB';
	} elseif($size >= 1024) {
		$size = round($size / 1024 * 100) / 100 .' KB';
	} else {
		$size = intval($size).' Bytes';
	}
	return $size;
}

function swapclass($class1, $class2 = '') {
	static $swapc = null;
	$swapc = isset($swapc) && $swapc != $class1 ? $class1 : $class2;
	return $swapc;
}

function writelog($file, $log) {
	helper_log::writelog($file, $log);
}

function logger($type, $member, $operationuid, $data = [], $device = [], $record = '', $source = 'Web') {
	global $_G;

	if(empty($_G['setting']['log'][$type])) {
		return;
	}

	$log_data = [
		'uid' => !empty($member['uid']) ? $member['uid'] : 0,
		'loginname' => !empty($member['loginname']) ? $member['loginname'] : '',
		'username' => !empty($member['username']) ? $member['username'] : '',
		'type' => $type,
		'data' => json_encode($data),
		'operationuid' => $operationuid,
		'source' => $source,
		'device' => json_encode(!empty($device) ? $device : getLogInfo()),
		'record' => $record,
		'dateline' => getglobal('timestamp')
	];
	table_common_log::t()->insert($log_data, false, false, true);
}

function getstatus($status, $position) {
	$t = (int)$status & pow(2, (int)$position - 1) ? 1 : 0;
	return $t;
}

function setstatus($position, $value, $baseon = null) {
	$t = pow(2, $position - 1);
	if($value) {
		$t = $baseon | $t;
	} elseif($baseon !== null) {
		$t = $baseon & ~$t;
	} else {
		$t = ~$t;
	}
	return $t & 0xFFFF;
}

function notification_add($touid, $type, $note, $notevars = [], $system = 0) {
	$f = childfile('notification_add', 'global/core');
	if($f) {
		require $f;
	}
	return helper_notification::notification_add($touid, $type, $note, $notevars, $system);
}

function manage_addnotify($type, $from_num = 0, $langvar = []) {
	$f = childfile('manage_addnotify', 'global/core');
	if($f) {
		require $f;
	}
	helper_notification::manage_addnotify($type, $from_num, $langvar);
}

function sendpm($toid, $subject, $message, $fromid = '', $replypmid = 0, $isusername = 0, $type = 0) {
	$f = childfile('sendpm', 'global/core');
	if($f) {
		require $f;
	}
	return helper_pm::sendpm($toid, $subject, $message, $fromid, $replypmid, $isusername, $type);
}

function g_icon($groupid, $return = 0, $height = 20) {
	global $_G;
	if(empty($_G['cache']['usergroups'][$groupid]['icon'])) {
		$s = '';
	} else {
		$h = $height > 0 ? 'style="width:auto;height:'.intval($height).'px" ' : '';
		if(preg_match('/^https?:\/\//is', $_G['cache']['usergroups'][$groupid]['icon'])) {
			$s = '<img src="'.$_G['cache']['usergroups'][$groupid]['icon'].'" alt="" class="vm" '.$h.'/>';
		} else {
			$s = '<img src="'.$_G['setting']['attachurl'].'common/'.$_G['cache']['usergroups'][$groupid]['icon'].'" alt="" class="vm" '.$h.'/>';
		}
	}
	if($return) {
		return $s;
	} else {
		echo $s;
	}
}

function updatediytemplate($targettplname = '', $tpldirectory = '') {
	$r = false;
	$alldata = !empty($targettplname) ? [table_common_diy_data::t()->fetch_diy($targettplname, $tpldirectory)] : table_common_diy_data::t()->range();
	require_once libfile('function/portalcp');
	foreach($alldata as $value) {
		$r = save_diy_data($value['tpldirectory'], $value['primaltplname'], $value['targettplname'], dunserialize($value['diycontent']));
	}
	return $r;
}

function getposttablebytid($tids, $primary = 0) {
	return table_forum_post::getposttablebytid($tids, $primary);
}

function getposttable($tableid = 0, $prefix = false) {
	return table_forum_post::getposttable($tableid, $prefix);
}

function lmemory($cmd, $key = '', $value = '', $ttl = 0) {
	if(!getglobal('config/memory/yac')) {
		return null;
	}
	static $m = null;
	if($m === null) {
		$m = new memory_driver_yac();
		$m->init([]);
	}
	if($cmd == 'check') {
		return $m->enable;
	}
	$key = 'L_'.getglobal('config/memory/prefix').md5($key);
	if(!$m->enable) {
		return memory($cmd, $key, $value, $ttl);
	}
	return match ($cmd) {
		'set' => $m->set($key, $value, $ttl),
		'get' => $m->get($key),
		'rm' => $m->rm($key),
		'inc' => $m->inc($key, $value ? $value : 1),
		'dec' => $m->dec($key, $value ? $value : 1),
		'clear' => $m->clear(),
		default => null,
	};
}

/*
 * 以下命令，$value传入的是prefix，其它命令prefix都是最后一个参数
 * 		get, rm, scard, smembers, hgetall, zcard, exists
 * eval 时，传入参数如下：
 * 		$cmd = 'eval', $key = script, $value = argv, 
 * 		$ttl = 用于存储script hash的key, $prefix 会自动成为脚本的第一个参数，其余参数序号顺延
 * zadd 时，参数如下：
 * 		$cmd = 'zadd', $key = key, $value = member, $ttl = score
 * zincrby 时，参数如下：
 * 		$cmd = 'zincrby', $key = key, $value = member, $ttl = value to increase
 * zrevrange 和 zrevrangewithscore 时，参数如下；
 * 		$cmd = 'zrevrange', $key = key, $value = start, $ttl = end
 * inc, dec, incex 的 $ttl 无效
 */
function memory($cmd, $key = '', $value = '', $ttl = 0, $prefix = '') {
	static $supported_command = [
		'set', 'add', 'get', 'rm', 'inc', 'dec', 'exists',
		'incex', /* 存在时才inc */
		'sadd', 'srem', 'scard', 'smembers', 'sismember',
		'hmset', 'hgetall', 'hexists', 'hget',
		'eval',
		'zadd', 'zcard', 'zrem', 'zscore', 'zrevrange', 'zincrby', 'zrevrangewithscore' /* 带score返回 */,
		'pipeline', 'commit', 'discard',
		'info', 'expire'
	];

	if($cmd == 'check') {
		return C::memory()->enable ? C::memory()->type : '';
	} elseif(C::memory()->enable && in_array($cmd, $supported_command)) {
		if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) {
			if(is_array($key)) {
				foreach($key as $k) {
					C::memory()->debug[$cmd][] = ($cmd == 'get' || $cmd == 'rm' || $cmd == 'add' ? $value : '').$prefix.$k;
				}
			} else {
				if($cmd === 'hget') {
					C::memory()->debug[$cmd][] = $prefix.$key.'->'.$value;
				} elseif($cmd === 'eval') {
					C::memory()->debug[$cmd][] = $key.'->'.$ttl;
				} else {
					C::memory()->debug[$cmd][] = ($cmd == 'get' || $cmd == 'rm' || $cmd == 'add' ? $value : '').$prefix.$key;
				}
			}
		}
		switch($cmd) {
			case 'set':
				return C::memory()->set($key, $value, $ttl, $prefix);
				break;
			case 'add':
				return C::memory()->add($key, $value, $ttl, $prefix);
				break;
			case 'get':
				return C::memory()->get($key, $value/*prefix*/);
				break;
			case 'rm':
				return C::memory()->rm($key, $value/*prefix*/);
				break;
			case 'exists':
				return C::memory()->exists($key, $value/*prefix*/);
				break;
			case 'inc':
				return C::memory()->inc($key, $value ? $value : 1, $prefix);
				break;
			case 'incex':
				return C::memory()->incex($key, $value ? $value : 1, $prefix);
				break;
			case 'dec':
				return C::memory()->dec($key, $value ? $value : 1, $prefix);
				break;
			case 'sadd':
				return C::memory()->sadd($key, $value, $prefix);
				break;
			case 'srem':
				return C::memory()->srem($key, $value, $prefix);
				break;
			case 'scard':
				return C::memory()->scard($key, $value/*prefix*/);
				break;
			case 'smembers':
				return C::memory()->smembers($key, $value/*prefix*/);
				break;
			case 'sismember':
				return C::memory()->sismember($key, $value, $prefix);
				break;
			case 'hmset':
				return C::memory()->hmset($key, $value, $prefix);
				break;
			case 'hgetall':
				return C::memory()->hgetall($key, $value/*prefix*/);
				break;
			case 'hexists':
				return C::memory()->hexists($key, $value/*field*/, $prefix);
				break;
			case 'hget':
				return C::memory()->hget($key, $value/*field*/, $prefix);
				break;
			case 'eval':
				return C::memory()->evalscript($key/*script*/, $value/*args*/, $ttl/*sha key*/, $prefix);
				break;
			case 'zadd':
				return C::memory()->zadd($key, $value, $ttl/*score*/, $prefix);
				break;
			case 'zrem':
				return C::memory()->zrem($key, $value, $prefix);
				break;
			case 'zscore':
				return C::memory()->zscore($key, $value, $prefix);
				break;
			case 'zcard':
				return C::memory()->zcard($key, $value/*prefix*/);
				break;
			case 'zrevrange':
				return C::memory()->zrevrange($key, $value/*start*/, $ttl/*end*/, $prefix);
				break;
			case 'zrevrangewithscore':
				return C::memory()->zrevrange($key, $value/*start*/, $ttl/*end*/, $prefix, true);
				break;
			case 'zincrby':
				return C::memory()->zincrby($key, $value/*member*/, $ttl ? $ttl : 1/*to increase*/, $prefix);
				break;
			case 'pipeline':
				return C::memory()->pipeline();
				break;
			case 'commit':
				return C::memory()->commit();
				break;
			case 'discard':
				return C::memory()->discard();
				break;
			case 'info':
				return C::memory()->info($key);
				break;
			case 'expire':
				return C::memory()->expire($key, $value, $prefix);
				break;
		}
	}
	return null;
}

function ipaccess($ip, $accesslist) {
	return ip::checkaccess($ip, $accesslist);
}

function ipbanned($ip) {
	return ip::checkbanned($ip);
}

function getcount($tablename, $condition) {
	$arg = [];
	if(empty($condition)) {
		$where = '1';
	} elseif(is_array($condition)) {
		if(!DB::is_pdo()) {
			$where = DB::implode_field_value($condition, ' AND ');
		} else {
			$where = DB::implode_field_value_prepared($condition, $arg, ' AND ');
		}
	} else {
		$where = $condition;
	}
	return intval(DB::result_first('SELECT COUNT(*) AS num FROM '.DB::table($tablename)." WHERE $where", $arg));
}

function sysmessage($message) {
	helper_sysmessage::show($message);
}

function forumperm($permstr, $groupid = 0) {
	return (new helper_forumperm($permstr))->check($groupid);
}

function checkperm($perm) {
	global $_G;
	return defined('IN_ADMINCP') ? true : (empty($_G['group'][$perm]) ? '' : $_G['group'][$perm]);
}

function periodscheck($periods, $showmessage = 1) {
	global $_G;
	if(($periods == 'postmodperiods' || $periods == 'postbanperiods') && (getglobal('setting/postignorearea') || getglobal('setting/postignoreip'))) {
		if($_G['setting']['postignoreip']) {
			foreach(explode("\n", $_G['setting']['postignoreip']) as $ctrlip) {
				if(preg_match('/^('.preg_quote(($ctrlip = trim($ctrlip)), '/').')/', $_G['clientip'])) {
					return false;
					break;
				}
			}
		}
		if($_G['setting']['postignorearea']) {
			$location = $whitearea = '';
			require_once libfile('function/misc');
			$location = trim(convertip($_G['clientip']));
			if($location) {
				$whitearea = preg_quote(trim($_G['setting']['postignorearea']), '/');
				$whitearea = str_replace(["\\*"], ['.*'], $whitearea);
				$whitearea = '.*'.$whitearea.'.*';
				$whitearea = '/^('.str_replace(["\r\n", ' '], ['.*|.*', ''], $whitearea).')$/i';
				if(@preg_match($whitearea, $location)) {
					return false;
				}
			}
		}
	}
	if(!$_G['group']['disableperiodctrl'] && $_G['setting'][$periods]) {
		$now = dgmdate(TIMESTAMP, 'G.i', $_G['setting']['timeoffset']);
		foreach(explode("\r\n", str_replace(':', '.', $_G['setting'][$periods])) as $period) {
			[$periodbegin, $periodend] = explode('-', $period);
			if(($periodbegin > $periodend && ($now >= $periodbegin || $now < $periodend)) || ($periodbegin < $periodend && $now >= $periodbegin && $now < $periodend)) {
				$banperiods = str_replace("\r\n", ', ', $_G['setting'][$periods]);
				if($showmessage) {
					showmessage('period_nopermission', NULL, ['banperiods' => $banperiods], ['login' => 1]);
				} else {
					return TRUE;
				}
			}
		}
	}
	return FALSE;
}

function cknewuser($return = 0) {
	global $_G;

	$result = true;

	if(!$_G['uid']) return true;

	if(checkperm('disablepostctrl')) {
		return $result;
	}
	$ckuser = $_G['member'];

	if($_G['setting']['newbiespan'] && $_G['timestamp'] - $ckuser['regdate'] < $_G['setting']['newbiespan'] * 60) {
		if(empty($return)) showmessage('no_privilege_newbiespan', '', ['newbiespan' => $_G['setting']['newbiespan']], []);
		$result = false;
	}
	if($_G['setting']['need_avatar'] && empty($ckuser['avatarstatus'])) {
		if(empty($return)) showmessage('no_privilege_avatar', '', [], []);
		$result = false;
	}
	if($_G['setting']['need_secmobile'] && empty($ckuser['secmobilestatus'])) {
		if(empty($return)) showmessage('no_privilege_secmobile', '', [], []);
		$result = false;
	}
	if($_G['setting']['need_email'] && empty($ckuser['emailstatus'])) {
		if(empty($return)) showmessage('no_privilege_email', '', [], []);
		$result = false;
	}
	if($_G['setting']['need_friendnum']) {
		space_merge($ckuser, 'count');
		if($ckuser['friends'] < $_G['setting']['need_friendnum']) {
			if(empty($return)) showmessage('no_privilege_friendnum', '', ['friendnum' => $_G['setting']['need_friendnum']], []);
			$result = false;
		}
	}
	return $result;
}

function useractionlog($uid, $action) {
	return helper_log::useractionlog($uid, $action);
}

function getuseraction($var) {
	return helper_log::getuseraction($var);
}

function getuserapp($panel = 0) {
	return '';
}

function getmyappiconpath($appid, $iconstatus = 0) {
	return '';
}

function getexpiration() {
	global $_G;
	$date = getdate($_G['timestamp']);
	return mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']) + 86400;
}

function return_bytes($val) {
	$last = strtolower($val[strlen($val) - 1]);
	if(!is_numeric($val)) {
		$val = substr(trim($val), 0, -1);
	}
	switch($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
}

function iswhitelist($host) {
	global $_G;
	static $iswhitelist = [];

	if(isset($iswhitelist[$host])) {
		return $iswhitelist[$host];
	}
	$hostlen = strlen($host);
	$iswhitelist[$host] = false;
	if(!$_G['cache']['domainwhitelist']) {
		loadcache('domainwhitelist');
	}
	if(is_array($_G['cache']['domainwhitelist'])) foreach($_G['cache']['domainwhitelist'] as $val) {
		$domainlen = strlen($val);
		if($domainlen > $hostlen) {
			continue;
		}
		if(substr($host, -$domainlen) == $val) {
			$iswhitelist[$host] = true;
			break;
		}
	}
	if(!$iswhitelist[$host]) {
		$iswhitelist[$host] = $host == $_SERVER['HTTP_HOST'];
	}
	return $iswhitelist[$host];
}

function getattachtablebyaid($aid) {
	$attach = table_forum_attachment::t()->fetch($aid);
	$tableid = $attach['tableid'];
	return 'forum_attachment_'.($tableid >= 0 && $tableid < 10 ? intval($tableid) : 'unused');
}

function getattachtableid($tid) {
	$tid = (string)$tid;
	return intval($tid[strlen($tid) - 1]);
}

function getattachtablebytid($tid) {
	return 'forum_attachment_'.getattachtableid($tid);
}

function getattachtablebypid($pid) {
	$tableid = DB::result_first('SELECT tableid FROM '.DB::table('forum_attachment')." WHERE pid='$pid' LIMIT 1");
	return 'forum_attachment_'.($tableid >= 0 && $tableid < 10 ? intval($tableid) : 'unused');
}

function getattachnewaid($uid = 0) {
	global $_G;
	$uid = !$uid ? $_G['uid'] : $uid;
	return table_forum_attachment::t()->insert(['tid' => 0, 'pid' => 0, 'uid' => $uid, 'tableid' => 127], true);
}

function get_seosetting($page, $data = [], $defset = []) {
	return helper_seo::get_seosetting($page, $data, $defset);
}

function getimgthumbname($fileStr, $extend = '.thumb.jpg', $holdOldExt = true) {
	if(empty($fileStr)) {
		return '';
	}
	if(!$holdOldExt) {
		$fileStr = substr($fileStr, 0, strrpos($fileStr, '.'));
	}
	$extend = str_contains($extend, '.') ? $extend : '.'.$extend;
	return $fileStr.$extend;
}

function updatemoderate($idtype, $ids, $status = 0) {
	helper_form::updatemoderate($idtype, $ids, $status);
}

function userappprompt() {
}

function dintval($int, $allowarray = false) {
	$ret = intval($int);
	if($int == '' || $int == $ret || !$allowarray && is_array($int)) return $ret;
	if($allowarray && is_array($int)) {
		foreach($int as &$v) {
			$v = dintval($v, true);
		}
		return $int;
	} elseif($int <= 0xffffffff) {
		$l = strlen($int);
		$m = str_starts_with($int, '-') ? 1 : 0;
		if(($l - $m) === strspn($int, '0987654321', $m)) {
			return $int;
		}
	}
	return $ret;
}


function makeSearchSignUrl() {
	return [];
}

function get_related_link($extent) {
	return helper_seo::get_related_link($extent);
}

function parse_related_link($content, $extent) {
	return helper_seo::parse_related_link($content, $extent);
}

function check_diy_perm($topic = [], $flag = '') {
	static $ret = [];
	if(empty($ret)) {
		global $_G;
		$common = !empty($_G['style']['tplfile']) || getgpc('inajax');
		$blockallow = getstatus(getglobal('member/allowadmincp'), 4) || getstatus(getglobal('member/allowadmincp'), 5) || getstatus(getglobal('member/allowadmincp'), 6);
		$ret['data'] = $common && $blockallow;
		$ret['layout'] = $common && (!empty($_G['group']['allowdiy']) || (
					CURMODULE === 'topic' && ($_G['group']['allowmanagetopic'] || $_G['group']['allowaddtopic'] && $topic && $topic['uid'] == $_G['uid'])
				));
	}
	return empty($flag) ? $ret['data'] || $ret['layout'] : $ret[$flag];
}

function strhash($string, $operation = 'DECODE', $key = '') {
	$key = md5($key != '' ? $key : getglobal('authkey'));
	if($operation == 'DECODE') {
		$hashcode = base64_decode($string);
		$hashcode = gzuncompress($hashcode);
		$string = substr($hashcode, 0, -16);
		$hash = substr($hashcode, -16);
		unset($hashcode);
	}

	$vkey = substr(md5($string.substr($key, 0, 16)), 4, 8).substr(md5($string.substr($key, 16, 16)), 18, 8);

	if($operation == 'DECODE') {
		return $hash == $vkey ? $string : '';
	}

	return base64_encode(gzcompress($string.$vkey));
}

function dunserialize($data) {
	// 由于 Redis 驱动侧以序列化保存 array, 取出数据时会自动反序列化（导致反序列化了非Redis驱动序列化的数据），因此存在参数入参为 array 的情况.
	// 考虑到 PHP 8 增强了类型体系, 此类数据直接送 unserialize 会导致 Fatal Error, 需要通过代码层面对此情况进行规避.
	if(is_array($data)) {
		$ret = $data;
	} elseif(($ret = @unserialize($data)) === false) {
		$ret = @unserialize(stripslashes($data));
	}
	return $ret;
}

function browserversion($type) {
	static $return = [];
	static $types = ['ie' => 'msie', 'firefox' => '', 'chrome' => '', 'opera' => '', 'safari' => '', 'mozilla' => '', 'webkit' => '', 'maxthon' => '', 'qq' => 'qqbrowser'];
	if(!$return) {
		$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$other = 1;
		foreach($types as $i => $v) {
			$v = $v ? $v : $i;
			if(str_contains($useragent, $v)) {
				preg_match('/'.$v.'(\/|\s)([\d\.]+)/i', $useragent, $matches);
				$ver = $matches[2];
				$other = $ver !== 0 && $v != 'mozilla' ? 0 : $other;
			} else {
				$ver = 0;
			}
			$return[$i] = $ver;
		}
		$return['other'] = $other;
	}
	return $return[$type];
}

function currentlang() {
	$charset = strtoupper(CHARSET);
	if($charset == 'UTF-8') {
		global $_G;
		if(!empty($_G['config']['lang'])) {
			return $_G['config']['lang'];
		} elseif($_G['config']['output']['language'] == 'zh_cn') {
			return 'SC_UTF8';
		} elseif($_G['config']['output']['language'] == 'zh_tw') {
			return 'TC_UTF8';
		}
	} else {
		return '';
	}
}

function dpreg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null) {
	require_once libfile('function/preg');
	return _dpreg_replace($pattern, $replacement, $subject, $limit, $count);
}

function check_protect_username($username, $return = false) {
	global $_G;

	$censorexp = '/^('.str_replace(['\\*', "\r\n", ' '], ['.*', '|', ''], preg_quote(($_G['setting']['censoruser'] = trim($_G['setting']['censoruser'])), '/')).')$/i';

	if($_G['setting']['censoruser'] && @preg_match($censorexp, $username)) {
		if(!$return) {
			showmessage('profile_username_protect');
		} else {
			return true;
		}
	}
	return false;
}

function delay_task($op, $key, $func = [], $ttl = 86400) {
	$key = 'dzDt_'.$key;
	switch($op) {
		case 'run':
			$func = memory('get', $key);
			if(empty($func) || empty($func[0]) || empty($func[1])) {
				return null;
			}
			try {
				$return = call_user_func_array($func[0], $func[1]);
			} catch (Exception $e) {
				writelog('dt', print_r($e, 1));
				return null;
			}
			memory('rm', $key);
			return $return !== null ? $return : true;
			break;
		case 'set':
			if(empty($func) || empty($func[0]) || empty($func[1])) {
				return null;
			}
			memory('set', $key, $func, $ttl);
			return true;
			break;
		default:
			return null;
	}
}

function restfulAuthSign() {
	$restful = new restful([]);
	return urlencode($restful->getAuthSign());
}

/**
 * @param string $salt
 *
 * @return string
 */
function uuid($salt) {
	return md5($salt.uniqid(md5(microtime(true)), true));
}

// 获取毫秒级时间戳
function getMillisecond() {
	[$microsecond, $time] = explode(' ', microtime()); //' '中间是一个空格
	return (float)sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
}

/**
 * 判断url是否以http://或者https://开头
 * @param string $url
 *
 * @return boolean
 */
function isHttpOrHttps($url) {
	if(str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
		return true;
	}
	return false;
}

// 生成不重复的随机数字字符串
function generateRandomNumbers($length) {
	$numbers = range(0, 9);
	shuffle($numbers);
	return implode('', array_slice($numbers, 0, $length));
}

// 生成不重复的随机大小写字母字符串
function generateRandomLetters($length) {
	$letters = array_merge(range('a', 'z'), range('A', 'Z'));
	shuffle($letters);
	return implode('', array_slice($letters, 0, $length));
}

// 生成不重复的随机数字大小写字母混合字符串
function generateRandomAlphanumeric($length) {
	$characters = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
	shuffle($characters);
	return implode('', array_slice($characters, 0, $length));
}

/**
 * 对比两个json的结构是否一致
 * @param $json1
 * @param $json2
 * @return bool
 */
function compareJsonStructures($json1, $json2) {
	$data1 = json_decode($json1, true);
	$data2 = json_decode($json2, true);

	// 检查 $data1 和 $data2 是否都是数组或对象
	if(!is_array($data1) && !is_object($data1) || !is_array($data2) && !is_object($data2)) {
		return false;
	}

	// 如果类型不同，则结构不同
	if(gettype($data1) !== gettype($data2)) {
		return false;
	}

	// 如果都是数组，则递归比较键和子结构
	if(is_array($data1)) {
		if(count($data1) !== count($data2)) {
			return false;
		}

		foreach($data1 as $key => $value) {
			if(!array_key_exists($key, $data2)) {
				return false;
			}
			if(is_array($value) && !compareJsonStructures(json_encode($value), json_encode($data2[$key]))) {
				return false;
			}
		}
	}

	// 如果都是对象，则递归比较属性和子结构
	if(is_object($data1)) {
		$data1 = get_object_vars($data1);
		$data2 = get_object_vars($data2);

		if(count($data1) !== count($data2)) {
			return false;
		}

		foreach($data1 as $key => $value) {
			if(!array_key_exists($key, $data2)) {
				return false;
			}

			if(is_object($value) && !compareJsonStructures(json_encode($value), json_encode($data2[$key]))) {
				return false;
			}
		}
	}

	// 所有检查都通过，结构相同
	return true;
}

function getimportfilename($fn) {
	if(file_exists($return = $fn.'.json')) {
		return $return;
	} elseif(file_exists($return = $fn.'.xml')) {
		return $return;
	} else {
		return false;
	}
}

// 获取最近使用的标签
function recent_use_tag($idtype = 'tid') {
	$tagarray = $stringarray = [];
	$string = '';
	$i = 0;
	$query = table_common_tagitem::t()->select(0, 0, $idtype, 'itemid', 'DESC', 10);
	foreach($query as $result) {
		if($i > 4) {
			break;
		}
		if($tagarray[$result['tagid']] == '') {
			$i++;
		}
		$tagarray[$result['tagid']] = 1;
	}
	if($tagarray) {
		$query = table_common_tag::t()->fetch_all(array_keys($tagarray));
		foreach($query as $result) {
			$tagarray[$result['tagid']] = $result['tagname'];
		}
	}
	return $tagarray;
}

/**
 * 生成内容发布格式的JSON响应
 * @param string $type 类型，默认：text
 * @param string $editor 编辑器类型，默认：default
 * @param mixed $content 内容，默认空字符串，可以是任意类型
 * @param array $extend 扩展字段，JSON类型，默认空数组
 * @return string JSON格式的字符串
 */
function generate_content_json($type = 'text', $editor = 'default', $content = '', $extend = []) {

	$data = [
		'type' => $type,
		'editor' => $editor,
		'content' => $content,
		'extend' => $extend
	];

	return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * 判断传入内容是否为有效JSON格式且不为空JSON对象
 * @param mixed $content 要检查的内容
 * @param bool $check_null_empty 是否单独验证null或空字符串，默认true
 * @param bool $return_decode_assoc 是否返回decode后的数组，默认false
 * @return bool 是有效的非空JSON返回true，否则返回false
 */
function is_valid_non_empty_json($content, $check_null_empty = true, $return_decode_assoc = false) {
	// 如果开启了null和空字符串检查
	if($check_null_empty) {
		// 检查是否为null
		if($content === null) {
			return false;
		}

		// 检查是否为空字符串
		if(is_string($content) && trim($content) === '') {
			return false;
		}
	}

	// 确保内容是字符串类型
	if(!is_string($content)) {
		return false;
	}

	// 去除首尾空白字符
	$content = trim($content);

	// 检查是否为空字符串
	if(empty($content)) {
		return false;
	}

	// 检查是否为有效的JSON格式
	json_decode($content);
	if(json_last_error() !== JSON_ERROR_NONE) {
		return false;
	}

	// 检查是否为JSON对象且不为空
	if(strpos($content, '{') === 0 && strrpos($content, '}') === strlen($content) - 1) {
		$decoded = json_decode($content, true);
		if(is_array($decoded) && empty($decoded)) {
			return false;
		}
	}

	// 通过所有检查，是有效的非空JSON
	if($return_decode_assoc) {
		$decoded = json_decode($content, true);
		return $decoded;
	} else {
		return true;
	}
}

function jsonExit($err = 0, $key = 'errcode') {
	if($err == 0) {
		exit('{}');
	}
	exit('{"'.$key.'":'.dintval($err).'}');
}

function jsonMsg($return) {
	exit(json_encode($return));
}

// 检查 URL 是否为视频文件
function isVideoUrl($url) {
	$video_extensions = ['rm', 'rmvb', 'flv', 'swf', 'asf', 'asx', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4', 'm4v', '3gp', 'ogv', 'webm', 'mov', 'mkv'];
	$url_parts = parse_url($url);
	if (isset($url_parts['path'])) {
		$extension = strtolower(pathinfo($url_parts['path'], PATHINFO_EXTENSION));
		return in_array($extension, $video_extensions);
	}
	return false;
}

// 检查 URL 是否为音频文件
function isAudioUrl($url) {
	$audio_extensions = ['aac', 'flac', 'ogg', 'mp3', 'm4a', 'weba', 'wma', 'mid', 'wav', 'ra', 'ram'];
	$url_parts = parse_url($url);
	if (isset($url_parts['path'])) {
		$extension = strtolower(pathinfo($url_parts['path'], PATHINFO_EXTENSION));
		return in_array($extension, $audio_extensions);
	}
	return false;
}

/**
 * 解析格式@[用户名]的@用户文本（定界符为[]），适配含空格的用户名
 * @param string $content 帖子/评论原始内容
 * @return array 匹配到的用户列表 [用户名 => UID]（Discuz需UID关联用户）
 */
function parse_at_user($content) {
	global $_G;
	$atlist = $allUsernames = [];

	// 如果用户组不允许@功能，直接返回空数组
	if(!$_G['group']['allowat']) {
		return $atlist;
	}
	// 1. 匹配新格式：@[用户名]（支持含空格的用户名）
	preg_match_all('/@\[([^\]]+)\]/i', $content, $matches);
	if (isset($matches[1])) {
		foreach ($matches[1] as $match) {
			$username = trim($match);
			if (!empty($username)) {
				$allUsernames[] = $username;
			}
		}
	}

	// 2. 匹配旧格式：@用户名（兼容无空格的旧格式）
	preg_match_all('/@([^\s\@\[\]]+)/i', $content.' ', $oldMatches);
	if (isset($oldMatches[1])) {
		foreach ($oldMatches[1] as $oldName) {
			$allUsernames[] = $oldName;
		}
	}

	$uniqueUsernames = array_slice(array_unique($allUsernames), 0, $_G['group']['allowat']);
	if(!empty($uniqueUsernames)) {
		if(!$_G['setting']['at_anyone']) {
			// 先查询关注的人
			$followList = table_home_follow::t()->fetch_all_by_uid_fusername($_G['uid'], $uniqueUsernames);
			foreach($followList as $row) {
				$atlist[$row['followuid']] = $row['fusername'];
			}

			// 如果关注的人不够，查询好友
			if(count($atlist) < $_G['group']['allowat']) {
				$friendList = table_home_friend::t()->fetch_all_by_uid_username($_G['uid'], $uniqueUsernames);
				foreach($friendList as $row) {
					$atlist[$row['fuid']] = $row['fusername'];
				}
			}
		} else {
			// 允许at任何人时，直接查询所有用户
			$userList = table_common_member::t()->fetch_all_by_username($uniqueUsernames);
			foreach($userList as $row) {
				$atlist[$row['uid']] = $row['username'];
			}
		}
	}
	return $atlist;
}
