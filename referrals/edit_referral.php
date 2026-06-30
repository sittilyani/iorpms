<?php
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mat_id = $_POST['mat_id'];
        $status = $_POST['status'];

        // Update the referral status
        $sql = "UPDATE referral SET status = ?, referral_date = NOW() WHERE mat_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $status, $mat_id);

        if ($stmt->execute()) {
                echo "<script>
                                alert('Referral status updated successfully');
                                window.location.href = 'referral_dashboard.php';
                            </script>";
        } else {
                echo "Error updating referral: " . $conn->error;
        }

        $stmt->close();
        exit();
}

$mat_id = $_GET['mat_id'] ?? null;

if (!$mat_id) {
        die("Referral ID is required.");
}

// Fetch referral details
$sql = "SELECT * FROM referral WHERE mat_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
        die("No referral found.");
}

$referral = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <title>Edit Referral</title>
        <style>
                body {
                        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                        background: #f4f7fa;
                        margin: 0;
                        padding: 0;
                }
                .container {
                        max-width: 600px;
                        margin: 50px auto;
                        background: #fff;
                        padding: 25px 30px;
                        border-radius: 12px;
                        box-shadow: 0px 8px 16px rgba(0,0,0,0.1);
                }
                h3 {
                        text-align: center;
                        margin-bottom: 10px;
                        font-size: 22px;
                        color: #333;
                }
                p.note {
                        text-align: center;
                        color: #c62828;
                        font-size: 14px;
                        margin-bottom: 20px;
                }
                label {
                        font-weight: 600;
                        color: #444;
                }
                select {
                        width: 100%;
                        padding: 10px 12px;
                        border: 1px solid #ccc;
                        border-radius: 8px;
                        outline: none;
                        transition: border-color 0.3s;
                        font-size: 14px;
                        margin-top: 6px;
                        margin-bottom: 20px;
                }
                select:focus {
                        border-color: #007bff;
                }
                .btn {
                        background: #007bff;
                        color: #fff;
                        padding: 12px 20px;
                        border: none;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 15px;
                        width: 100%;
                        transition: background 0.3s;
                }
                .btn:hover {
                        background: #0056b3;
                }
        </style>
</head>
<body>

<div class="container">
        <h3>Action on Referral Notes</h3>
        <p class="note">(Update status from Viewed, Reviewed to Completed when done)</p>

        <form method="POST" action="">
                <input type="hidden" name="mat_id" value="<?php echo $referral['mat_id']; ?>">

                <div class="form-group">
                        <label for="status">Change Status:</label>
                        <select name="status" required>
                                <option value="viewed" <?php echo $referral['status'] === 'viewed' ? 'selected' : ''; ?>>Viewed</option>
                                <option value="reviewed" <?php echo $referral['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="completed" <?php echo $referral['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                </div>

                <button type="submit" class="btn">Update Status</button>
        </form>
</div>

</body>
</html>
