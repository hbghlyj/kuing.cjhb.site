<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

global $_G;

$path = DISCUZ_ROOT.'sitemap.xml';
$temporaryPath = $path.'.tmp';
$handle = @fopen($temporaryPath, 'wb');
if(!$handle) {
	runlog('error', 'Unable to create sitemap.xml');
	return;
}

$write = static function($content) use ($handle) {
	return fwrite($handle, $content) === strlen($content);
};

$siteurl = rtrim($_G['setting']['siteurl'], '/').'/';
$postperpage = max(1, intval($_G['setting']['postperpage']));
$success = $write("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n")
	&& $write("<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");

if($success) {
	$query = DB::query('SELECT tid, replies, lastpost FROM %t WHERE displayorder>=0 AND fid NOT IN (2, 9, 10) ORDER BY tid ASC', ['forum_thread']);
	while($success && $thread = DB::fetch($query)) {
		$pagecount = max(1, intval(ceil(($thread['replies'] + 1) / $postperpage)));
		for($page = 1; $page <= $pagecount; $page++) {
			$url = $siteurl.'forum.php?mod=viewthread&tid='.$thread['tid'];
			if($page > 1) {
				$url .= '&page='.$page;
			}

			$success = $write("\t<url>\n")
				&& $write("\t\t<loc>".htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8')."</loc>\n")
				&& $write("\t\t<lastmod>".gmdate('Y-m-d', $thread['lastpost'])."</lastmod>\n")
				&& $write("\t</url>\n");
			if(!$success) {
				break;
			}
		}
	}
}

$success = $success && $write("</urlset>\n");
fclose($handle);

if($success && @rename($temporaryPath, $path)) {
	return;
}

@unlink($temporaryPath);
runlog('error', 'Unable to write sitemap.xml');
