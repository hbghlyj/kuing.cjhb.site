# Runtime

- GeoIP needs readable `data/ipdata/GeoOpen-Country-ASN.mmdb` and `source/class/ip/geoip2.phar`.
- Missing avatars render deterministic username initials; pass `avatarstatus` to avoid nonexistent-avatar requests.
- `forum_forum.name` is a language-keyed JSON map. Use the localization helper.
- `forum_forum.lastpost`: `tid\tdateline\tauthor\tsubject`.
