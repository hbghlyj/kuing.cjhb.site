# 分支升级说明

本文档描述当前分支相对 `master` 的差异，面向站点维护者与部署人员，不是完整 changelog。

## Compared To

- 对比基线：本地 `master`
- 基线提交：`019032ed4242fab35fc6299f022bb5e9a4631273`
- 当前分支说明：包含大量高于 `master` 的功能、行为与运维侧变更，因此本文档只记录部署相关、兼容性相关、数据库相关与较高信号的分支差异

## Must Review Before Deploy

- 这是部署说明，不是功能宣传页。每次更新代码前，应先阅读本文档与 `README.md`。
- 对于存量站点，不要只覆盖代码。至少需要同步检查：
  - `install/sql/sql_upgrade_3.5.php`
  - `config/config_global_default.php`
- 当前分支已经引入额外运行时依赖与默认策略，例如 MaxMind GeoIP2、HTTPS 输出策略、后台登录相关默认项；这些都会影响实际部署结果。

## Backward-Incompatible Changes

### [Required] 账号与登录相关结构已变化

- 存量 X3.5 站点更新代码后，必须执行下面的 SQL，否则可能出现“首次可登录、之后无法再次登录”的问题：

```sql
ALTER TABLE uc_members
  MODIFY COLUMN password varchar(255) NOT NULL DEFAULT '',
  MODIFY COLUMN salt varchar(20) NOT NULL DEFAULT '';
```

- 若贵站的 UCenter 前缀不是 `uc_`，请按实际前缀调整。
- 来源：`README.md`

### [Required] 当前分支默认按升级脚本的新账号体系运行

- 当前分支的升级脚本已经引入新的账号相关结构，包括但不限于：
  - `pre_common_member.loginname`
  - `pre_common_member_username_history`
  - `pre_common_member_account`
- 这些变更不应手工挑选性跳过。对于已有站点，应完整评估并执行 `install/sql/sql_upgrade_3.5.php`。
- 来源：`install/sql/sql_upgrade_3.5.php`

### [Required] 版块名称改为多语言 JSON

- `pre_forum_forum.name` 由单一字符串改为按语言键保存的 JSON 对象，例如 `{"SC_UTF8":"默认版块"}`。
- X3.5 升级脚本会把原有 `name` 保存为 `SC_UTF8`；运行时按当前界面语言读取，并在缺少翻译时回退到已有名称。
- 当前分支曾使用的自定义 `name_en` 列已废弃，不属于 X3.5 升级脚本的输入结构。
- 来源：`install/sql/sql_upgrade_3.5.php`

### [Required] 用户标签功能已移除

- 用户标签、按用户标签搜索会员，以及基于用户标签的版块权限均已删除；普通主题、文章和日志标签不受影响。
- 存量站点更新代码后应清理用户标签数据并移除对应管理权限列：

```sql
DELETE FROM pre_common_tagitem WHERE idtype = 'uid';
DELETE FROM pre_common_tag WHERE status = 3;
ALTER TABLE pre_common_tagitem MODIFY idtype enum('tid','blogid') NOT NULL DEFAULT 'tid';
ALTER TABLE pre_common_admingroup DROP COLUMN alloweditusertag;
```

- 若数据表前缀不是 `pre_`，请按实际前缀调整。

### 旧 IP 查询后端已移除

- 当前默认运行时网络归属查询使用 `GeoOpen-Country-ASN.mmdb`，返回国家、ASN 和自治系统组织名称。
- 老的 `tinyipdata.dat` / `wry` 系列数据文件、查询类和 `source/child/core/ip.php` 路由均已移除。
- 部署时不应再保留或配置旧 `ipdb` 后端。

### [Manual review] 输出策略默认值与旧站点预期不同

- 当前默认输出策略为：

```php
$_config['output']['upgradeinsecure'] = 1;
```

- 这意味着：
  - HTTPS 环境下会默认要求浏览器升级 HTTP 内链到 HTTPS
  - 不再提供兼容低版本 IE 的额外 CSS 开关和样式加载路径
- 若旧站点依赖外域 HTTP 资源，应在部署前先评估。
- 来源：`config/config_global_default.php`、`README.md`

