<?php
session_start();
include('../includes/config.php');
include ("../includes/footer.php");
include ("../includes/header.php");

?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div>" . $message . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>stopped patients</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <!-- Add your CSS styling here -->
    <style>
    body{

             font-family: "Times New Roman", Times, serif;
        }
        .header{

            margin: 10px 30px;
        }

        .register-input{
            display: in-line block;
            background-color: #2C3162;
            color: white;
            align-content: center;
            align-items: center;
            padding: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancel-input{
            background-color: green;
            color: white;
            width: 80px;
            margin-right: 40px;
            margin-left: 40px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
        }
         .search-input{
            background-color: grey;
            color: white;
            width: 100px;
            margin-right: 10px;
            margin-left: 20px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
        }

         #sign-input{
            background-color: red;
            color: white;
            width: 120px;
            margin-right: 10px;
            margin-left: 100px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
            padding: 5px;
        }
         h2{
             font-size: 24px;
             margin-bottom: 10px;
             color: red;
         }
         th, td{
             padding: 10px 10px;
             white-space: nowrap;
         }
         #print-pdf{
           background-color: grey;
           color: white;
           width: 100px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }

          #export-excel{
           background-color: green;
           color: white;
           width: 140px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }
    </style>
</head>
<body>
    <h2 style="color: #2C3162; ">All stopped Patients List</h2>



    <form id="searchForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
    <div class="header">
        <label for="search">Search:</label>
        <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search" class="search-input">
        <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button> <!-- Add Cancel Button -->

        <!-- Label for printing to PDF -->
        <label for="print-pdf"></label>
        <button id="print-pdf" onclick="window.print()">Print PDF</button>

        <!-- Label for exporting to Excel -->
        <label for="export-excel"></label>
        <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

        stopped:&nbsp;&nbsp;<span style="font-weight: normal; color: red;" ><?php include '../counts/stopped_patient_count.php'; ?></span><br>
    </div>
    </form>

    <script>
    // Function to export table data to Excel
        function exportToExcel() {
            var table = document.getElementsByTagName("table")[0];
            var html = table.outerHTML;

            // Format HTML for Excel
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);

            // Create temporary link element and trigger download
            var link = document.createElement("a");
            link.href = uri;
            link.style = "visibility:hidden";
            link.download = "data.xls";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>


    <!-- Display Data -->
    <?php
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sql = "SELECT * FROM patients
            WHERE (mat_id LIKE '%$search%' OR mat_number LIKE '%$search%' OR clientName LIKE '%$search%' OR nickName LIKE '%$search%' OR dob LIKE '%$search%' OR age LIKE '%$search%' OR sex LIKE '%$search%' OR p_address LIKE '%$search%' OR peer_edu_name LIKE '%$search%' OR peer_edu_phone LIKE '%$search%' OR cso LIKE '%$search%' OR dosage LIKE '%$search%')
            AND current_status = 'stopped'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>p_ID</th>
                    <th>MAT ID</th>
                    <th>MAT Number</th>
                    <th>Client Name</th>
                    <th>Nick Name</th>
                    <th>Date of Birth</th>
                    <th>Date of Enrolment</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Physical Address</th>
                    <th>CSO</th>
                    <th>Dosage</th>
                    <th>Current Status</th>
                    <th>Action</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['p_id'] . "</td>
                    <td>" . $row['mat_id'] . "</td>
                    <td>" . $row['mat_number'] . "</td>
                    <td>" . $row['clientName'] . "</td>
                    <td>" . $row['nickName'] . "</td>
                    <td>" . $row['dob'] . "</td>
                    <td>" . $row['reg_date'] . "</td>
                    <td>" . $row['age'] . "</td>
                    <td>" . $row['sex'] . "</td>
                    <td>" . $row['p_address'] . "</td>
                    <td>" . $row['cso'] . "</td>
                    <td>" . $row['dosage'] . "</td>
                    <td>" . $row['current_status'] . "</td>
                    <td>
                        <a href='../Psycho-social/view.php?p_id=" . $row['p_id'] . "'>View</a> &#124;
                        <a href='../Psycho-social/update.php?p_id=" . $row['p_id'] . "'>Update</a> &#124;
                        <a href='../psycho-social/delete.php?p_id=" . $row['p_id'] . "'>Delete</a>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<div>No results found.</div>";
    }
    ?>
    <script>
        function exportToExcel() {
            var table = document.getElementsByTagName("table")[0];
            var html = table.outerHTML;
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"><style>td { border: 1px solid black; }</style></head><body>' + html + '</body></html>');
            var link = document.createElement("a");
            link.href = uri;
            link.download = "data.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>

    <script>
        function cancelSearch() {
            document.getElementById("search").value = ''; // Clear search input
            document.getElementById("searchForm").submit(); // Submit form to reset
        }

        // Real-Time Filtering
        document.getElementById("search").addEventListener("input", function() {
            setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 2000); // Submit form after a brief delay (e.g., 500 milliseconds)
        });
    </script>
    <script>
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
            setTimeout(hideMessageContainer, 5000); // Hide message after 5 seconds (5000 milliseconds)
        }
    });
</script>

<script>
    // Function to export table data to Excel
    function exportToExcel() {
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;

        // Format HTML for Excel
        var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"><meta name=ProgId content=Excel.Sheet><meta name=Generator content="Microsoft Excel 15"><style>td { border: 1px solid black; }</style></head><body>' + html + '</body></html>');

        // Create temporary link element and trigger download
        var link = document.createElement("a");
        link.href = uri;
        link.style = "visibility:hidden";
        link.download = "data.xls";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>
