<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

global $_G;
$op = in_array($_GET['op'], array('search','match', 'manage', 'set')) ? $_GET['op'] : '';
$taglist = array();
$thread = & $_G['thread'];

if($op == 'search') {
	$searchkey = stripsearchkey($_GET['searchkey']);
	if (empty($searchkey)) {
		exit;
	}
	$query = C::t('common_tag')->fetch_all_by_status(0, $searchkey, 50, 0);
	foreach($query as $value) {
		$taglist[] = $value;
	}
	if($_G['inajax']) {
		echo json_encode(array_column($taglist, 'tagname'));
		exit;
	}
	$searchkey = dhtmlspecialchars($searchkey);
} elseif($op == 'match') {
	$content = $_POST['content'];
	if (empty($content)) {
		echo '<?xml version="1.0" encoding="utf-8"?>
<root><![CDATA[<h3 class="flb">
<em>提取标签</em>
<span><a href="javascript:;" onclick="hideWindow(\'choosetag\');" class="flbc"></a></span></h3>
	<textarea id="content" rows="4" style="width:-webkit-fill-available;margin:5px"></textarea>
	<div id="taglistarea" style="padding: 0 5px;width: 400px;"></div>
	<p class="o pns">
		<button onclick="
		let formData = new FormData();
		formData.append(\'content\', document.querySelector(\'textarea#content\').value);
		fetch(\'forum.php?mod=tag&op=match\', {
			method: \'POST\',
			body: formData
		})
		.then(response => response.text())
		.then(data => {
			const tags = data.split(\',\');
			tags.forEach(tag => {
				const a = document.createElement(\'a\');
				a.className = \'xi2\';
				a.innerHTML = tag;
				a.onclick = function() {
					if(this.className == \'xi2\') {
						addKeyword(tag);
						doane();
						this.className += \' marked\';
					}
				};
				document.querySelector(\'div#taglistarea\').appendChild(a);
				a.insertAdjacentHTML(\'afterend\', \', \');
			});
		})
		.catch(error => console.error(\'Error:\', error));" class="pn pnc"><em>提取</em></button>
	</p>
	<script>document.querySelector(\'textarea#content\').value = $(\'subject\').value+\'\\n\'+textobj.value;</script>
	]]></root>';
	}
	else {
		$taglist = DB::fetch_all("SELECT t.tagName
			FROM " . DB::table("common_tag") . " t
			WHERE %s LIKE CONCAT(%s, t.tagName, %s) LIMIT 5", array($content,'%', '%'));
		echo implode(',', array_column($taglist, 'tagName'));
	}
	exit;
} elseif($op == 'manage') {
	if($_G['tid']) {
		$tagarray_all = $array_temp = $threadtag_array = array();
		$tags = C::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
		$tags = $tags['tags'];
		$tagarray_all = explode("\t", $tags);
		if($tagarray_all) {
			foreach($tagarray_all as $var) {
				if($var) {
					$array_temp = explode(',', $var);
					$threadtag_array[] = $array_temp['1'];
				}
			}
		}
		$tags = implode(',', $threadtag_array);

		$recent_use_tag = array();
		$i = 0;
		$query = C::t('common_tagitem')->select(0, 0, 'tid', 'itemid', 'DESC', 10);
		foreach($query as $result) {
			if($recent_use_tag[$result['tagid']] == '') {
				$i++;
			}
			$recent_use_tag[$result['tagid']] = 1;
		}
		if($recent_use_tag) {
			$query = C::t('common_tag')->fetch_all(array_keys($recent_use_tag));
			foreach($query as $result) {
				$recent_use_tag[$result['tagid']] = $result['tagname'];
			}
		}
	}
} elseif($op == 'set' && $_GET['formhash'] == FORMHASH && $_G['group']['allowmanagetag']) {
	$class_tag = new tag();
	$tagstr = $class_tag->update_field($_GET['tags'], $_G['tid'], 'tid', $_G['thread']);
	C::t('forum_post')->update_by_tid('tid:'.$_G['tid'], $_G['tid'], array('tags' => $tagstr), false, false, 1);
}

include_once template("forum/tag");
?>