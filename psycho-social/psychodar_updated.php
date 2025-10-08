<?php
session_start();
include "../includes/config.php";

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}


// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$therapists_initials = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $therapists_initials = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PsychoDAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>

    <style>
         .form{
             display: grid;
             grid-template-columns: repeat(5, 1fr);
             background-color: #99FFBB;
             margin: 0 50px;
             padding: 20px;

         }
          input, .form-control, textarea{
              width: 80%;
              height: 30px;
              margin-bottom: 15px;
              margin-top: 10px;
          }

           textarea{
              width: 80%;
              margin-bottom: 15px;
              margin-top: 10px;
              height: 60px;
          }
          label{
              font-weight: bold;
              width: 80%;
          }
          h2{
              color: #2C3162;
              margin-top: 10px;
              margin-left: 50px;
          }
          #btn-submit{
              width: 80%;
              color: white;
              background-color: #2C3162;
              height: 35px;
              border-radius: 5px;
              border: none;
              cursor: pointer;
          }
           .readonly-input{
              background-color: #E8E8E8;
              cursor: not-allowed;
           }


            .checkbox-label {
                font-size: 14px;
                font-weight: bold;
                display: block;
                margin-bottom: 0;
            }

            .check-box-input {
                margin-right: 0; /* Spacing between checkbox and label text */
                height: 14px;
            }
            .checkbox-container {
                display: grid;
                grid-template-columns: repeat(5, 1fr); /* Two equal columns */
                gap: 8px; /* Space between items */
                margin-top: 10px;
                background-color: yellow;
                width: 300px;
            }

            .checkbox-container label {
                font-size: 14px;
                display: block;
                font-weight: normal;
                align-items: center;
            }
             input[check-box]{
              width: 10px;
              height: 10px;
              margin-bottom: 5;
          }

    </style>
