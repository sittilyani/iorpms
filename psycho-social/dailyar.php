<?php
  include "../includes/config.php";

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DailyAR</title>
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">

    <style>
         .form{
             display: grid;
             grid-template-columns: repeat(4, 1fr);
             background-color: #99FFBB;
             margin: 0 50px;
             padding: 20px;

         }
          input, select{
              width: 250px;
              height: 30px;
              margin-bottom: 15px;
              margin-top: 10px;
          }
          label{
              font-weight: bold;
          }
          h2{
              color: #2C3162;margin-top: 80px;
              margin-left: 50px;
          }
          #btn-submit{
              width: 250px;
              color: white;
              background-color: #2C3162;
              height: 35px;
              border-radius: 5px;
              border: none;
              cursor: pointer;
          }

    </style>
</head>
<body>
    <div class=content-main">
      <h2>Psycho-social Daily Activity Register</h2>
            <form action="dailyar.php" method="post" class="post">

                  <div class="form-group">
                      <label for="visitDate">Visit Date (b)</label><br>
                      <input type="text" name="visitDate"><br>

                      <label for="mat_id">MAT ID (c)</label><br>
                      <input type="text" name="mat_id"> <br>

                      <label for="age">Age (d)</label><br>
                      <input type="number" name="age"><br>

                      <!-- Tp pick gender from table tblgender -->
                      <label for="sex">Gender (e)</label> <br>
                      <select name="sex" class="form-control">
                        <option value="male" >Male</option>
                        <option value="female">Female</option>
                        <option value="transgender">Transgender</option>
                        <option value="other">Other</option>
                      </select> <br>

                        <label for="marital_status">Marital Status (f)</label><br>
                        <select name="marital_status" class="form-control">
                            <option value="single" >Single</option>
                            <option value="married_monogamous">Married (Monogamous)</option>
                            <option value="married_polygamous">Married (Polygamous)</option>
                            <option value="separated_divorced">Separated/Divorced</option>
                            <option value="cohabiting">Cohabiting</option>
                        </select> <br>
                  </div>
                  <div class="form-group">
                      <label for="hotspot">Hotspot/DIC (g)</label> <br>
                      <input type="text" name="hotspot"> <br>
                      <label for="accomodation">Accomodation/Residence (h)</label><br>
                      <select name="accomodation" class="form-control">
                        <option value="stable" >Stable</option>
                        <option value="unstable">Unstable</option>
                        <option value="not_applicable">Not Applicable</option>
                      </select> <br>

                      <label for="dosage">Dosage (i)</label><br>
                      <input type="text" name="dosage"> <br>

                      <label for="employment_status">Employment Status (j)</label><br>
                      <select name="employment_status" class="form-control">
                        <option value="skilled" >Skilled Employment</option>
                        <option value="unskilled">Unskilled Employment</option>
                        <option value="self">Self Employed</option>
                        <option value="unemployed">Unemployed</option>
                      </select> <br>

                      <label for="rx_stage">Treatment Stage (k)</label> <br>
                      <select name="rx_stage" class="form-control">
                        <option value="new_induction" >New Inducted</option>
                        <option value="re_induction">Re-introduced</option>
                        <option value="stabilization">Stabilization</option>
                        <option value="maintainance">Maintainance</option>
                        <option value="cessation">Cessation</option>
                        <option value="weaned">Weaned Off</option>
                      </select> <br>


                  </div>
                  <div class="form-group">
                      <label for="psycho_issues">Psycho-social Issues (l)</label><br>
                      <textarea name="psycho_issues" id="" cols="30" rows="5"></textarea><br>
                      <label for="psycho_interventions">Psycho-social Interventions</label> <br>
                      <select name="psycho_interventions" class="form-control">
                        <option value="individual_therapy" >Individual Therapy</option>
                        <option value="couple_therapy">Couple Therapy</option>
                        <option value="group_therapy">Group Therapy</option>
                        <option value="family_therapy">Family Therapy</option>
                        <option value="psycho_education">Psycho Education</option>
                        <option value="crisis_management">Crisis/Conflict Management</option>
                      </select> <br>

                      <label for="reintegration_status">Reintegration Status (m)</label> <br>
                      <select name="rx_stage" class="form-control">
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
                      </select> <br>

                      <label for="legal_issues">Legal/Criminal/Court Issues (n) </label> <br>
                      <textarea name="legal_issues" id="legal_issues" cols="30" rows="5"></textarea>  <br>

                  </div>
                  <div class="form-group">
                      <label for="gbv_screen">Screened for GBV? (o)</label> <br>
                      <select name="gbv_screen" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="not_applicable">Not Applicable</option>
                      </select>

                      <label for="gbv_support">Given GBV support? (p)</label> <br>
                      <select name="gbv_support" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="not_applicable">Not Applicable</option>

                      </select> <br>
                      <label for="linkage">Referral & Linkage Services (q)</label> <br>
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
                        <option value="gbv_services">GBV Services</option> <br>

                      </select> <br>

                      <label for="therapist_initials">Therapist Initials (r)</label> <br>
                      <input type="text" name="therapist_initials"> <br>
                      <label for="next_appointment">Next Appointment Date</label> <br>
                      <input type="date" name="next_appointment">  <br>
                      <button class="submit" id="btn-submit">Submit</button>
                  </div>
            </div>
        </form>
</body>
</html>