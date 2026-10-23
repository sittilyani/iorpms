<?php
/**
 * includes/migrations.php
 *
 * Idempotent database migration runner used by the GitHub updater
 * (update.php). Every file in updates/migrations/ is applied once per
 * clinic database and recorded in `schema_migrations`, so a clinic that is
 * several versions behind simply runs everything it has not yet seen, in
 * filename order.
 *
 *   NNN_description.sql  - plain SQL (statements separated by ";" at line end)
 *   NNN_description.php  - receives $conn (mysqli) and may use the mig_* helpers
 *
 * Write migrations defensively (IF NOT EXISTS, mig_add_column ...) so they
 * are also safe on a database that already has the change.
 */

if (!function_exists('mig_table_exists')) {
    function mig_table_exists(mysqli $conn, string $table): bool
    {
        $t = $conn->real_escape_string($table);
        $r = $conn->query("SHOW TABLES LIKE '$t'");
        return $r && $r->num_rows > 0;
    }

    function mig_column_exists(mysqli $conn, string $table, string $column): bool
    {
        if (!mig_table_exists($conn, $table)) return false;
        $t = str_replace('`', '', $table);
        $c = $conn->real_escape_string($column);
        $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
        return $r && $r->num_rows > 0;
    }

    /** ALTER TABLE ... ADD COLUMN only when the column is missing. */
    function mig_add_column(mysqli $conn, string $table, string $column, string $definition): void
    {
        if (mig_column_exists($conn, $table, $column)) return;
        $t = str_replace('`', '', $table);
        $c = str_replace('`', '', $column);
        if (!$conn->query("ALTER TABLE `$t` ADD COLUMN `$c` $definition")) {
            throw new Exception("Add column $table.$column failed: " . $conn->error);
        }
    }

    function mig_exec(mysqli $conn, string $sql): void
    {
        if (!$conn->query($sql)) {
            throw new Exception($conn->error . ' [' . substr(trim((string)($sql)), 0, 120) . ']');
        }
    }
}

/**
 * Apply all pending migrations.
 * @return array{applied:string[],failed:?string,skipped:int}
 */
function run_migrations(mysqli $conn, string $dir): array
{
    $result = ['applied' => [], 'failed' => null, 'skipped' => 0];

    $conn->query(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(190) NOT NULL UNIQUE,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $done = [];
    $res = $conn->query("SELECT migration FROM schema_migrations");
    while ($res && ($row = $res->fetch_assoc())) {
        $done[$row['migration']] = true;
    }

    $files = array_merge(glob($dir . '/*.sql') ?: [], glob($dir . '/*.php') ?: []);
    sort($files, SORT_STRING);

    foreach ($files as $file) {
        $name = basename($file);
        if (isset($done[$name])) {
            $result['skipped']++;
            continue;
        }
        try {
            if (substr($name, -4) === '.sql') {
                $sql = file_get_contents($file);
                foreach (preg_split('/;\s*[\r\n]+/', $sql) as $stmt) {
                    $stmt = trim((string)(preg_replace('/^\s*--.*$/m', '', $stmt)));
                    if ($stmt !== '') {
                        mig_exec($conn, rtrim((string)($stmt), ';'));
                    }
                }
            } else {
                (static function (mysqli $conn, string $__file) {
                    require $__file;
                })($conn, $file);
            }
            $ins = $conn->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
            $ins->bind_param('s', $name);
            $ins->execute();
            $ins->close();
            $result['applied'][] = $name;
        } catch (Throwable $e) {
            // Stop at the first failure so later migrations never run on a
            // half-migrated schema; the next update run retries this one.
            $result['failed'] = $name . ': ' . $e->getMessage();
            break;
        }
    }
    return $result;
}

/** Run every updates/repeatable/*.php on each update (must be idempotent). */
function run_repeatable(mysqli $conn, string $dir): array
{
    $out = ['ran' => [], 'failed' => null];
    $files = glob($dir . '/*.php') ?: [];
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        try {
            (static function (mysqli $conn, string $__file) {
                require $__file;
            })($conn, $file);
            $out['ran'][] = basename($file);
        } catch (Throwable $e) {
            $out['failed'] = basename($file) . ': ' . $e->getMessage();
            break;
        }
    }
    return $out;
}
