<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

include('../includes/config.php');

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_facility_id = $_POST['facility_id'];
    $facilityincharge = $_POST['facilityincharge'];
    $facilityphone = $_POST['facilityphone'];
    $email = $_POST['email'];

    $conn->begin_transaction();

    try {
        // Step 1: Get ALL necessary facility details from the 'facilities' table
        $get_facility_details_sql = "SELECT facilityname, countyname, subcountyname, mflcode, owner, sdp, agency, emr, emrstatus, infrastructuretype, latitude, longitude
                                     FROM facilities WHERE id = ?";
        $stmt_get_details = $conn->prepare($get_facility_details_sql);
        if (!$stmt_get_details) {
            throw new Exception("Error preparing statement for facility details: " . $conn->error);
        }
        $stmt_get_details->bind_param("i", $selected_facility_id);
        $stmt_get_details->execute();
        $result_get_details = $stmt_get_details->get_result();
        $facility_data = $result_get_details->fetch_assoc();
        $stmt_get_details->close();

        if (!$facility_data) {
            throw new Exception("Selected facility not found in the database.");
        }

        // Extract the values needed for insertion into facility_settings
        $facilityname_to_insert = $facility_data['facilityname'] ?? 'N/A';
        $mflcode_to_insert = !empty($facility_data['mflcode']) ? $facility_data['mflcode'] : 'N/A';
        $countyname_to_insert = $facility_data['countyname'] ?? 'N/A';
        $subcountyname_to_insert = $facility_data['subcountyname'] ?? 'N/A';

        // Step 2: Delete all existing records in facility_settings (table can only hold one row)
        $delete_sql = "DELETE FROM facility_settings";
        if (!$conn->query($delete_sql)) {
            throw new Exception("Error deleting existing facility settings: " . $conn->error);
        }

        // Step 3: Insert the new facility settings
        $insert_sql = "INSERT INTO facility_settings (facility_id, facilityname, mflcode, countyname, subcountyname, facilityincharge, facilityphone, email)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        if (!$stmt_insert) {
            throw new Exception("Error preparing insert statement: " . $conn->error);
        }

        $stmt_insert->bind_param("isssssss",
            $selected_facility_id,
            $facilityname_to_insert,
            $mflcode_to_insert,
            $countyname_to_insert,
            $subcountyname_to_insert,
            $facilityincharge,
            $facilityphone,
            $email
        );

        if (!$stmt_insert->execute()) {
            throw new Exception("Error inserting new facility settings: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        // Commit the transaction
        $conn->commit();
        $success_message = "The Facility Details added successfully. Previous settings were replaced.";

        // Step 4: Update all relevant session variables with new data
        $_SESSION['current_facility_id'] = $selected_facility_id;
        $_SESSION['current_facility_name'] = $facility_data['facilityname'];
        $_SESSION['current_mflcode'] = $facility_data['mflcode'];
        $_SESSION['current_county'] = $facility_data['countyname'];
        $_SESSION['current_subcounty'] = $facility_data['subcountyname'];
        $_SESSION['current_owner'] = $facility_data['owner'];
        $_SESSION['current_sdp'] = $facility_data['sdp'];
        $_SESSION['current_agency'] = $facility_data['agency'];
        $_SESSION['current_emr'] = $facility_data['emr'];
        $_SESSION['current_emrstatus'] = $facility_data['emrstatus'];
        $_SESSION['current_infrastructuretype'] = $facility_data['infrastructuretype'];
        $_SESSION['current_latitude'] = $facility_data['latitude'];
        $_SESSION['current_longitude'] = $facility_data['longitude'];
        $_SESSION['current_facility_incharge'] = $facilityincharge;
        $_SESSION['current_facility_phone'] = $facilityphone;
        $_SESSION['current_facility_email'] = $email;

        echo "<div class='message success'>";
        echo $success_message;
        echo "</div>";

        echo "<script>
                  setTimeout(function() {
                      window.location.href = '../dashboard/dashboard.php';
                  }, 4000);
              </script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Setup failed. Please try again. Error: " . $e->getMessage();
        echo "<div class='message error'>";
        echo $error_message;
        echo "</div>";
    }
}

