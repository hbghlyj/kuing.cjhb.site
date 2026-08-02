<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
$op=isset($_GET['op'])?trim($_GET['op']):'';
$id=isset($_GET['id'])?intval($_GET['id']):0;
if($op && $id && $_GET['formhash']==FORMHASH){
    if($op=='delete'){
        DB::delete('aigeo_knowledge_item',array('id'=>$id));
        DB::delete('aigeo_knowledge_chunk',array('item_id'=>$id));
        DB::delete('aigeo_knowledge_source',array('item_id'=>$id));
        cpmsg('已删除资料及关联片段',aigeo_k_admin_query('admin_list'),'succeed');
    } elseif(in_array($op,array('publish','draft','private','disable'))){
        $status=array('publish'=>'published','draft'=>'draft','private'=>'private','disable'=>'disabled');
        $data=array('status'=>$status[$op],'updated_at'=>TIMESTAMP);
        if($op=='publish') $data['public_access']=1;
        DB::update('aigeo_knowledge_item',$data,array('id'=>$id));
        cpmsg('状态已更新',aigeo_k_admin_query('admin_list'),'succeed');
    }
}
if(submitcheck('itemsave')){
    $id=intval($_POST['id']);
    $data=array(
        'title'=>$_POST['title'],'slug'=>$_POST['slug'],'domain'=>$_POST['domain'],'module'=>$_POST['module'],'type'=>$_POST['type'],'category'=>$_POST['category'],'tags'=>$_POST['tags'],'summary'=>$_POST['summary'],'content'=>$_POST['content'],'keywords'=>$_POST['keywords'],'version_scope'=>$_POST['version_scope'],'source_domain'=>$_POST['source_domain'],'source_module'=>$_POST['source_module'],'source_type'=>$_POST['source_type'],'source_table'=>$_POST['source_table'],'source_id'=>$_POST['source_id'],'source_sub_id'=>$_POST['source_sub_id'],'source_title'=>$_POST['source_title'],'source_file'=>$_POST['source_file'],'source_url'=>$_POST['source_url'],'status'=>$_POST['status'],'ai_access'=>isset($_POST['ai_access'])?1:0,'public_access'=>isset($_POST['public_access'])?1:0,'priority'=>$_POST['priority']
    );
    $newid=aigeo_k_save_item($data,$id);
    aigeo_k_rebuild_chunks($newid,$data['content'],$data['domain'],$data['module'],$data['type']);
    cpmsg('已保存',aigeo_k_admin_query('admin_list','&op=edit&id='.$newid),'succeed');
}
$editing=false; $itemRaw=array();
if($op=='edit'){
    $editing=true;
    if($id) $itemRaw=DB::fetch_first("SELECT * FROM %t WHERE id=%d",array('aigeo_knowledge_item',$id));
    if(!$itemRaw) $itemRaw=array('id'=>0,'title'=>'','slug'=>'','domain'=>'common','module'=>'','type'=>'doc','category'=>'','tags'=>'','summary'=>'','content'=>'','keywords'=>'','version_scope'=>'','source_domain'=>'','source_module'=>'','source_type'=>'manual','source_table'=>'','source_id'=>'','source_sub_id'=>'','source_title'=>'','source_file'=>'','source_url'=>'','status'=>'draft','ai_access'=>1,'public_access'=>0,'priority'=>'normal');
    foreach($itemRaw as $k=>$v){ ${'item_'.$k}=dhtmlspecialchars((string)$v); }
    $item_id=intval($itemRaw['id']);
    $item_ai_checked=!empty($itemRaw['ai_access'])?' checked':'';
    $item_public_checked=!empty($itemRaw['public_access'])?' checked':'';
    $item_type_doc=$itemRaw['type']=='doc'?' selected':'';
    $item_type_faq=$itemRaw['type']=='faq'?' selected':'';
    $item_type_compare=$itemRaw['type']=='compare'?' selected':'';
    $item_type_rule=$itemRaw['type']=='rule'?' selected':'';
    $item_type_case=$itemRaw['type']=='case'?' selected':'';
    $item_type_api=$itemRaw['type']=='api'?' selected':'';
    $item_type_data=$itemRaw['type']=='data_dictionary'?' selected':'';
    $item_type_tool=$itemRaw['type']=='tool_spec'?' selected':'';
    $item_status_draft=$itemRaw['status']=='draft'?' selected':'';
    $item_status_pending=$itemRaw['status']=='pending'?' selected':'';
    $item_status_published=$itemRaw['status']=='published'?' selected':'';
    $item_status_private=$itemRaw['status']=='private'?' selected':'';
    $item_status_disabled=$itemRaw['status']=='disabled'?' selected':'';
    $item_priority_high=$itemRaw['priority']=='high'?' selected':'';
    $item_priority_normal=$itemRaw['priority']=='normal'?' selected':'';
    $item_priority_low=$itemRaw['priority']=='low'?' selected':'';
}
$keyword=isset($_GET['keyword'])?trim($_GET['keyword']):'';
$status=isset($_GET['status'])?trim($_GET['status']):'';
$type=isset($_GET['type'])?trim($_GET['type']):'';
$where='1'; $params=array('aigeo_knowledge_item');
if($keyword!==''){ $where.=' AND (title LIKE %s OR keywords LIKE %s OR summary LIKE %s)'; $like='%'.$keyword.'%'; array_push($params,$like,$like,$like); }
if($status!==''){ $where.=' AND status=%s'; $params[]=$status; }
if($type!==''){ $where.=' AND type=%s'; $params[]=$type; }
$rows=DB::fetch_all("SELECT * FROM %t WHERE $where ORDER BY updated_at DESC,id DESC LIMIT 80",$params);
$list=array();
foreach($rows as $it){
    $list[]=array(
        'id'=>intval($it['id']),'title'=>dhtmlspecialchars($it['title']),'domain'=>dhtmlspecialchars(aigeo_k_domain_label($it['domain'])),'type'=>dhtmlspecialchars(aigeo_k_type_label($it['type'])),'status'=>dhtmlspecialchars(aigeo_k_status_label($it['status'])),'status_raw'=>dhtmlspecialchars($it['status']),'ai'=>intval($it['ai_access']),'public'=>intval($it['public_access']),'time'=>$it['updated_at']?dgmdate($it['updated_at'],'Y-m-d H:i'):'--','edit_url'=>aigeo_k_admin_url('admin_list','&op=edit&id='.intval($it['id'])),'publish_url'=>aigeo_k_admin_url('admin_list','&op=publish&id='.intval($it['id']).'&formhash='.FORMHASH),'draft_url'=>aigeo_k_admin_url('admin_list','&op=draft&id='.intval($it['id']).'&formhash='.FORMHASH),'delete_url'=>aigeo_k_admin_url('admin_list','&op=delete&id='.intval($it['id']).'&formhash='.FORMHASH)
    );
}
$newUrl=aigeo_k_admin_url('admin_list','&op=edit');
$returnUrl=aigeo_k_admin_url('admin_list');
$importUrl=aigeo_k_admin_url('admin_import');
$keywordSafe=dhtmlspecialchars($keyword);
$status_draft=$status=='draft'?' selected':''; $status_pending=$status=='pending'?' selected':''; $status_published=$status=='published'?' selected':''; $status_private=$status=='private'?' selected':''; $type_faq=$type=='faq'?' selected':''; $type_doc=$type=='doc'?' selected':''; $type_compare=$type=='compare'?' selected':''; $type_rule=$type=='rule'?' selected':''; $type_tool=$type=='tool_spec'?' selected':''; $type_data=$type=='data_dictionary'?' selected':'';
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/list');