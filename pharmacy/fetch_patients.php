<?php
include '../includes/config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$results_per_page = isset($_GET['results_per_page']) ? (int)$_GET['results_per_page'] : 10;
$count_only = isset($_GET['count']) && $_GET['count'] === 'true';

$search = '%' . $search . '%';
$query = "SELECT p_id, mat_id, mat_number, clientName, nickName, dob, age, sex, p_address, cso, dosage, drugname, current_status
                    FROM patients
                    WHERE (mat_id LIKE ? OR mat_number LIKE ? OR clientName LIKE ? OR nickName LIKE ? OR dob LIKE ? OR age LIKE ? OR sex LIKE ? OR p_address LIKE ? OR cso LIKE ? OR dosage LIKE ? OR drugname LIKE ? OR current_status LIKE ?)
                    AND current_status IN ('Active', 'LTFU', 'Defaulted')";

if ($count_only) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssssssssss', $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        echo $result->num_rows;
        $stmt->close();
        $conn->close();
        exit;
}

$start_limit = ($page - 1) * $results_per_page;
$query .= " LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ssssssssssssii', $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $start_limit, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
}

foreach ($patients as $patient) {
        echo '<div class="search-result-item"
                    data-p-id="' . htmlspecialchars($patient['p_id']) . '"
                    data-mat-id="' . htmlspecialchars($patient['mat_id']) . '"
                    data-mat-number="' . htmlspecialchars($patient['mat_number']) . '"
                    data-name="' . htmlspecialchars($patient['clientName']) . '"
                    data-nickname="' . htmlspecialchars($patient['nickName']) . '"
                    data-dob="' . htmlspecialchars($patient['dob']) . '"
                    data-age="' . htmlspecialchars($patient['age']) . '"
                    data-sex="' . htmlspecialchars($patient['sex']) . '"
                    data-address="' . htmlspecialchars($patient['p_address']) . '"
                    data-cso="' . htmlspecialchars($patient['cso']) . '"
                    data-dosage="' . htmlspecialchars($patient['dosage'] ?? '0') . '"
                    data-drugname="' . htmlspecialchars($patient['drugname'] ?? '') . '"
                    data-status="' . htmlspecialchars($patient['current_status']) . '">
                        <strong>' . htmlspecialchars($patient['clientName']) . ' (' . htmlspecialchars($patient['mat_id']) . ')</strong>
                        <p>Status: ' . htmlspecialchars($patient['current_status']) . ' | Drug: ' . htmlspecialchars($patient['drugname'] ?? '') . ' | Dosage: ' . htmlspecialchars($patient['dosage'] ?? '0') . ' mg</p>
                    </div>';
}

$stmt->close();
$conn->close();
?>