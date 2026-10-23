<?php
session_start();
include '../includes/config.php';
require_once '../includes/pharmacy_helpers.php';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}
$conn->set_charset('utf8mb4');

$mat_id = isset($_POST['mat_id']) ? $_POST['mat_id'] : null;

// --- Initialize Result Arrays ---
$routineDispenseSuccess = false;
$otherPrescriptionsSuccess = false;
$errorMessages = [];
$successMessages = [];

// --- Helper function to display messages and redirect ---
function displayMessagesAndRedirect($conn, $successes, $errors, $mat_id) {
    if (!empty($errors)) {
        $_SESSION['dispensing_errors'] = $errors;
        if (!empty($successes)) {
            $_SESSION['dispensing_successes'] = $successes;
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? 'dispensing.php';
        header("Location: " . $referer);
        exit();
    }

    if (!empty($successes)) {
        $_SESSION['dispensing_successes'] = $successes;
        // Redirect back to the patient page so the success banner is shown
        // there for 2 seconds before auto-redirecting to the dispensing list.
        $back = !empty($mat_id)
            ? 'dispensingData.php?mat_id=' . urlencode((string)($mat_id))
            : 'dispensing.php';
        header("Location: $back");
        exit();
    }

    exit();
}

// ==============================================================================
// 1. ROUTINE DISPENSING LOGIC (Part 1)
// Executed only if mat_id and drugname are set (assumes primary MAT drug)
// ==============================================================================

$dispenseMat = (($_POST['dispense_mat'] ?? '1') !== '0');

if ($dispenseMat && $mat_id && isset($_POST['drugname']) && !empty($_POST['drugname'])) {

    $routineErrors = []; // Local error store for this transaction
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE); // Start transaction for Part 1

    try {
        // Capture form data and sanitize
        $visitDate = $_POST['visitDate'];
        $DaysToNextAppointment = $_POST['daysToNextAppointment'];
        $isMissed = $_POST['isMissed'] === 'true';
        $mat_number = $_POST['mat_number'];
        $clientName = $_POST['clientName'];
        $nickName = $_POST['nickName'];
        $age = $_POST['age'];
        $sex = $_POST['sex'];
        $p_address = $_POST['p_address'];
        $cso = $_POST['cso'];
        $drugname = $_POST['drugname'];
        $dosage = (float)$_POST['dosage'];
        $reasons = $_POST['reasons'];
        $current_status = $_POST['current_status'];
        $pharm_officer_name = $_POST['pharm_officer_name'];
        // Reservoir the methadone was drawn from by syringe (no pump motor
        // involved) — recorded on the pharmacy row exactly like the pump
        // flow does, so reservoir-remaining accounting stays accurate.
        $pump_device = (isset($_POST['pump_device']) && $_POST['pump_device'] !== '')
            ? (int)$_POST['pump_device']
            : null;

        // 1. Duplicate Entry Check — routine drug already dispensed today.
        // Scoped to the routine drug so that other prescriptions dispensed
        // earlier today (which are also recorded in pharmacy) don't block it.
        // This is a skip, not an error: other prescriptions still go through.
        // Buprenorphine is recorded per tablet strength, so any strength counts.
        $isBup = ph_is_buprenorphine($drugname);
        $dupDrugPattern = $isBup ? 'Buprenorphine%' : $drugname;
        $checkQuery = "SELECT 1 FROM pharmacy WHERE mat_id = ? AND drugname LIKE ? AND DATE(visitDate) = CURDATE() LIMIT 1";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ss', $mat_id, $dupDrugPattern);
        $checkStmt->execute();
        $alreadyDispensedToday = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if ($alreadyDispensedToday) {
            $conn->rollback();
            $successMessages[] = "ℹ Routine drug ($drugname) was already dispensed today — skipped.";
        } else {

        // 2. Restrict submission if `current_status` is not "Active"
        if ($current_status !== "Active") {
            $routineErrors[] = "Routine Dispensing Failed: Client status is not 'Active'.";
        }

        // 3. Missed Appointment Check - Updated with appointment type
        if ($isMissed || $DaysToNextAppointment == 0) {
            $appointmentType = $_POST['appointmentType'] ?? 'Appointment';
            $referTo = $_POST['referTo'] ?? 'Clinician';

            $routineErrors[] = "Routine Dispensing Failed: Client has a **Missed {$appointmentType} Appointment**. Kindly refer to the {$referTo}.";
        }

        // 4. Dosage Validation
        if ($dosage <= 0) {
            $routineErrors[] = "Routine Dispensing Failed: Can't dispense 0 or negative doses for **$drugname**.";
        }

        // 5a. Buprenorphine: the dispenser chooses which tablet strengths make up the dose
        $bupStrengths = [];
        $bupComponents = [];
        if ($isBup) {
            $bupStrengths  = bup_strengths($conn);
            $bupComponents = bup_parse_post($_POST, $bupStrengths);
            $bupErr = bup_validate($bupComponents, $dosage, $bupStrengths);
            if ($bupErr) {
                $routineErrors[] = "Routine Dispensing Failed: $bupErr";
            }
        }

        // 5. Stock Validation (Buprenorphine stock is checked per strength in bup_dispense)
        $stockQuery = "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
        $stockStmt = $conn->prepare($stockQuery);
        $stockStmt->bind_param('s', $drugname);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $currentStock = 0;
        if ($stockResult->num_rows > 0) {
            $stockRow = $stockResult->fetch_assoc();
            $currentStock = (float)$stockRow['total_qty'];
        }
        $stockStmt->close();

        if (!$isBup && $currentStock < $dosage) {
            $routineErrors[] = "Routine Dispensing Failed: **$drugname** is **OUT OF STOCK** (Current: $currentStock, Required: $dosage).";
        }

        // If no errors, proceed with insertion and update
        if (empty($routineErrors) && $isBup) {
            $detail = bup_dispense($conn, [
                'visitDate' => $visitDate, 'mat_id' => $mat_id, 'mat_number' => $mat_number,
                'clientName' => $clientName, 'nickName' => $nickName, 'age' => $age, 'sex' => $sex,
                'p_address' => $p_address, 'cso' => $cso, 'reasons' => $reasons,
                'current_status' => $current_status, 'pharm_officer_name' => $pharm_officer_name,
            ], $bupComponents, $bupStrengths);
            // Remember the strengths so they are pre-selected next visit
            bup_save($conn, $mat_id, $bupComponents, $dosage);

            $updateStatusStmt = $conn->prepare("UPDATE patients SET current_status = 'Active' WHERE mat_id = ?");
            $updateStatusStmt->bind_param('s', $mat_id);
            $updateStatusStmt->execute();
            $updateStatusStmt->close();

            $conn->commit();
            $routineDispenseSuccess = true;
            $successMessages[] = "Buprenorphine dispensed successfully! ({$dosage} mg = {$detail})";
        } elseif (empty($routineErrors)) {
            [$conversion, $conversionUnit] = ph_conversion($drugname, $dosage);
            $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name, pump_id, conversion, conversion_unit)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param('ssssssssssssssids', $visitDate, $mat_id, $mat_number, $clientName, $nickName, $age, $sex, $p_address, $cso, $drugname, $dosage, $reasons, $current_status, $pharm_officer_name, $pump_device, $conversion, $conversionUnit);

            if ($stmt->execute()) {
                // Update stock quantity
                $updateStockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                $updateStockStmt = $conn->prepare($updateStockQuery);
                $updateStockStmt->bind_param('ds', $dosage, $drugname);
                $updateStockStmt->execute();
                $updateStockStmt->close();

                // Update the patient's current status to "Active"
                $updateStatusQuery = "UPDATE patients SET current_status = 'Active' WHERE mat_id = ?";
                $updateStatusStmt = $conn->prepare($updateStatusQuery);
                $updateStatusStmt->bind_param('s', $mat_id);
                $updateStatusStmt->execute();
                $updateStatusStmt->close();

                $conn->commit();
                $routineDispenseSuccess = true;
                $successMessages[] = "Routine Drug ($drugname) dispensed successfully! (Dosage: $dosage)";
            } else {
                throw new Exception("Database error inserting routine record: " . $stmt->error);
            }
            $stmt->close();
        } else {
             // Rollback if there were logical errors
             $conn->rollback();
        }
        } // end: not already dispensed today
    } catch (Exception $e) {
        $conn->rollback();
        $routineErrors[] = "Routine Dispensing Failed (DB/System Error): " . htmlspecialchars((string)($e->getMessage()));
    }

    // ===========================================================================
    // 1.5  CO-MEDICATIONS — ARV / Anti-TB / TPT
    // Runs only after the main drug was successfully dispensed.
    // Each co-medication gets its own pharmacy record and stock decrement.
    // ===========================================================================
    if ($routineDispenseSuccess && isset($_POST['co_meds']) && is_array($_POST['co_meds'])) {
        foreach ($_POST['co_meds'] as $ci => $cmed) {
            // Skip unchecked rows
            if (empty($cmed['dispense']) || $cmed['dispense'] !== '1') continue;

            $co_drug    = trim((string)($cmed['drugname'] ?? ''));
            $co_dosage  = (float)($cmed['dosage'] ?? 1);
            $co_reasons = trim((string)($cmed['reasons']  ?? 'Auto-dispensed with MAT drug'));
            $co_prescription_drug_id = !empty($cmed['prescription_drug_id']) ? (int)$cmed['prescription_drug_id'] : null;

            if (!$co_drug) continue;

            // Skip if already dispensed today for this drug
            $coDupStmt = $conn->prepare(
                "SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND drugname = ? AND visitDate = CURDATE()"
            );
            $coDupStmt->bind_param('ss', $mat_id, $co_drug);
            $coDupStmt->execute();
            $coDup = (int)$coDupStmt->get_result()->fetch_assoc()['cnt'];
            $coDupStmt->close();

            if ($coDup > 0) {
                $successMessages[] = "ℹ {$co_drug} already recorded for today — skipped.";
                continue;
            }

            $conn->begin_transaction();
            try {
                // Stock validation — a co-medication that is not in stock is not dispensed
                $coStockQ = $conn->prepare(
                    "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1"
                );
                $coStockQ->bind_param('s', $co_drug);
                $coStockQ->execute();
                $coStockRow = $coStockQ->get_result()->fetch_assoc();
                $coStockQ->close();

                if (!$coStockRow || (float)$coStockRow['total_qty'] < $co_dosage) {
                    throw new Exception("{$co_drug} is OUT OF STOCK or insufficient — not dispensed.");
                }

                // Insert pharmacy record for co-medication
                $coIns = $conn->prepare(
                    "INSERT INTO pharmacy
                         (visitDate, mat_id, mat_number, clientName, nickName, age, sex,
                          p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                     VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $coIns->bind_param(
                    'sssssssssdsss',      // 9s + d + 3s = 13 params
                    $mat_id, $mat_number, $clientName, $nickName,
                    $age, $sex, $p_address, $cso,
                    $co_drug, $co_dosage, $co_reasons, $current_status, $pharm_officer_name
                );
                if (!$coIns->execute()) throw new Exception($coIns->error);
                $coIns->close();

                // Decrement stock only if the drug name exists in stock_movements
                $coStockChk = $conn->prepare(
                    "SELECT COUNT(*) AS cnt FROM stock_movements WHERE drugname = ?"
                );
                $coStockChk->bind_param('s', $co_drug);
                $coStockChk->execute();
                $hasStock = (int)$coStockChk->get_result()->fetch_assoc()['cnt'] > 0;
                $coStockChk->close();

                if ($hasStock) {
                    $coUpd = $conn->prepare(
                        "UPDATE stock_movements SET total_qty = total_qty - ?
                         WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1"
                    );
                    $coUpd->bind_param('ds', $co_dosage, $co_drug);
                    $coUpd->execute();
                    $coUpd->close();
                }

                // DOT drugs are also tracked in prescription_drugs (total dosage
                // prescribed vs. dispensed so far) — keep that running balance in
                // sync now that this dose has actually gone out via the co-meds panel.
                if ($co_prescription_drug_id) {
                    $dotFetch = $conn->prepare("SELECT prescription_id, total_dosage, total_dispensed FROM prescription_drugs WHERE id = ?");
                    $dotFetch->bind_param('i', $co_prescription_drug_id);
                    $dotFetch->execute();
                    $dotRow = $dotFetch->get_result()->fetch_assoc();
                    $dotFetch->close();

                    if ($dotRow) {
                        $dotRemainingBefore = (float)$dotRow['total_dosage'] - (float)$dotRow['total_dispensed'];
                        $dotDispenseNow     = min($co_dosage, $dotRemainingBefore);
                        $dotNewRemaining    = $dotRemainingBefore - $dotDispenseNow;
                        $dotStatus          = ($dotNewRemaining <= 0) ? 'dispensed and closed' : 'partially dispensed';

                        $dotUpd = $conn->prepare(
                            "UPDATE prescription_drugs SET total_dispensed = total_dispensed + ?, remaining_balance = total_dosage - total_dispensed - ?, prescr_status = ? WHERE id = ?"
                        );
                        $dotUpd->bind_param('ddsi', $dotDispenseNow, $dotDispenseNow, $dotStatus, $co_prescription_drug_id);
                        $dotUpd->execute();
                        $dotUpd->close();

                        if ($dotNewRemaining <= 0) {
                            $dotPrescrUpd = $conn->prepare(
                                "UPDATE other_prescriptions SET prescr_status = 'dispensed' WHERE prescription_id = ?
                                 AND NOT EXISTS (SELECT 1 FROM prescription_drugs WHERE prescription_id = ? AND (total_dosage - total_dispensed) > 0)"
                            );
                            $dotPrescrUpd->bind_param('ss', $dotRow['prescription_id'], $dotRow['prescription_id']);
                            $dotPrescrUpd->execute();
                            $dotPrescrUpd->close();
                        }
                    }
                }

                $conn->commit();
                $successMessages[] = "✓ {$co_drug} dispensed automatically.";

            } catch (Exception $coEx) {
                $conn->rollback();
                $errorMessages[] = "Co-medication error ({$co_drug}): " . htmlspecialchars((string)($coEx->getMessage()));
            }
        }
    }
    // ===========================================================================
    // END CO-MEDICATIONS
    // ===========================================================================

    // Append routine errors to the main error list
    $errorMessages = array_merge($errorMessages, $routineErrors);
}


// ==============================================================================
// 2. OTHER PRESCRIPTIONS DISPENSING LOGIC (Part 2)
// Executed only if 'dispense' and 'quantity' arrays are submitted
// ==============================================================================

if (isset($_POST['dispense']) && is_array($_POST['dispense']) && isset($_POST['quantity']) && is_array($_POST['quantity'])) {

    $prescriptionsToProcess = []; // Stores unique prescription IDs that had at least one drug dispensed
    $otherPrescriptionErrors = [];
    $dispensedCount = 0;

    // Filter submitted data to only include items marked for dispensing
    foreach ($_POST['dispense'] as $drug_id_from_form => $prescription_id) {
        $dispense_id = (int)$drug_id_from_form; // This is the ID from 'prescription_drugs' table
        $quantity = (float)($_POST['quantity'][$dispense_id] ?? 0);

        // Only process if quantity is greater than zero and drug was checked/present
        if ($quantity > 0) {
            $prescriptionsToProcess[$prescription_id][] = [
                'drug_id' => $dispense_id,
                'quantity' => $quantity,
                // Fetch existing total_dosage from DB for validation and remaining balance calculation
            ];
            $dispensedCount++;
        }
    }

    // Process the dispensing in a single transaction for atomicity
    if ($dispensedCount > 0) {
        $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

        try {
            // Prepare statements outside the loop
            $stmt_fetch_drug = $conn->prepare("SELECT total_dosage, total_dispensed, drug_name FROM prescription_drugs WHERE id = ?");
            $stmt_update_drug = $conn->prepare("UPDATE prescription_drugs SET total_dispensed = total_dispensed + ?, remaining_balance = total_dosage - total_dispensed - ?, prescr_status = ? WHERE id = ?");
            $stmt_update_stock = $conn->prepare("UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
            $stmt_update_prescr = $conn->prepare("UPDATE other_prescriptions SET prescr_status = ? WHERE prescription_id = ?");
            $stmt_stock_check = $conn->prepare("SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
            $stmt_insert_pharm = $conn->prepare(
                "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                 VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            // Patient details for the pharmacy (dispensing) records — taken from
            // the patients table so they don't depend on form fields being posted.
            $patRow = [];
            $patStmt = $conn->prepare("SELECT * FROM patients WHERE mat_id = ? LIMIT 1");
            $patStmt->bind_param('s', $mat_id);
            $patStmt->execute();
            $patRow = $patStmt->get_result()->fetch_assoc() ?: [];
            $patStmt->close();

            $op_mat_number = (string)($patRow['mat_number'] ?? ($_POST['mat_number'] ?? ''));
            $op_clientName = (string)($patRow['clientName'] ?? ($_POST['clientName'] ?? ''));
            $op_nickName   = (string)($patRow['nickName']   ?? ($_POST['nickName']   ?? ''));
            $op_age        = (string)($_POST['age'] ?? ($patRow['age'] ?? ''));
            $op_sex        = (string)($patRow['sex']        ?? ($_POST['sex']        ?? ''));
            $op_address    = (string)($patRow['p_address']  ?? ($_POST['p_address']  ?? ''));
            $op_cso        = (string)($patRow['cso']        ?? ($_POST['cso']        ?? ''));
            $op_status     = (string)($patRow['current_status'] ?? ($_POST['current_status'] ?? ''));
            $op_officer    = (string)($_POST['pharm_officer_name'] ?? ($_SESSION['username'] ?? ''));

            $uniquePrescriptionsDispensed = 0;

            foreach ($prescriptionsToProcess as $prescription_id => $drugs) {
                $isPrescriptionComplete = true; // Assume complete until proven otherwise

                foreach ($drugs as $drug) {
                    $drug_id = $drug['drug_id'];
                    $dispensed_amount = $drug['quantity'];

                    // A. Fetch current state (total_dosage, total_dispensed, drug_name)
                    $stmt_fetch_drug->bind_param("i", $drug_id);
                    $stmt_fetch_drug->execute();
                    $result_fetch = $stmt_fetch_drug->get_result();
                    $current_drug_data = $result_fetch->fetch_assoc();

                    if (!$current_drug_data) {
                        throw new Exception("Drug ID $drug_id not found in prescription_drugs.");
                    }

                    $total_dosage = (float)$current_drug_data['total_dosage'];
                    $current_dispensed = (float)$current_drug_data['total_dispensed'];
                    $drug_name = $current_drug_data['drug_name'];

                    $remaining_before = $total_dosage - $current_dispensed;

                    // B. Validation
                    if ($dispensed_amount > $remaining_before) {
                        throw new Exception("Dispensed amount ($dispensed_amount) for **$drug_name** cannot be greater than the remaining balance ($remaining_before).");
                    }

                    // C. Determine new status and update prescription_drugs
                    $new_remaining = $remaining_before - $dispensed_amount;
                    $drug_status = ($new_remaining <= 0) ? 'dispensed and closed' : 'partially dispensed';

                    $stmt_update_drug->bind_param("ddsi", $dispensed_amount, $dispensed_amount, $drug_status, $drug_id);
                    $stmt_update_drug->execute();

                    if ($new_remaining > 0) {
                        $isPrescriptionComplete = false; // Mark prescription as partially complete
                    }

                    // D. Stock validation — nothing is dispensed if the drug is not in stock
                    $stmt_stock_check->bind_param("s", $drug_name);
                    $stmt_stock_check->execute();
                    $stockRowOp = $stmt_stock_check->get_result()->fetch_assoc();
                    $availableOp = $stockRowOp ? (float)$stockRowOp['total_qty'] : 0;

                    if (!$stockRowOp || $availableOp <= 0) {
                        throw new Exception("**$drug_name** is OUT OF STOCK (not found in stock). Nothing was dispensed.");
                    }
                    if ($availableOp < $dispensed_amount) {
                        throw new Exception("Insufficient stock for **$drug_name** (Available: $availableOp, Required: $dispensed_amount). Nothing was dispensed.");
                    }

                    // E. Update Stock
                    $stmt_update_stock->bind_param("ds", $dispensed_amount, $drug_name);
                    $stmt_update_stock->execute();

                    // F. Record the dispensing in the pharmacy table (like routine drugs)
                    $op_reason = "Other prescription: " . $prescription_id;
                    $stmt_insert_pharm->bind_param(
                        "sssssssssdsss",
                        $mat_id, $op_mat_number, $op_clientName, $op_nickName, $op_age,
                        $op_sex, $op_address, $op_cso, $drug_name, $dispensed_amount,
                        $op_reason, $op_status, $op_officer
                    );
                    if (!$stmt_insert_pharm->execute()) {
                        throw new Exception("Could not record dispensing for $drug_name: " . $stmt_insert_pharm->error);
                    }
                }

                // E. Update other_prescriptions status after processing all drugs for this prescription
                $new_prescr_status = $isPrescriptionComplete ? 'dispensed' : 'partially dispensed';
                $stmt_update_prescr->bind_param("ss", $new_prescr_status, $prescription_id);
                $stmt_update_prescr->execute();

                $uniquePrescriptionsDispensed++;
            }

            // All successful: Commit the transaction
            $conn->commit();
            $otherPrescriptionsSuccess = true;
            $successMessages[] = "Other Prescriptions Dispensing Successful! **$uniquePrescriptionsDispensed** unique prescriptions updated with **$dispensedCount** drug items dispensed.";

        } catch (Exception $e) {
            $conn->rollback();
            $otherPrescriptionErrors[] = "Other Prescriptions Dispensing Failed (Error in dispensing process): " . htmlspecialchars((string)($e->getMessage()));
        } finally {
            // Close prepared statements for Part 2
            $stmt_fetch_drug->close();
            $stmt_update_drug->close();
            $stmt_update_stock->close();
            $stmt_update_prescr->close();
            if (isset($stmt_stock_check)) $stmt_stock_check->close();
            if (isset($stmt_insert_pharm)) $stmt_insert_pharm->close();
        }
    }

    // Append other prescription errors to the main error list
    $errorMessages = array_merge($errorMessages, $otherPrescriptionErrors);
}


// ==============================================================================
// 3. FINAL MESSAGE DISPLAY AND REDIRECT
// ==============================================================================

if (empty($errorMessages) && empty($successMessages)) {
    // This happens if the form was submitted but nothing was selected/required
    $errorMessages[] = "No routine drug data submitted or no 'Other Prescriptions' selected for dispensing. Nothing was processed.";
}

// Display collected messages (successes and errors) and redirect
displayMessagesAndRedirect($conn, $successMessages, $errorMessages, $mat_id);

$conn->close();
?>