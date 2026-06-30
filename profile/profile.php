<?php
ob_start();
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$user = [];
$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Check if user_id is provided
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];

    // Non-admin users can only view their own profile
    if ($_SESSION['userrole'] != 'Admin' && $_SESSION['user_id'] != $user_id) {
        header("Location: ../login.php?error=access_denied");
        exit();
    }
} else {
    // Default to current user's profile
    $user_id = $_SESSION['user_id'];
}

// Fetch user data
$sql = "SELECT user_id, username, first_name, last_name, userrole, email, gender, mobile, date_created, photo
        FROM tblusers
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        header("Location: userslist.php?error=user_not_found");
        exit();
    }
    $stmt->close();
} else {
    die("SQL error: " . $conn->error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: profile.php?user_id=$user_id&error=" . urlencode("Invalid CSRF token"));
        exit;
    }

    $username = $_POST['username'] ?? '';
    $userrole = $_POST['userrole'] ?? $user['userrole'];
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $photo = $user['photo'] ?? ''; // Keep existing photo by default

    // Non-admin users can't change their role
    if ($_SESSION['userrole'] != 'Admin') {
        $userrole = $user['userrole'];
    }

    // Validate required fields
    if (empty($username) || empty($email) || empty($gender)) {
        header("Location: profile.php?user_id=$user_id&error=" . urlencode("All required fields must be filled"));
        exit;
    }

    // Handle photo upload
    $upload_dir = '../photos/users/';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_type = $_FILES['photo']['type'];
        $file_size = $_FILES['photo']['size'];
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        // Validate file type and size
        if (!in_array($file_type, $allowed_types)) {
            header("Location: profile.php?user_id=$user_id&error=" . urlencode("Invalid file type. Only JPEG, PNG, and GIF are allowed."));
            exit;
        }

        if ($file_size > $max_size) {
            header("Location: profile.php?user_id=$user_id&error=" . urlencode("File size exceeds 5MB limit."));
            exit;
        }

        // Generate new filename: full_name_user_id_current_date
        $full_name = str_replace(' ', '_', trim(($user['first_name'] ?? '') . '_' . ($user['last_name'] ?? '')));
        $current_date = date('Ymd');
        $photo = "{$full_name}_{$user_id}_{$current_date}.{$file_ext}";

        // Delete old photo if it exists
        if (!empty($user['photo']) && file_exists($upload_dir . $user['photo'])) {
            unlink($upload_dir . $user['photo']);
        }

        // Move uploaded file to destination
        if (!move_uploaded_file($file_tmp, $upload_dir . $photo)) {
            header("Location: profile.php?user_id=$user_id&error=" . urlencode("Failed to upload photo."));
            exit;
        }
    } elseif (isset($_POST['webcam_photo']) && !empty($_POST['webcam_photo'])) {
        // Handle webcam photo
        $full_name = str_replace(' ', '_', trim(($user['first_name'] ?? '') . '_' . ($user['last_name'] ?? '')));
        $current_date = date('Ymd');
        $photo = "{$full_name}_{$user_id}_{$current_date}.jpg";

        // Decode base64 image from webcam
        $webcam_data = $_POST['webcam_photo'];
        $webcam_data = str_replace('data:image/jpeg;base64,', '', $webcam_data);
        $webcam_data = str_replace(' ', '+', $webcam_data);
        $image_data = base64_decode($webcam_data);

        if ($image_data === false) {
            header("Location: profile.php?user_id=$user_id&error=" . urlencode("Failed to process webcam photo."));
            exit;
        }

        // Delete old photo if it exists
        if (!empty($user['photo']) && file_exists($upload_dir . $user['photo'])) {
            unlink($upload_dir . $user['photo']);
        }

        // Save webcam photo
        if (!file_put_contents($upload_dir . $photo, $image_data)) {
            header("Location: profile.php?user_id=$user_id&error=" . urlencode("Failed to save webcam photo."));
            exit;
        }
    }

    // Update users table
    $sql_users = "UPDATE tblusers SET username = ?, userrole = ?, email = ?, gender = ?, mobile = ?, photo = ? WHERE user_id = ?";
    $stmt_users = $conn->prepare($sql_users);
    if ($stmt_users) {
        $stmt_users->bind_param('ssssssi', $username, $userrole, $email, $gender, $mobile, $photo, $user_id);

        if ($stmt_users->execute()) {
            header("Location: profile.php?user_id=$user_id&success=Profile updated successfully");
            exit();
        } else {
            header("Location: profile.php?user_id=$user_id&error=" . urlencode("Update failed: " . $stmt_users->error));
            exit();
        }
        $stmt_users->close();
    } else {
        header("Location: profile.php?user_id=$user_id&error=" . urlencode("Update preparation failed: " . $conn->error));
        exit;
    }
}

