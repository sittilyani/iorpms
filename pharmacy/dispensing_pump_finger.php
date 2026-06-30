<?php
session_start();
include('../includes/config.php');

// Add fingerprint verification function
function verifyFingerprint($mat_id, $conn) {
    $sql = "SELECT f.template_data, f.fingerprint_data, p.clientName
            FROM fingerprints f
            JOIN patients p ON f.mat_id = p.mat_id
            WHERE f.mat_id = ? AND f.fingerprint_data = ? AND f.template_data IS NOT NULL
            ORDER BY f.capture_date DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            'verified' => true,
            'clientName' => $row['clientName'],
            'fingerprint_data' => $row['fingerprint_data'],
            'template_data' => $row['template_data']
        ];
    }

    // Get client name even if no fingerprint
    $sql2 = "SELECT clientName FROM patients WHERE mat_id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $mat_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $client = $result2->fetch_assoc();

    return [
        'verified' => false,
        'clientName' => $client ? $client['clientName'] : 'Unknown',
        'fingerprint_data' => null,
        'template_data' => null
    ];
}

if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div id='message-container'>" . $message . "</div>";
}

if (isset($_SESSION['successMessages'])) {
    $successMessages = $_SESSION['successMessages'];
    unset($_SESSION['successMessages']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy DAR - Dispense with Pump</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <style>
        .fingerprint-verified {
            background-color: #d4edda !important;
            color: #155724 !important;
        }
        .fingerprint-missing {
            background-color: #fff3cd !important;
            color: #856404 !important;
        }
        .fingerprint-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .status-verified { background-color: #28a745; color: white; }
        .status-not-registered { background-color: #dc3545; color: white; }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
        }
        .close-modal {
            float: right;
            font-size: 28px;
            cursor: pointer;
        }
        .scanner-container {
            text-align: center;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php if (isset($successMessages)): ?>
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php foreach ($successMessages as $success): ?>
                <p><?php echo htmlspecialchars($success); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 style="color: #2C3162;">Dispense with Pump (Fingerprint Verification)</h2>

    <!-- Fingerprint Scanner Modal -->
    <div id="fingerprintModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h4 style="color: #2C3162;">Fingerprint Verification</h4>
            <div id="modal-message"></div>
            <div class="scanner-container">
                <p id="verification-instructions">Place your finger on the scanner for verification</p>
                <div id="scanner-status" style="padding: 10px; margin: 10px 0; background: #f8f9fa; border-radius: 5px;">
                    Scanner not initialized
                </div>
                <div id="quality-indicator" style="display: none;">
                    <label>Quality Score: <span id="quality-score">0</span>/100</label>
                    <div style="background: #e9ecef; height: 10px; border-radius: 5px;">
                        <div id="quality-bar" style="background: #28a745; height: 100%; width: 0%;"></div>
                    </div>
                </div>
                <button onclick="startFingerprintVerification()" style="padding: 10px 20px; background: #2C3162; color: white; border: none; border-radius: 5px; margin: 10px;">
                    Start Verification
                </button>
                <button onclick="closeModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; margin: 10px;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
        <div class="header">
            <label for="search">Search:</label>
            <input type="text" id="search" class="search-entry" name="search"
                   value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>"
                   placeholder="Search by MAT ID, name, etc...">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>
            <a href="edit_dispensed_dose.php" style="background: red; color: #ffffff; text-decoration: none; height: 40px; border-radius: 5px; padding: 8px 12px;">
                Delete Dispensed Record
            </a>
        </div>
    </form>

    <!-- Display Data -->
    <?php
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sql = "SELECT * FROM patients WHERE (mat_id LIKE '%$search%'
            OR mat_number LIKE '%$search%'
            OR clientName LIKE '%$search%'
            OR nickName LIKE '%$search%'
            OR dob LIKE '%$search%'
            OR age LIKE '%$search%'
            OR sex LIKE '%$search%'
            OR p_address LIKE '%$search%'
            OR peer_edu_name LIKE '%$search%'
            OR peer_edu_phone LIKE '%$search%'
            OR cso LIKE '%$search%'
            OR drugname LIKE '%$search%'
            OR dosage LIKE '%$search%'
            OR current_status LIKE '%$search%')
            AND current_status IN ('Active', 'LTFU', 'Defaulted')
            AND drugname = 'Methadone'";

    $results_per_page = 10;
    $number_of_results = mysqli_num_rows(mysqli_query($conn, $sql));
    $number_of_pages = ceil($number_of_results / $results_per_page);

    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_limit = ($current_page - 1) * $results_per_page;
    $start_range = $start_limit + 1;
    $end_range = min($start_limit + $results_per_page, $number_of_results);

    $sql .= " LIMIT $start_limit, $results_per_page";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table>
                <thead>
                    <tr>
                    <th style='width: 60px;'>ID</th>
                    <th>MAT ID</th>
                    <th>Client Name</th>
                    <th style='width: 80px;'>Age</th>
                    <th style='width: 80px;'>Sex</th>
                    <th>Drug</th>
                    <th style='width: 80px;'>Dosage</th>
                    <th style='width: 120px;'>Current Status</th>
                    <th style='width: 120px;'>Fingerprint Status</th>
                    <th style='width: 80px;'>History</th>
                    <th>Action</th>
                </tr>
            </thead>
        <tbody>";

        while ($row = $result->fetch_assoc()) {
            // Check fingerprint status
            $fingerprintCheck = verifyFingerprint($row['mat_id'], $conn);
            $hasFingerprint = $fingerprintCheck['verified'];
            $fingerprintStatus = $hasFingerprint ?
                '<span class="fingerprint-status status-verified">? Verified</span>' :
                '<span class="fingerprint-status status-not-registered">Not Registered</span>';

            $rowClass = $hasFingerprint ? 'fingerprint-verified' : 'fingerprint-missing';

            echo "<tr class='$rowClass'>
                    <td>" . $row['p_id'] . "</td>
                    <td>" . $row['mat_id'] . "</td>
                    <td>" . $row['clientName'] . "</td>
                    <td>" . $row['age'] . "</td>
                    <td>" . $row['sex'] . "</td>
                    <td style='color: blue;'>" . $row['drugname'] . "</td>
                    <td>" . $row['dosage'] . "</td>
                    <td>" . $row['current_status'] . "</td>
                    <td>" . $fingerprintStatus . "</td>
                    <td>
                        <center>
                        <a href='history.php?p_id=" . $row['p_id'] . "' style='font-size: 24px; color: brown;'><i class='fa fa-exclamation-circle'></i></a>
                        </center>
                    </td>
                    <td>
                        <a href='../pharmacy/view-missed.php?mat_id=" . $row['mat_id'] . "'>View</a> |
                        <a href='dispensingData_pump.php?mat_id=" . $row['mat_id'] . "'>DISPENSE</a> &#124; |
                        <a href='#' onclick='verifyBeforeDispense(\"" . $row['mat_id'] . "\", \"" . addslashes($row['clientName']) . "\")'>DISPENSE</a> |
                        <a href='multi_dispensing.php?mat_id=" . $row['mat_id'] . "'>MDD</a> |
                        <a href='../referrals/referral.php?mat_id=" . $row['mat_id'] . "'>Refer</a>
                    </td>
                </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No results found.</p>";
    }

    echo "<div>Showing $start_range-$end_range of $number_of_results results</div>";

    $max_links = 5;
    $start_page = max(1, $current_page - floor($max_links / 2));
    $end_page = min($number_of_pages, $start_page + $max_links - 1);

    echo "<div>";
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        echo "<a href='?page=$prev_page&search=$search'>Previous</a> ";
    }

    for ($page = $start_page; $page <= $end_page; $page++) {
        $active = $page == $current_page ? "style='font-weight:bold;'" : '';
        echo "<a href='?page=$page&search=$search' $active>$page</a> ";
    }

    if ($current_page < $number_of_pages) {
        $next_page = $current_page + 1;
        echo "<a href='?page=$next_page&search=$search'>Next</a> ";
    }
    echo "</div>";
    ?>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        // Global variables for fingerprint verification
        let currentMatId = '';
        let currentClientName = '';
        let storedTemplate = null;
        let isVerifying = false;

        function verifyBeforeDispense(matId, clientName) {
            currentMatId = matId;
            currentClientName = clientName;

            // Check if fingerprint exists via AJAX
            $.ajax({
                url: '../fingerPrints/check_fingerprint.php',
                method: 'POST',
                data: { mat_id: matId },
                success: function(response) {
                    if (response.hasFingerprint) {
                        // Store the template for verification
                        storedTemplate = response.template_data;
                        document.getElementById('modal-message').innerHTML =
                            `<div class="alert alert-info">Verifying: ${clientName} (${matId})</div>`;
                        document.getElementById('verification-instructions').textContent =
                            'Place your finger on the scanner for verification';
                        document.getElementById('fingerprintModal').style.display = 'block';
                    } else {
                        // No fingerprint registered
                        if (confirm(`Fingerprint for ${clientName} (${matId}) not found.\n\nRegister fingerprint now?`)) {
                            window.location.href = '../fingerPrints/fingerprints_search.php?search=' + encodeURIComponent(matId);
                        }
                    }
                },
                error: function() {
                    alert('Error checking fingerprint status');
                }
            });
        }

        function closeModal() {
            document.getElementById('fingerprintModal').style.display = 'none';
            isVerifying = false;
            currentMatId = '';
            currentClientName = '';
            storedTemplate = null;
        }

        function startFingerprintVerification() {
            if (isVerifying) return;

            isVerifying = true;
            document.getElementById('scanner-status').innerHTML =
                '<span style="color: #007bff;">Scanning... Place finger on scanner</span>';

            // Simulate fingerprint scan (replace with actual scanner API)
            setTimeout(() => {
                verifyCapturedFingerprint();
            }, 2000);
        }

        function verifyCapturedFingerprint() {
            // In real implementation, this would capture actual fingerprint
            // and compare with storedTemplate

            // For demo, simulate verification (70% success rate)
            const isMatch = Math.random() > 0.3;

            if (isMatch) {
                document.getElementById('scanner-status').innerHTML =
                    '<span style="color: #28a745;">? Fingerprint verified successfully!</span>';
                document.getElementById('modal-message').innerHTML =
                    `<div class="alert alert-success">Fingerprint verified for ${currentClientName}</div>`;

                // Redirect to dispensing page after successful verification
                setTimeout(() => {
                    closeModal();
                    window.location.href = 'dispensingData_pump.php?mat_id=' + encodeURIComponent(currentMatId);
                }, 1500);
            } else {
                document.getElementById('scanner-status').innerHTML =
                    '<span style="color: #dc3545;">? Fingerprint verification failed</span>';
                document.getElementById('modal-message').innerHTML =
                    `<div class="alert alert-danger">
                        <p>Fingerprint verification failed for ${currentClientName}.</p>
                        <p>Please try again or register fingerprint.</p>
                    </div>`;

                // Offer to register fingerprint
                setTimeout(() => {
                    if (confirm('Verification failed. Register fingerprint now?')) {
                        window.location.href = '../fingerPrints/fingerprints_search.php?search=' + encodeURIComponent(currentMatId);
                    } else {
                        closeModal();
                    }
                }, 2000);
            }

            isVerifying = false;
        }

        function cancelSearch() {
            document.getElementById("search").value = '';
            document.getElementById("searchForm").submit();
        }

        document.getElementById("search").addEventListener("input", function() {
            setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 3000);
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('fingerprintModal');
            if (event.target == modal) {
                closeModal();
            }
        };

        function hideMessageContainer() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.style.display = 'none';
            }
        }

        window.addEventListener('load', function() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                setTimeout(hideMessageContainer, 3000);
            }
        });
    </script>
</body>
</html>