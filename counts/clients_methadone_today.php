<?php
                // Include the configuration file
                include '../includes/config.php';

                // Get the current date
                $currentDate = date("Y-m-d");

                // SQL query
                $sql = "SELECT COUNT(DISTINCT mat_id) AS unique_mat_ids FROM pharmacy WHERE DATE(visitDate) = ? AND dosage IS NOT NULL";
                $stmt = $conn->prepare($sql);

                // Check if the query was prepared successfully
                if ($stmt) {
                    // Bind the parameter
                    $stmt->bind_param("s", $currentDate);

                    // Execute the query
                    $stmt->execute();

                    // Get the result
                    $result = $stmt->get_result();

                    // Fetch the row
                    $row = $result->fetch_assoc();

                    // Output the count of unique mat_ids
                    echo '<p><span style="font-weight: bold; color: green;" >' . $row['unique_mat_ids'] . '</span></p>';

                    // Close the statement
                    $stmt->close();
                } else {
                    // Output error message if query preparation fails
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }

                // if they are needed in separate blocks.)
                $conn->close();
                ?>