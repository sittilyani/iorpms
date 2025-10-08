<?php
session_start();

include('../includes/config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Define expected fields
    $fields = [
        'reg_facility', 'mflcode', 'county', 'scounty', 'mat_id', 'mat_number', 'clientName',
        'sname', 'nickName', 'nat_id', 'dob', 'sex', 'marital_status', 'residence_scounty',
        'p_address', 'client_phone', 'mat_status', 'transfer_id', 'referral_type',
        'referring_facility', 'reffering_fac_client_number', 'accompanment_type',
        'peer_edu_name', 'peer_edu_phone', 'rx_supporter_name', 'dosage', 'reasons',
        'current_status', 'next_appointment', 'hcw_name'
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

    // Prepare and execute SQL query to insert data into the database
    $sql = "INSERT INTO patients (
        reg_facility, mflcode, county, scounty, mat_id, mat_number, clientName, sname,
        nickName, nat_id, dob, age, sex, marital_status, residence_scounty, p_address,
        client_phone, mat_status, transfer_id, referral_type, referring_facility,
        reffering_fac_client_number, accompanment_type, peer_edu_name, peer_edu_phone,
        rx_supporter_name, drugname, dosage, reasons, current_status, next_appointment, hcw_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {

    $formData['drugname'] = trim($_POST['drugname'] ?? 'Methadone');

    $stmt->bind_param(
            "ssssssssssssssssssssssssssssssss",
            $formData['reg_facility'],
            $formData['mflcode'],
            $formData['county'],
            $formData['scounty'],
            $formData['mat_id'],
            $formData['mat_number'],
            $formData['clientName'],
            $formData['sname'],
            $formData['nickName'],
            $formData['nat_id'],
            $formData['dob'],
            $formData['age'],
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
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
}
?>

<?php
include "../includes/config.php";

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

?>

<?php
// Existing includes and configurations
include "../includes/config.php";

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$hcw_name = 'Unknown';
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Initial Patient Registration</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.3.min.js"></script>
    <style>
    form {
            display: grid;
            grid-template-columns: repeat(5, 1fr); /* Three equal columns */}

        .main-content {
            font-family: Arial, Helvetica, sans-serif;
            padding: 20px;
            max-width: 95%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
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
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM facilities";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['facilityname'] . "'>" . $row['facilityname'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
      </div>
      <div class="form-group">
        <label for="mflcode">MFL code <span style='color: red; font-weight: bold;'>&#10033;</span></label>
        <input type="text" id="mflcode" name="mflcode" pattern="[1-9]\d{0,4}" title="MFL code must not start with 0 and have a maximum of 5 digits." required>
      </div>
      <div class="form-group">
        <label for="county">County <span style='color: red; font-weight: bold;'>&#10033;</span></label>
        <select id="county" name="county" required>
            <option value="">Select County</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM counties";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['county_name'] . "'>" . $row['county_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
        <label for="scounty">Sub County <span style='color: red; font-weight: bold;'>&#10033;</span></label>
        <select id="scounty" name="scounty" required>
            <option value="">Select Sub County</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM sub_counties";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['sub_county_name'] . "'>" . $row['sub_county_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
        <!--<label for="reg_date">Registration Date</label>
            <input type="date" id="reg_date" name="reg_date" required>
        <label for="reg_time">Time</label>
            <input type="time" id="reg_time" name="reg_time" required>-->
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
        <input type="date" id="dob" name="dob" required onchange="calculateAge()">
        </div>
        <div class="form-group">
        <label for="sex">Sex <span style='color: red; font-weight: bold;'>&#10033;</span></label>
        <select id="sex" name="sex" required>
            <option value="">Select Sex</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM tblgender";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['gender_name'] . "'>" . $row['gender_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
        <label for="marital_status">Marital Status</label>
        <select id="marital_status" name="marital_status" required>
            <option value="">Select marital status</option>
            <?php
            include('../includes/config.php');

            // Fetch marital status from the database
            $sql = "SELECT * FROM marital_status";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['marital_status_name'] . "'>" . $row['marital_status_name'] . "</option>";
                }
            }
            $conn->close();
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
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM enrolment_status";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['enrolment_status_name'] . "'>" . $row['enrolment_status_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
        <label for="transfer_id">Transfer in MAT ID</label>
            <input type="text" id="transfer_id" name="transfer_id">
        </div>
        <div class="form-group">
        <label for="referral_type">Referral Type</label>
        <select id="referral_type" name="referral_type" required>
            <option value="">Select Referral Type</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM tblreferral";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['referralType'] . "'>" . $row['referralType'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
        <label for="cso">CSO</label>
        <select id="cso" name="cso">
            <option value="">Select CSO</option>
            <?php
            include('../includes/config.php');

            // Fetch CSO from the database
            $sql = "SELECT * FROM tblcso";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['cso_name'] . "'>" . $row['cso_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
        <label for="referring_facility">Referring Facility</label>
        <select id="referring_facility" name="referring_facility">
            <option value="">Select Referring Facility</option>
            <?php
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM facilities";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['facilityname'] . "'>" . $row['facilityname'] . "</option>";
                }
            }
            $conn->close();
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
            include('../includes/config.php');

            // Fetch counties from the database
            $sql = "SELECT * FROM tblaccompanment";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['accompanmentType'] . "'>" . $row['accompanmentType'] . "</option>";
                }
            }
            $conn->close();
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
                <!--<option value="">Select current status</option> -->
                <?php
                include('../includes/config.php');

                // Fetch drug names from the database
                $sql = "SELECT * FROM drug";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Use htmlspecialchars to prevent potential XSS attacks
                        echo "<option value='" . htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['drugName'], ENT_QUOTES, 'UTF-8') . "</option>";
                    }
                } else {
                    echo "<option value=''>No drugs available</option>";
                }

                $conn->close();
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
            <!--<option value="">Select current status</option> -->
            <?php
            include('../includes/config.php');

            // Fetch status from the database
            $sql = "SELECT * FROM status";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['status_name'] . "'>" . $row['status_name'] . "</option>";
                }
            }
            $conn->close();
            ?>
        </select>
        </div>
        <div class="form-group">
            <label for="next_appointment">Next appointment <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="date" name="next_appointment">
        </div>
        <div class="form-group">
            <label for="hcw_name">Name of service provider <span style='color: red; font-weight: bold;'>&#10033;</span></label>
            <input type="text" name="hcw_name" value="<?php echo htmlspecialchars($hcw_name); ?>" class="readonly-input readonly">
        </div>
        <div class="form-group">
            <input type="submit" class="custom-submit-btn" value="Submit">
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

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
