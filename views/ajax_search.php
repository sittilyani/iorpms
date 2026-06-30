<?php
include '../includes/config.php';

if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];

    // Your existing SQL query with search condition
    $sql = "SELECT * FROM patient WHERE status = 'active' AND (mat_id LIKE '%$searchTerm%' OR nat_id LIKE '%$searchTerm%' OR fname LIKE '%$searchTerm%' OR lname LIKE '%$searchTerm%' OR sex LIKE '%$searchTerm%' OR doe LIKE '%$searchTerm%' OR cso LIKE '%$searchTerm%')";
    $result = $conn->query($sql);
    $filteredPatients = $result->fetch_all(MYSQLI_ASSOC);

    // Output the result
    echo '<p>Showing example 1-' . count($filteredPatients) . ' of the total rows</p>';
    echo '<table>';
    // Output your table rows based on filteredPatients
    foreach ($filteredPatients as $patient) {
        // ... Your existing table row structure ...
    }
    echo '</table>';
}
?>
