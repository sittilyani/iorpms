<?php
session_start();
include '../includes/config.php';

$page_title = 'Psychiatric encounter form';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$conn->set_charset('utf8mb4');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

if ($userId) {
    // Fetch the current settings for the user
    $query = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();
}

// Fetch the logged-in user's name from tblusers
$reviewed_by = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $reviewed_by = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// Placeholder for missing variables in your original code
$religionOptions = '<option value="Christian">Christian</option><option value="Muslim">Muslim</option>';
$referralOptions = '<option value="Self">Self</option><option value="Clinic">Clinic</option>';

// Handle success/error messages
$successMessage = '';
$errorMessage = '';

if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychiatric Encounter Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
        }
        .form-container {
            width: 80%;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
        }
        .form-header h3 {
            color: #6633CC;
            font-size: 18px;
            margin: 5px 0;
        }
        .form-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-group label {
            width: 250px;
            font-weight: bold;
            color: #2c3e50;
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
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
        }
        .checkbox-group {
            margin: 20px 0;
        }
        .checkbox-group label {
            display: block;
            margin-bottom: 12px;
            color: #34495e;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        .radio-group {
            margin: 20px 0;
        }
        .radio-group label {
            display: block;
            margin-bottom: 10px;
            color: #34495e;
        }
        .radio-group input[type="radio"] {
            margin-right: 10px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .signature-table th,
        .signature-table td {
            border: 1px solid #dcdcdc;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        .signature-table th {
            background-color: #3498db;
            color: #fff;
        }
        .signature-table td {
            background-color: #f9f9f9;
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

        /* Color Palette */
        :root {
            --color-primary: #4a90e2;
            --color-secondary: #50b89a;
            --color-dark: #2C3162;
            --color-light-bg: #f8f9fa;
            --color-card-bg: white;
            --color-readonly: #e3f0ff;
            --color-warning: #ffb74d;
            --color-danger: #d32f2f;
            --color-success: #4caf50;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', Arial, sans-serif;
        }

        /* Alert Messages */
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .alert-success i, .alert-error i {
            margin-right: 10px;
            font-size: 20px;
        }

        .custom-submit-btn {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px auto;
        }

        .custom-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .section-header {
            background-color: var(--color-light-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-primary);
        }

        .section-header h2 {
            color: var(--color-dark);
            margin-bottom: 15px;
            font-size: 20px;
        }

        .section-full-width {
            width: 100%;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Custom Alert */
        .custom-alert {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            text-align: center;
        }

        .custom-alert button {
            margin-top: 15px;
            padding: 8px 20px;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Success/Error Messages -->
        <?php if ($successMessage): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <form action="submit_form3j.php?mat_id=<?php echo urlencode($userId); ?>" method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($userId); ?>">

            <div class="form-header">
                <div>
                    <h2>REPUBLIC OF KENYA</h2>
                    <h3>MINISTRY OF HEALTH</h3>
                </div>
                <div>
                    <h2>PSYCHIATRIC ENCOUNTER FORM</h2>
                    <p>Form 3J</p>
                </div>
                <div>
                    <p>Patient ID: <?php echo htmlspecialchars($userId); ?></p>
                </div>
            </div>

            <div style="width: 100%;">
                <!-- SECTION 1: DEMOGRAPHICS -->
                <div class="section-header">
                    <h2>1. DEMOGRAPHICS</h2>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <div class="form-group">
                                <label for="clientName">Client Name</label>
                                <input type="text" name="clientName" value="<?php echo isset($currentSettings['first_name']) ? htmlspecialchars($currentSettings['first_name'] . ' ' . $currentSettings['last_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" name="age" value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select name="gender">
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo (isset($currentSettings['gender']) && $currentSettings['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($currentSettings['gender']) && $currentSettings['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" value="<?php echo isset($currentSettings['dob']) ? htmlspecialchars($currentSettings['dob']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" name="address" value="<?php echo isset($currentSettings['address']) ? htmlspecialchars($currentSettings['address']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="tel">Telephone</label>
                                <input type="tel" name="tel" value="<?php echo isset($currentSettings['tel']) ? htmlspecialchars($currentSettings['tel']) : ''; ?>">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input type="text" name="occupation">
                            </div>
                            <div class="form-group">
                                <label for="education">Education</label>
                                <input type="text" name="education">
                            </div>
                            <div class="form-group">
                                <label for="religion">Religion</label>
                                <select name="religion">
                                    <option value="">Select</option>
                                    <?php echo $religionOptions; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="marital_status">Marital Status</label>
                                <select name="marital_status">
                                    <option value="">Select</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="referral">Referral From</label>
                                <select name="referral">
                                    <option value="">Select</option>
                                    <?php echo $referralOptions; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="relative_name">Relative Name</label>
                                <input type="text" name="relative_name">
                            </div>
                            <div class="form-group">
                                <label for="reletionships">Relationship</label>
                                <input type="text" name="reletionships">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: PRESENTING COMPLAINTS -->
                <div class="section-header section-full-width">
                    <h2>2. PRESENTING COMPLAINTS</h2>
                    <div class="form-group">
                        <label for="complaints_from_pt">Complaints from patient</label>
                        <textarea name="complaints_from_pt" id="complaints_from_pt"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="collaborative_hx">Collaborative History</label>
                        <textarea name="collaborative_hx" id="collaborative_hx"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="presenting_illness_hx">History of presenting illness</label>
                        <textarea name="presenting_illness_hx" id="presenting_illness_hx"></textarea>
                    </div>
                </div>

                <!-- SECTION 3: PAST PSYCHIATRIC HISTORY -->
                <div class="section-header section-full-width">
                    <h2>3. PAST PSYCHIATRIC HISTORY</h2>
                    <div class="form-group">
                        <label for="past_psychiatric_hx">Past psychiatric history</label>
                        <textarea name="past_psychiatric_hx" id="past_psychiatric_hx"></textarea>
                    </div>
                </div>

                <!-- SECTION 4: PAST MEDICAL AND SURGICAL HISTORY -->
                <div class="section-header section-full-width">
                    <h2>4. PAST MEDICAL AND SURGICAL HISTORY</h2>
                    <div class="form-group">
                        <label for="past_medsurg_hx">Past medical and surgical history</label>
                        <textarea name="past_medsurg_hx" id="past_medsurg_hx"></textarea>
                    </div>
                </div>

                <!-- SECTION 5: SUBSTANCE USE HISTORY -->
                <div class="section-header section-full-width">
                    <h2>5. SUBSTANCE USE HISTORY</h2>
                    <div class="form-group">
                        <label for="substance_use_hx">Substance use history</label>
                        <textarea name="substance_use_hx" id="substance_use_hx"></textarea>
                    </div>
                </div>

                <!-- SECTION 6: FAMILY HISTORY -->
                <div class="section-header section-full-width">
                    <h2>6. FAMILY HISTORY</h2>
                    <div class="form-group">
                        <label for="family_hx">Family history</label>
                        <textarea name="family_hx" id="family_hx"></textarea>
                    </div>
                </div>

                <!-- SECTION 7: PERSONAL HISTORY -->
                <div class="section-header">
                    <h2>7. PERSONAL HISTORY</h2>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="anc_birth_hx">Antenatal and birth history</label>
                            <textarea name="anc_birth_hx" id="anc_birth_hx"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="early_devt">Early development</label>
                            <textarea name="early_devt" id="early_devt"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="child_devt">Childhood development</label>
                            <textarea name="child_devt" id="child_devt"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edu_hx">Education History</label>
                            <textarea name="edu_hx" id="edu_hx"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="occupation_hx">Occupation History</label>
                            <textarea name="occupation_hx" id="occupation_hx"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="sexual_hx">Sexual History</label>
                            <textarea name="sexual_hx" id="sexual_hx"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="premorbid_hx">Premorbid History</label>
                            <textarea name="premorbid_hx" id="premorbid_hx"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="forensic_hx">Forensic History</label>
                            <textarea name="forensic_hx" id="forensic_hx"></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECTION 8: EXAMINATIONS -->
                <div class="section-header section-full-width">
                    <h2>8. EXAMINATIONS</h2>
                    <div class="form-group">
                        <label for="physical_exam">Physical Examination Form 3A</label>
                        <textarea name="physical_exam" id="physical_exam"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="mental_status_exam">Mental Status Examination Form 3A - 14</label>
                        <p>(Appearance and behaviour, mood and effect, speech, thought, perception, memory, cognition, insight)</p>
                        <p>PASTE SEC 16 FROM FORM 3A - 14</p>
                        <textarea name="mental_status_exam" id="mental_status_exam"></textarea>
                    </div>
                </div>

                <!-- SECTION 9: DIAGNOSIS -->
                <div class="section-header section-full-width">
                    <h2>9. DIAGNOSIS</h2>
                    <div class="form-group">
                        <label for="diagnosis">Diagnosis</label>
                        <textarea name="diagnosis" id="diagnosis"></textarea>
                    </div>
                </div>

                <!-- SECTION 10: MANAGEMENT PLAN -->
                <div class="section-header section-full-width">
                    <h2>10. MANAGEMENT PLAN (Biopsychosocial & spiritual)</h2>
                    <div class="form-group">
                        <label for="management_plan">Management Plan</label>
                        <textarea name="management_plan" id="management_plan"></textarea>
                    </div>
                </div>

                <!-- SECTION 11: PSYCHIATRIC FOLLOW UP VISIT -->
                <div class="section-header">
                    <h2>11. PSYCHIATRIC FOLLOW UP VISIT</h2>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="visitDate">Visit Date</label>
                            <input type="text" name="visitDate" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="psychiatric_tca">Next Appointment</label>
                            <input type="date" name="psychiatric_tca">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="progress_report">Progress Report</label>
                        <textarea name="progress_report" id="progress_report"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="rx_plan_copy">Treatment Plan</label>
                        <textarea name="rx_plan_copy" id="rx_plan_copy"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="service_provider">Reviewed By</label>
                        <input type="text" name="service_provider" value="<?php echo htmlspecialchars($reviewed_by); ?>" readonly>
                    </div>

                    <button type="submit" class="custom-submit-btn">
                        <i class="fas fa-save"></i> Save Encounter
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- Custom Alert -->
    <div id="customAlert" class="custom-alert">
        <p>Please complete all required fields!</p>
        <button onclick="document.getElementById('customAlert').style.display='none'">OK</button>
    </div>

    <script>
        // Form validation
        function validateForm() {
            const clientName = document.querySelector('input[name="clientName"]').value;
            const diagnosis = document.getElementById('diagnosis').value;

            if (!clientName || !diagnosis) {
                document.getElementById('customAlert').style.display = 'block';
                return false;
            }
            return true;
        }

        // Auto-sizing textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
            });
        });

        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>

</body>
</html>