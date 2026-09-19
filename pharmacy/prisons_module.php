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

// ── Date range (shared by the search form and the selected patient's dispense
//    action) — this generation logic is unchanged from before. ────────────────
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d', strtotime('+6 days'));

// ── Patient search ───────────────────────────────────────────────────────────
// This page dispenses to ONE patient at a time: search for them (any patient,
// not just those flagged as inmates), select them, pick the date range, then
// dispense — the underlying per-date sequential dispensing logic
// (prisons_dispense_sequential.php → prisons_dispense_pump.php /
// prisons_dispense_tablet.php, routed by the patient's actual drug) is unchanged.
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

// ── Selected patient ─────────────────────────────────────────────────────────
$selectedMatId = isset($_GET['mat_id']) ? trim($_GET['mat_id']) : '';
$selectedPatient = null;
if ($selectedMatId !== '') {
    $selStmt = $conn->prepare("SELECT * FROM patients WHERE mat_id = ?");
    $selStmt->bind_param('s', $selectedMatId);
    $selStmt->execute();
    $selectedPatient = $selStmt->get_result()->fetch_assoc();
    $selStmt->close();
}

$dateRangeInvalid = strtotime($startDate) > strtotime($endDate);
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

        .search-form {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .results-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .results-table th, .results-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .results-table th {
            background-color: #2C3162;
            color: white;
        }

        .results-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .patient-card {
            background: #66ccff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
        }

        .patient-card .field {
            display: inline-block;
            min-width: 220px;
            margin: 6px 20px 6px 0;
        }

        .patient-card .field label {
            display: block;
            font-weight: bold;
            color: #2C3162;
            font-size: 13px;
        }

        .patient-card .field .value {
            background: #fff3cd;
            border-radius: 4px;
            padding: 6px 10px;
            display: inline-block;
            min-width: 200px;
        }

        .date-range-form {
            margin: 20px 0;
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

        .alert ul {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <h2 style="color: #2C3162;">Bulk Dispensing</h2>

    <div class="search-form">
        <form method="GET" action="">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <label for="search_patient">Search Patient (MAT ID, MAT Number or Name):</label>
            <input type="text" id="search_patient" name="search_patient" style="margin-left: 10px; margin-right: 10px; width: 260px;"
                   value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="e.g. 11094MAT0002 or client name">
            <button type="submit" class="submit-btn" style="margin-top: 0;">Search</button>
        </form>

        <?php if ($searchTerm !== ''): ?>
            <?php if (empty($searchResults)): ?>
                <div class="alert alert-info" style="margin-top: 15px;">No matching patients found.</div>
            <?php else: ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>MAT ID</th>
                            <th>MAT Number</th>
                            <th>Client Name</th>
                            <th>Sex</th>
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
                                <td><?php echo htmlspecialchars($sr['drugname'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($sr['current_status']); ?></td>
                                <td>
                                    <?php if ($sr['mat_id'] === $selectedMatId): ?>
                                        <span style="color:green;">✓ Selected below</span>
                                    <?php else: ?>
                                        <a href="?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&search_patient=<?php echo urlencode($searchTerm); ?>&mat_id=<?php echo urlencode($sr['mat_id']); ?>"
                                           class="submit-btn" style="padding: 4px 10px; font-size: 13px; margin-top:0; text-decoration:none;">Select</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($selectedPatient): ?>
        <div class="patient-card">
            <h3 style="color:#2C3162; margin-top:0;">
                <?php echo htmlspecialchars($selectedPatient['clientName']); ?>
                <small style="font-weight:normal;">(<?php echo htmlspecialchars($selectedPatient['mat_id']); ?>)</small>
            </h3>

            <div class="field">
                <label>MAT Number</label>
                <span class="value"><?php echo htmlspecialchars($selectedPatient['mat_number']); ?></span>
            </div>
            <div class="field">
                <label>Sex</label>
                <span class="value"><?php echo htmlspecialchars($selectedPatient['sex']); ?></span>
            </div>
            <div class="field">
                <label>Physical Address</label>
                <span class="value"><?php echo htmlspecialchars($selectedPatient['p_address']); ?></span>
            </div>
            <div class="field">
                <label>Drug</label>
                <span class="value"><?php echo htmlspecialchars($selectedPatient['drugname'] ?? ''); ?></span>
            </div>
            <div class="field">
                <label>Current Status</label>
                <span class="value"><?php echo htmlspecialchars($selectedPatient['current_status']); ?></span>
            </div>

            <form method="GET" action="" class="date-range-form">
                <input type="hidden" name="search_patient" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($selectedPatient['mat_id']); ?>">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?php echo htmlspecialchars($startDate); ?>">

                <label for="end_date" style="margin-left: 20px;">End Date:</label>
                <input type="date" id="end_date" name="end_date" required
                       value="<?php echo htmlspecialchars($endDate); ?>">

                <button type="submit" class="submit-btn" style="margin-left: 20px;">Update Dates</button>
            </form>

            <?php if ($dateRangeInvalid): ?>
                <div class="alert alert-danger">End date must be after start date.</div>
            <?php else: ?>
                <button type="button"
                        onclick="openDispenseModal('<?php echo htmlspecialchars($selectedPatient['mat_id'], ENT_QUOTES); ?>',
                                                    '<?php echo htmlspecialchars($selectedPatient['clientName'], ENT_QUOTES); ?>',
                                                    '<?php echo htmlspecialchars($startDate, ENT_QUOTES); ?>',
                                                    '<?php echo htmlspecialchars($endDate, ENT_QUOTES); ?>')"
                        style="background:#2C3162;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-size:15px;">
                        DISPENSE</button>
                &nbsp;
                <a href="history.php?p_id=<?php echo urlencode($selectedPatient['p_id']); ?>">History</a>
                &nbsp;|&nbsp;
                <a href="view-missed.php?mat_id=<?php echo urlencode($selectedPatient['mat_id']); ?>">View Missed</a>
                &nbsp;|&nbsp;
                <a href="../referrals/referral.php?mat_id=<?php echo urlencode($selectedPatient['mat_id']); ?>">Refer</a>
            <?php endif; ?>
        </div>
    <?php elseif ($selectedMatId !== ''): ?>
        <div class="alert alert-danger">Patient not found: <?php echo htmlspecialchars($selectedMatId); ?></div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         Sequential Dispensing Modal
         Opens when the pharmacist clicks DISPENSE for the selected patient.
         Shows the date range and lets them start the sequential dispensing flow.
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="dispenseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
         background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; padding:32px; max-width:500px; width:90%;
                    box-shadow:0 12px 40px rgba(0,0,0,.25); position:relative;">
            <!-- Close button -->
            <button onclick="closeDispenseModal()"
                    style="position:absolute;top:12px;right:14px;background:none;border:none;
                           font-size:22px;cursor:pointer;color:#666;">&times;</button>

            <h4 style="color:#2C3162; margin:0 0 6px;">Bulk Dispensing</h4>
            <p id="modal-patient-name" style="font-size:1rem; color:#555; margin:0 0 18px;"></p>

            <div style="background:#f8f9fa; border-radius:8px; padding:14px; margin-bottom:18px;">
                <div style="font-weight:700; color:#2C3162; margin-bottom:10px;">
                    Dates to dispense (<span id="modal-date-count"></span>):
                </div>
                <div id="modal-date-chips" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
            </div>

            <p style="font-size:.9rem; color:#888; margin-bottom:20px;">
                The dispensing window will appear for each date in sequence.
                After each dispense, the next date is loaded automatically.
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
    </script>
</body>
</html>
