<?php
session_start();
include('../includes/config.php');

?>

<?php
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div>" . $message . "</div>";
}

if (isset($_SESSION['successMessages'])) {
    $successMessages = $_SESSION['successMessages'];
    unset($_SESSION['successMessages']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy DAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <!-- Add your CSS styling here -->
    <style>


    </style>
</head>
<body>
    <?php if (isset($successMessages)): ?>
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <?php foreach ($successMessages as $success): ?>
                <p><?php echo htmlspecialchars((string)($success)); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <h2 style="color: #2D008A; ">Dispense with Pump</h2>

    <form id="searchForm" action="<?php echo htmlspecialchars((string)($_SERVER["PHP_SELF"])); ?>" method="GET">
    <div class="header">
        <label for="search">Search:</label>
        <input type="text" id="search" class="search-entry"name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search" class="search-input">
        <button type="button" onclick="cancelSearch()" class="cancel-input">Cancel</button> <!-- Add Cancel Button -->
        <a href="edit_dispensed_dose.php" style="background: red; color: #ffffff; text-decoration: none; height: 40px; border-radius: 5px; align-text: center;">Delete Dispensed Record</a>
    </div>

    </form>
    <!-- Display Data -->
<?php
// Handle search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM patients WHERE (mat_id LIKE '%$search%'
        OR mat_number LIKE '%$search%'
        OR clientName LIKE '%$search%'
        OR nickName LIKE '%$search%'
        OR dob LIKE '%$search%'
        OR age LIKE '%$search%'
        OR sex LIKE '%$search%'
        OR p_address LIKE '%$search%'
        OR peer_edu_name LIKE '%$search%'
        OR peer_edu_phone LIKE '%$search%'
        OR cso LIKE '%$search%'
        OR drugname LIKE '%$search%'
        OR dosage LIKE '%$search%'
        OR current_status LIKE '%$search%')
        AND current_status IN ('Active', 'LTFU', 'Defaulted')
        AND drugname = 'Methadone'";
// Pagination setup
$results_per_page = 10; // Number of results per page
$number_of_results = mysqli_num_rows(mysqli_query($conn, $sql)); // Total number of results
$number_of_pages = ceil($number_of_results / $results_per_page); // Total pages

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page, default to 1
$start_limit = ($current_page - 1) * $results_per_page; // Start limit for SQL query
$start_range = $start_limit + 1; // Start range for display
$end_range = min($start_limit + $results_per_page, $number_of_results); // End range for display

$sql .= " LIMIT $start_limit, $results_per_page"; // Append LIMIT to SQL query

// Fetch data from the database
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Display the data in a table
    echo "<table>
            <thead>
                <tr>
                <th style='width: 60px;'>ID</th>
                <th>MAT ID</th>
                <th>Client Name</th>
                <th style='width: 80px;'>Age</th>
                <th style='width: 80px;'>Sex</th>
                <th>Drug</th>
                <th style='width: 80px;'>Dosage</th>
                <th style='width: 120px;'>Current Status</th>
                <th style='width: 80px;'>History</th>
                <th>Action</th>
            </tr>
        </thead>
    <tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['p_id'] . "</td>
                <td>" . $row['mat_id'] . "</td>
                <td>" . $row['clientName'] . "</td>
                <td>" . $row['age'] . "</td>
                <td>" . $row['sex'] . "</td>
                <td style ='color: blue;'>" . $row['drugname'] . "</td>
                <td>" . $row['dosage'] . "</td>
                <td>" . $row['current_status'] . "</td>
                <td>
                    <center>
                    <a href='history.php?p_id=" . $row['p_id'] . "' style='font-size: 24px; color: brown;'><i class='fa fa-exclamation-circle'></i></a>
                    </center>

                </td>
                <td>
                    <a href='../pharmacy/view-missed.php?mat_id=" . $row['mat_id'] . "'>View</a> &#124;
                    <a href='dispensingData_pump.php?mat_id=" . urlencode((string)($row['mat_id'])) . "' class='js-dispense' data-mat='" . htmlspecialchars((string)($row['mat_id'])) . "' data-dose='" . (float)$row['dosage'] . "' data-appt='" . (strtotime((string)($row['next_appointment'])) < strtotime((string)(date('Y-m-d'))) ? 'overdue' : 'ok') . "'>DISPENSE</a> &#124;
                    <a href='dose_history.php?mat_id=" . urlencode((string)($row['mat_id'])) . "'>ADHERENCE</a> &#124;
                    <a href='multi_dispensing.php?mat_id=" . $row['mat_id'] . "'>MDD</a>
                    <a href='../referrals/referral.php?mat_id=" . $row['mat_id'] . "'Refer</a>
                </td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results found.</p>";
}

// Display range of current rows
echo "<div>Showing $start_range-$end_range of $number_of_results results</div>";

// Pagination links
$max_links = 5; // Maximum number of page links to display
$start_page = max(1, $current_page - floor($max_links / 2)); // Start page for links
$end_page = min($number_of_pages, $start_page + $max_links - 1); // End page for links

echo "<div>";
if ($current_page > 1) {
    $prev_page = $current_page - 1;
    echo "<a href='?page=$prev_page&search=$search'>Previous</a> ";
}

