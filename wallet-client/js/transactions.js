document.addEventListener('DOMContentLoaded', function() {
    // Retrieve the JWT from localStorage
    const token = localStorage.getItem('jwt');
    if (!token) {
        // Redirect to login if no token is found
        window.location.href = 'login.html';
        return;
    }

    // Axios configuration with JWT in the Authorization header
    const axiosConfig = {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    };

    const filterBtn    = document.getElementById('filterBtn');
    const filterDate   = document.getElementById('filterDate');
    const typeSelect   = document.getElementById('typeSelect');
    const transactionsList = document.getElementById('transactionsList');

    let loggedInUserId = null; // We'll set this after we fetch from the server

    // Optionally load all transactions on page load
    fetchTransactions();

    filterBtn.addEventListener('click', function() {
        fetchTransactions();
    });

    function fetchTransactions() {
        // Build query parameters
        const params = new URLSearchParams();
        if (filterDate.value) {
            params.append('date', filterDate.value);
        }
        if (typeSelect.value) {
            params.append('type', typeSelect.value);
        }

        axios.get('/digital-wallet-platform/wallet-server/user/v1/get_transactions.php?' + params.toString(), axiosConfig)
            .then(response => {
                if (response.data.error) {
                    transactionsList.innerHTML = `<p>Error: ${response.data.error}</p>`;
                } else {
                    const txns = response.data.transactions;
                    // Store the userId from the server response
                    loggedInUserId = response.data.userId || null;
                    renderTransactions(txns);
                }
            })
            .catch(error => {
                console.error("Error fetching transactions:", error);
                transactionsList.innerHTML = "<p>Failed to load transactions.</p>";
            });
    }

    function renderTransactions(transactions) {
        if (!transactions || transactions.length === 0) {
            transactionsList.innerHTML = "<p>No transactions found.</p>";
            return;
        }

        let html = `<table class="transactions-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>`;

        transactions.forEach(tx => {
            const dateStr = new Date(tx.created_at).toLocaleString();
            let details   = '';

            switch (tx.transaction_type) {
                case 'deposit':
                    details = ''; // No need to show sender/recipient
                    break;
                case 'withdrawal':
                    details = ''; // No need to show sender/recipient
                    break;
                case 'transfer':
                    // If I'm the sender, show "To: recipient_email"
                    // If I'm the recipient, show "From: sender_email"
                    if (parseInt(tx.sender_id) === parseInt(loggedInUserId)) {
                        details = `To: ${tx.recipient_email || 'N/A'}`;
                    } else if (parseInt(tx.recipient_id) === parseInt(loggedInUserId)) {
                        details = `From: ${tx.sender_email || 'N/A'}`;
                    }
                    break;
            }

            html += `
              <tr>
                <td>${dateStr}</td>
                <td>${tx.transaction_type}</td>
                <td>${tx.amount}</td>
                <td>${details}</td>
              </tr>`;
        });

        html += `</tbody></table>`;
        transactionsList.innerHTML = html;
    }
});
