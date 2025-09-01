This repository contains the source code for the site https://kuing.cjhb.site/. The platform is based on [DiscuzX](https://gitee.com/Discuz/DiscuzX) with [DocPHT](https://github.com/docpht/docpht) used to manage static documentation. Mathematical expressions are rendered with [MathJax](https://www.mathjax.org/), and diagrams are generated through [Upmath LaTeX Renderer](https://github.com/parpalak/i.upmath.me) and [Asymptote Command-Line Interface](https://asymptote.sourceforge.io/doc/Command_002dLine-Interface.html) services.

The repository root contains code from both projects:

- **DocPHT:** `json/`, `pages/`, `public/`, `src/`, `temp/`, `vendor/`
- **DiscuzX:** `api/`, `archiver/`, `config/`, `data/`, `install/`, `source/`, `static/`, `template/`, `uc_client/`, `uc_server/`

## DocPHT page storage

This repository includes a very basic "flat" mode where
each page lives as a single Markdown file under the `page/` directory. The new
pageModel can read and write these
files. Pages are shown under `/page/{topic}/{filename}` and logged in users can
edit them at `/page/update`. The controller uses the same
`MediaWikiParsedown` parser employed by the rest of the documentation and saves
the Markdown back to `page/`.

DocPHT stores previous revisions as ZIP archives. The archive filename uses the
page slug combined with the page's last modification time formatted with the
`DATAFORMAT` constant (defined as `Y-m-d H:i`). If a page is saved again within
the same minute, the timestamp does not change so `ZipArchive::CREATE`
overwrites the existing file and the earlier version is lost.
### Translation check
Run `php tests/check_translation_keys.php` to make sure every call to `T::trans()` refers to an existing string in `src/translations/zh_CN.php`.

## Attachment tables

DiscuzX handles attachments using an index table and sharded data tables.

* **Index (`pre_forum_attachment`):** All uploads are indexed in this table. It
  stores the attachment ID (`aid`), thread/post IDs (`tid`, `pid`), author ID
  (`uid`), a download counter, and a `tableid` that identifies the data shard.

* **Posted attachments:** When a post is submitted, `tableid` is set to a value
  from `0`&ndash;`9` pointing to the sharded table
  (`pre_forum_attachment_0`&ndash;`pre_forum_attachment_9`) that stores the full
  record including filename, size and image metadata.

* **Unused attachments:** If a file is uploaded but the post is never submitted:
  * An index entry is created with `tid` and `pid` set to `0` and `tableid`
    set to `127`.
  * The full attachment data is stored in `pre_forum_attachment_unused`.
  * Unused attachments are automatically removed after roughly 24 hours.

### Attachment editing workflow

When a post is edited, `source/include/post/post_editpost.php` rewrites image
attachment tags from `[attach]123[/attach]` to `[attachimg]123[/attachimg]` while
the edit form is displayed. This lets the editor show thumbnails instead of
plain download links. After the user submits the form, the message passes through
`model_forum_post::editpost()` which converts those tags back to the standard
`[attach]` form before the post is saved.

Attachment cleanup for the post being edited is handled inside `updateattach()`
in `source/function/function_post.php`. When the edit is submitted, this
function associates newly added attachments from the
`pre_forum_attachment_unused` table with the post and deletes any files that
were removed from the message. It only operates on attachments belonging to the
current post.

## Running locally

1. Install PHP (8.0 or newer) and a MySQL-compatible database such as MariaDB.
2. Install composer dependencies:
   ```bash
   composer install
   ```
3. Copy `config/config_global_default.php` to `config/config_global.php` and configure the database connection.
4. Import the database schema from the `install` directory. If you need to rerun the Discuz installer, make sure to remove the `data/install.lock` file first so the setup can proceed.

### Migrating thread tags
If you are upgrading from an older version where tags were stored in the `pre_forum_post` table,
execute the following SQL statements:

```sql
ALTER TABLE `pre_forum_thread` ADD COLUMN `tags` varchar(255) NOT NULL DEFAULT "" AFTER `closed`;
UPDATE `pre_forum_thread` t
  INNER JOIN `pre_forum_post` p ON t.tid=p.tid AND p.first=1
  SET t.tags=p.tags;
ALTER TABLE `pre_forum_post` DROP COLUMN `tags`;
```
### Dropping deprecated `allowbegincode` column
If your database still has the legacy `[begin]` permission field, remove it:

```sql
ALTER TABLE `pre_common_usergroup_field` DROP COLUMN `allowbegincode`;
```
### Removing thread stamp and icon columns
Threads no longer support the legacy stamp and icon features. Drop the related fields and permissions:

```sql
ALTER TABLE `pre_forum_thread` DROP COLUMN `stamp`, DROP COLUMN `icon`;
ALTER TABLE `pre_forum_threadmod` DROP COLUMN `stamp`;
ALTER TABLE `pre_common_smiley` DROP COLUMN `type`, DROP COLUMN `typeid`;
ALTER TABLE `pre_common_admingroup` DROP COLUMN `allowstampthread`, DROP COLUMN `allowstamplist`;
```
5. Launch the site locally:
   ```bash
   php -S localhost:8080
   ```
   Then open `http://localhost:8080` in your browser.

### Restoring the Monday backup
If you wish to populate your local installation with sample data, download the
`backup_monday.sql.gz` archive from the project site:

