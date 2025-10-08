<?php
require_once "config.php";


try {
        // Connect to the database

        // Set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to select all active patients
        $query = "SELECT * FROM patients";

        // Prepare the statement
        $stmt = $pdo->prepare($query);

        // Execute the statement
        $stmt->execute();

        // Loop through the results
 while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get the patients ID
        $mat_id = $row['mat_id'];
        $current_status = $row['current_status'];

        // Query to get the last visit date for the patients
        $last_visit_query = "SELECT visitDate FROM pharmacy WHERE mat_id = :mat_id ORDER BY visitDate DESC LIMIT 1";


        // Prepare the statement
        $last_visit_stmt = $pdo->prepare($last_visit_query);
        $last_visit_stmt->bindParam(':mat_id', $mat_id);
        $last_visit_stmt->execute();
        $last_visit_result = $last_visit_stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the "visitDate" element exists in the $last_visit_result array
        if (isset($last_visit_result['visitDate'])) {
                // The "visitDate" element exists, so we can safely access it
                $last_visit_date = $last_visit_result['visitDate'];
                //$dosage = $last_visit_result['dosage'];

                // Calculate the difference between the current date and the last visit date
                $date_diff = strtotime(date('Y-m-d')) - strtotime($last_visit_date);

                // Convert the date difference to number of days
                $days_diff = floor($date_diff / (60 * 60 * 24));

                // If the difference is more than 30 days, change the patients's current_status to Lost
                if ($current_status == "Active" && $days_diff > 5 && $days_diff < 30) {
                        $update_query = "UPDATE patients SET current_status='Defaulted' WHERE mat_id=:mat_id";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->bindParam(':mat_id', $mat_id);
                        $update_stmt->execute();

                        // insert into current_statushistory table
                        $insert_query = "INSERT INTO patients (mat_id, current_status, date_of_change) VALUES (:mat_id, :current_status, :date_of_change)";
                        $insert_stmt = $pdo->prepare($insert_query);
                        $insert_stmt->bindParam(':mat_id', $mat_id);
                        $insert_stmt->bindValue(':current_status', 'Defaulter');
                        $insert_stmt->bindValue(':date_of_change', date('Y-m-d'));
                        $insert_stmt->execute();

                }
                else if ($current_status == "Active" && $days_diff >= 30) {
                        $update_query = "UPDATE patients SET current_status='ltfu' WHERE mat_id=:mat_id";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->bindParam(':mat_id', $mat_id);
                        $update_stmt->execute();
                }
                else if($current_status == "Weaned off") {
                        $update_query = "UPDATE patients SET current_status='Weaned' WHERE mat_id=:mat_id";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->bindParam(':mat_id', $mat_id);
                        $update_stmt->execute();
                }
                else if($current_status == "dead"){
                        $update_query = "UPDATE patients SET current_status='dead' WHERE mat_id=:mat_id";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->bindParam(':mat_id', $mat_id);
                        $update_stmt->execute();
                }
                        else if($current_status == "Transout"){
                        $update_query = "UPDATE patients SET current_status='Transout' WHERE mat_id=:mat_id";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->bindParam(':mat_id', $mat_id);
                        $update_stmt->execute();
                }
                            else if($current_status == "Discontinued"){
                        $update_query = "UPDATE patients SET current_status='stopped' WHERE mat_id=:mat_id";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->bindParam(':mat_id', $mat_id);
                        $update_stmt->execute();
                }
        } else {

                // You can add some code here to handle this case if needed, for example:
                echo "";
                //echo "No pharmacy records found for patients with mat_id $mat_id";
        }
}



        // Display a success message
        echo "Running";

} catch(PDOException $e) {
        // Display an error message
        echo "Error: " . $e->getMessage();
}

// Close the connection
$db = null;


// Close the connection
$db = null;

?>
