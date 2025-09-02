<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cache_smilies_js.php 24968 2011-10-19 09:51:28Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_smilies_js() {
	global $_G;

        $return = 'var smilies_type = [];' . 'var smilies_array = [];' . 'var smilies_fast = [];';
        $i = 0;
        foreach(C::t('common_smiley')->fetch_all() as $smiley) {
                $code = str_replace('\'', '\\\'', $smiley['code']);
                $return .= "smilies_array[".$smiley['id']."]=['".$code."','".$smiley['url']."'];";
                $i++;
        }
        $return = "var smthumb = '{$_G['setting']['smthumb']}';".$return;
        $cachedir = DISCUZ_ROOT.'./data/cache/';
        if(file_put_contents($cachedir.'common_smilies_var.js', $return, LOCK_EX) === false) {
                exit('Can not write to cache files, please check directory ./data/ and ./data/cache/ .');
        }

}
