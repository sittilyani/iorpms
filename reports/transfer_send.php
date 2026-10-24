<?php
/** Creates the acceptance link for a saved transfer form and optionally emails it to the receiving facility. JSON in and out. */
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'Clinician', 'Psychiatrist', 'Psychologist', 'Nursing', 'Pharmacist', 'HRIO', 'Data Manager']);
include "../includes/config.php";
require_once "../includes/transfer_helpers.php";
header('Content-Type: application/json');
$in = json_decode((string)file_get_contents('php://input'), true) ?: [];
$id = (int)($in['form_id'] ?? 0);
if (strtolower((string)($_SESSION['userrole'] ?? '')) === 'demo') { echo json_encode(['ok' => false, 'message' => 'Demo accounts cannot send transfers.']); exit; }
[$ok, $link, $emailed, $msg] = transfer_form_send($conn, $id, (string)($in['email'] ?? ''), getUserFullName());
echo json_encode(['ok' => $ok, 'link' => $link, 'emailed' => $emailed, 'message' => $msg]);
