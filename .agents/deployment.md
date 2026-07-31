# Deployment

- Review `install/sql/sql_upgrade_3.5.php` before upgrading an existing X3.5 database.
- The X5 rating migration converts grouped post ratings to comments, seeds missing binary reply votes, and then drops the rating table, columns, permissions, and settings. Back up the affected tables before applying it; previously awarded credits are intentionally retained.
- Never commit installation-specific backup paths.
- **Deploy with `git pull`**: Always deploy changes to the live server by committing, pushing to git, and executing `git pull` on the server. Never use `scp` to copy individual modified files.
- After CSS changes, run:

```sh
php tools/rebuild_styles.php --host=kuing.cjhb.site
```

Use the public host. The tool compiles scheme-relative asset URLs.
