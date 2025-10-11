<?php
session_start();
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

// Fetch facility name from facility settings table
$facility_query = "SELECT facility_id, facilityname FROM facility_settings LIMIT 1";
$facility_result = mysqli_query($conn, $facility_query);
$facility = mysqli_fetch_assoc($facility_result);

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$clinician_name = 'Unknown';
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
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Clinic Initial Encounter Form</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; line-height: 1.6; min-height: 100vh; }
        .form-container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 30px; background: #ffffff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border: 1px solid #e1e8ed; }
        .form-header { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 25px; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 3px solid #2c3e50; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; }
        .header-center { text-align: center; }
        .form-header h2 { color: #2c3e50; font-size: 28px; margin: 0; font-weight: 700; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .form-header h4 { color: #6633CC; font-size: 20px; margin: 8px 0; font-weight: 600; }
        .form-header p { color: #6c757d; font-size: 14px; text-align: right; margin: 0; font-weight: 500; }
        .form-section { background: #f8f9fa; padding: 25px; margin: 25px 0; border-radius: 12px; border-left: 5px solid #3498db; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .form-section:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12); }
        .section-header { color: #2c3e50; font-size: 22px; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #3498db; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group { display: flex; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .form-group label { width: 280px; font-weight: 600; color: #2c3e50; font-size: 15px; margin-right: 20px; }
        .form-group input, .form-group select, .form-group textarea { flex: 1; min-width: 300px; padding: 12px 15px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: #ffffff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); transform: translateY(-1px); }
        .form-group select { background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); cursor: pointer; }
        .readonly-input, .read-only { cursor: not-allowed; background: #FFCCFF !important; border-color: #dda0dd !important; color: #4b0082; font-weight: 500; }
        .required-field::after { content: " *"; color: #e74c3c; font-weight: bold; }
        .submit-button { display: block; margin: 40px auto 0; padding: 15px 40px; background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; border: none; border-radius: 8px; font-size: 17px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3); text-transform: uppercase; letter-spacing: 0.5px; }
        .submit-button:hover { background: linear-gradient(135deg, #2980b9, #1f618d); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4); }
        .drug-history-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .drug-history-table th, .drug-history-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .drug-history-table th { background-color: #f2f2f2; font-weight: bold; }
        .checkbox-group { display: flex; gap: 15px; flex-wrap: wrap; }
        .checkbox-group label { width: auto; display: flex; align-items: center; gap: 5px; }
        .checkbox-group input[type="checkbox"] { width: auto; min-width: auto; }
        .cows-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .cows-table th, .cows-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .cows-table th { background-color: #f2f2f2; font-weight: bold; }
        .cows-table .symptom { text-align: left; }
        .profile-section { background: linear-gradient(135deg, #e8f5e8 0%, #f0f8ff 100%); border-left: 5px solid #27ae60; }
        .clinical-section { background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border-left: 5px solid #9b59b6; }
        .history-section { background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%); border-left: 5px solid #e67e22; }
        .examination-section { background: linear-gradient(135deg, #ffebee 0%, #f3e5f5 100%); border-left: 5px solid #c0392b; }
        .treatment-section { background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-left: 5px solid #43a047; }
        @media (max-width: 768px) {
            .form-container { width: 95%; padding: 20px; margin: 10px auto; }
            .form-header { grid-template-columns: 1fr; text-align: center; gap: 15px; }
            .form-header p { text-align: center; }
            .form-group { flex-direction: column; align-items: flex-start; }
            .form-group label { width: 100%; margin-bottom: 8px; }
            .form-group input, .form-group select, .form-group textarea { width: 100%; min-width: unset; }
            .drug-history-table, .cows-table { font-size: 12px; }
        }
        @media (max-width: 480px) {
            body { padding: 10px; }
            .form-container { padding: 15px; }
            .form-header h2 { font-size: 24px; }
            .form-header h4 { font-size: 18px; }
            .section-header { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo" style="filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.1));">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>CLINICAL ENCOUNTER FORM</h4>
            </div>
            <p>VER.APRIL 2023 FORM 3A</p>
        </div>

        <form action="submit_form3a.php" method="POST">
            <!-- Hidden field for patient ID -->
            <?php if (isset($_GET['p_id'])): ?>
                <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($_GET['p_id']); ?>">
            <?php endif; ?>

            <!-- FACILITY INFORMATION SECTION -->
            <div class="form-section profile-section">
                <h3 class="section-header" style="color: #27ae60;">Facility Information</h3>

                <div class="form-group">
                    <label for="facility_name" class="required-field">Facility Name:</label>
                    <input type="text" name="facility_name" class="readonly-input" readonly value="<?php echo $facility ? htmlspecialchars($facility['facilityname']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="mfl_code" class="required-field">MFL Code:</label>
                    <input type="text" name="mfl_code" class="readonly-input" readonly value="<?php echo $currentSettings ? htmlspecialchars($currentSettings['mflcode']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="county">County:</label>
                    <input type="text" name="county" class="read-only" readonly value="<?php echo isset($currentSettings['county']) ? htmlspecialchars($currentSettings['county']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="sub_county">Sub County:</label>
                    <input type="text" name="sub_county" class="read-only" readonly value="<?php echo isset($currentSettings['scounty']) ? htmlspecialchars($currentSettings['scounty']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="enrolment_date" class="required-field">Enrolment Date (dd/mm/yyyy):</label>
                    <input type="text" name="enrolment_date" class="readonly-input" readonly value="<?php echo isset($currentSettings['reg_date']) ? htmlspecialchars($currentSettings['reg_date']) : date('d/m/Y'); ?>">
                </div>

                <div class="form-group">
                    <label for="enrolment_time">Time:</label>
                    <input type="time" name="enrolment_time" value="<?php echo date('H:i'); ?>">
                </div>

                <div class="form-group">
                    <label for="visit_type">Visit Type:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="visit_type[]" value="induction"> INDUCTION</label>
                        <label><input type="checkbox" name="visit_type[]" value="reinduction"> REINDUCTION</label>
                    </div>
                </div>
            </div>

            <!-- PART A: CLIENT PROFILE SECTION -->
            <div class="form-section profile-section">
                <h3 class="section-header" style="color: #27ae60;">PART A: CLIENT PROFILE</h3>

                <div class="form-group">
                    <label for="client_name" class="required-field">Client Name:</label>
                    <input type="text" id="client_name" name="client_name" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="nickname">Nickname:</label>
                    <input type="text" id="nickname" name="nickname">
                </div>

                <div class="form-group">
                    <label for="mat_id" class="required-field">MAT/Unique ID Number:</label>
                    <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="sex" class="required-field">Sex:</label>
                    <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly
    value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="presenting_complaints">Presenting Complaints:</label>
                    <textarea name="presenting_complaints" id="presenting_complaints" cols="30" rows="3"></textarea>
                </div>

                <!-- DRUG USE HISTORY -->
                <h4>DRUG USE HISTORY</h4>
                <table class="drug-history-table">
                    <thead>
                        <tr>
                            <th>Type of Drug</th>
                            <th>Age first use drug</th>
                            <th>Duration of use (years)</th>
                            <th>Frequency of use in last 30 days</th>
                            <th>Quantity used regularly</th>
                            <th>Usual route of administration</th>
                            <th>Date & time last used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $drugs = array(
                            'a' => 'Heroin',
                            'b' => 'Cannabis Sativa',
                            'c' => 'Tobacco',
                            'd' => 'Benzodiazepines',
                            'e' => 'Alcohol',
                            'f' => 'Amphetamine',
                            'g' => 'Cocaine',
                            'h' => 'Miraa',
                            'i' => 'Glue',
                            'j' => 'Barbiturates',
                            'k' => 'Phencyclidine',
                            'l' => 'Other'
                        );

                        foreach($drugs as $key => $drug) {
                            echo "<tr>";
                            echo "<td>$drug</td>";
                            echo "<td><input type='number' name='drug_age_first_use[$key]' min='0' max='100'></td>";
                            echo "<td><input type='number' name='drug_duration[$key]' min='0' max='100'></td>";
                            echo "<td>
                                <select name='drug_frequency[$key]'>
                                    <option value=''>Select</option>
                                    <option value='never'>Never</option>
                                    <option value='once_twice'>Once or Twice</option>
                                    <option value='weekly'>Weekly</option>
                                    <option value='almost_daily'>Almost Daily</option>
                                    <option value='daily'>Daily</option>
                                </select>
                            </td>";
                            echo "<td><input type='text' name='drug_quantity[$key]'></td>";
                            echo "<td>
                                <select name='drug_route[$key]'>
                                    <option value=''>Select</option>
                                    <option value='oral'>Oral</option>
                                    <option value='nasal'>Nasal</option>
                                    <option value='smoking'>Smoking</option>
                                    <option value='injection'>Injection</option>
                                </select>
                            </td>";
                            echo "<td><input type='datetime-local' name='drug_last_used[$key]'></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- HEROIN INJECTING DRUG USE HISTORY -->
                <h4>HEROIN INJECTING DRUG USE HISTORY</h4>

                <div class="form-group">
                    <label for="injecting_history">History of injecting drug use:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="injecting_history" value="yes"> Yes</label>
                        <label><input type="radio" name="injecting_history" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reasons_injecting">Reasons for shifting to injecting drug use:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="reasons_injecting[]" value="peer_pressure"> Peer Pressure</label>
                        <label><input type="checkbox" name="reasons_injecting[]" value="feel_high"> Feel High</label>
                        <label><input type="checkbox" name="reasons_injecting[]" value="financial"> Financial</label>
                        <label><input type="checkbox" name="reasons_injecting[]" value="other"> Other (Specify): <input type="text" name="reasons_injecting_other"></label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="flash_blood">Ever injected yourself with blood of someone who just injected drugs (blood sharing practice known as 'flash blood'):</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="flash_blood" value="yes"> Yes</label>
                        <label><input type="radio" name="flash_blood" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shared_needles">Have you ever shared needles and syringes or other injecting equipment?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="shared_needles" value="yes"> Yes</label>
                        <label><input type="radio" name="shared_needles" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="injecting_complications">Ever had any complications of injecting (abscesses, wound/ulcer, blocked veins, gangrene)?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="injecting_complications" value="yes"> Yes</label>
                        <label><input type="radio" name="injecting_complications" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="drug_overdose">Ever experienced any incidents of drug overdose?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="drug_overdose" value="yes"> Yes</label>
                        <label><input type="radio" name="drug_overdose" value="no"> No</label>
                    </div>
                </div>
            </div>

            <!-- VITAL SIGNS SECTION -->
            <div class="form-section clinical-section">
                <h3 class="section-header" style="color: #9b59b6;">VITAL SIGNS</h3>

                <div class="form-group">
                    <label for="pulse">a. Pulse:</label>
                    <input type="number" name="pulse" min="0" max="200"> bpm
                </div>

                <div class="form-group">
                    <label for="oxygen_saturation">b. Oxygen saturation:</label>
                    <input type="number" name="oxygen_saturation" min="0" max="100"> %
                </div>

                <div class="form-group">
                    <label for="blood_pressure">c. Blood pressure:</label>
                    <input type="text" name="blood_pressure" placeholder="e.g., 120/80"> mmHg
                </div>

                <div class="form-group">
                    <label for="temperature">d. Temperature:</label>
                    <input type="number" name="temperature" step="0.1" min="30" max="45"> �C
                </div>

                <div class="form-group">
                    <label for="respiratory_rate">e. Respiratory rate:</label>
                    <input type="number" name="respiratory_rate" min="0" max="60"> breaths/min
                </div>

                <div class="form-group">
                    <label for="height">f. Height:</label>
                    <input type="number" name="height" step="0.1" min="0" max="250"> cm
                </div>

                <div class="form-group">
                    <label for="weight">g. Weight:</label>
                    <input type="number" name="weight" step="0.1" min="0" max="300"> kg
                </div>

                <div class="form-group">
                    <label for="bmi">h. BMI:</label>
                    <input type="number" name="bmi" step="0.1" min="0" max="100">
                    <select name="bmi_interpretation">
                        <option value="">Select interpretation</option>
                        <option value="underweight">Underweight (<18.5)</option>
                        <option value="normal">Normal (18.5-24.9)</option>
                        <option value="overweight">Overweight (25-29.9)</option>
                        <option value="obesity">Obesity (>30)</option>
                    </select>
                </div>
            </div>

            <!-- CLINICAL OPIATE WITHDRAWAL SCALE (COWS) -->
            <div class="form-section clinical-section">
                <h3 class="section-header" style="color: #9b59b6;">CLINICAL OPIATE WITHDRAWAL SCALE (COWS)</h3>

                <table class="cows-table">
                    <thead>
                        <tr>
                            <th>Symptom</th>
                            <th>Score</th>
                            <th>Time 1</th>
                            <th>Time 2</th>
                            <th>Time 3</th>
                            <th>Time 4</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cows_symptoms = array(
                            'Resting Pulse Rate' => array(
                                '0' => 'pulse rate 80 or below',
                                '1' => 'pulse rate 81-100',
                                '2' => 'pulse rate 101-120',
                                '4' => 'pulse rate greater than 120'
                            ),
                            'Sweating' => array(
                                '1' => 'no report of chills or flushing',
                                '2' => 'subjective report of chills or flushing',
                                '3' => 'flushed or observable moistness on face',
                                '4' => 'beads of sweat on brow or face',
                                '5' => 'sweat streaming off face'
                            ),
                            'Restlessness' => array(
                                '0' => 'able to sit still',
                                '1' => 'reports difficulty sitting still, but is able to do so',
                                '3' => 'frequent shifting or extraneous movements of legs/arms',
                                '5' => 'Unable to sit still for more than a few seconds'
                            ),
                            'Pupil size' => array(
                                '0' => 'pupils pinned or normal size for room light',
                                '1' => 'pupils possibly larger than normal for room light',
                                '2' => 'pupils moderately dilated',
                                '5' => 'pupils so dilated that only the rim of the iris is visible'
                            ),
                            'Bone or Joint aches' => array(
                                '0' => 'not present',
                                '1' => 'mild diffuse discomfort',
                                '2' => 'patient reports severe diffuse aching of joints/muscles',
                                '4' => 'patient is rubbing joints or muscles and is unable to sit still because of discomfort'
                            ),
                            'Runny nose or tearing' => array(
                                '0' => 'not present',
                                '1' => 'nasal stuffiness or unusually moist eyes',
                                '2' => 'nose running or tearing',
                                '4' => 'nose constantly running or tears streaming down cheeks'
                            ),
                            'GI Upset' => array(
                                '0' => 'no GI symptoms',
                                '1' => 'stomach cramps',
                                '2' => 'nausea or loose stool',
                                '3' => 'vomiting or diarrhea',
                                '5' => 'Multiple episodes of diarrhea or vomiting'
                            ),
                            'Tremor' => array(
                                '0' => 'No tremor',
                                '1' => 'tremor can be felt, but not observed',
                                '2' => 'slight tremor observable',
                                '4' => 'gross tremor or muscle twitching'
                            ),
                            'Yawning' => array(
                                '0' => 'no yawning',
                                '1' => 'yawning once or twice during assessment',
                                '2' => 'yawning three or more times during assessment',
                                '4' => 'yawning several times/minute'
                            ),
                            'Anxiety or Irritability' => array(
                                '0' => 'none',
                                '1' => 'patient reports increasing irritability or anxiousness',
                                '2' => 'patient obviously irritable anxious',
                                '4' => 'patient so irritable or anxious that participation in the assessment is difficult'
                            ),
                            'Gooseflesh skin' => array(
                                '0' => 'skin is smooth',
                                '3' => 'piloerrection of skin can be felt or hairs standing up on arms',
                                '5' => 'prominent piloerrection'
                            )
                        );

                        foreach($cows_symptoms as $symptom => $scores) {
                            echo "<tr>";
                            echo "<td class='symptom'>$symptom</td>";
                            echo "<td>";
                            foreach($scores as $score => $description) {
                                echo "<div>$score: $description</div>";
                            }
                            echo "</td>";
                            for($i = 1; $i <= 4; $i++) {
                                echo "<td><input type='number' name='cows_{$symptom}_time{$i}' min='0' max='5' style='width: 50px;'></td>";
                            }
                            echo "</tr>";
                        }
                        ?>
                        <tr>
                            <td colspan="2"><strong>Total scores</strong></td>
                            <?php for($i = 1; $i <= 4; $i++): ?>
                            <td><input type="number" name="cows_total_time<?php echo $i; ?>" readonly style="width: 50px; background-color: #f0f0f0;"></td>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Scale Interpretation</strong></td>
                            <?php for($i = 1; $i <= 4; $i++): ?>
                            <td>
                                <select name="cows_interpretation_time<?php echo $i; ?>" style="width: 100%;">
                                    <option value="">Select</option>
                                    <option value="mild">Mild (5-12)</option>
                                    <option value="moderate">Moderate (13-24)</option>
                                    <option value="moderately_severe">Moderately Severe (25-36)</option>
                                    <option value="severe">Severe (>36)</option>
                                </select>
                            </td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>

                <div class="form-group">
                    <label for="cows_provider">Name of Service Provider:</label>
                    <input type="text" name="cows_provider" value="<?php echo htmlspecialchars($clinician_name); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="cows_date">Date:</label>
                    <input type="date" name="cows_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <!-- PART B: CLINICAL ASSESSMENT -->
            <div class="form-section clinical-section">
                <h3 class="section-header" style="color: #9b59b6;">PART B: CLINICAL ASSESSMENT</h3>

                <!-- PERSONAL MEDICAL HISTORY -->
                <h4>10. PERSONAL MEDICAL HISTORY</h4>
                <table class="drug-history-table">
                    <thead>
                        <tr>
                            <th>Have you ever been diagnosed with any of the following illnesses?</th>
                            <th>Yes/No</th>
                            <th>If so, current medication and dose for illness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $medical_conditions = array(
                            'a' => 'Asthma',
                            'b' => 'Heart disease',
                            'c' => 'Tuberculosis',
                            'd' => 'Liver disease',
                            'e' => 'STDs (syphilis, chlamydia, gonorrhoea, etc.)',
                            'f' => 'Accidents or surgery',
                            'g' => 'HIV',
                            'h' => 'Hypertension',
                            'i' => 'Hepatitis B',
                            'j' => 'Hepatitis C',
                            'k' => 'Diabetes'
                        );

                        foreach($medical_conditions as $key => $condition) {
                            echo "<tr>";
                            echo "<td>$condition</td>";
                            echo "<td>
                                <div class='checkbox-group'>
                                    <label><input type='radio' name='medical_history[$key]' value='yes'> Yes</label>
                                    <label><input type='radio' name='medical_history[$key]' value='no'> No</label>
                                </div>
                            </td>";
                            echo "<td><input type='text' name='medical_medication[$key]'></td>";
                            echo "</tr>";
                        }
                        ?>
                        <tr>
                            <td>HIV Date of Diagnosis:</td>
                            <td colspan="2"><input type="date" name="hiv_diagnosis_date"></td>
                        </tr>
                        <tr>
                            <td>HIV Facility of Care:</td>
                            <td colspan="2"><input type="text" name="hiv_facility_care"></td>
                        </tr>
                        <tr>
                            <td>m. Any other medical problems or medications:</td>
                            <td colspan="2"><textarea name="other_medical_problems" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td>n. Do you have any allergies?</td>
                            <td colspan="2">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="allergies[]" value="sulphur"> Sulphur</label>
                                    <label><input type="checkbox" name="allergies[]" value="penicillin"> Penicillin</label>
                                    <label><input type="checkbox" name="allergies[]" value="none"> None</label>
                                    <label><input type="checkbox" name="allergies[]" value="other"> Other (Specify): <input type="text" name="allergies_other"></label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- REPRODUCTIVE HEALTH HISTORY -->
                <h4>11. REPRODUCTIVE HEALTH HISTORY</h4>

                <div class="form-group">
                    <label for="contraception_use">a. Are you using any contraception?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="contraception_use" value="yes"> Yes</label>
                        <label><input type="radio" name="contraception_use" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contraception_method">b. If YES, which method are you using? (Mark all responses mentioned)</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $contraception_methods = array(
                            'male_condom' => 'Male condom',
                            'female_condom' => 'Female condom',
                            'injectables' => 'Injectables',
                            'iud' => 'IUD',
                            'implants' => 'Implants',
                            'lactational_amenorrhea' => 'Lactational Amenorrhea',
                            'foam_jelly' => 'Foam/jelly',
                            'withdrawal' => 'Withdrawal',
                            'rhythm_method' => 'Rhythm method',
                            'abstain' => 'Abstain',
                            'emergency_contraception' => 'Emergency contraception',
                            'female_sterilization' => 'Female sterilization',
                            'male_sterilization' => 'Male sterilization',
                            'pill' => 'Pill',
                            'none' => 'None'
                        );

                        foreach($contraception_methods as $key => $method) {
                            echo "<label><input type='checkbox' name='contraception_method[]' value='$key'> $method</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_menstrual_period">c. Date of Last Menstrual Period (LMP):</label>
                    <input type="date" name="last_menstrual_period">
                </div>

                <div class="form-group">
                    <label for="pregnancy_status">d. Pregnancy status:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="pregnancy_status" value="pregnant"> Pregnant</label>
                        <label><input type="radio" name="pregnancy_status" value="not_pregnant"> Not Pregnant</label>
                        <label><input type="radio" name="pregnancy_status" value="unknown"> Unknown</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pregnancy_weeks">e. If pregnant, how many weeks?</label>
                    <input type="number" name="pregnancy_weeks" min="0" max="50"> weeks
                </div>

                <div class="form-group">
                    <label for="breastfeeding">f. Are you breastfeeding?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="breastfeeding" value="yes"> Yes</label>
                        <label><input type="radio" name="breastfeeding" value="no"> No</label>
                    </div>
                </div>

                <!-- MENTAL HEALTH HISTORY -->
                <h4>12. MENTAL HEALTH HISTORY</h4>

                <div class="form-group">
                    <label for="mental_health_diagnosis">a. Have you ever been diagnosed with a mental health condition?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="mental_health_diagnosis" value="yes"> Yes</label>
                        <label><input type="radio" name="mental_health_diagnosis" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_condition">b. If YES, which condition?</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $mental_health_conditions = array(
                            'depression' => 'Depression',
                            'anxiety' => 'Anxiety',
                            'bipolar' => 'Bipolar',
                            'schizophrenia' => 'Schizophrenia',
                            'ptsd' => 'PTSD',
                            'other' => 'Other (Specify): <input type="text" name="mental_health_other">'
                        );

                        foreach($mental_health_conditions as $key => $condition) {
                            echo "<label><input type='checkbox' name='mental_health_condition[]' value='$key'> $condition</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_medication">c. Are you currently taking any medication for mental health?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="mental_health_medication" value="yes"> Yes</label>
                        <label><input type="radio" name="mental_health_medication" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_medication_details">d. If YES, please specify medication and dose:</label>
                    <textarea name="mental_health_medication_details" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="suicidal_thoughts">e. Have you had any thoughts of harming yourself or ending your life in the past month?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="suicidal_thoughts" value="yes"> Yes</label>
                        <label><input type="radio" name="suicidal_thoughts" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="psychiatric_hospitalization">f. Have you ever been hospitalized for psychiatric reasons?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="psychiatric_hospitalization" value="yes"> Yes</label>
                        <label><input type="radio" name="psychiatric_hospitalization" value="no"> No</label>
                    </div>
                </div>

                <!-- FAMILY HISTORY -->
                <h4>13. FAMILY HISTORY</h4>

                <div class="form-group">
                    <label for="family_drug_use">a. Is there any history of drug use in your family?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="family_drug_use" value="yes"> Yes</label>
                        <label><input type="radio" name="family_drug_use" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="family_mental_health">b. Is there any history of mental health conditions in your family?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="family_mental_health" value="yes"> Yes</label>
                        <label><input type="radio" name="family_mental_health" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="family_medical_conditions">c. Is there any history of the following medical conditions in your family?</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $family_medical_conditions = array(
                            'diabetes' => 'Diabetes',
                            'hypertension' => 'Hypertension',
                            'heart_disease' => 'Heart Disease',
                            'cancer' => 'Cancer',
                            'other' => 'Other (Specify): <input type="text" name="family_medical_other">'
                        );

                        foreach($family_medical_conditions as $key => $condition) {
                            echo "<label><input type='checkbox' name='family_medical_conditions[]' value='$key'> $condition</label>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- PHYSICAL EXAMINATION SECTION -->
            <div class="form-section examination-section">
                <h3 class="section-header" style="color: #c0392b;">14. PHYSICAL EXAMINATION</h3>

                <div class="form-group">
                    <label for="general_appearance">a. General Appearance:</label>
                    <textarea name="general_appearance" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="skin_examination">b. Skin:</label>
                    <textarea name="skin_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="head_examination">c. Head:</label>
                    <textarea name="head_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="eyes_examination">d. Eyes:</label>
                    <textarea name="eyes_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="ears_examination">e. Ears:</label>
                    <textarea name="ears_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="nose_examination">f. Nose:</label>
                    <textarea name="nose_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="mouth_throat_examination">g. Mouth and Throat:</label>
                    <textarea name="mouth_throat_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="neck_examination">h. Neck:</label>
                    <textarea name="neck_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="chest_examination">i. Chest:</label>
                    <textarea name="chest_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="heart_examination">j. Heart:</label>
                    <textarea name="heart_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="abdomen_examination">k. Abdomen:</label>
                    <textarea name="abdomen_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="genitalia_examination">l. Genitalia:</label>
                    <textarea name="genitalia_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="extremities_examination">m. Extremities:</label>
                    <textarea name="extremities_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="neurological_examination">n. Neurological:</label>
                    <textarea name="neurological_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="musculoskeletal_examination">o. Musculoskeletal:</label>
                    <textarea name="musculoskeletal_examination" rows="3"></textarea>
                </div>
            </div>

            <!-- DIAGNOSIS AND TREATMENT PLAN -->
            <div class="form-section treatment-section">
                <h3 class="section-header" style="color: #43a047;">15. DIAGNOSIS AND TREATMENT PLAN</h3>

                <div class="form-group">
                    <label for="diagnosis_opioid_use">a. Diagnosis of Opioid Use Disorder:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="diagnosis_opioid_use" value="mild"> Mild</label>
                        <label><input type="radio" name="diagnosis_opioid_use" value="moderate"> Moderate</label>
                        <label><input type="radio" name="diagnosis_opioid_use" value="severe"> Severe</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="other_diagnoses">b. Other Diagnoses:</label>
                    <textarea name="other_diagnoses" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="treatment_plan">c. Treatment Plan:</label>
                    <textarea name="treatment_plan" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="medication_prescribed">d. Medication Prescribed:</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $medications = array(
                            'methadone' => 'Methadone',
                            'buprenorphine' => 'Buprenorphine',
                            'naltrexone' => 'Naltrexone',
                            'other' => 'Other (Specify): <input type="text" name="medication_other">'
                        );

                        foreach($medications as $key => $medication) {
                            echo "<label><input type='checkbox' name='medication_prescribed[]' value='$key'> $medication</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="initial_dose">e. Initial Dose:</label>
                    <input type="text" name="initial_dose">
                </div>

                <div class="form-group">
                    <label for="next_appointment" class="required-field">f. Next Appointment Date:</label>
                    <input type="date" name="next_appointment" required>
                </div>

                <div class="form-group">
                    <label for="clinician_name">g. Clinician Name:</label>
                    <input type="text" name="clinician_name" value="<?php echo htmlspecialchars($clinician_name); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="clinician_signature">h. Clinician Signature:</label>
                    <input type="text" name="clinician_signature" value="<?php echo htmlspecialchars($clinician_name); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="patient_consent">i. Patient Consent for Treatment:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="patient_consent" value="yes" required> I have read and understood the information provided and consent to treatment</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-button">
                Submit Initial Clinical Encounter Form
            </button>
        </form>
    </div>

    <script>
        // Set minimum date for appointment to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const appointmentField = document.querySelector('input[name="next_appointment"]');

            if (appointmentField) {
                appointmentField.min = today;
            }

            // Calculate BMI when height and weight are entered
            const heightField = document.querySelector('input[name="height"]');
            const weightField = document.querySelector('input[name="weight"]');
            const bmiField = document.querySelector('input[name="bmi"]');

            function calculateBMI() {
                if (heightField.value && weightField.value) {
                    const heightInMeters = heightField.value / 100;
                    const bmi = (weightField.value / (heightInMeters * heightInMeters)).toFixed(1);
                    bmiField.value = bmi;
                }
            }

            if (heightField && weightField && bmiField) {
                heightField.addEventListener('input', calculateBMI);
                weightField.addEventListener('input', calculateBMI);
            }

            // Calculate COWS total scores
            const cowsTables = document.querySelectorAll('.cows-table');
            cowsTables.forEach(table => {
                for (let i = 1; i <= 4; i++) {
                    const timeInputs = table.querySelectorAll(`input[name$="_time${i}"]`);
                    const totalField = table.querySelector(`input[name="cows_total_time${i}"]`);

                    if (timeInputs && totalField) {
                        timeInputs.forEach(input => {
                            input.addEventListener('input', function() {
                                let total = 0;
                                timeInputs.forEach(input => {
                                    if (input.value) {
                                        total += parseInt(input.value);
                                    }
                                });
                                totalField.value = total;
                            });
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>