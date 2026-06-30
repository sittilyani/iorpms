<?php
include '../includes/config.php';
require '../includes/email_alert.php';

$three_months = date('Y-m-d', strtotime('+3 months'));
$one_month = date('Y-m-d', strtotime('+1 month'));

$sql = "SELECT drug_name, batch_no, expiry_date, facilityname, quantity
        FROM stock_entries
        WHERE expiry_date BETWEEN CURDATE() AND '$one_month'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $body = "<h2>Near Expiry Alert</h2><table border='1' cellpadding='5' cellspacing='0'>
             <tr><th>Drug</th><th>Batch</th><th>Expiry</th><th>Qty</th><th>Facility</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $body .= "<tr><td>{$row['drug_name']}</td><td>{$row['batch_no']}</td>
                  <td>{$row['expiry_date']}</td><td>{$row['quantity']}</td>
                  <td>{$row['facilityname']}</td></tr>";
    }
    $body .= "</table>";

    $admins = $conn->query("SELECT email FROM tblusers WHERE userrole = 'Admin' OR userrole LIKE '%County%'");
    while ($admin = $admins->fetch_assoc()) {
        send_email($admin['email'], "Near Expiry Drugs Alert", $body);
    }
}
?>