// Helper function to safely output values
function safeOutput($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?= safeOutput($user['username']); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .profile-card {
            max-width: 1200px;
            margin: 30px auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(90deg, rgba(2, 0, 36, 1) 0%, rgba(22, 22, 51, 1) 29%, rgba(9, 9, 121, 1) 78%, rgba(0, 212, 255, 1) 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .profile-body {
            padding: 30px;
            background-color: #f8f9fa;
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #495057;
        }
        .form-group input, .form-group select, .form-group video, .form-group canvas, .form-group img {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        .form-group input[readonly] {
            background-color: #e9ecef;
            opacity: 0.8;
        }
        .btn-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #218838, #1aa179);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .alert {
            border-radius: 8px;
            margin: 20px auto;
            max-width: 800px;
        }
        .webcam-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        #start-webcam, #capture-btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #start-webcam:hover, #capture-btn:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .profile-card {
                margin: 15px;
            }
        }
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
        }
    </style>
</head>
<body>
    <div class="content-main">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= safeOutput($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= safeOutput($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($user)): ?>
            <div class="profile-card">
                <div class="profile-header">
                    <img src="<?= !empty($user['photo']) && file_exists('../photos/users/' . $user['photo']) ? '../photos/users/' . safeOutput($user['photo']) : '../photos/default.png'; ?>" alt="Profile Photo" class="profile-photo">
                    <h2><?= safeOutput($user['username']); ?></h2>
                    <p><?= safeOutput($user['userrole']); ?></p>
                </div>
                <div class="profile-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?= safeOutput($user['user_id']); ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="webcam_photo" id="webcam_photo">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username">User Name</label>
                                <input type="text" id="username" name="username" value="<?= safeOutput($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?= safeOutput($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="mobile">Mobile</label>
                                <input type="text" id="mobile" name="mobile" value="<?= safeOutput($user['mobile']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="photo">Upload New Photo</label>
                                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
                            </div>
                            <div class="form-group webcam-container">
                                <label>Or Take a Photo</label>
                                <video id="video" width="100%" height="auto" autoplay style="display: none;"></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                                <img id="preview" style="display: none; width: 100%; border-radius: 5px;">
                                <button type="button" id="start-webcam" class="btn btn-secondary">Start Webcam</button>
                                <button type="button" id="capture-btn" style="display: none;">Capture Photo</button>
                            </div>
                            <?php if ($_SESSION['userrole'] == 'Admin'): ?>
                                <div class="form-group">
                                    <label for="userrole">User Role</label>
                                    <select id="userrole" name="userrole" required>
                                        <option value="User" <?= ($user['userrole'] ?? '') == 'User' ? 'selected' : ''; ?>>User</option>
                                        <option value="Admin" <?= ($user['userrole'] ?? '') == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="userrole" value="<?= safeOutput($user['userrole']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Date Created</label>
                            <input type="text" value="<?= safeOutput($user['date_created']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="submit" class="btn-custom"><i class="fas fa-save"></i> Update Profile</button>
                        </div>
                    </form>
                    <a href="../dashboard/dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center" role="alert">
                User not found.
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Webcam capture functionality
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const preview = document.getElementById('preview');
        const startWebcamBtn = document.getElementById('start-webcam');
        const captureBtn = document.getElementById('capture-btn');
        const webcamPhotoInput = document.getElementById('webcam_photo');
        const photoInput = document.getElementById('photo');

        startWebcamBtn.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.style.display = 'block';
                captureBtn.style.display = 'block';
                startWebcamBtn.style.display = 'none';
                preview.style.display = 'none';
                photoInput.value = ''; // Clear file input if webcam is used
            } catch (err) {
                alert('Error accessing webcam: ' + err.message);
            }
        });

        captureBtn.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const dataUrl = canvas.toDataURL('image/jpeg');
            preview.src = dataUrl;
            preview.style.display = 'block';
            webcamPhotoInput.value = dataUrl;
            video.style.display = 'none';
            captureBtn.style.display = 'none';
            startWebcamBtn.style.display = 'block';
            photoInput.value = ''; // Clear file input if webcam is used

            // Stop webcam stream
            video.srcObject.getTracks().forEach(track => track.stop());
        });

        // Clear webcam photo if file input is used
        photoInput.addEventListener('change', () => {
            if (photoInput.files.length > 0) {
                webcamPhotoInput.value = '';
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>