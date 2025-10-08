const searchButton = document.getElementById('search-button');
const statusMessage = document.getElementById('status-message');

searchButton.addEventListener('click', () => {
    statusMessage.textContent = 'Scanning fingerprint...';
    $.ajax({
        url: `${API_URL}/capture`,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success' && response.fingerprint) {
                $.ajax({
                    url: 'search_fingerprint.php',
                    method: 'POST',
                    data: { fingerprint_template: response.fingerprint.template },
                    success: function(searchResult) {
                        if (searchResult.found) {
                            statusMessage.textContent = `Patient found: ${searchResult.patient_name}`;
                        } else {
                            statusMessage.textContent = 'No matching patient found.';
                        }
                    },
                    error: function() {
                        statusMessage.textContent = 'Error searching for patient.';
                    }
                });
            } else {
                statusMessage.textContent = 'Failed to capture fingerprint.';
            }
        },
        error: function() {
            statusMessage.textContent = 'Error capturing fingerprint.';
        }
    });
});