<?php
/**
 * includes/pharmacy_helpers.php
 *
 * Shared dispensing helpers:
 *   - conversion of a dispensed dose into what physically leaves the shelf
 *     (Methadone: mg -> mL, Buprenorphine: mg -> tablets). The value is
 *     calculated here, BEFORE the row is inserted into `pharmacy`
 *     (`pharmacy.conversion`, NULL for everything else) - it is not a
 *     generated column.
 *   - Buprenorphine multi-strength dispensing (2 / 4 / 8 mg tablets), the
 *     per-client "remembered" formulation, stock deduction and the bin card
 *     ledger (`bin_card_entries`).
 *
 * Requires a connected mysqli $conn. Tables/columns used here are created
 * by updates/migrations/ (see includes/migrations.php).
 */

if (!defined('METHADONE_MG_PER_ML')) {
    define('METHADONE_MG_PER_ML', 5.0);
}

if (!function_exists('ph_is_methadone')) {
    function ph_is_methadone(?string $drugname): bool
    {
        return $drugname !== null && stripos($drugname, 'methadone') !== false;
    }
}

if (!function_exists('ph_is_buprenorphine')) {
    function ph_is_buprenorphine(?string $drugname): bool
    {
        return $drugname !== null && stripos($drugname, 'buprenorphine') !== false;
    }
}

if (!function_exists('ph_strength_from_name')) {
    /** "Buprenorphine 8mg" -> 8.0 ; no strength in the name -> null */
    function ph_strength_from_name(?string $drugname): ?float
    {
        if ($drugname !== null && preg_match('/(\d+(?:\.\d+)?)\s*mg/i', $drugname, $m)) {
            $v = (float)$m[1];
            return $v > 0 ? $v : null;
        }
        return null;
    }
}

if (!function_exists('ph_conversion')) {
    /**
     * Calculate the `conversion` column for a dispensed dose.
     * Returns [value|null, unit|null]. $mgPerMl lets pump flows pass the
     * calibrated concentration.
     */
    function ph_conversion(?string $drugname, float $dosage, ?float $mgPerMl = null): array
    {
        if (ph_is_methadone($drugname)) {
            $conc = ($mgPerMl !== null && $mgPerMl > 0) ? $mgPerMl : METHADONE_MG_PER_ML;
            return [round($dosage / $conc, 2), 'mL'];
        }
        if (ph_is_buprenorphine($drugname)) {
            $strength = ph_strength_from_name($drugname);
            if ($strength !== null) {
                return [round($dosage / $strength, 2), 'tablets'];
            }
        }
        return [null, null];
    }
}

if (!function_exists('ph_column_exists')) {
    function ph_column_exists(mysqli $conn, string $table, string $column): bool
    {
        $t = $conn->real_escape_string($table);
        $c = $conn->real_escape_string($column);
        $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
        return $r && $r->num_rows > 0;
    }
}

/* ------------------------------------------------------------------ *
 *  Buprenorphine multi-strength dispensing
 * ------------------------------------------------------------------ */

if (!function_exists('bup_strengths')) {
    /**
     * Buprenorphine formulations configured in the `drug` table
     * (e.g. "Buprenorphine 2mg"), keyed by drug name, smallest first.
     * @return array<string,array{drugID:int,name:string,mg:float}>
     */
    function bup_strengths(mysqli $conn): array
    {
        $out = [];
        $res = $conn->query("SELECT drugID, drugName FROM drug WHERE drugName LIKE 'Buprenorphine%'");
        while ($res && ($row = $res->fetch_assoc())) {
            $mg = ph_strength_from_name($row['drugName']);
            if ($mg !== null) {
                $out[$row['drugName']] = ['drugID' => (int)$row['drugID'], 'name' => $row['drugName'], 'mg' => $mg];
            }
        }
        uasort($out, fn($a, $b) => $a['mg'] <=> $b['mg']);
        return $out;
    }
}

