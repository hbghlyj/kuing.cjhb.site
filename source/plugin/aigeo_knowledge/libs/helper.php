<?php


if(!defined('IN_DISCUZ')) exit('Access Denied');

if(!function_exists('aigeo_html')) {
function aigeo_html($s){ return dhtmlspecialchars((string)$s); }
}
if(!function_exists('aigeo_url')) {
function aigeo_url($s){ return dhtmlspecialchars((string)$s); }
}
if(!function_exists('aigeo_badge')) {
function aigeo_badge($text,$type=''){ $cls=$type ? ' aigeo-badge-'.aigeo_html($type) : ''; return '<span class="aigeo-badge'.$cls.'">'.aigeo_html($text).'</span>'; }
}
if(!function_exists('aigeo_empty')) {
function aigeo_empty($text){ return '<div class="aigeo-empty">'.aigeo_html($text).'</div>'; }
}
if(!function_exists('aigeo_row')) {
function aigeo_row($cells){ $html='<tr>'; foreach($cells as $c){ $html.='<td>'.$c.'</td>'; } return $html.'</tr>'; }
}
if(!function_exists('aigeo_th')) {
function aigeo_th($cells){ $html='<tr>'; foreach($cells as $c){ $html.='<th>'.aigeo_html($c).'</th>'; } return $html.'</tr>'; }
}

