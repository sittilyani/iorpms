<?php
session_start();
include('../includes/config.php');
include("../includes/footer.php");
include('../includes/header.php');
?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div id='message-container'>" . $message . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FingerPrint Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>
        /* Add your CSS styling here */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            margin-left: 10px;
            margin-right: 10px;
        }

        .header {
            margin-bottom: 20px;
        }

        .search-input, .cancel-input, #sign-input {
            color: white;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
            padding: 5px;
        }

        .search-input {
            background-color: grey;
            width: 100px;
        }

        .cancel-input {
            background-color: green;
            width: 80px;
            margin: 0 20px;
        }

        #sign-input {
            background-color: red;
            width: 120px;
            margin-left: 100px;
        }

        h2 {
            margin-top: 30px;
            margin-bottom: 20px;
            color: #2C3162;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h2>Capture Fingerprints Form</h2>

    <!-- Search Form -->
    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
        <div class="header">
            <label for="search">Search:</label>
            <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button>
        </div>
    </form>

    <!-- Display Data -->
    <?php
    // Retrieve search query or default to an empty string
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

    // Define the SQL query
    $sql = "SELECT * FROM patients
            WHERE mat_id LIKE '%$search%'
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
               OR current_status LIKE '%$search%'";

    // Pagination setup
    $results_per_page = 5;
    $number_of_results = mysqli_num_rows(mysqli_query($conn, $sql));
    $number_of_pages = ceil($number_of_results / $results_per_page);

    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_limit = ($current_page - 1) * $results_per_page;

    // Add limit to SQL query
    $sql .= " LIMIT $start_limit, $results_per_page";

    // Execute the query
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>p_ID</th>
                    <th>MAT ID</th>
                    <th>MAT Number</th>
                    <th>Client Name</th>
                    <th>Nick Name</th>
                    <th>Date of Birth</th>
                    <th>Sex</th>
                    <th>Current Status</th>
                    <th>Action</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['p_id']}</td>
                    <td>{$row['mat_id']}</td>
                    <td>{$row['mat_number']}</td>
                    <td>{$row['clientName']}</td>
                    <td>{$row['nickName']}</td>
                    <td>{$row['dob']}</td>
                    <td>{$row['sex']}</td>
                    <td>{$row['current_status']}</td>
                    <td>
                        <a href='../Psycho-social/view.php?p_id={$row['p_id']}'>View</a> &#124;
                        <a href='fingerprintData.php?p_id={$row['p_id']}'>RegisterPrint</a> &#124;
                        <a href='delete.php?p_id={$row['p_id']}'>Delete</a>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No results found.</p>";
    }

    // Pagination links
    echo "<div>";
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        echo "<a href='?page=$prev_page&search=$search'>Previous</a> ";
    }
    for ($page = 1; $page <= $number_of_pages; $page++) {
        echo "<a href='?page=$page&search=$search'>$page</a> ";
    }
    if ($current_page < $number_of_pages) {
        $next_page = $current_page + 1;
        echo "<a href='?page=$next_page&search=$search'>Next</a>";
    }
    echo "</div>";
    ?>

    <script>
        function cancelSearch() {
            document.getElementById("search").value = ''; // Clear search input
            document.getElementById("searchForm").submit(); // Submit form to reset
        }
    </script>
</body>
</html>
