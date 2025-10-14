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
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Request Form</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
        }
        .form-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid #e1e8ed;
        }
        .form-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 3px solid #2c3e50;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 10px;
        }
        .header-center {
            text-align: center;
        }
        .form-header h2 {
            color: #2c3e50;
            font-size: 28px;
            margin: 0;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .form-header h4 {
            color: #6633CC;
            font-size: 20px;
            margin: 8px 0;
            font-weight: 600;
        }
        .form-header p {
            color: #6c757d;
            font-size: 14px;
            text-align: right;
            margin: 0;
            font-weight: 500;
        }
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            margin: 25px 0;
            border-radius: 12px;
            border-left: 5px solid #3498db;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .form-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }
        .section-header {
            color: #2c3e50;
            font-size: 22px;
            margin: 0 0 25px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #3498db;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .form-group label {
            width: 280px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
            margin-right: 20px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            flex: 1;
            min-width: 300px;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #ffffff;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-1px);
        }
        .form-group select {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            cursor: pointer;
        }
        .readonly-input, .read-only {
            cursor: not-allowed;
            background: #FFCCFF !important;
            border-color: #dda0dd !important;
            color: #4b0082;
            font-weight: 500;
        }
        .required-field::after {
            content: " *";
            color: #e74c3c;
            font-weight: bold;
        }
        .submit-button {
            display: block;
            margin: 40px auto 0;
            padding: 15px 40px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .submit-button:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .toxicology-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .notes-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-left: 5px solid #9b59b6;
        }
        .profile-section {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8ff 100%);
            border-left: 5px solid #27ae60;
        }
        .routine-tests-section {
            background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%);
            border-left: 5px solid #e67e22;
        }
        .toxicology-section {
            background: linear-gradient(135deg, #ffebee 0%, #f3e5f5 100%);
            border-left: 5px solid #c0392b;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        @media (max-width: 768px) {
            .form-container {
                width: 95%;
                padding: 20px;
                margin: 10px auto;
            }
            .form-header {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
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
                margin-bottom: 8px;
            }
            .form-group input,
            .form-group select,
            .form-group textarea {
                width: 100%;
                min-width: unset;
            }
            .test-grid,
            .toxicology-grid {
                grid-template-columns: 1fr;
            }
            .form-section {
                padding: 20px;
                margin: 20px 0;
            }
            .section-header {
                font-size: 20px;
            }
        }
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .form-container {
                padding: 15px;
            }
            .form-header h2 {
                font-size: 24px;
            }
            .form-header h4 {
                font-size: 18px;
            }
            .section-header {
                font-size: 18px;
            }
        }
        .viral-load-group {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .viral-load-group select {
            flex: 1;
            min-width: 200px;
        }
        .viral-load-group input {
            flex: 2;
            min-width: 250px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo" style="filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.1));">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>LABORATORY REQUEST & RESULT TRACKER</h4>
            </div>
            <p>FORM 3Fb VER. Ver April. 2023</p>
        </div>

        <form action="submit_form3h.php" method="POST">
            <!-- Hidden field for patient ID -->
            <?php if (isset($_GET['p_id'])): ?>
                <input type="hidden" name="p_id" value="<?php echo htmlspecialchars($_GET['p_id']); ?>">
            <?php endif; ?>

            <!-- CLIENT INFORMATION SECTION -->
            <div class="form-section profile-section">
                <h3 class="section-header" style="color: #27ae60;">Client Information</h3>

                <div class="form-group">
                    <label for="visitDate" class="required-field">Date (dd/mm/yyyy):</label>
                    <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('d/m/Y'); ?>">
                </div>

                <div class="form-group">
                    <label for="client_name" class="required-field">Name of Client:</label>
                    <input type="text" id="client_name" name="client_name" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="mat_id" class="required-field">MAT ID:</label>
                    <input type="text" id="mat_id" name="mat_id" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="sex" class="required-field">Sex:</label>
                    <input type="text" id="sex" name="sex" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="cso">CSO:</label>
                    <input type="text" id="cso" name="cso" class="readonly-input" readonly
                           value="<?php echo isset($currentSettings['cso']) ? htmlspecialchars($currentSettings['cso']) : ''; ?>">
                </div>
            </div>

            <!-- PATIENT PROFILE SECTION -->
            <div class="form-section profile-section">
                <h3 class="section-header" style="color: #27ae60;">Patient Profile</h3>

                <div class="form-group">
                    <label for="visitDate_profile">Visit Date:</label>
                    <input type="text" name="visitDate_profile" class="read-only" readonly value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="mat_id_profile">MAT ID:</label>
                    <input type="text" name="mat_id_profile" class="read-only" readonly
                           value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="clientName">Client Name:</label>
                    <input type="text" name="clientName" class="read-only" readonly
                           value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="type_client">Visit Type:</label>
                    <select name="type_client">
                        <option value="new">New</option>
                        <option value="re_induction">Re-Induction</option>
                        <option value="routine">Routine</option>
                        <option value="followup">Follow Up</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mode_drug_use" class="required-field">Mode of Drug Use:</label>
                    <select name="mode_drug_use" required>
                        <option value="PWUD">PWUD</option>
                        <option value="PWID">PWID</option>
                    </select>
                </div>
            </div>

            <!-- ROUTINE TESTS SECTION -->
            <div class="form-section routine-tests-section">
                <h3 class="section-header" style="color: #e67e22;">Routine Tests</h3>

                <div class="test-grid">
                    <div class="form-group">
                        <label for="hiv_status">HIV Testing:</label>
                        <select name="hiv_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hbv_status">Hepatitis B:</label>
                        <select name="hbv_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hepc_status">Hepatitis C:</label>
                        <select name="hepc_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="malaria_status">Malaria (mRDTs):</label>
                        <select name="malaria_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="pregnancy_status">Pregnancy (hCG):</label>
                        <select name="pregnancy_status">
                            <option value="not_done">Not Done</option>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="vdrl_status">Syphilis (VDRL):</label>
                        <select name="vdrl_status">
                            <option value="not_done">Not Done</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="urinalysis_status">Urinalysis:</label>
                        <select name="urinalysis_status">
                            <option value="not_done">Not Done</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                        </select>
                    </div>

                    <div class="form-group viral-load-group">
                        <label for="viral_load">Viral Load:</label>
                        <select name="viral_load">
                            <option value="not_done">Not Done</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                        </select>
                        <input type="text" name="viral_load_results" placeholder="Viral Load Results">
                    </div>

                    <div class="form-group">
                        <label for="additional_tests">Additional Tests:</label>
                        <select name="additional_tests">
                            <option value="not_done">Not Done</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TOXICOLOGY SECTION -->
            <div class="form-section toxicology-section">
                <h3 class="section-header" style="color: #c0392b;">Toxicology</h3>

                <div class="toxicology-grid">
                    <div class="form-group">
                        <label for="amphetamine">Amphetamine (AMP):</label>
                        <select name="amphetamine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="metamphetamine">Metamphetamine (MET):</label>
                        <select name="metamphetamine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="morphine">Morphine (MOP):</label>
                        <select name="morphine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="barbiturates">Barbiturates (BAR):</label>
                        <select name="barbiturates">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cocaine">Cocaine (COC):</label>
                        <select name="cocaine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="codeine">Codeine (COD):</label>
                        <select name="codeine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="benzodiazepines">Benzodiazepines (BDZ):</label>
                        <select name="benzodiazepines">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marijuana">Marijuana (THC):</label>
                        <select name="marijuana">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amitriptyline">Amitriptyline (ATR):</label>
                        <select name="amitriptyline">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="opiates">Opiates (OPI):</label>
                        <select name="opiates">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="phencyclidine">Phencyclidine (PCP):</label>
                        <select name="phencyclidine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="methadone">Methadone (MTD):</label>
                        <select name="methadone">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="buprenorphine">Buprenorphine (BUP):</label>
                        <select name="buprenorphine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nicotine">Nicotine:</label>
                        <select name="nicotine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="Other Tricyclic Antidepressants">Tricyclic antidepressants (TCA):</label>
                        <select name="other_tca">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tramadol">Tramadol</label>
                        <select name="tramadol">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="Ketamine">Ketamine:</label>
                        <select name="ketamine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fentanyl">Fentanyl:</label>
                        <select name="fentanyl">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="oxycodone">Oxycodone:</label>
                        <select name="oxycodone">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="propoxyphene">Propoxyphene:</label>
                        <select name="buprenorphine">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ecstacy">Ecstacy (MDMA):</label>
                        <select name="ecstacy">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="other_drugs">Other Drugs:</label>
                        <select name="other_drugs">
                            <option value="no">Negative</option>
                            <option value="yes">Positive</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- NOTES & APPOINTMENTS SECTION -->
            <div class="form-section notes-section">
                <h3 class="section-header" style="color: #9b59b6;">Notes & Appointments</h3>

                <div class="form-group">
                    <label for="lab_notes" class="required-field">Lab Notes:</label>
                    <textarea name="lab_notes" id="lab_notes" rows="4" required placeholder="Enter laboratory observations and findings..."></textarea>
                </div>

                <div class="form-group">
                    <label for="date_of_test">Date of Test:</label>
                    <input type="date" name="date_of_test" id="date_of_test">
                </div>

                <div class="form-group">
                    <label for="next_appointment" class="required-field">Next Appointment Date:</label>
                    <input type="date" name="next_appointment" required>
                </div>

                <div class="form-group">
                    <label for="lab_officer_name">Dispensing Officer Name:</label>
                    <input type="text" name="lab_officer_name" value="<?php echo htmlspecialchars($lab_office_name); ?>" class="read-only" readonly>
                </div>

                <button type="submit" class="submit-button">
                    Submit Lab Request
                </button>
            </div>
        </form>
    </div>

    <script>
        // Set minimum date for appointment to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const appointmentField = document.querySelector('input[name="next_appointment"]');
            const testDateField = document.querySelector('input[name="date_of_test"]');

            if (appointmentField) {
                appointmentField.min = today;
            }
            if (testDateField) {
                testDateField.max = today;
            }
        });
    </script>
</body>
</html>