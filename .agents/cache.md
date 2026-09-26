# Caches

- The web-process user must own `data/cache`, `data/template`, and `data/sysdata`.
- After CSS changes: `php .agents/tools/rebuild_styles.php`.
- After JavaScript or other versioned static asset changes: `php .agents/tools/update_verhash.php`.
- After template or language changes: clear compiled templates in AdminCP.
- Use HTTPS in browser tests; HTTP can trigger bot classification.
- After template changes from the CLI: `cleartemplatecache()` (see `deployment.md` — no committed tool does this).

## The `setting` cache bakes absolute URLs — never build it under a placeholder host

`updatecache('setting')` stores **fully rendered markup**, including absolute
URLs. `source/function/cache/cache_setting.php:1183` builds the user-nav icons:

```php
$navicon = preg_match('/^(https?:)?\/\//i', $navicon) ? $navicon : $_G['siteurl'].$navicon;
$nav['icon'] = ' style="background-image:url('.$navicon.') !important"';
```

So the base URL at build time is frozen into `pre_common_syscache`. Building it
from a CLI context that resolves the host to `localhost` stores
`http://localhost/static/image/feed/thread_b.png` permanently, the icons fail to
load, and the user dropdown renders see-through over the page behind it. 62 such
URLs were stored on 2026-09-26 before this was caught.

Build it with the real host:

```sh
sudo -u www-data php .agents/tools/rebuild_cache.php --cachename=setting
```

The tool defaults to `kuing.cjhb.site`, refuses `localhost`/`127.0.0.1`, and
verifies `$_G['siteurl']` after bootstrap and aborts before writing if it does
not match. Verify afterwards:

```sh
mysql -u root ultrax -N -B -e "SELECT data FROM pre_common_syscache WHERE cname='setting';" \
  | grep -c 'localhost'      # must be 0
```

If it is non-zero, delete the row (`DELETE FROM pre_common_syscache WHERE
cname='setting'`) and rebuild — but **only** with the corrected tool, or the same
corruption returns. Deleting the row alone leaves the site working (it computes
settings at runtime) but with nav icons missing until the cache is rebuilt.

## Writing a CLI bootstrap for Discuz

`require './source/class/class_core.php'` **wipes the global scope**. Anything
assigned before it is gone by the time `C::app()->init()` returns:

```php
$buildHost = 'kuing.cjhb.site';
require_once './source/class/class_core.php';
var_dump($buildHost);   // NULL
```

This is silent and cost several rounds here — a pre-init guard passed, then the
post-init guard saw an empty variable and computed `https:///`. **Pass values
across the bootstrap in constants, not variables:**

```php
define('DZ_BUILD_HOST', $host);
```

`$_SERVER['HTTP_HOST']` and friends *are* safe to set before the require, since
Discuz reads them during `init()` rather than relying on caller globals.

