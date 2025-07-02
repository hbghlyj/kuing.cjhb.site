<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$checkurl = array('v.qq.com/x/page/','v.qq.com/x/cover/');

function media_qq($url, $width, $height) {
	if(preg_match("/https?:\/\/v.qq.com\/x\/page\/([^\/]+)(.html|)/i", $url, $matches)) {
                        $vid = explode(".html", $matches[1]);
                        $iframe = 'https://v.qq.com/txp/iframe/player.html?vid='.$vid[0];
                        $imgurl = '';
	} else if(preg_match("/https?:\/\/v.qq.com\/x\/cover\/([^\/]+)\/([^\/]+)(.html|)/i", $url, $matches)) {
                        $vid = explode(".html", $matches[2]);
                        $iframe = 'https://v.qq.com/txp/iframe/player.html?vid='.$vid[0];
                        $imgurl = '';
	}
        return array($iframe, $url, $imgurl);
}