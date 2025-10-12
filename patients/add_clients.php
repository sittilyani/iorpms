<?php
session_start();

// 1. Include the config file ONCE at the top
include('../includes/config.php');

// Initialize $conn and check if it's available before proceeding to use it in other blocks
if (!isset($conn) || $conn->connect_error) {
    // Handle database connection error if necessary
    // die("Connection failed: " . $conn->connect_error);
}

// --- PHP Processing Block for Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Define expected fields (including reg_date)
    $fields = [
        'reg_facility', 'mflcode', 'county', 'scounty', 'mat_id', 'mat_number', 'clientName',
        'sname', 'nickName', 'nat_id', 'dob', 'sex', 'marital_status', 'residence_scounty',
        'p_address', 'client_phone', 'mat_status', 'transfer_id', 'referral_type',
        'referring_facility', 'reffering_fac_client_number', 'accompanment_type',
        'peer_edu_name', 'peer_edu_phone', 'rx_supporter_name', 'dosage', 'reasons',
        'current_status', 'next_appointment', 'hcw_name', 'reg_date' // Correctly included
    ];

    // Validate and sanitize input
    $formData = [];
    foreach ($fields as $field) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    // Calculate age based on date of birth
    $dob_timestamp = strtotime($formData['dob']);
    $current_timestamp = time();
    $formData['age'] = date('Y', $current_timestamp) - date('Y', $dob_timestamp);
    if (date('md', $current_timestamp) < date('md', $dob_timestamp)) {
        $formData['age']--;
    }

    // Set drugname
    $formData['drugname'] = trim($_POST['drugname'] ?? 'Methadone');

    // Prepare and execute SQL query to insert data into the database
    $sql = "INSERT INTO patients (
        reg_facility, mflcode, county, scounty, reg_date, mat_id, mat_number, clientName, sname,
        nickName, nat_id, dob, age, sex, marital_status, residence_scounty, p_address,
        client_phone, mat_status, transfer_id, referral_type, referring_facility,
        reffering_fac_client_number, accompanment_type, peer_edu_name, peer_edu_phone,
        rx_supporter_name, drugname, dosage, reasons, current_status, next_appointment, hcw_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {

        // 33 parameters in total (32 string fields + 1 integer field for 'age')
        $type_string = "ssssssssssssissssssssssssssssssss";

        $stmt->bind_param(
            $type_string,
            $formData['reg_facility'],
            $formData['mflcode'],
            $formData['county'],
            $formData['scounty'],
            $formData['reg_date'], // Correctly captured
            $formData['mat_id'],
            $formData['mat_number'],
            $formData['clientName'],
            $formData['sname'],
            $formData['nickName'],
            $formData['nat_id'],
            $formData['dob'],
            $formData['age'], // Bound as integer 'i'
            $formData['sex'],
            $formData['marital_status'],
            $formData['residence_scounty'],
            $formData['p_address'],
            $formData['client_phone'],
            $formData['mat_status'],
            $formData['transfer_id'],
            $formData['referral_type'],
            $formData['referring_facility'],
            $formData['reffering_fac_client_number'],
            $formData['accompanment_type'],
            $formData['peer_edu_name'],
            $formData['peer_edu_phone'],
            $formData['rx_supporter_name'],
            $formData['drugname'],
            $formData['dosage'],
            $formData['reasons'],
            $formData['current_status'],
            $formData['next_appointment'],
            $formData['hcw_name']
        );

        if ($stmt->execute()) {
            echo "<div id='successmessage' style='background: #b8fcdf; height: 30px; font-style: italic;'>New client added successfully</div>";
            echo "<script>
                setTimeout(function() {
                    var element = document.getElementById('successmessage');
                    if (element) element.parentNode.removeChild(element);
                }, 3000);
            </script>";
        } else {
            echo "Error executing statement: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

// --- PHP Block for Fetching User/Settings Data ---

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId && isset($conn)) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Check if the user is logged in and fetch their user_id
$hcw_name = 'Unknown';
if (isset($_SESSION['user_id']) && isset($conn)) {
    $loggedInUserId = $_SESSION['user_id'];

    // Fetch the logged-in user's name from tblusers
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hcw_name = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// --- START HTML ---
?>

<!DOCTYPE html>
<html>
<head>
    <title>Initial Patient Registration</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>

    <style>
    form {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* Five equal columns for dense form */
        gap: 15px; /* Added gap for better spacing */
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .main-content {
        font-family: Arial, Helvetica, sans-serif;
        padding: 20px;
        max-width: 95%;
        margin: 20px auto; /* Center the main content */
        background-color: var(--card-background, #fff); /* Use CSS variable or default */
        border-radius: 8px;
        box-shadow: 0 4px 10px var(--shadow-light, rgba(0,0,0,0.1));
    }
    /* Style for readonly/disabled inputs */
    .readonly-input {
        background-color: #f0f0f0;
        cursor: not-allowed;
    }
    </style>

</head>
<body>
    <div class="main-content">
        <div class="form-group"><h2>Initial client registration form</h2></div>

    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">

        <div class="form-group">
            <label for="reg_facility">Facility <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="reg_facility" name="reg_facility" required>
                <option value="">Select Facility</option>
                <?php
                // Fetch facilities and required details for data attributes
                // Rely on the single connection opened at the top
                if (isset($conn)) {
                    $sql = "SELECT facilityname, mflcode, countyname, subcountyname FROM facilities ORDER BY facilityname ASC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Store MFL, County, and SubCounty data in the option's data attributes
                            echo "<option
                                    value='" . htmlspecialchars($row['facilityname']) . "'
                                    data-mfl='" . htmlspecialchars($row['mflcode']) . "'
                                    data-county='" . htmlspecialchars($row['countyname']) . "'
                                    data-scounty='" . htmlspecialchars($row['subcountyname']) . "'
                                >" . htmlspecialchars($row['facilityname']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="mflcode">MFL code <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="mflcode" name="mflcode">
        </div>

        <div class="form-group">
            <label for="county">County <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="county" name="county">
        </div>

        <div class="form-group">
            <label for="scounty">Sub County <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="scounty" name="scounty">
        </div>

        <div class="form-group">
            <label for="reg_date">Registration Date</label>
            <input type="date" id="reg_date" name="reg_date" required>
        </div>

        <div class="form-group">
            <label for="mat_id">Unique (MAT) ID <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="mat_id" name="mat_id" required>
        </div>
        <div class="form-group">
            <label for="mat_number">MAT Number</label>
            <input type="text" id="mat_number" name="mat_number">
        </div>
        <div class="form-group">
            <label for="clientName">Client Name <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="clientName" name="clientName" required>
        </div>
        <div class="form-group">
            <label for="sname">SurName</label>
            <input type="text" id="sname" name="sname">
        </div>
        <div class="form-group">
            <label for="nickName">Nick Name</label>
            <input type="text" id="nickName" name="nickName" >
        </div>
        <div class="form-group">
            <label for="nat_id">National ID/PPNo.</label>
            <input type="text" id="nat_id" name="nat_id" >
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="date" id="dob" name="dob" required>
        </div>

        <div class="form-group">
            <label for="sex">Sex <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="sex" name="sex" required>
                <option value="">Select Sex</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT gender_name FROM tblgender";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['gender_name']) . "'>" . htmlspecialchars($row['gender_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="marital_status">Marital Status</label>
            <select id="marital_status" name="marital_status">
                <option value="">Select marital status</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT marital_status_name FROM marital_status";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['marital_status_name']) . "'>" . htmlspecialchars($row['marital_status_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="residence_scounty">Residence Sub County</label>
            <input type="text" id="residence_scounty" name="residence_scounty" >
        </div>
        <div class="form-group">
            <label for="p_address">Physical Address (residence)</label>
            <input type="text" id="p_address" name="p_address" >
        </div>
        <div class="form-group">
            <label for="client_phone">Phone Number</label>
            <input type="number" id="client_phone" name="client_phone" pattern="0\d{9}" title="Phone number must start with 0 and have 10 digits.">
        </div>

        <div class="form-group">
            <label for="mat_status">Have you ever received MAT services elsewhere? <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="mat_status" name="mat_status" required>
                <option value="">Select enrolment status</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT enrolment_status_name FROM enrolment_status";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['enrolment_status_name']) . "'>" . htmlspecialchars($row['enrolment_status_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="transfer_id">Transfer in MAT ID</label>
            <input type="text" id="transfer_id" name="transfer_id">
        </div>
        <div class="form-group">
            <label for="referral_type">Referral Type</label>
            <select id="referral_type" name="referral_type">
                <option value="">Select Referral Type</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT referralType FROM tblreferral";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['referralType']) . "'>" . htmlspecialchars($row['referralType']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="cso">CSO</label>
            <select id="cso" name="cso">
                <option value="">Select CSO</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT cso_name FROM tblcso";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['cso_name']) . "'>" . htmlspecialchars($row['cso_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="referring_facility">Referring Facility</label>
            <select id="referring_facility" name="referring_facility">
                <option value="">Select Referring Facility</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT facilityname FROM facilities";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['facilityname']) . "'>" . htmlspecialchars($row['facilityname']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="reffering_fac_client_number">Referring Facility Clients Registration Number</label>
            <input type="text" id="reffering_fac_client_number" name="reffering_fac_client_number">
        </div>

        <div class="form-group">
            <label for="accompanment_type">Accompanied By</label>
            <select id="accompanment_type" name="accompanment_type">
                <option value="">Select accompanment</option>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT accompanmentType FROM tblaccompanment";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['accompanmentType']) . "'>" . htmlspecialchars($row['accompanmentType']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="peer_edu_name">Peer Educator's/ORW Name</label>
            <input type="text" id="peer_edu_name" name="peer_edu_name">
        </div>
        <div class="form-group">
            <label for="peer_edu_phone">Peer Educator's/ORW phone number</label>
            <input type="number" id="peer_edu_phone" name="peer_edu_phone" pattern="0\d{9}" title="Phone number must start with 0 and have 10 digits.">
        </div>
        <div class="form-group">
            <label for="rx_supporter_name">Treatment supporter's name</label>
            <input type="text" id="rx_supporter_name" name="rx_supporter_name" >
        </div>

        <div class="form-group">
            <label for="drugname">Drug <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <select id="drugname" name="drugname" required>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT drugName FROM drug";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8') . "</option>";
                        }
                    } else {
                        echo "<option value=''>No drugs available</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="dosage">Dosage <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" id="dosage" name="dosage" required>
        </div>
        <div class="form-group">
            <label for="reasons">Reasons</label>
            <input type="text" id="reasons" name="reasons">
        </div>
        <div class="form-group">
            <label for="current_status">Current Status</label>
            <select id="current_status" name="current_status" required>
                <?php
                if (isset($conn)) {
                    $sql = "SELECT status_name FROM status";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['status_name']) . "'>" . htmlspecialchars($row['status_name']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="next_appointment">Next appointment <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="date" name="next_appointment" required>
        </div>
        <div class="form-group">
            <label for="hcw_name">Name of service provider <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" name="hcw_name" value="<?php echo htmlspecialchars($hcw_name); ?>" class="readonly-input" readonly>
        </div>
        <div class="form-group">
            <input type="submit" class="custom-submit-btn" value="Submit">
        </div>
    </form>
</div>

<?php
// Only close the connection once, at the very end of the file.
if (isset($conn)) {
    $conn->close();
}
?>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the facility select element
        const facilitySelect = document.getElementById('reg_facility');
        const mflcodeInput = document.getElementById('mflcode');
        const countyInput = document.getElementById('county');
        const scountyInput = document.getElementById('scounty');

        // Listener for facility change
        facilitySelect.addEventListener('change', function() {
            // Get the selected option element
            const selectedOption = this.options[this.selectedIndex];

            if (selectedOption.value) {
                // Retrieve data attributes from the selected option
                const mfl = selectedOption.getAttribute('data-mfl');
                const county = selectedOption.getAttribute('data-county');
                const scounty = selectedOption.getAttribute('data-scounty');

                // Populate the readonly fields
                mflcodeInput.value = mfl || '';
                countyInput.value = county || '';
                scountyInput.value = scounty || '';
            } else {
                // Clear the fields if "Select Facility" is chosen
                mflcodeInput.value = '';
                countyInput.value = '';
                scountyInput.value = '';
            }
        });

        // This is the correct, simple method for auto-populating fields
        // using data already loaded in the HTML.
    });
</script>
</body>
</html>