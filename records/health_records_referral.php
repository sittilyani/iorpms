<?php
session_start();
include('../includes/config.php');

// Check if `mat_id` is passed in the URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['mat_id'])) {
    die("No MAT ID provided.");
}

$mat_id = $_GET['mat_id'] ?? null;

// Fetch patient details
if ($mat_id) {
    $sql = "SELECT mat_id, clientName, age, sex FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("No patient found with the provided MAT ID.");
    }

    $patient = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mat_id = $_POST['mat_id'];
    $clientName = $_POST['clientName'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $refer_from = $_POST['refer_from'];
    $refer_to = $_POST['refer_to'];
    $referral_notes = $_POST['referral_notes'];
    $referral_name = $_POST['referral_name'];
    $client_type = $_POST['client_type'];
    $triage_priority = $_POST['triage_priority'] ?? 'Normal';
    $vital_signs = $_POST['vital_signs'] ?? '';
    $chief_complaint = $_POST['chief_complaint'] ?? '';

    $insert_sql = "INSERT INTO referral (mat_id, clientName, age, sex, referral_notes, refer_to, refer_from, referral_name, client_type, triage_priority, vital_signs, chief_complaint)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param(
        "ssssssssssss",
        $mat_id,
        $clientName,
        $age,
        $sex,
        $referral_notes,
        $refer_to,
        $refer_from,
        $referral_name,
        $client_type,
        $triage_priority,
        $vital_signs,
        $chief_complaint
    );

    if ($insert_stmt->execute()) {
        echo "<script>
                alert('Client Triaged and Referred Successfully');
                setTimeout(function(){
                    window.history.go(-2);
                }, 2000);
            </script>";
        exit();
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$referral_name = 'Unknown';
$userQuery = "SELECT first_name, last_name, userrole, mobile FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $referral_name = $user['first_name'] . ' ' . $user['last_name'];
    $user_role = $user['userrole'];
    $user_mobile = $user['mobile'];
} else {
    $user_role = "Role not found";
    $user_mobile = "Mobile not found";
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIO Triage & Referral Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #0052CC;
            --primary-dark: #001D3D;
            --accent-teal: #00A8A8;
            --bg-soft: #F8FAFB;
            --bg-white: #FFFFFF;
            --text-primary: #1A2332;
            --text-secondary: #5A6C7D;
            --text-muted: #8B99A8;
            --border-light: #E4E9ED;
            --border-medium: #CBD5E0;
            --shadow-sm: 0 1px 3px rgba(0, 29, 61, 0.06);
            --shadow-md: 0 4px 16px rgba(0, 29, 61, 0.08);
            --shadow-lg: 0 10px 40px rgba(0, 29, 61, 0.12);
            --emergency-red: #DC2626;
            --urgent-amber: #F59E0B;
            --normal-green: #059669;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #F8FAFB 0%, #EDF2F7 100%);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 300px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-teal) 100%);
            opacity: 0.03;
            z-index: 0;
            pointer-events: none;
        }

        .main-content {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 50px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 50px;
            padding-bottom: 30px;
            border-bottom: 2px solid var(--border-light);
            position: relative;
        }

        .form-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-teal));
            border-radius: 2px;
        }

        .form-header h2 {
            font-family: 'Fraunces', Georgia, serif;
            color: var(--primary-dark);
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        .info-box {
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
            border: 1px solid #BAE6FD;
            border-radius: var(--radius-md);
            padding: 24px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-sm);
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.1s backwards;
        }

        .info-box p {
            margin: 8px 0;
            font-size: 0.95rem;
            color: var(--primary-dark);
            font-weight: 500;
        }

        .info-box strong {
            color: var(--primary-blue);
            font-weight: 600;
            margin-right: 8px;
        }

        .section-title {
            font-family: 'Fraunces', Georgia, serif;
            color: var(--primary-dark);
            font-size: 1.4rem;
            font-weight: 700;
            margin: 45px 0 25px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
            position: relative;
            letter-spacing: -0.01em;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--primary-blue);
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) backwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.05s; }
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.15s; }
        .form-group:nth-child(4) { animation-delay: 0.2s; }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
            letter-spacing: 0.01em;
        }

        .required::after {
            content: ' *';
            color: var(--emergency-red);
            font-weight: 700;
        }

        .form-control, select, textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-primary);
            background-color: var(--bg-white);
            transition: var(--transition-smooth);
            outline: none;
        }

        .form-control:hover, select:hover, textarea:hover {
            border-color: var(--border-medium);
        }

        .form-control:focus, select:focus, textarea:focus {
            border-color: var(--primary-blue);
            background-color: var(--bg-white);
            box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.08);
        }

        .form-control[readonly], input[readonly] {
            background-color: var(--bg-soft);
            cursor: not-allowed;
            border-color: var(--border-light);
            color: var(--text-muted);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
        }

        /* Client Type Options */
        .client-type-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .radio-option {
            position: relative;
        }

        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .radio-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px 20px;
            background: var(--bg-soft);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition-smooth);
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            color: var(--text-secondary);
            user-select: none;
        }

        .radio-option label:hover {
            border-color: var(--primary-blue);
            background: #F0F9FF;
            color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .radio-option input[type="radio"]:checked + label {
            background: linear-gradient(135deg, var(--primary-blue), #0066FF);
            color: white;
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        /* Priority Options */
        .priority-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .priority-option {
            position: relative;
        }

        .priority-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .priority-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px 24px;
            background: var(--bg-soft);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition-smooth);
            font-weight: 700;
            font-size: 1rem;
            text-align: center;
            color: var(--text-secondary);
            user-select: none;
        }

        .priority-option label:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .priority-option.emergency label:hover {
            border-color: var(--emergency-red);
            background: #FEF2F2;
            color: var(--emergency-red);
        }

        .priority-option.urgent label:hover {
            border-color: var(--urgent-amber);
            background: #FFFBEB;
            color: var(--urgent-amber);
        }

        .priority-option.normal label:hover {
            border-color: var(--normal-green);
            background: #F0FDF4;
            color: var(--normal-green);
        }

        .priority-option.emergency input[type="radio"]:checked + label {
            background: linear-gradient(135deg, var(--emergency-red), #EF4444);
            color: white;
            border-color: var(--emergency-red);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
            transform: translateY(-3px);
        }

        .priority-option.urgent input[type="radio"]:checked + label {
            background: linear-gradient(135deg, var(--urgent-amber), #FBBF24);
            color: white;
            border-color: var(--urgent-amber);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
            transform: translateY(-3px);
        }

        .priority-option.normal input[type="radio"]:checked + label {
            background: linear-gradient(135deg, var(--normal-green), #10B981);
            color: white;
            border-color: var(--normal-green);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.3);
            transform: translateY(-3px);
        }

        /* Select2 Styling */
        .select2-container {
            z-index: 9999 !important;
        }

        .select2-dropdown {
            z-index: 9999 !important;
            border: 2px solid var(--border-medium);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
        }

        .select2-container--open {
            z-index: 9999 !important;
        }

        .select2-container--default .select2-selection--single {
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            height: auto;
            padding: 12px 16px;
            background: var(--bg-white);
            transition: var(--transition-smooth);
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: var(--border-medium);
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.08);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-primary);
            line-height: 1.5;
            padding: 0;
            font-size: 0.95rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: var(--text-muted);
        }

        .select2-results__option {
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .select2-results__option--highlighted {
            background: var(--primary-blue) !important;
        }

        /* Submit Button */
        .custom-submit-btn {
            width: 100%;
            padding: 18px 36px;
            background: linear-gradient(135deg, var(--primary-blue), #0066FF);
            color: white;
            font-weight: 700;
            font-size: 1.05rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 4px 16px rgba(0, 82, 204, 0.25);
            letter-spacing: 0.02em;
            margin-top: 30px;
            font-family: inherit;
        }

        .custom-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 82, 204, 0.35);
            background: linear-gradient(135deg, #0066FF, var(--primary-blue));
        }

        .custom-submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }

            .main-content {
                padding: 30px 20px;
            }

            .form-header h2 {
                font-size: 1.75rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .client-type-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .priority-options {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 24px 16px;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }

            .client-type-options {
                grid-template-columns: 1fr;
            }

            .custom-submit-btn {
                font-size: 1rem;
                padding: 16px 28px;
            }
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="form-header">
        <h2>HRIO Triage & Referral Form</h2>
        <p>Health Records and Information Officer · Client Triage System</p>
    </div>

    <!-- Patient Information Display -->
    <div class="info-box">
        <p><strong>MAT ID:</strong> <?php echo htmlspecialchars($patient['mat_id']); ?></p>
        <p><strong>Client Name:</strong> <?php echo htmlspecialchars($patient['clientName']); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($patient['age']); ?> | <strong>Sex:</strong> <?php echo htmlspecialchars($patient['sex']); ?></p>
    </div>

    <form method="POST" action="" id="triageForm">
        <!-- Hidden fields for patient data -->
        <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($patient['mat_id']); ?>">
        <input type="hidden" name="clientName" value="<?php echo htmlspecialchars($patient['clientName']); ?>">
        <input type="hidden" name="age" value="<?php echo htmlspecialchars($patient['age']); ?>">
        <input type="hidden" name="sex" value="<?php echo htmlspecialchars($patient['sex']); ?>">

        <!-- Section 1: Client Type -->
        <div class="section-title">Client Type Classification</div>
        <div class="form-group">
            <label class="required">Select Client Type</label>
            <div class="client-type-options">
                <div class="radio-option">
                    <input type="radio" id="type_new" name="client_type" value="New" required>
                    <label for="type_new">New Client</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="type_reinduction" name="client_type" value="Reinduction">
                    <label for="type_reinduction">Reinduction</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="type_revisit" name="client_type" value="Revisit">
                    <label for="type_revisit">Revisit</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="type_transit" name="client_type" value="Transit">
                    <label for="type_transit">Transit</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="type_transfer" name="client_type" value="Transfer In">
                    <label for="type_transfer">Transfer In</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="type_returning" name="client_type" value="Returning">
                    <label for="type_returning">Returning</label>
                </div>
            </div>
        </div>

        <!-- Section 2: Triage Priority -->
        <div class="section-title">Triage Priority Level</div>
        <div class="form-group">
            <label class="required">Priority Assessment</label>
            <div class="priority-options">
                <div class="priority-option emergency">
                    <input type="radio" id="priority_emergency" name="triage_priority" value="Emergency">
                    <label for="priority_emergency">🚨 Emergency</label>
                </div>
                <div class="priority-option urgent">
                    <input type="radio" id="priority_urgent" name="triage_priority" value="Urgent">
                    <label for="priority_urgent">⚠️ Urgent</label>
                </div>
                <div class="priority-option normal">
                    <input type="radio" id="priority_normal" name="triage_priority" value="Normal" checked>
                    <label for="priority_normal">✓ Normal</label>
                </div>
            </div>
        </div>

        <!-- Section 3: Clinical Information -->
        <div class="section-title">Clinical Information</div>
        <div class="form-group">
            <label for="chief_complaint" class="required">Chief Complaint / Reason for Visit</label>
            <textarea id="chief_complaint" name="chief_complaint" class="form-control" rows="3" placeholder="Enter main reason for patient's visit..." required></textarea>
        </div>

        <div class="form-group">
            <label for="vital_signs">Vital Signs (Optional)</label>
            <textarea id="vital_signs" name="vital_signs" class="form-control" rows="3" placeholder="BP: ___ mmHg, Temp: ___ °C, Pulse: ___ bpm, RR: ___ /min, Weight: ___ kg, Height: ___ cm"></textarea>
        </div>

        <!-- Section 4: Referral Information -->
        <div class="section-title">Referral Details</div>
        <div class="form-grid">
            <div class="form-group">
                <label for="refer_from">Referring Department</label>
                <input type="text" id="refer_from" name="refer_from" class="form-control" readonly value="<?php echo htmlspecialchars($user_role); ?>">
            </div>

            <div class="form-group">
                <label for="mobile">Contact Number</label>
                <input type="text" id="mobile" name="mobile" class="form-control" readonly value="<?php echo htmlspecialchars($user_mobile); ?>">
            </div>

            <div class="form-group">
                <label for="referral_name">Referring Officer</label>
                <input type="text" name="referral_name" class="form-control" readonly value="<?php echo htmlspecialchars($referral_name); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="refer_to" class="required">Refer to Department/Service</label>
            <select id="refer_to" name="refer_to" class="form-control" required>
                <option value="">-- Select Department --</option>
                <?php
                // Fetch department/role names from the "userroles" table
                $statusQuery = "SELECT id, role FROM userroles ORDER BY role";
                $statusResult = $conn->query($statusQuery);

                if ($statusResult->num_rows > 0) {
                    while ($statusRow = $statusResult->fetch_assoc()) {
                        $statusName = htmlspecialchars($statusRow['role']);
                        echo "<option value='$statusName'>$statusName</option>";
                    }
                } else {
                    echo "<option value=''>No departments found</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="referral_notes" class="required">Referral Notes / Additional Information</label>
            <textarea id="referral_notes" name="referral_notes" class="form-control" rows="5" placeholder="Enter detailed referral notes, observations, or special instructions..." required></textarea>
        </div>

        <button type="submit" class="custom-submit-btn">Complete Triage & Submit Referral</button>
    </form>
</div>

<!-- jQuery and Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for department dropdown
    $('#refer_to').select2({
        placeholder: 'Search and select department...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('body')
    });

    // Prevent clicks inside Select2 from closing it
    $(document).on('click', '.select2-container', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', '.select2-dropdown', function(e) {
        e.stopPropagation();
    });

    // Form validation
    $('#triageForm').on('submit', function(e) {
        // Check if client type is selected
        if (!$('input[name="client_type"]:checked').val()) {
            e.preventDefault();
            alert('Please select a client type');
            return false;
        }

        // Check if priority is selected
        if (!$('input[name="triage_priority"]:checked').val()) {
            e.preventDefault();
            alert('Please select a triage priority level');
            return false;
        }

        // Check if department is selected
        if (!$('#refer_to').val()) {
            e.preventDefault();
            alert('Please select a department to refer to');
            $('#refer_to').select2('open');
            return false;
        }

        return true;
    });

    // Auto-suggest vital signs format
    $('#vital_signs').on('focus', function() {
        if ($(this).val() === '') {
            $(this).val('BP: ___ mmHg, Temp: ___ °C, Pulse: ___ bpm, RR: ___ /min, Weight: ___ kg, Height: ___ cm');
            // Select the first blank for easy editing
            this.setSelectionRange(4, 7);
        }
    });
});
</script>

</body>
</html>