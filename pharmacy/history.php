<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dispensing History</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3498db;
        }

        .patient-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }

        .patient-details p {
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.6;
        }

        .patient-details strong {
            color: #2c3e50;
            display: inline-block;
            width: 100px;
        }

        h3 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: bold;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .history-list {
            list-style: none;
            padding: 0;
        }

        .history-list li {
            background-color: #fff;
            padding: 15px 20px;
            margin-bottom: 12px;
            border-radius: 6px;
            border-left: 4px solid #27ae60;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            line-height: 1.8;
            transition: all 0.3s ease;
        }

        .history-list li:hover {
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
            transform: translateX(5px);
        }

        .drug-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .date-range {
            color: #7f8c8d;
        }

        .dosage {
            color: #6633CC;
            font-weight: 500;
        }

        .error-message {
            background-color: #fee;
            color: #c0392b;
            padding: 15px 20px;
            border-radius: 6px;
            border-left: 4px solid #c0392b;
            margin: 20px 0;
        }

        .no-records {
            background-color: #fef5e7;
            color: #d68910;
            padding: 15px 20px;
            border-radius: 6px;
            border-left: 4px solid #f39c12;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h2 {
                font-size: 24px;
            }

            h3 {
                font-size: 20px;
            }

            .patient-details strong {
                width: auto;
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        include "../includes/config.php"; // Include your database configuration

        if (isset($_GET['p_id'])) {
            $p_id = $_GET['p_id'];

            // Fetch patient details from the `patients` table
            $query_patient = "SELECT clientName, mat_id, sex FROM patients WHERE p_id = ?";
            $stmt_patient = $conn->prepare($query_patient);
            $stmt_patient->bind_param("s", $p_id);
            $stmt_patient->execute();
            $result_patient = $stmt_patient->get_result();
            $patient = $result_patient->fetch_assoc();

            if ($patient) {
                // Display patient details
                echo "<h2>Patient Details</h2>";
                echo "<div class='patient-details'>";
                echo "<p><strong>Name:</strong> " . htmlspecialchars($patient['clientName']) . "</p>";
                echo "<p><strong>MAT ID:</strong> " . htmlspecialchars($patient['mat_id']) . "</p>";
                echo "<p><strong>Gender:</strong> " . htmlspecialchars($patient['sex']) . "</p>";
                echo "</div>";

                $mat_id = $patient['mat_id']; // Retrieve mat_id for pharmacy query

                // Check if `mat_id` is valid
                if (!empty($mat_id)) {
                    // Fetch grouped visit details from the `pharmacy` table
                    $query_pharmacy = "
                        SELECT
                            drugname,
                            MIN(visitDate) AS startDate,
                            MAX(visitDate) AS endDate,
                            dosage,
                            COUNT(*) AS daysCount
                        FROM pharmacy
                        WHERE mat_id = ?
                        GROUP BY drugname, dosage
                        ORDER BY startDate ASC";

                    $stmt_pharmacy = $conn->prepare($query_pharmacy);
                    $stmt_pharmacy->bind_param("s", $mat_id);
                    $stmt_pharmacy->execute();
                    $result_pharmacy = $stmt_pharmacy->get_result();

                    if ($result_pharmacy->num_rows > 0) {
                        echo "<h3>Pharmacy Dispensing History</h3>";
                        echo "<ul class='history-list'>";

                        // Display grouped records
                        while ($row = $result_pharmacy->fetch_assoc()) {
                            echo "<li>";
                            echo "<span class='drug-name'>" . htmlspecialchars($row['drugname']) . "</span> ";
                            echo "<span class='date-range'>from " . htmlspecialchars($row['startDate']) .
                                " to " . htmlspecialchars($row['endDate']) . "</span>: ";
                            echo "<span class='dosage'>" . htmlspecialchars($row['dosage']) . " mg</span> ";
                            echo "(" . htmlspecialchars($row['daysCount']) . " days total)";
                            echo "</li>";
                        }

                        echo "</ul>";
                    } else {
                        // No pharmacy records found
                        echo "<p class='no-records'>No dispensing records found for this patient.</p>";
                    }
                } else {
                    // mat_id is invalid or not found
                    echo "<p class='error-message'>No valid MAT ID associated with this patient.</p>";
                }
            } else {
                // No patient found with the given p_id
                echo "<p class='error-message'>No patient found with the provided ID.</p>";
            }
        } else {
            echo "<p class='error-message'>No patient ID provided in the URL.</p>";
        }
        ?>
    </div>
</body>
</html>