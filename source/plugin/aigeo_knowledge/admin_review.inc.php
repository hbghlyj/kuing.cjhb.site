<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
$rows=DB::fetch_all("SELECT * FROM %t WHERE status IN('draft','pending') ORDER BY updated_at DESC,id DESC LIMIT 50", array('aigeo_knowledge_item'));
$tableHtml=''; if(!$rows){ $tableHtml=aigeo_empty('暂无待审核资料'); } else { $tableHtml='<table class="aigeo-table">'.aigeo_th(array('ID','标题','类型','来源','更新时间','操作')); foreach($rows as $it){ $edit=aigeo_k_admin_url('admin_list','&op=edit&id='.intval($it['id'])); $tableHtml.=aigeo_row(array('#'.intval($it['id']),aigeo_html($it['title']),aigeo_html(aigeo_k_type_label($it['type'])),aigeo_html($it['source_type']),dgmdate($it['updated_at']?$it['updated_at']:$it['created_at'],'Y-m-d H:i'),'<a class="aigeo-link" href="'.aigeo_url($edit).'">审核编辑</a>')); } $tableHtml.='</table>'; }
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/review');