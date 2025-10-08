<?php
// Include the database connection file
include '../includes/config.php';


// Initialize variables at the top of your script
$success_message = "";
$opening_bal = 0;
$received_by = ""; // Initialize to avoid undefined variable warning

// Logic to fetch logged-in user's name (if applicable)
// This might need session_start() if not already in config.php or header.php
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    if ($stmt) {
        $stmt->bind_param('i', $loggedInUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $received_by = $user['first_name'] . ' ' . $user['last_name'];
        }
        $stmt->close();
    } else {
        // Handle prepare error
        error_log("Error preparing user query: " . $conn->error);
    }
}


// Fetch drug names and IDs from the "drug" table
$sql_drugs = "SELECT drugID, drugName FROM drug";
$result_drugs = $conn->query($sql_drugs);
if (!$result_drugs) {
    die("Error fetching drug data: " . $conn->error);
}

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $drugname = $_POST['drugname'] ?? '';
    $qty_in = isset($_POST['qty_in']) ? (int)$_POST['qty_in'] : 0;
    $received_from = isset($_POST['received_from']) ? $_POST['received_from'] : '';
    $batch_number = isset($_POST['batch_number']) ? $_POST['batch_number'] : '';
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $received_by = $_POST['received_by'] ?? '';

    // Get the opening balance for the selected drug
    $sql_latest_total_qty = "SELECT total_qty FROM stock_movements WHERE drugName = ? ORDER BY trans_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql_latest_total_qty);
    if ($stmt) {
        $stmt->bind_param('s', $drugname);
        $stmt->execute();
        $result_latest_total_qty = $stmt->get_result();
        if ($result_latest_total_qty->num_rows > 0) {
            $opening_bal = $result_latest_total_qty->fetch_assoc()['total_qty'];
        } else {
            $opening_bal = 0; // Default if no record is found
        }
        $stmt->close();
    } else {
        error_log("Error preparing latest total qty query: " . $conn->error);
    }


    // Calculate the total quantity
    $total_qty = $opening_bal + $qty_in;

    // Get the drugID for the selected drugname
    $drugID = null;
    $sql_get_drug_id = "SELECT drugID FROM drug WHERE drugName = ?";
    $stmt = $conn->prepare($sql_get_drug_id);
    if ($stmt) {
        $stmt->bind_param('s', $drugname);
        $stmt->execute();
        $result_drug_id = $stmt->get_result();
        $row_drug_id = $result_drug_id->fetch_assoc();
        $drugID = $row_drug_id['drugID'] ?? null;
        $stmt->close();
    } else {
        error_log("Error preparing drug ID query: " . $conn->error);
    }

    // Insert stock movement record
    if ($drugID !== null) {
        $sql = "INSERT INTO stock_movements (drugID, drugName, opening_bal, qty_in, received_from, batch_number, expiry_date, received_by, total_qty, trans_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssisssssi', $drugID, $drugname, $opening_bal, $qty_in, $received_from, $batch_number, $expiry_date, $received_by, $total_qty);
            if ($stmt->execute()) {
                $success_message = "Stock data inserted successfully.";
            } else {
                echo "Error inserting stock data: " . $stmt->error;
            }
            $stmt->close();
        } else {
            error_log("Error preparing insert query: " . $conn->error);
        }
    } else {
        echo "Error: Drug not found for selected name.";
    }
}

