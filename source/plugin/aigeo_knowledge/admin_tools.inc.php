<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
$rawRows=DB::fetch_all("SELECT id,title,domain,module,status,updated_at FROM %t WHERE type=%s ORDER BY updated_at DESC,id DESC LIMIT 50", array('aigeo_knowledge_item','tool_spec'));
$tableHtml=''; if(!$rawRows){ $tableHtml=aigeo_empty('暂无工具说明'); } else { $tableHtml='<table class="aigeo-table">'.aigeo_th(array('ID','工具说明','业务域','模块','状态','更新时间')); foreach($rawRows as $row){ $edit=aigeo_k_admin_url('admin_list','&op=edit&id='.intval($row['id'])); $tableHtml.=aigeo_row(array('#'.intval($row['id']),'<a class="aigeo-link" href="'.aigeo_url($edit).'">'.aigeo_html($row['title']).'</a>',aigeo_html(aigeo_k_domain_label($row['domain'])),aigeo_html($row['module']!==''?$row['module']:'--'),aigeo_badge(aigeo_k_status_label($row['status'])),($row['updated_at']?dgmdate($row['updated_at'],'Y-m-d H:i'):'--'))); } $tableHtml.='</table>'; }
$newUrl=aigeo_k_admin_url('admin_list','&op=edit');
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/tools');