// For initial load - Load existing facility settings if available
$current_setup_facility_id = null;
$current_setup_facility_name_display = 'Not Set';
$current_setup_incharge = '';
$current_setup_phone = '';
$current_setup_email = '';
$current_mflcode = '';
$current_county = '';
$current_subcounty = '';
$current_owner = '';
$current_sdp = '';
$current_agency = '';
$current_emr = '';
$current_emr_status = '';
$current_infrastructure_type = '';
$current_latitude = '';
$current_longitude = '';

if (isset($conn) && $conn instanceof mysqli) {
    $sql_get_current_settings = "SELECT fs.*, f.owner, f.sdp, f.agency, f.emr, f.emrstatus, f.infrastructuretype, f.latitude, f.longitude
                                 FROM facility_settings fs
                                 LEFT JOIN facilities f ON fs.facility_id = f.id
                                 LIMIT 1";
    $result_current_settings = $conn->query($sql_get_current_settings);

    if ($result_current_settings && $result_current_settings->num_rows > 0) {
        $current_settings = $result_current_settings->fetch_assoc();

        // Populate all variables from database
        $current_setup_facility_id = $current_settings['facility_id'];
        $current_setup_facility_name_display = $current_settings['facilityname'] ?? 'Not Set';
        $current_county = $current_settings['countyname'] ?? '';
        $current_subcounty = $current_settings['subcountyname'] ?? '';
        $current_setup_incharge = $current_settings['facilityincharge'];
        $current_setup_phone = $current_settings['facilityphone'];
        $current_setup_email = $current_settings['email'];
        $current_mflcode = $current_settings['mflcode'];
        $current_owner = $current_settings['owner'];
        $current_sdp = $current_settings['sdp'];
        $current_agency = $current_settings['agency'];
        $current_emr = $current_settings['emr'];
        $current_emr_status = $current_settings['emrstatus'];
        $current_infrastructure_type = $current_settings['infrastructuretype'];
        $current_latitude = $current_settings['latitude'];
        $current_longitude = $current_settings['longitude'];

        // Update session variables with current data
        $_SESSION['current_facility_id'] = $current_setup_facility_id;
        $_SESSION['current_facility_name'] = $current_setup_facility_name_display;
        $_SESSION['current_mflcode'] = $current_mflcode;
        $_SESSION['current_county'] = $current_county;
        $_SESSION['current_subcounty'] = $current_subcounty;
        $_SESSION['current_owner'] = $current_owner;
        $_SESSION['current_sdp'] = $current_sdp;
        $_SESSION['current_agency'] = $current_agency;
        $_SESSION['current_emr'] = $current_emr;
        $_SESSION['current_emrstatus'] = $current_emr_status;
        $_SESSION['current_infrastructuretype'] = $current_infrastructure_type;
        $_SESSION['current_latitude'] = $current_latitude;
        $_SESSION['current_longitude'] = $current_longitude;
        $_SESSION['current_facility_incharge'] = $current_setup_incharge;
        $_SESSION['current_facility_phone'] = $current_setup_phone;
        $_SESSION['current_facility_email'] = $current_setup_email;
    } else {
        // No facility set up - Set default/empty values in session
        $_SESSION['current_facility_id'] = null;
        $_SESSION['current_facility_name'] = 'Not Set';
        $_SESSION['current_mflcode'] = '';
        $_SESSION['current_county'] = '';
        $_SESSION['current_subcounty'] = '';
        $_SESSION['current_owner'] = '';
        $_SESSION['current_sdp'] = '';
        $_SESSION['current_agency'] = '';
        $_SESSION['current_emr'] = '';
        $_SESSION['current_emrstatus'] = '';
        $_SESSION['current_infrastructuretype'] = '';
        $_SESSION['current_latitude'] = '';
        $_SESSION['current_longitude'] = '';
        $_SESSION['current_facility_incharge'] = '';
        $_SESSION['current_facility_phone'] = '';
        $_SESSION['current_facility_email'] = '';
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Setup</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .form-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 960px; /* Increased max-width for three columns */
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three equal columns */
            gap: 20px 30px; /* Row and column gap */
        }

        .form-group {
            margin-bottom: 0; /* No margin-bottom here, gap handles spacing */
        }

        label {
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box; /* Include padding in element's total width */
            font-size: 1rem;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        button[type="submit"] {
            grid-column: 1 / -1; /* Make the button span across all columns */
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            height: 45px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .message {
            width: 100%;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 20px; /* Space below messages */
            box-sizing: border-box;
        }

        .success_messages {
            background-color: #d4edda;
            color: #FFFFFF;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        .error_message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        .note-message {
            grid-column: 1 / -1; /* Span across all columns */
            text-align: center;
            margin-top: 15px;
            font-style: italic;
            color: #666;
            font-size: 0.9em;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) { /* Adjust breakpoint for 2 columns */
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) { /* Adjust breakpoint for 1 column */
            .form-grid {
                grid-template-columns: 1fr; /* Stack columns on smaller screens */
            }
            button[type="submit"], .note-message {
                grid-column: auto; /* Reset grid-column for smaller screens */
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="post" action="initialsettings.php">
            <div class="form-grid">
                <div class="form-group">
                    <label for="facility_id">Select Facility Name:</label>
                    <select name="facility_id" id="facility_id" required>
                        <option value="">-- Select Facility --</option>
                        <?php
                        if (isset($conn) && $conn instanceof mysqli) {
                            $result = $conn->query("SELECT id, facilityname FROM facilities ORDER BY facilityname ASC");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($current_setup_facility_id == $row['id']) ? 'selected' : '';
                                    echo "<option value='{$row['id']}' {$selected}>{$row['facilityname']}</option>";
                                }
                            } else {
                                echo "<option value=''>Error loading facilities</option>";
                            }
                        } else {
                            echo "<option value=''>Database connection error</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mflcode">MFL Code:</label>
                    <input type="int" name="mflcode" id="mflcode" readonly value="<?php echo htmlspecialchars($current_mflcode); ?>">
                </div>

                <div class="form-group">
                    <label for="countyname">County Name:</label>
                    <input type="text" name="countyname" id="countyname" readonly value="<?php echo htmlspecialchars($current_county); ?>">
                </div>

                <div class="form-group">
                    <label for="subcountyname">Sub-County Name:</label>
                    <input type="text" name="subcountyname" id="subcountyname" readonly value="<?php echo htmlspecialchars($current_subcounty); ?>">
                </div>

                <div class="form-group">
                    <label for="owner">Owner:</label>
                    <input type="text" name="owner" id="owner" readonly value="<?php echo htmlspecialchars($current_owner); ?>">
                </div>

                <div class="form-group">
                    <label for="sdp">SDP:</label>
                    <input type="text" name="sdp" id="sdp" readonly value="<?php echo htmlspecialchars($current_sdp); ?>">
                </div>

                <div class="form-group">
                    <label for="agency">Agency:</label>
                    <input type="text" name="agency" id="agency" readonly value="<?php echo htmlspecialchars($current_agency); ?>">
                </div>

                <div class="form-group">
                    <label for="emr">EMR:</label>
                    <input type="text" name="emr" id="emr" readonly value="<?php echo htmlspecialchars($current_emr); ?>">
                </div>

                <div class="form-group">
                    <label for="emrstatus">EMR Status:</label>
                    <input type="text" name="emrstatus" id="emrstatus" readonly value="<?php echo htmlspecialchars($current_emr_status); ?>">
                </div>

                <div class="form-group">
                    <label for="infrastructuretype">Infrastructure Type:</label>
                    <input type="text" name="infrastructuretype" id="infrastructuretype" readonly value="<?php echo htmlspecialchars($current_infrastructure_type); ?>">
                </div>

                <div class="form-group">
                    <label for="latitude">Latitude:</label>
                    <input type="text" name="latitude" id="latitude" readonly value="<?php echo htmlspecialchars($current_latitude); ?>">
                </div>

                <div class="form-group">
                    <label for="longitude">Longitude:</label>
                    <input type="text" name="longitude" id="longitude" readonly value="<?php echo htmlspecialchars($current_longitude); ?>">
                </div>

                <div class="form-group">
                    <label for="facilityincharge">Name of Facility Incharge:</label>
                    <input type="text" name="facilityincharge" id="facilityincharge" required value="<?php echo htmlspecialchars($current_setup_incharge); ?>">
                </div>

                <div class="form-group">
                    <label for="facilityphone">Facility Phone:</label>
                    <input type="tel" name="facilityphone" id="facilityphone" required value="<?php echo htmlspecialchars($current_setup_phone); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($current_setup_email); ?>">
                </div>

                <div class="form-group note-message">
                    <p>Please be sure because this will replace any existing facility setup and cannot be easily changed after submission.</p>
                </div>

                <button type="submit">Save and Submit</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const facilitySelect = document.getElementById('facility_id');
            const fieldsToPopulate = [
                'mflcode', 'countyname', 'subcountyname', 'owner', 'sdp',
                'agency', 'emr', 'emrstatus', 'infrastructuretype', 'latitude', 'longitude'
            ];

            function populateFields(data) {
                fieldsToPopulate.forEach(field => {
                    const inputElement = document.getElementById(field);
                    if (inputElement) {
                        inputElement.value = data[field] || '';
                    }
                });
            }

            function clearFields() {
                fieldsToPopulate.forEach(field => {
                    const inputElement = document.getElementById(field);
                    if (inputElement) {
                        inputElement.value = '';
                    }
                });
            }

            facilitySelect.addEventListener('change', function() {
                var facilityId = this.value;
                if (facilityId) {
                    fetch('fetch_facility_details.php?id=' + facilityId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                clearFields();
                            } else {
                                populateFields(data);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching facility details:', error);
                            alert('An error occurred while fetching facility details.');
                            clearFields();
                        });
                } else {
                    clearFields();
                }
            });
            // Removed the duplicate JavaScript block.
        });
    </script>

    <script>
        document.getElementById('facility_id').addEventListener('change', function() {
            var facilityId = this.value;
            if (facilityId) {
                // Make an AJAX request to fetch facility details
                fetch('fetch_facility_details.php?id=' + facilityId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            // Clear all fields if error
                            document.getElementById('mflcode').value = '';
                            document.getElementById('countyname').value = '';
                            document.getElementById('subcountyname').value = '';
                            document.getElementById('owner').value = '';
                            document.getElementById('sdp').value = '';
                            document.getElementById('agency').value = '';
                            document.getElementById('emr').value = '';
                            document.getElementById('emrstatus').value = '';
                            document.getElementById('infrastructuretype').value = '';
                            document.getElementById('latitude').value = '';
                            document.getElementById('longitude').value = '';
                        } else {
                            // Populate the readonly fields
                            document.getElementById('mflcode').value = data.mflcode || '';
                            document.getElementById('countyname').value = data.countyname || '';
                            document.getElementById('subcountyname').value = data.subcountyname || '';
                            document.getElementById('owner').value = data.owner || '';
                            document.getElementById('sdp').value = data.sdp || '';
                            document.getElementById('agency').value = data.agency || '';
                            document.getElementById('emr').value = data.emr || '';
                            document.getElementById('emrstatus').value = data.emrstatus || '';
                            document.getElementById('infrastructuretype').value = data.infrastructuretype || '';
                            document.getElementById('latitude').value = data.latitude || '';
                            document.getElementById('longitude').value = data.longitude || '';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching facility details:', error);
                        alert('An error occurred while fetching facility details.');
                    });
            } else {
                // Clear all fields if no facility is selected
                document.getElementById('mflcode').value = '';
                document.getElementById('countyname').value = '';
                document.getElementById('subcountyname').value = '';
                document.getElementById('owner').value = '';
                document.getElementById('sdp').value = '';
                document.getElementById('agency').value = '';
                document.getElementById('emr').value = '';
                document.getElementById('emrstatus').value = '';
                document.getElementById('infrastructuretype').value = '';
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
            }

        });
    </script>
</body>
</html>