### 编辑器 BBCode / HTML 转换提示

- 发帖编辑器在源码编辑模式和 WYSIWYG 模式之间切换时，会尽量保留未编辑内容的原始源码文本（可能是 BBCode，也可能是 HTML）。
- 如果需要把一段 BBCode 转换为 HTML，或把一段 HTML 转换回 BBCode，可以先在普通编辑框中粘贴代码，切换到 WYSIWYG 模式，再切换“HTML 代码”编辑状态，最后切回普通编辑模式。
- 切换“HTML 代码”状态会丢弃未编辑内容的原始源码缓冲，因此最终切回普通编辑模式时会基于当前 WYSIWYG 内容重新转换。
- 用户在帖子 HTML 中写入的 `<em>` 会以正常字形显示。全局 UI 样式将 `<em>` 作为中性结构元素重置为 `font-style: normal`；Discuz 的 `[i]...[/i]` BBCode 则会渲染为 `<i>`，并在帖子内容中保持斜体。

## Database Migrations Required

### Required before deploy

#### [Required] 在线会话机器人识别原因字段

- 机器人识别原因改为独立保存，不再依赖会被在线列表格式化覆盖的会话用户名。

```sql
ALTER TABLE pre_common_session
  ADD COLUMN bot_reason varchar(255) NOT NULL DEFAULT '' AFTER username;
```

#### [Required] 在线会话城市字段

- 在线访客的 Cloudflare 城市改为独立保存，避免与 ASN/自治系统组织混在 `location` 中。
- 已部署过临时分隔符实现的站点，先备份，再执行以下 SQL（按实际表前缀调整）：

```sql
ALTER TABLE pre_common_session
  ADD COLUMN city varchar(191) NOT NULL DEFAULT '' AFTER location;

UPDATE pre_common_session
SET city = SUBSTRING_INDEX(location, 0x1F, 1),
    location = SUBSTRING(location, LOCATE(0x1F, location) + 1)
WHERE LOCATE(0x1F, location) > 0;
```

- 未使用临时实现的站点只需执行 `ALTER TABLE`。旧会话记录会继续按旧格式显示，直到过期。

#### [Required] 执行 UCenter 密码字段扩容

- SQL：

```sql
ALTER TABLE uc_members
  MODIFY COLUMN password varchar(255) NOT NULL DEFAULT '',
  MODIFY COLUMN salt varchar(20) NOT NULL DEFAULT '';
```

- 适用对象：存量 X3.5 站点
- 来源：`README.md`

#### [Required] 审核并执行 `install/sql/sql_upgrade_3.5.php`

- 当前分支新增的升级脚本是部署存量站点的主入口，不建议只手工补一两条 SQL。
- 该文件已包含较大范围的结构变更，例如：
  - 用户名/作者字段批量扩容到 50
  - `pre_common_member.loginname`
  - `pre_common_member_username_history`
  - `pre_common_member_account`
  - `pre_forum_thread.summary`
  - `pre_common_session` 重建
  - `pre_forum_post` 的 `content` / `source` / `original` / `bestanswer`
  - `pre_common_member_profile` / `pre_common_member_profile_history` 的 `fields` JSON 字段
  - `pre_common_log`、`pre_common_emaillog`
  - RESTful 相关表
  - `pre_common_editorblock`
  - `pre_common_stylevar_extra`
- 适用对象：已有站点从旧代码升级到当前分支
- 来源：`install/sql/sql_upgrade_3.5.php`

#### [Required] 清理已移除功能的遗留结构

- 当前代码和全新安装不再使用下列表。存量站点应先备份数据库，再按实际表前缀执行：

```sql
DROP TABLE IF EXISTS pre_common_devicetoken;
DROP TABLE IF EXISTS pre_common_member_security;
DROP TABLE IF EXISTS pre_common_patch;
DROP TABLE IF EXISTS pre_common_sphinxcounter;
DROP TABLE IF EXISTS pre_home_docomment_recomend_log;
DROP TABLE IF EXISTS pre_mobile_setting;
DROP TABLE IF EXISTS pre_mobile_wsq_threadlist;
DROP TABLE IF EXISTS pre_security_evilpost;
DROP TABLE IF EXISTS pre_security_eviluser;
DROP TABLE IF EXISTS pre_security_failedlog;
```

