<?php

/**
 *      This is NOT a freeware, use is subject to license terms
 *      Ӧ������: ��ֹ�ظ��������� ��ʽ�� 1.1
 *      ���ص�ַ: https://addon.dismall.com/plugins/zhi200_nodup.html
 *      Ӧ�ÿ�����: Column893s
 *      ������QQ: 370961342
 *      ��������: 202505140400
 *      ��Ȩ����: kuing.cjhb.site
 *      ��Ȩ��: 2025051320zO6j5j0OFG
 *      δ��Ӧ�ó��򿪷���/�����ߵ�������ɣ����ý��з��򹤳̡������ࡢ�������ȣ��������Ը��ơ��޸ġ����ӡ�ת�ء���ࡢ�������桢��չ��֮�йص�������Ʒ����Ʒ��
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