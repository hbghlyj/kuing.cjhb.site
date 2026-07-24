# Deployment

## Database and configuration

Before upgrading a persisted X3.5 database, review and execute the applicable migrations in `install/sql/sql_upgrade_3.5.php`. Do not treat schema changes as a code-only deployment.

The current network lookup uses `data/ipdata/GeoOpen-Country-ASN.mmdb` through the GeoIP2 reader. The PHP-FPM user must be able to read both the MMDB and `source/class/ip/geoip2.phar`.

Backup paths are installation-specific. Never commit or publish their values.

## Rebuild stylesheet cache from CLI

After deploying CSS changes, run this from the site root:

```sh
php tools/rebuild_styles.php --host=kuing.cjhb.site
```

Pass the public site host. The tool builds style cache files with HTTPS request metadata. The CSS compiler emits scheme-relative asset URLs, so browser requests use HTTPS on HTTPS pages.

Clear compiled templates through the AdminCP cache update workflow after template changes. Do not use the stylesheet tool as a replacement for database migrations or the full cache update workflow.
