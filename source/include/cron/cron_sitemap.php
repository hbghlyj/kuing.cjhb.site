<?php
if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$filename = 'sitemap.xml';
// 以下五项根据具体情况修改即可
$cfg_updateperi = '60'; // 协议文件更新周期的上限，单位为分钟
$web_root = $_G['siteurl']; // 根网址
$CHARSET = 'utf-8'; // or gbk // 选择编码方式

/***********************************************************************************************/
// 网站地图sitemap.xml
$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$sitemap .= "<urlset\n";
$sitemap .= "xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
$sitemap .= "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
$sitemap .= "xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n";
$sitemap .= "http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

$querys = DB::query("SELECT a.tid FROM " . DB::table('forum_thread') . " a INNER JOIN " . DB::table('forum_forum') . " b ON a.fid = b.fid ORDER BY a.tid DESC LIMIT 0,10000"); // 限制10000条

if ($querys) {
    while ($threadfid = DB::fetch($querys)) {
        $turl = $web_root . 'thread-' . $threadfid['tid'] . '-1-1.html'; // 注意静态规则
        $link = htmlspecialchars($turl, ENT_QUOTES, 'UTF-8'); // 防止特殊字符
        $t = time(); // 当前时间
        $riqi = date("Y-m-d", $t); // 日期
        $priority = rand(1, 10) / 10; // 优先级

        $sitemap .= "<url>\n";
        $sitemap .= "<loc>$link</loc>\n";
        $sitemap .= "<priority>$priority</priority>\n";
        $sitemap .= "<lastmod>$riqi</lastmod>\n";
        $sitemap .= "<changefreq>weekly</changefreq>\n";
        $sitemap .= "</url>\n";
    }
} else {
    runlog('error',"Database query failed: " . DB::error()); // 记录错误日志
}

$sitemap .= "</urlset>\n";

$filepath = DISCUZ_ROOT . '/' . $filename;
if ($fp = fopen($filepath, 'w')) {
    fwrite($fp, $sitemap);
    fclose($fp);
} else {
    runlog('error',"Failed to open file for writing: $filepath"); // 记录错误日志
}
?>