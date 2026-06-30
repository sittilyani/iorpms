<?php
session_start();

// Check if the user is not logged in and not on the login page
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        echo '<style>
                        body {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                height: 100vh;
                                margin: 0;
                        }
            </style>';
        echo '<div style="text-align: center; padding: 20px; border: 1px solid #ccc; background-color: #f8d7da; color: #721c24; border-radius: 5px; font-size: 20px;">
                        <p><strong>Ooooops!:</strong></p>
                        <p> You are not allowed to access this page.</p>
                        <p>Please contact your system administrator or login.</p>
                    </div>';

        echo '<script>
                        setTimeout(function() {
                                window.location.href = "../public/login.php";
                        }, 3000);
                    </script>';
        exit();
}
?>



