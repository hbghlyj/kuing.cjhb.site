<?php

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
require_once DISCUZ_ROOT.'source/plugin/aigeo_knowledge/libs/helper.php';

$siteUrl = rtrim($_G['siteurl'], '/').'/';
$frontUrl = $siteUrl.'plugin.php?id=aigeo_knowledge';
$feedbackUrl = 'https://ai.liangjianyun.com/plugin.php?id=aigeo_feedback:index';
$qrcodeUrl = 'https://ai.liangjianyun.com/wxwork.jpg';
aigeo_k_admin_head();
include template('aigeo_knowledge:admin/feedback');