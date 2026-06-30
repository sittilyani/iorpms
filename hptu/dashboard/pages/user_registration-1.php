<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/config.php';
include '../includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current logged-in user's full name for created_by
$created_by_name = '';
$created_by_id = $_SESSION['user_id'] ?? null;

if ($created_by_id) {
    $user_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tblusers WHERE user_id = ?");
    $user_stmt->bind_param("i", $created_by_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_row = $user_result->fetch_assoc()) {
        $created_by_name = $user_row['full_name'];
    }
    $user_stmt->close();
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

        $photo = null;

        if (empty($username) || empty($first_name) || empty($last_name) || empty($facilityname)) {
            $_SESSION['error_message'] = "Required fields are missing.";
        } elseif (empty($created_by_id)) {
            $_SESSION['error_message'] = "Session error: Cannot identify the user creating this account.";
        } else {
            // Handle file upload
            if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handlePhotoUpload($_FILES['photo_upload'], $first_name, $last_name);

                if ($uploadResult['success']) {
                    $photo = $uploadResult['photo_data'];
                } else {
                    $_SESSION['error_message'] = $uploadResult['error'];
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $default_password = '123456';
                $hashed_password = password_hash($default_password, PASSWORD_BCRYPT);

                $sql = "INSERT INTO tblusers (
                            username, first_name, last_name, email, gender, mobile, photo, userrole, cadre, department, position,
                            facilityname, level_of_care, mflcode, subcountyname, countyname, supervisor, password, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssssbssssssissssi",
                        $username, $first_name, $last_name, $email, $gender, $mobile, $photo, $userrole, $cadre, $department, $position,
                        $facilityname, $level_of_care, $mflcode, $subcountyname, $countyname, $supervisor, $hashed_password, $created_by_id
                    );

                    if ($photo !== null) {
                        $stmt->send_long_data(6, $photo);
                    }

                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "User registered successfully! Default password: <strong>123456</strong>";
                    } else {
                        $_SESSION['error_message'] = "Database error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = "Database preparation error: " . $conn->error;
                }
            }
        }
    }
}

function handlePhotoUpload($file, $first_name, $last_name) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 1 * 1024 * 1024;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size too large. Maximum 1MB allowed.'];
    }

    $image_info = getimagesize($file['tmp_name']);
    if (!$image_info) {
        return ['success' => false, 'error' => 'Invalid image file.'];
    }

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

function generateFileName($first_name, $last_name, $mime_type) {
    $extension_map = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    $full_name = preg_replace('/[^a-zA-Z0-9]/', '_', $first_name . '_' . $last_name);
    $timestamp = date('Ymd_His');
    $extension = $extension_map[$mime_type] ?? 'jpg';
    return strtolower($full_name . '_' . $timestamp . '.' . $extension);
}
?>

<style>
:root { --primary: #4B0082; --primary-dark: #330066; --success: #28a745; --danger: #dc3545; --light: #f8f9fa; --border: #dee2e6; }
.reg-container { max-width: 1200px; margin: 20px auto; }
.reg-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
.reg-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 30px 40px; border-bottom: 4px solid rgba(255,255,255,0.1); }
.reg-header h4 { font-size: 1.75rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
.reg-header h4 i { font-size: 2rem; opacity: 0.9; }
.creator-badge { background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 8px; margin-top: 12px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; backdrop-filter: blur(10px); }
.creator-badge i { font-size: 1.1rem; }
.reg-body { padding: 40px; }
.form-section { margin-bottom: 35px; }
.section-title { color: var(--primary); font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--light); display: flex; align-items: center; gap: 10px; }
.section-title i { opacity: 0.8; }
.form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 25px; }
.form-grid.two-col { grid-template-columns: repeat(2, 1fr); }
.form-group { display: flex; flex-direction: column; }
.form-group label { font-weight: 600; color: #343a40; margin-bottom: 8px; font-size: 0.9rem; }
.form-group label .text-danger { color: var(--danger); font-weight: 700; }
.form-control, .form-select { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 8px; font-size: 14px; transition: all 0.3s ease; background: white; }
.form-control:focus, .form-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(75,0,130,0.1); background: #fafbfc; }
.form-control:hover, .form-select:hover { border-color: #adb5bd; }
.form-control[readonly] { background: #e9ecef; opacity: 0.7; cursor: not-allowed; }
.form-select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 40px; }
.form-text { font-size: 0.8rem; color: #6c757d; margin-top: 5px; }
.form-text i { margin-right: 4px; }
.alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-weight: 500; animation: slideIn 0.3s ease; }
.alert i { font-size: 1.2rem; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid var(--success); }
.alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid var(--danger); }
@keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.btn-group { display: flex; gap: 15px; margin-top: 30px; padding-top: 30px; border-top: 2px solid var(--light); }
.btn { padding: 14px 32px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; }
.btn i { font-size: 1.1rem; }
.btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; box-shadow: 0 4px 12px rgba(75,0,130,0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(75,0,130,0.4); }
.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
@media (max-width: 992px) { .form-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; } }
@media (max-width: 768px) {
    .reg-body { padding: 25px 20px; }
    .reg-header { padding: 25px 20px; }
    .form-grid, .form-grid.two-col { grid-template-columns: 1fr; gap: 20px; }
    .btn-group { flex-direction: column-reverse; }
    .btn { width: 100%; justify-content: center; }
}
</style>

