<?php
session_start();
include '../includes/config.php';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}
$conn->set_charset('utf8mb4');

$errorMessages = [];
$mat_id = isset($_POST['mat_id']) ? $_POST['mat_id'] : null;

function displayErrorAndRedirect($conn, $messages, $mat_id) {
    foreach ($messages as $error) {
        echo $error;
    }
    // Rollback any pending transaction before redirecting
    $conn->rollback();
    echo "<script>setTimeout(function(){ window.location.href = 'dispensingdata.php?mat_id=" . urlencode($mat_id) . "'; }, 5000);</script>";
    exit();
}

try {
    // Start a transaction to ensure atomicity
    $conn->begin_transaction();

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

    // 1. Restrict submission if `current_status` is not "Active"
    if ($current_status !== "Active") {
        $errorMessages[] = "<div style='background-color: #EDFEB0; color: red; padding: 10px;  height: 40px; border-radius: 5px; font-weight: bold;'>
            Form cannot be submitted because the client's status is not 'Active'.
        </div>";
    }

    // 2. Duplicate Entry Check
    $checkQuery = "SELECT * FROM pharmacy WHERE mat_id = ? AND visitDate = CURDATE()";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('s', $mat_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $errorMessages[] = "<div style='background-color: #EDFEB0; color: red; padding: 10px; height: 40px; border-radius: 5px; font-weight: bold;'>
            The Client with mat_id $mat_id already dispensed today.
        </div>";
    }
    $checkStmt->close();

    // 3. Missed Appointment Check
    if ($isMissed || $DaysToNextAppointment == 0) {
        $errorMessages[] = "<div style='background-color: #EDFEB0; color: red; padding: 10px; border-radius: 5px; height: 40px;'>
            This Client has a Missed Appointment or No appointment date. Kindly refer to the clinician.
        </div>";
    }

    // 4. Dosage Validation
    if ($dosage <= 0) {
        $errorMessages[] = "<div style='background-color: #EDFEB0;; color: red; padding: 10px;  height: 40px; border-radius: 5px; font-weight: bold;'>
            Can't dispense 0 or negative doses. Please check the prescribed drug and try again.
        </div>";
    }

    // 5. Stock Validation
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

    if ($currentStock < $dosage) {
        $errorMessages[] = "<div style='background-color: yellow; color: red; padding: 10px; border: 1px solid red; border-radius: 5px; font-weight: bold;'>
            $drugname is OUT OF STOCK. Please add more $drugname to dispense.
        </div>";
    }

    // If any errors exist, display them and redirect
    if (!empty($errorMessages)) {
        displayErrorAndRedirect($conn, $errorMessages, $mat_id);
    }

    // Proceed with insertion and update if no errors
    $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);

    if ($stmt) {
        $stmt->bind_param('ssssssssssssss', $visitDate, $mat_id, $mat_number, $clientName, $nickName, $age, $sex, $p_address, $cso, $drugname, $dosage, $reasons, $current_status, $pharm_officer_name);

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

            // Commit the transaction
            $conn->commit();

            echo "<div style='background-color: #DDFCAF; color: green; font-size: 18px; padding: 15px; text-align: center; border-radius: 5px;'>$drugname dispensed successfully!</div>";
        } else {
            $errorMessages[] = "<div style='background-color: #EDFEB0; color: red; padding: 10px; height: 40px; border-radius: 5px;'>Error inserting record into pharmacy.</div>";
            $conn->rollback();
            displayErrorAndRedirect($conn, $errorMessages, $mat_id);
        }
        $stmt->close();
    } else {
        $errorMessages[] = "<div style='background-color: #EDFEB0; color: red; padding: 10px; height: 40px; border-radius: 5px;'>Error preparing statement.</div>";
        $conn->rollback();
        displayErrorAndRedirect($conn, $errorMessages, $mat_id);
    }
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $errorMessages[] = "<div style='background-color: #EDFEB0; color: red; padding: 10px; border: height: 40px; border-radius: 5px;'>Transaction failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    displayErrorAndRedirect($conn, $errorMessages, $mat_id);
}

// Close connection
$conn->close();

// Redirect after successful operation
header("Refresh: 2; URL=dispensing.php");
exit();
?>