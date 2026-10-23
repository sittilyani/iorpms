<?php
session_start();
include '../includes/config.php';
require_once '../includes/pump_guard.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }
header('Content-Type: application/json');
echo json_encode(pump_readiness($conn));
