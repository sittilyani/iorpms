<?php
// Your existing PHP code remains unchanged
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $username      = trim($_POST['username'] ?? '');
        $first_name    = trim($_POST['first_name'] ?? '');
        $last_name     = trim($_POST['last_name'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $gender        = $_POST['gendername'] ?? '';
        $mobile        = trim($_POST['mobile'] ?? '');
        $userrole      = $_POST['userrole'] ?? '';
        $cadre         = $_POST['cadre'] ?? '';
        $department    = $_POST['department'] ?? '';
        $position      = $_POST['position'] ?? '';
        $supervisor    = $_POST['supervisor'] ?? '';
        $facilityname  = $_POST['facilityname'] ?? '';
        $level_of_care = $_POST['level_of_care'] ?? '';
        $mflcode       = $_POST['mflcode'] ?? '';
        $countyname    = $_POST['countyname'] ?? '';
        $subcountyname = $_POST['subcountyname'] ?? '';

        // Initialize photo variable
        $photo = null;

        if (empty($username) || empty($first_name) || empty($last_name) || empty($facilityname)) {
            $_SESSION['error_message'] = "Required fields are missing.";
        } else {
            // Handle file upload
            if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handlePhotoUpload($_FILES['photo_upload'], $first_name, $last_name);

                if ($uploadResult['success']) {
                    $photo = $uploadResult['photo_data'];
                } else {
                    $_SESSION['error_message'] = $uploadResult['error'];
                    // Continue without photo or return error based on your requirements
                }
            }

            // Only proceed if no upload errors or if upload is optional
            if (!isset($_SESSION['error_message'])) {
                $default_password = '123456';
                $hashed_password = password_hash($default_password, PASSWORD_BCRYPT);

                $sql = "INSERT INTO tblusers (
                            username, first_name, last_name, email, gender, mobile, photo, userrole, cadre, department, position,
                            facilityname, level_of_care, mflcode, subcountyname, countyname, supervisor, password
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssbsssssiissss",
                    $username, $first_name, $last_name, $email, $gender, $mobile, $photo, $userrole, $cadre, $department, $position,
                    $facilityname, $level_of_care, $mflcode, $subcountyname, $countyname, $supervisor, $hashed_password
                );

                // Send blob data
                if ($photo !== null) {
                    $stmt->send_long_data(6, $photo); // photo is at position 6
                }

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "User registered successfully! Default password: <strong>123456</strong>";
                    // NO header() → Use JS redirect below
                } else {
                    $_SESSION['error_message'] = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

/**
 * Handle photo upload and convert to MEDIUMBLOB
 */
function handlePhotoUpload($file, $first_name, $last_name) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 1 * 1024 * 1024; // 1MB in bytes

    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size too large. Maximum 1MB allowed.'];
    }

    // Validate image dimensions and content
    $image_info = getimagesize($file['tmp_name']);
    if (!$image_info) {
        return ['success' => false, 'error' => 'Invalid image file.'];
    }

    // Read file content
    $photo_data = file_get_contents($file['tmp_name']);
    if ($photo_data === false) {
        return ['success' => false, 'error' => 'Could not read uploaded file.'];
    }

    return [
        'success' => true,
        'photo_data' => $photo_data,
        'file_name' => generateFileName($first_name, $last_name, $mime_type)
    ];
}

/**
 * Generate filename in format: full_name_timestamp.extension
 */
function generateFileName($first_name, $last_name, $mime_type) {
    $extension_map = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];

    $full_name = preg_replace('/[^a-zA-Z0-9]/', '_', $first_name . '_' . $last_name);
    $timestamp = date('Ymd_His');
    $extension = $extension_map[$mime_type] ?? 'jpg';

    return strtolower($full_name . '_' . $timestamp . '.' . $extension);
}
?>

