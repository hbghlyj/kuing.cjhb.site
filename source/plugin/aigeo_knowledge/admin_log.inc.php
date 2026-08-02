<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
if(isset($_GET['clear']) && $_GET['formhash']==FORMHASH){
    DB::delete('aigeo_knowledge_search_log', '1');
    dheader('Location: '.aigeo_k_admin_url('admin_log'));
}
$clearUrl=aigeo_k_admin_url('admin_log','&clear=1&formhash='.FORMHASH);
$rawRows=DB::fetch_all("SELECT * FROM %t ORDER BY created_at DESC LIMIT 100", array('aigeo_knowledge_search_log'));
$tableHtml=''; if(!$rawRows){ $tableHtml=aigeo_empty('暂无搜索日志'); } else { $tableHtml='<table class="aigeo-table">'.aigeo_th(array('ID','UID','关键词','命中','来源','时间')); foreach($rawRows as $row){ $tableHtml.=aigeo_row(array('#'.intval($row['id']),intval($row['uid']),aigeo_html($row['keyword']),aigeo_html($row['matched_ids']),aigeo_html($row['source']),$row['created_at']?dgmdate($row['created_at'],'Y-m-d H:i'):'--')); } $tableHtml.='</table>'; }
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/log');