<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';
function aigeo_k_read_md_upload(){
    $md=''; $fileName='';
    if(!empty($_FILES['mdfile']['tmp_name']) && is_uploaded_file($_FILES['mdfile']['tmp_name'])){
        $fileName=$_FILES['mdfile']['name'];
        $ext=strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
        if(!in_array($ext,array('md','markdown'))) cpmsg('只允许上传 .md 或 .markdown 文件','','error');
        if(filesize($_FILES['mdfile']['tmp_name'])>5*1024*1024) cpmsg('Markdown 文件不能超过 5MB','','error');
        $md=file_get_contents($_FILES['mdfile']['tmp_name']);
    }
    if(trim($md)==='') $md=(string)$_POST['markdown'];
    if(strlen($md)>5*1024*1024) cpmsg('Markdown 内容不能超过 5MB','','error');
    if(trim($md)==='') cpmsg('请上传或粘贴 Markdown 内容','','error');
    return array($md,$fileName);
}
$preview=false; $previewRows=array(); $metaSafe=array(); $bodySafe=''; $markdownSafe=''; $fileNameSafe='';
if(submitcheck('previewsubmit')){
    list($md,$fileName)=aigeo_k_read_md_upload();
    list($meta,$body)=aigeo_k_parse_front_matter($md);
    $title=isset($meta['title'])?$meta['title']:'';
    if($title==='' && $fileName!=='') $title=preg_replace('/\.(md|markdown)$/i','',$fileName);
    if($title==='' && preg_match('/^#\s+(.+)$/m',$body,$m)) $title=trim($m[1]);
    if($title==='' && preg_match('/^\s*(\S.{2,80})$/m',$body,$m)) $title=trim($m[1]);
    if($title==='') $title='未命名文档';
    $domain=isset($meta['domain'])?$meta['domain']:aigeo_k_import_default_domain();
    $module=isset($meta['module'])?$meta['module']:'doc';
    $type=isset($meta['type'])?$meta['type']:aigeo_k_import_default_type();
    $chunks=aigeo_k_chunks($body);
    $preview=true;
    $metaSafe=array('title'=>dhtmlspecialchars($title),'domain'=>dhtmlspecialchars($domain),'module'=>dhtmlspecialchars($module),'type'=>dhtmlspecialchars(aigeo_k_type_label($type)),'summary'=>dhtmlspecialchars(isset($meta['summary'])?$meta['summary']:aigeo_k_extract_summary($body)),'chunks'=>count($chunks));
    foreach(array_slice($chunks,0,8) as $c){ $previewRows[]=array('heading'=>dhtmlspecialchars($c['heading']),'length'=>strlen($c['content'])); }
    $markdownSafe=dhtmlspecialchars(base64_encode($md)); $fileNameSafe=dhtmlspecialchars($fileName);
} elseif(submitcheck('importsubmit')){
    $md=base64_decode((string)$_POST['markdown_payload']); $fileName=(string)$_POST['source_file'];
    if(trim($md)==='') cpmsg('导入内容为空',aigeo_k_admin_query('admin_import'),'error');
    list($meta,$body)=aigeo_k_parse_front_matter($md);
    $title=isset($meta['title'])?$meta['title']:'';
    if($title==='' && $fileName!=='') $title=preg_replace('/\.(md|markdown)$/i','',$fileName);
    if($title==='' && preg_match('/^#\s+(.+)$/m',$body,$m)) $title=trim($m[1]);
    if($title==='' && preg_match('/^\s*(\S.{2,80})$/m',$body,$m)) $title=trim($m[1]);
    if($title==='') $title='未命名文档';
    $defaultDomain=aigeo_k_import_default_domain();
    $defaultType=aigeo_k_import_default_type();
    $data=array('title'=>$title,'slug'=>isset($meta['slug'])?$meta['slug']:'','domain'=>isset($meta['domain'])?$meta['domain']:$defaultDomain,'module'=>isset($meta['module'])?$meta['module']:'doc','type'=>isset($meta['type'])?$meta['type']:$defaultType,'category'=>isset($meta['category'])?$meta['category']:'','tags'=>isset($meta['tags'])?$meta['tags']:'','summary'=>isset($meta['summary'])?$meta['summary']:aigeo_k_extract_summary($body),'content'=>$body,'keywords'=>isset($meta['keywords'])?$meta['keywords']:'','version_scope'=>isset($meta['version_scope'])?$meta['version_scope']:'','source_domain'=>isset($meta['domain'])?$meta['domain']:$defaultDomain,'source_module'=>isset($meta['module'])?$meta['module']:'doc','source_type'=>'markdown','source_table'=>'','source_id'=>'','source_sub_id'=>'','source_title'=>$title,'source_file'=>$fileName,'source_url'=>'','status'=>'pending','ai_access'=>aigeo_k_import_default_ai_access()?1:0,'public_access'=>0,'priority'=>isset($meta['priority'])?$meta['priority']:'normal');
    $id=aigeo_k_save_item($data,0); aigeo_k_rebuild_chunks($id,$body,$data['domain'],$data['module'],$data['type']);
    DB::insert('aigeo_knowledge_source',array('item_id'=>$id,'source_domain'=>$data['source_domain'],'source_module'=>$data['source_module'],'source_type'=>'markdown','source_title'=>$title,'source_url'=>'','raw_content'=>$md,'normalized_content'=>$body,'created_at'=>TIMESTAMP));
    cpmsg('Markdown 已导入为待审核资料',aigeo_k_admin_query('admin_list','&op=edit&id='.$id),'succeed');
}
$importUrl=aigeo_k_admin_url('admin_import');
aigeo_k_admin_head(); include template('aigeo_knowledge:admin/import');