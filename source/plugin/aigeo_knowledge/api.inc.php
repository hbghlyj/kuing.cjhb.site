<?php

if(!defined('IN_DISCUZ')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
aigeo_k_maybe_cleanup();
$apiMode=aigeo_k_api_mode();
if($apiMode==='off') aigeo_k_json(array('ok'=>false,'message'=>'api_disabled'));
if($apiMode==='login' && empty($_G['uid'])) aigeo_k_json(array('ok'=>false,'message'=>'login_required'));
$tokenValid=false;
if($apiMode==='token'){
    $expected=aigeo_k_api_token();
    $provided='';
    if(!empty($_SERVER['HTTP_X_AIGEO_TOKEN'])) $provided=trim((string)$_SERVER['HTTP_X_AIGEO_TOKEN']);
    elseif(isset($_GET['token'])) $provided=trim((string)$_GET['token']);
    if($expected!=='' && $provided!=='' && function_exists('hash_equals')) $tokenValid=hash_equals($expected,$provided);
    elseif($expected!=='' && $provided!=='' ) $tokenValid=$expected===$provided;
    if(!$tokenValid) aigeo_k_json(array('ok'=>false,'message'=>'invalid_token'));
}
$action=isset($_GET['action'])?trim($_GET['action']):'search';
if($action=='search'){
    $keyword=isset($_GET['keyword'])?trim((string)$_GET['keyword']):'';
    $forAi=($apiMode==='token' && isset($_GET['for_ai']))?intval($_GET['for_ai']):0;
    $maxResults=aigeo_k_api_max_results();
    $items=aigeo_k_search($keyword,isset($_GET['limit'])?intval($_GET['limit']):10,$forAi,0,$maxResults);
    $out=array();
    foreach($items as $it){
        $one=array('id'=>intval($it['id']),'title'=>$it['title'],'summary'=>$it['summary'],'type'=>$it['type'],'domain'=>$it['domain'],'module'=>$it['module'],'matched_heading'=>isset($it['matched_heading'])?$it['matched_heading']:'','matched_excerpt'=>isset($it['matched_excerpt'])?$it['matched_excerpt']:'','updated_at'=>intval($it['updated_at']));
        if($forAi){ $one['content']=cutstr(strip_tags($it['content']),1200,''); $one['source_title']=$it['source_title']; $one['source_file']=$it['source_file']; }
        $out[]=$one;
    }
    aigeo_k_log_search($keyword,array_map(function($x){return $x['id'];},$out),$forAi?'ai':'api');
    aigeo_k_json(array('ok'=>true,'data'=>array('items'=>$out)));
}
if($action=='detail'){
    $id=0;
    if(isset($_GET['kid'])) $id=intval($_GET['kid']);
    elseif(isset($_GET['itemid'])) $id=intval($_GET['itemid']);
    elseif(isset($_GET['id']) && is_numeric($_GET['id'])) $id=intval($_GET['id']);
    $it=DB::fetch_first("SELECT * FROM %t WHERE id=%d AND status='published' AND public_access=1",array('aigeo_knowledge_item',$id));
    if(!$it) aigeo_k_json(array('ok'=>false,'message'=>'not_found'));
    aigeo_k_json(array('ok'=>true,'data'=>array('id'=>intval($it['id']),'title'=>$it['title'],'summary'=>$it['summary'],'content'=>$it['content'],'type'=>$it['type'],'domain'=>$it['domain'],'module'=>$it['module'],'source_title'=>$it['source_title'],'updated_at'=>intval($it['updated_at']))));
}
aigeo_k_json(array('ok'=>false,'message'=>'unknown_action'));