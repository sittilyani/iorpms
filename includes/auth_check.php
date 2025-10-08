<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Display an error message and redirect after 5 seconds
    echo "
    <html>
    <head>
        <title>Access Denied</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                margin-top: 20%;
            }
            .error-message {
                color: red;
                font-size: 20px;
                font-weight: bold;
            }
        </style>
        <script>
            // Redirect to the index page after 5 seconds
            setTimeout(function() {
                window.location.href = '/public/index.php';
            }, 5000);
        </script>
    </head>
    <body>
        <div class='error-message'>
            Access Denied: Please contact the administrator.<br>
            Redirecting to the login page in 5 seconds...
        </div>
    </body>
    </html>
    ";
    exit; // Stop further script execution
}
?>
