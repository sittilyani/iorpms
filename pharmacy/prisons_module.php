<?php
session_start();
include '../includes/config.php';

$page_title = 'Routine Dispensing';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$conn->set_charset('utf8mb4');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

// Variable to track if we should redirect
$shouldRedirect = false;

// ── Patient search / add-to-list ────────────────────────────────────────────
// The bulk list defaults to inmates (p_address LIKE '%inmate%'), but staff can
// search for and add ANY patient (e.g. one held elsewhere, or on Buprenorphine
// rather than Methadone). Added patients are carried across "Generate List"
// submissions via the extra_ids GET param — the date-range/generation logic
// itself is unchanged.
$extraIds = [];
if (isset($_GET['extra_ids']) && $_GET['extra_ids'] !== '') {
    $extraIds = array_values(array_unique(array_filter(array_map('trim', explode(',', $_GET['extra_ids'])))));
}
$searchTerm = isset($_GET['search_patient']) ? trim($_GET['search_patient']) : '';
$searchResults = [];
if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    $searchStmt = $conn->prepare(
        "SELECT mat_id, mat_number, clientName, sex, p_address, drugname, current_status
         FROM patients
         WHERE (mat_id LIKE ? OR clientName LIKE ? OR mat_number LIKE ?)
           AND current_status IN ('Active', 'LTFU', 'Defaulted')
         ORDER BY clientName ASC
         LIMIT 25"
    );
    $searchStmt->bind_param('sss', $like, $like, $like);
    $searchStmt->execute();
    $searchResults = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $searchStmt->close();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dispensing'])) {
    $errorMessages = [];
    $successMessages = [];

    try {
        $conn->begin_transaction();

        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $pharm_officer_name = $_POST['pharm_officer_name'];

        // Get all dates in the range
        $dateRange = [];
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);

        while ($currentDate <= $endDateObj) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        // Process each patient's dispensing
        foreach ($_POST['patients'] as $mat_id => $patientData) {
            foreach ($dateRange as $date) {
                if (isset($patientData[$date]) && $patientData[$date] > 0) {
                    $dosage = (float)$patientData[$date];

                    // Get patient info
                    $patientQuery = "SELECT * FROM patients WHERE mat_id = ?";
                    $patientStmt = $conn->prepare($patientQuery);
                    $patientStmt->bind_param('s', $mat_id);
                    $patientStmt->execute();
                    $patientResult = $patientStmt->get_result();
                    $patient = $patientResult->fetch_assoc();
                    $patientStmt->close();

                    // Check if already dispensed for this date
                    $checkQuery = "SELECT * FROM pharmacy WHERE mat_id = ? AND visitDate = ?";
                    $checkStmt = $conn->prepare($checkQuery);
                    $checkStmt->bind_param('ss', $mat_id, $date);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows === 0) {
                        // Insert dispensing record
                        $insertQuery = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertQuery);

                        // Use the patient's actual prescribed drug (Methadone or
                        // Buprenorphine) rather than always recording Methadone —
                        // this list can now include Buprenorphine patients found
                        // via search, and hardcoding the drug name here would
                        // silently mislabel their dispensing record.
                        $drugname = !empty($patient['drugname']) ? $patient['drugname'] : 'Methadone';
                        $reasons = 'Bulk dispensing';

                        $stmt->bind_param('ssssssssssssss',
                            $date,
                            $mat_id,
                            $patient['mat_number'],
                            $patient['clientName'],
                            $patient['nickName'],
                            $patient['age'],
                            $patient['sex'],
                            $patient['p_address'],
                            $patient['cso'],
                            $drugname,
                            $dosage,
                            $reasons,
                            $patient['current_status'],
                            $pharm_officer_name);

                        if ($stmt->execute()) {
                            // Update stock
                            $stockQuery = "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                            $stockStmt = $conn->prepare($stockQuery);
                            $stockStmt->bind_param('ds', $dosage, $drugname);
                            $stockStmt->execute();
                            $stockStmt->close();

                            $successMessages[] = "Dispensed $dosage mg of $drugname to {$patient['clientName']} (MAT ID: $mat_id) for $date";
                        } else {
                            $errorMessages[] = "Error dispensing to {$patient['clientName']} for $date";
                        }
                        $stmt->close();
                    } else {
                        $errorMessages[] = "{$patient['clientName']} (MAT ID: $mat_id) already dispensed for $date";
                    }
                    $checkStmt->close();
                }
            }
        }

        $conn->commit();

        // Set redirect flag if there are success messages and no errors
        if (!empty($successMessages) && empty($errorMessages)) {
            $shouldRedirect = true;
        }

        // Display success/error messages
        if (!empty($successMessages)) {
            echo '<div class="alert alert-success">';
            echo '<h4>Successfully Dispensed:</h4>';
            echo '<ul>';
            foreach ($successMessages as $msg) {
                echo '<li>' . $msg . '</li>';
            }
            echo '</ul>';
            if ($shouldRedirect) {
                echo '<p><strong>Redirecting to dispensing page in 2 seconds...</strong></p>';
            }
            echo '</div>';
        }

        if (!empty($errorMessages)) {
            echo '<div class="alert alert-danger">';
            echo '<h4>Errors:</h4>';
            echo '<ul>';
            foreach ($errorMessages as $msg) {
                echo '<li>' . $msg . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo '<div class="alert alert-danger">Transaction failed: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bulk Dispensing</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <style>


        .header {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .date-range-form {
            margin-bottom: 30px;
        }

        .prison-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .prison-table th, .prison-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .prison-table th {
            background-color: #2C3162;
            color: white;
        }

        .prison-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .dosage-input {
            width: 60px;
            text-align: center;
        }

        .submit-btn {
            background-color: #2C3162;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #1a1d3d;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .remove-btn:hover {
            background-color: #c82333;
        }

        .alert ul {
            margin-bottom: 0;
        }
    </style>

    <?php if ($shouldRedirect): ?>
    <script>
        // Redirect after 2 seconds
        setTimeout(function() {
            window.location.href = 'dispensing.php';
        }, 2000);
    </script>
    <?php endif; ?>
</head>
<body>
    <h2 style="color: #2C3162;">Bulk Dispensing</h2>

    <div class="date-range-form">
        <form method="GET" action="">
            <input type="hidden" name="extra_ids" value="<?php echo htmlspecialchars(implode(',', $extraIds)); ?>">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'); ?>">

                <label for="end_date" style="margin-left: 20px;">End Date:</label>
                <input type="date" id="end_date" name="end_date" required
                       value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+6 days')); ?>">

                <button type="submit" class="submit-btn" style="margin-left: 20px;">Generate List</button>
            </div>
        </form>
    </div>

    <div class="search-form" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <form method="GET" action="">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars(isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d')); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars(isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+6 days'))); ?>">
            <input type="hidden" name="extra_ids" value="<?php echo htmlspecialchars(implode(',', $extraIds)); ?>">
            <label for="search_patient">Search Patient (MAT ID, MAT Number or Name):</label>
            <input type="text" id="search_patient" name="search_patient" style="margin-left: 10px; margin-right: 10px; width: 260px;"
                   value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="e.g. 11094MAT0002 or client name">
            <button type="submit" class="submit-btn" style="margin-left: 0;">Search</button>
        </form>

        <?php if ($searchTerm !== ''): ?>
            <?php if (empty($searchResults)): ?>
                <div class="alert alert-info" style="margin-top: 15px;">No matching patients found.</div>
            <?php else: ?>
                <table class="prison-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>MAT ID</th>
                            <th>MAT Number</th>
                            <th>Client Name</th>
                            <th>Sex</th>
                            <th>Physical Address</th>
                            <th>Drug</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searchResults as $sr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sr['mat_id']); ?></td>
                                <td><?php echo htmlspecialchars($sr['mat_number']); ?></td>
                                <td><?php echo htmlspecialchars($sr['clientName']); ?></td>
                                <td><?php echo htmlspecialchars($sr['sex']); ?></td>
                                <td><?php echo htmlspecialchars($sr['p_address']); ?></td>
                                <td><?php echo htmlspecialchars($sr['drugname'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($sr['current_status']); ?></td>
                                <td>
                                    <?php if (in_array($sr['mat_id'], $extraIds, true)): ?>
                                        <span style="color:green;">✓ In list below</span>
                                    <?php else: ?>
                                        <?php
                                            $newExtraIds = implode(',', array_merge($extraIds, [$sr['mat_id']]));
                                        ?>
                                        <a href="?start_date=<?php echo urlencode($_GET['start_date'] ?? date('Y-m-d')); ?>&end_date=<?php echo urlencode($_GET['end_date'] ?? date('Y-m-d', strtotime('+6 days'))); ?>&extra_ids=<?php echo urlencode($newExtraIds); ?>&search_patient=<?php echo urlencode($searchTerm); ?>"
                                           class="submit-btn" style="padding: 4px 10px; font-size: 13px; text-decoration:none;">Add to list</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php
    if (isset($_GET['start_date'])) {
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];

        // Validate dates
        if (strtotime($startDate) > strtotime($endDate)) {
            echo '<div class="alert alert-danger">End date must be after start date.</div>';
        } else {
            // Get all dates in the range
            $dateRange = [];
            $currentDate = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);

            while ($currentDate <= $endDateObj) {
                $dateRange[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }

            // Get all inmates, plus any patients added via search (extra_ids)
            if (!empty($extraIds)) {
                $extraPlaceholders = implode(',', array_fill(0, count($extraIds), '?'));
                $inmatesQuery = "SELECT * FROM patients
                                  WHERE (p_address LIKE '%inmate%' OR mat_id IN ($extraPlaceholders))
                                    AND current_status IN ('Active', 'LTFU', 'Defaulted')";
                $inmatesStmt = $conn->prepare($inmatesQuery);
                $extraTypes = str_repeat('s', count($extraIds));
                $inmatesStmt->bind_param($extraTypes, ...$extraIds);
                $inmatesStmt->execute();
                $inmatesResult = $inmatesStmt->get_result();
            } else {
                $inmatesQuery = "SELECT * FROM patients WHERE p_address LIKE '%inmate%' AND current_status IN ('Active', 'LTFU', 'Defaulted')";
                $inmatesResult = $conn->query($inmatesQuery);
            }

            if ($inmatesResult && $inmatesResult->num_rows > 0) {
                echo '<form method="POST" action="">';
                echo '<input type="hidden" name="start_date" value="' . $startDate . '">';
                echo '<input type="hidden" name="end_date" value="' . $endDate . '">';

                echo '<div class="form-group" style="margin-bottom: 20px;">';
                echo '<label for="pharm_officer_name">Pharmacy Officer Name:</label>';
                echo '<input type="text" id="pharm_officer_name" name="pharm_officer_name" required>';
                echo '</div>';

                echo '<table class="prison-table">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>p_ID</th>';
                echo '<th>MAT ID</th>';
                echo '<th>Client Name</th>';
                echo '<th>Sex</th>';
                echo '<th>Physical Address</th>';

                // Add date columns
                foreach ($dateRange as $date) {
                    echo '<th>' . date('m/d/Y', strtotime($date)) . '</th>';
                }

                echo '<th>Current Status</th>';
                echo '<th>History</th>';
                echo '<th>Action</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ($inmate = $inmatesResult->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $inmate['p_id'] . '</td>';
                    echo '<td>' . $inmate['mat_id'] . '</td>';
                    echo '<td>' . $inmate['clientName'] . '</td>';
                    echo '<td>' . $inmate['sex'] . '</td>';
                    echo '<td>' . $inmate['p_address'] . '</td>';

                    // Add dosage inputs for each date
                    foreach ($dateRange as $date) {
                        echo '<td>';
                        echo '<input type="number" class="dosage-input" name="patients[' . $inmate['mat_id'] . '][' . $date . ']"
                              value="' . $inmate['dosage'] . '" min="0" step="0.5">';
                        echo '</td>';
                    }

                    echo '<td>' . $inmate['current_status'] . '</td>';
                    echo '<td>';
                    echo '<center>';
                    echo '<a href="history.php?p_id=' . $inmate['p_id'] . '" style="font-size: 24px; color: brown;"><i class="fa fa-exclamation-circle"></i></a>';
                    echo '</center>';
                    echo '</td>';
                    // Build JS-safe date list for the modal
                    $jsDateList = implode(',', $dateRange);
                    echo '<td>';
                    echo '<a href="view-missed.php?mat_id=' . $inmate['mat_id'] . '">View</a> | ';
                    echo '<button type="button" class="dispense-btn"
                              onclick="openDispenseModal(\'' . htmlspecialchars($inmate['mat_id'], ENT_QUOTES) . '\',
                                                          \'' . htmlspecialchars($inmate['clientName'], ENT_QUOTES) . '\',
                                                          \'' . $startDate . '\',
                                                          \'' . $endDate . '\')"
                              style="background:#2C3162;color:white;padding:4px 10px;border:none;border-radius:4px;cursor:pointer;">
                              DISPENSE</button> | ';
                    echo '<a href="../referrals/referral.php?mat_id=' . $inmate['mat_id'] . '">Refer</a> | ';
                    echo '<button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';

                echo '<button type="submit" name="submit_dispensing" class="submit-btn">Submit Bulk Dispensing</button>';
                echo '</form>';
            } else {
                echo '<div class="alert alert-info">No inmates found in the database.</div>';
            }
        }
    }
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         Sequential Dispensing Modal
         Opens when the pharmacist clicks DISPENSE on any inmate row.
         Shows the date range and lets them start the sequential pump flow.
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="dispenseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
         background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; padding:32px; max-width:500px; width:90%;
                    box-shadow:0 12px 40px rgba(0,0,0,.25); position:relative;">
            <!-- Close button -->
            <button onclick="closeDispenseModal()"
                    style="position:absolute;top:12px;right:14px;background:none;border:none;
                           font-size:22px;cursor:pointer;color:#666;">&times;</button>

            <h4 style="color:#2C3162; margin:0 0 6px;">Pump Dispensing</h4>
            <p id="modal-patient-name" style="font-size:1rem; color:#555; margin:0 0 18px;"></p>

            <div style="background:#f8f9fa; border-radius:8px; padding:14px; margin-bottom:18px;">
                <div style="font-weight:700; color:#2C3162; margin-bottom:10px;">
                    Dates to dispense (<span id="modal-date-count"></span>):
                </div>
                <div id="modal-date-chips" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
            </div>

            <p style="font-size:.9rem; color:#888; margin-bottom:20px;">
                The dispensing window will appear for each date in sequence.
                After each pump dispense, the next date is loaded automatically.
            </p>

            <div style="display:flex; gap:12px;">
                <button onclick="closeDispenseModal()"
                        style="flex:1; padding:10px; border:1px solid #ccc; border-radius:8px;
                               background:#fff; cursor:pointer; font-size:.95rem;">
                    Cancel
                </button>
                <a id="modal-start-btn" href="#"
                   style="flex:2; padding:10px; background:#2C3162; color:#fff; border:none;
                          border-radius:8px; cursor:pointer; font-size:1rem; font-weight:700;
                          text-align:center; text-decoration:none; display:block;">
                    🚀 Start Dispensing
                </a>
            </div>
        </div>
    </div>

    <script>
        function removeRow(btn) {
            var row = btn.closest('tr');
            row.style.display = 'none';

            // Disable all inputs in the row so they won't be submitted
            var inputs = row.getElementsByTagName('input');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].disabled = true;
            }
        }

        // ── Sequential dispense modal ────────────────────────────────────────────
        function openDispenseModal(matId, clientName, startDate, endDate) {
            // Build date range between start and end
            var dates = [];
            var cur = new Date(startDate + 'T00:00:00');
            var end = new Date(endDate   + 'T00:00:00');
            while (cur <= end) {
                dates.push(cur.toISOString().split('T')[0]);
                cur.setDate(cur.getDate() + 1);
            }

            // Populate modal
            document.getElementById('modal-patient-name').textContent =
                clientName + ' (' + matId + ')';
            document.getElementById('modal-date-count').textContent = dates.length;

            var chipsEl = document.getElementById('modal-date-chips');
            chipsEl.innerHTML = '';
            dates.forEach(function(d) {
                var dt = new Date(d + 'T00:00:00');
                var label = dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short'});
                var chip = document.createElement('span');
                chip.textContent = label;
                chip.style.cssText = 'background:#e9ecef;color:#495057;padding:4px 10px;'
                                   + 'border-radius:16px;font-size:.8rem;font-weight:600;';
                chipsEl.appendChild(chip);
            });

            // Set start button URL (step 0)
            var url = 'prisons_dispense_sequential.php'
                    + '?mat_id='     + encodeURIComponent(matId)
                    + '&start_date=' + encodeURIComponent(startDate)
                    + '&end_date='   + encodeURIComponent(endDate)
                    + '&step=0';
            document.getElementById('modal-start-btn').href = url;

            // Show modal
            var modal = document.getElementById('dispenseModal');
            modal.style.display = 'flex';
        }

        function closeDispenseModal() {
            document.getElementById('dispenseModal').style.display = 'none';
        }

        // Close on backdrop click
        document.getElementById('dispenseModal').addEventListener('click', function(e) {
            if (e.target === this) closeDispenseModal();
        });

        // Set default end date to 6 days after start date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            var startDate = new Date(this.value);
            var endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 6);

            // Format the date as YYYY-MM-DD
            var endDateStr = endDate.toISOString().split('T')[0];
            document.getElementById('end_date').value = endDateStr;
        });
    </script>
</body>
</html>