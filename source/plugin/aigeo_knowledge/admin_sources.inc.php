<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
$rawRows=DB::fetch_all("SELECT source_domain,source_module,source_type,COUNT(*) total FROM %t GROUP BY source_domain,source_module,source_type ORDER BY total DESC", array('aigeo_knowledge_item'));
$tableHtml=''; if(!$rawRows){ $tableHtml=aigeo_empty('暂无来源数据'); } else { $tableHtml='<table class="aigeo-table">'.aigeo_th(array('业务域','模块','来源类型','资料数')); foreach($rawRows as $row){ $tableHtml.=aigeo_row(array(aigeo_html($row['source_domain']!==''?aigeo_k_domain_label($row['source_domain']):'--'),aigeo_html($row['source_module']!==''?$row['source_module']:'--'),aigeo_html($row['source_type']!==''?$row['source_type']:'--'),intval($row['total']))); } $tableHtml.='</table>'; }
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/sources');