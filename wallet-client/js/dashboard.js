document.addEventListener('DOMContentLoaded', function() {
    const userNameElem = document.querySelector('.dashboard-user-name');

    axios.get('/digital-wallet-platform/wallet-server/user/v1/get_profile.php')
        .then(response => {
            if (response.data.success) {
                let fullName = response.data.user.full_name;
                if (!fullName || fullName.trim() === "") {
                    userNameElem.innerHTML = 'No name set. <a href="profile.html">Update your profile</a>';
                } else {
                    userNameElem.textContent = fullName;
                }
            } else {
                console.warn("Profile fetch failed:", response.data.message);
                userNameElem.textContent = "Unknown User";
            }
        })
        .catch(error => {
            console.error("Error fetching profile:", error);
            userNameElem.textContent = "Error Loading Name";
        });

    const verificationWidget  = document.getElementById('verificationWidget');
    const verificationTitle   = document.getElementById('verificationTitle');
    const verificationMessage = document.getElementById('verificationMessage');
    const verificationButton  = document.getElementById('verificationButton');

    axios.get('/digital-wallet-platform/wallet-server/user/v1/get_verification_status.php')
        .then(response => {
            if (response.data.error) {
                verificationTitle.textContent   = 'Error';
                verificationMessage.textContent = response.data.error;
                return;
            }

            // Parse the verification status (0: pending, 1: approved, -1: rejected)
            const status = parseInt(response.data.is_validated, 10);

            verificationWidget.classList.remove('verification-pending', 'verification-approved', 'verification-rejected');

            switch (status) {
                case 0:  // Pending
                    verificationWidget.classList.add('verification-pending');
                    verificationTitle.textContent   = 'Verification Pending';
                    verificationMessage.textContent = 'Your documents are under review. Please wait for approval.';
                    verificationButton.style.display = 'none';
                    break;

                case 1:  // Approved
                    verificationWidget.classList.add('verification-approved');
                    verificationTitle.textContent   = 'Account Verified';
                    verificationMessage.textContent = 'Your account is verified. Enjoy full access to our services!';
                    verificationButton.style.display = 'none';
                    break;

                case -1: // Rejected
                    verificationWidget.classList.add('verification-rejected');
                    verificationTitle.textContent   = 'Verification Rejected';
                    verificationMessage.textContent = 'Unfortunately, your verification was rejected. Please resubmit.';
                    verificationButton.style.display = 'inline-block';
                    verificationButton.textContent   = 'Resubmit';
                    verificationButton.onclick = function() {
                        window.location.href = 'verification.html';
                    };
                    break;

                default:
                    verificationTitle.textContent   = 'Not Verified';
                    verificationMessage.textContent = 'No verification record found. Please verify to unlock features.';
                    verificationButton.style.display = 'inline-block';
                    verificationButton.textContent   = 'Verify Now';
                    verificationButton.onclick = function() {
                        window.location.href = 'verification.html';
                    };
                    break;
            }
        })
        .catch(error => {
            console.error('Error fetching verification status:', error);
            verificationTitle.textContent   = 'Error';
            verificationMessage.textContent = 'Unable to load verification status.';
        });

    const balanceAmountElem = document.getElementById('balanceAmount');
    if (balanceAmountElem) {
        axios.get('/digital-wallet-platform/wallet-server/user/v1/get_balance.php')
            .then(response => {
                if (response.data.error) {
                    balanceAmountElem.textContent = `Error: ${response.data.error}`;
                } else {
                    const balance = response.data.balance !== undefined ? response.data.balance : 0;
                    balanceAmountElem.textContent = balance + ' USDT';
                }
            })
            .catch(error => {
                console.error('Error fetching balance:', error);
                balanceAmountElem.textContent = 'Error Loading Balance';
            });
    }
});
