<?php
session_start();
include '../includes/config.php';

$page_title = 'Yellow Card Clinical Visit Form';

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

// Get the mat_id from the query parameter
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

if (!$mat_id) {
    die("Patient ID (mat_id) is required");
}

// Fetch patient information
$query = "SELECT * FROM patients WHERE mat_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $mat_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found");
}

// Fetch medical_history_new information
$query2 = "SELECT art_regimen FROM medical_history_new WHERE mat_id = ?";
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param('s', $mat_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$medical_history = $result2->fetch_assoc();
$stmt2->close();

// Fetch the logged-in user's name
$clinician_name = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $clinician_name = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// Handle success/error messages
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #50b89a;
            --dark-color: #2C3162;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --border-color: #dcdcdc;
            --success-color: #4caf50;
            --danger-color: #d32f2f;
            --warning-color: #ff9800;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: yellow;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
        }

        .form-header h1 {
            color: var(--dark-color);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .patient-info {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .patient-info-item {
            display: flex;
            flex-direction: column;
        }

        .patient-info-item label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 3px;
        }

        .patient-info-item span {
            font-size: 16px;
            font-weight: 600;
        }

        .section {
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
        }

        .section-header {
            background: var(--dark-color);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group input[readonly] {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 30px auto;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
        }

        .back-btn {
            padding: 10px 25px;
            background: var(--danger-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #b71c1c;
            transform: translateX(-3px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        .full-width {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <a href="search_yellow_card.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Search
    </a>

    <div class="container">
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="form-header">
            <h1><i class="fas fa-file-medical-alt"></i> FORM 3C - MEDICALLY ASSISTED THERAPY</h1>
            <p>Patient Treatment Card - Clinical Follow-up Visit</p>
        </div>

        <div class="patient-info">
            <div class="patient-info-item">
                <label>MAT ID</label>
                <span><?php echo htmlspecialchars($patient['mat_id']); ?></span>
            </div>
            <div class="patient-info-item">
                <label>Client Name</label>
                <span><?php echo htmlspecialchars($patient['clientName']); ?></span>
            </div>
            <div class="patient-info-item">
                <label>Age / Sex</label>
                <span><?php echo htmlspecialchars($patient['age'] . ' / ' . $patient['sex']); ?></span>
            </div>
            <div class="patient-info-item">
                <label>Current Drug</label>
                <span><?php echo htmlspecialchars($patient['drugname']); ?></span>
            </div>
            <div class="patient-info-item">
                <label>Current Dosage</label>
                <span><?php echo htmlspecialchars($patient['dosage']); ?></span>
            </div>
        </div>

        <form action="submit_yellow_card.php" method="POST">
            <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($mat_id); ?>">

            <!-- Basic Visit Information -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-calendar-check"></i> Visit Information
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="consultation_date">Date of Consultation *</label>
                        <input type="date" name="consultation_date" id="consultation_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="mat_start_date">Date Started on MAT</label>
                        <input type="date" name="mat_start_date" id="mat_start_date" value="<?php echo htmlspecialchars($patient['reg_date']); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Vital Signs -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-heartbeat"></i> Vital Signs
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="height_weight_bmi">Height/Weight (BMI)</label>
                        <input type="text" name="height_weight_bmi" id="height_weight_bmi" placeholder="e.g., 170cm/70kg (24.2)">
                    </div>
                    <div class="form-group">
                        <label for="bp">Blood Pressure</label>
                        <input type="text" name="bp" id="bp" placeholder="e.g., 120/80">
                    </div>
                    <div class="form-group">
                        <label for="pulse_rate">Pulse Rate</label>
                        <input type="text" name="pulse_rate" id="pulse_rate" placeholder="e.g., 72 bpm">
                    </div>
                    <div class="form-group">
                        <label for="temperature">Temperature</label>
                        <input type="text" name="temperature" id="temperature" placeholder="e.g., 36.5°C">
                    </div>
                    <div class="form-group">
                        <label for="rr_spo2">RR/SPO2</label>
                        <input type="text" name="rr_spo2" id="rr_spo2" placeholder="e.g., 18/98%">
                    </div>
                    <div class="form-group">
                        <label for="alcohol_breathalyzer">Alcohol Breathalyzer Results</label>
                        <input type="text" name="alcohol_breathalyzer" id="alcohol_breathalyzer" placeholder="Below 0.02 full dose, 0.02-0.04 half dose, >0.04 withhold">
                    </div>
                </div>
            </div>

            <!-- Methadone/Buprenorphine Maintenance -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-pills"></i> Methadone/Buprenorphine Maintenance
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="current_dose">Current Dose</label>
                        <input type="text" name="current_dose" id="current_dose" value="<?php echo htmlspecialchars($patient['dosage']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="days_missed_doses">Days Missed Doses</label>
                        <input type="text" name="days_missed_doses" id="days_missed_doses">
                    </div>
                    <div class="form-group">
                        <label for="cows_score">COWS Score</label>
                        <input type="text" name="cows_score" id="cows_score">
                    </div>
                    <div class="form-group">
                        <label for="new_dose">New Dose</label>
                        <input type="text" name="new_dose" id="new_dose">
                    </div>
                    <div class="form-group full-width">
                        <label for="complaints">Complaints</label>
                        <textarea name="complaints" id="complaints"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label for="signs_overdose">Signs of Overdose/Over Medication</label>
                        <textarea name="signs_overdose" id="signs_overdose"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="urine_drug_test">Urine Drug Test Results</label>
                        <input type="text" name="urine_drug_test" id="urine_drug_test">
                    </div>
                    <div class="form-group">
                        <label for="ecg_results">ECG Results</label>
                        <input type="text" name="ecg_results" id="ecg_results">
                    </div>
                    <div class="form-group full-width">
                        <label for="dose_adjustment_reason">Reason for Dose Adjustment</label>
                        <textarea name="dose_adjustment_reason" id="dose_adjustment_reason"></textarea>
                    </div>
                </div>
            </div>

            <!-- Treatment for Side Effects -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-exclamation-triangle"></i> Treatment for MTD/Buprenorphine Side Effects
                </div>
                <div class="form-group full-width">
                    <label for="side_effects_treatment">Side Effects and Treatment</label>
                    <textarea name="side_effects_treatment" id="side_effects_treatment" rows="3"></textarea>
                </div>
            </div>

            <!-- Management of Co-morbidities -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-procedures"></i> Management of Identified Co-morbidities
                </div>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="mental_health_treatment">Mental Health Disorder Treatment</label>
                        <textarea name="mental_health_treatment" id="mental_health_treatment"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="art_regimen">ART Regimen (All HIV +ve clients)</label>
                        <input type="text" name="art_regimen" id="art_regimen" value="<?php echo htmlspecialchars($medical_history['art_regimen'] ?? 'Not on ART'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="viral_load_results">Viral Load Results</label>
                        <input type="text" name="viral_load_results" id="viral_load_results">
                    </div>
                    <div class="form-group">
                        <label for="prep_pep">PrEP/PEP</label>
                        <input type="text" name="prep_pep" id="prep_pep">
                    </div>
                    <div class="form-group">
                        <label for="tb_screening_treatment">TB Screening/Treatment Regimen</label>
                        <input type="text" name="tb_screening_treatment" id="tb_screening_treatment">
                    </div>
                    <div class="form-group">
                        <label for="hepatitis_b_regimen">Hepatitis B – Regimen</label>
                        <input type="text" name="hepatitis_b_regimen" id="hepatitis_b_regimen">
                    </div>
                    <div class="form-group">
                        <label for="hepatitis_c_regimen">Hepatitis C – Regimen</label>
                        <input type="text" name="hepatitis_c_regimen" id="hepatitis_c_regimen">
                    </div>
                    <div class="form-group">
                        <label for="sti_treatment">STI Treatment</label>
                        <input type="text" name="sti_treatment" id="sti_treatment">
                    </div>
                    <div class="form-group full-width">
                        <label for="other_comorbidity">Other</label>
                        <textarea name="other_comorbidity" id="other_comorbidity"></textarea>
                    </div>
                </div>
            </div>

            <!-- Reproductive Health Services -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-venus"></i> Reproductive Health Services
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="pregnant">Pregnant (Yes/No/NA)</label>
                        <select name="pregnant" id="pregnant">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="NA">N/A</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="lmp">LMP (Last Menstrual Period)</label>
                        <input type="date" name="lmp" id="lmp">
                    </div>
                    <div class="form-group">
                        <label for="edd">EDD (Expected Delivery Date)</label>
                        <input type="date" name="edd" id="edd">
                    </div>
                    <div class="form-group">
                        <label for="cervical_cancer_screening">Cervical Cancer Screening (Y/N)</label>
                        <select name="cervical_cancer_screening" id="cervical_cancer_screening">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="on_fp">On Family Planning (Y/N)</label>
                        <select name="on_fp" id="on_fp">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fp_method">FP Method</label>
                        <input type="text" name="fp_method" id="fp_method">
                    </div>
                    <div class="form-group full-width">
                        <label for="gbv_screening">GBV Screening Results</label>
                        <textarea name="gbv_screening" id="gbv_screening"></textarea>
                    </div>
                </div>
            </div>

            <!-- Referral and Linkages -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-link"></i> Referral and Linkages
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="psychosocial_support">Psychosocial Support</label>
                        <input type="text" name="psychosocial_support" id="psychosocial_support">
                    </div>
                    <div class="form-group">
                        <label for="psychiatric_support">Psychiatric Support</label>
                        <input type="text" name="psychiatric_support" id="psychiatric_support">
                    </div>
                    <div class="form-group">
                        <label for="nutritional_support">Nutritional Support</label>
                        <input type="text" name="nutritional_support" id="nutritional_support">
                    </div>
                    <div class="form-group">
                        <label for="vaccination_service">Vaccination Service</label>
                        <input type="text" name="vaccination_service" id="vaccination_service">
                    </div>
                    <div class="form-group">
                        <label for="sexual_reproductive_health">Sexual and Reproductive Health</label>
                        <input type="text" name="sexual_reproductive_health" id="sexual_reproductive_health">
                    </div>
                    <div class="form-group">
                        <label for="radiology_service">Radiology Service</label>
                        <input type="text" name="radiology_service" id="radiology_service">
                    </div>
                    <div class="form-group">
                        <label for="laboratory_service">Laboratory Service</label>
                        <input type="text" name="laboratory_service" id="laboratory_service">
                    </div>
                    <div class="form-group">
                        <label for="legal_paralegal_services">Legal/Paralegal Services</label>
                        <input type="text" name="legal_paralegal_services" id="legal_paralegal_services">
                    </div>
                    <div class="form-group">
                        <label for="social_protection">Social Protection</label>
                        <input type="text" name="social_protection" id="social_protection">
                    </div>
                    <div class="form-group">
                        <label for="gbv_services">GBV Services</label>
                        <input type="text" name="gbv_services" id="gbv_services">
                    </div>
                    <div class="form-group full-width">
                        <label for="other_referrals">Others</label>
                        <textarea name="other_referrals" id="other_referrals"></textarea>
                    </div>
                </div>
            </div>

            <!-- Follow-up and Clinician Info -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-user-md"></i> Follow-up and Clinician Information
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="next_visit_date">Date of Next Visit *</label>
                        <input type="date" name="next_visit_date" id="next_visit_date" required>
                    </div>
                    <div class="form-group">
                        <label for="clinician_name">Clinician Name</label>
                        <input type="text" name="clinician_name" id="clinician_name" value="<?php echo htmlspecialchars($clinician_name); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="clinician_signature">Clinician Signature</label>
                        <input type="text" name="clinician_signature" id="clinician_signature" placeholder="Type your name to sign">
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Save Clinical Visit
            </button>
        </form>
    </div>

    <script>
        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
