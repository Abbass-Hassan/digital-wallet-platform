document.addEventListener('DOMContentLoaded', function() {
    // 1. Get the JWT from localStorage
    const token = localStorage.getItem('jwt');
    if (!token) {
        // If there's no token, user is not logged in; redirect to login
        window.location.href = 'login.html';
        return;
    }

    // 2. Helper for axios config with Authorization header
    const axiosConfig = {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    };

    // 3. Fetch and display the user's name and tier in the header
    const userNameElem = document.querySelector('.dashboard-user-name');
    const userMetaElem = document.querySelector('.dashboard-user-meta');

    axios.get('http://13.38.91.228/user/v1/get_profile.php', axiosConfig)
        .then(response => {
            if (response.data.success) {
                let fullName = response.data.user.full_name;
                let userTier = response.data.user.tier; // <-- fetch the tier

                // Handle the user's name
                if (!fullName || fullName.trim() === "") {
                    userNameElem.innerHTML = 'No name set. <a href="profile.html">Update your profile</a>';
                } else {
                    userNameElem.textContent = fullName;
                }

                // Handle the user's tier
                if (!userTier || userTier.trim() === "") {
                    // If no tier is set, default to 'Regular User'
                    userMetaElem.textContent = 'User Level: Regular';
                } else {
                    userMetaElem.textContent = 'User Level: ' + userTier;
                }

            } else {
                console.warn("Profile fetch failed:", response.data.message);
                userNameElem.textContent = "Unknown User";
                userMetaElem.textContent = "VIP Level: Unknown";
            }
        })
        .catch(error => {
            console.error("Error fetching profile:", error);
            userNameElem.textContent = "Error Loading Name";
            userMetaElem.textContent = "VIP Level: Error";
        });

    // 4. Verification widget logic
    const verificationWidget  = document.getElementById('verificationWidget');
    const verificationTitle   = document.getElementById('verificationTitle');
    const verificationMessage = document.getElementById('verificationMessage');
    const verificationButton  = document.getElementById('verificationButton');

    axios.get('http://13.38.91.228/user/v1/get_verification_status.php', axiosConfig)
        .then(response => {
            if (response.data.error) {
                verificationTitle.textContent = 'Error';
                verificationMessage.textContent = response.data.error;
                return;
            }

            // Parse the verification status (0: pending, 1: approved, -1: rejected)
            const status = parseInt(response.data.is_validated, 10);

            // Remove old status classes
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

    // 5. Fetch and display the user's wallet balance
    const balanceAmountElem = document.getElementById('balanceAmount');
    if (balanceAmountElem) {
        axios.get('http://13.38.91.228/user/v1/get_balance.php', axiosConfig)
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

    // 6. Fetch and display the user's transaction limits usage
    const dailyInfo   = document.getElementById('dailyInfo');
    const weeklyInfo  = document.getElementById('weeklyInfo');
    const monthlyInfo = document.getElementById('monthlyInfo');

    const dailyBar    = document.getElementById('dailyBar');
    const weeklyBar   = document.getElementById('weeklyBar');
    const monthlyBar  = document.getElementById('monthlyBar');

    function updateProgressBar(used, limit, barElem, infoElem) {
        const ratio   = limit > 0 ? (used / limit) : 0;
        const percent = Math.min(ratio * 100, 100);  // cap at 100%

        // Set bar width
        barElem.style.width = percent.toFixed(2) + '%';
        // Set numeric text, e.g., "50.00 / 200.00"
        infoElem.textContent = used.toFixed(2) + ' / ' + limit.toFixed(2);
    }

    axios.get('http://13.38.91.228/user/v1/get_limits_usage.php', axiosConfig)
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
