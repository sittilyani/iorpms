<p>
                <?php
                    include '../includes/config.php';

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS total_qty
                            FROM stock_movements
                            JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                            WHERE drug.drugID = 13
                            AND drug.drugName = 'Naltrexone Implant'
                            ORDER BY stock_movements.trans_date DESC
                            LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Naltrexone Implant Balance: <span style="font-weight: bold; color: #000099;">' . $row['total_qty'] . '&nbsp;Implants</strong></p>';
                        } else {
                            echo '<p>No Naltrexone Implant stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>


            </p>