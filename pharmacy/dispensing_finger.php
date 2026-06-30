<?php
session_start();
include('../includes/config.php');

// Function to check fingerprint status
function checkFingerprintStatus($mat_id, $conn) {
    $sql = "SELECT f.template_data, p.clientName
            FROM fingerprints f
            JOIN patients p ON f.mat_id = p.mat_id
            WHERE f.mat_id = ? AND f.template_data IS NOT NULL
            ORDER BY f.capture_date DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            'hasFingerprint' => true,
            'clientName' => $row['clientName'],
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
        'hasFingerprint' => false,
        'clientName' => $client ? $client['clientName'] : 'Unknown',
        'template_data' => null
    ];
}

if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div id='message-container'>" . $message . "</div>";
}

if (isset($_SESSION['dispensing_successes'])) {
    $successMessages = $_SESSION['dispensing_successes'];
    unset($_SESSION['dispensing_successes']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy DAR - Dispensing</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <style>
        .tablets-display { color: blue; font-weight: bold; }
        .volume-display { color: red; font-weight: bold; }

        .fingerprint-verified {
            background-color: #e8f5e9 !important;
        }
        .fingerprint-missing {
            background-color: #ffebee !important;
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

    <h2 style="color: #2C3162;">Dispensing without Pump</h2>

    <!-- Fingerprint Verification Modal -->
    <div id="fingerprintModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h4 style="color: #2C3162;">Fingerprint Verification Required</h4>
            <div id="modal-message"></div>
            <div id="verification-instructions" style="padding: 20px; text-align: center;">
                <p>Please verify fingerprint before dispensing</p>
                <button onclick="startFingerprintVerification()" style="padding: 10px 20px; background: #2C3162; color: white; border: none; border-radius: 5px; margin: 10px;">
                    Start Fingerprint Scan
                </button>
                <button onclick="closeModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; margin: 10px;">
                    Cancel
                </button>
            </div>
            <div id="verification-result" style="display: none;"></div>
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
            OR dosage LIKE '%$search%'
            OR current_status LIKE '%$search%')
            AND current_status IN ('Active', 'LTFU', 'Defaulted')";

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
                    <th style='width: 160px;'>MAT ID</th>
                    <th>Client Name</th>
                    <th style='width: 80px;'>Age</th>
                    <th style='width: 80px;'>Sex</th>
                    <th style='width: 120px;'>Drug</th>
                    <th style='width: 100px;'>Dosage(mg)</th>
                    <th style='width: 100px;'>Volume(mL)</th>
                    <th style='width: 120px;'>Fingerprint Status</th>
                    <th style='width: 60px;'>Current Status</th>
                    <th style='width: 80px;'>History</th>
                    <th>Action</th>
                </tr>
            </thead>
        <tbody>";

        while ($row = $result->fetch_assoc()) {
            // Check fingerprint status
            $fingerprintCheck = checkFingerprintStatus($row['mat_id'], $conn);
            $hasFingerprint = $fingerprintCheck['hasFingerprint'];

            // Set row class based on fingerprint status
            $rowClass = $hasFingerprint ? 'fingerprint-verified' : 'fingerprint-missing';

            // Fingerprint status badge
            $fingerprintStatus = $hasFingerprint ?
                '<span class="fingerprint-status status-verified">? Registered</span>' :
                '<span class="fingerprint-status status-not-registered">Not Registered</span>';

            $drugname = $row['drugname'];
            $dosage   = (float)$row['dosage'];

            // Check if drug is Methadone (liquid) or tablets
            if (stripos($drugname, 'methadone') !== false) {
                // Methadone: 5mg/mL
                $display_value = $dosage / 5;
                $display_text  = number_format($display_value, 1) . " mL";
                $display_class = "volume-display";
            } else {
                // Tablets
                preg_match('/(\d+(\.\d+)?)\s*mg/i', $drugname, $matches);
                $tablet_strength = isset($matches[1]) ? (float)$matches[1] : 1;

                if ($tablet_strength > 0) {
                    $tablets = $dosage / $tablet_strength;
                    $display_text = number_format($tablets, 1) . " Tablet(s)";
                } else {
                    $display_text = $dosage . " Tablet(s)";
                }
                $display_class = "tablets-display";
            }

            echo "<tr class='$rowClass'>
                <td>{$row['p_id']}</td>
                <td>{$row['mat_id']}</td>
                <td>{$row['clientName']}</td>
                <td>{$row['age']}</td>
                <td>{$row['sex']}</td>
                <td style='color: blue;'>{$row['drugname']}</td>
                <td>{$dosage}</td>
                <td class='{$display_class}'>{$display_text}</td>
                <td>{$fingerprintStatus}</td>
                <td>{$row['current_status']}</td>
                <td>
                    <center>
                        <a href='history.php?p_id={$row['p_id']}' style='font-size:24px;color:brown;'>
                            <i class='fa fa-exclamation-circle'></i>
                        </a>
                    </center>
                </td>
                <td>
                    <a href='../pharmacy/view-missed.php?mat_id={$row['mat_id']}'>View</a> |
                    <a href='#' onclick='verifyFingerprintBeforeDispense(\"" . $row['mat_id'] . "\", \"" . addslashes($row['clientName']) . "\", " . ($hasFingerprint ? 'true' : 'false') . ")'>DISPENSE</a> |
                    <a href='multi_dispensing.php?mat_id={$row['mat_id']}'>MDD</a> |
                    <a href='../referrals/referral.php?mat_id={$row['mat_id']}'>Refer</a>
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
        let hasFingerprint = false;

        function verifyFingerprintBeforeDispense(matId, clientName, fingerprintExists) {
            currentMatId = matId;
            currentClientName = clientName;
            hasFingerprint = fingerprintExists;

            if (!fingerprintExists) {
                // No fingerprint registered
                if (confirm(`Fingerprint for ${clientName} (${matId}) not found.\n\nDo you want to register fingerprint now?`)) {
                    // Redirect to fingerprint registration search
                    window.location.href = '../fingerPrints/fingerprints_search.php?search=' + encodeURIComponent(matId);
                }
                return;
            }

            // Show verification modal
            document.getElementById('modal-message').innerHTML =
                `<div class="alert alert-info">
                    <strong>Fingerprint Verification Required</strong><br>
                    Patient: ${clientName}<br>
                    MAT ID: ${matId}
                </div>`;
            document.getElementById('fingerprintModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('fingerprintModal').style.display = 'none';
            currentMatId = '';
            currentClientName = '';
            hasFingerprint = false;
            document.getElementById('verification-result').style.display = 'none';
            document.getElementById('verification-result').innerHTML = '';
        }

        function startFingerprintVerification() {
            document.getElementById('verification-instructions').innerHTML =
                '<p>Scanning fingerprint... Please place finger on scanner</p>' +
                '<div style="padding: 20px; background: #f8f9fa; border-radius: 5px;">' +
                '   <div class="spinner-border text-primary" role="status">' +
                '       <span class="sr-only">Loading...</span>' +
                '   </div>' +
                '   <p>Verifying...</p>' +
                '</div>';

            // Simulate fingerprint verification (3 seconds)
            setTimeout(() => {
                // For demo purposes, simulate 90% success rate
                const isVerified = Math.random() < 0.9;

                if (isVerified) {
                    document.getElementById('verification-result').innerHTML =
                        `<div class="alert alert-success">
                            <strong>? Fingerprint Verified Successfully!</strong><br>
                            Patient: ${currentClientName}<br>
                            MAT ID: ${currentMatId}
                        </div>`;

                    // Redirect to dispensing page after successful verification
                    setTimeout(() => {
                        closeModal();
                        window.location.href = 'dispensingData.php?mat_id=' + encodeURIComponent(currentMatId);
                    }, 2000);
                } else {
                    document.getElementById('verification-result').innerHTML =
                        `<div class="alert alert-danger">
                            <strong>? Fingerprint Verification Failed</strong><br>
                            Patient: ${currentClientName}<br>
                            MAT ID: ${currentMatId}<br><br>
                            <p>Please try again or contact administrator.</p>
                        </div>`;
                }

                document.getElementById('verification-result').style.display = 'block';
                document.getElementById('verification-instructions').innerHTML =
                    '<p>Verification completed</p>';

            }, 3000);
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