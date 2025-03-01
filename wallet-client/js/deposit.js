document.getElementById('depositForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const depositAmount = parseFloat(document.getElementById('depositAmount').value);
    if (isNaN(depositAmount) || depositAmount <= 0) {
        alert("Please enter a valid deposit amount.");
        return;
    }

    axios.post('/digital-wallet-platform/wallet-server/user/v1/deposit.php', { amount: depositAmount })
        .then(function(response) {
            if (response.data.error) {
                alert(response.data.error);
            } else {
                window.location.href = "dashboard.html";
            }
        })
        .catch(function(error) {
            console.error("Error during deposit:", error);
            alert("An error occurred. Please try again later.");
        });
});
