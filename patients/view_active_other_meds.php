<?php require_once __DIR__ . '/../includes/print_header.php'; print_header_start('internal', 'Active Other Meds clients');
session_start();
include('../includes/config.php');

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Active clients who are NOT on Methadone / Buprenorphine but are receiving other prescriptions.
$sql = "
    SELECT
        patients.*,
        (SELECT DATE_FORMAT(MAX(op.prescription_date), '%Y-%m-%d') FROM other_prescriptions op WHERE op.mat_id = patients.mat_id) AS last_prescription_date,
        (SELECT GROUP_CONCAT(DISTINCT pd.drug_name SEPARATOR ', ')
           FROM prescription_drugs pd
           JOIN other_prescriptions op2 ON op2.prescription_id = pd.prescription_id
          WHERE op2.mat_id = patients.mat_id) AS other_drugs
    FROM patients
    WHERE (mat_id LIKE '%$search%'
        OR mat_number LIKE '%$search%'
        OR clientName LIKE '%$search%'
        OR nickName LIKE '%$search%'
        OR sex LIKE '%$search%'
        OR p_address LIKE '%$search%'
        OR cso LIKE '%$search%')
      AND current_status IN ('defaulted', 'active')
      AND NOT (drugname LIKE '%Methadone%' OR drugname LIKE '%Buprenorphine%')
      AND EXISTS (SELECT 1 FROM other_prescriptions op3 WHERE op3.mat_id = patients.mat_id)
    ORDER BY clientName
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Active on other medications</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
</head>
<body>
    <h2 style="color: #2D008A;">Active Clients on Other Medications</h2>
    <p>Clients not on Methadone or Buprenorphine who are receiving other prescriptions.
       <a href="view_active_patients.php">&larr; Active clients on Methadone / Buprenorphine</a></p>

    <form id="searchForm" action="<?php echo htmlspecialchars((string)($_SERVER["PHP_SELF"])); ?>" method="GET">
        <div class="header">
            <label for="search">Search:</label>
            <input type="text" id="search" class="search-entry" name="search" value="<?php echo htmlspecialchars((string)($_GET['search'] ?? '')); ?>">
            <input type="submit" value="Search" class="search-input">
            <button type="button" onclick="document.getElementById('search').value='';document.getElementById('searchForm').submit();" class="cancel-input">Cancel</button>
            <button type="button" onclick="window.print()">Print PDF</button>
            Count:&nbsp;<span style="color: red;"><?php echo $result ? $result->num_rows : 0; ?></span>
        </div>
    </form>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>p_ID</th><th>MAT ID</th><th>Client Name</th><th>Age</th><th>Sex</th>
                <th>Residence</th><th>CSO</th><th>Other drugs prescribed</th>
                <th>Last prescription</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int)$row['p_id']; ?></td>
                <td><?php echo htmlspecialchars((string)($row['mat_id'])); ?></td>
                <td><?php echo htmlspecialchars((string)($row['clientName'])); ?></td>
                <td><?php echo htmlspecialchars((string)($row['age'])); ?></td>
                <td><?php echo htmlspecialchars((string)($row['sex'])); ?></td>
                <td><?php echo htmlspecialchars((string)($row['p_address'])); ?></td>
                <td><?php echo htmlspecialchars((string)($row['cso'])); ?></td>
                <td><?php echo htmlspecialchars((string)($row['other_drugs'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($row['last_prescription_date'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($row['current_status'])); ?></td>
                <td><a href="view_patient.php?p_id=<?php echo (int)$row['p_id']; ?>">View</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <div>No results found.</div>
<?php endif; ?>
</body>
</html>
