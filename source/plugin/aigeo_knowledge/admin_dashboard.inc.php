<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
$row=DB::fetch_first("SELECT COUNT(*) total,SUM(status='published') published,SUM(status IN('draft','pending')) draft,SUM(ai_access=1) ai_count,SUM(public_access=1) public_count FROM %t", array('aigeo_knowledge_item'));
$today=intval(DB::result_first("SELECT COUNT(*) FROM %t WHERE created_at>%d", array('aigeo_knowledge_search_log', strtotime('today'))));
$stats=array(array('资料总数',intval($row['total'])),array('已发布',intval($row['published'])),array('待整理',intval($row['draft'])),array('AI 可调用',intval($row['ai_count'])),array('前台公开',intval($row['public_count'])),array('今日搜索',$today));
$statsHtml=''; foreach($stats as $s){ $statsHtml.='<div class="aigeo-stat-card"><div class="aigeo-stat-label">'.aigeo_html($s[0]).'</div><div class="aigeo-stat-value">'.intval($s[1]).'</div></div>'; }
$recent=DB::fetch_all("SELECT * FROM %t ORDER BY updated_at DESC,id DESC LIMIT 10", array('aigeo_knowledge_item'));
$recentHtml=''; if(!$recent){ $recentHtml=aigeo_empty('暂无资料，建议先导入 Markdown 文档。'); } else { $recentHtml='<table class="aigeo-table">'.aigeo_th(array('ID','标题','业务域','类型','状态','更新时间')); foreach($recent as $it){ $url=aigeo_k_admin_url('admin_list','&op=edit&id='.intval($it['id'])); $recentHtml.=aigeo_row(array('#'.intval($it['id']),'<a class="aigeo-link" href="'.aigeo_url($url).'">'.aigeo_html($it['title']).'</a>',aigeo_html(aigeo_k_domain_label($it['domain'])),aigeo_html(aigeo_k_type_label($it['type'])),aigeo_badge(aigeo_k_status_label($it['status'])),dgmdate($it['updated_at']?$it['updated_at']:$it['created_at'],'Y-m-d H:i'))); } $recentHtml.='</table>'; }
$importUrl=aigeo_k_admin_url('admin_import');
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/dashboard');