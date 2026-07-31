# Server Error & Operational Logging Guide

## Overview & Architecture

Discuz! X handles logging via two complementary mechanisms:
1. **Operational & Audit Logs (`table_common_log`)**:
   - Driven by `writelog()` in `source/function/function_core.php`.
   - Pluggable storage configured via `$_config['log']['type']` in `config/config_global.php`.
   - On this production server (`$_config['log']['type'] = 'file'`), operational logs are written to flat PHP log files under `data/log/YYYYMM_log_<type>.php` instead of the `pre_common_log` database table.
2. **Direct System & Security Errors (`discuz_error`)**:
   - PHP runtime crashes, SQL errors, and `_xss_check()` security blocks bypass the database and write directly to `data/log/YYYYMM_log_error.php` to guarantee logging even during DB outages.

---

## Log Locations on Server (`root@<server-ip>`)

App Root: `<app-root>`

### 1. Discuz! Application Logs (`data/log/`)
- **System & Security Error Log**: `data/log/YYYYMM_log_error.php`
- **AdminCP Action Log**: `data/log/YYYYMM_log_cp.php`
- **Moderator Action Log**: `data/log/YYYYMM_log_mods.php`

### 2. Web Server & PHP Service Logs
- **Nginx Error Log**: `/var/log/nginx/error.log`
- **PHP-FPM Service Log**: `/var/log/php8.5-fpm.log`

---

## How to Inspect Server Error Logs

Execute these SSH commands to inspect server logs:

### 1. Inspect Recent Discuz! Application Errors
```sh
ssh root@<server-ip> "tail -n 30 <app-root>/data/log/*_log_error.php"
```

### 2. Inspect AdminCP & Moderator Action Logs
```sh
ssh root@<server-ip> "tail -n 30 <app-root>/data/log/*_log_cp.php <app-root>/data/log/*_log_mods.php"
```

### 3. Inspect Nginx & PHP-FPM Service Error Logs
```sh
ssh root@<server-ip> "tail -n 30 /var/log/nginx/error.log /var/log/php8.5-fpm.log"
```

### 4. Query MySQL Database Log Table (If using MySQL log driver)
```sh
ssh root@<server-ip> "mysql -e 'SELECT * FROM ultrax.pre_common_log ORDER BY id DESC LIMIT 10;'"
```
