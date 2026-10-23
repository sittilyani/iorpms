<?php
/** Backup file naming: <Facility_Name>_methadone_<dd-mm-YYYY-HH-ii-ss>.sql (facility from Facility Setup). */
if (!function_exists('backup_facility_prefix')) {
    function backup_facility_prefix(mysqli $conn): string
    {
        $r = @$conn->query("SELECT facilityname FROM facility_settings LIMIT 1");
        $name = ($r && ($row = $r->fetch_row())) ? (string)$row[0] : '';
        $slug = trim((string)(preg_replace('/[^A-Za-z0-9]+/', '_', $name)), '_');
        return ($slug !== '' ? $slug . '_' : '') . 'methadone';
    }
    function backup_file_name(mysqli $conn, string $dateFormat = 'd-m-Y-H-i-s'): string
    {
        return backup_facility_prefix($conn) . '_' . date($dateFormat) . '.sql';
    }
}
