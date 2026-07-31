# Database

- **Database Safety**: Always perform a database backup (e.g. running `/usr/local/sbin/kuing-db-backup` or creating a timestamped dump) before executing any direct database operations, batch updates, or migrations on the live database.
- **Full Backup Before Partial**: Always take a full database backup (all tables) before taking any partial/selective backup. Never rely on a partial backup as the sole safety net.
- **Confirm Before Overwrite**: Always explicitly confirm with the user before overwriting any existing database table or data. Show clearly which tables/rows will be affected and get user approval before proceeding.
- **Never Assume Tables Are Unchanged**: Never assume a table is still in the same state as when the last backup was taken. Always compare the current live table against the backup before performing any restore or overwrite — changes (user edits, new rows, updates) may have occurred since the backup.
