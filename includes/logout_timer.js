
// Start the timer when the page is loaded or when the user logs in
var inactivityTimeout;

function startInactivityTimer() {
    inactivityTimeout = setTimeout(logout, 20 * 60 * 1000); // 20 minutes in milliseconds
}

// Reset the timer whenever there is user activity
function resetTimer() {
    clearTimeout(inactivityTimeout);
    startInactivityTimer();
}

// Logout function
function logout() {
    // Redirect the user to the logout page
    window.location.href = '../public/signout.php';
}

// Start the timer initially
startInactivityTimer();

// Reset the timer on user activity
document.addEventListener('mousemove', resetTimer);
document.addEventListener('keypress', resetTimer);
