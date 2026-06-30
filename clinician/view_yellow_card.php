<?php
session_start();
include '../includes/config.php';

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

// Fetch all visits for this patient
$visitsQuery = "SELECT * FROM yellow_card_visits WHERE mat_id = ? ORDER BY consultation_date DESC";
$stmt = $conn->prepare($visitsQuery);
$stmt->bind_param('s', $mat_id);
$stmt->execute();
$visitsResult = $stmt->get_result();
$visits = $visitsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Yellow Card - Patient Treatment Record';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #50b89a;
            --dark-color: #2C3162;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --border-color: #dcdcdc;
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
            max-width: 1600px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
        }

        .header h1 {
            color: var(--dark-color);
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .patient-info-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .info-item span {
            font-size: 16px;
            font-weight: 600;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #3a7bc8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #4caf50;
            color: var(--white);
        }

        .btn-success:hover {
            background: #388e3c;
        }

        .btn-danger {
            background: #d32f2f;
            color: var(--white);
        }

        .btn-danger:hover {
            background: #b71c1c;
        }

        .visits-summary {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .visits-summary h3 {
            color: var(--dark-color);
            font-size: 18px;
        }

        .visit-card {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .visit-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .visit-header {
            background: var(--dark-color);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .visit-header h3 {
            font-size: 18px;
        }

        .visit-date {
            background: var(--white);
            color: var(--dark-color);
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: 600;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            background: var(--light-bg);
            color: var(--dark-color);
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .data-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 3px solid var(--primary-color);
        }

        .data-item label {
            font-size: 12px;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }

        .data-item span {
            font-size: 14px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .data-full {
            grid-column: 1 / -1;
        }

        .no-visits {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-visits i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        @media print {
            .action-bar, .btn {
                display: none !important;
            }
            
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                padding: 20px;
            }
            
            .visit-card {
                page-break-inside: avoid;
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-medical"></i> FORM 3C - MEDICALLY ASSISTED THERAPY</h1>
            <p>Patient Treatment Card - Clinical Follow-up Visits Record</p>
        </div>

        <div class="patient-info-card">
            <div class="patient-info-grid">
                <div class="info-item">
                    <label>MAT ID</label>
                    <span><?php echo htmlspecialchars($patient['mat_id']); ?></span>
                </div>
                <div class="info-item">
                    <label>MAT Number</label>
                    <span><?php echo htmlspecialchars($patient['mat_number']); ?></span>
                </div>
                <div class="info-item">
                    <label>Client Name</label>
                    <span><?php echo htmlspecialchars($patient['clientName']); ?></span>
                </div>
                <div class="info-item">
                    <label>Nickname</label>
                    <span><?php echo htmlspecialchars($patient['nickName']); ?></span>
                </div>
                <div class="info-item">
                    <label>Age / Sex</label>
                    <span><?php echo htmlspecialchars($patient['age'] . ' / ' . $patient['sex']); ?></span>
                </div>
                <div class="info-item">
                    <label>Current Drug</label>
                    <span><?php echo htmlspecialchars($patient['drugname']); ?></span>
                </div>
                <div class="info-item">
                    <label>Current Dosage</label>
                    <span><?php echo htmlspecialchars($patient['dosage']); ?></span>
                </div>
                <div class="info-item">
                    <label>Status</label>
                    <span><?php echo htmlspecialchars($patient['current_status']); ?></span>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="search_yellow_card.php" class="btn btn-danger">
                <i class="fas fa-arrow-left"></i> Back to Search
            </a>
            <div>
                <a href="yellow_card_form.php?mat_id=<?php echo urlencode($mat_id); ?>" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Visit
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Record
                </button>
            </div>
        </div>

        <div class="visits-summary">
            <h3><i class="fas fa-chart-line"></i> Total Visits: <?php echo count($visits); ?></h3>
            <?php if (count($visits) > 0): ?>
                <span>Latest Visit: <?php echo date('d M Y', strtotime($visits[0]['consultation_date'])); ?></span>
            <?php endif; ?>
        </div>

        <?php if (count($visits) > 0): ?>
            <?php foreach ($visits as $index => $visit): ?>
                <div class="visit-card">
                    <div class="visit-header">
                        <h3><i class="fas fa-stethoscope"></i> Visit #<?php echo count($visits) - $index; ?></h3>
                        <span class="visit-date"><?php echo date('d M Y', strtotime($visit['consultation_date'])); ?></span>
                    </div>

                    <!-- Basic Visit Information -->
                    <div class="section">
                        <div class="section-title">
                            <i class="fas fa-calendar-check"></i> Visit Information
                        </div>
                        <div class="data-grid">
                            <div class="data-item">
                                <label>Consultation Date</label>
                                <span><?php echo $visit['consultation_date'] ? date('d M Y', strtotime($visit['consultation_date'])) : 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>MAT Start Date</label>
                                <span><?php echo $visit['mat_start_date'] ? date('d M Y', strtotime($visit['mat_start_date'])) : 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Next Visit Date</label>
                                <span><?php echo $visit['next_visit_date'] ? date('d M Y', strtotime($visit['next_visit_date'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Vital Signs -->
                    <div class="section">
                        <div class="section-title">
                            <i class="fas fa-heartbeat"></i> Vital Signs
                        </div>
                        <div class="data-grid">
                            <div class="data-item">
                                <label>Height/Weight (BMI)</label>
                                <span><?php echo $visit['height_weight_bmi'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Blood Pressure</label>
                                <span><?php echo $visit['bp'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Pulse Rate</label>
                                <span><?php echo $visit['pulse_rate'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Temperature</label>
                                <span><?php echo $visit['temperature'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>RR/SPO2</label>
                                <span><?php echo $visit['rr_spo2'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Alcohol Breathalyzer</label>
                                <span><?php echo $visit['alcohol_breathalyzer'] ?: 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Methadone/Buprenorphine Maintenance -->
                    <div class="section">
                        <div class="section-title">
                            <i class="fas fa-pills"></i> Methadone/Buprenorphine Maintenance
                        </div>
                        <div class="data-grid">
                            <div class="data-item">
                                <label>Current Dose</label>
                                <span><?php echo $visit['current_dose'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>New Dose</label>
                                <span><?php echo $visit['new_dose'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Days Missed</label>
                                <span><?php echo $visit['days_missed_doses'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>COWS Score</label>
                                <span><?php echo $visit['cows_score'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>Urine Drug Test</label>
                                <span><?php echo $visit['urine_drug_test'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="data-item">
                                <label>ECG Results</label>
                                <span><?php echo $visit['ecg_results'] ?: 'N/A'; ?></span>
                            </div>
                            <?php if ($visit['complaints']): ?>
                                <div class="data-item data-full">
                                    <label>Complaints</label>
                                    <span><?php echo nl2br(htmlspecialchars($visit['complaints'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($visit['signs_overdose']): ?>
                                <div class="data-item data-full">
                                    <label>Signs of Overdose</label>
                                    <span><?php echo nl2br(htmlspecialchars($visit['signs_overdose'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($visit['dose_adjustment_reason']): ?>
                                <div class="data-item data-full">
                                    <label>Reason for Dose Adjustment</label>
                                    <span><?php echo nl2br(htmlspecialchars($visit['dose_adjustment_reason'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Treatment for Side Effects -->
                    <?php if ($visit['side_effects_treatment']): ?>
                        <div class="section">
                            <div class="section-title">
                                <i class="fas fa-exclamation-triangle"></i> Treatment for Side Effects
                            </div>
                            <div class="data-item data-full">
                                <span><?php echo nl2br(htmlspecialchars($visit['side_effects_treatment'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Co-morbidities Management -->
                    <?php 
                    $has_comorbidity = $visit['mental_health_treatment'] || $visit['art_regimen'] || 
                                      $visit['viral_load_results'] || $visit['prep_pep'] || 
                                      $visit['tb_screening_treatment'] || $visit['hepatitis_b_regimen'] || 
                                      $visit['hepatitis_c_regimen'] || $visit['sti_treatment'] || 
                                      $visit['other_comorbidity'];
                    ?>
                    <?php if ($has_comorbidity): ?>
                        <div class="section">
                            <div class="section-title">
                                <i class="fas fa-procedures"></i> Management of Co-morbidities
                            </div>
                            <div class="data-grid">
                                <?php if ($visit['mental_health_treatment']): ?>
                                    <div class="data-item data-full">
                                        <label>Mental Health Treatment</label>
                                        <span><?php echo nl2br(htmlspecialchars($visit['mental_health_treatment'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['art_regimen']): ?>
                                    <div class="data-item">
                                        <label>ART Regimen</label>
                                        <span><?php echo htmlspecialchars($visit['art_regimen']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['viral_load_results']): ?>
                                    <div class="data-item">
                                        <label>Viral Load Results</label>
                                        <span><?php echo htmlspecialchars($visit['viral_load_results']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['prep_pep']): ?>
                                    <div class="data-item">
                                        <label>PrEP/PEP</label>
                                        <span><?php echo htmlspecialchars($visit['prep_pep']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['tb_screening_treatment']): ?>
                                    <div class="data-item">
                                        <label>TB Screening/Treatment</label>
                                        <span><?php echo htmlspecialchars($visit['tb_screening_treatment']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['hepatitis_b_regimen']): ?>
                                    <div class="data-item">
                                        <label>Hepatitis B Regimen</label>
                                        <span><?php echo htmlspecialchars($visit['hepatitis_b_regimen']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['hepatitis_c_regimen']): ?>
                                    <div class="data-item">
                                        <label>Hepatitis C Regimen</label>
                                        <span><?php echo htmlspecialchars($visit['hepatitis_c_regimen']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['sti_treatment']): ?>
                                    <div class="data-item">
                                        <label>STI Treatment</label>
                                        <span><?php echo htmlspecialchars($visit['sti_treatment']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['other_comorbidity']): ?>
                                    <div class="data-item data-full">
                                        <label>Other</label>
                                        <span><?php echo nl2br(htmlspecialchars($visit['other_comorbidity'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Reproductive Health -->
                    <?php 
                    $has_reproductive = $visit['pregnant'] || $visit['lmp'] || $visit['edd'] || 
                                       $visit['cervical_cancer_screening'] || $visit['on_fp'] || 
                                       $visit['fp_method'] || $visit['gbv_screening'];
                    ?>
                    <?php if ($has_reproductive): ?>
                        <div class="section">
                            <div class="section-title">
                                <i class="fas fa-venus"></i> Reproductive Health Services
                            </div>
                            <div class="data-grid">
                                <?php if ($visit['pregnant']): ?>
                                    <div class="data-item">
                                        <label>Pregnant</label>
                                        <span><?php echo htmlspecialchars($visit['pregnant']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['lmp']): ?>
                                    <div class="data-item">
                                        <label>LMP</label>
                                        <span><?php echo date('d M Y', strtotime($visit['lmp'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['edd']): ?>
                                    <div class="data-item">
                                        <label>EDD</label>
                                        <span><?php echo date('d M Y', strtotime($visit['edd'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['cervical_cancer_screening']): ?>
                                    <div class="data-item">
                                        <label>Cervical Cancer Screening</label>
                                        <span><?php echo htmlspecialchars($visit['cervical_cancer_screening']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['on_fp']): ?>
                                    <div class="data-item">
                                        <label>On Family Planning</label>
                                        <span><?php echo htmlspecialchars($visit['on_fp']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['fp_method']): ?>
                                    <div class="data-item">
                                        <label>FP Method</label>
                                        <span><?php echo htmlspecialchars($visit['fp_method']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['gbv_screening']): ?>
                                    <div class="data-item data-full">
                                        <label>GBV Screening</label>
                                        <span><?php echo nl2br(htmlspecialchars($visit['gbv_screening'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Referral and Linkages -->
                    <?php 
                    $has_referral = $visit['psychosocial_support'] || $visit['psychiatric_support'] || 
                                   $visit['nutritional_support'] || $visit['vaccination_service'] || 
                                   $visit['sexual_reproductive_health'] || $visit['radiology_service'] || 
                                   $visit['laboratory_service'] || $visit['legal_paralegal_services'] || 
                                   $visit['social_protection'] || $visit['gbv_services'] || 
                                   $visit['other_referrals'];
                    ?>
                    <?php if ($has_referral): ?>
                        <div class="section">
                            <div class="section-title">
                                <i class="fas fa-link"></i> Referral and Linkages
                            </div>
                            <div class="data-grid">
                                <?php if ($visit['psychosocial_support']): ?>
                                    <div class="data-item">
                                        <label>Psychosocial Support</label>
                                        <span><?php echo htmlspecialchars($visit['psychosocial_support']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['psychiatric_support']): ?>
                                    <div class="data-item">
                                        <label>Psychiatric Support</label>
                                        <span><?php echo htmlspecialchars($visit['psychiatric_support']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['nutritional_support']): ?>
                                    <div class="data-item">
                                        <label>Nutritional Support</label>
                                        <span><?php echo htmlspecialchars($visit['nutritional_support']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['vaccination_service']): ?>
                                    <div class="data-item">
                                        <label>Vaccination Service</label>
                                        <span><?php echo htmlspecialchars($visit['vaccination_service']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['sexual_reproductive_health']): ?>
                                    <div class="data-item">
                                        <label>Sexual & Reproductive Health</label>
                                        <span><?php echo htmlspecialchars($visit['sexual_reproductive_health']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['radiology_service']): ?>
                                    <div class="data-item">
                                        <label>Radiology Service</label>
                                        <span><?php echo htmlspecialchars($visit['radiology_service']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['laboratory_service']): ?>
                                    <div class="data-item">
                                        <label>Laboratory Service</label>
                                        <span><?php echo htmlspecialchars($visit['laboratory_service']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['legal_paralegal_services']): ?>
                                    <div class="data-item">
                                        <label>Legal/Paralegal Services</label>
                                        <span><?php echo htmlspecialchars($visit['legal_paralegal_services']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['social_protection']): ?>
                                    <div class="data-item">
                                        <label>Social Protection</label>
                                        <span><?php echo htmlspecialchars($visit['social_protection']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['gbv_services']): ?>
                                    <div class="data-item">
                                        <label>GBV Services</label>
                                        <span><?php echo htmlspecialchars($visit['gbv_services']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($visit['other_referrals']): ?>
                                    <div class="data-item data-full">
                                        <label>Other Referrals</label>
                                        <span><?php echo nl2br(htmlspecialchars($visit['other_referrals'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Clinician Information -->
                    <div class="section">
                        <div class="section-title">
                            <i class="fas fa-user-md"></i> Clinician Information
                        </div>
                        <div class="data-grid">
                            <div class="data-item">
                                <label>Clinician Name</label>
                                <span><?php echo htmlspecialchars($visit['clinician_name']); ?></span>
                            </div>
                            <div class="data-item">
                                <label>Signature</label>
                                <span><?php echo htmlspecialchars($visit['clinician_signature']); ?></span>
                            </div>
                            <div class="data-item">
                                <label>Date Recorded</label>
                                <span><?php echo date('d M Y H:i', strtotime($visit['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-visits">
                <i class="fas fa-clipboard"></i>
                <h2>No Clinical Visits Recorded</h2>
                <p>This patient has no recorded clinical follow-up visits yet.</p>
                <a href="yellow_card_form.php?mat_id=<?php echo urlencode($mat_id); ?>" class="btn btn-success" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Add First Visit
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
