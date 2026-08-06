# Caches

- The web-process user must own `data/cache`, `data/template`, and `data/sysdata`.
- After CSS changes: `php .agents/tools/rebuild_styles.php`.
- After JavaScript or other versioned static asset changes: `php .agents/tools/update_verhash.php`.
- After template or language changes: clear compiled templates in AdminCP.
- Use HTTPS in browser tests; HTTP can trigger bot classification.
