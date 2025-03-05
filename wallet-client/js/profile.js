document.addEventListener("DOMContentLoaded", function () {
    // Retrieve the JWT from localStorage
    const token = localStorage.getItem('jwt');
    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    // Create axios configuration with the Authorization header
    const axiosConfig = {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    };

    // Fetch the profile using the JWT in the header
    axios.get("/digital-wallet-platform/wallet-server/user/v1/get_profile.php", axiosConfig)
        .then(response => {
            if (response.data.success) {
                document.getElementById("fullName").value = response.data.user.full_name || "";
                document.getElementById("dob").value = response.data.user.date_of_birth || "";
                document.getElementById("phone").value = response.data.user.phone_number || "";
                document.getElementById("street").value = response.data.user.street_address || "";
                document.getElementById("city").value = response.data.user.city || "";
                document.getElementById("country").value = response.data.user.country || "";
            } else {
                console.warn(response.data.message);
            }
        })
        .catch(error => {
            console.error("Error fetching profile:", error);
        });

    // Handle the profile update form submission
    document.getElementById("profileForm").addEventListener("submit", function (e) {
        e.preventDefault();
        
        const formData = {
            full_name: document.getElementById("fullName").value,
            date_of_birth: document.getElementById("dob").value,
            phone_number: document.getElementById("phone").value,
            street_address: document.getElementById("street").value,
            city: document.getElementById("city").value,
            country: document.getElementById("country").value
        };

        axios.post("/digital-wallet-platform/wallet-server/user/v1/update_profile.php", formData, axiosConfig)
            .then(response => {
                if (response.data.success) {
                    alert("Profile updated successfully!");
                    window.location.href = "dashboard.html";
                } else {
                    alert("Error: " + response.data.message);
                }
            })
            .catch(error => {
                console.error("Error updating profile:", error);
                alert("An error occurred while updating the profile.");
            });
    });
});
