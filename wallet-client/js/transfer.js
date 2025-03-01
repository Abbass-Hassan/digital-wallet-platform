document.addEventListener("DOMContentLoaded", function() {
    const transferBtn = document.getElementById("transferBtn");

    transferBtn.addEventListener("click", function(e) {
        e.preventDefault();

        // Get the recipient email and transfer amount from the form.
        const recipientEmail = document.getElementById("recipientQuery").value.trim();
        const transferAmount = parseFloat(document.getElementById("transferAmount").value);

        if (!recipientEmail) {
            alert("Please enter a recipient email.");
            return;
        }
        if (isNaN(transferAmount) || transferAmount <= 0) {
            alert("Please enter a valid transfer amount.");
            return;
        }

        // Build request data.
        const data = {
            recipient_email: recipientEmail,
            amount: transferAmount
        };

        axios.post('/digital-wallet-platform/wallet-server/user/v1/transfer.php', data)
            .then(response => {
                if (response.data.error) {
                    alert(response.data.error);
                } else {
                    alert(response.data.message);
                    // Optionally refresh the dashboard or redirect.
                    window.location.href = 'dashboard.html';
                }
            })
            .catch(error => {
                console.error("Transfer error:", error);
                alert("An error occurred during the transfer. Please try again later.");
            });
    });
});
