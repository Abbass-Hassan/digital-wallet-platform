document.addEventListener('DOMContentLoaded', function() {
    // 1. Fetch and display the user's name in the header
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

    // 2. Verification widget logic
    const verificationWidget  = document.getElementById('verificationWidget');
    const verificationTitle   = document.getElementById('verificationTitle');
    const verificationMessage = document.getElementById('verificationMessage');
    const verificationButton  = document.getElementById('verificationButton');

    axios.get('/digital-wallet-platform/wallet-server/user/v1/get_verification_status.php')
        .then(response => {
            if (response.data.error) {
                verificationTitle.textContent = 'Error';
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
            verificationTitle.textContent = 'Error';
            verificationMessage.textContent = 'Unable to load verification status.';
        });

    // 3. Fetch and display the user's wallet balance
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

    // 4. Fetch and display the user's transaction limits usage
    const dailyUsedElem     = document.getElementById('dailyUsed');
    const dailyLimitElem    = document.getElementById('dailyLimit');
    const dailyRemainingElem= document.getElementById('dailyRemaining');
    const weeklyUsedElem    = document.getElementById('weeklyUsed');
    const weeklyLimitElem   = document.getElementById('weeklyLimit');
    const weeklyRemainingElem = document.getElementById('weeklyRemaining');
    const monthlyUsedElem   = document.getElementById('monthlyUsed');
    const monthlyLimitElem  = document.getElementById('monthlyLimit');
    const monthlyRemainingElem = document.getElementById('monthlyRemaining');

    axios.get('/digital-wallet-platform/wallet-server/user/v1/get_limits_usage.php')
        .then(response => {
            if (response.data.error) {
                dailyUsedElem.textContent = 'Error';
                weeklyUsedElem.textContent = 'Error';
                monthlyUsedElem.textContent = 'Error';
            } else {
                dailyUsedElem.textContent = response.data.dailyUsed.toFixed(2) + ' USDT';
                dailyLimitElem.textContent = response.data.dailyLimit.toFixed(2) + ' USDT';
                dailyRemainingElem.textContent = response.data.dailyRemaining.toFixed(2) + ' USDT';

                weeklyUsedElem.textContent = response.data.weeklyUsed.toFixed(2) + ' USDT';
                weeklyLimitElem.textContent = response.data.weeklyLimit.toFixed(2) + ' USDT';
                weeklyRemainingElem.textContent = response.data.weeklyRemaining.toFixed(2) + ' USDT';

                monthlyUsedElem.textContent = response.data.monthlyUsed.toFixed(2) + ' USDT';
                monthlyLimitElem.textContent = response.data.monthlyLimit.toFixed(2) + ' USDT';
                monthlyRemainingElem.textContent = response.data.monthlyRemaining.toFixed(2) + ' USDT';
            }
        })
        .catch(error => {
            console.error("Error fetching limits usage:", error);
        });
});