// Close the database connection (moved to after all processing)
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Stock Movement</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <style>
        /* CSS Variables for easy theme changes */
        :root {
            --primary-color: #0056b3; /* Darker blue for primary actions */
            --secondary-color: #6c757d; /* Grey for secondary elements */
            --background-light: #f8f9fa; /* Light background for overall page */
            --card-background: #ffffff; /* White for form background */
            --border-color: #dee2e6;
            --success-color: #28a745;
            --success-bg-color: #d4edda;
            --text-color: #343a40;
            --input-border: #ced4da;
            --input-focus-border: #80bdff;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --font-family: 'Arial', sans-serif; /* Changed from Times New Roman for a modern look */
        }



        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        #success-message {
            background-color: var(--success-bg-color);
            color: var(--success-color);
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px solid var(--success-color);
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        #success-message .fas {
            font-size: 1.2em;
        }


        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three equal columns */
            gap: 25px; /* Spacing between columns and rows */
            padding: 20px;
            background-color: #FFFFE0; /* Original light yellow background */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in width */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .readonly-input {
            background-color: #e9ecef; /* Light gray for readonly fields */
            cursor: not-allowed;
        }

        .custom-submit-btn {
            grid-column: 1 / -1; /* Make the button span all three columns */
            padding: 15px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-submit-btn:hover {
            background-color: #004085; /* Darker shade on hover */
            transform: translateY(-2px); /* Slight lift effect */
        }

        .custom-submit-btn:active {
            transform: translateY(0);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            form {
                grid-template-columns: repeat(2, 1fr); /* Two columns on medium screens */
            }
            .custom-submit-btn {
                grid-column: 1 / -1; /* Still span full width */
            }
        }

        @media (max-width: 768px) {
            form {
                grid-template-columns: 1fr; /* Single column on small screens */
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div id="success-message" style="display: <?php echo $success_message ? 'flex' : 'none'; ?>;">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <h2>Add Drug Stocks</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="drugname">Drug Name</label>
                <select name="drugname" class='select' id="drugname" onchange="getOpeningBal()" required>
                    <option value="">Select Drug</option>
                    <?php
                    // Ensure result_drugs is valid before fetching
                    if ($result_drugs && $result_drugs->num_rows > 0) {
                        // Reset pointer to beginning if fetched before
                        $result_drugs->data_seek(0);
                        while ($row = $result_drugs->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['drugName']) . '">' . htmlspecialchars($row['drugName']) . '</option>';
                        }
                    } else {
                        echo '<option value="">No drugs found</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="opening_bal">Opening Balance</label>
                <input type="number" name="opening_bal" id="opening_bal" value="<?php echo $opening_bal; ?>" readonly class="readonly-input">
            </div>

            <div class="form-group">
                <label for="qty_in">Quantity Received</label>
                <input type="number" name="qty_in" required>
            </div>

            <div class="form-group">
                <label for="received_from">Received From</label>
                <input type="text" name="received_from" required>
            </div>

            <div class="form-group">
                <label for="batch_number">Batch Number</label>
                <input type="text" name="batch_number" required>
            </div>

            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="date" name="expiry_date" id="expiry-date" required>
            </div>

            <div class="form-group">
                <label for="received_by">Received By</label>
                <input type="text" name="received_by" class="readonly-input" readonly value="<?php echo htmlspecialchars($received_by); ?>">
            </div>

            <input type="submit" class='custom-submit-btn' name="submit" value="Add Drug Stocks">
        </form>
    </div>

    <script>
        function getOpeningBal() {
            var drugname = document.getElementById("drugname").value;
            if (drugname === "") {
                document.getElementById("opening_bal").value = 0; // Reset if no drug selected
                return;
            }

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState === 4) {
                    if (this.status === 200) {
                        try {
                            var data = JSON.parse(this.responseText);
                            document.getElementById("opening_bal").value = data.latest_total_qty || 0;
                        } catch (e) {
                            console.error("Error parsing JSON response for opening balance:", e);
                            document.getElementById("opening_bal").value = 0; // Default to 0 on error
                        }
                    } else {
                        console.error("Error fetching opening balance: " + this.status);
                        document.getElementById("opening_bal").value = 0; // Default to 0 on HTTP error
                    }
                }
            };
            xhttp.open("GET", "get_opening_bal.php?drugname=" + encodeURIComponent(drugname), true);
            xhttp.send();
        }

        // Optional: Hide success message after a few seconds
        window.onload = function() {
            var successMessage = document.getElementById('success-message');
            if (successMessage.style.display === 'flex') {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 5000); // Hide after 5 seconds
            }
        };
    </script>
</body>
</html>