<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm">
        <div class="card-header text-white py-3" style="background: #4B0082;">
            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i> Register New User</h4>
        </div>
        <div class="card-body p-4">

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
                <script>
                    // Auto-refresh after success (clean reload)
                    setTimeout(() => {
                        window.location.href = '?page=user_registration';
                    }, 2000);
                </script>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="row g-3">

                    <!-- Username -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>

                    <!-- First Name -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email<span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <!-- Gender -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Gender<span class="text-danger">*</span></label>
                        <select class="form-select" name="gendername" required>
                            <option value="">-- Select Gender --</option>
                            <?php
                            $result = $conn->query("SELECT gendername FROM gender");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['gendername']) . "'>" . htmlspecialchars($row['gendername']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Mobile -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mobile Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="mobile" required>
                    </div>

                    <!-- Photo Upload -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Photo <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="photo_upload" accept=".jpg,.jpeg,.png,.gif" required>
                        <div class="form-text">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Max file size: 1MB. Allowed formats: JPG, PNG, GIF.
                                File will be stored as: firstname_lastname_timestamp.ext
                            </small>
                        </div>
                    </div>

                    <!-- Hidden field for backward compatibility -->
                    <input type="hidden" name="photo" value="uploaded_photo">

                    <!-- User Role -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">User Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="userrole" required>
                            <option value="">-- Select Role --</option>
                            <?php
                            $result = $conn->query("SELECT role FROM userroles ORDER BY role");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['role']) . "'>" . htmlspecialchars($row['role']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Cadre -->
                    <div class="col-md-6">
                        <label class="form-label">Cadre</label>
                        <select class="form-select" name="cadre">
                            <option value="">-- Select Cadre --</option>
                            <?php
                            $result = $conn->query("SELECT cadrename FROM cadres ORDER BY cadrename");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['cadrename']) . "'>" . htmlspecialchars($row['cadrename']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Department -->
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department">
                            <option value="">-- Select Department --</option>
                            <?php
                            $result = $conn->query("SELECT departmentname FROM departments ORDER BY departmentname");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['departmentname']) . "'>" . htmlspecialchars($row['departmentname']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Position -->
                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <select class="form-select" name="position">
                            <option value="">-- Select Position --</option>
                            <?php
                            $result = $conn->query("SELECT positionname FROM positions ORDER BY positionname");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['positionname']) . "'>" . htmlspecialchars($row['positionname']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Supervisor -->
                    <div class="col-md-6">
                        <label class="form-label">Supervisor</label>
                        <select class="form-select" name="supervisor">
                            <option value="">-- Select Supervisor --</option>
                            <?php
                            $result = $conn->query("SELECT full_name FROM tblusers WHERE full_name IS NOT NULL AND full_name != '' AND (userrole LIKE '%County%' OR userrole = 'Admin') ORDER BY full_name");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['full_name']) . "'>" . htmlspecialchars($row['full_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Facility -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Facility Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="facilityname" name="facilityname" required>
                            <option value="">-- Select Facility --</option>
                            <?php
                            $result = $conn->query("SELECT facilityname FROM facilities ORDER BY facilityname");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['facilityname']) . "'>" . htmlspecialchars($row['facilityname']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Level of Care -->
                    <div class="col-md-3">
                        <label class="form-label">Level of Care</label>
                        <input type="text" class="form-control" id="level_of_care" name="level_of_care" readonly placeholder="Auto-filled">
                    </div>

                    <!-- MFL Code -->
                    <div class="col-md-3">
                        <label class="form-label">MFL Code</label>
                        <input type="text" class="form-control" id="mflcode" name="mflcode" readonly placeholder="Auto-filled">
                    </div>

                    <!-- County -->
                    <div class="col-md-3">
                        <label class="form-label">County</label>
                        <input type="text" class="form-control" id="countyname" name="countyname" readonly placeholder="Auto-filled">
                    </div>

                    <!-- Sub-County -->
                    <div class="col-md-3">
                        <label class="form-label">Sub-County</label>
                        <input type="text" class="form-control" id="subcountyname" name="subcountyname" readonly placeholder="Auto-filled">
                    </div>

                    <!-- Submit -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5" style="background: #4B0082;">
                            <i class="fa fa-user-check"></i> Register User
                        </button>
                        <a href="?page=user_registration" class="btn btn-secondary btn-lg px-5 ms-2" style="background: #4B0082;">
                            <i class="fa fa-sync"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- AJAX for Facility Details -->
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    const facilitySelect = document.getElementById('facilityname');
    const fields = {
        level_of_care: document.getElementById('level_of_care'),
        mflcode: document.getElementById('mflcode'),
        countyname: document.getElementById('countyname'),
        subcountyname: document.getElementById('subcountyname')
    };

    facilitySelect.addEventListener('change', function() {
        const facility = this.value.trim();

        // Clear fields first
        Object.values(fields).forEach(field => field.value = '');

        if (!facility) return;

        // Show loading feedback (optional)
        Object.values(fields).forEach(field => {
            field.value = 'Loading...';
            field.style.backgroundColor = '#f8f9fa';
        });

        // Send request
        fetch('fetch_facility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'facilityname=' + encodeURIComponent(facility)
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            fields.level_of_care.value = data.level_of_care || '';
            fields.mflcode.value       = data.mflcode || '';
            fields.countyname.value    = data.countyname || '';
            fields.subcountyname.value = data.subcountyname || '';
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Could not load facility details. Please try again.');
            Object.values(fields).forEach(field => field.value = '');
        });
    });

    // File upload validation
    const fileInput = document.querySelector('input[name="photo_upload"]');
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            // Check file size (1MB)
            if (file.size > 1024 * 1024) {
                alert('File size must be less than 1MB');
                this.value = '';
                return;
            }

            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, GIF)');
                this.value = '';
                return;
            }
        }
    });
});
</script>

<style>
    .card { border-radius: 12px; overflow: hidden; }
    .card-header { background: ; }
    .form-control[readonly], .form-select[readonly] {
        background-color: #e9ecef;
        opacity: 0.7;
    }
    .btn-lg { font-weight: 600; }
    .form-text { font-size: 0.8rem; }
</style>