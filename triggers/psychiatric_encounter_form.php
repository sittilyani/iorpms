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
    $stmt->close();}

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychiatric Encounter Form</title>
    <!-- Assuming bootstrap.min.js is used for utility/modals, though not strictly needed for this aesthetic style -->
    <script src="../assets/js/bootstrap.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Color Palette */
        :root {
            --color-primary: #4a90e2; /* Professional Blue */
            --color-secondary: #50b89a; /* Calming Green */
            --color-dark: #2C3162; /* Navy Text */
            --color-light-bg: #f8f9fa; /* Light background */
            --color-card-bg: white;
            --color-readonly: #e3f0ff; /* Very light blue for readonly */
            --color-warning: #ffb74d; /* Orange for warning */
            --color-danger: #d32f2f; /* Red for danger */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', Arial, sans-serif;
        }

        body {
            background-color: var(--color-light-bg);
            padding: 20px;
        }

        .container {
            max-width: 1500px;
            margin: 0 auto;
        }

        h2 {
            color: var(--color-dark);
            margin: 20px 0 10px;
            text-align: left;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--color-primary);
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* --- Stats Container Styling (Data Cards) --- */
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            background-color: var(--color-card-bg);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            flex: 1;
            min-width: 120px; /* Ensure readability on small screens */
            margin: 5px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        /* Stat specific coloring (using original class names but enhanced colors) */
        .stat-days { background-color: #e6f9f0; color: var(--color-secondary); border: 1px solid #b3e0c4; }
        .stat-missed { background-color: #ffeeee; color: var(--color-danger); border: 1px solid #ffcccc; }
        .stat-appointment { background-color: #eaf3ff; color: var(--color-primary); border: 1px solid #cce0ff; }
        .stat-visit { background-color: #f7eaff; color: #9c27b0; border: 1px solid #e1bee7; }
        .stat-days-next { background-color: #fff8eb; color: var(--color-warning); border: 1px solid #ffd8a1; }
        .stat-photo { background-color: #e6f9e6; color: var(--color-secondary); border: 1px solid #b3e0b3; }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 13px;
            margin-top: 5px;
            opacity: 0.8;
        }

        /* --- Form Container Styling --- */
        /* This is the main grid, but it contains all sections, so let's adjust the layout to be more vertical/section-based */
        .form-container {
            display: block; /* Disable the main 5-column grid for better section flow */
            background-color: var(--color-card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-header {
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
        }
        .section-header:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        /* Dedicated grid for the first section and photo to maintain the requested layout */
        .social-demographic-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr) 220px;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--color-dark);
            font-size: 14px;
        }

        .form-group p {
            font-size: 13px;
            color: #777;
            margin-top: -5px;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background-color: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.15);
            outline: none;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .readonly-input {
            background-color: var(--color-readonly);
            cursor: default;
            font-weight: bold;
            color: var(--color-dark);
            border-color: #c0d8f0;
        }

        /* Checkbox/Radio Group Styling */
        .checkbox-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .checkbox-item input[type="radio"] {
            margin-right: 5px;
            width: auto;
            transform: scale(1.1);
        }
        .checkbox-item label {
            display: inline;
            font-weight: normal;
            cursor: pointer;
        }

        /* --- Photo Container Styling (Vertical Slot) --- */
        .photo-container {
            grid-column: 5;
            grid-row: 1 / span 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border: 2px dashed #b0c4de;
            padding: 20px;
            border-radius: 10px;
            background-color: #f0f7ff;
            height: 400px; /* Set a fixed height for visual balance */
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
        }

        .photo-container img {
            max-width: 100%;
            height: auto;
            border: 4px solid white;
            border-radius: 50%; /* Circular image */
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }

        .photo-container p {
            text-align: center;
            color: var(--color-primary);
            font-style: italic;
            font-size: 14px;
        }

        /* --- Submit Button Styling --- */
        .custom-submit-btn {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%; /* Full width in its section-header context */
            margin-top: 20px;
            transition: background-color 0.3s, transform 0.1s, box-shadow 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .custom-submit-btn:hover {
            background-color: #3b7ad6;
            transform: translateY(-1px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        /* Textarea specific full-width layouts */
        .section-full-width {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }


        /* MISC */
        .missed-appointment {
            color: var(--color-danger);
            font-weight: bold;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .social-demographic-grid {
                grid-template-columns: repeat(3, 1fr) 200px; /* 4 columns total + photo */
            }
        }

        @media (max-width: 992px) {
            .social-demographic-grid {
                grid-template-columns: repeat(2, 1fr) 200px; /* 2 columns + photo */
            }
            .stats-container {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .social-demographic-grid {
                grid-template-columns: 1fr; /* Stack all columns */
            }
            .photo-container {
                grid-column: 1;
                order: -1; /* Move photo to the top */
                margin-bottom: 20px;
                height: auto;
            }
            .stat-item {
                flex: 1 1 45%; /* Two items per row on tablet */
            }
        }

    </style>
</head>
<body>
    <div class="container">

        <h2>Psychiatric Encounter Form - Form 3J</h2>

        <!-- Placeholder for stats from dynamic data -->
        <div class="stats-container">
            <div class="stat-item stat-days">
                <span class="stat-value">45</span>
                <span class="stat-label">Days In Care</span>
            </div>
            <div class="stat-item stat-missed">
                <span class="stat-value">2</span>
                <span class="stat-label">Missed Appointments</span>
            </div>
            <div class="stat-item stat-appointment">
                <span class="stat-value">12-01-2024</span>
                <span class="stat-label">Last Appt Date</span>
            </div>
            <div class="stat-item stat-days-next">
                <span class="stat-value">7</span>
                <span class="stat-label">Days to Next Appt</span>
            </div>
            <div class="stat-item stat-visit">
                <span class="stat-value">5</span>
                <span class="stat-label">Total Visits</span>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <span class="stat-label">Upload Photo</span>
            </div>
        </div>


        <form id="dispenseForm" action="dispensingData_process.php" method="post" onsubmit="return validateForm()">
            <div class="form-container">

                <!-- SECTION 1: SOCIAL-DEMOGRAPHIC DETAILS & PHOTO -->
                <div class="social-demographic-grid">

                    <div class="photo-container">
                        <!-- Placeholder image for visual clarity -->
                        <img src="https://placehold.co/180x180/4a90e2/ffffff?text=Client\nPhoto" alt="Client Photo">
                        <p>ID: <?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : 'N/A'; ?></p>
                        <button class="custom-submit-btn" style="background-color: #82b543;">Change Photo</button>
                    </div>

                    <div class="section-column" style="grid-column: 1 / span 4;">
                        <h2>1. SOCIAL-DEMOGRAPHIC DETAILS</h2>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                            <div class="form-group">
                                <label for="visitDate">Date of consultation</label>
                                <input type="date" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="clientName">Client Name</label>
                                <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="mat_id">MAT ID</label>
                                <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="text" name="dob" class="readonly-input" readonly value="<?php echo isset($currentSettings['dob']) ? htmlspecialchars($currentSettings['dob']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="text" name="age" class="readonly-input" readonly value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input type="text" name="occupation">
                            </div>
                            <div class="form-group">
                                <label for="sex">Gender</label>
                                <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="religion">Religion/spiritual belief</label>
                                <select id="religion_name" name="religion_name">
                                    <?php echo $religionOptions; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="referral">Referral</label>
                                <select id="referral" name="referral">
                                    <?php echo $referralOptions; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Accompanying Rx supporter</label>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="radio" id="yes" name="rx_supporter" value="yes">
                                        <label for="yes">Yes</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="radio" id="no" name="rx_supporter" value="no">
                                        <label for="no">No</label>
                                    </div>
                                </div>
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
                        <textarea name="complaints_from_pt" id="complaints_from_pt" ></textarea>
                    </div>
                    <div class="form-group">
                        <label for="collaborative_hx">Collaborative History</label>
                        <textarea name="collaborative_hx" id="collaborative_hx" ></textarea>
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

                <!-- SECTION 7: PERSONAL HISTORY (Grouped into 2 columns for better screen usage) -->
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
                        <p>(Appearance and behaviour, mood and effect, speech, thought, perception, memory, cognition, insight) </p>
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

                <!-- SECTION 11: PSYCHIATRIC FOLLOW UP VISIT (Grouped into 2 columns) -->
                <div class="section-header">
                    <h2>11. PSYCHIATRIC FOLLOW UP VISIT</h2>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label for="visitDate">Visit Date</label>
                            <input type ="text" name="visitDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="psychiatric_tca">Next Appointment</label>
                            <input type ="date" name="psychiatric_tca">
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
                        <input type ="text" name="service_provider" value="<?php echo htmlspecialchars($reviewed_by); ?>">
                    </div>

                    <button type="submit" class="custom-submit-btn">
                        <i class="fas fa-save"></i> Save Encounter
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- Custom Alert (since alert() is blocked in this environment) -->
    <div id="customAlert" class="custom-alert">
        <p>Please complete all required fields!</p>
        <button onclick="document.getElementById('customAlert').style.display='none'">OK</button>
    </div>

    <script>
        // Custom JavaScript for form validation or other UI logic
        function validateForm() {
            // Simple example validation
            const clientName = document.querySelector('input[name="clientName"]').value;
            const diagnosis = document.getElementById('diagnosis').value;

            if (!clientName || !diagnosis) {
                document.getElementById('customAlert').style.display = 'block';
                return false;
            }
            return true;
        }

        // Auto-sizing textareas for better content viewing
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
            });
        });
    </script>

</body>
</html>