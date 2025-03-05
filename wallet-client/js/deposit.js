document.getElementById('depositForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Retrieve the JWT from localStorage
    const token = localStorage.getItem('jwt');
    if (!token) {
        // If no token is found, redirect to login
        window.location.href = 'login.html';
        return;
    }

    const depositAmount = parseFloat(document.getElementById('depositAmount').value);
    if (isNaN(depositAmount) || depositAmount <= 0) {
        alert("Please enter a valid deposit amount.");
        return;
    }

    // Include the JWT in the Authorization header
    axios.post(
        '/digital-wallet-platform/wallet-server/user/v1/deposit.php',
        { amount: depositAmount },
        {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        }
    )
    .then(function(response) {
        if (response.data.error) {
            alert(response.data.error);
            // If token is invalid or expired, you might force logout here:
            // if (response.data.error === 'Invalid or expired token') {
            //     localStorage.removeItem('jwt');
            //     window.location.href = 'login.html';
            // }
        } else {
            // If deposit is successful, redirect to dashboard or show success message
            window.location.href = "dashboard.html";
        }
    })
    .catch(function(error) {
        console.error("Error during deposit:", error);
        alert("An error occurred. Please try again later.");
    });
});
