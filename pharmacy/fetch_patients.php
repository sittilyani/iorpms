<?php
include '../includes/config.php';

$search = isset($_GET['search']) ? trim((string)($_GET['search'])) : '';
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
                    data-p-id="' . htmlspecialchars((string)($patient['p_id'])) . '"
                    data-mat-id="' . htmlspecialchars((string)($patient['mat_id'])) . '"
                    data-mat-number="' . htmlspecialchars((string)($patient['mat_number'])) . '"
                    data-name="' . htmlspecialchars((string)($patient['clientName'])) . '"
                    data-nickname="' . htmlspecialchars((string)($patient['nickName'])) . '"
                    data-dob="' . htmlspecialchars((string)($patient['dob'])) . '"
                    data-age="' . htmlspecialchars((string)($patient['age'])) . '"
                    data-sex="' . htmlspecialchars((string)($patient['sex'])) . '"
                    data-address="' . htmlspecialchars((string)($patient['p_address'])) . '"
                    data-cso="' . htmlspecialchars((string)($patient['cso'])) . '"
                    data-dosage="' . htmlspecialchars((string)($patient['dosage'] ?? '0')) . '"
                    data-drugname="' . htmlspecialchars((string)($patient['drugname'] ?? '')) . '"
                    data-status="' . htmlspecialchars((string)($patient['current_status'])) . '">
                        <strong>' . htmlspecialchars((string)($patient['clientName'])) . ' (' . htmlspecialchars((string)($patient['mat_id'])) . ')</strong>
                        <p>Status: ' . htmlspecialchars((string)($patient['current_status'])) . ' | Drug: ' . htmlspecialchars((string)($patient['drugname'] ?? '')) . ' | Dosage: ' . htmlspecialchars((string)($patient['dosage'] ?? '0')) . ' mg</p>
                    </div>';
}

$stmt->close();
$conn->close();
?>