## 分支升级与兼容性说明

请同时阅读 `UPGRADE_NOTES.md`，其中专门记录了当前分支相对 `master` 的差异、部署前必看项、数据库迁移要求以及不兼容变更。


## 当前分支额外行为

### 开发环境缓存目录权限

对于开发克隆站点，以下运行时生成目录应统一由 Web 进程用户持有：

- `data/cache`
- `data/template`
- `data/sysdata`

否则即使目录本身是 `777`，只要其中已有缓存文件被错误地写成 `root:root` 或其它不可覆盖状态，后台如“模板管理 / 风格管理”等涉及缓存重建的操作，仍可能报：

```text
Can not write to cache files, please check directory ./data/ and ./data/cache/ .
```

在开发克隆环境中，已确认需要将上述整棵目录树统一修正为 `www-data:www-data`，而不是只修目录权限。

### HTTPS 与机器人判定

当前分支沿用了上游的请求头判定逻辑：当浏览器请求里缺少 `HTTP_SEC_FETCH_MODE` 时，运行时可能把该访问记为 `UnusualSecFetchModeHeader`，从而按爬虫路径处理。

这在开发环境里尤其需要注意：

- `kuing.cjhb.site` 这类 HTTPS 访问通常会带上 `Sec-Fetch-*` 头；
- 如果仍以纯 HTTP 访问，浏览器可能不发送这些头；
- 结果就是开发环境可能出现“游客头部为空、`#onlinelist` 不显示、fastlogin 不显示、会话里被记成机器人”等现象。

因此，测试当前分支时应尽量保证测试域名也使用 HTTPS。否则即使代码与 `master` 一致，开发环境仍可能因为请求头差异触发机器人判定。

### IP 地理位置库

当前分支的活动 IP 网络归属查询使用 MaxMind DB Reader。

- `source/class/class_ip.php` 和 `source/class/discuz/discuz_application.php` 会加载 `source/class/ip/geoip2.phar`
- Reader 固定读取 `data/ipdata/GeoOpen-Country-ASN.mmdb`
- 查询结果包含国家代码、ASN 和自治系统组织名称，不包含城市
- PHP-FPM 运行用户必须能够读取上述 PHAR 和 MMDB 文件
- 旧的 `tinyipdata.dat` / `wry` 系列数据文件不再是当前默认查询路径

### 用户头像回退

没有上传头像的用户统一显示由用户名生成的彩色首字母头像，不请求不存在的 `_avatar_*.jpg` 文件。

- 主题页在加载帖子时把 `common_member.avatarstatus` 传给 `avatar()`；该函数输出带 `data-avatar-missing="1"` 的图片占位，前端 `loadAvatar()` 立即替换为首字母头像。
- 聊天室历史接口 `chat/php/history.php` 查询同一字段。未上传头像时 JSON 的 `actor.image` 为空，`PusherChatWidget` 直接调用同一前端 `renderInitialAvatar()` 回退逻辑。

两条路径的视觉和回退规则相同；区别只在于主题页传输 HTML，而聊天室传输 JSON，因而不会产生额外的 404 请求。

### 聊天室配置 (chat/php/config.php)

请确保 chat/php/config.php 文件已正确配置，该文件已加入 .gitignore 以防泄漏。

### PHP 语言文件单一来源

当前分支已将 PHP 语言文件也统一收敛到 `source/i18n`。

需要注意：

- `source/language` 已从运行时代码中移除，不应再向该目录补 key；
- 新增或修复 PHP 语言 key 时，只改 `source/i18n/SC_UTF8`、`source/i18n/TC_UTF8`、`source/i18n/EN_UTF8`；
- 删除旧语言树前，已按文件路径与 key 做过迁移审计，避免出现 `!recent_use_tag!` 这类缺 key 现象；
- 如果修改的是服务端语言文件，不仅要重建 `data/cache/lang_*.js`，还要按需要清理对应语言的 `data/template/*` 编译模板。

### 前端 JS 语言来源

