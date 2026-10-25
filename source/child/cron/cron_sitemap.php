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
$perform = static function($stage, $operation) {
	$warning = '';
	set_error_handler(static function($severity, $message) use (&$warning) {
		$warning = $message;
		return true;
	}, E_WARNING);
	try {
		$result = $operation();
	} finally {
		restore_error_handler();
	}
	if($result === false) {
		runlog('error', 'Sitemap '.$stage.' failed: '.($warning ?: 'operation returned false without a system warning'));
	}
	return $result;
};
$temporaryPath = $perform('create temporary file', static fn() => tempnam(dirname($path), '.sm'));
if($temporaryPath === false || realpath(dirname($temporaryPath)) !== realpath(dirname($path))) {
	if($temporaryPath !== false) {
		runlog('error', 'Sitemap create temporary file failed: temporary file was created outside the destination directory');
		$perform('remove fallback temporary file', static fn() => unlink($temporaryPath));
	}
	return;
}
$handle = $perform('open temporary file', static fn() => fopen($temporaryPath, 'wb'));
if(!$handle) {
	$perform('remove unopened temporary file', static fn() => unlink($temporaryPath));
	return;
}

try {
	$write = static function($content) use ($handle, $perform) {
		$written = $perform('write temporary file', static fn() => fwrite($handle, $content));
		if($written !== false && $written !== strlen($content)) {
			runlog('error', 'Sitemap write temporary file failed: partial write ('.$written.'/'.strlen($content).' bytes)');
		}
		return $written === strlen($content);
	};

	$siteurl = rtrim($_G['setting']['siteurl'], '/').'/';
	$postperpage = max(1, intval($_G['setting']['postperpage']));
	$success = $write("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n")
		&& $write("<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");

	if($success) {
		$query = DB::query('SELECT tid, replies, lastpost, fid IN (9, 10) AS excl FROM %t WHERE displayorder>=0 ORDER BY tid ASC', ['forum_thread']);
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
					&& $write("\t\t<priority>".($thread['excl'] ? '0' : ($thread['replies'] > 4 ? '0.9' : ($thread['replies'] > 3 ? '0.8' : ($thread['replies'] > 2 ? '0.7' : ($thread['replies'] > 1 ? '0.6' : '0.5')))))."</priority>\n")
					&& $write("\t\t<changefreq>daily</changefreq>\n")
					&& $write("\t</url>\n");
				if(!$success) {
					break;
				}
			}
		}
	}

	$success = $success && $write("</urlset>\n");
	$flushed = $perform('flush temporary file', static fn() => fflush($handle));
	$closed = $perform('close temporary file', static fn() => fclose($handle));
	$handle = null;

	if(!$success || !$flushed || !$closed) {
		return;
	}
	if($perform('set temporary file permissions', static fn() => chmod($temporaryPath, 0644))) {
		$perform('rename '.basename($temporaryPath).' to sitemap.xml', static fn() => rename($temporaryPath, $path));
	}
} catch(Throwable $exception) {
	runlog('error', 'Sitemap generation failed: '.$exception->getMessage());
	throw $exception;
} finally {
	if(is_resource($handle)) $perform('cleanup close temporary file', static fn() => fclose($handle));
	if(is_file($temporaryPath)) $perform('cleanup remove temporary file', static fn() => unlink($temporaryPath));
}
