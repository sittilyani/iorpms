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
    <title>Fingerprint Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">

    <style>
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

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
            background-color: white;
        }

        th, td {
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #2C3162;
            color: white;
        }

        .action-links a {
            color: #2C3162;
            text-decoration: none;
            margin-right: 5px;
            padding: 2px 6px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }

        .action-links a:hover {
            background-color: #f0f0f0;
        }

        .fingerprint-status {
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .print-exists {
            background-color: #d4edda;
            color: #155724;
        }

        .no-print {
            background-color: #f8d7da;
            color: #721c24;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            background-color: #2C3162;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="content-main">
        <h2 style="color: #2C3162;">Fingerprint Registration</h2>

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

        <?php
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sql = "SELECT p.*,
                       (SELECT COUNT(*) FROM fingerprints WHERE mat_id = p.mat_id) as print_count
                FROM patients p
                WHERE p.mat_id LIKE '%$search%' OR p.mat_number LIKE '%$search%' OR p.clientName LIKE '%$search%'
                OR p.nickName LIKE '%$search%' OR p.dob LIKE '%$search%' OR p.age LIKE '%$search%'
                OR p.sex LIKE '%$search%' OR p.p_address LIKE '%$search%' OR p.peer_edu_name LIKE '%$search%'
                OR p.peer_edu_phone LIKE '%$search%' OR p.cso LIKE '%$search%' OR p.dosage LIKE '%$search%'
                OR p.current_status LIKE '%$search%'";

        $results_per_page = 5;
        $number_of_results = mysqli_num_rows(mysqli_query($conn, $sql));
        $number_of_pages = ceil($number_of_results / $results_per_page);

        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
        $start_limit = ($current_page - 1) * $results_per_page;

        $sql .= " LIMIT $start_limit, $results_per_page";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<div class='table-container'>";
            echo "<table>
                    <tr>
                        <th>Pt ID</th>
                        <th>MAT ID</th>
                        <th>MAT No.</th>
                        <th>Client Name</th>
                        <th>Fingerprint Status</th>
                        <th>Sex</th>
                        <th>Current Status</th>
                        <th>Action</th>
                    </tr>";

            while ($row = $result->fetch_assoc()) {
                $hasPrint = $row['print_count'] > 0;
                $printStatus = $hasPrint ?
                    '<span class="fingerprint-status print-exists">Registered</span>' :
                    '<span class="fingerprint-status no-print">Not Registered</span>';

                echo "<tr>
                        <td>" . htmlspecialchars($row['p_id']) . "</td>
                        <td>" . htmlspecialchars($row['mat_id']) . "</td>
                        <td>" . htmlspecialchars($row['mat_number']) . "</td>
                        <td>" . htmlspecialchars($row['clientName']) . "</td>
                        <td>" . $printStatus . "</td>
                        <td>" . htmlspecialchars($row['sex']) . "</td>
                        <td>" . htmlspecialchars($row['current_status']) . "</td>
                        <td class='action-links'>
                            <a href='../patients/view_patient.php?p_id=" . $row['p_id'] . "'>View</a>";

                if ($hasPrint) {
                    echo "<a href='fingerprint_capture.php?p_id=" . $row['p_id'] . "&action=update'>Update Print</a>";
                    echo "<a href='delete_fingerprint.php?p_id=" . $row['p_id'] . "' onclick='return confirm(\"Delete fingerprint?\")'>Delete</a>";
                } else {
                    echo "<a href='fingerprint_capture.php?p_id=" . $row['p_id'] . "'>Register Print</a>";
                }

                echo "</td></tr>";
            }
            echo "</table></div>";

            // Pagination
            $start_range = max(1, $current_page - 2);
            $end_range = min($number_of_pages, $start_range + 4);

            echo "<div class='pagination'>";
            if ($current_page > 1) {
                $prev_page = $current_page - 1;
                echo "<a href='?page=$prev_page&search=" . urlencode($search) . "'>Previous</a>";
            }

            for ($page = $start_range; $page <= $end_range; $page++) {
                $active = ($page == $current_page) ? " style='background-color: #1a1f3a;'" : "";
                echo "<a href='?page=$page&search=" . urlencode($search) . "'$active>$page</a>";
            }

            if ($current_page < $number_of_pages) {
                $next_page = $current_page + 1;
                echo "<a href='?page=$next_page&search=" . urlencode($search) . "'>Next</a>";
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

        setTimeout(function() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) messageContainer.style.display = 'none';
        }, 5000);
    </script>
</body>
</html>