- 如果站点使用非空表前缀，且同时存在无前缀和带前缀的 `common_robot_user_agents`，无前缀表是旧安装脚本产生的重复表，可以删除。表前缀为空时不得执行这条语句：

```sql
DROP TABLE IF EXISTS common_robot_user_agents;
```

- 下列字段已无运行时代码读取。仅在字段确实存在时执行删除；MySQL 5.7 不支持 `DROP COLUMN IF EXISTS`：

```sql
ALTER TABLE pre_forum_attachment_0 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_1 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_2 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_3 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_4 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_5 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_6 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_7 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_8 DROP COLUMN sha1;
ALTER TABLE pre_forum_attachment_9 DROP COLUMN sha1;
ALTER TABLE pre_portal_article_title DROP COLUMN tag;
ALTER TABLE pre_common_usergroup_field DROP COLUMN allowimgcontent;
```

- 删除主题内容转图片功能后，还应移除其遗留设置：

```sql
DELETE FROM pre_common_setting WHERE skey = 'imgcontentwidth';
```

- `pre_common_robot_user_agents`、`pre_common_session.location`、`pre_common_session.referrer`、`pre_forum_thread.tags` 和 `pre_portal_article_title.tags` 仍在使用，不属于清理范围。
- 来源：运行时代码引用审计、`install/sql/sql_install.php`

### Manual review

#### [Manual review] 新表与新字段不建议手工摘抄式迁移

- 如果站点已经明显落后于当前分支，不建议只挑少数表或字段补丁式迁移。
- 推荐做法：
  - 先备份数据库
  - 逐项审查 `install/sql/sql_upgrade_3.5.php`
  - 使用 `install/sql/sql_install.php` 作为当前全新安装结构的唯一基准

## Config / Environment Changes

### 手机模板支持 `{csstemplate}`

- 手机模板 CSS 已纳入风格缓存，源文件路径为 `template/<模板>/touch/common/`。
- 手机缓存使用 `style_<styleid>_touch_*.css`，与 PC 缓存隔离。
- 自定义模板和插件的手机 CSS 覆盖路径分别为：
  - `template/<模板>/touch/common/extend_<文件名>.css`
  - `source/plugin/<插件>/template/touch/extend_<文件名>.css`
- 部署后需要更新 CSS 缓存并清理手机编译模板。

### [Required] PHP 扩展和函数检查

- 安装环境检查会阻止缺少以下必备 PHP 扩展的环境继续安装：
  - `mysqli`
  - `json`
  - `mbstring`
  - `gd`
  - `curl`
  - `openssl`
  - `xml`
  - `filter`
  - `ctype`
  - `spl`
- 检查同时验证各扩展所需的核心函数是否可用，避免扩展被禁用或安装不完整时进入数据库安装阶段后才失败。

### [Required] 数据库备份改由系统 cron 生成

- AdminCP 的数据库备份页面不再执行 PHP 分卷导出，只显示并下载系统 cron 生成的三个固定备份：
  - `backup_monday.sql.gz`
  - `backup_wednesday.sql.gz`
  - `backup_friday.sql.gz`
- cron 必须把文件写入 Discuz! 配置的 `data/backup_<backupdir>/` 目录。
- `<backupdir>` 的实际值仅属于站点部署配置，不得写入受 Git 跟踪的文件、提交信息或公开仓库历史；该目录中的数据库备份文件同样不得提交。
- 管理入口仍为 `/?app=admin&platform=system?action=db&operation=export`。

### [Required] Country/ASN MMDB 运行时依赖

- 当前默认运行时网络归属查询使用 MaxMind DB Reader。
- `source/class/class_ip.php` 和 `source/class/discuz/discuz_application.php` 会加载 `source/class/ip/geoip2.phar`。
- `MaxMind\Db\Reader` 固定读取 `data/ipdata/GeoOpen-Country-ASN.mmdb`。
- 数据库提供国家代码、ASN 和自治系统组织名称，不提供城市。
- 部署时必须准备兼容的 MMDB，并确保 PHP-FPM 运行用户能够读取 PHAR 和 MMDB；否则 IP 网络归属解析及游客会话网络信息补全无法工作。

