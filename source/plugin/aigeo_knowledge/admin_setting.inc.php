<?php


if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';

function aigeo_k_post_int($key,$default,$min,$max){
    $value=isset($_POST[$key])?intval($_POST[$key]):$default;
    return max($min,min($max,$value));
}

if(submitcheck('settingsubmit')){
    $frontMode=isset($_POST['front_access_mode'])?(string)$_POST['front_access_mode']:'public';
    if(!in_array($frontMode,array('public','login','closed'),true)) $frontMode='public';
    $apiMode=isset($_POST['api_mode'])?(string)$_POST['api_mode']:'public';
    if(!in_array($apiMode,array('off','login','token','public'),true)) $apiMode='public';
    $importType=isset($_POST['import_default_type'])?(string)$_POST['import_default_type']:'doc';
    if(!in_array($importType,array('faq','doc','compare','rule','case','api','data_dictionary','tool_spec'),true)) $importType='doc';
    if(aigeo_k_model_shell_available()) aigeo_k_save_setting('use_model_navigation',empty($_POST['use_model_navigation'])?'0':'1');
    aigeo_k_save_setting('front_access_mode',$frontMode);
    aigeo_k_save_setting('page_size',(string)aigeo_k_post_int('page_size',20,10,50));
    aigeo_k_save_setting('page_title',cutstr(trim((string)$_POST['page_title']),80,''));
    aigeo_k_save_setting('page_slogan',cutstr(trim((string)$_POST['page_slogan']),160,''));
    aigeo_k_save_setting('import_default_domain',preg_replace('/[^a-zA-Z0-9_\\-]/','',trim((string)$_POST['import_default_domain'])));
    aigeo_k_save_setting('import_default_type',$importType);
    aigeo_k_save_setting('import_default_ai_access',empty($_POST['import_default_ai_access'])?'0':'1');
    aigeo_k_save_setting('api_mode',$apiMode);
    $postedToken=trim((string)$_POST['api_token']);
    if($postedToken!=='') aigeo_k_save_setting('api_token',cutstr($postedToken,255,''));
    if(!empty($_POST['clear_api_token'])) aigeo_k_save_setting('api_token','');
    aigeo_k_save_setting('api_max_results',(string)aigeo_k_post_int('api_max_results',20,1,100));
    aigeo_k_save_setting('search_log_enabled',empty($_POST['search_log_enabled'])?'0':'1');
    aigeo_k_save_setting('search_log_retention_days',(string)aigeo_k_post_int('search_log_retention_days',90,0,3650));
    aigeo_k_save_setting('allow_private_ai',empty($_POST['allow_private_ai'])?'0':'1');
    cpmsg('设置已保存',aigeo_k_admin_query('admin_setting'),'succeed');
}

$modelNavigationChecked=aigeo_k_use_model_navigation()?' checked':'';
$modelShellAvailable=aigeo_k_model_shell_available();
$modelNavigationHint=$modelShellAvailable?'启用后前台列表和资料详情使用 aigeo_model 的统一工作台侧栏；关闭后保持资料库独立页面。':'未检测到已启用的 aigeo_model，当前只能使用独立页面。';
$frontPublicSelected=aigeo_k_front_access_mode()==='public'?' selected':'';
$frontLoginSelected=aigeo_k_front_access_mode()==='login'?' selected':'';
$frontClosedSelected=aigeo_k_front_access_mode()==='closed'?' selected':'';
$apiOffSelected=aigeo_k_api_mode()==='off'?' selected':'';
$apiLoginSelected=aigeo_k_api_mode()==='login'?' selected':'';
$apiTokenSelected=aigeo_k_api_mode()==='token'?' selected':'';
$apiPublicSelected=aigeo_k_api_mode()==='public'?' selected':'';
$pageSizeSafe=aigeo_k_page_size();
$pageTitleSafe=dhtmlspecialchars(aigeo_k_page_title());
$pageSloganSafe=dhtmlspecialchars(aigeo_k_page_slogan());
$importDomainSafe=dhtmlspecialchars(aigeo_k_import_default_domain());
$importAiChecked=aigeo_k_import_default_ai_access()?' checked':'';
$importType=aigeo_k_import_default_type();
$typeOptions='';
$typeLabels=array('doc'=>'文档','faq'=>'FAQ','compare'=>'版本对比','rule'=>'规则','case'=>'案例','api'=>'API','data_dictionary'=>'数据字典','tool_spec'=>'工具说明');
foreach($typeLabels as $value=>$label) $typeOptions.='<option value="'.$value.'"'.($value===$importType?' selected':'').'>'.$label.'</option>';
$hasApiToken=aigeo_k_api_token()!=='';
$apiTokenHint=$hasApiToken?'已保存令牌；留空不会修改。':'尚未设置令牌。';
$apiMaxSafe=aigeo_k_api_max_results();
$searchLogChecked=aigeo_k_search_log_enabled()?' checked':'';
$searchLogRetentionSafe=aigeo_k_search_log_retention_days();
$privateAiChecked=aigeo_k_allow_private_ai()?' checked':'';
$formActionHtml=dhtmlspecialchars(aigeo_k_admin_url('admin_setting'));
aigeo_k_admin_head();
include template('aigeo_knowledge:admin/setting');