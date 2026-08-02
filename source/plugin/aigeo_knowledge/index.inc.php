<?php


if(!defined('IN_DISCUZ')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
aigeo_k_maybe_cleanup();
$frontAccessMode=aigeo_k_front_access_mode();
if($frontAccessMode==='closed') showmessage('资料库前台暂未开放');
if($frontAccessMode==='login' && empty($_G['uid'])) showmessage('请登录后访问资料库','member.php?mod=logging&action=login');
$action=isset($_GET['action'])?trim($_GET['action']):'index';
$keyword=isset($_GET['keyword'])?trim($_GET['keyword']):'';
$page=max(1,isset($_GET['page'])?intval($_GET['page']):1);
$brandLogoUrlSafe='source/plugin/aigeo_knowledge/static/images/aigeo-logo-mark.svg';
$pageTitle=aigeo_k_page_title();
$pageSlogan=aigeo_k_page_slogan();
$from=isset($_GET['from'])?trim((string)$_GET['from']):'';
$sid=isset($_GET['sid'])?intval($_GET['sid']):0;
$fromService=$from==='service';
$contextExtra=($fromService?'&from=service':'').($sid?'&sid='.$sid:'');
$serviceUrl='plugin.php?id=aigeo_service'.($sid?'&sid='.$sid:'');
$knowledgeUrl='plugin.php?id=aigeo_knowledge'.$contextExtra;
$feedbackUrl='plugin.php?id=aigeo_feedback';
$tabsHtml='<div class="aigeo-tabs"><a href="'.aigeo_url($serviceUrl).'">智能客服</a><a class="current" href="'.aigeo_url($knowledgeUrl).'">资料库</a><a href="'.aigeo_url($feedbackUrl).'">意见反馈</a></div>';
$hiddenHtml=($fromService?'<input type="hidden" name="from" value="service">':'').($sid?'<input type="hidden" name="sid" value="'.$sid.'">':'');
$useModelShell=aigeo_k_use_model_navigation() && aigeo_k_model_shell_available();
if($useModelShell){
    require_once DISCUZ_ROOT.'source/plugin/aigeo_model/libs/product_shell.php';
    $shellContext=aigeomodel_product_shell_context('knowledge');
    extract($shellContext);
}
if($action=='detail'){
    $id=isset($_GET['kid']) ? intval($_GET['kid']) : (isset($_GET['itemid']) ? intval($_GET['itemid']) : 0);
    $item=DB::fetch_first("SELECT * FROM %t WHERE id=%d AND status='published' AND public_access=1", array('aigeo_knowledge_item',$id));
    if(!$item) showmessage('资料不存在或未公开','plugin.php?id=aigeo_knowledge');
    DB::update('aigeo_knowledge_item', array('views'=>$item['views']+1), array('id'=>$id));
    $chunks=DB::fetch_all("SELECT * FROM %t WHERE item_id=%d ORDER BY displayorder ASC,id ASC", array('aigeo_knowledge_chunk',$id));
    $itemTitle=dhtmlspecialchars($item['title']);
    $itemSummary=dhtmlspecialchars($item['summary']);
    $itemType=dhtmlspecialchars(aigeo_k_type_label($item['type']));
    $itemDomain=dhtmlspecialchars(aigeo_k_domain_label($item['domain']));
    $itemUpdated=dgmdate($item['updated_at'] ? $item['updated_at'] : $item['created_at'], 'Y-m-d H:i');
    $chunkHtml='';
    $tocHtml='';
    $tocRows=array();
    foreach($chunks as $c){
        $heading=trim((string)$c['heading']);
        $level=aigeo_k_chunk_heading_level($c['content'], $heading);
        $content=aigeo_k_strip_chunk_heading($c['content'], $heading);
        $bodyHtml=aigeo_k_render_markdown($content);
        $anchor='aigeo-doc-sec-'.intval($c['id']);
        if(trim(strip_tags($bodyHtml))==='') continue;
        $levelClass=' level-'.$level;
        $chunkHtml.='<section class="aigeo-doc-section'.$levelClass.'" id="'.$anchor.'"><h2>'.aigeo_html($heading?$heading:'正文').'</h2><div class="aigeo-doc-body">'.$bodyHtml.'</div></section>';
        $tocRows[]='<a class="level-'.$level.'" href="#'.$anchor.'">'.aigeo_html($heading?$heading:'正文').'</a>';
    }
    if(!$chunkHtml && trim((string)$item['content'])!==''){
        $chunkHtml='<section class="aigeo-doc-section"><div class="aigeo-doc-body">'.aigeo_k_render_markdown($item['content']).'</div></section>';
    }
    if(count($tocRows)>1) $tocHtml='<aside class="aigeo-doc-toc"><div class="aigeo-doc-toc-title">目录</div>'.implode('', $tocRows).'</aside>';
    if(!$chunkHtml) $chunkHtml=aigeo_empty('暂无正文内容');
    $returnUrl=$knowledgeUrl.($keyword!==''?'&keyword='.rawurlencode($keyword):'').($page>1?'&page='.$page:'');
    include template('aigeo_knowledge:touch/detail');
    exit;
}
$perpage=aigeo_k_page_size();
$start=($page-1)*$perpage;
$multipage='';
if($keyword===''){
    $total=intval(DB::result_first("SELECT COUNT(*) FROM %t WHERE status='published' AND public_access=1", array('aigeo_knowledge_item')));
    $items=DB::fetch_all("SELECT * FROM %t WHERE status='published' AND public_access=1 ORDER BY FIELD(priority,'high','normal','low'), updated_at DESC LIMIT %d,%d", array('aigeo_knowledge_item',$start,$perpage));
} else {
    $allItems=aigeo_k_search($keyword,500,0,0,500);
    $total=count($allItems);
    $items=array_slice($allItems,$start,$perpage);
}
$multiUrl='plugin.php?id=aigeo_knowledge'.$contextExtra.($keyword!==''?'&keyword='.rawurlencode($keyword):'');
$multipage=multi($total,$perpage,$page,$multiUrl);
if($keyword!==''){
    $ids=array(); foreach($items as $x){ $ids[]=intval($x['id']); }
    aigeo_k_log_search($keyword,$ids,'front');
}
$listHtml='';
foreach($items as $it){ $url='plugin.php?id=aigeo_knowledge&action=detail&kid='.intval($it['id']).$contextExtra.($keyword!==''?'&keyword='.rawurlencode($keyword):'').($page>1?'&page='.$page:''); $summary=$it['summary']?$it['summary']:cutstr(strip_tags($it['content']),160,''); $listHtml.='<div class="aigeo-k-item"><div class="aigeo-k-title"><a class="aigeo-link" href="'.aigeo_url($url).'">'.aigeo_html($it['title']).'</a></div><div>'.aigeo_badge(aigeo_k_domain_label($it['domain'])).' '.aigeo_badge(aigeo_k_type_label($it['type'])).'</div><div class="aigeo-desc">'.aigeo_html($summary).'</div></div>'; }
if(!$listHtml) $listHtml=aigeo_empty('暂无公开资料');
$keywordSafe=dhtmlspecialchars($keyword);
include template('aigeo_knowledge:touch/index');