### [Recommended] 核对后台登录默认配置

- 当前默认配置中已经包含新的后台登录相关项：
  - `$_config['admincp']['synclogin_front']`
  - `$_config['admincp']['qrcode_only']`
  - `$_config['admincp']['validate']['method']`
  - `$_config['admincp']['validate']['user']`
  - `$_config['admincp']['validate']['pass']`
- 部署时应明确这些值是否符合贵站安全策略。
- 来源：`config/config_global_default.php`

### [Recommended] 核对输出与兼容性配置

- 当前默认配置：

```php
$_config['output']['upgradeinsecure'] = 1;
```

- 若站点仍依赖 HTTP 外链资源，请在部署前手工确认；旧 IE 兼容 CSS 已不再支持。

## Notable Changes Compared To master

### 账号、登录与认证

- 登录与账号结构更偏向“多账号 / 登录名”体系，而不只是旧的用户名字段。
- Google Connect 相关流程已经是分支自定义实现，行为与 `master` 不完全一致。
- 部分登录行为已带有更强的运行时判断与自动化路径，例如按账号内容自动判定邮箱登录。

### 论坛、主题与标签

- 标签输入与匹配行为已跟进分支调整，不再沿用旧的空格分隔习惯。
- 主题合并、主题摘要、帖子 JSON 内容等能力已经进入当前分支的结构层与运行层。
- 部分帖子编辑、引用、自动链接与预览逻辑也已偏离 `master`。

### 在线列表、游客与爬虫会话

- 在线列表已改为更动态的实现路径，相关统计、刷新与展示逻辑与 `master` 不同。
- 当前分支对游客 / 爬虫会话、展示名与复用逻辑做了额外处理，运维侧应把它视为分支行为，而不是 `master` 原样行为。

### 聊天与实时更新

- 聊天室与实时回帖通知是当前分支的重要增量，包含更丰富的实时追加、编辑、删除、断线恢复与前端反馈行为。
- 这部分不属于 `master` 的简单小补丁，而是分支自带的持续演进区。

### MathJax、字体与前端渲染

- MathJax 相关渲染、预览、复制、字体切换与主题样式均存在较多分支特化逻辑。
- 字体与数学显示不应再按 `master` 的默认前端预期来判断。

### 后台与应用框架

- 当前分支已经引入更多 X5 后台与应用框架层面的内容，后台菜单、平台菜单、扫码登录相关配置与行为都明显多于 `master`。

### IP 与网络归属

- 默认查询已经切到 Country/ASN 合并 MMDB。
- IP、游客、爬虫、在线列表等行为在当前分支中存在联动，不应再按旧 tiny/wry 路径理解。

### 文件完整性基线

- `source/data/admincp/discuzfiles.md5` 按当前分支实际跟踪文件和 AdminCP 文件校验范围生成。
- 分支新增的 RESTful、现代化模板和静态资源已纳入基线；已删除的旧模板镜像和子功能不再保留清单项。

### HTML5 上传接口命名

- Flash 上传运行时已经移除，上传接口统一使用 `misc.php?mod=upload`。
- 原 `misc.php?mod=swfupload` 路由不再兼容；调用该接口的插件和自定义模板必须改用新路由。
- HTML5 兼容封装由 `static/js/discuz_uploader.js` 提供，全局构造器为 `DiscuzUploader`。第三方 `static/js/webuploader/` 库名保持不变。

## Update Rule

以下情况必须在同一个变更中同步更新 `UPGRADE_NOTES.md`：

- 修改 `install/data/*.sql`
- 修改安装/升级脚本中的结构性逻辑
- 修改必须人工配置的默认项
- 引入新的运行时依赖、外部数据文件或部署前置条件
- 修改认证、登录、会话语义
- 引入会改变站点运维预期的分支专有行为

以下情况通常不需要更新本文档：

- 纯样式微调
- 无部署风险的文案修正
- 已有行为的 no-op / duplicate / 弱变体修复
