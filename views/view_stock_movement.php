<?php

include ("../includes/header.php");

?>

<!-- Display stock movement table -->
    <h3>Stock Movement History</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Trans ID</th>
                <th>Opening Balance</th>
                <th>Received Stock</th>
                <th>Received From</th>
                <th>Total Stock</th>
                <th>Issued Stock</th>
                <th>Issued To</th>
                <th>Closing Stock</th>
                <th>Transaction Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch stock movement records from the database
            $sql = "SELECT * FROM stock_movements";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["transID"] . "</td>";
                    echo "<td>" . $row["opening_balance"] . "</td>";
                    echo "<td>" . $row["received_stock"] . "</td>";
                    echo "<td>" . $row["received_from"] . "</td>";
                    echo "<td>" . $row["total_stock"] . "</td>";
                    echo "<td>" . $row["issued_stock"] . "</td>";
                    echo "<td>" . $row["issued_to"] . "</td>";
                    echo "<td>" . $row["closing_stock"] . "</td>";
                    echo "<td>" . $row["trans_date"] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>