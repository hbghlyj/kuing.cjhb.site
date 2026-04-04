# 分支升级说明

本文档描述当前分支相对 `master` 的差异，面向站点维护者与部署人员，不是完整 changelog。

## Compared To

- 对比基线：本地 `master`
- 基线提交：`019032ed4242fab35fc6299f022bb5e9a4631273`
- 当前分支说明：包含大量高于 `master` 的功能、行为与运维侧变更，因此本文档只记录部署相关、兼容性相关、数据库相关与较高信号的分支差异

## Must Review Before Deploy

- 这是部署说明，不是功能宣传页。每次更新代码前，应先阅读本文档与 `README.md`。
- 对于存量站点，不要只覆盖代码。至少需要同步检查：
  - `install/data/upgrade.sql`
  - `install/data/install.sql`
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
- 这些变更不应手工挑选性跳过。对于已有站点，应完整评估并执行 `install/data/upgrade.sql`。
- 来源：`install/data/upgrade.sql`

### [Manual review] 旧 IP 数据文件不再是默认运行路径

- 当前默认运行时地理位置查询已经切换到 MaxMind GeoIP2。
- 老的 `tinyipdata.dat` / `wry` 系列数据文件不再是当前默认查询路径，但仓库中仍保留了旧后端相关代码，不能简单按“文件是否存在”判断是否切换完成。
- 因此部署时应按当前实际运行路径核对，而不是沿用旧数据文件部署习惯。

### [Manual review] 输出策略默认值与旧站点预期不同

- 当前默认输出策略为：

```php
$_config['output']['upgradeinsecure'] = 1;
$_config['output']['css4legacyie'] = 0;
```

- 这意味着：
  - HTTPS 环境下会默认要求浏览器升级 HTTP 内链到 HTTPS
  - 默认不再加载兼容低版本 IE 的额外 CSS
- 若旧站点依赖外域 HTTP 资源或旧 IE 呈现，应在部署前先评估。
- 来源：`config/config_global_default.php`、`README.md`

## Database Migrations Required

### Required before deploy

#### [Required] 执行 UCenter 密码字段扩容

- SQL：

```sql
ALTER TABLE uc_members
  MODIFY COLUMN password varchar(255) NOT NULL DEFAULT '',
  MODIFY COLUMN salt varchar(20) NOT NULL DEFAULT '';
```

- 适用对象：存量 X3.5 站点
- 来源：`README.md`

#### [Required] 审核并执行 `install/data/upgrade.sql`

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
- 来源：`install/data/upgrade.sql`

### Recommended / situational

#### [Recommended] 对照 `install/data/install.sql` 检查 install-only 表

- 有些新表只出现在安装 SQL 中，未必已经包含在升级路径里。对于从旧站点长期演进、又准备启用相关功能的站点，应比对并确认是否已经存在。
- 当前至少应人工检查：
  - `pre_common_devicetoken`
  - `pre_mobile_setting`
- 适用场景：
  - 启用移动端相关能力
  - 从较老分支直接跳到当前分支
  - 历史升级路径不完整
- 来源：`install/data/install.sql`

### Manual review

#### [Manual review] 新表与新字段不建议手工摘抄式迁移

- 如果站点已经明显落后于当前分支，不建议只挑少数表或字段补丁式迁移。
- 推荐做法：
  - 先备份数据库
  - 逐项审查 `install/data/upgrade.sql`
  - 再根据站点实际启用功能补充检查 `install/data/install.sql`

## Config / Environment Changes

### [Required] MaxMind GeoIP2 运行时依赖

- 当前默认运行时地理位置查询使用 MaxMind GeoIP2。
- 运行时路径：
  - `source/class/class_ip.php`
  - `source/class/ip/geoip2.phar`
  - `data/ipdata/GeoLite2-City.mmdb`
- 部署时必须自行准备可用的 `GeoLite2-City.mmdb`，否则 IP 归属地解析无法按当前默认实现工作。

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
$_config['output']['css4legacyie'] = 0;
```

- 若站点仍依赖 HTTP 外链资源或旧 IE 兼容模式，请在部署前手工确认。

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

### IP 与地理位置

- 默认地理位置查询已经切到 GeoIP2。
- IP、游客、爬虫、在线列表等行为在当前分支中存在联动，不应再按旧 tiny/wry 路径理解。

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