</head>
<body>
    <div class="content-main">
      <h2>Psycho-social Daily Activity Register</h2>
            <form action="psychodar_process.php" method="post" class="post">

                  <div class="form-group">
                      CLIENT BIODATA 
                      <label for="visitDate">Visit Date</label>
                      <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                  </div>
                  <div class="form-group">

                       <!-- Tp pick gender from table tblgender -->
                      <label for="follow_up">Follow Up</label> 
                      <select name="follow_up" class="form-control">
                        <option value="initial" >Initial</option>
                        <option value="follow_up_1">Follow Up 1</option>
                        <option value="follow_up_2">Follow Up 2</option>
                        <option value="follow_up_3">Follow Up 3</option>
                        <option value="follow_up_4">Follow Up 4</option>
                        <option value="follow_up_5">Follow Up 5</option>
                        <option value="follow_up_6">Follow Up 6</option>
                        <option value="follow_up_7">Follow Up 7</option>
                        <option value="follow_up_8">Follow Up 8</option>
                        <option value="follow_up_other">Follow Up Other</option>
                      </select> 
                  </div>
                  <div class="form-group">

                        <label for="mat_id">MAT ID</label>
                        <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?>">
                    </div>
                  <div class="form-group">
                        <label for="clientName">Client Name</label>
                        <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?>">
                         <input type="hidden" name="dob" value="<?php echo isset($currentSettings['dob']) ? $currentSettings['dob'] : ''; ?>">
                    </div>
                  <div class="form-group">
                         <label for="sex">Gender</label>
                        <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? $currentSettings['sex'] : ''; ?>">
                  </div>
                  <div class="form-group">
                      <label for="age">Age</label>
                        <input type="number" name="age" class="readonly-input" readonly value="<?php echo $currentSettings['age']; ?>">
                    </div>

                  SOCIAL HISTORY
                  <div class="form-group">
                    <label for="date_of_intake">a. Date of Intake Interview</label>
                        <input type="text" name="date_of_intake" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>">
                    </div>
                  <div class="form-group">
                        <label for="marital_status">b. Marital Status</label>
                        <select name="marital_status" class="form-control">
                            <option value="single" >Single</option>
                            <option value="married_monogamous">Married</option>
                            <option value="remarried">Remarried</option>
                            <option value="divorced">Divorced</option>
                            <option value="separated">Separated</option>
                            <option value="never_married">Never Married</option>
                            <option value="widowed">Widowed</option>
                            <option value="Other">Other Specify</option>
                        </select> 
                    </div>
                  <div class="form-group">
                      <label for="other_marital_status">Other (Specify)</label> 
                      <input type="text" name="other_marital_status"> 
                   </div>
                  <div class="form-group">
                      <label for="living_arrangements">c. How would you describe your usual living arrangements in the past three months?</label> 
                      <select name="iving_arrangements" class="form-control">
                            <option value="stable arrangement" >Stable Arrangement</option>
                            <option value="non_stable arrangement" >Non-stable Arrangement</option>
                            <option value="married_monogamous">Married</option>
                      </select> 
                   </div>
                  <div class="form-group">
                      <!--Checkbox options-->
                      <label for="usual_arrangements" class="checkbox-label">d. Usual living arrangements in the past three months? (Select all that apply)</label>
                        <div class="dropdown-container">
                            <select name="living_arrangements[]" id="living_arrangements" multiple>
                                <option value="family">Family House</option>
                                <option value="friends">Friend's House</option>
                                <option value="usstreets">Streets</option>
                                <option value="abandoned">Abandoned buildings</option>
                                <option value="upublic">Public areas</option>
                                <option value="parks">Parks</option>
                                <option value="bus">Bus stations</option>
                                <option value="utunnels">Tunnels</option>
                                <option value="sites">Injection Sites</option>
                                <option value="others">Other Specify</option>
                            </select>
                        </div>
                  </div>
                  <div class="form-group">
                      <label for="accomodation">Accomodation/Residence</label>
                      <select name="accomodation" class="form-control" required>
                        <option value="stable" >Stable</option>
                        <option value="unstable">Unstable</option>
                        <option value="not_applicable">Not Applicable</option>
                      </select> 
                    </div>
                  <div class="form-group">
                      <label for="dosage">Dosage</label>
                        <input type="text" name="dosage" class="readonly-input" readonly value="<?php echo $currentSettings['dosage']; ?>">
                   </div>
                  <div class="form-group">
                      <label for="employment_status">Employment Status</label>
                      <select name="employment_status" class="form-control">
                        <option value="skilled" >Skilled Employment</option>
                        <option value="unskilled">Unskilled Employment</option>
                        <option value="self">Self Employed</option>
                        <option value="unemployed">Unemployed</option>
                      </select> 
                    </div>
                  <div class="form-group">
                      <label for="rx_stage">Treatment Stage</label> 
                      <select name="rx_stage" class="form-control">
                        <option value="new_induction" >New Inducted</option>
                        <option value="re_induction">Re-introduced</option>
                        <option value="stabilization">Stabilization</option>
                        <option value="maintainance">Maintainance</option>
                        <option value="cessation">Cessation</option>
                        <option value="weaned">Weaned Off</option>
                      </select> 
                      <label for="psycho_issues">Psycho-social Issues</label>
                      <textarea name="psycho_issues" id="" cols="30" rows="5"></textarea>

                  </div>
                  <div class="form-group">
                      <label for="psycho_interventions">Psycho-social Interventions</label> 
                      <select name="psycho_interventions" class="form-control">
                        <option value="individual_therapy" >Individual Therapy</option>
                        <option value="couple_therapy">Couple Therapy</option>
                        <option value="group_therapy">Group Therapy</option>
                        <option value="family_therapy">Family Therapy</option>
                        <option value="psycho_education">Psycho Education</option>
                        <option value="crisis_management">Crisis/Conflict Management</option>
                        <option value="none">None</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label for="reintegration_status">Reintegration Status</label> 
                      <select name="reintegration_status" class="form-control">
                        <option value="family_reintegration" >Family Reintegration</option>
                        <option value="employment_reintegration">Employment Reintegration</option>
                        <option value="housing_reintegration">Housing Reintegration</option>
                        <option value="stable_reintegration">Stable Reintegration</option>
                        <option value="education_reintegration">Education Reintegration</option>
                        <option value="commmunity_reintegration">Community Reintegration</option>
                        <option value="legal_reintegration">Legal Reintegration</option>
                        <option value="health_reintegration">Health Reintegration</option>
                        <option value="peer_reintegration">Peer Support Reintegration</option>
                        <option value="cultural_reintegration">Cultural Reintegration</option>
                        <option value="none">None</option>
                      </select> 
                    </div>
                  <div class="form-group">
                      <label for="legal_issues">Legal/Criminal/Court Issues </label> 
                      <textarea name="legal_issues" id="legal_issues" cols="30" rows="5"></textarea>  
                      <label for="gbv_screen">Screened for GBV?</label> 
                      <select name="gbv_screen" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="not_applicable">Not Applicable</option>
                      </select> 
                    </div>
                  <div class="form-group">
                      <label for="gbv_support">Given GBV support?</label> 
                      <select name="gbv_support" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="not_applicable">Not Applicable</option>

                      </select> 

                  </div>
                  <div class="form-group">
                       <label for="linkage">Referral & Linkage Services</label> 
                      <select name="linkage" class="form-control">
                        <option value="education_support" >Education Programs</option>
                        <option value="legal_support">Legal Support</option>
                        <option value="community_support">Community Networks Support</option>
                        <option value="peer_support">Peer Support Networks</option>
                        <option value="family_support">Family Support Services</option>
                        <option value="rehabilitation">Rehabilitation</option>
                        <option value="mental_health_support">Mental Health</option>
                        <option value="medical_services">Medical services</option>
                        <option value="hiv_services">HIV services</option>
                        <option value="gbv_services">GBV Services</option> 
                        <option value="none">None</option>
                      </select>
                    </div>
                  <div class="form-group">
                      <label for="therapists_initials">Therapist's Name</label>
                      <input type="text" name="therapists_initials" value="<?php echo htmlspecialchars($therapists_initials); ?>" class="readonly-input readonly">
                    </div>
                  <div class="form-group">
                        <label for="therapists_notes" required>Therapist's Notes</label>
                        <textarea name="" id="" cols="30" rows="10"></textarea>
                   </div>
                  <div class="form-group">
                      <label for="next_appointment">Next Appointment Date</label> 
                      <input type="date" name="next_appointment" required>  
                      <button class="submit" id="btn-submit">Submit</button>
                  </div>

        </form>
      </div>
</body>
</html>