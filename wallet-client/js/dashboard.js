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
    // Elements for numeric usage/limits
    const dailyInfo          = document.getElementById('dailyInfo');
    const weeklyInfo         = document.getElementById('weeklyInfo');
    const monthlyInfo        = document.getElementById('monthlyInfo');
    // Elements for the progress bars
    const dailyBar           = document.getElementById('dailyBar');
    const weeklyBar          = document.getElementById('weeklyBar');
    const monthlyBar         = document.getElementById('monthlyBar');

    // Helper function to update progress bar & text
    function updateProgressBar(used, limit, barElem, infoElem) {
        const ratio = limit > 0 ? (used / limit) : 0;
        const percent = Math.min(ratio * 100, 100);  // cap at 100%
        
        // Set bar width
        barElem.style.width = percent.toFixed(2) + '%';

        // Set numeric text, e.g. "50.00 / 200.00"
        infoElem.textContent = used.toFixed(2) + ' / ' + limit.toFixed(2);
    }

    axios.get('/digital-wallet-platform/wallet-server/user/v1/get_limits_usage.php')
        .then(response => {
            if (response.data.error) {
                dailyInfo.textContent   = 'Error';
                weeklyInfo.textContent  = 'Error';
                monthlyInfo.textContent = 'Error';
            } else {
                const dailyUsed    = response.data.dailyUsed;
                const dailyLimit   = response.data.dailyLimit;
                const weeklyUsed   = response.data.weeklyUsed;
                const weeklyLimit  = response.data.weeklyLimit;
                const monthlyUsed  = response.data.monthlyUsed;
                const monthlyLimit = response.data.monthlyLimit;

                // Update daily row
                updateProgressBar(dailyUsed, dailyLimit, dailyBar, dailyInfo);

                // Update weekly row
                updateProgressBar(weeklyUsed, weeklyLimit, weeklyBar, weeklyInfo);

                // Update monthly row
                updateProgressBar(monthlyUsed, monthlyLimit, monthlyBar, monthlyInfo);
            }
        })
        .catch(error => {
            console.error("Error fetching limits usage:", error);
            dailyInfo.textContent   = 'Error';
            weeklyInfo.textContent  = 'Error';
            monthlyInfo.textContent = 'Error';
        });
});