<div class="reg-container">
    <div class="reg-card">
        <div class="reg-header">
            <h4><i class="fas fa-user-plus"></i> Register New User</h4>
            <?php if ($created_by_name): ?>
                <div class="creator-badge">
                    <i class="fas fa-user-shield"></i>
                    <span>Creating as: <strong><?= htmlspecialchars($created_by_name) ?></strong></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="reg-body">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $_SESSION['success_message'] ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
                <script>setTimeout(() => window.location.href = '?page=user_registration', 2000);</script>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> Personal Information
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Gender <span class="text-danger">*</span></label>
                            <select class="form-select" name="gendername" required>
                                <option value="">-- Select Gender --</option>
                                <?php
                                $result = $conn->query("SELECT gendername FROM gender ORDER BY gendername");
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['gendername']) . "'>" . htmlspecialchars($row['gendername']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mobile Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="mobile" required>
                        </div>
                    </div>
                    <div class="form-grid two-col">
                        <div class="form-group">
                            <label>Photo <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="photo_upload" accept=".jpg,.jpeg,.png,.gif" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Max 1MB. Formats: JPG, PNG, GIF
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-briefcase"></i> Professional Information
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>User Role <span class="text-danger">*</span></label>
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
                        <div class="form-group">
                            <label>Cadre</label>
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
                        <div class="form-group">
                            <label>Department</label>
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
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Position</label>
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
                        <div class="form-group">
                            <label>Supervisor</label>
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
                    </div>
                </div>

                <!-- Facility Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-hospital"></i> Facility Information
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Facility Name <span class="text-danger">*</span></label>
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
                        <div class="form-group">
                            <label>Level of Care</label>
                            <input type="text" class="form-control" id="level_of_care" name="level_of_care" readonly placeholder="Auto-filled">
                        </div>
                        <div class="form-group">
                            <label>MFL Code</label>
                            <input type="text" class="form-control" id="mflcode" name="mflcode" readonly placeholder="Auto-filled">
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>County</label>
                            <input type="text" class="form-control" id="countyname" name="countyname" readonly placeholder="Auto-filled">
                        </div>
                        <div class="form-group">
                            <label>Sub-County</label>
                            <input type="text" class="form-control" id="subcountyname" name="subcountyname" readonly placeholder="Auto-filled">
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="?page=user_registration" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-check"></i> Register User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
        Object.values(fields).forEach(field => field.value = '');
        if (!facility) return;

        Object.values(fields).forEach(field => field.value = 'Loading...');

        fetch('fetch_facility.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'facilityname=' + encodeURIComponent(facility)
        })
        .then(response => response.json())
        .then(data => {
            fields.level_of_care.value = data.level_of_care || '';
            fields.mflcode.value = data.mflcode || '';
            fields.countyname.value = data.countyname || '';
            fields.subcountyname.value = data.subcountyname || '';
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Could not load facility details.');
            Object.values(fields).forEach(field => field.value = '');
        });
    });

    const fileInput = document.querySelector('input[name="photo_upload"]');
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 1024 * 1024) {
                alert('File size must be less than 1MB');
                this.value = '';
                return;
            }
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, GIF)');
                this.value = '';
            }
        }
    });
});
</script>