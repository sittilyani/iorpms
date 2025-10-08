<<?php
session_start();
require_once '../includes/footer.php';
require_once '../includes/header.php';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <meta name="generator" content="PhpSpreadsheet, https://github.com/PHPOffice/PhpSpreadsheet">
            <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
            <link rel="stylesheet" href="stylep7.css" type="text/css">

    </head>

    <body>
<style>
@page { margin-left: 0.7in; margin-right: 0.7in; margin-top: 0.75in; margin-bottom: 0.75in; }
body { margin-left: 0.7in; margin-right: 0.7in; margin-top: 0.75in; margin-bottom: 0.75in; }
</style>
        <table border="0" cellpadding="0" cellspacing="0" id="sheet0" class="sheet0 gridlines">
                <col class="col0">
                <col class="col1">
                <col class="col2">
                <col class="col3">
                <col class="col4">
                <col class="col5">
                <col class="col6">
                <col class="col7">
                <col class="col8">
                <col class="col9">
                <col class="col10">
                <tbody>
                    <tr class="row0">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 null style3" colspan="1">

                                       <img src="../assets/images/KenyaEmblem.JPG" width="201" height="191" alt="">
                        </td>
                        <td class="column5 style1 null style3" colspan="7">
                            <h2><b>MEDICALLY ASSISTED THERAPY CONTROLLED DRUGS CONSUMPTION REQUEST AND REPORT FORM</b></h2>
                        </td>
                        <td class="column9 style1 null style2" colspan="2">
                            <b>FORM P7</b>
                        </td>
                    </tr>
                    <tr class="row1">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 s style3" colspan="5">Facility:</td>
                        <td class="column6 style1 s style2" colspan="5">County:</td>
                    </tr>
                    <tr class="row2">
                        <td class="column0">&nbsp;</td>
                       <td class="column6 style1 s style2" colspan="4">
                        <?php
                            if (isset($_SESSION['current_facilityname'])) {
                                echo htmlspecialchars($_SESSION['current_facilityname']);
                            } else {
                                echo "No Facility Set";
                            }
                        ?></td>
                        <td class="column6 style1 s style2" colspan="4">
                        <?php
                            if (isset($_SESSION['current_countyname'])) {
                                echo htmlspecialchars($_SESSION['current_countyname']);
                            } else {
                                echo "No countyname Set";
                            }
                        ?>
                        </td>
                    </tr>
                    <tr class="row3">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style24 s style25" colspan="2">Beginning Date</td>
                        <td class="column3 style26 s style27" colspan="3">
                             <?php
                                // Get the beginning date of the current month
                                $beginningOfMonth = date('Y-m-01');

                                // Output the input field with the beginning date as the default value and set as readonly
                                echo "<input type='text' name='beginning_date' id='beginning_date' value='$beginningOfMonth' readonly>";
                                ?>
                        </td>
                        <td class="column6 style24 s style25" colspan="3">Ending Date</td>
                        <td class="column9 style28 s style28" colspan="2">
                                <?php
                                // Get the current year and month and end of the month
                                $currentYear = date('Y');
                                $currentMonth = date('m');

                                // Get the number of days in the current month
                                $numberOfDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

                                // Construct the end date of the current month
                                $endOfMonth = date('Y-m-' . $numberOfDaysInMonth);

                                // Output the input field with the end date as the default value and set as readonly
                                echo "<input type='text' name='end_date' id='end_date' value='$endOfMonth' readonly>";
                                ?>
                        </td>
                    </tr>
                    <tr class="row4">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style5 s style8" colspan="2">Active on Methadone</td>
                        <td class="column3 style17 s style18" colspan="2">
                                <?php
                            // Include the database connection file
                            include_once('../includes/config.php');

                            // Define the start and end dates for the last month
                            $startDate = date('Y-m-01', strtotime('-1 month')); // First day of last month
                            $endDate = date('Y-m-t', strtotime('-1 month'));    // Last day of last month

                            // Define the SQL query to count active patients on methadone within the last month
                            $query = "SELECT COUNT(DISTINCT p.mat_id) AS count
                                        FROM patients p
                                        INNER JOIN pharmacy d ON p.mat_id = d.mat_id
                                        WHERE p.current_status = 'active'
                                        AND d.drugname = 'methadone'
                                        AND d.visitDate BETWEEN '$startDate' AND '$endDate'";

                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                // Fetch the count
                                $row = $result->fetch_assoc();
                                $count = $row['count'];

                                // Output the count
                                echo $count;
                            } else {
                                echo "0"; // If no active patients on methadone found within the last month, display 0
                            }
                            ?>

                        </td>
                        <td class="column5 style8 s style6" colspan="2">Active of Buprenorphine</td>
                        <td class="column7 style5 s style6" colspan="2">
                               <?php
                                // Include the database connection file
                                include_once('../includes/config.php');

                                // Define the date range for the last 6 days
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');

                                // Define the SQL query to count active patientss on methadone within the last 6 days
                                $query = "SELECT COUNT(*) AS count
                                                    FROM patients p
                                                    INNER JOIN pharmacy d ON p.mat_id = d.mat_id
                                                    WHERE p.current_status = 'active'
                                                    AND d.drugname = 'buprenorphine'
                                                    AND d.visitDate BETWEEN '$startDate' AND '$endDate'";

                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                        // Fetch the count
                                        $row = $result->fetch_assoc();
                                        $count = $row['count'];

                                        // Output the count
                                        echo $count;
                                } else {
                                        echo "0"; // If no active patientss on methadone found within the last 6 days, display 0
                                }
                                ?>
                        </td>
                        <td class="column9 style9 s">Active on Naltrexone</td>
                        <td class="column10 style7 null">
                                <?php
                                // Include the database connection file
                                include_once('../includes/config.php');

                                // Define the date range for the last 6 days
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');

                                // Define the SQL query to count active patientss on methadone within the last 6 days
                                $query = "SELECT COUNT(*) AS count
                                                    FROM patients p
                                                    INNER JOIN pharmacy d ON p.mat_id = d.mat_id
                                                    WHERE p.current_status = 'active'
                                                    AND d.drugname = 'naltrexone'
                                                    AND d.visitDate BETWEEN '$startDate' AND '$endDate'";

                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                        // Fetch the count
                                        $row = $result->fetch_assoc();
                                        $count = $row['count'];

                                        // Output the count
                                        echo $count;
                                } else {
                                        echo "0"; // If no active patientss on methadone found within the last 6 days, display 0
                                }
                                ?>
                        </td>
                    </tr>
                    <tr class="row5">
                        <td class="column0 style10 null">#</td>
                        <td class="column1 style11 s">DRUG PRODUCT</td>
                        <td class="column2 style12 s">&nbsp;Basic Pack Size</td>
                        <td class="column3 style16 s" style="white-space: nowrap;">&nbsp;&nbsp;Beginning Balance</td>
                        <td class="column4 style16 s" style="white-space: nowrap;">&nbsp;&nbsp;Quantity Received this period</td>
                        <td class="column5 style12 s" style="white-space: nowrap;">&nbsp;Total Quantity dispensed in themonth</td>
                        <td class="column6 style12 s">&nbsp;Losses</td>
                        <td class="column7 style12 s">&nbsp;Adjustments</td>
                        <td class="column8 style12 s" style="white-space: nowrap;">&nbsp;Physical Count at store</td>
                        <td class="column9 style12 s">&nbsp;Days out of stock at the store</td>
                        <td class="column10 style19 s" style="white-space: nowrap;">&nbsp;Quantity required for RESUPPLY (Continuing patientss)</td>
                    </tr>
                    <tr class="row6">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Methadone </td>
                        <td class="column2 style20 s">
                            <input type="text" name="bp1" id="bp1" value="1000 mL" readonly>
                        </td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/MethadoneBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/MethadoneRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Methadonedisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l1" id="l1" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad1" id="ad1" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps1" id="ps1" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockmethadone.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row7">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Buprenorphine 2mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp1" id="bp1" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Buprenorphine2mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Buprenorphine2mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Buprenorphine2mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l2" id="l2" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad2" id="ad2" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps2" id="ps2" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockbuprenorphine2mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row8">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Buprenorphine 4mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp2" id="bp2" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Buprenorphine4mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Buprenorphine4mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Buprenorphine4mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l3" id="l3" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad3" id="ad3" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps3" id="ps3" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockbuprenorphine4mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row9">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Buprenorphine 8mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp3" id="bp3" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Buprenorphine8mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Buprenorphine8mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Buprenorphine8mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l4" id="l4" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad4" id="ad4" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps4" id="ps4" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstockbuprenorphine8mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row10">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone tabs 50mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp5" id="bp5" value="7 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Naltrexone50mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Naltrexone50mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Naltrexone50mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l5" id="l5" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad5" id="ad5" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps5" id="ps5" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexone50mg.php'; ?></center></td>
                        <td class="column10 style20 s">-<input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row11">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone tabs 100mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp6" id="bp6" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Naltrexone100mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Naltrexone100mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Naltrexone100mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l6" id="l6" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad6" id="ad6" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps6" id="ps6" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexone100mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row12">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone tabs 150mg </td>
                        <td class="column2 style20 s"><input type="text" name="bp7" id="bp7" value="10 tabs blister pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/Naltrexone150mgBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/Naltrexone150mgRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/Naltrexone150mgdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l7" id="l7" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad7" id="ad7" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps7" id="ps7" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexone150mg.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row13">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style14 s">Naltrexone Implant</td>
                        <td class="column2 style20 s"><input type="text" name="bp8" id="bp8" value="1 implant pack" readonly></td>
                        <td class="column3 style20 s"><?php include '../countsFormP7/NaltrexoneImplantBB.php'; ?></td>
                        <td class="column4 style20 s"><?php include '../countsFormP7/NaltrexoneImplantRcvd.php'; ?></td>
                        <td class="column5 style20 s"><?php include '../countsFormP7/NaltrexoneImplantdisp.php'; ?></td>
                        <td class="column6 style20 s"><input type="number" name="l8" id="l8" value=""></td>
                        <td class="column7 style20 s"><input type="number" name="ad8" id="ad8" value=""></td>
                        <td class="column8 style20 s"><input type="number" name="ps8" id="ps8" value=""></td>
                        <td class="column9 style20 s"><center><?php include '../countsFormP7/outofstocknaltrexoneImplant.php'; ?></center></td>
                        <td class="column10 style20 s"><input type="number" name="qrfres" id="qrfres" value=""></td>
                    </tr>
                    <tr class="row14">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style15 s style15" colspan="10" rowspan="2">Comments (Including explanation of losses and adjustments): TEXTBOXAREA</td>
                    </tr>
                    <tr class="row15">
                        <td class="column0">&nbsp;</td>
                    </tr>
                    <tr class="row16">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 s style3" colspan="2">Submitted By:</td>
                        <td class="column3 style21 s style22" colspan="2"><input type="text" name="matpharm1" id="matpharm1" value=""></td>
                        <td class="column5 style21 s style22" colspan="2"><input type="text" name="sign-in" id="sign-in" value=""></td>
                        <td class="column7 style21 s style22" colspan="2"><input type="number" name="mobile" id="mobile" value=""></td>
                        <td class="column9 style21 s style22" colspan="2"><input type="date" name="date1" id="date1" value=""></td>
                    </tr>
                    <tr class="row17">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 null style3" colspan="2"></td>
                        <td class="column3 style5 s style6" colspan="2">MAT Pharmacist in charge </td>
                        <td class="column5 style1 s style3" colspan="2">Signature</td>
                        <td class="column7 style1 s style3" colspan="2">Mobile Phone</td>
                        <td class="column9 style1 s style3" colspan="2">Date</td>
                    </tr>
                    <tr class="row18">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 s style3" colspan="2">Reviwed By:</td>
                        <td class="column3 style21 s style22" colspan="2"><input type="text" name="matpharm" id="matpharm" value=""></td>
                        <td class="column5 style21 s style22" colspan="2"><input type="text" name="sign-in" id="sign-in" value=""></td>
                        <td class="column7 style21 s style22" colspan="2"><input type="number" name="mobile" id="mobile" value=""></td>
                        <td class="column9 style21 s style22" colspan="2"><input type="date" name="date1" id="date1" value=""></td>
                    </tr>
                    <tr class="row19">
                        <td class="column0">&nbsp;</td>
                        <td class="column1 style1 null style3" colspan="2"></td>
                        <td class="column3 style5 s style6" colspan="2">Pharmacist in charge </td>
                        <td class="column5 style1 s style3" colspan="2">Signature</td>
                        <td class="column7 style1 s style3" colspan="2">Mobile Phone</td>
                        <td class="column9 style1 s style3" colspan="2">Date</td>
                    </tr>
                </tbody>
        </table>
    </body>
</html>