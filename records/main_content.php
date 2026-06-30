<?php
session_start();
include('../includes/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

$page_title = 'Search Clients';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

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
                        <?php while ($checkin = mysqli_fetch_assoc($recent_checkins_result)): ?>
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
                            <small class="text-muted">Leave blank if no referral needed</small>
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
                            ?????
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
                if (referToSelect.value) {
                    referralNotes.required = true;
                    referralNotes.parentElement.style.display = 'block';
                } else {
                    referralNotes.required = false;
                    referralNotes.parentElement.style.display = 'block';
                }
            }

            if (referToSelect) {
                referToSelect.addEventListener('change', toggleReferralNotes);
                toggleReferralNotes(); // Initial check
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