当前分支前端 JS 已切换到 `source/i18n/*/lang_js.php` 生成的 `data/cache/lang_*.js`。

需要注意：

- 浏览器端统一通过 `_JSLANG_` 和 `$L(...)` 读取语言字符串；
- `static/js/register.js` 使用 `email_domains`；
- `static/js/common_extra.js` 使用 `color_texts`；
- 不应再从旧语言树直接下发 `lang_js.js` 这类全局脚本；
- `source/language` 已不再作为运行时语言源，前端 JS 相关语言 key 只应维护在 `source/i18n/.../lang_js.php`。

如果前端出现语言回退或缺字，优先检查：

- `source/i18n/*/lang_js.php` 是否已有对应 key；
- `data/cache/lang_*.js` 是否已重建；
- 页面是否仍残留旧的 `lng[...]` / `emaildomains` 直连依赖。

### URL 锚点写法

`[url=sec1][/url]` 会生成 `<a name="sec1"></a>`。

`[url=#sec1]跳转[/url]` 会生成跳转到该锚点的链接。

### 手机模板 CSS 缓存

- 手机模板支持与 PC 模板相同的 `{csstemplate}` 机制。
- 手机 CSS 源文件位于 `template/<模板>/touch/common/`，默认模板使用 `template/default/touch/common/common.css`。
- 生成的缓存文件使用 `style_<styleid>_touch_*.css` 命名，避免与 PC CSS 缓存互相覆盖。
- 插件可通过 `source/plugin/<插件>/template/touch/extend_<文件名>.css` 扩展手机 CSS。

### `discuzx5` 是 `default` 的覆盖层

`template/discuzx5` 不是一套完整、独立的模板，而是建立在 `template/default` 之上的局部覆盖层。当前风格缺少某个模板文件时，模板解析器会回退到 `template/default` 中的同名 `.htm` 或 `.php` 文件。因此，不能仅检查 `discuzx5` 目录来判断某个界面或功能是否有实现。

手机模板是例外：`discuzx5` 没有 `touch/` 模板目录。移动端解析器不会静默回退到桌面模板；当当前风格缺少请求的手机模板时，会显示 `mobile_template_no_found` 并将 `mobile` Cookie 设为 `no`。移动端样式由 `common_setting.styleid2` 选择，不读取用户的 `styleid` Cookie。安装数据已将 `styleid2` 默认设为 `1`（`default`）；若管理员将它改为 `discuzx5`，则应改回 `1`，或为该风格补齐对应的 `touch/` 模板。

与 `default` 相比，`discuzx5` 的目录和组件覆盖范围明显不完整。`admin`、`cell`、`cells` 虽然存在，但只实现了其中一部分文件；`forum` 目录尤其精简，大量专用界面完全由 `default` 提供。主要回退类别包括：

- `ajax_*`：图片列表、附件、快速回复等 AJAX 弹窗模板；
- `collection_*`：淘专辑及相关列表功能；
- `modcp_*`：完整的前台版主管理面板；
- `post_*`：投票、悬赏、商品、辩论等专用发帖表单；
- `viewthread_*`：打印、付费、悬赏、投票、辩论、相册等专用主题视图。

维护 `discuzx5` 时，应把它视为 `default` 的差异层：通用实现优先修改 `default`，只有确实需要 X5 专属结构或样式时才在 `discuzx5` 添加覆盖文件。修改上述组件后必须同时检查实际命中的模板路径，避免误以为 X5 页面只会执行 `discuzx5` 内的代码。

### forum lastpost 字段顺序

当前分支运行时应按以下顺序理解 `pre_forum_forum.lastpost`：

```text
tid \t dateline \t author \t subject
```

也就是说：

- 第 2 段是最后回复时间戳
- 第 3 段是最后回复作者
- 第 4 段才是最后回复主题标题

不要再按旧的

```text
tid \t subject \t dateline \t author
```

顺序来解析，否则会出现“标题显示成时间戳、日期显示成 1970-1-1”之类的问题。

### `class_i18n` 与语言文件装载路径

