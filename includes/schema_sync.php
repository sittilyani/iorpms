<?php
/**
 * includes/schema_sync.php
 *
 * Brings a clinic's database up to the structure of the developer's blank
 * database (blankDB/methadone_blank_db.sql, a phpMyAdmin dump):
 *
 *   - tables missing locally      -> created (CREATE TABLE IF NOT EXISTS), then their
 *                                    keys / auto-increment / foreign keys are applied
 *   - columns missing locally     -> ALTER TABLE ... ADD COLUMN (placed after the same
 *                                    column as in the blank database)
 *   - entries of reference tables -> INSERT IGNORE (rows already present are untouched,
 *                                    nothing is ever updated or deleted)
 *
 * It never drops or changes existing columns/tables and never touches patient,
 * pharmacy, user or any other clinic data: entries are only inserted for the
 * reference tables listed in SCHEMA_SYNC_DATA_TABLES. Triggers, views and
 * routines in the dump are not applied.
 *
 * Every statement is run on its own; a failure is recorded and the sync
 * carries on, so one bad statement cannot leave the rest undone.
 */

/** Reference tables whose rows are copied with INSERT IGNORE. */
const SCHEMA_SYNC_DATA_TABLES = [
    'administrationroutes', 'categories', 'counties', 'countries', 'dosing', 'drug', 'drug_sources',
    'drugcategory', 'durationunits', 'education_level', 'employment_status', 'enrolment_status',
    'facilities', 'formulation', 'hepb_status', 'hepc_status', 'integration_status', 'living_conditions',
    'marital_status', 'nursing_services', 'offences', 'other_status', 'referral_linkage_services',
    'regimen_type', 'regimens', 'reintegration_status', 'religion', 'seq_0_to_30', 'status',
    'sub_counties', 'tb_regimens', 'tb_status', 'tbl_hiv_status', 'tblaccompanment', 'tblgender',
    'tblreferral', 'tpt_regimens', 'transaction_types', 'treatment_stage', 'userroles',
];

/** Split an SQL dump into statements, respecting quotes and comments. */
function schema_sync_split(string $sql): array
{
    // Trigger / routine blocks (DELIMITER $$ ... DELIMITER ;) are skipped
    $sql = preg_replace('/^DELIMITER \$\$.*?^DELIMITER ;[ \t]*$/ms', '', $sql);

    $out = []; $buf = ''; $len = strlen((string)($sql)); $i = 0;
    while ($i < $len) {
        $c = $sql[$i];
        if ($c === "'" || $c === '"' || $c === '`') {
            $q = $c; $j = $i + 1;
            while ($j < $len) {
                if ($sql[$j] === '\\' && $q !== '`') { $j += 2; continue; }
                if ($sql[$j] === $q) {
                    if ($j + 1 < $len && $sql[$j + 1] === $q) { $j += 2; continue; }   // doubled quote
                    break;
                }
                $j++;
            }
            $buf .= substr($sql, $i, $j - $i + 1); $i = $j + 1; continue;
        }
        if ($c === '-' && substr($sql, $i, 2) === '--' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) {
            $nl = strpos($sql, "\n", $i); $i = ($nl === false) ? $len : $nl + 1; continue;
        }
        if ($c === '#') { $nl = strpos($sql, "\n", $i); $i = ($nl === false) ? $len : $nl + 1; continue; }
        if ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
            $end = strpos($sql, '*/', $i + 2); $i = ($end === false) ? $len : $end + 2; continue;
        }
        if ($c === ';') {
            $s = trim((string)($buf)); if ($s !== '') $out[] = $s;
            $buf = ''; $i++; continue;
        }
        $buf .= $c; $i++;
    }
    $s = trim((string)($buf)); if ($s !== '') $out[] = $s;
    return $out;
}

/** "(`a` int, `b` varchar(5) DEFAULT 'x,y')" body -> ordered [name => definition] */
function schema_sync_columns(string $createStmt): array
{
    $start = strpos($createStmt, '(');
    $end = strrpos($createStmt, ')');
    // the closing paren of the column list is the last ")" before ") ENGINE"
    if (preg_match('/\)\s*ENGINE\s*=/i', $createStmt, $m, PREG_OFFSET_CAPTURE)) $end = $m[0][1];
    if ($start === false || $end === false) return [];
    $body = substr($createStmt, $start + 1, $end - $start - 1);

    $parts = []; $depth = 0; $cur = ''; $n = strlen((string)($body));
    for ($i = 0; $i < $n; $i++) {
        $c = $body[$i];
        if ($c === "'" || $c === '"' || $c === '`') {
            $q = $c; $j = $i + 1;
            while ($j < $n) {
                if ($body[$j] === '\\' && $q !== '`') { $j += 2; continue; }
                if ($body[$j] === $q) { if (($body[$j + 1] ?? '') === $q) { $j += 2; continue; } break; }
                $j++;
            }
            $cur .= substr($body, $i, $j - $i + 1); $i = $j; continue;
        }
        if ($c === '(') $depth++;
        if ($c === ')') $depth--;
        if ($c === ',' && $depth === 0) { $parts[] = trim((string)($cur)); $cur = ''; continue; }
        $cur .= $c;
    }
    if (trim((string)($cur)) !== '') $parts[] = trim((string)($cur));

    $cols = [];
    foreach ($parts as $p) {
        if (preg_match('/^`([^`]+)`\s+(.*)$/s', $p, $m)) $cols[$m[1]] = $m[2];
    }
    return $cols;
}

