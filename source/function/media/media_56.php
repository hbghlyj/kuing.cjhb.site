<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('www.56.com');

function media_56($url, $width, $height) {
	if(preg_match("/^http:\/\/www.56.com\/\S+\/play_album-aid-(\d+)_vid-(.+?).html/i", $url, $matches)) {
               $matches[1] = $matches[2];
	} elseif(preg_match("/^http:\/\/www.56.com\/\S+\/([^\/]+).html/i", $url, $matches)) {
	}
	if(!$width && !$height && !empty($matches[1])) {
		$api = 'http://vxml.56.com/json/'.str_replace('v_', '', $matches[1]).'/?src=out';
		$str = dfsockopen($api);
		if(!empty($str) && preg_match("/\"img\":\"(.+?)\"/i", $str, $image)) {
			$imgurl = trim($image[1]);
		}
	}
        return array($iframe, $url, $imgurl);
}