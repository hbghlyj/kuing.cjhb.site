<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$lang =
	[
	'contact' => '聯繫方式:',
	'anonymous' => '匿名',
	'anonymoususer' => '匿名者',
	'guestuser' => '遊客',
	'has_expired' => '該資訊已經過期',
	'click_view' => '點擊查看',
	'never_expired' => '永不過期',
	'sort_update' => '更新',
	'sort_upload' => '上傳',
	'view_noperm' => '隱藏內容',
	'post_hidden' => '**** 本內容被作者隱藏 ****',
	'post_sold' => '**** 本內容購買後可見 ****',
	'post_banned' => '**** 作者被禁止或刪除 內容自動屏蔽 ****',
	'post_single_banned' => '**** 該帖被屏蔽 ****',
	'post_reply_quote' => '{author} 發表於 {time}',

	'perms_viewperm' => '允許查看',
	'perms_postperm' => '允許發新主題',
	'perms_replyperm' => '允許回覆',
	'perms_getattachperm' => '允許下載附件',
	'perms_postattachperm' => '允許上傳附件',
	'perms_postimageperm' => '允許上傳圖片',
	'perms_allowvisit' => '訪問論壇',
	'perms_readaccess' => '閱讀權限',
	'perms_allowinvisible' => '隱身',
	'perms_allowsearch' => '使用搜索',
	'perms_allowcstatus' => '自定義頭銜',
	'perms_disablepostctrl' => '發帖不受限制',
	'perms_allowsendpm' => '允許發短消息',
	'perms_allowfriend' => '允許加好友',
	'perms_allowstatdata' => '查看統計數據報表',
	'perms_allowpostarticle' => '發表文章',
	'perms_allowpost' => '發新話題',
	'perms_allowreply' => '發表回覆',
	'perms_allowpostpoll' => '發起投票',
	'perms_allowvote' => '參與投票',
	'perms_allowpostreward' => '發表懸賞',
	'perms_allowpostactivity' => '發表活動',
	'perms_allowpostdebate' => '發表辯論',
	'perms_allowposttrade' => '發表交易',
	'perms_allowat' => '允許 @ 的人數',
	'perms_allowreplycredit' => '允許設置回帖獎勵',
	'perms_allowposttag' => '允許使用標籤',
	'perms_allowcreatecollection' => '允許創建淘專輯的數量',
	'perms_maxsigsize' => '最大簽名長度',
	'perms_allowsigbbcode' => '簽名中使用編輯器代碼',
	'perms_allowsigimgcode' => '簽名中使用 [img] 代碼',
	'perms_allowrecommend' => '主題評價影響值',
	'perms_allowcommentpost' => '允許參與點評',
	'perms_allowmediacode' => '允許使用多媒體代碼',
	'perms_allowgetattach' => '下載附件',
	'perms_allowgetimage' => '查看圖片',
	'perms_allowpostattach' => '上傳附件',
	'perms_allowpostimage' => '上傳圖片',
	'perms_allowsetattachperm' => '允許設置附件權限',
	'perms_maxattachsize' => '單個最大附件尺寸',
	'perms_maxsizeperday' => '每天最大附件總尺寸',
	'perms_maxattachnum' => '每天最大附件數量',
	'perms_attachextensions' => '附件類型',
	'perms_allowpoke' => '允許打招呼',
	'perms_allowclick' => '允許表態',
	'perms_allowcomment' => '發表留言/評論',
	'perms_maxspacesize' => '空間大小',
	'perms_maximagesize' => '單張圖片最大尺寸',
	'perms_allowblog' => '發表日誌',
	'perms_allowupload' => '上傳圖片',
	'perms_allowshare' => '發布分享',
	'perms_allowdoing' => '發表記錄',

	'price' => '售價',
	'pay_view' => '記錄',
	'attachment_buy' => '購買',

	'post_trade_name' => '商品名稱',
	'post_trade_price' => '商品價格',

	'post_trade_locus' => '所在地點',

	'post_trade_transport_seller' => '賣家承擔運費',
	'post_trade_transport_buyer' => '買家承擔運費',
	'post_trade_transport_mail' => '平郵',
	'post_trade_transport_express' => '快遞',
	'post_trade_transport_virtual' => '虛擬物品或無需郵遞',
	'post_trade_transport_physical' => '買家收到貨物後直接支付給物流公司',

	'postappend_content' => '補充內容',
	'payment_unit' => '元',

	'attach_img' => '圖片附件',

	'post_trade_transport' => '郵費',
	'trade_unstart' => '<font color="gray">未生效的交易</font>',

	'trade_closed' => '<font color="gray">交易中途關閉(未完成)</font>',

	'trade_offline_1' => '交易單生效',
	'trade_offline_4' => '我已付款，等待賣家發貨',
	'trade_offline_5' => '我已發貨',
	'trade_offline_7' => '我收到貨，交易成功結束',
	'trade_offline_8' => '取消此次交易',
	'trade_offline_10' => '我要退貨，等待賣家同意退款',
	'trade_offline_11' => '賣家拒絕退款',
	'trade_offline_12' => '賣家同意退款',
	'trade_offline_13' => '我已退貨，等待賣家收貨',
	'trade_offline_17' => '賣家收到退貨，已退款',

	'trade_message_4' => '可輸入付款方式、銀行賬號等資訊',
	'trade_message_5' => '可輸入發貨公司、發貨單號等資訊',
	'trade_message_13' => '可輸入發貨公司、發貨單號等資訊',

	'credit_payment' => '積分充值',
	'credit_forum_payment' => '論壇積分充值',

	'credit_total' => '總積分',

	'invite_payment' => '購買邀請碼',
	'invite_forum_payment' => '購買邀請碼',
	'invite_forum_payment_unit' => '個',

	'formulaperm_regdate' => '註冊時間',
	'formulaperm_regday' => '註冊天數',
	'formulaperm_regip' => '註冊 IP',
	'formulaperm_lastip' => '最後登入 IP',
	'formulaperm_buyercredit' => '買家信用評價',
	'formulaperm_sellercredit' => '賣家信用評價',
	'formulaperm_digestposts' => '精華帖數',
	'formulaperm_posts' => '發帖數',
	'formulaperm_threads' => '主題數',
	'formulaperm_oltime' => '在線時間(小時)',

	'formulaperm_and' => '並且',
	'formulaperm_or' => '或者',
	'formulaperm_extcredits' => '自定義積分',

	'login_normal_mode' => '在線',
	'login_switch_invisible_mode' => '切換在線狀態',

	'login_invisible_mode' => '隱身',

	'eccredit_explain' => '解釋',

	'click_here' => '點擊這裡',

	'week_0' => '星期日',
	'week_1' => '星期一',
	'week_2' => '星期二',
	'week_3' => '星期三',
	'week_4' => '星期四',
	'week_5' => '星期五',
	'week_6' => '星期六',

	'y_m_d' => 'Y年m月d日',

	'buy_trade' => '購買商品',

	'join_activity' => '參與活動',

	'at_invite' => '@我的好友',

	'lower' => '低於',
	'higher' => '高於',
	'report_msg_your' => '您的 ',
	'report_noreward' => '不獎懲',
	'activity_viewimg' => '點擊查看',

	'crime_postreason' => '{reason} &nbsp; <a href="forum.php?mod=redirect&goto=findpost&pid={pid}&ptid={tid}" target="_blank" class="xi2">查看詳情</a>',
	'crime_reason' => '{reason}',

	'avatar' => '頭像',
	'custom_title' => '自定義頭銜',

	'patch_close' => '關閉',

	'plugin_title' => '應用更新提醒',
	'plugin_memo' => '您有 <span class="xi1">{number}</span> 款應用有可用更新',
	'plugin_link' => '現在更新',

	'seccode' => '驗證碼',
	'seccode_update' => '🔄',

	'secqaa' => '安全驗證',

	];

