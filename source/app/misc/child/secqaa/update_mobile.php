<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$refererhost = parse_url($_SERVER['HTTP_REFERER']);
$refererhost['host'] .= !empty($refererhost['port']) ? (':'.$refererhost['port']) : '';

if($refererhost['host'] != $_SERVER['HTTP_HOST']) {
	exit('Access Denied');
}

$message = '';
$showid = 'secqaa_'.$idhash;
if($_G['setting']['secqaa']) {
	$question = make_secqaa();
}

$seclang = lang('forum/misc');
header("Content-Type: application/javascript");
$questionJson = json_encode(preg_replace("/\r|\n/", '', $question), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$placeholderJson = json_encode($seclang['secqaa'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
echo <<<EOF
var secqaaContainer = document.getElementById('$showid');
if(secqaaContainer) {
	var secqaaQuestion = $questionJson;
	var secqaaQuestionLink = document.getElementById('v$showid');
	if(!secqaaQuestionLink) {
		var secqaaHash = document.createElement('input');
		secqaaHash.type = 'hidden';
		secqaaHash.name = 'secqaahash';
		secqaaHash.value = '$idhash';
		secqaaContainer.appendChild(secqaaHash);

		var secqaaInput = document.createElement('input');
		secqaaInput.type = 'text';
		secqaaInput.className = 'txt px vm';
		secqaaInput.style.width = '115px';
		secqaaInput.autocomplete = 'off';
		secqaaInput.name = 'secanswer';
		secqaaInput.id = 'secqaaverify_$idhash';
		secqaaInput.placeholder = $placeholderJson;
		secqaaContainer.appendChild(secqaaInput);

		secqaaQuestionLink = document.createElement('span');
		secqaaQuestionLink.id = 'v$showid';
		secqaaContainer.appendChild(secqaaQuestionLink);

		var secqaaCheck = document.createElement('span');
		secqaaCheck.id = 'checksecqaaverify_$idhash';
		secqaaContainer.appendChild(secqaaCheck);
	}

	var secqaaRefresh = document.createElement('a');
	secqaaRefresh.href = 'javascript:;';
	secqaaRefresh.className = 'xi2';
	secqaaRefresh.textContent = secqaaQuestion;
	secqaaRefresh.addEventListener('click', function() {
		updatesecqaa('$idhash');
	});
	secqaaQuestionLink.replaceChildren(secqaaRefresh);
}
EOF;
