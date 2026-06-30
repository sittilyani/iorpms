<?php
   include '../includes/header.php';
   include '../includes/footer.php';

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drug category</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
           .row{
               margin-top:  70px;
           }

    </style>
    <style>
            .row{
                margin-left: 40px;
            }

    </style>

</head>
<body>

<div class="row">
<div class="page-header centered clearfix"> <h2 class="pull-left">Drug Category</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">

        </div>
        <div class="col-md-6">
           <p>Please fill this form to capture drug category details.</p>
            <form action="addcategory_process.php" method="post">
                <label for="catname">Drug Category:</label>
                <input type="text" name="catname" placeholder="add category name here" required >


                <div class="form-group">
                    <label>Category description</label>
                    <textarea type="text" name="description" class="form-control" palceholder="give brief description for the category"></textarea>
                    <span class="help-block"></span>
                </div>

                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Add category Details">
                    <input type="reset" class="btn btn-default" value="Reset">
                </div>
            </form>
            <br>
            <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p>

        </div>
<!--         <div class="col-md-3">
                <div class="centered">
                    <img src="upload/medicallogo.jpg" style=" margin-top: 70px;width:300px;height:320px;"/>
                </div>

            </div> -->
    </div>

<div class="page-header centered clearfix"></div>
</div>
</body>
</html>