function aigeo_k_static_url($path) { return 'source/plugin/aigeo_knowledge/' . ltrim($path, '/'); }
function aigeo_k_admin_head() { static $p=false; if($p) return; $p=true; echo '<link rel="stylesheet" href="'.dhtmlspecialchars(aigeo_k_static_url('static/css/aigeo-knowledge.css')).'?v=2026071901" />'; }
function aigeo_k_admin_query($pmod='admin_dashboard', $extra='') { $pluginid=intval(DB::result_first("SELECT pluginid FROM %t WHERE identifier=%s", array('common_plugin','aigeo_knowledge'))); $frame=isset($_GET['frame']) && $_GET['frame']==='no'?'&frame=no':''; return 'action=plugins&operation=config&do='.$pluginid.'&identifier=aigeo_knowledge&pmod='.$pmod.$extra.$frame; }
function aigeo_k_admin_url($pmod='admin_dashboard', $extra='') { return ADMINSCRIPT.'?'.aigeo_k_admin_query($pmod,$extra); }
function aigeo_k_pluginid(){ static $id=null; if($id===null) $id=intval(DB::result_first("SELECT pluginid FROM %t WHERE identifier=%s", array('common_plugin','aigeo_knowledge'))); return $id; }
function aigeo_k_setting($key,$default=''){ $pluginid=aigeo_k_pluginid(); if(!$pluginid) return $default; $value=DB::result_first("SELECT value FROM %t WHERE pluginid=%d AND variable=%s", array('common_pluginvar',$pluginid,$key)); return $value===null?$default:$value; }
function aigeo_k_save_setting($key,$value){ $pluginid=aigeo_k_pluginid(); if(!$pluginid) return false; $row=DB::fetch_first("SELECT pluginvarid FROM %t WHERE pluginid=%d AND variable=%s", array('common_pluginvar',$pluginid,$key)); $data=array('displayorder'=>-1,'title'=>'','description'=>'','type'=>'_hidden','value'=>(string)$value,'extra'=>''); if($row){ DB::update('common_pluginvar',$data,array('pluginvarid'=>intval($row['pluginvarid']))); } else { $data['pluginid']=$pluginid; $data['variable']=$key; DB::insert('common_pluginvar',$data); } return true; }
function aigeo_k_setting_int($key,$default,$min,$max){ return max($min,min($max,intval(aigeo_k_setting($key,(string)$default)))); }
function aigeo_k_front_access_mode(){ $v=aigeo_k_setting('front_access_mode','public'); return in_array($v,array('public','login','closed'),true)?$v:'public'; }
function aigeo_k_api_mode(){ $v=aigeo_k_setting('api_mode','public'); return in_array($v,array('off','login','token','public'),true)?$v:'public'; }
function aigeo_k_api_token(){ return trim((string)aigeo_k_setting('api_token','')); }
function aigeo_k_api_max_results(){ return aigeo_k_setting_int('api_max_results',20,1,100); }
function aigeo_k_search_log_enabled(){ return intval(aigeo_k_setting('search_log_enabled','1'))>0; }
function aigeo_k_search_log_retention_days(){ return aigeo_k_setting_int('search_log_retention_days',90,0,3650); }
function aigeo_k_allow_private_ai(){ return intval(aigeo_k_setting('allow_private_ai','0'))>0; }
function aigeo_k_page_size(){ return aigeo_k_setting_int('page_size',20,10,50); }
function aigeo_k_page_title(){ $v=trim((string)aigeo_k_setting('page_title','亮剑AI知识库')); if($v==='' || $v==='aigeo知识库') $v='亮剑AI知识库'; return cutstr($v,80,''); }
function aigeo_k_page_slogan(){ return cutstr(trim((string)aigeo_k_setting('page_slogan','让资料同时服务用户和 AI')),160,''); }
function aigeo_k_import_default_domain(){ $v=preg_replace('/[^a-zA-Z0-9_\\-]/','',trim((string)aigeo_k_setting('import_default_domain','discuz'))); return $v===''?'discuz':cutstr($v,32,''); }
function aigeo_k_import_default_type(){ $v=aigeo_k_setting('import_default_type','doc'); $allowed=array('faq','doc','compare','rule','case','api','data_dictionary','tool_spec'); return in_array($v,$allowed,true)?$v:'doc'; }
function aigeo_k_import_default_ai_access(){ return intval(aigeo_k_setting('import_default_ai_access','1'))>0; }
function aigeo_k_log_search($keyword,$ids,$source){
    global $_G;
    if(!aigeo_k_search_log_enabled()) return;
    DB::insert('aigeo_knowledge_search_log',array('uid'=>intval($_G['uid']),'keyword'=>cutstr((string)$keyword,255,''),'matched_ids'=>implode(',',array_map('intval',(array)$ids)),'source'=>$source,'created_at'=>TIMESTAMP));
}
function aigeo_k_maybe_cleanup(){
    $last=intval(aigeo_k_setting('cleanup_last_at','0'));
    if($last>TIMESTAMP-86400) return;
    $days=aigeo_k_search_log_retention_days();
    if($days>0) DB::query("DELETE FROM ".DB::table('aigeo_knowledge_search_log')." WHERE created_at<".intval(TIMESTAMP-$days*86400));
    aigeo_k_save_setting('cleanup_last_at',(string)TIMESTAMP);
}
function aigeo_k_use_model_navigation(){ return intval(aigeo_k_setting('use_model_navigation','0'))>0; }
function aigeo_k_model_shell_available(){ $shell=DISCUZ_ROOT.'source/plugin/aigeo_model/libs/product_shell.php'; if(!is_file($shell)) return false; $plugin=DB::fetch_first("SELECT available FROM %t WHERE identifier=%s", array('common_plugin','aigeo_model')); return $plugin && intval($plugin['available'])===1; }
function aigeo_k_status_label($s) { $map=array('draft'=>'草稿','pending'=>'待审核','published'=>'已发布','private'=>'内部','archived'=>'已归档','disabled'=>'已停用'); return isset($map[$s])?$map[$s]:'草稿'; }
function aigeo_k_type_label($t) { $map=array('faq'=>'FAQ','doc'=>'文档','compare'=>'版本对比','rule'=>'规则','snippet'=>'代码片段','case'=>'案例','api'=>'API','data_dictionary'=>'数据字典','tool_spec'=>'工具说明'); return isset($map[$t])?$map[$t]:'文档'; }
function aigeo_k_domain_label($d) { $map=array('discuz'=>'Discuz!','mall'=>'商城','local'=>'同城','plugin'=>'插件','operation'=>'运营','system'=>'系统','common'=>'通用'); return isset($map[$d])?$map[$d]:'通用'; }
function aigeo_k_clean($v,$len=0){ $v=trim((string)$v); if($len>0) $v=cutstr($v,$len,''); return $v; }
function aigeo_k_parse_front_matter($md) { $meta=array(); $body=$md; if(preg_match('/^---\s*\n(.*?)\n---\s*\n/s',$md,$m)){ $body=substr($md, strlen($m[0])); $lines=preg_split('/\r?\n/',$m[1]); $key=''; $mode=''; $buf=array(); $flush=function() use (&$meta,&$key,&$mode,&$buf){ if($key==='') return; if($mode==='array'){ $meta[$key]=implode(',', $buf); } elseif($mode==='block'){ $meta[$key]=trim(implode(' ', $buf)); } $key=''; $mode=''; $buf=array(); }; foreach($lines as $line){ if(preg_match('/^([a-zA-Z0-9_\-]+):\s*(.*)$/',$line,$mm)){ $flush(); $key=$mm[1]; $val=trim($mm[2]); if($val==='>-' || $val==='|' || $val==='>-'){ $mode='block'; $buf=array(); } elseif($val===''){ $mode='array'; $buf=array(); } else { $meta[$key]=trim($val," \t\"'"); $key=''; $mode=''; $buf=array(); } } elseif($mode==='array' && preg_match('/^\s*-\s*(.+)$/',$line,$mm)){ $buf[]=trim($mm[1]," \t\"'"); } elseif($mode==='block' && preg_match('/^\s+(.+)$/',$line,$mm)){ $buf[]=trim($mm[1]); } } $flush(); } return array($meta,$body); }
function aigeo_k_extract_summary($body){ $plain=trim(strip_tags(preg_replace('/```.*?```/s','',$body))); $plain=preg_replace('/[#>*`\-\[\]\(\)]/','',$plain); $plain=preg_replace('/\s+/',' ',$plain); return cutstr($plain,220,''); }
function aigeo_k_inline_markdown($text){
    $html=dhtmlspecialchars((string)$text);
    $html=preg_replace('/`([^`]+)`/','<code>$1</code>',$html);
    $html=preg_replace('/\*\*([^*]+)\*\*/','<strong>$1</strong>',$html);
    $html=preg_replace_callback('/https:\/\/[^\s<>"\']+/i', function($m){
        $url=rtrim($m[0], '.,;:!?)');
        $tail=substr($m[0], strlen($url));
        return '<a class="aigeo-link" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url.'</a>'.$tail;
    }, $html);
    return $html;
}
function aigeo_k_is_table_separator($line){
    return preg_match('/^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/', (string)$line);
}
function aigeo_k_table_cells($line){
    $line=trim((string)$line);
    $line=trim($line, '|');
    $cells=explode('|', $line);
    foreach($cells as $k=>$v){ $cells[$k]=trim($v); }
    return $cells;
}
function aigeo_k_render_table($rows){
    if(count($rows)<2) return '';
    $head=aigeo_k_table_cells($rows[0]);
    $html='<div class="aigeo-doc-table"><table><thead><tr>';
    foreach($head as $cell){ $html.='<th>'.aigeo_k_inline_markdown($cell).'</th>'; }
    $html.='</tr></thead><tbody>';
    for($i=2;$i<count($rows);$i++){
        $cells=aigeo_k_table_cells($rows[$i]);
        $html.='<tr>';
        for($j=0;$j<count($head);$j++){
            $html.='<td>'.aigeo_k_inline_markdown(isset($cells[$j])?$cells[$j]:'').'</td>';
        }
        $html.='</tr>';
    }
    $html.='</tbody></table></div>';
    return $html;
}
function aigeo_k_render_markdown($text){
    $text=str_replace(array("\r\n","\r"), "\n", (string)$text);
    $text=preg_replace('/<!--.*?-->/s','',$text);
    $text=preg_replace('/^---\s*\n.*?\n---\s*\n/s','',$text);
    $lines=preg_split('/\n/',$text);
    $html='';
    $paragraph=array();
    $list=array();
    $listType='';
    $flushParagraph=function() use (&$html,&$paragraph){
        if(!$paragraph) return;
        $body=trim(implode("\n",$paragraph));
        if($body!=='') $html.='<p>'.aigeo_k_inline_markdown(preg_replace('/\s*\n\s*/',' ',$body)).'</p>';
        $paragraph=array();
    };
    $flushList=function() use (&$html,&$list,&$listType){
        if(!$list) return;
        $tag=$listType==='ol'?'ol':'ul';
        $html.='<'.$tag.'>';
        foreach($list as $item){ $html.='<li>'.aigeo_k_inline_markdown($item).'</li>'; }
        $html.='</'.$tag.'>';
        $list=array();
        $listType='';
    };
    $count=count($lines);
    for($i=0;$i<$count;$i++){
        $line=rtrim($lines[$i]);
        if(preg_match('/^```/', trim($line))){
            $flushParagraph(); $flushList();
            $code=array();
            for($i=$i+1;$i<$count;$i++){
                if(preg_match('/^```/', trim($lines[$i]))) break;
                $code[]=$lines[$i];
            }
            $html.='<pre><code>'.dhtmlspecialchars(rtrim(implode("\n",$code))).'</code></pre>';
            continue;
        }
        if(trim($line)===''){ $flushParagraph(); $flushList(); continue; }
        if(preg_match('/^\s*>\s*$/',$line)){ $flushParagraph(); $flushList(); continue; }
        if($i+1<$count && strpos($line,'|')!==false && aigeo_k_is_table_separator($lines[$i+1])){
            $flushParagraph(); $flushList();
            $tableRows=array($line,$lines[$i+1]);
            for($i=$i+2;$i<$count;$i++){
                if(strpos($lines[$i],'|')===false || trim($lines[$i])==='') { $i--; break; }
                $tableRows[]=$lines[$i];
            }
            $html.=aigeo_k_render_table($tableRows);
            continue;
        }
        if(preg_match('/^(#{1,4})\s+(.+)$/',$line,$m)){
            $flushParagraph(); $flushList();
            $level=min(4, max(2, strlen($m[1])));
            $html.='<h'.$level.'>'.aigeo_k_inline_markdown(trim($m[2])).'</h'.$level.'>';
            continue;
        }
        if(preg_match('/^\s*>\s*(.+)$/',$line,$m)){
            $flushParagraph(); $flushList();
            $html.='<blockquote>'.aigeo_k_inline_markdown(trim($m[1])).'</blockquote>';
            continue;
        }
        if(preg_match('/^\s*[-*]\s+(.+)$/',$line,$m)){
            $flushParagraph();
            if($listType && $listType!=='ul') $flushList();
            $listType='ul';
            $list[]=trim($m[1]);
            continue;
        }
        if(preg_match('/^\s*\d+\.\s+(.+)$/',$line,$m)){
            $flushParagraph();
            if($listType && $listType!=='ol') $flushList();
            $listType='ol';
            $list[]=trim($m[1]);
            continue;
        }
        $paragraph[]=$line;
    }
    $flushParagraph(); $flushList();
    return $html;
}
function aigeo_k_strip_chunk_heading($content,$heading){
    $content=ltrim((string)$content);
    $heading=trim((string)$heading);
    if($heading==='') return $content;
    $lines=preg_split('/\r?\n/',$content,2);
    $first=isset($lines[0])?trim($lines[0]):'';
    $plain=preg_replace('/^#{1,6}\s+/','',$first);
    if(trim($plain)===$heading) return isset($lines[1])?$lines[1]:'';
    return $content;
}
function aigeo_k_chunk_heading_level($content,$heading){
    $heading=trim((string)$heading);
    $lines=preg_split('/\r?\n/', ltrim((string)$content), 2);
    $first=isset($lines[0])?trim($lines[0]):'';
    if(preg_match('/^(#{1,6})\s+(.+)$/',$first,$m) && trim($m[2])===$heading) return min(4, max(2, strlen($m[1])));
    return 2;
}
function aigeo_k_chunks($body){ $rows=array(); $lines=preg_split('/\r?\n/',$body); $heading='正文'; $buf=array(); $order=0; foreach($lines as $line){ if(preg_match('/^(#{2,4})\s+(.+)$/',$line,$m)){ if(trim(implode("\n",$buf))!==''){ $rows[]=array('heading'=>$heading,'content'=>trim(implode("\n",$buf)),'displayorder'=>$order++); } $heading=trim($m[2]); $buf=array($line); } else { $buf[]=$line; } } if(trim(implode("\n",$buf))!==''){ $rows[]=array('heading'=>$heading,'content'=>trim(implode("\n",$buf)),'displayorder'=>$order++); } return $rows; }
function aigeo_k_save_item($data, $id=0){ $now=TIMESTAMP; $row=array('title'=>aigeo_k_clean($data['title'],255),'slug'=>aigeo_k_clean($data['slug'],255),'domain'=>aigeo_k_clean($data['domain'],32),'module'=>aigeo_k_clean($data['module'],32),'type'=>aigeo_k_clean($data['type'],32),'category'=>aigeo_k_clean($data['category'],100),'tags'=>aigeo_k_clean($data['tags'],255),'summary'=>trim((string)$data['summary']),'content'=>trim((string)$data['content']),'keywords'=>aigeo_k_clean($data['keywords'],255),'version_scope'=>aigeo_k_clean($data['version_scope'],255),'source_domain'=>aigeo_k_clean($data['source_domain'],32),'source_module'=>aigeo_k_clean($data['source_module'],32),'source_type'=>aigeo_k_clean($data['source_type'],32),'source_table'=>aigeo_k_clean($data['source_table'],64),'source_id'=>aigeo_k_clean($data['source_id'],100),'source_sub_id'=>aigeo_k_clean($data['source_sub_id'],100),'source_title'=>aigeo_k_clean($data['source_title'],255),'source_file'=>aigeo_k_clean($data['source_file'],255),'source_url'=>aigeo_k_clean($data['source_url'],255),'status'=>aigeo_k_clean($data['status'],32),'ai_access'=>empty($data['ai_access'])?0:1,'public_access'=>empty($data['public_access'])?0:1,'priority'=>aigeo_k_clean($data['priority'],16),'updated_at'=>$now); if($id>0){ DB::update('aigeo_knowledge_item',$row,array('id'=>$id)); return $id; } $row['created_at']=$now; return DB::insert('aigeo_knowledge_item',$row,true); }
function aigeo_k_rebuild_chunks($item_id,$content,$domain,$module,$type){ DB::delete('aigeo_knowledge_chunk', array('item_id'=>intval($item_id))); foreach(aigeo_k_chunks($content) as $c){ DB::insert('aigeo_knowledge_chunk', array('item_id'=>intval($item_id),'domain'=>$domain,'module'=>$module,'type'=>$type,'heading'=>cutstr($c['heading'],255,''),'heading_path'=>cutstr($c['heading'],500,''),'content'=>$c['content'],'content_hash'=>sha1($c['content']),'chunk_type'=>'section','displayorder'=>intval($c['displayorder']),'token_count'=>strlen($c['content']),'created_at'=>TIMESTAMP,'updated_at'=>TIMESTAMP)); } }
function aigeo_k_terms($keyword){ $keyword=trim((string)$keyword); if($keyword==='') return array(); $terms=array($keyword); if(preg_match_all('/[A-Za-z0-9_\\.\\-]{2,}|[\x{4e00}-\x{9fa5}]{2,}/u',$keyword,$m)){ foreach($m[0] as $term){ $term=trim($term); if($term!=='' && strlen($term)<=80) $terms[]=$term; } } $out=array(); foreach($terms as $term){ if(!isset($out[$term])) $out[$term]=$term; if(count($out)>=8) break; } return array_values($out); }
function aigeo_k_match_score($row,$terms){ $score=0; foreach($terms as $term){ if($term==='') continue; if(stripos($row['title'],$term)!==false) $score+=80; if(stripos($row['keywords'],$term)!==false) $score+=60; if(stripos($row['summary'],$term)!==false) $score+=40; if(stripos($row['content'],$term)!==false) $score+=10; } if($row['priority']=='high') $score+=3; elseif($row['priority']=='normal') $score+=2; return $score; }
function aigeo_k_excerpt($text,$terms,$len=180){ $plain=trim(strip_tags((string)$text)); $plain=preg_replace('/\s+/',' ',$plain); $pos=false; foreach($terms as $term){ if($term!=='' && ($p=stripos($plain,$term))!==false){ $pos=$p; break; } } if($pos===false) return cutstr($plain,$len,''); $start=max(0,$pos-45); return cutstr(substr($plain,$start),$len,''); }
function aigeo_k_search($keyword,$limit=10,$for_ai=0,$include_private=0,$max_limit=20){
    $keyword=trim((string)$keyword); $max_limit=max(1,intval($max_limit)); $limit=max(1,min($max_limit,intval($limit))); $terms=aigeo_k_terms($keyword); $results=array();
    if($for_ai){
        $baseWhere=$include_private ? "status IN('published','private') AND ai_access=1" : "status='published' AND ai_access=1";
    } else {
        $baseWhere="status='published' AND public_access=1";
    }
    $params=array('aigeo_knowledge_item'); $where=$baseWhere;
    if($terms){ $parts=array(); foreach($terms as $term){ $parts[]="(title LIKE %s OR summary LIKE %s OR keywords LIKE %s OR content LIKE %s)"; $like='%'.$term.'%'; array_push($params,$like,$like,$like,$like); } $where.=" AND (".implode(' OR ',$parts).")"; }
    $rows=DB::fetch_all("SELECT * FROM %t WHERE $where ORDER BY FIELD(priority,'high','normal','low'), updated_at DESC LIMIT 60",$params);
    foreach($rows as $row){ $score=$terms?aigeo_k_match_score($row,$terms):10; $row['_aigeo_score']=$score; $row['matched_heading']=''; $row['matched_excerpt']=aigeo_k_excerpt($row['summary']?$row['summary']:$row['content'],$terms); $results[intval($row['id'])]=$row; }
    if($terms){
        $chunkParams=array('aigeo_knowledge_chunk','aigeo_knowledge_item'); $chunkWhere="i.$baseWhere"; $chunkParts=array();
        foreach($terms as $term){ $chunkParts[]="(c.heading LIKE %s OR c.content LIKE %s)"; $like='%'.$term.'%'; array_push($chunkParams,$like,$like); }
        $chunkWhere.=" AND (".implode(' OR ',$chunkParts).")";
        $chunks=DB::fetch_all("SELECT i.*, c.heading AS matched_heading, c.content AS matched_content FROM %t c LEFT JOIN %t i ON i.id=c.item_id WHERE $chunkWhere ORDER BY i.updated_at DESC LIMIT 80",$chunkParams);
        foreach($chunks as $row){ if(empty($row['id'])) continue; $id=intval($row['id']); $score=aigeo_k_match_score($row,$terms)+25; if(stripos($row['matched_heading'],$keyword)!==false) $score+=20; if(isset($results[$id]) && $results[$id]['_aigeo_score']>=$score) continue; $row['_aigeo_score']=$score; $row['matched_heading']=$row['matched_heading']; $row['matched_excerpt']=aigeo_k_excerpt($row['matched_content'],$terms); $results[$id]=$row; }
    }
    $rows=array_values($results); usort($rows,function($a,$b){ if($a['_aigeo_score']==$b['_aigeo_score']) return intval($b['updated_at'])-intval($a['updated_at']); return intval($b['_aigeo_score'])-intval($a['_aigeo_score']); });
    return array_slice($rows,0,$limit);
}
function aigeo_k_json($arr){ @header('Content-Type: application/json; charset=utf-8'); echo json_encode($arr); exit; }