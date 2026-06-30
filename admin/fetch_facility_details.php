<?php
include('../includes/config.php'); // Ensure this connects to your database

header('Content-Type: application/json');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $facility_id = intval($_GET['id']); // Ensure it's an integer

    if ($conn) {
        $stmt = $conn->prepare("SELECT mflcode, countyname, subcountyname, owner, sdp, agency, emr, emrstatus, infrastructuretype, latitude, longitude FROM facilities WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $facility_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $facility_data = $result->fetch_assoc();
                echo json_encode($facility_data);
            } else {
                echo json_encode(['error' => 'Facility not found.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Database query preparation failed.']);
        }
    } else {
        echo json_encode(['error' => 'Database connection failed.']);
    }
} else {
    echo json_encode(['error' => 'No facility ID provided.']);
}
?>