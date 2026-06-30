<?php
  include "../includes/config.php";
  include "../includes/footer.php";
  include "../includes/header.php";

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LabDAR</title>

    <style>
         .grid-container{
             display: grid;
             grid-template-columns: repeat(5, 1fr);
             background-color: #99CCFF;
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
      <h2>Laboratory Daily Activity Register</h2>
            <form action="dailyar.php" method="post" class="post">
              <div class="grid-container">
                  <div class="grid-item">
                      <label for="visitDate">Visit Date</label><br>
                      <input type="text" name="visitDate"><br>

                      <label for="mat_id">MAT ID</label><br>
                      <input type="text" name="mat_id"> <br>
                      <label for="clientName">First Name</label><br>
                      <input type="text" name="clientName"> <br>

                      <label for="lname">Second Name</label><br>
                      <input type="text" name="lname"> <br>

                      <label for="sname">Sur Name</label><br>
                      <input type="text" name="sname"> <br>




                  </div>
                  <div class="grid-item">
                       <!-- Tp pick gender from table tblgender -->
                      <label for="sex">Gender</label> <br>
                      <select name="sex" class="form-control">
                        <option value="male" >Male</option>
                        <option value="female">Female</option>
                        <option value="transgender">Transgender</option>
                        <option value="other">Other</option>
                      </select> <br>

                      <label for="age">Age</label><br>
                      <input type="number" name="age"><br>

                        <label for="marital_status">Marital Status</label><br>
                        <select name="marital_status" class="form-control">
                            <option value="single" >Single</option>
                            <option value="married_monogamous">Married (Monogamous)</option>
                            <option value="married_polygamous">Married (Polygamous)</option>
                            <option value="separated_divorced">Separated/Divorced</option>
                            <option value="cohabiting">Cohabiting</option>
                        </select> <br>

                      <label for="type_client">Type of Client</label> <br>
                      <select name="type_client" class="form-control">
                        <option value="new" >New</option>
                        <option value="re_induction">Re-Induction</option>
                        <option value="routine">Routine</option>
                        <option value="weaned">Weaned Off</option>
                      </select> <br>

                      <label for="mode_drug_use">Mode of Drug Use</label><br>
                      <select name="mode_drug_use" class="form-control">
                        <option value="pwud" >PWUD</option>
                        <option value="pwid">PWID</option>
                      </select> <br>



                  </div>
                  <div class="grid-item">
                      <label for="hiv_status">Treatment Stage</label> <br>
                      <select name="hiv_status" class="form-control">
                        <option value="positive" >Positive</option>
                        <option value="negative">Negative</option>
                        <option value="not_done">Not Done</option>
                        <option value="not_applicable">Not Applicable</option>
                      </select> <br>
                      <label for="hbv_status">HBV Status</label> <br>
                      <select name="hbv_status" class="form-control">
                        <option value="positive" >Positive</option>
                        <option value="negative">Negative</option>
                        <option value="not_done">Not Done</option>
                        <option value="not_applicable">Not Applicable</option>
                      </select> <br>

                      <h3 style="color: red;">Other Drug Use Detected</h3>

                      <label for="amphetmaine">Amphetamine</label> <br>
                      <select name="amphetamine" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>

                      <label for="metamphetmaine">Metamphetamine</label> <br>
                      <select name="metamphetamine" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>




                  </div>
                  <div class="grid-item">
                      <label for="morphine">Morphine</label> <br>
                      <select name="morphine" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>

                      <label for="barbiturates">Barbiturates</label> <br>
                      <select name="barbiturates" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>

                      <label for="cocaine">Cocaine</label> <br>
                      <select name="cocaine" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>

                      <label for="codeine">Codeine</label> <br>
                      <select name="codeine" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>

                      <label for="marijuana">Marijuana</label> <br>
                      <select name="marijuana" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>


                  </div>
                  <div class="grid-item">
                       <label for="amitriptyline">Amitriptyline</label> <br>
                      <select name="amitriptyline" class="form-control">
                        <option value="yes" >Yes</option>
                        <option value="no">No</option>
                        <option value="na">Not Done</option>
                      </select> <br>

                      <label for="lab_notes">Laboratory Notes</label> <br>
                      <textarea name="lab_notes" id="lab_notes" cols="30" rows="5"></textarea>
                      <label for="lab_officer_name">Lab Officer Name</label> <br>
                      <input type="text" name="lab_officer_name"> <br>
                      <label for="next_appointment">Next Appointment Date</label> <br>
                      <input type="date" name="next_appointment">  <br>
                      <button class="submit" id="btn-submit">Submit</button>
                  </div>
            </div>
        </form>
</body>
</html>