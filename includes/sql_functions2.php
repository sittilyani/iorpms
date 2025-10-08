                <?php
                    // Include the configuration file
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // Get the current month
                    $currentMonth = date("m");
                    $currentYear = date("Y");

                    // Calculate the start and end date of the current month
                    $startDate = date("Y-m-01");
                    $endDate = date("Y-m-t");

                    // SQL query
                    $sql = "SELECT SUM(dosage) AS methadone_total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Methadone'";

                    $stmt = $conn->prepare($sql);

                    // Check if the query was prepared successfully
                    if ($stmt) {
                        // Bind the parameters
                        $stmt->bind_param("ss", $startDate, $endDate);

                        // Execute the query
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Fetch the row
                        $row = $result->fetch_assoc();

                        // Output the total dosage dispensed for the month
                        echo '<p>Methadone Disp in the Month: <span style="font-weight: bold; color: #0033CC;" >' . $row['methadone_total_dosage'] . '&nbsp;mg</p>';

                        // Close the statement
                        $stmt->close();
                    } else {
                        // Output error message if query preparation fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

            <?php
                    // Include the configuration file
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // Get the current month
                    $currentMonth = date("m");
                    $currentYear = date("Y");

                    // Calculate the start and end date of the current month
                    $startDate = date("Y-m-01");
                    $endDate = date("Y-m-t");

                    // SQL query
                    $sql = "SELECT SUM(dosage) AS bupren2_total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Buprenorphine 2mg'";

                    $stmt = $conn->prepare($sql);

                    // Check if the query was prepared successfully
                    if ($stmt) {
                        // Bind the parameters
                        $stmt->bind_param("ss", $startDate, $endDate);

                        // Execute the query
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Fetch the row
                        $row = $result->fetch_assoc();

                        // Output the total dosage dispensed for the month
                        echo '<p>Buprenor 2mg Disp in the Month: <span style="font-weight: bold; color: #0033CC;" >' . $row['bupren2_total_dosage'] . '&nbsp;Tablets</p>';

                        // Close the statement
                        $stmt->close();
                    } else {
                        // Output error message if query preparation fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

            <?php
                    // Include the configuration file
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // Get the current month
                    $currentMonth = date("m");
                    $currentYear = date("Y");

                    // Calculate the start and end date of the current month
                    $startDate = date("Y-m-01");
                    $endDate = date("Y-m-t");

                    // SQL query
                    $sql = "SELECT SUM(dosage) AS bupren8_total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Buprenorphine 8mg'";

                    $stmt = $conn->prepare($sql);

                    // Check if the query was prepared successfully
                    if ($stmt) {
                        // Bind the parameters
                        $stmt->bind_param("ss", $startDate, $endDate);

                        // Execute the query
                        $stmt->execute();

                        // Get the result
                        $result = $stmt->get_result();

                        // Fetch the row
                        $row = $result->fetch_assoc();

                        // Output the total dosage dispensed for the month
                        echo '<p>Buprenor 8mg Disp in the Month: <span style="font-weight: bold; color: #0033CC;" >' . $row['bupren8_total_dosage'] . '&nbsp;Tablets</p>';

                        // Close the statement
                        $stmt->close();
                    } else {
                        // Output error message if query preparation fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

