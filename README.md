# kuing.cjhb.site

This repository contains the source code for the kuing.cjhb.site forum and documentation site. The platform is based on [DiscuzX](https://gitee.com/Discuz/DiscuzX) with [DocPHT](https://github.com/docpht/docpht) used to manage static documentation. Mathematical expressions are rendered with [MathJax](https://www.mathjax.org/), and diagrams are generated through Upmath and Asymptote services.

## Running locally

1. Install PHP (8.0 or newer) and a MySQL-compatible database such as MariaDB.
2. Install composer dependencies:
   ```bash
   composer install
   ```
3. Copy `config/config_global_default.php` to `config/config_global.php` and configure the database connection.
4. Import the database schema from the `install` directory.
5. Launch the site locally:
   ```bash
   php -S localhost:8080
   ```
   Then open `http://localhost:8080` in your browser.

## Acknowledgements

The project uses the following open source tools:
* [Discuz/DiscuzX](https://gitee.com/Discuz/DiscuzX)
* [MathJax](https://www.mathjax.org/)
* [DocPHT](https://github.com/docpht/docpht)

The following services are relied upon for mathematical content:
* [Upmath LaTeX Renderer](https://github.com/parpalak/i.upmath.me)
* [Asymptote Command-Line Interface](https://asymptote.sourceforge.io/doc/Command_002dLine-Interface.html)



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

