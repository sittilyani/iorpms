<?php
            include '../includes/footer.php';
            include '../includes/header.php';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drug Formulation</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
            .form-group input[type="submit"],
            .form-group input[type="reset"] {
                background-color: blue;
                color: white;
                border: none;
                border-radius: 5px;
                padding: 10px;
                cursor: pointer;
                width: auto;
                transition: background-color 0.3s; /* Add a smooth transition effect */
                margin-bottom: 10px;
                margin-top: 10px;
            }

            .form-group input[type="submit"]:hover,
            .form-group input[type="reset"]:hover {
                background-color: green;
            }
             .row{
                 margin-top: 80px;
                 margin-left: 40px;
             }
            label, input{
                margin-bottom: 15px;
            }
            .form-group input[type="text"]{
                width: 300px;
            }
            .btn-primary {
            width: 300px;
        }
        h2{
            color: #000099;
            margin-left: 35px;
            font-size: 24px;
            font-weight: bold;
        }

    </style>
</head>
<body>

<div class="row">
<div class="page-header centered clearfix"> <h2 class="pull-left">Drug Formulation</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">

        </div>
        <div class="col-md-6">
           <p>Please fill this form to capture drug formulation details.</p>
            <form action="addformulation_process.php" method="post">
                <div class="form-group">
                   <b> <label for="fname">Formulation:</label> </b>
                    <input type="text" name="fname" class="form-control" placeholder="example sysrup, tablets etc"></input>
                </div>

                <div class="form-group">
                    <b><label>Description</label> </b>
                    <input type="text" name="description" class="form-control" placeholder="enter formulation notes"></input>
                    <span class="help-block"></span>
                </div>

                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Add formulation Details">
                    <input type="reset" class="btn btn-default" value="Reset">
                </div>
            </form>
            
            <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p>

        </div>

    </div>

<div class="page-header centered clearfix"></div>
</div>
</body>
</html>
