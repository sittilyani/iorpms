<?php
  include ("../includes/footer.php");
  include ("../includes/header.php");

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .grid-container{
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            margin-top: 10px;
            grid-gap: 10px;
            margin-left: 40px;
            padding: 20px;
        }
         h4{
             margin-left: 30px;
             font-size: 18px;
             color: red;
             margin-left: 40px;

         }
          a{
              font-size: 16px;
              font-weight: normal;
              color: #000099;
          }

    </style>
</head>
<body>
     <div class="grid-container">
        <div class="grid-item">
             <h4>Database and Users</h4>
             <ul><a href="initialsettings.php">Initial Facility Settings</a></ul>
             <ul><a href="../backup/backup.php">BackUp Database</a></ul>
             <ul><a href="../views/userslist.php">Users' List'</a></ul>
             <ul><a href="../public/user_registration.php">Register User</a></ul>
             <ul><a href="../TableUpdates/update_table.php">Update Tables</a></ul>
        </div>
         <div class="grid-item">
             <h4>Pharmacy</h4>
             <ul><a href="../pages/add_drug_source.php">Add Drug Source</a></ul>
             <ul><a href="../pages/sourcelist.php">Drug Sources List View</a></ul>
             <ul><a href="../views/viewstocks.php">Stock List</a></ul>
             <ul><a href="../pages/stock_movement2.php">Add Stock</a></ul>
             <ul><a href="../processors/adddrug.php">New Drug</a></ul>
             <ul><a href="../views/druglist.php">Drug List</a></ul>
             <ul><a href="../processors/addcategory.php">New Category</a></ul>
             <ul><a href="../views/category_list.php">Category List</a></ul>
             <ul><a href="../processors/addFormulation.php">Add drug formulation</a></ul>

         </div>
         <div class="grid-item">
             <h4>Facility</h4>
             <!--<ul><a href="../pages/add_county.php">Add Counties</a></ul>
             <ul><a href="../pages/add_sub_county.php">Add Sub Counties</a> </ul>-->
             <ul><a href="../pages/add_facility.php">Add Facility</a></ul>
             <ul><a href="../pages/add_cso.php">Add CSO</a> </ul>



         </div>
         <div class="grid-item">
             <h4>Patient</h4>
             <ul><a href="../Psycho-social/add_clients.php">Register Patients</a></ul>
             <ul><a href="../pages/status.php">Add Current Patient Status</a></ul>
             <ul><a href="../pages/add_regimen.php">Add Regimen</a></ul>
             <ul><a href="../pages/add_regimen_type.php">Add Regimen Type</a></ul>
             <ul><a href="../pages/add_tb_status.php">Add TB Status</a></ul>
             <ul><a href="../pages/addreferral.php">Add Referral</a></ul>
             <ul><a href="../pages/add_gender.php">Add Gender/Sex</a></ul>
             <ul><a href="../pages/add_marital_status.php">Add Marital Status</a></ul>


         </div>
         <div class="grid-item">
             <h4>Other Settings</h4>
             <ul><a href="../pages/accompanment.php">Add accompanment type</a></ul>
             <ul><a href="../pages/add_hepb_status.php">Add Hepatitis B Status</a></ul>
             <ul><a href="../pages/add_hepc_status.php">Add Hepatitis C Status</a></ul>
             <ul><a href="../pages/add_hiv_status.php">Add HIV Status</a></ul>
             <ul><a href="../pages/add_other_status.php">Add other diseases</a></ul>
             <ul><a href="../photos/index.php">Add Photo</a></ul>
             <ul><a href="../fingerPrints/index.php">Register Finger Print</a></ul>



         </div>


      </div>

</body>
</html>