```bash
wget -O backup.sql.gz https://kuing.cjhb.site/uc_server/data/backup_monday.sql.gz
gunzip backup.sql.gz
sed -i 's/utf8mb4_0900_ai_ci/utf8mb4_general_ci/g' backup.sql
mysql -u root ultrax < backup.sql
# migrate thread tags if your schema hasn't been updated
mysql -u root ultrax -e "ALTER TABLE `pre_forum_thread` ADD COLUMN `tags` varchar(255) NOT NULL DEFAULT "" AFTER `closed`;UPDATE pre_forum_thread t INNER JOIN pre_forum_post p ON t.tid=p.tid AND p.first=1 SET t.tags=p.tags;ALTER TABLE pre_forum_post DROP COLUMN tags;"
```

This converts the unsupported `utf8mb4_0900_ai_ci` collation and imports the
contents into the `ultrax` database.

## Database field reference

Below are some common enumerations used in the DiscuzX database schema.

### `forum_thread.status`
Bitwise flags controlling thread behaviour:

| Bit | Meaning |
|----|---------|
|0x0001|Cache post positions|
|0x0002|Replies visible only to managers and author|
|0x0004|Rush thread|
|0x0008|View replies in reverse order|
|0x0010|Has a thread stamp|
|0x0020|Notify author on reply|
|0x0040|Push to QQ Zone|
|0x0080|Push to Tencent Weibo|
|0x0100|Collected to an album|
|0x0200|Rebroadcast|
|0x0400|Mobile text|
|0x0800|Mobile geo/photo|
|0x1000|Mobile recording|
|0x2000|Pushed to Tencent Weibo successfully|
|0x4000|Generated thread image|
|0x8000|Pushed to mobile forum successfully|

### `forum_thread` / `forum_post`.attachment

| Value | Meaning |
|------|---------|
|0|No attachment|
|1|Has attachment|
|2|Image attachment|

### `forum_thread.displayorder`

| Value | Meaning |
|------|---------|
|4|Global sticky|
|3|Sticky level 3|
|2|Sticky level 2|
|1|Sticky level 1|
|0|Normal|
|-1|Recycle bin|
|-2|Under moderation|
|-3|Moderation ignored|
|-4|Draft|

### `forum_post.status`
Bitwise flags for post state:

| Bit | Meaning |
|----|---------|
|0x00000001|Post shielded|
|0x00000002|Post warned|
|0x00000004|Edited after moderation|
|0x00000008|Posted from mobile|
|0x00000010|Weibo backflow|
|0x00000020|Show location on mobile|
|0x00000040|Contains mobile recording|
|0x00000080|Mobile model iOS|
|0x00000100|Mobile model Android/WindowsPhone|
|0x00000200|Mobile model Symbian/WeChat|
|0x00000400|Marked as spam|
|0x00000800|Returned from mobile forum|

### `forum_post.invisible`

| Value | Meaning |
|------|---------|
|0|Normal|
|-1|Recycle bin|
|-2|Pending moderation|
|-3|Ignored/Draft|
|-4|N/A|
|-5|Recycle bin reply|

### `forum_attachment_n.isimage`

`pre_forum_attachment_n` tables (where `n` ranges from 0 to 9) store post attachments. The `isimage` column indicates how the file should be treated:

| Value | Meaning |
|------|---------|
|1|File is an image and used as a standard picture|
|0|File is not an image|
|-1|File is an image but handled like a generic attachment|

See [issue #232](https://github.com/hbghlyj/kuing.cjhb.site/issues/232) for background on these values.


## JavaScript helper functions

`static/js/common.js` provides several utility functions used across the site.

### `ajaxget(url, showid, waitid, loading, display, recall)`
Performs a GET request and injects the result into `showid`. `waitid` shows a loading message. `display` controls whether the element is shown (`''`), hidden (`'none'`), or toggled (`'auto'`). The optional `recall` callback runs after completion.

### `ajaxpost(formid, showid, waitid, showidclass, submitbtn, recall)`
Submits a form asynchronously. The response is written into `showid` and the submit button is disabled during the request. `recall` executes once data is received.

### `ajaxmenu(ctrlObj, timeout, cache, duration, pos, recall)`
Displays a menu near `ctrlObj`. The menu can be cached and is hidden after `timeout` milliseconds depending on `duration`. `pos` defines the positioning and `recall` runs when the menu is shown.

### `showDialog(msg, mode, t, func, cover)`
Opens a dialog box where `mode` can be `info`, `notice`, `alert`, or `confirm`. If confirmed, `func` is called. Set `cover` to block the page with an overlay.

### `showWindow(k, url, mode, cache, v)`
Opens a floating window identified by `k`. Content is loaded via GET or POST according to `mode`. Only one window with the same key is displayed at a time.

### `showMenu(v)`
Shows a configurable menu. The object `v` can include keys such as `ctrlid`, `showid`, `pos`, `duration`, and `timeout`.

### `setMenuPosition(showid, menuid, pos)`
Positions the menu element `menuid` relative to `showid` using the two-digit `pos` code denoting anchor and direction.

### `_ajaxget(url, showid, waitid, loading, display, recall)`
### `_ajaxpost(formid, showid, waitid, showidclass, submitbtn, recall)`
Low level helpers from `static/js/ajax.js` that fetch new content via GET or POST. After receiving HTML, the response is processed by `evalscript()` which in turn uses `appendscript()` from `static/js/common.js` to inject any `<script>` blocks so dynamic features remain functional. These functions are invoked by the global `ajaxget()` and `ajaxpost()` wrappers defined in `static/js/common.js`.

## JavaScript libraries

The file `static/js/webuploader/webuploader.min.js` bundles **WebUploader 0.1.5**, an
open-source uploader from the Baidu FEX team. Download it from their [official releases](https://fex-team.github.io/webuploader/download.html) or from the
[jsDelivr CDN](https://www.jsdelivr.com/package/npm/webuploader). This provides the upload runtime used
by `static/js/webuploader.js`.
