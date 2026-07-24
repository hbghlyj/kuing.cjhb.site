# Deployment

- Review `install/sql/sql_upgrade_3.5.php` before upgrading an existing X3.5 database.
- Never commit installation-specific backup paths.
- After CSS changes, run:

```sh
php tools/rebuild_styles.php --host=kuing.cjhb.site
```

Use the public host. The tool compiles scheme-relative asset URLs.
