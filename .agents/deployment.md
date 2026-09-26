# Deployment

- Review `install/sql/sql_upgrade_3.5.php` before upgrading an existing X3.5 database.
- The X5 rating migration converts grouped post ratings to comments, seeds missing binary reply votes, and then drops the rating table, columns, permissions, and settings. Back up the affected tables before applying it; previously awarded credits are intentionally retained.
- Never commit installation-specific backup paths.
- **Deploy with `git pull`**: Always deploy changes to the live server by committing, pushing to git, and executing `git pull` on the server. Never use `scp` to copy individual modified files.
- After CSS changes, run:

```sh
php .agents/tools/rebuild_styles.php
```

The tool compiles root-relative asset URLs.

After JavaScript or other versioned static asset changes, run:

```sh
php .agents/tools/update_verhash.php
```

After **template** changes, run `cleartemplatecache()`. `{lang …}` tokens are expanded when a template is compiled, so a changed key or value will not appear until the compiled templates in `data/template/` are cleared. `updatecache('lang')` is *not* needed — lang files are read from disk per request. A `{lang some_key}` token surviving into `data/template/*.tpl.php` means a stale cache, not a missing key.

There is no committed tool for the template cache; `rebuild_cache.php` only handles named caches via `--cachename=`. Bootstrap Discuz in a CLI script (same pattern as `rebuild_cache.php`: stub `$_SERVER`, `require './source/class/class_core.php'`, `C::app()` with `init_user/init_session/init_cron/init_misc` all false), then call `cleartemplatecache()` from `source/function/function_cache.php`. Run it as `www-data`.

## Database credentials

- `config/config_global.php` holds the site's DB password and must stay **`640 root:www-data`** — php-fpm workers run as `www-data`. It was `644`, i.e. world-readable.
- DB `root@localhost` uses **`unix_socket`** auth, so no root password exists on disk. `/root/.my.cnf` was deleted. `mysql -u root` and `mysqldump` work passwordless over the socket, which is what `/usr/local/sbin/kuing-db-backup` relies on (it passes no credential flags at all). Do not reintroduce a password file; if root ever needs password auth, expect to recreate `/root/.my.cnf`.
- Query the DB through a `mysql --defaults-extra-file` written with `umask 077`, never credentials on a command line. Parse them out of `config_global.php` with PHP. The key is **`dbpw`**, not `dbpass`, and there is a `]` between the key and the `=`. `config_global_default.php` also contains a `dbpw` key but only a short placeholder, and nothing in `source/` loads it.

## Backups

- `/usr/local/sbin/kuing-db-backup` writes to `data/backup_80b536/` **inside the web root**, as `backup_{weekday}_{am|pm}.sql.gz` — only 14 predictable names, ~16 MB each, full database including `pre_common_member` password hashes and private messages.
- nginx blocks these with `location ~* \.(?:sql(?:\.gz)?|bak|old|log)$ { return 404; }`. A `location ^~ /data/backup_80b536/` block used to carve out an exception that made every dump publicly downloadable; it was removed on 2026-09-26 because `^~` outranks the regex deny. **Do not re-add that block.** If remote backup downloads are ever needed again, use IP allowlisting or basic auth rather than an `^~` exception.
- Vhost backups live beside the live config as `/etc/nginx/sites-available/bbs.<timestamp>-before-<change>`; current one is `bbs.20260926-203733-before-close-backup-exposure`.
- Verify exposure from a **browser, not the server** — the server cannot hairpin its own public hostname, so `curl https://kuing.cjhb.site/...` returns `000` from localhost even when the site is fine. Use `curl -H 'Host: kuing.cjhb.site' http://127.0.0.1/...` for server-side health checks, and add a cache-busting query string when re-testing a URL you already fetched, or the browser will serve a stale 200.
