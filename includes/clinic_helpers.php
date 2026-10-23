<?php
/** Small helpers shared by registration, nursing, encounter and transfer pages. */

if (!function_exists('transfer_signature')) {
    /** Initials + timestamp, e.g. "BO 2026-10-22 14:05" */
    function transfer_signature(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim((string)(preg_replace('/^(Dr\.?|Nurse|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '', $fullName))));
        $ini = '';
        foreach ($parts as $p) { if ($p !== '') $ini .= mb_strtoupper(mb_substr($p, 0, 1)); }
        return $ini . ' ' . date('Y-m-d H:i');
    }
}

if (!function_exists('next_mat_id')) {
    /** Next MAT ID for a facility: <mfl>MAT0090 after <mfl>MAT0089 */
    function next_mat_id(mysqli $conn, string $mfl): string
    {
        $like = $conn->real_escape_string($mfl) . 'MAT%';
        $r = $conn->query("SELECT MAX(CAST(SUBSTRING(mat_id, " . (strlen((string)($mfl)) + 4) . ") AS UNSIGNED)) m FROM patients WHERE mat_id LIKE '$like'");
        $m = (int)(($r ? $r->fetch_assoc()['m'] : 0) ?? 0);
        return $mfl . 'MAT' . str_pad($m + 1, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('referral_department_for_role')) {
    /** Department a referral comes from, judged by the role of the staff member who raised it. */
    function referral_department_for_role(?string $role): string
    {
        $map = ['clinician' => 'Clinical', 'psychiatrist' => 'Psychiatry', 'psychologist' => 'Psychosocial', 'pharmacist' => 'Pharmacy', 'nursing' => 'Nursing',
                'laboratory scientist' => 'Laboratory', 'peer educator' => 'Peer support', 'receptionist' => 'Records', 'hrio' => 'Records', 'data manager' => 'Records', 'admin' => 'Administration', 'super admin' => 'Administration'];
        return $map[strtolower(trim((string)$role))] ?? 'Other';
    }
}
if (!function_exists('referral_destination_department')) {
    /** Service a referral is going to, from the free-text "refer to" field. */
    function referral_destination_department(?string $referTo): string
    {
        $t = strtolower((string)$referTo);
        foreach (['psychosocial' => 'Psychosocial', 'counsel' => 'Psychosocial', 'tb' => 'TB care', 'ccc' => 'HIV / ART', 'art ' => 'HIV / ART', 'hiv' => 'HIV / ART', 'psychiatr' => 'Psychiatry', 'mental' => 'Psychiatry', 'anc' => 'Maternal & child health', 'maternity' => 'Maternal & child health', 'family planning' => 'Maternal & child health',
                 'lab' => 'Laboratory', 'nutrition' => 'Nutrition', 'legal' => 'Legal / paralegal', 'paralegal' => 'Legal / paralegal', 'gbv' => 'GBV / social support', 'social' => 'GBV / social support', 'rehab' => 'Rehabilitation', 'dental' => 'Specialist clinics', 'eye' => 'Specialist clinics', 'ortho' => 'Specialist clinics',
                 'clinic review' => 'Clinical', 'clinician' => 'Clinical', 'pharmacy' => 'Pharmacy', 'nursing' => 'Nursing', 'hospital' => 'Hospital / inpatient', 'casualty' => 'Hospital / inpatient', 'emergency' => 'Hospital / inpatient'] as $k => $v) {
            if (strpos($t, $k) !== false) return $v;
        }
        return 'Other';
    }
}
