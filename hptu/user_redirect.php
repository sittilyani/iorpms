<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box;}
        body {font-family: Arial, sans-serif; background-color: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh;}
        .redirect {text-align: center; background-color: white; padding: 40px; border-radius: width: 40%; 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);}
        .redirect img {max-width: 100%; height: auto; margin-bottom: 20px;}

        .redirect p {font-size: 16px; color: #555; margin: 10px 0;}
        .or-text {font-weight: bold; margin: 20px 0; color: #777;}
        .button-container {margin-top: 30px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;}
        .btn {padding: 12px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold; transition: all 0.3s ease; display: inline-block;}
        .btn-login {background-color: #4CAF50; color: white;}
        .btn-login:hover {background-color: #45a049;}
        .btn-back {background-color: #2196F3; color: white;}
        .btn-back:hover {background-color: #0b7dda;}
    </style>
</head>
<body>
    <div class="redirect">
        <img src="assets/images/warning-404-removebg-preview.png" width="402" height="212" alt="Warning">

        <p class="warning-text" style="color: #d32f2f; font-size: 28px; font-weight: bold; margin-bottom: 15px;">You are not allowed to access this page</p>
        <p>Please contact your administrator!</p>
        <p class="or-text">OR</p>

        <div class="button-container">
            <a href="javascript:history.back()" class="btn btn-back">Go Back</a>
            <a href="public/login.php" class="btn btn-login">Login</a>
        </div>
    </div>
</body>
</html>