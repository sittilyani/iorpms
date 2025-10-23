<?php
include "../includes/config.php";

// Retrieve form data and sanitize inputs with default values
$visitDate = $_POST['visitDate'];
$mat_id = $_POST['mat_id'];
$clientName = $_POST['clientName'];
$type_client = $_POST['type_client'] ?? 'Routine'; // Default to first option
$mode_drug_use = $_POST['mode_drug_use'] ?? 'PWUD'; // Default to first option

// Routine Tests - set defaults to first option in each dropdown
$hiv_status = $_POST['hiv_status'] ?? 'Not Done';
$hbv_status = $_POST['hbv_status'] ?? 'Not Done';
$hepc_status = $_POST['hepc_status'] ?? 'Not Done';
$malaria_status = $_POST['malaria_status'] ?? 'Not Done';
$pregnancy_status = $_POST['pregnancy_status'] ?? 'Not Done';
$vdrl_status = $_POST['vdrl_status'] ?? 'Not Done';
$urinalysis_status = $_POST['urinalysis_status'] ?? 'Not Done';

// Toxicology - set defaults to first option in each dropdown
$amphetamine = $_POST['amphetamine'] ?? 'Not Done';
$metamphetamine = $_POST['metamphetamine'] ?? 'Not Done';
$morphine = $_POST['morphine'] ?? 'Not Done';
$barbiturates = $_POST['barbiturates'] ?? 'Not Done';
$cocaine = $_POST['cocaine'] ?? 'Not Done';
$codeine = $_POST['codeine'] ?? 'Not Done';
$benzodiazepines = $_POST['benzodiazepines'] ?? 'Not Done';
$marijuana = $_POST['marijuana'] ?? 'Not Done';
$amitriptyline = $_POST['amitriptyline'] ?? 'Not Done';
$amitriptyline = $_POST['amitriptyline'] ?? 'Not Done';
$opiates = $_POST['opiates'] ?? 'Not Done';
$phencyclidine = $_POST['phencyclidine'] ?? 'Not Done';
$methadone = $_POST['methadone'] ?? 'Not Done';
$buprenorphine = $_POST['buprenorphine'] ?? 'Not Done';
$nicotine = $_POST['nicotine'] ?? 'Not Done';
$other_tca = $_POST['other_tca'] ?? 'Not Done';
$tramadol = $_POST['tramadol'] ?? 'Not Done';
$ketamine = $_POST['ketamine'] ?? 'Not Done';
$fentanyl = $_POST['fentanyl'] ?? 'Not Done';
$oxycodone = $_POST['oxycodone'] ?? 'Not Done';
$propoxyphene = $_POST['propoxyphene'] ?? 'Not Done';
$ecstacy = $_POST['ecstacy'] ?? 'Not Done';
$other_drugs = $_POST['other_drugs'] ?? 'Not Done';

$lab_notes = $_POST['lab_notes'];
$date_of_test = $_POST['date_of_test'];
$next_appointment = $_POST['next_appointment'];
$lab_officer_name = $_POST['lab_officer_name'];

// Transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Insert into `laboratory`
    $lab_query = "INSERT INTO laboratory
        (visitDate, mat_id, clientName, type_client, mode_drug_use, hiv_status, hbv_status,
        hepc_status, malaria_status, pregnancy_status,
         vdrl_status, urinalysis_status, lab_notes, next_appointment, lab_officer_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $lab_stmt = $conn->prepare($lab_query);

    if ($lab_stmt) {
        $lab_stmt->bind_param(
            'sssssssssssssss',
            $visitDate, $mat_id, $clientName, $type_client,
            $mode_drug_use, $hiv_status, $hbv_status, $hepc_status, $malaria_status, $pregnancy_status,
            $vdrl_status, $urinalysis_status, $lab_notes, $next_appointment, $lab_officer_name
        );

        $lab_stmt->execute();
        $lab_id = $conn->insert_id; // Get the last inserted ID for `laboratory`
    } else {
        throw new Exception("Error preparing statement for laboratory: " . $conn->error);
    }

    // Insert into `toxicologyresults`
    $tox_query = "INSERT INTO toxicology_results
        (visitDate, mat_id, clientName, mode_drug_use, amphetamine, metamphetamine, morphine, barbiturates, cocaine, codeine, benzodiazepines, marijuana, amitriptyline, opiates, phencyclidine, methadone, buprenorphine, nicotine, other_tca, tramadol, ketamine,  fentanyl, oxycodone, propoxyphene, ecstacy, other_drugs, lab_notes, date_of_test, next_appointment, lab_officer_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $tox_stmt = $conn->prepare($tox_query);

    if ($tox_stmt) {
        $tox_stmt->bind_param(
            'ssssssssssssssssssssssssssssss',
            $visitDate, $mat_id, $clientName, $mode_drug_use, $amphetamine, $metamphetamine, $morphine,
            $barbiturates, $cocaine, $codeine, $benzodiazepines, $marijuana, $amitriptyline, $opiates, $phencyclidine, $methadone, $buprenorphine, $nicotine, $other_tca, $tramadol, $ketamine,  $fentanyl, $oxycodone, $propoxyphene, $ecstacy, $other_drugs, $lab_notes,
            $date_of_test, $next_appointment, $lab_officer_name
        );

        $tox_stmt->execute();
    } else {
        throw new Exception("Error preparing statement for toxicologyresults: " . $conn->error);
    }

    // Commit transaction
    $conn->commit();

    // Success message and redirection
    echo "<span style='background-color: #74f7c7; color: darkgreen; font-style: italic; font-size: 16px; height: 40px; line-height: 40px; padding: 5px 10px; margin-bottom: 10px;'>Patient Clinical Information and Toxicology Data Updated Successfully</span>";
    header("Refresh: 2; url=labdar.php");
    exit;
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $errorMessage = "Transaction failed: " . $e->getMessage();
}

// Close statements and connection
if (isset($lab_stmt)) $lab_stmt->close();
if (isset($tox_stmt)) $tox_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Labdar Processing</title>
</head>
<body>
    <?php if (isset($errorMessage)) : ?>
        <p style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php endif; ?>
</body>
</html>