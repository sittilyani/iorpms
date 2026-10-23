<?php
session_start();
include '../includes/config.php';
require_once '../includes/pharmacy_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prescription_id = $_POST['prescription_id'];
    $dispensed_amounts = $_POST['total_dispensed'];   // quantity issued in THIS dispensing, per drug
    $drug_ids = $_POST['drug_id'];

    $conn->begin_transaction();

    try {
        // Client details for the pharmacy (dispensing) record
        $pStmt = $conn->prepare(
            "SELECT p.mat_id, p.mat_number, p.clientName, p.nickName, p.age, p.sex, p.p_address, p.cso, p.current_status
               FROM other_prescriptions op JOIN patients p ON p.mat_id = op.mat_id
              WHERE op.prescription_id = ? LIMIT 1"
        );
        $pStmt->bind_param('s', $prescription_id);
        $pStmt->execute();
        $pat = $pStmt->get_result()->fetch_assoc();
        $pStmt->close();
        if (!$pat) {
            throw new Exception("Client for prescription $prescription_id not found.");
        }
        $officer = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? '');
        $reason  = "Other prescription: " . $prescription_id;

        $stmt_get   = $conn->prepare("SELECT drug_name, total_dosage, total_dispensed, prescr_status FROM prescription_drugs WHERE id = ? FOR UPDATE");
        $stmt_drugs = $conn->prepare("UPDATE prescription_drugs SET total_dispensed = total_dispensed + ?, remaining_balance = total_dosage - total_dispensed, prescr_status = ? WHERE id = ?");
        $stmt_pharm = $conn->prepare(
            "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
             VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_stock = $conn->prepare("UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
        $stmt_chk   = $conn->prepare("SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");

        $all_dispensed = true;

        for ($i = 0; $i < count($drug_ids); $i++) {
            $drug_id = (int)$drug_ids[$i];
            $now = (float)$dispensed_amounts[$i];

            $stmt_get->bind_param('i', $drug_id);
            $stmt_get->execute();
            $d = $stmt_get->get_result()->fetch_assoc();
            if (!$d) {
                throw new Exception("Drug line $drug_id not found in this prescription.");
            }
            $remaining_before = (float)$d['total_dosage'] - (float)$d['total_dispensed'];
            if ($now < 0 || $now > $remaining_before + 1e-9) {
                throw new Exception("Quantity for {$d['drug_name']} cannot be more than the remaining balance ($remaining_before).");
            }

            $remaining_after = $remaining_before - $now;
            $status = ($remaining_after <= 0) ? 'dispensed and closed' : (($now > 0 || (float)$d['total_dispensed'] > 0) ? 'partially dispensed' : ($d['prescr_status'] ?? 'pending'));

            if ($now > 0) {
                // Account for it in `pharmacy` like any other dispensing
                $drug_name = $d['drug_name'];
                $stmt_pharm->bind_param(
                    'sssssssssdsss',
                    $pat['mat_id'], $pat['mat_number'], $pat['clientName'], $pat['nickName'], $pat['age'],
                    $pat['sex'], $pat['p_address'], $pat['cso'], $drug_name, $now,
                    $reason, $pat['current_status'], $officer
                );
                if (!$stmt_pharm->execute()) {
                    throw new Exception("Could not record dispensing of $drug_name: " . $stmt_pharm->error);
                }

                // Deduct stock when this drug is tracked in stock_movements
                $stmt_chk->bind_param('s', $drug_name);
                $stmt_chk->execute();
                $stockRow = $stmt_chk->get_result()->fetch_assoc();
                if ($stockRow) {
                    if ((float)$stockRow['total_qty'] < $now) {
                        throw new Exception("Insufficient stock for $drug_name (available {$stockRow['total_qty']}, need $now).");
                    }
                    $stmt_stock->bind_param('ds', $now, $drug_name);
                    $stmt_stock->execute();
                }
            }

            $stmt_drugs->bind_param('dsi', $now, $status, $drug_id);
            $stmt_drugs->execute();

            if ($remaining_after > 0) {
                $all_dispensed = false;
            }
        }
        $stmt_get->close(); $stmt_drugs->close(); $stmt_pharm->close(); $stmt_stock->close(); $stmt_chk->close();

        $new_prescr_status = $all_dispensed ? 'dispensed' : 'partially dispensed';
        $stmt_prescr = $conn->prepare("UPDATE other_prescriptions SET prescr_status = ? WHERE prescription_id = ?");
        $stmt_prescr->bind_param("ss", $new_prescr_status, $prescription_id);
        $stmt_prescr->execute();
        $stmt_prescr->close();

        $conn->commit();

        header("Location: view_prescriptions.php?success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . htmlspecialchars((string)($e->getMessage()));
    }
}
?>
