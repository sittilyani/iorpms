<?php
/**
 * includes/dose_schedule_helpers.php
 *
 * Shared helpers for "alternate dosing" (e.g. Buprenorphine dispensed every
 * 2nd or 3rd day). A dose_schedules row can now carry a dosing_interval_days
 * value:
 *   1 (or NULL)  = daily dosing (normal / default behaviour, e.g. Methadone)
 *   2            = every other day (alternate day dosing)
 *   3            = every 3rd day
 *   ...etc
 *
 * skip_dates continues to work as an explicit, one-off list of extra dates
 * to exclude (e.g. a public holiday) on top of whatever pattern is set.
 *
 * These helpers are the single source of truth for "was a dose actually due
 * on this date for this client" so that missed-dose reporting (pharmacy
 * dispensing screen, view-missed.php, view_missed_fivedays.php) agrees with
 * what the clinician prescribed, instead of assuming every calendar day is
 * a dose day.
 *
 * Requires $conn to be a connected mysqli instance.
 */

if (!function_exists('dsh_parse_skip_dates')) {
    /**
     * Parse a comma-separated skip_dates string into a clean array of
     * 'YYYY-MM-DD' strings.
     */
    function dsh_parse_skip_dates(?string $skipDates): array
    {
        if (empty($skipDates)) {
            return [];
        }
        $parts = array_map('trim', explode(',', $skipDates));
        return array_values(array_filter($parts, fn($d) => $d !== ''));
    }
}

if (!function_exists('isDoseDueOnDate')) {
    /**
     * Given a dose_schedules row (associative array with at least
     * start_date, skip_dates, dosing_interval_days) determine whether a
     * dose was actually due on the given date.
     */
    function isDoseDueOnDate(array $schedule, string $date): bool
    {
        // An explicit skip date always wins, regardless of pattern.
        $skips = dsh_parse_skip_dates($schedule['skip_dates'] ?? null);
        if (in_array($date, $skips, true)) {
            return false;
        }

        $interval = isset($schedule['dosing_interval_days']) && $schedule['dosing_interval_days'] !== null
            ? (int)$schedule['dosing_interval_days']
            : 1;

        if ($interval <= 1) {
            return true; // daily dosing — every day is a dose day
        }

        if (empty($schedule['start_date'])) {
            return true; // no anchor date to calculate from, assume due
        }

        try {
            $start = new DateTime($schedule['start_date']);
            $check = new DateTime($date);
        } catch (Exception $e) {
            return true;
        }

        if ($check < $start) {
            return false; // before this schedule started
        }

        $daysSinceStart = (int)$start->diff($check)->days;
        return ($daysSinceStart % $interval) === 0;
    }
}

if (!function_exists('dsh_get_schedules_in_range')) {
    /**
     * Fetch all dose_schedules rows (any non-cancelled status) that overlap
     * the given date range for a patient, ordered oldest-first.
     */
    function dsh_get_schedules_in_range(mysqli $conn, string $mat_id, string $startDate, string $endDate): array
    {
        $stmt = $conn->prepare(
            "SELECT id, dose_mg, start_date, end_date, skip_dates, dosing_interval_days, status
             FROM dose_schedules
             WHERE mat_id = ?
               AND status != 'cancelled'
               AND start_date <= ?
               AND (end_date IS NULL OR end_date >= ?)
             ORDER BY start_date ASC"
        );
        $stmt->bind_param('sss', $mat_id, $endDate, $startDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('dsh_schedule_covering_date')) {
    /**
     * From a pre-fetched list of schedules (see dsh_get_schedules_in_range),
     * find the one that covers a given date. If more than one somehow
     * overlaps, the one with the latest start_date wins.
     */
    function dsh_schedule_covering_date(array $schedules, string $date): ?array
    {
        $match = null;
        foreach ($schedules as $sc) {
            $scStart = $sc['start_date'];
            $scEnd   = $sc['end_date'] ?: '9999-12-31';
            if ($date >= $scStart && $date <= $scEnd) {
                if ($match === null || $sc['start_date'] >= $match['start_date']) {
                    $match = $sc;
                }
            }
        }
        return $match;
    }
}

if (!function_exists('computeDoseAdherence')) {
    /**
     * Walk every day in [$startDate, $endDate] and classify it as:
     *   - 'dispensed'   : a dispensing record with dosage > 0 exists that day
     *   - 'missed'      : a dose was due (per schedule/pattern) but not dispensed
     *   - 'off_pattern' : an alternate-dosing "skip" day — not due, not counted as missed
     *   - 'no_schedule' : no active dose schedule covered this date
     *
     * Returns an array with per-day classification plus rollup counts.
     */
    function computeDoseAdherence(mysqli $conn, string $mat_id, string $startDate, string $endDate): array
    {
        $result = [
            'days'             => [],
            'due_count'        => 0,
            'missed_count'     => 0,
            'dispensed_count'  => 0,
            'off_pattern_count'=> 0,
            'missed_dates'     => [],
        ];

        if ($startDate > $endDate) {
            return $result;
        }

        $schedules = dsh_get_schedules_in_range($conn, $mat_id, $startDate, $endDate);

        $dispensedDates = [];
        $dStmt = $conn->prepare(
            "SELECT DISTINCT DATE(dispDate) AS d FROM pharmacy
             WHERE mat_id = ? AND DATE(dispDate) BETWEEN ? AND ? AND dosage > 0"
        );
        $dStmt->bind_param('sss', $mat_id, $startDate, $endDate);
        $dStmt->execute();
        $res = $dStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $dispensedDates[$row['d']] = true;
        }
        $dStmt->close();

        $cursor = new DateTime($startDate);
        $end    = new DateTime($endDate);

        while ($cursor <= $end) {
            $d = $cursor->format('Y-m-d');
            $schedule = dsh_schedule_covering_date($schedules, $d);

            if (isset($dispensedDates[$d])) {
                $result['days'][$d] = 'dispensed';
                $result['dispensed_count']++;
                $result['due_count']++;
            } elseif ($schedule === null) {
                $result['days'][$d] = 'no_schedule';
            } elseif (isDoseDueOnDate($schedule, $d)) {
                $result['days'][$d] = 'missed';
                $result['missed_count']++;
                $result['due_count']++;
                $result['missed_dates'][] = $d;
            } else {
                $result['days'][$d] = 'off_pattern';
                $result['off_pattern_count']++;
            }

            $cursor->modify('+1 day');
        }

        return $result;
    }
}
