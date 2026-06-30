<?php


include '../includes/footer.php';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Patient</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 10px;
            background-color: none;
            padding: 20px;
            width: 840px;
            margin-left: auto;
            margin-right: auto;

        }

        .item1, .item2, .item3 {
            background-color: none;
            text-align: center;
            font-size: 14px;
            padding: 10px;
        }

        label, input {
            margin-top: 5px;
            height: 25px;
            font-size: 14px;

        }

        button[type="submit"] {
            background-color: blue;
            color: white;
            height: 30px;
            width: 100px;
            margin-top: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }

        h1 {
            margin-top: 20px;
            margin-left: 60px;
            font-size: 20px;
            color: #000099;
        }

        p {
            margin-top: 20px;
            margin-left: 60px;
            font-size: 16px;
        }
        .message-container {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<h1>Register New Patient</h1>

<p>Please enter details to register the client/patient</p>
<form action="add_patient.php" method="post">
<div class="grid-container">

    <div class="item1">
        <label for="mat_id">MAT ID</label> <br>
        <input name="mat_id" id="mat_id" required>
        <br>

        <label for="nat_id">National ID</label> <br>
        <input name="nat_id" id="nat_id" >
        <br>

        <label for="fname">First Name</label> <br>
        <input name="fname" id="fname" required>
        <br>

        <label for="lname">Last Name</label><br>
        <input name="lname" id="lname" >
        <br>

        <label for="sname">SurName</label> <br>
        <input name="sname" id="sname">
        <br>
    </div>

    <div class="item2">
        <label for="nname">Nick Name</label> <br>
        <input name="nname" id="nname">
        <br>

        <label for="residence">Residence</label>  <br>
        <input name="residence" id="residence" required>
        <br>

        <label for="dob">Date of Birth</label>  <br>
        <input type="date" name="dob" id="dob" required>
        <br>

        <label for="doe">Date of Enrolment</label> <br>
        <input type="date" name="doe" id="doe" required>
        <br>

        <label for="cso">CSO Name</label> <br>
        <input name="cso" id="cso" required>
        <br>
    </div>

    <div class="item3">
        <label for="dosage">Dosage</label> <br>
        <input name="dosage" id="dosage" required>
        <br>

        <label for="phone">Phone</label> <br>
        <input name="phone" id="phone" >
        <br>

        <label for="sex">Sex:</label> <br>
        <select name="sex" id="sex" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
        </select>
        <br>

        <label for="status">Status:</label><br>
        <select name="status" id="status" required>
            <option value="active">Active</option>
            <option value="ltfu">Lost to follow Up</option>
            <option value="defaulted">Defaulted</option>
            <option value="weaned">Weaned Off</option>
            <option value="stopped">Stopped</option>
            <option value="dead">Dead</option>
            <option value="transout">Transfer Out</option>
            <option value="transit">Transit Pts</option>
        </select>
        <br>

        <label for="image">Add profile picture:</label>
        <input type="file" id="image" name="image" accept="image/png, image/jpeg" />
        <br>

        <button type="submit">SUBMIT</button>
    </div>
    </form>
</div>
         <?php
            // Check if the success message is set and not empty
            if (isset($successMessage) && !empty($successMessage)) {
                echo '<div class="message-container">' . $successMessage . '</div>';
            }
        ?>
</body>
</html>
