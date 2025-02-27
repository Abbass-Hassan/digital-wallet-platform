document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.getElementById("verificationRequestsBody");

    function fetchRequests() {
        axios.get("http://localhost/digital-wallet-platform/wallet-server/admin/v1/verification_requests.php")
            .then(response => {
                if (response.data.status === "success") {
                    tableBody.innerHTML = ""; // Clear existing rows

                    response.data.data.forEach(request => {
                        const row = document.createElement("tr");

                        row.innerHTML = `
                            <td>${request.email}</td>
                            <td><a href="/digital-wallet-platform/wallet-server/uploads/${request.id_document}" target="_blank">View ID</a></td>
                            <td>
                                <button class="approve-btn" data-user-id="${request.user_id}">Approve</button>
                                <button class="reject-btn" data-user-id="${request.user_id}">Reject</button>
                            </td>
                        `;

                        tableBody.appendChild(row);
                    });

                    addActionListeners();
                } else {
                    tableBody.innerHTML = `<tr><td colspan="3">No pending requests found.</td></tr>`;
                }
            })
            .catch(error => {
                console.error("Error fetching verification requests:", error);
                tableBody.innerHTML = `<tr><td colspan="3">Failed to load requests.</td></tr>`;
            });
    }

    function addActionListeners() {
        document.querySelectorAll(".approve-btn").forEach(button => {
            button.addEventListener("click", function () {
                updateVerificationStatus(this.dataset.userId, 1);
            });
        });

        document.querySelectorAll(".reject-btn").forEach(button => {
            button.addEventListener("click", function () {
                updateVerificationStatus(this.dataset.userId, -1);
            });
        });
    }

    function updateVerificationStatus(user_id, is_validated) {
        if (!user_id) {
            alert("User ID missing.");
            return;
        }

        axios.post("http://localhost/digital-wallet-platform/wallet-server/admin/v1/update_verification.php", {
            user_id: user_id,
            is_validated: is_validated
        }, { headers: { "Content-Type": "application/json" } })
        .then(response => {
            if (response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert("Unexpected response from server.");
            }
            fetchRequests(); // Refresh table after update
        })
        .catch(error => {
            console.error("Error updating verification status:", error);
            alert("Failed to update status.");
        });
    }

    fetchRequests(); // Load requests on page load
});
