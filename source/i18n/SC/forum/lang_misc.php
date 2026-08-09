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
	'contact' => '联系方式:',
	'anonymous' => '匿名',
	'anonymoususer' => '匿名者',
	'guestuser' => '游客',
	'has_expired' => '该信息已经过期',
	'click_view' => '点击查看',
	'never_expired' => '永不过期',
	'sort_update' => '更新',
	'sort_upload' => '上传',
	'view_noperm' => '隐藏内容',
	'post_hidden' => '**** 本内容被作者隐藏 ****',
	'post_sold' => '**** 本内容购买后可见 ****',
	'post_banned' => '**** 作者被禁止或删除 内容自动屏蔽 ****',
	'post_single_banned' => '**** 该帖被屏蔽 ****',
	'post_reply_quote' => '{author} 发表于 {time}',

	'perms_viewperm' => '允许查看',
	'perms_postperm' => '允许发新主题',
	'perms_replyperm' => '允许回复',
	'perms_getattachperm' => '允许下载附件',
	'perms_postattachperm' => '允许上传附件',
	'perms_postimageperm' => '允许上传图片',
	'perms_allowvisit' => '访问论坛',
	'perms_readaccess' => '阅读权限',
	'perms_allowinvisible' => '隐身',
	'perms_allowsearch' => '使用搜索',
	'perms_allowcstatus' => '自定义头衔',
	'perms_disablepostctrl' => '发帖不受限制',
	'perms_allowsendpm' => '允许发短消息',
	'perms_allowfriend' => '允许加好友',
	'perms_allowstatdata' => '查看统计数据报表',
	'perms_allowpostarticle' => '发表文章',
	'perms_allowpost' => '发新话题',
	'perms_allowreply' => '发表回复',
	'perms_allowpostpoll' => '发起投票',
	'perms_allowvote' => '参与投票',
	'perms_allowpostreward' => '发表悬赏',
	'perms_allowpostactivity' => '发表活动',
	'perms_allowpostdebate' => '发表辩论',
	'perms_allowposttrade' => '发表交易',
	'perms_allowat' => '允许 @ 的人数',
	'perms_allowreplycredit' => '允许设置回帖奖励',
	'perms_allowposttag' => '允许使用标签',
	'perms_allowcreatecollection' => '允许创建淘专辑的数量',
	'perms_maxsigsize' => '最大签名长度',
	'perms_allowsigbbcode' => '签名中使用编辑器代码',
	'perms_allowsigimgcode' => '签名中使用 [img] 代码',
	'perms_allowrecommend' => '主题评价影响值',
	'perms_allowcommentpost' => '允许参与点评',
	'perms_allowmediacode' => '允许使用多媒体代码',
	'perms_allowgetattach' => '下载附件',
	'perms_allowgetimage' => '查看图片',
	'perms_allowpostattach' => '上传附件',
	'perms_allowpostimage' => '上传图片',
	'perms_allowsetattachperm' => '允许设置附件权限',
	'perms_maxattachsize' => '单个最大附件尺寸',
	'perms_maxsizeperday' => '每天最大附件总尺寸',
	'perms_maxattachnum' => '每天最大附件数量',
	'perms_attachextensions' => '附件类型',
	'perms_allowpoke' => '允许打招呼',
	'perms_allowclick' => '允许表态',
	'perms_allowcomment' => '发表留言/评论',
	'perms_maxspacesize' => '空间大小',
	'perms_maximagesize' => '单张图片最大尺寸',
	'perms_allowblog' => '发表日志',
	'perms_allowupload' => '上传图片',
	'perms_allowshare' => '发布分享',
	'perms_allowdoing' => '发表记录',

	'price' => '售价',
	'pay_view' => '记录',
	'attachment_buy' => '购买',

	'post_trade_name' => '商品名称',
	'post_trade_price' => '商品价格',

	'post_trade_locus' => '所在地点',

	'post_trade_transport_seller' => '卖家承担运费',
	'post_trade_transport_buyer' => '买家承担运费',
	'post_trade_transport_mail' => '平邮',
	'post_trade_transport_express' => '快递',
	'post_trade_transport_virtual' => '虚拟物品或无需邮递',
	'post_trade_transport_physical' => '买家收到货物后直接支付给物流公司',

	'postappend_content' => '补充内容',
	'payment_unit' => '元',

	'attach_img' => '图片附件',

	'post_trade_transport' => '邮费',
	'trade_unstart' => '<font color="gray">未生效的交易</font>',

	'trade_closed' => '<font color="gray">交易中途关闭(未完成)</font>',

	'trade_offline_1' => '交易单生效',
	'trade_offline_4' => '我已付款，等待卖家发货',
	'trade_offline_5' => '我已发货',
	'trade_offline_7' => '我收到货，交易成功结束',
	'trade_offline_8' => '取消此次交易',
	'trade_offline_10' => '我要退货，等待卖家同意退款',
	'trade_offline_11' => '卖家拒绝退款',
	'trade_offline_12' => '卖家同意退款',
	'trade_offline_13' => '我已退货，等待卖家收货',
	'trade_offline_17' => '卖家收到退货，已退款',

	'trade_message_4' => '可输入付款方式、银行账号等信息',
	'trade_message_5' => '可输入发货公司、发货单号等信息',
	'trade_message_13' => '可输入发货公司、发货单号等信息',

	'credit_payment' => '积分充值',
	'credit_forum_payment' => '论坛积分充值',

	'credit_total' => '总积分',

	'invite_payment' => '购买邀请码',
	'invite_forum_payment' => '购买邀请码',
	'invite_forum_payment_unit' => '个',

	'formulaperm_regdate' => '注册时间',
	'formulaperm_regday' => '注册天数',
	'formulaperm_regip' => '注册 IP',
	'formulaperm_lastip' => '最后登录 IP',
	'formulaperm_buyercredit' => '买家信用评价',
	'formulaperm_sellercredit' => '卖家信用评价',
	'formulaperm_digestposts' => '精华帖数',
	'formulaperm_posts' => '发帖数',
	'formulaperm_threads' => '主题数',
	'formulaperm_oltime' => '在线时间(小时)',

	'formulaperm_and' => '并且',
	'formulaperm_or' => '或者',
	'formulaperm_extcredits' => '自定义积分',

	'login_normal_mode' => '在线',
	'login_switch_invisible_mode' => '切换在线状态',

	'login_invisible_mode' => '隐身',

	'eccredit_explain' => '解释',

	'click_here' => '点击这里',

	'week_0' => '星期日',
	'week_1' => '星期一',
	'week_2' => '星期二',
	'week_3' => '星期三',
	'week_4' => '星期四',
	'week_5' => '星期五',
	'week_6' => '星期六',

	'y_m_d' => 'Y年m月d日',

	'buy_trade' => '购买商品',

	'join_activity' => '参与活动',

	'at_invite' => '@我的好友',

	'lower' => '低于',
	'higher' => '高于',
	'report_msg_your' => '您的 ',
	'report_noreward' => '不奖惩',
	'activity_viewimg' => '点击查看',

	'crime_postreason' => '{reason} &nbsp; <a href="forum.php?mod=redirect&goto=findpost&pid={pid}&ptid={tid}" target="_blank" class="xi2">查看详情</a>',
	'crime_reason' => '{reason}',

	'avatar' => '头像',
	'custom_title' => '自定义头衔',

	'patch_close' => '关闭',

	'plugin_title' => '应用更新提醒',
	'plugin_memo' => '您有 <span class="xi1">{number}</span> 款应用有可用更新',
	'plugin_link' => '现在更新',

	'seccode' => '验证码',
	'seccode_update' => '🔄',

	'secqaa' => '安全验证',

	];

