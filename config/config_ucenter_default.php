<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: config_ucenter_default.php 11023 2010-05-20 02:23:09Z monkey $
 */

// ============================================================================
define('UC_CONNECT', 'mysql');				// 连接 UCenter 的方式: mysql/NULL, 默认为空时为 fscoketopen(), mysql 是直接连接的数据库, 为了效率, 建议采用 mysql
define('UC_STANDALONE', 1);				// 独立模式开关，0=关闭, 1=打开，开启后将不再依赖UCenter Server。注意：开启时必须将 UC_CONNECT 改为 mysql ！
// 数据库相关 (mysql 连接时)
define('UC_DBHOST', 'localhost');			// UCenter 数据库主机
define('UC_DBUSER', 'root');				// UCenter 数据库用户名
define('UC_DBPW', '');				// UCenter 数据库密码
define('UC_DBNAME', 'ultrax');				// UCenter 数据库名称
define('UC_DBCHARSET', 'utf8mb4');				// UCenter 数据库字符集
define('UC_DBTABLEPRE', '`ultrax`.pre_ucenter_');		// UCenter 数据库表前缀
define('UC_DBCONNECT', '0');				// UCenter 数据库持久连接 0=关闭, 1=打开
// 头像相关
define('UC_AVTURL', '');		// 头像服务的基础路径，为空则为默认值，可以设置为独立域名/路径（结尾不能有/），配合CDN使用更佳。如涉及 avatar.php 需在其中再配置一次。
define('UC_AVTPATH', '');		// 头像存储路径，为空则为默认值，仅限独立模式使用，建议保持默认。

// 通信相关
define('UC_KEY', 'yeN3g9EbNfiaYfodV63dI1j8Fbk5HaL7W4yaW4y7u2j4Mf45mfg2v899g451k576');	// 与 UCenter 的通信密钥, 要与 UCenter 保持一致
define('UC_API', 'http://localhost/ucenter/branches/1.5.0/server'); // UCenter 的 URL 地址, 在调用头像时依赖此常量
define('UC_CHARSET', 'utf-8');				// UCenter 的字符集
define('UC_IP', '127.0.0.1');				// UCenter 的 IP, 当 UC_CONNECT 为非 mysql 方式时, 并且当前应用服务器解析域名有问题时, 请设置此值
define('UC_APPID', '1');				// 当前应用的 ID

// ============================================================================

define('UC_PPP', '20');

?>