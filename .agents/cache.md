# Caches

- The web-process user must own `data/cache`, `data/template`, and `data/sysdata`.
- After CSS changes: `php tools/rebuild_styles.php --host=kuing.cjhb.site`.
- After template or language changes: clear compiled templates in AdminCP.
- Use HTTPS in browser tests; HTTP can trigger bot classification.
