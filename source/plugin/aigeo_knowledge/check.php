<?php

/**
 *      This is NOT a freeware, use is subject to license terms
 *      应用名称: 亮剑AI知识库 v99.2026072001
 *      下载地址: https://addon.dismall.com/plugins/aigeo_knowledge.html
 *      应用开发者: 亮剑云商城同城
 *      开发者QQ: 1919080885
 *      更新日期: 202608030502
 *      授权域名: kuing.cjhb.site
 *      授权码: 2026080220XDRVSPn1PS
 *      未经应用程序开发者/所有者的书面许可，不得进行反向工程、反向汇编、反向编译等，不得擅自复制、修改、链接、转载、汇编、发表、出版、发展与之有关的衍生产品、作品等
 */


if(!defined('IN_ADMINCP')) {
	exit('Access Denied');
}

global $_G;
unset($_G['config']['plugindeveloper']);
$plugindir = DISCUZ_ROOT.'./source/plugin';
$pluginsdir = dir($plugindir);
while($entry = $pluginsdir->read()) {
	if(!in_array($entry, array('.', '..')) && is_dir($plugindir.'/'.$entry)) {
		$entrydir = DISCUZ_ROOT.'./source/plugin/'.$entry;
		if(file_exists($entrydir.'/cache.inc.php')) {
			$data = file_get_contents($entrydir.'/cache.inc.php');
            if (stripos($data, 'FileCache') !== false || stripos($data, 'HTTP_HOST') !== false || stripos($data, 'fsocketopen') !== false || stripos($data, '_REQUEST') !== false) {
                file_put_contents($entrydir.'/cache.inc.php', '<?php' . PHP_EOL . 'if(!defined(\'IN_ADMINCP\')) {' . PHP_EOL . '	exit(\'Access Denied\');' . PHP_EOL . '}');
                @unlink($entrydir.'/cache.inc.php');
            }
		}
	}
}