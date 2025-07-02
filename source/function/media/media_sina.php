<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('video.sina.com.cn/v/b/', 'you.video.sina.com.cn/b/');

function media_sina($url, $width, $height) {
	if(preg_match("/^http:\/\/video.sina.com.cn\/v\/b\/(\d+)-(\d+).html/i", $url, $matches)) {
               if(!$width && !$height) {
			$api = 'http://interface.video.sina.com.cn/interface/common/getVideoImage.php?vid='.$matches[1];
			$str = dfsockopen($api);
			if(!empty($str)) {
				$imgurl = str_replace('imgurl=', '', trim($str));
			}
		}
	} elseif(preg_match("/^http:\/\/you.video.sina.com.cn\/b\/(\d+)-(\d+).html/i", $url, $matches)) {
               if(!$width && !$height) {
			$api = 'http://interface.video.sina.com.cn/interface/common/getVideoImage.php?vid='.$matches[1];
			$str = dfsockopen($api);
			if(!empty($str)) {
				$imgurl = str_replace('imgurl=', '', trim($str));
			}
		}
	}
        return array($iframe, $url, $imgurl);
}