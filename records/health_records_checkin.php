<?php
// health_records_checkin.php
session_start();
include "../includes/config.php";

// Check if user is logged in and is health records personnel
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Health Records Personnel';

// Initialize variables
$patients = [];
$selected_patient = null;
$success_msg = '';
$error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_patient'])) {
        // Handle patient search
        $search_term = trim($_POST['search_term'] ?? '');

        if (!empty($search_term)) {
            $search_like = "%$search_term%";

            // Search patients by MAT ID or name
            $sql = "SELECT * FROM patients
                   WHERE mat_id LIKE ? OR clientName LIKE ? OR sname LIKE ?
                   ORDER BY clientName LIMIT 50";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $search_like, $search_like, $search_like);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['submit_checkin'])) {
        // Handle patient check-in/referral
        $mat_id = $_POST['mat_id'] ?? '';
        $visit_type = $_POST['visit_type'] ?? '';
        $refer_to = $_POST['refer_to'] ?? '';
        $referral_notes = mysqli_real_escape_string($conn, $_POST['referral_notes'] ?? '');
        $checkin_notes = mysqli_real_escape_string($conn, $_POST['checkin_notes'] ?? '');

        // Get patient details
        $patient_sql = "SELECT * FROM patients WHERE mat_id = ?";
        $stmt = $conn->prepare($patient_sql);
        $stmt->bind_param('s', $mat_id);
        $stmt->execute();
        $patient_result = $stmt->get_result();

        if ($patient_result->num_rows > 0) {
            $patient = $patient_result->fetch_assoc();
            $stmt->close();

            // Insert into referral table if referring to another department
            if (!empty($refer_to)) {
                $refer_from = 'Health Records';
                $referral_date = date('Y-m-d H:i:s');
                $status = 'pending';

                $referral_sql = "INSERT INTO referral (
                    mat_id, clientName, age, sex, refer_from, refer_to,
                    referral_notes, referral_name, referral_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($referral_sql);
                $client_name = $patient['clientName'] . ' ' . $patient['sname'];
                $stmt->bind_param(
                    'ssisssssss',
                    $mat_id, $client_name, $patient['age'], $patient['sex'],
                    $refer_from, $refer_to, $referral_notes,
                    $user_name, $referral_date, $status
                );

                if ($stmt->execute()) {
                    $success_msg = "Patient referred successfully to " . htmlspecialchars($refer_to);
                } else {
                    $error_msg = "Error creating referral: " . $stmt->error;
                }
                $stmt->close();
            }

            // Record check-in/visit
            $checkin_sql = "INSERT INTO patient_checkins (
                patient_id, mat_id, visit_type, notes, checked_in_by, checkin_date
            ) VALUES (?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($checkin_sql);
            $stmt->bind_param('issss', $patient['p_id'], $mat_id, $visit_type, $checkin_notes, $user_name);

            if ($stmt->execute()) {
                if (!isset($success_msg)) {
                    $success_msg = "Patient checked in successfully for " . htmlspecialchars($visit_type);
                }
            } else {
                $error_msg = "Error recording check-in: " . $stmt->error;
            }
            $stmt->close();

            // Clear selected patient after successful submission
            unset($selected_patient);
            $patients = [];
        } else {
            $error_msg = "Patient not found with MAT ID: " . htmlspecialchars($mat_id);
        }
    } elseif (isset($_POST['select_patient'])) {
        // Handle patient selection
        $selected_mat_id = $_POST['select_patient'];

        // Get selected patient details
        $sql = "SELECT * FROM patients WHERE mat_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $selected_mat_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $selected_patient = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Get user roles for referral dropdown
$roles_sql = "SELECT role FROM userroles WHERE role NOT IN ('Health Records', 'Admin') ORDER BY role";
$roles_result = mysqli_query($conn, $roles_sql);
$roles = [];
while ($row = mysqli_fetch_assoc($roles_result)) {
    $roles[] = $row['role'];
}

// Get recent check-ins (last 10)
$recent_checkins_sql = "SELECT pc.*, p.clientName, p.sname
                       FROM patient_checkins pc
                       JOIN patients p ON pc.patient_id = p.p_id
                       ORDER BY pc.checkin_date DESC LIMIT 10";
$recent_checkins_result = mysqli_query($conn, $recent_checkins_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Patient Check-in & Referral</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e1e8ed;
            color: #2c3e50;
        }

        .btn-secondary:hover {
            background: #d1d8dd;
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #219a52);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #219a52, #1e8449);
            transform: translateY(-2px);
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-results {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e1e8ed;
            border-radius: 6px;
            margin-top: 10px;
        }

        .patient-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .patient-item:hover {
            background: #f8f9fa;
        }

        .patient-item:last-child {
            border-bottom: none;
        }

        .patient-info h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .patient-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .patient-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            margin: 0;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .recent-checkins {
            margin-top: 30px;
        }

        .checkin-list {
            border: 1px solid #e1e8ed;
            border-radius: 6px;
            overflow: hidden;
        }

        .checkin-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: center;
        }

        .checkin-item:last-child {
            border-bottom: none;
        }

        .checkin-item:hover {
            background: #f8f9fa;
        }

        .checkin-patient {
            font-weight: 600;
            color: #2c3e50;
        }

        .checkin-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-induction {
            background: #fff3cd;
            color: #856404;
        }

        .type-reinduction {
            background: #d1ecf1;
            color: #0c5460;
        }

        .type-revisit {
            background: #d4edda;
            color: #155724;
        }

        .checkin-time {
            font-size: 12px;
            color: #666;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-menu {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .nav-menu a {
            text-decoration: none;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div>
                <h1>Health Records - Patient Check-in & Referral System</h1>
                <p>Manage patient check-ins and referrals to other departments</p>
            </div>
            <div class="user-info">
                <p>Logged in as: <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                <p><?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="nav-menu">
            <a href="../patients/view_all_patients.php" class="btn btn-secondary">Update Patient Records</a>
            <a href="../referrals/referral_dashboard.php" class="btn btn-secondary">View Referrals</a>
            <a href="../records/main_content.php" class="btn btn-secondary">Search Patient</a>
        </div>

        <div class="main-content">
            <!-- Left Column: Patient Search -->
            <div>
                <div class="card">
                    <h2>Search Patient</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="search_term">Search by MAT ID or Name:</label>
                            <input type="text"
                                   id="search_term"
                                   name="search_term"
                                   class="form-control"
                                   placeholder="Enter MAT ID or patient name..."
                                   value="<?php echo htmlspecialchars($_POST['search_term'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="search_patient" class="btn btn-primary">
                            Search Patient
                        </button>
                    </form>

                    <?php if (isset($patients) && !empty($patients)): ?>
                        <div class="search-results">
                            <?php foreach ($patients as $patient): ?>
                                <div class="patient-item">
                                    <div class="patient-info">
                                        <h4><?php echo htmlspecialchars($patient['clientName'] . ' ' . $patient['sname']); ?></h4>
                                        <p>MAT ID: <?php echo htmlspecialchars($patient['mat_id']); ?> |
                                           Age: <?php echo htmlspecialchars($patient['age']); ?> |
                                           Sex: <?php echo htmlspecialchars($patient['sex']); ?></p>
                                    </div>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="select_patient" value="<?php echo htmlspecialchars($patient['mat_id']); ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">Select</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (isset($_POST['search_patient']) && empty($patients)): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;">
                            No patients found matching your search.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Check-ins -->
                <?php if ($recent_checkins_result && mysqli_num_rows($recent_checkins_result) > 0): ?>
                <div class="card recent-checkins">
                    <h2>Recent Check-ins</h2>
                    <div class="checkin-list">
                        <?php
                        // Reset pointer and loop through results
                        mysqli_data_seek($recent_checkins_result, 0);
                        while ($checkin = mysqli_fetch_assoc($recent_checkins_result)):
                        ?>
                            <div class="checkin-item">
                                <div class="checkin-patient">
                                    <?php echo htmlspecialchars($checkin['clientName'] . ' ' . $checkin['sname']); ?>
                                </div>
                                <span class="checkin-type type-<?php echo strtolower($checkin['visit_type'] ?? 'revisit'); ?>">
                                    <?php echo htmlspecialchars($checkin['visit_type'] ?? 'Revisit'); ?>
                                </span>
                                <div class="checkin-time">
                                    <?php echo date('g:i A', strtotime($checkin['checkin_date'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Patient Check-in & Referral -->
            <div>
                <?php if (isset($selected_patient)): ?>
                <div class="card">
                    <h2>Patient Check-in & Referral</h2>

                    <!-- Patient Details -->
                    <div class="patient-details">
                        <h3>Selected Patient</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Patient Name</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($selected_patient['clientName'] . ' ' . $selected_patient['sname']); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">MAT ID</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($selected_patient['mat_id']); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Age</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($selected_patient['age']); ?> years
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Sex</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($selected_patient['sex']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Check-in Form -->
                    <form method="POST" action="">
                        <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($selected_patient['mat_id']); ?>">

                        <div class="form-group">
                            <label>Visit Type:</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="visit_type" value="Induction" required>
                                    Induction
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="visit_type" value="Re-induction">
                                    Re-induction
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="visit_type" value="Revisit" checked>
                                    Revisit
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="refer_to">Refer to Department (Optional):</label>
                            <select id="refer_to" name="refer_to" class="form-control">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role); ?>">
                                        <?php echo htmlspecialchars($role); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #666;">Leave blank if no referral needed</small>
                        </div>

                        <div class="form-group">
                            <label for="referral_notes">Referral Notes (if referring):</label>
                            <textarea id="referral_notes" name="referral_notes" class="form-control"
                                      placeholder="Reason for referral, specific instructions, etc."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="checkin_notes">Check-in Notes:</label>
                            <textarea id="checkin_notes" name="checkin_notes" class="form-control"
                                      placeholder="General notes about this visit..." required></textarea>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="submit_checkin" class="btn btn-success">
                                Complete Check-in
                            </button>
                            <a href="../patients/update.php?mat_id=<?php echo urlencode($selected_patient['mat_id']); ?>"
                               class="btn btn-primary">
                                Update Patient Info
                            </a>
                            <button type="button" onclick="window.location.href='health_records_checkin.php'"
                                    class="btn btn-secondary">
                                Cancel / Select Different Patient
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="card">
                    <h2>Patient Check-in & Referral</h2>
                    <div style="text-align: center; padding: 40px 20px;">
                        <p style="font-size: 18px; color: #666; margin-bottom: 20px;">
                            Select a patient from the search results to begin check-in
                        </p>
                        <div style="font-size: 60px; color: #e1e8ed; margin-bottom: 20px;">
                            ??????
                        </div>
                        <p style="color: #999;">
                            Search for a patient using MAT ID or name, then select them to proceed with check-in, referral, or information update.
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle referral notes based on referral selection
            const referToSelect = document.getElementById('refer_to');
            const referralNotes = document.getElementById('referral_notes');

            function toggleReferralNotes() {
                if (referToSelect && referralNotes) {
                    if (referToSelect.value) {
                        referralNotes.required = true;
                        referralNotes.parentElement.style.display = 'block';
                    } else {
                        referralNotes.required = false;
                        referralNotes.parentElement.style.display = 'block';
                    }
                }
            }

            if (referToSelect) {
                referToSelect.addEventListener('change', toggleReferralNotes);
                toggleReferralNotes();
            }

            // Auto-submit search on Enter key
            const searchInput = document.getElementById('search_term');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && this.value.trim().length >= 2) {
                        this.form.submit();
                    }
                });
            }

            // Confirm before submitting check-in
            const checkinForm = document.querySelector('form[action=""]');
            if (checkinForm) {
                checkinForm.addEventListener('submit', function(e) {
                    const visitType = document.querySelector('input[name="visit_type"]:checked');
                    const checkinNotes = document.getElementById('checkin_notes');

                    if (!visitType) {
                        e.preventDefault();
                        alert('Please select a visit type.');
                        return false;
                    }

                    if (!checkinNotes.value.trim()) {
                        e.preventDefault();
                        alert('Please enter check-in notes.');
                        checkinNotes.focus();
                        return false;
                    }

                    // Confirm if referring to another department
                    const referTo = document.getElementById('refer_to');
                    if (referTo && referTo.value) {
                        if (!confirm('Are you sure you want to refer this patient to ' + referTo.value + '?')) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    return true;
                });
            }
        });
    </script>
</body>
</html>