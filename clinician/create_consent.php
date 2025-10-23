<?php
session_start();
include "../includes/config.php";

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch patient details if p_id is provided
$client = null;
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];
    $patient_query = "SELECT clientName, nickName, sex, cso, mat_id, reg_date, client_phone, age, peer_edu_name FROM patients WHERE p_id = ?";
    $stmt = mysqli_prepare($conn, $patient_query);
    mysqli_stmt_bind_param($stmt, "s", $p_id);
    mysqli_stmt_execute($stmt);
    $patient_result = mysqli_stmt_get_result($stmt);
    $client = mysqli_fetch_assoc($patient_result);
    mysqli_stmt_close($stmt);
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$lab_office_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $lab_office_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Fetch facility name from facility_settings
$facilityname = '';
$facilityQuery = "SELECT facilityname FROM facility_settings LIMIT 1";
$facilityResult = mysqli_query($conn, $facilityQuery);
if ($facilityResult && mysqli_num_rows($facilityResult) > 0) {
    $facilityRow = mysqli_fetch_assoc($facilityResult);
    $facilityname = $facilityRow['facilityname'];
}

// Fetch clinicians list
$clinicians = [];
$clinicianQuery = "SELECT first_name, last_name FROM tblusers WHERE userrole = 'clinician' OR userrole LIKE '%clinician%'";
$clinicianResult = mysqli_query($conn, $clinicianQuery);
if ($clinicianResult) {
    while ($row = mysqli_fetch_assoc($clinicianResult)) {
        $clinicians[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}

// Fetch counselors list
$counselors = [];
$counselorQuery = "SELECT first_name, last_name FROM tblusers WHERE userrole = 'psychologist' OR userrole LIKE '%psychiatrist%'";
$counselorResult = mysqli_query($conn, $counselorQuery);
if ($counselorResult) {
    while ($row = mysqli_fetch_assoc($counselorResult)) {
        $counselors[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Consent Form</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .form-container {
            width: 85%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }
        .header-center {
            text-align: center;
        }
        .form-header h2 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
        }
        .form-header h4 {
            color: #6633CC;
            font-size: 18px;
            margin: 5px 0;
        }
        .form-header p {
            color: #7f8c8d;
            font-size: 14px;
            text-align: right;
            margin: 0;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .form-group label {
            width: 250px;
            font-weight: bold;
            color: #2c3e50;
            margin-right: 15px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 350px;
            padding: 10px;
            border: 1px solid #dcdcdc;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
        }
        .form-groups {
            margin: 20px 0;
        }
        .form-groups p {
            margin: 12px 0;
            padding: 8px 0;
        }
        .form-groups input[type="text"] {
            padding: 6px 10px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            margin: 0 5px;
            min-width: 150px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        .signature-table th,
        .signature-table td {
            border: 1px solid #dcdcdc;
            padding: 12px;
            text-align: left;
        }
        .signature-table th {
            background-color: #3498db;
            color: #fff;
            font-weight: bold;
        }
        .signature-table td {
            background-color: #f9f9f9;
        }
        .signature-table select,
        .signature-table input {
            width: 100%;
            padding: 8px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .submit-button {
            display: block;
            margin: 30px auto 0;
            padding: 12px 30px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .submit-button:hover {
            background-color: #2980b9;
        }
        .readonly-input {
            cursor: not-allowed;
            background-color: #FFCCFF;

        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .section-title {
            color: #2c3e50;
            font-size: 20px;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #ecf0f1;
        }
        .consent-list {
            margin-left: 40px;
        }
        .consent-list p {
            margin: 10px 0;
        }
        .italic-text {
            font-style: italic;
        }
        .red-text {
            color: red;
        }
        .blue-text {
            color: blue;
        }
        .bold-text {
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .form-container {
                width: 95%;
                padding: 15px;
            }
            .form-header {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 10px;
            }
            .form-header p {
                text-align: center;
            }
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            .form-group label {
                width: 100%;
                margin-bottom: 5px;
            }
            .form-group input,
            .form-group select,
            .form-group textarea {
                width: 100%;
            }
            .signature-table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>CLIENT CONSENT FORM</h4>
            </div>
            <p>FORM 1A VER. APR. 2023</p>
        </div>

        <form action="submit_form3h.php" method="POST">
            <!-- Hidden field for patient ID -->
            <?php if (isset($_GET['p_id'])): ?>
                <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($_GET['p_id']); ?>">
            <?php endif; ?>

            <!-- Basic Information Section -->
            <div class="section-title" style="color: blue; font-weight: bold; font-size: 18px;">Client Information</div>

            <div class="form-group">
                <label for="visitDate" class="required-field">Date (dd/mm/yyyy):</label>
                <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('d/m/Y'); ?>">
            </div>

            <div class="form-group">
                <label for="client_name" class="required-field">Name of Client:</label>
                <input type="text" id="client_name" name="client_name" class="readonly-input" readonly
                       value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="mat_id" class="required-field">MAT ID:</label>
                <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly
                       value="<?php echo $client ? htmlspecialchars($client['mat_id']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="sex" class="required-field">Sex:</label>
                <input type="text" id="sex" name="sex" class="readonly-input" readonly
                       value="<?php echo $client ? htmlspecialchars($client['sex']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="cso" >CSO:</label>
                <input type="text" id="cso" name="cso"
                       value="<?php echo htmlspecialchars($client['cso'] ?? ''); ?>">
            </div>

            <!-- Client Declaration Section -->
            <div class="section-title" style="color: blue; font-weight: bold; font-size: 18px;">Client Declaration</div>

            <div class="form-groups">
                <p>I <input type="text" name="declaration_name" class="readonly-input" readonly
                           value="<?php echo $client ? htmlspecialchars($client['clientName']) : ''; ?>" required>
                    &nbsp;&nbsp; of telephone number&nbsp;
                    <input type="text" name="client_phone" value="<?php echo $client ? htmlspecialchars($client['client_phone']) : ''; ?>">
                </p>

                <p>and ID number&nbsp; <input type="text" name="nat_id"></p>

                <p><span class="red-text italic-text">(where the client is under the age of 18 years, state the age of the patient)</span>
                    <input type="text" name="age" value="<?php echo $client ? htmlspecialchars($client['age']) : ''; ?>">
                </p>

                <p>and accompanied by&nbsp;
                    <input type="text" name="guardian" placeholder="If parent or guardian">
                    <span class="italic-text blue-text">(if accompanied by a guardian, also indicate the name and details of the guardian)</span>
                </p>

                <p>Guardian Name&nbsp;
                    <input type="text" name="guardianName">&nbsp;&nbsp;
                    Guardian ID: <input type="text" name="guardianID">
                </p>
            </div>

            <!-- Consent Section -->
            <div class="section-title">Consent Agreement</div>

            <div class="form-groups">
                <p class="blue-text bold-text" style="font-size: 18px;">I do hereby willingly consent to the following:</p>
            </div>

            <div class="consent-list">
                <p>1. That I have been given information at the CSO about the MAT program</p>
                <p>2. I have been taken through the rules and regulations of in the MAT program</p>
                <p>3. I understand that participation in the program is voluntary</p>
                <p>4. I have been informed of the risks and benefits of being in the MAT program</p>
                <p>5. Although I understand that the treatment is beneficial to me, I have the right to withdraw from treatment</p>
                <p>7.	I agree to keep, and be on time for all my scheduled appointments with the service provider and his/her health care team at the clinic/treatment Centre.</p>
                <p>8.	I understand that the staff at the clinic/treatment Centre will need to confirm my identity every time before issuing my medication.</p>
                <p>9.	I agree to conduct myself in a courteous manner at the clinic/treatment Centre; No violence, verbal abuse, physical assault and repeated unacceptable destructive behavior to staff and or fellow clients.</p>
                <p>10.	I agree not to arrive at the clinic/treatment Centre intoxicated or under the influence of drugs. If I do, the doctor may not see me and I may not be given any medication until my next scheduled appointment.</p>
                <p>11.	I agree not to sell, share or give any of my medication to another person. I understand that such mishandling of my medication is a serious violation of this agreement and would result in my treatment being terminated without recourse for appeal.</p>
                <p>12.	I agree not to deal, steal or conduct any other illegal or disruptive activities in the clinic/treatment Centre –Drug possession/dealing, carrying weapons and property damage within and around the facility.</p>
                <p>13.	I agree to collect my medication personally at my regular clinic/treatment Centre through daily visits and to consume the whole dose under direct observation of dispensing staff.</p>
                <p>14.	I understand that if I miss an appointment and fail to collect my medication on any day I will not be given an extra dose the following day.</p>
                <p>15.	I understand that if I miss three or more consecutive doses of my medication, the prescription will be cancelled and can only be renewed after another full medical check-up. </p>
                <p>16.	I agree that it is my responsibility to take the full dose of medication I receive from the clinic/treatment Centre staff. I agree that any medication that spills/drops while being taken will not be replaced regardless of the reasons for the loss.</p>
                <p>17.	I understand the dangers of taking more than my prescribed dose of methadone. I agree not to obtain similar medications from any other physicians, pharmacies or other sources without informing my primary treatment providers.</p>
                <p>18.	I understand that mixing my methadone/buprenorphine with other substances, especially alcohol, benzodiazepines such as Diazepam, and other drugs of abuse, can be dangerous. I also understand that death can occur among persons mixing methadone/buprenorphine with benzodiazepines.</p>
                <p>19.	I agree to take my medication as the doctor has instructed and not to alter the way I take my medication without first consulting the doctor.</p>
                <p>20.	I understand that methadone/buprenorphine alone is not sufficient treatment for my dependence and I agree to participate in the patient education and relapse prevention program, as provided, to assist me in my treatment.</p>
                <p>21.	I understand that the consent form will be administered after 3 months of induction and when need arise.</p>
                <p>22.	I understand that consenting to the above listed rules will apply to the mobile van. I will also be bound by all MAT clinic regulations.</p>

            </div>

            <!-- Agreement Section -->
            <div class="form-groups">
                <p>I have been given an opportunity to ask any questions that will help me make an informed decision</p>

                <p>I FREELY and VOLUNTARILY agree to undergo MAT at
                    <input type="text" name="facilityname"
                           value="<?php echo htmlspecialchars($facilityname); ?>" required>
                    or any other MAT outlet:
                </p>
            </div>

            <!-- Signature Section -->
            <div class="form-group">
                <label for="thumb_print" class="required-field">Client's Signature or Left thumb print:</label>
                <a href="../clinician/fingerprint_capture.php?p_id=<?php echo htmlspecialchars($client['mat_id']); ?>">Capture Fingerprint</a>
            </div>

            <!-- Treatment Team Section -->
            <div class="section-title">Treatment Team</div>

            <table class="signature-table">
                <thead>
                    <tr>
                        <th>Designation</th>
                        <th>Name</th>
                        <th>Organization</th>
                        <th>Signature</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>MAT Clinician</td>
                        <td>
                            <select name="clinician_name" required>
                                <option value="">Select Clinician</option>
                                <?php foreach ($clinicians as $clinician): ?>
                                    <option value="<?php echo htmlspecialchars($clinician); ?>">
                                        <?php echo htmlspecialchars($clinician); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="clinician_org" value="<?php echo htmlspecialchars($facilityname); ?>" required></td>
                        <td><input type="text" name="clinician_signature" placeholder="Clinician's signature" required></td>
                        <td><input type="text" name="clinician_date" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>" required></td>
                    </tr>
                    <tr>
                        <td>MAT Counselor</td>
                        <td>
                            <select name="counselor_name" required>
                                <option value="">Select Counselor</option>
                                <?php foreach ($counselors as $counselor): ?>
                                    <option value="<?php echo htmlspecialchars($counselor); ?>">
                                        <?php echo htmlspecialchars($counselor); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="counselor_org" value="<?php echo htmlspecialchars($facilityname); ?>" required></td>
                        <td><input type="text" name="counselor_signature" placeholder="Counselor's signature" required></td>
                        <td><input type="text" name="counselor_date" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>" required></td>
                    </tr>
                </tbody>
            </table>

            <!-- Submit Button -->
            <button type="submit" class="submit-button">Submit Consent Form</button>
        </form>
    </div>

    <script>
        // Auto-fill current date for signature dates if empty
        document.addEventListener('DOMContentLoaded', function() {
            const currentDate = '<?php echo date("d/m/Y"); ?>';

            // Set current date for signature fields if they're empty
            const dateFields = document.querySelectorAll('input[name="clinician_date"], input[name="counselor_date"]');
            dateFields.forEach(field => {
                if (!field.value) {
                    field.value = currentDate;
                }
            });
        });
    </script>
</body>
</html>