<?php
if(!defined('IN_DISCUZ')) {
exit('Access Denied');
}

$filename='sitemap.xml';
//以下五项根据具体情况修改即可
$cfg_updateperi='60';//协议文件更新周期的上限，单位为分钟
$web_root=$_G['siteurl'];//根网址
$CHARSET='utf-8';// or gbk //选择编码方式
/***********************************************************************************************/
//网站地图sitemap.xml
$sitemap="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$sitemap.="<urlset\n";
$sitemap.="xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
$sitemap.="xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
$sitemap.="xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n";
$sitemap.="http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";
$querys = DB::query("SELECT a.tid FROM ".DB::table('forum_thread')." a inner join ".DB::table('forum_forum')." b on a.fid=b.fid ORDER BY a.tid DESC LIMIT 0,10000");
while($threadfid = DB::fetch($querys))
{
$turl=$web_root.'thread-'.$threadfid['tid'].'-1-1.html';//注意静态规则
$link = $turl;
$t=time();
$riqi=date("Y-m-d",$t);
$priority=rand(1,10)/10;
//date("D F d Y",$t);
$sitemap.="<url>\n";
$sitemap.="<loc>$link</loc>\n";
$sitemap.="<priority>$priority</priority>\n";
$sitemap.="<lastmod>$riqi</lastmod>\n";
$sitemap.="<changefreq>weekly</changefreq>\n";
$sitemap.="</url>\n";
}
$sitemap .= "</urlset>\n";
$fp = fopen(DISCUZ_ROOT.'/'.$filename,'w');
fwrite($fp,$sitemap);
fclose($fp);
?>