if (!function_exists('bup_components_mg')) {
    /** Total mg of a [drugname => tablets] selection. */
    function bup_components_mg(array $components, array $strengths): float
    {
        $mg = 0.0;
        foreach ($components as $name => $tabs) {
            if (isset($strengths[$name])) {
                $mg += $strengths[$name]['mg'] * (float)$tabs;
            }
        }
        return round($mg, 2);
    }
}

if (!function_exists('bup_describe')) {
    /** [drugname => tablets] -> "1 x 8mg + 1 x 4mg" */
    function bup_describe(array $components, array $strengths): string
    {
        $parts = [];
        foreach ($strengths as $name => $s) {
            $t = (float)($components[$name] ?? 0);
            if ($t > 0) {
                $parts[] = rtrim(rtrim(number_format($t, 2, '.', ''), '0'), '.') . ' x ' . rtrim(rtrim(number_format($s['mg'], 2, '.', ''), '0'), '.') . 'mg';
            }
        }
        return implode(' + ', array_reverse($parts));
    }
}

if (!function_exists('bup_get_saved')) {
    /** Last formulation chosen for this client: [drugname => tablets] (may be empty). */
    function bup_get_saved(mysqli $conn, string $mat_id): array
    {
        if (!ph_column_exists($conn, 'patient_bup_formulation', 'components')) {
            return [];
        }
        $stmt = $conn->prepare("SELECT components FROM patient_bup_formulation WHERE mat_id = ?");
        $stmt->bind_param('s', $mat_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $arr = $row ? json_decode($row['components'], true) : [];
        return is_array($arr) ? array_map('floatval', $arr) : [];
    }
}

if (!function_exists('bup_save')) {
    /** Remember the formulation so it persists until the dispenser changes it. */
    function bup_save(mysqli $conn, string $mat_id, array $components, float $dose_mg): void
    {
        $json = json_encode($components);
        $stmt = $conn->prepare(
            "INSERT INTO patient_bup_formulation (mat_id, components, dose_mg)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE components = VALUES(components), dose_mg = VALUES(dose_mg)"
        );
        $stmt->bind_param('ssd', $mat_id, $json, $dose_mg);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('bup_default_split')) {
    /** Fewest-tablets split of $dose_mg, used only to pre-fill when nothing is saved. */
    function bup_default_split(float $dose_mg, array $strengths): array
    {
        $left = round($dose_mg, 2);
        $out = [];
        foreach (array_reverse($strengths, true) as $name => $s) {
            $n = floor(($left + 1e-9) / $s['mg'] * 2) / 2;   // whole or half tablets
            if ($n > 0) {
                $out[$name] = $n;
                $left = round($left - $n * $s['mg'], 2);
            }
        }
        return $left > 0.001 ? [] : $out;
    }
}

if (!function_exists('bup_parse_post')) {
    /** POST bup_tabs[<drugname>] = n  ->  [drugname => n] (positive only, known strengths only). */
    function bup_parse_post(array $post, array $strengths): array
    {
        $out = [];
        foreach (($post['bup_tabs'] ?? []) as $name => $n) {
            $n = round((float)$n * 2) / 2;   // whole or half tablets only
            if ($n > 0 && isset($strengths[$name])) {
                $out[$name] = $n;
            }
        }
        return $out;
    }
}

if (!function_exists('bup_validate')) {
    /** Returns an error string, or null when the selection exactly makes up the dose. */
    function bup_validate(array $components, float $dose_mg, array $strengths): ?string
    {
        if (!$strengths) {
            return "No Buprenorphine formulations (e.g. 'Buprenorphine 2mg') are configured in the drug list.";
        }
        if (!$components) {
            return "Select the Buprenorphine tablet strength(s) to issue.";
        }
        $mg = bup_components_mg($components, $strengths);
        if (abs($mg - $dose_mg) > 0.001) {
            return "Selected tablets total {$mg} mg but the client's dose is {$dose_mg} mg.";
        }
        return null;
    }
}

if (!function_exists('bup_dispense')) {
    /**
     * Dispense a validated Buprenorphine selection. MUST be called inside
     * the caller's open transaction. For each strength it:
     *   1. inserts a `pharmacy` row (drugname = that strength, dosage = mg,
     *      conversion = tablets),
     *   2. deducts the tablets from stock_movements,
     *   3. appends a bin card line (opening bal = previous stock balance).
     *
     * $ctx keys: visitDate, mat_id, mat_number, clientName, nickName, age,
     *            sex, p_address, cso, reasons, current_status, pharm_officer_name
     * Throws Exception on any stock/DB problem. Returns a short summary string.
     */
    function bup_dispense(mysqli $conn, array $ctx, array $components, array $strengths): string
    {
        $group  = 'BUP' . date('ymdHis') . substr(md5(uniqid('', true)), 0, 6);
        $detail = bup_describe($components, $strengths);

        foreach ($components as $name => $tabs) {
            $s  = $strengths[$name];
            $mg = round($s['mg'] * $tabs, 2);

            // Latest stock line for this strength (locked for the update).
            $st = $conn->prepare(
                "SELECT trans_id, total_qty, batch_number FROM stock_movements
                 WHERE drugname = ? ORDER BY trans_date DESC, trans_id DESC LIMIT 1 FOR UPDATE"
            );
            $st->bind_param('s', $name);
            $st->execute();
            $stock = $st->get_result()->fetch_assoc();
            $st->close();

            $available = $stock ? (float)$stock['total_qty'] : 0.0;
            if ($available < $tabs) {
                throw new Exception("$name is out of stock ($available tablet(s) available, need $tabs).");
            }
            $newBal = $available - $tabs;

            $ins = $conn->prepare(
                "INSERT INTO pharmacy
                    (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso,
                     drugname, dosage, reasons, current_status, pharm_officer_name,
                     conversion, conversion_unit, formulation_detail, dispense_group)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'tablets', ?, ?)"
            );
            $tabsF = (float)$tabs;
            $ins->bind_param(
                'ssssssssssdsssdss',
                $ctx['visitDate'], $ctx['mat_id'], $ctx['mat_number'], $ctx['clientName'], $ctx['nickName'],
                $ctx['age'], $ctx['sex'], $ctx['p_address'], $ctx['cso'],
                $name, $mg, $ctx['reasons'], $ctx['current_status'], $ctx['pharm_officer_name'],
                $tabsF, $detail, $group
            );
            if (!$ins->execute()) {
                throw new Exception("Could not record $name dispensing: " . $ins->error);
            }
            $dispId = (int)$conn->insert_id;
            $ins->close();

            $upd = $conn->prepare("UPDATE stock_movements SET total_qty = total_qty - ? WHERE trans_id = ?");
            $upd->bind_param('di', $tabsF, $stock['trans_id']);
            $upd->execute();
            $upd->close();

            // Bin card line: received-from blank, opening = previous balance.
            $bc = $conn->prepare(
                "INSERT INTO bin_card_entries
                    (trans_date, drugID, drugname, mat_id, mat_number, batch_number, received_from,
                     opening_balance, qty_in, qty_out_tablets, qty_out_mg, stock_balance, disp_id, issued_by)
                 VALUES (?, ?, ?, ?, ?, ?, '', ?, NULL, ?, ?, ?, ?, ?)"
            );
            $batch = $stock['batch_number'];
            $drugId = $s['drugID'];
            $bc->bind_param(
                'sissssddddis',
                $ctx['visitDate'], $drugId, $name, $ctx['mat_id'], $ctx['mat_number'], $batch,
                $available, $tabsF, $mg, $newBal, $dispId, $ctx['pharm_officer_name']
            );
            if (!$bc->execute()) {
                throw new Exception("Could not write bin card entry: " . $bc->error);
            }
            $bc->close();
        }

        return $detail;
    }
}
