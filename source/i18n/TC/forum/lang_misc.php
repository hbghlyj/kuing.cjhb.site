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

	'post_edit_regexp' => '/^\[i=s\] 本帖最後由 .*? 於 .*? 編輯 \[\/i\][\r\n][\r\n]/s',

	'post_edithtml_regexp' => '/^\[i=s\] 本帖最後由 .*? 於 .*? 編輯 \[\/i\]&lt;br \/&gt;&lt;br \/&gt;/s',

	'post_editnobbcode_regexp' => '/^\[ 本帖最後由 .*? 於 .*? 編輯 \][\r\n][\r\n]/s',

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
	'signature' => '簽名',
	'custom_title' => '自定義頭銜',

	'patch_close' => '關閉',

	'plugin_title' => '應用更新提醒',
	'plugin_memo' => '您有 <span class="xi1">{number}</span> 款應用有可用更新',
	'plugin_link' => '現在更新',

	'seccode' => '驗證碼',
	'seccode_update' => '換一個',

	'secqaa' => '安全驗證',

	];