for ($page = $start_page; $page <= $end_page; $page++) {
    $active = $page == $current_page ? "style='font-weight:bold;'" : '';
    echo "<a href='?page=$page&search=$search' $active>$page</a> ";
}

if ($current_page < $number_of_pages) {
    $next_page = $current_page + 1;
    echo "<a href='?page=$next_page&search=$search'>Next</a> ";
}
echo "</div>";
?>

<div id="gate" style="display:none;position:fixed;inset:0;background:rgba(20,0,60,.55);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto">
  <div id="gateBox" style="background:#fff;border-radius:10px;max-width:520px;width:92%;margin:16px 0;border-top:6px solid #E41E39;padding:22px;font-family:Segoe UI,Arial,sans-serif">
    <h3 id="gateTitle" style="margin:0 0 8px;color:#2D008A"></h3><p id="gateBody"></p>
    <div id="gateBtns" style="text-align:right"></div></div></div>
<script>
(function(){
  var gate=document.getElementById('gate'),T=document.getElementById('gateTitle'),B=document.getElementById('gateBody'),BT=document.getElementById('gateBtns');
  // This page is shown inside an iframe that the menu stretches to the full page height, so a centred, fixed modal ends up
  // far down. Put it at the top of the part of the frame the user can actually see.
  function place(){var top=0;try{var fe=window.frameElement;if(fe){top=Math.max(0,-fe.getBoundingClientRect().top)}}catch(e){}document.getElementById('gateBox').style.marginTop=(top+16)+'px'}
  function btn(label,color,fn){var b=document.createElement('button');b.textContent=label;b.type='button';b.style.cssText='background:'+color+';color:#fff;border:0;border-radius:6px;padding:9px 18px;margin-left:8px;font-weight:600;cursor:pointer';b.onclick=fn;BT.appendChild(b)}
  function show(t,body,btns){T.textContent=t;B.innerHTML=body;BT.innerHTML='';btns.forEach(function(x){btn(x[0],x[1],x[2])});btn('Cancel','#888',function(){gate.style.display='none'});gate.style.display='flex';place()}
  document.querySelectorAll('.js-dispense').forEach(function(a){a.addEventListener('click',function(e){
    e.preventDefault();var href=a.href,mat=a.dataset.mat;
    fetch('pump_status.php').then(function(r){return r.json()}).then(function(s){
      if(!s.ready){
        
        show('Reverse Prime, then Prime and Calibrate first','The pump has not dispensed for '+(s.hours===null?'a long time':s.hours+' hours')+' (limit 12 hours). <b>Reverse Prime then Prime and calibrate first before you begin dispensing.</b>',
          [['1. Reverse Prime & Prime','#2D008A',function(){location.href='pump_reservoir.php'}],['2. Calibrate','#04B04B',function(){location.href='calibration.php'}]]);return}
      if(parseFloat(a.dataset.dose)<=0){show('No dose on record','This client has no dose set. Do you want to update the dose now?',[['Yes, update dose','#04B04B',function(){location.href='../clinician/update_dose.php?mat_id='+encodeURIComponent(mat)}],['No, continue','#2D008A',function(){location.href=href}]]);return}
      if(a.dataset.appt==='overdue'){show('Appointment overdue','This client has no valid next appointment. Update it now?',[['Yes, update appointment','#04B04B',function(){location.href='../appointments/update_appointments.php?mat_id='+encodeURIComponent(mat)}],['No, continue','#2D008A',function(){location.href=href}]]);return}
      location.href=href;
    }).catch(function(){location.href=href});
  })});
})();
</script>
<script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>

    <!-- Fingerprint auto-identify disabled: this unconditionally started a
         continuous scanner-polling loop (every ~2s) the moment the search
         page loaded, loading every registered fingerprint template and
         repeatedly hitting the scanner service in the background while the
         pharmacist was searching/dispensing - this was the cause of the
         page (and subsequent pump commands) feeling very slow.
         Uncomment to re-enable hands-free patient identification.
    <script src="../assets/js/fingerprint_listener.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            startFingerprintIdentifyLoop(true);
        });
    </script>
    -->

<script>
        function cancelSearch() {
            document.getElementById("search").value = ''; // Clear search input
            document.getElementById("searchForm").submit(); // Submit form to reset
        }

        // Real-Time Filtering
        document.getElementById("search").addEventListener("input", function() {
            setTimeout(function() {
                document.getElementById("searchForm").submit();
            }, 3000); // Submit form after a brief delay (e.g., 500 milliseconds)
        });
    </script>
    <script>
    // Function to remove the message container
    function hideMessageContainer() {
        var messageContainer = document.getElementById('message-container');
        if (messageContainer) {
            messageContainer.style.display = 'none';
        }
    }

    // Check if the message container exists and hide it after 5 seconds
    window.addEventListener('load', function() {
        var messageContainer = document.getElementById('message-container');
        if (messageContainer) {
            setTimeout(hideMessageContainer, 3000); // Hide message after 5 seconds (5000 milliseconds)
        }
    });
</script>
</body>
</html>