当前分支服务端语言装载已统一走 i18n 链路，核心入口是 `source/class/class_i18n.php`。

需要注意：

- 模板里的 `{lang ...}`、`lang()`、以及部分 app wrapper 的语言装载，取决于 i18n 目录、`class_i18n`、`currentlang()`、以及模板编译缓存路径；
- `DISCUZ_LANG` 仍负责浏览器语言分流与语言相关缓存分隔；
- 删除旧语言树后，新增或修复语言 key 时应只改 `source/i18n/...`。

因此，排查语言问题时不要只看模板：

- 需要同时检查 `source/class/class_i18n.php`；
- 需要确认当前请求实际使用的是哪套 i18n 目录；
- 需要确认对应编译模板是否已按语言分目录重建。

换句话说，`source/i18n/*/lang_js.php` 负责生成前端 JS 语言缓存，而 `class_i18n` 负责服务端语言装载与模板 `{lang}` 解析问题。


### **5版本说明** 

相对于3.4版本，做了以下修改：

#### 1. 数据库相关变更

3.5版本，支持InnoDB与MyISAM两种数据库引擎，在两种引擎下数据库都不再支持utf8编码，转而支持utf8mb4编码。

##### 1.1 数据库表结构的变更：

参考 [scheme-change-without-data-loss.sql](https://gitee.com/oldhuhu/DiscuzX34235/blob/master/scheme/scheme-change-without-data-loss.sql)
  * 修改了所有的IP地址，改为varchar(45)类型;
  * 在所有记录IP地址的地方，增加了端口号的记录;
  * 在pre_common_banned表中，增加了upperip和lowerip两个VARBINARY(16)类型的字段，用于记录IP地址的封禁范围最大值和最小
  * 将部分字段改”大“，比如INT改为BIGINT, TEXT改为MEDIUMTEXT等
  * 为支持IPv6，去掉了所有IP1/IP2/IP3/IP4的字段定义，参考[scheme-change-drop-columns.sql](https://gitee.com/oldhuhu/DiscuzX34235/blob/master/scheme/scheme-change-drop-columns.sql)

##### 1.2 为支持InnoDB相关的变更

对于InnoDB数据库引擎，还会做如下变更，参考 [scheme-change-innodb.sql](https://gitee.com/oldhuhu/DiscuzX34235/blob/master/scheme/scheme-change-innodb.sql)
  * 为支持InnoDB，在表pre_common_member_grouppm中增加了一个索引
  * 为支持InnoDB，在表pre_forum_post中，取消了position的auto_increment属性

在配置文件中，引入了一个新的相关配置项，这个配置项要正确设置。尤其对于升级用户，否则会导致发帖功能不正常。

```
/*
 * 数据库引擎，根据自己的数据库引擎进行设置，3.5之后默认为innodb，之前为myisam
 * 对于从3.4升级到3.5，并且没有转换数据库引擎的用户，在此设置为myisam
 */
$_config['db']['common']['engine'] = 'innodb';
```


##### 1.3 为支持utf8mb4相关的变更

对于MyISAM引擎，由于1000个字节的索引长度限制，因此要对一些索引做重新定义，参考 [scheme-change-myisam-utf8mb4.sql](https://gitee.com/oldhuhu/DiscuzX34235/blob/master/scheme/scheme-change-myisam-utf8mb4.sql)

无论是InnoDB还是MyISAM，所有的表都使用utf8mb4编码与utf8mb4_unicode_ci，参考 [scheme-change-charset.sql](https://gitee.com/oldhuhu/DiscuzX34235/blob/master/scheme/scheme-change-charset.sql)


#### 2. IP相关变更

在3.5版本中，为了支持IPv6，做了以下变更

##### 2.1 IP地址库

系统使用 `source/class/class_ip.php` 调用 `GeoOpen-Country-ASN.mmdb`，同时支持 IPv4 和 IPv6 地址的国家与 ASN 查询。

##### 2.2 IP封禁

现在IP地址封禁，不再使用 `*` 作为通配符，而是使用[子网掩码(CIDR)](https://cloud.tencent.com/developer/article/1392116)的方式来指定要封禁的地址范围。

IP封禁的配置，现在保存在pre_common_banned表中，**每次**用户访问的时候，都会触发检查。现在的检查效率较高，每次只会产生一个带索引的SQL查询（基于VARBINARY类型的大小比较）。对于一般的站点性能不会带来问题。另外可以启用Redis缓存，来进一步提高性能。另外还有一个配置项可关闭此功能，使用外部的防火墙等来进行IP封禁管理：

```
$_config['security']['useipban'] = 1; // 是否开启允许/禁止IP功能，高负载站点可以将此功能疏解至HTTP Server/CDN/SLB/WAF上，降低服务器压力
```

##### 2.3 IP地址获取

IP地址获取，现在默认只信任REMOTE_ADDR，其它的因为太容易仿造，默认禁止。获取的方式也可以扩展，在配置文件中增加了以下配置项

```
/**
 * IP获取扩展
 * 考虑到不同的CDN服务供应商提供的判断CDN源IP的策略不同，您可以定义自己服务供应商的IP获取扩展。
 * 为空为使用默认体系，非空情况下会自动调用source/class/ip/getter_值.php内的get方法获取IP地址。
 * 系统提供dnslist(IP反解析域名白名单)、serverlist(IP地址白名单，支持CIDR)、header扩展，具体请参考扩展文件。
 * 性能提示：自带的两款工具由于依赖RDNS、CIDR判定等操作，对系统效率有较大影响，建议大流量站点使用HTTP Server
 * 或CDN/SLB/WAF上的IP黑白名单等逻辑实现CDN IP地址白名单，随后使用header扩展指定服务商提供的IP头的方式实现。
 * 安全提示：由于UCenter、UC_Client独立性及扩展性原因，您需要单独修改相关文件的相关业务逻辑，从而实现此类功能。
 * $_config['ipgetter']下除setting外均可用作自定义IP获取模型设置选项，也欢迎大家PR自己的扩展IP获取模型。
 * 扩展IP获取模型的设置，请使用格式：
 * 		$_config['ipgetter']['IP获取扩展名称']['设置项名称'] = '值';
 * 比如：
 * 		$_config['ipgetter']['onlinechk']['server'] = '100.64.10.24';
 */
$_config['ipgetter']['setting'] = '';
$_config['ipgetter']['header']['header'] = 'HTTP_X_FORWARDED_FOR';
$_config['ipgetter']['iplist']['header'] = 'HTTP_X_FORWARDED_FOR';
$_config['ipgetter']['iplist']['list']['0'] = '127.0.0.1';
$_config['ipgetter']['dnslist']['header'] = 'HTTP_X_FORWARDED_FOR';
$_config['ipgetter']['dnslist']['list']['0'] = 'comsenz.com';
```
##### 2.4 游客与爬虫网络标签

游客和爬虫会话均保存国家、ASN 和自治系统组织名称。Cloudflare 提供有效的 `HTTP_CF_IPCOUNTRY` 和 `HTTP_CF_IPCITY` 时，国家代码和城市用于界面显示；ASN 与组织名称由 Country/ASN MMDB 补全。

在线用户列表同时显示游客和爬虫；两者分别使用游客与爬虫图标，不添加“游客”或爬虫名称前缀。论坛内的紧凑列表为游客显示国旗和 Cloudflare 城市，标题提示显示 ASN 与组织名称；爬虫直接显示组织名称，标题提示只显示 ASN。完整在线列表显示全部信息。

##### 2.5 Country/ASN 查询与输出策略调整

本分支使用 MaxMind DB Reader 读取 Country/ASN 合并库：

* `source/class/class_ip.php` 不再走原有可插拔 IPDB 实现；
* `source/class/class_ip.php` 和 `source/class/discuz/discuz_application.php` 都会加载 `source/class/ip/geoip2.phar`；
* `MaxMind\Db\Reader` 固定读取 `data/ipdata/GeoOpen-Country-ASN.mmdb`，PHP-FPM 运行用户必须对这两个文件具有读取权限；
* IP 归属显示国家、ASN 和自治系统组织名称，不再查询城市；
* 旧的 `ip_tiny.php` 与 `ip_wry.php` 已移除，不再作为位置库后端使用。

因此，部署时需要准备兼容的 `GeoOpen-Country-ASN.mmdb`，否则 IP 网络归属解析将返回空结果。

同时，本分支已明确采用以下输出策略：

```php
$_config['output']['upgradeinsecure'] = 1;
```

也就是说：

* 在 HTTPS 环境下默认请求浏览器将 HTTP 内链升级为 HTTPS；
* 不再提供兼容低版本 IE 的额外 CSS 开关和样式加载路径。

##### 2.6 标签输入分隔规则

本分支已跟进上游标签输入逻辑调整：

* 添加标签时，不再使用空格作为多个标签的分隔符；
* 多个标签应使用英文逗号或可被标准化为逗号的对应全角符号分隔。

这意味着旧的“以空格拆分多个标签”的行为已取消；如果仍按空格输入，将不会再按多个标签解析。

##### 2.7 自动链接显示规则

本分支已跟进上游自动链接显示逻辑调整：

* 自动识别的外链文本会隐藏 `http://`、`https://` 以及 `www.` 前缀；
* 当显示文本过长时，按新的较短规则进行截断显示。

这只影响链接的可见文本，不改变链接实际跳转目标。

### Google 登录（googleconnect 插件）

本分支对 `source/plugin/googleconnect/connect/connect_login.php` 做了以下改动：

#### 用 `sub` 作为稳定用户标识

Google ID Token 中的 `sub` 字段是用户在 Google 账号体系中唯一且永不变化的标识（名称和邮箱均可被用户修改）。本分支已将其作为主键存储于 `pre_common_member_account` 表中（`atype = 4` 代表 Google 账号）。

登录查找流程如下：

1. **优先**：通过 `account = $sub AND atype = 4` 查 `common_member_account`，直接定位用户；
2. **兜底**：若未找到（兼容旧账号），退回邮箱查找 `common_member`，找到后自动将 `sub` 写入 `common_member_account`，下次登录即走第 1 步；
3. **新用户**：注册成功后立即写入 `sub`。

这意味着即使用户修改了 Google 邮箱，只要 `sub` 不变，仍可正常登录。

#### 重名处理

若 `$payload['name']` 已被其他账号使用（`uc_user_register` 返回 `-3`），系统会自动在名称后追加随机 3-4 位数字并最多重试 5 次，避免向用户显示报错页面。

#### `common_member_profile.fields` JSON 约束兼容

`pre_common_member_profile` 表的 `fields` 列带有 MariaDB CHECK 约束：

```sql
CONSTRAINT `fields` CHECK (json_valid(`fields`))
```

`insert_user()` 现在在插入 profile 时，若调用方未提供 `fields` 值，会自动补填 `'{}'`，避免触发约束失败（错误 4025）。

### 图片上传下限

服务端会拒绝总像素数少于 16 的图片（例如 1×1 PNG），并返回上传状态 `13`。该限制避免保存无效的极小图片；自动化上传测试应使用至少 4×4 的有效图片。

### 临时目录说明

DiscuzX 系统运行过程中会使用到几个带有 `temp` 的临时或缓存目录，它们的用途分别如下：

- `data/temp`：用于存放系统或某些特定功能生成的通用临时文件数据。
- `data/attachment/temp`：主要用于在用户上传文件或图片的过程中，将数据临时存放在此处。待处理（如缩略图、水印生成等）完毕并正式保存后，这些文件才会被移动到正式的附件目录中。
- `data/template`：这里存放的是由 `.htm` 源码模板编译生成的 `.tpl.php` 缓存文件。通过将模板预编译成 PHP 代码，可以大大提高页面渲染效率。每次修改了前台模板或更换了语言环境后，通常需要清理此目录以重新生成最新缓存。