/**
 * @return array{created:string[],columns:string[],rows:array<string,int>,errors:string[],tables:int}
 */
function schema_sync(mysqli $conn, string $dumpFile, ?callable $progress = null): array
{
    $rep = ['created' => [], 'columns' => [], 'rows' => [], 'errors' => [], 'tables' => 0];
    if (!is_file($dumpFile)) { $rep['errors'][] = 'Blank database file not found: ' . basename($dumpFile); return $rep; }

    $stmts = schema_sync_split(file_get_contents($dumpFile));

    $tables = [];   // name => ['create'=>, 'cols'=>, 'alters'=>[], 'fks'=>[], 'inserts'=>[]]
    foreach ($stmts as $s) {
        if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`/i', $s, $m)) {
            $tables[$m[1]] = ['create' => $s, 'cols' => schema_sync_columns($s), 'alters' => [], 'fks' => [], 'inserts' => []];
        } elseif (preg_match('/^ALTER\s+TABLE\s+`([^`]+)`/i', $s, $m) && isset($tables[$m[1]])) {
            $key = (stripos($s, 'ADD CONSTRAINT') !== false) ? 'fks' : 'alters';
            $tables[$m[1]][$key][] = $s;
        } elseif (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`([^`]+)`/i', $s, $m) && isset($tables[$m[1]])) {
            // always IGNORE: existing rows are never overwritten
            $tables[$m[1]]['inserts'][] = preg_replace('/^INSERT\s+(?:IGNORE\s+)?INTO/i', 'INSERT IGNORE INTO', $s);
        }
    }
    $rep['tables'] = count($tables);

    $oldMode = $conn->query('SELECT @@SESSION.sql_mode')->fetch_row()[0] ?? '';
    $conn->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');

    $existing = [];
    $r = $conn->query('SHOW TABLES');
    while ($r && ($row = $r->fetch_row())) $existing[strtolower((string)($row[0]))] = $row[0];

    $run = function (string $sql, string $what) use ($conn, &$rep): bool {
        try {
            if ($conn->query($sql) === false) { $rep['errors'][] = "$what: " . $conn->error; return false; }
            return true;
        } catch (Throwable $e) { $rep['errors'][] = "$what: " . $e->getMessage(); return false; }
    };

    $i = 0; $total = count($tables); $newTables = [];
    foreach ($tables as $name => $t) {
        $i++;
        if ($progress) $progress($i, $total, $name);

        if (!isset($existing[strtolower((string)($name))])) {
            $create = preg_replace('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?/i', 'CREATE TABLE IF NOT EXISTS ', $t['create']);
            if ($run($create, "create $name")) {
                $rep['created'][] = $name;
                $newTables[$name] = true;
                foreach ($t['alters'] as $a) $run($a, "keys $name");
            }
        } else {
            // add missing columns, keeping the blank database's column order
            $local = [];
            $cr = $conn->query("SHOW COLUMNS FROM `$name`");
            while ($cr && ($c = $cr->fetch_assoc())) $local[strtolower((string)($c['Field']))] = true;
            $prev = null;
            foreach ($t['cols'] as $col => $def) {
                if (!isset($local[strtolower((string)($col))])) {
                    $pos = $prev !== null ? "AFTER `$prev`" : 'FIRST';
                    if ($run("ALTER TABLE `$name` ADD COLUMN `$col` $def $pos", "add column $name.$col")) {
                        $rep['columns'][] = "$name.$col";
                        $local[strtolower((string)($col))] = true;
                    }
                }
                $prev = $col;
            }
        }
    }

    // Entries (reference tables only), INSERT IGNORE
    foreach (SCHEMA_SYNC_DATA_TABLES as $name) {
        if (empty($tables[$name]['inserts'])) continue;
        $added = 0;
        foreach ($tables[$name]['inserts'] as $ins) {
            if ($run($ins, "insert $name")) $added += max(0, $conn->affected_rows);
        }
        if ($added > 0) $rep['rows'][$name] = $added;
    }

    // Foreign keys only for tables created in this run
    foreach ($newTables as $name => $_) {
        foreach ($tables[$name]['fks'] as $fk) $run($fk, "foreign keys $name");
    }

    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    $conn->query("SET SESSION sql_mode = '" . $conn->real_escape_string($oldMode) . "'");
    return $rep;
}
