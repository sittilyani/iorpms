<?php
session_start();
include('../includes/config.php');
?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div id='message-container' class='alert alert-info'>" . $message . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Photos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">

    <style>
        /* Mobile-first responsive design */
        .content-main {
            padding: 10px;
            overflow-x: auto;
        }

        .header {
            margin-bottom: 20px;
        }

        .search-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-entry {
            flex: 1;
            min-width: 200px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-input, .cancel-input {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-input {
            background-color: #2C3162;
            color: white;
        }

        .cancel-input {
            background-color: #dc3545;
            color: white;
        }

        /* Responsive table styles */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Minimum width to prevent cramping */
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #ddd;
            white-space: nowrap;
        }

        th {
            background-color: #2C3162;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        /* Action links styling */
        .action-links a {
            color: #2C3162;
            text-decoration: none;
            margin-right: 5px;
            font-size: 14px;
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }

        .action-links a:hover {
            text-decoration: none;
            background-color: #f0f0f0;
        }

        .update-link {
            color: #28a745 !important;
        }

        .delete-link {
            color: #dc3545 !important;
        }

        .photo-status {
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .photo-exists {
            background-color: #d4edda;
            color: #155724;
        }

        .no-photo {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Pagination styling */
        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            align-items: center;
        }

        .pagination a {
            padding: 8px 12px;
            background-color: #2C3162;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .pagination a:hover {
            background-color: #1a1f3a;
        }

        .results-info {
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        /* Mobile-specific adjustments */
        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-entry {
                min-width: unset;
                width: 100%;
            }

            .search-input, .cancel-input {
                width: 100%;
                margin-top: 5px;
            }

            h2 {
                font-size: 1.5rem;
                text-align: center;
            }

            th, td {
                padding: 8px 4px;
                font-size: 12px;
            }

            .action-links a {
                font-size: 12px;
                display: block;
                margin: 2px 0;
                text-align: center;
            }

            .consent-status {
                font-size: 10px;
                display: block;
                text-align: center;
                margin: 2px 0;
            }

            .pagination a {
                padding: 6px 10px;
                font-size: 12px;
            }
        }

        /* Very small screens */
        @media (max-width: 480px) {
            .container {
                padding: 5px;
            }

            table {
                min-width: 600px;
            }

            th, td {
                padding: 6px 3px;
                font-size: 11px;
            }

            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="content-main">
        <h2 style="color: #2C3162;">Clients' Consent Form</h2>

        <!-- Search Form -->
        <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
            <div class="header">
                <div class="search-container">
                    <label for="search">Search:</label>
                    <input type="text" id="search" class="search-entry" name="search"
                           value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>"
                           placeholder="Search by MAT ID, name, etc...">
                    <input type="submit" value="Search" class="search-input">
                    <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>
                </div>
                <a href="read.php" style="color: #2C3162;">Display all clients</a>
            </div>
        </form>

        <!-- Display Data -->
        <?php
        // Handle search functionality
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sql = "SELECT p.*,
                       (SELECT COUNT(*) FROM consents WHERE mat_id = p.mat_id) as consent_count,
                       (SELECT client_fingerprint, date_of_consent FROM consents WHERE mat_id = p.mat_id ORDER BY date_of_consent DESC LIMIT 1) as latest_fingerprint
                FROM patients p
                WHERE p.mat_id LIKE '%$search%' OR p.mat_number LIKE '%$search%' OR p.clientName LIKE '%$search%' OR p.nickName LIKE '%$search%' OR p.dob LIKE '%$search%' OR p.age LIKE '%$search%' OR p.sex LIKE '%$search%' OR p.p_address LIKE '%$search%' OR p.peer_edu_name LIKE '%$search%' OR p.peer_edu_phone LIKE '%$search%' OR p.cso LIKE '%$search%' OR p.dosage LIKE '%$search%' OR p.current_status LIKE '%$search%'";

        // Pagination
        $results_per_page = 5;
        $number_of_results = mysqli_num_rows(mysqli_query($conn, $sql));
        $number_of_pages = ceil($number_of_results / $results_per_page);

        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
        $start_limit = ($current_page - 1) * $results_per_page;

        $sql .= " LIMIT $start_limit, $results_per_page";

        // Fetch data from the database
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Display the data in a responsive table
            echo "<div class='table-container'>";
            echo "<table>
                    <thead>
                        <tr>
                            <th style='width: 80px;'>Pt ID</th>
                            <th style='width: 160px;'>Date of consent</th>
                            <th style='width: 160px;'>MAT ID</th>
                            <th style='width: 160px;'>Client Name</th>
                            <th style='width: 140px;'>Consent Status</th>
                            <th style='width: 140px;'>FingerPrint Status</th>
                            <th style='width: 160px;'>Date of Birth</th>
                            <th  style='width: 120px;'>Sex</th>
                            <th style='width: 140px;'>Current Status</th>
                            <th style='width: 260px;'>Action</th>
                        </tr>
                    </thead>
                    <tbody>";

            while ($row = $result->fetch_assoc()) {
                $hasfingerprint = $row['fingerprint_count'] > 0;
                $fingerprintStatus = $hasfingerprint ?
                    '<span class="consent-status consent-exists">Consented</span>' :
                    '<span class="consent-status no-consent">No consent</span>';

                echo "<tr>
                        <td>" . htmlspecialchars($row['p_id']) . "</td>
                        <td>" . htmlspecialchars($row['date_of_consent']) . "</td>
                        <td>" . htmlspecialchars($row['mat_id']) . "</td>
                        <td>" . htmlspecialchars($row['clientName']) . "</td>
                        <td>" . $fingerprintStatus . "</td>
                        <td>" . htmlspecialchars($row['dob']) . "</td>
                        <td>" . htmlspecialchars($row['sex']) . "</td>
                        <td>" . htmlspecialchars($row['current_status']) . "</td>
                        <td class='action-links'>
                            <a href='../patients/view_patient.php?p_id=" . $row['p_id'] . "'>View</a>";

                if ($hasPhoto) {
                    // If photo exists, show Update and Delete options
                    echo "<a href='../fingerPrints/finegerprint_capture.php?p_id=" . $row['p_id'] . "&action=update' class='update-link'>Update Photo</a>";
                    echo "<a href='../fingerPrints/fingerprint_capture.php?p_id=" . $row['p_id'] . "&action=update' class='update-link'>Update FingerPrint</a>";
                    echo "<a href='../fingerPrints/fingerprint_capture.php?p_id=" . $row['p_id'] . "&action=update' class='update-link'>Sign Consent</a>";
                } else {
                    // If no photo exists, show Capture option
                    echo "<a href='../fingerPrints/fingerprint_capture.php?p_id=" . $row['p_id'] . "'>Capture Photo</a>";
                    echo "<a href='../fingerPrints/fingerprint_capture.php?p_id=" . $row['p_id'] . "'>Capture FingerPrint</a>";
                }

                echo "</td>
                    </tr>";
            }
            echo "</tbody></table>";
            echo "</div>";

            // Pagination links
            $start_range = max(1, $current_page - 2);
            $end_range = min($number_of_pages, $start_range + 4);

            echo "<div class='results-info'>Showing " . (($current_page - 1) * $results_per_page + 1) . " to " . min($current_page * $results_per_page, $number_of_results) . " of $number_of_results results</div>";

            echo "<div class='pagination'>";
            if ($current_page > 1) {
                $prev_page = $current_page - 1;
                echo "<a href='?page=$prev_page" . (isset($_GET['search']) ? "&search=" . urlencode($_GET['search']) : "") . "'>Previous</a>";
            }

            // Page numbers
            for ($page = $start_range; $page <= $end_range; $page++) {
                $active = ($page == $current_page) ? " style='background-color: #1a1f3a;'" : "";
                echo "<a href='?page=$page" . (isset($_GET['search']) ? "&search=" . urlencode($_GET['search']) : "") . "'$active>$page</a>";
            }

            // Next link
            if ($current_page < $number_of_pages) {
                $next_page = $current_page + 1;
                echo "<a href='?page=$next_page" . (isset($_GET['search']) ? "&search=" . urlencode($_GET['search']) : "") . "'>Next</a>";
            }
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>No records found.</div>";
        }
        ?>
    </div>

    <script>
        function cancelSearch() {
            document.getElementById("search").value = '';
            document.getElementById("searchForm").submit();
        }

        // Real-Time Filtering with debounce
        let searchTimeout;
        document.getElementById("search").addEventListener("input", function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 1000); // Reduced delay to 1 second
        });

        // Function to remove the message container
        function hideMessageContainer() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.style.display = 'none';
            }
        }

        // Check if the message container exists and hide it after 5 seconds
        window.addEventListener('load', function() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                setTimeout(hideMessageContainer, 5000);
            }
        });
    </script>
</body>
</html>