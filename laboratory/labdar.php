<?php
session_start();
include "../includes/config.php";

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$lab_office_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $lab_office_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LabDAR</title>
    <link rel="stylesheet" href="../assets/css/labdar.css" type="text/css">

    <style>

    </style>
</head>
<body>
    <div class="content-main">
        <div class="page-header">
            <h1 class="page-title">Laboratory Daily Activity Register (LabDAR) for </h1>
            <span style='color: red;'><?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?> - <?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?></span>
        </div>

        <form action="labdar_process.php" method="post" class="post">

            <div class="form-container">
                <!-- PROFILE SECTION -->
                <div class="form-section">
                    <h3 class="section-header">Patient Profile</h3>

                    <div class="form-group">
                        <label for="visitDate">Visit Date</label>
                        <input type="text" name="visitDate" class="read-only" readonly value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="mat_id">MAT ID</label>
                        <input type="text" name="mat_id" class="read-only" readonly value="<?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="clientName">Client Name</label>
                        <input type="text" name="clientName" class="read-only" readonly value="<?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="type_client">Type of Client</label>
                        <select name="type_client">
                            <option value="new">New</option>
                            <option value="re_induction">Re-Induction</option>
                            <option value="routine">Routine</option>
                            <option value="weaned">Weaned Off</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mode_drug_use">Mode of Drug Use</label>
                        <select name="mode_drug_use" required>
                            <option value="PWUD">PWUD</option>
                            <option value="PWID">PWID</option>
                        </select>
                    </div>
                </div>

                <!-- ROUTINE TESTS SECTION -->
                <div class="form-section">
                    <h3 class="section-header">Routine Tests</h3>

                    <div class="form-group">
                        <label for="hiv_status">HIV Testing</label>
                        <select name="hiv_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hbv_status">Hepatitis B</label>
                        <select name="hbv_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hepc_status">Hepatitis C</label>
                        <select name="hepc_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="malaria_status">Malaria (mRDTs)</label>
                        <select name="malaria_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="pregnancy_status">Pregnancy (hCG)</label>
                        <select name="pregnancy_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="vdrl_status">Syphilis (VDRL)</label>
                        <select name="vdrl_status">
                            <option value="not_done">Not Done</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="urinalysis_status">Urinalysis</label>
                        <select name="urinalysis_status">
                            <option value="not_done">Not Done</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                        </select>
                    </div>
                </div>

                <!-- TOXICOLOGY SECTION -->
                <div class="form-section">
                    <h3 class="section-header">Toxicology</h3>

                    <div class="form-group">
                        <label for="amphetamine">Amphetamine</label>
                        <select name="amphetamine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="metamphetamine">Metamphetamine</label>
                        <select name="metamphetamine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="morphine">Morphine</label>
                        <select name="morphine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="barbiturates">Barbiturates</label>
                        <select name="barbiturates">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cocaine">Cocaine</label>
                        <select name="cocaine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="codeine">Codeine</label>
                        <select name="codeine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="benzodiazepines">Benzodiazepines</label>
                        <select name="benzodiazepines">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marijuana">Marijuana</label>
                        <select name="marijuana">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amitriptyline">Amitriptyline</label>
                        <select name="amitriptyline">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                </div>

                <!-- NOTES & APPOINTMENTS SECTION -->
                <div class="form-section">
                    <h3 class="section-header">Notes & Appointments</h3>

                    <div class="form-group">
                        <label for="lab_notes">Lab Notes</label>
                        <textarea name="lab_notes" id="lab_notes" rows="3" required placeholder="Enter laboratory observations..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date_of_test">Date of Test</label>
                        <input type="date" name="date_of_test" id="date_of_test">
                    </div>

                    <div class="form-group">
                        <label for="next_appointment">Next Appointment Date</label> <br>
                        <input type="date" name="next_appointment" required>  <br>
                    </div>

                    <div class="form-group">
                        <label for="lab_officer_name">Dispensing Officer Name</label><br>
                        <input type="text" name="lab_officer_name" value="<?php echo htmlspecialchars($lab_office_name); ?>" class="read-only" readonly><br>
                    </div>

                    <div class="form-group">
                            <button class="submit" id="btn-submit" style="background: linear-gradient(135deg, #1a2a6c, #2b5876);color: white; width: 100%; border: none; border-radius: 5px; height: 40px;">Submit</button>
                    </div>
                </div>
                </div>
            </form>
    </div>

</body>
</html>