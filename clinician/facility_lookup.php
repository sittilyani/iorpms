<?php
// Facility search for pick-lists (transfer form, etc.): GET ?q=<2+ letters of the name or MFL code> -> JSON ["Name (MFL)", ...]
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
header('Content-Type: application/json');
$q = trim((string)($_GET['q'] ?? ''));
if (strlen($q) < 2) { echo '[]'; exit; }
$like = '%' . $conn->real_escape_string($q) . '%';
$r = $conn->query("SELECT facilityname, mflcode FROM facilities WHERE facilityname LIKE '$like' OR mflcode LIKE '$like' ORDER BY facilityname LIMIT 25");
echo json_encode(array_map(fn($x) => $x['facilityname'] . ' (' . $x['mflcode'] . ')', $r ? $r->fetch_all(MYSQLI_ASSOC) : []));
