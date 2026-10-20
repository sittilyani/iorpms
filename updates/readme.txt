EasyFlow-L / IORPMS - how updates reach the clinics
===================================================

1. Developer: commit + push to https://github.com/sittilyani/iorpms (branch main).

2. Database changes (new table, new column, seed rows):
   add a numbered file to  updates/migrations/   e.g.  004_add_xyz_column.php
     - .php files get $conn (mysqli) and helpers: mig_add_column(), mig_exec(),
       mig_table_exists(), mig_column_exists()
     - .sql files are plain statements separated by ";" at line end
   Every migration runs ONCE per clinic database (recorded in schema_migrations),
   in filename order, so a clinic that is many versions behind catches up in one go.
   Write them defensively (IF NOT EXISTS / mig_add_column) so re-running is harmless.
   Scripts in updates/repeatable/ run on EVERY update (must be idempotent),
   e.g. backfill_pharmacy_conversion.php.

3. Email the clinic:   http://localhost/iorpms/update.php
   When someone at the clinic opens it (on the Laragon machine, or from their LAN) it:
     - compares every file with GitHub and downloads only the new/changed ones
       (each file is checksum-verified; if any download fails nothing is changed)
     - saves the old copy of each replaced file in backup/update_rollback/<time>/
     - applies the pending database migrations
     - shows a report
   It NEVER touches: clientPhotos/, photos/users/, backup/database/, cache/, upload/,
   includes/config-*.php, SQL dumps, dotfiles.  Files deleted on GitHub are not deleted locally.

   It refuses to run on a Git working copy (developer machine - use git pull) and on the
   online server.  ?dry=1 lists what would change without changing anything.
