# Runtime

- GeoIP needs readable `data/ipdata/GeoOpen-Country-ASN.mmdb` and `source/class/ip/geoip2.phar`.
- Missing avatars render deterministic username initials; pass `avatarstatus` to avoid nonexistent-avatar requests.
- `common_setting.bbname`, `common_setting.sitename`, `common_nav.name`, `forum_onlinelist.title`, and `forum_forum.name` are language-keyed JSON maps. Use the localization helpers.
- `common_nav.name.EN` supplies the HTML `title`; there is no separate navigation-title field.
- `forum_forum.lastpost`: `tid\tdateline\tauthor\tsubject`.
