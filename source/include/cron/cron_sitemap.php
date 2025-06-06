<?php
if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$filename = 'sitemap.xml';
// 以下五项根据具体情况修改即可
$cfg_updateperi = '60'; // 协议文件更新周期的上限，单位为分钟
$web_root = $_G['setting']['siteurl'].'/'; // 根网址
$CHARSET = 'utf-8'; // or gbk // 选择编码方式

/***********************************************************************************************/
// 网站地图sitemap.xml
$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$sitemap .= "<urlset\n";
$sitemap .= "xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
$sitemap .= "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
$sitemap .= "xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n";
$sitemap .= "http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

$result = DB::fetch_all('SELECT tid,replies,lastpost FROM ' . DB::table("forum_thread") . ' WHERE fid NOT IN (2,9,10) ORDER BY lastpost DESC');

if (!$result) {
    runlog('error', "Failed to fetch data from database"); // 记录错误日志
}
foreach ($result as $row) {
    //页面伪静态规则是：thread-{tid}-{page}-{prevpage}.html即规则为：thread-{帖子ID}-{帖子翻页ID}-{当前帖子所在的列表页ID}.html
    for ($i = 1; $i <= ceil($row['replies'] / 20); $i++) {
        //帖子翻页ID用于页头页尾的"上一页/下一页"的链接
        //在这里进行循环生成生成了第i页的地址，i最大是replies除以20向上取整，因为每页20个帖子
        $url = $web_root . 'thread-' . $row['tid'] . '-' . $i . '-1.html';
        //当前帖子所在的列表页ID用于"返回列表"{lang return_forumdisplay}的链接，都填1，因为不太好计算到底是第几页
        $link = htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); // 防止特殊字符
        $lastmod = date('Y-m-d', $row['lastpost']); // 最后更新时间

        $sitemap .= "<url>\n";
        $sitemap .= "<loc>$link</loc>\n";
        $sitemap .= "<lastmod>$lastmod</lastmod>\n";
        if ($row['replies'] > 4) {
            $sitemap .= "<priority>0.9</priority>\n";
        } elseif ($row['replies'] > 3) {
            $sitemap .= "<priority>0.8</priority>\n";
        } elseif ($row['replies'] > 2) {
            $sitemap .= "<priority>0.7</priority>\n";
        } elseif ($row['replies'] > 1) {
            $sitemap .= "<priority>0.6</priority>\n";
        } else {
            $sitemap .= "<priority>0.5</priority>\n";
        }
        $sitemap .= "<changefreq>daily</changefreq>\n";
        $sitemap .= "</url>\n";
    }
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