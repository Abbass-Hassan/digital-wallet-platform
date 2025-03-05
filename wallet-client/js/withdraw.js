document.getElementById('withdrawForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Retrieve the JWT from localStorage
    const token = localStorage.getItem('jwt');
    if (!token) {
        // If no token is found, redirect to login
        window.location.href = 'login.html';
        return;
    }

    const withdrawAmount = parseFloat(document.getElementById('withdrawAmount').value);
    if (isNaN(withdrawAmount) || withdrawAmount <= 0) {
        alert("Please enter a valid withdrawal amount.");
        return;
    }

    axios.post(
        '/digital-wallet-platform/wallet-server/user/v1/withdraw.php',
        { amount: withdrawAmount },
        {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        }
    )
    .then(function(response) {
        if (response.data.error) {
            alert(response.data.error);
        } else {
            window.location.href = "dashboard.html";
        }
    })
    .catch(function(error) {
        console.error("Error during withdrawal:", error);
        alert("An error occurred. Please try again later.");
    });
});
