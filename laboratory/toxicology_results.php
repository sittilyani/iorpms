<?php
session_start();
include('../includes/config.php');

// Handle message display
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div id='message-container'>" . htmlspecialchars($message) . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Toxicology Results</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="content-main">
        <h2>Toxicology Results</h2>

        <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
            <div class="header">
                <label for="search">Search:</label>
                <input type="text" id="search" class="search-entry" name="search" value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
                <input type="submit" value="Search" class="search-input">
                <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>

                <label for="print-pdf"></label>
                <button id="print-pdf" onclick="window.print()">Print PDF</button>

                <label for="export-excel"></label>
                <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

                Total Tests Done:&nbsp;&nbsp;<span style="font-weight: normal; color: red;"><?php include '../counts/toxicology_count.php'; ?></span><br>
            </div>
        </form>
    </div>

    <!-- Display Data -->
    <?php
    // Handle search functionality
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

    // SQL query to get the latest record per mat_id
    $sqlCount = "SELECT COUNT(DISTINCT mat_id) as total
                 FROM toxicology_results
                 WHERE mat_id LIKE ?
                    OR tox_id LIKE ?
                    OR clientName LIKE ?
                    OR mode_drug_use LIKE ?
                    OR date_of_test LIKE ?
                    OR next_appointment LIKE ?
                    OR visitDate LIKE ?";

    $sql = "SELECT t1.*
            FROM toxicology_results t1
            INNER JOIN (
                SELECT mat_id, MAX(date_of_test) as max_date
                FROM toxicology_results
                WHERE mat_id LIKE ?
                   OR tox_id LIKE ?
                   OR clientName LIKE ?
                   OR mode_drug_use LIKE ?
                   OR date_of_test LIKE ?
                   OR next_appointment LIKE ?
                   OR visitDate LIKE ?
                GROUP BY mat_id
            ) t2 ON t1.mat_id = t2.mat_id AND t1.date_of_test = t2.max_date
            ORDER BY t1.mat_id";

    // Pagination
    $results_per_page = 5;

    // Get total number of unique mat_id records
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param('sssssss', $search, $search, $search, $search, $search, $search, $search);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $number_of_results = $resultCount->fetch_assoc()['total'];
    $number_of_pages = ceil($number_of_results / $results_per_page);
    $stmtCount->close();

    // Calculate current page and limit
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_limit = ($current_page - 1) * $results_per_page;

    // Append LIMIT to the main query
    $sql .= " LIMIT ?, ?";

    // Fetch data from the database
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssssi', $search, $search, $search, $search, $search, $search, $search, $start_limit, $results_per_page);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Display the data in a table
        echo "<table border='1'>
                <tr>
                    <th>Tox ID</th>
                    <th>MAT ID</th>
                    <th>Client Name</th>
                    <th>Mode of Drug Use</th>
                    <th>Date of Test</th>
                    <th>Last Visit Date</th>
                    <th>Lab Officer</th>
                    <th>Action</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['tox_id'] ?? 'N/A') . "</td>
                    <td>" . htmlspecialchars($row['mat_id']) . "</td>
                    <td>" . htmlspecialchars($row['clientName']) . "</td>
                    <td>" . htmlspecialchars($row['mode_drug_use']) . "</td>
                    <td>" . htmlspecialchars($row['date_of_test']) . "</td>
                    <td>" . htmlspecialchars($row['visitDate']) . "</td>
                    <td>" . htmlspecialchars($row['lab_officer_name']) . "</td>
                    <td>
                        <a href='../laboratory/view_toxicology_results_history.php?mat_id=" . htmlspecialchars($row['mat_id']) . "'>View history</a>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<div>No results found.</div>";
    }

    $stmt->close();
    $conn->close();
    ?>

    <!-- Pagination links -->
    <?php
    // Calculate pagination details
    $start_range = max(1, $current_page - 2); // Ensure the start range is at least 1
    $end_range = min($number_of_pages, $start_range + 4); // Ensure the end range is within the total number of pages

    echo "<div>Showing $start_range to $end_range of $number_of_results results</div>";
    echo "<div>";

    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        echo "<a href='?page=$prev_page&search=" . urlencode($search) . "'>Previous</a> ";
    }

    // Page numbers
    for ($page = $start_range; $page <= $end_range; $page++) {
        echo "<a href='?page=$page&search=" . urlencode($search) . "'>$page</a> ";
    }

    // Next link
    if ($current_page < $number_of_pages) {
        $next_page = $current_page + 1;
        echo "<a href='?page=$next_page&search=" . urlencode($search) . "'>Next</a> ";
    }

    echo "</div>";
    ?>

    <script>
        function cancelSearch() {
            window.location.href = '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>';
        }

        function exportToExcel() {
            var table = document.querySelector("table");
            var html = table.outerHTML;
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement("a");
            link.href = uri;
            link.style = "visibility:hidden";
            link.download = "toxicology_results.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Real-Time Filtering
        document.getElementById("search").addEventListener("input", function() {
            setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 2000); // Submit form after a brief delay (e.g., 2000 milliseconds)
        });

        // Hide message after 5 seconds
        window.addEventListener('load', function() {
            var messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                setTimeout(function() {
                    messageContainer.style.display = 'none';
                }, 5000); // Hide message after 5 seconds
            }
        });
    </script>
</body>
</html>