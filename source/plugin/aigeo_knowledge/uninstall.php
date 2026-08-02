<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');

$requestUrl = preg_replace('/(^|&)step=[^&]*/', '', $_SERVER['QUERY_STRING']);
$requestUrl = trim(preg_replace('/&+/', '&', $requestUrl), '&');

$_G['lang']['admincp']['ok'] = '保留插件数据';
$_G['lang']['admincp']['cancel'] = '删除插件数据';

switch(isset($_GET['step']) ? $_GET['step'] : '') {
    case 'deletesql':
        DB::query("DROP TABLE IF EXISTS ".DB::table('aigeo_knowledge_item'), 'SILENT');
        DB::query("DROP TABLE IF EXISTS ".DB::table('aigeo_knowledge_chunk'), 'SILENT');
        DB::query("DROP TABLE IF EXISTS ".DB::table('aigeo_knowledge_source'), 'SILENT');
        DB::query("DROP TABLE IF EXISTS ".DB::table('aigeo_knowledge_search_log'), 'SILENT');
        $finish = true;
        break;

    case 'ok':
        $finish = true;
        break;

    default:
        $checkPlugin = C::t('common_plugin')->fetch($pluginid);
        if(empty($checkPlugin) && !empty($plugin)) {
            C::t('common_plugin')->insert($plugin);
        }

        $keepUrl = $requestUrl . ($requestUrl ? '&' : '') . 'step=ok';
        $deleteUrl = $requestUrl . ($requestUrl ? '&' : '') . 'step=deletesql';
        cpmsg(
            '<img src="static/image/common/info.gif" /><br /><br />'
            . '<b>请选择卸载后是否保留知识库插件数据？</b><br /><br />'
            . '如果您希望保留插件数据，请点击“保留插件数据”；如果点击“删除插件数据”，将删除知识库资料、正文分块、来源记录和搜索日志数据表。<br /><br />'
            . '<b>注意：选择“删除插件数据”后，资料库内容、分块正文、来源记录和搜索日志将永久丢失且不可恢复。</b><br /><br />',
            $keepUrl,
            'form',
            array(),
            '',
            true,
            ADMINSCRIPT . '?' . $deleteUrl
        );
        break;
}