document.addEventListener("DOMContentLoaded", function () {
  const resetPasswordForm = document.getElementById("resetPasswordForm");

  resetPasswordForm.addEventListener("submit", function (e) {
    e.preventDefault();

    // Get the token from the hidden field (populated from URL by inline JS)
    const token = document.getElementById("token").value.trim();
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = document.getElementById("confirm_password").value;

    if (!newPassword || !confirmPassword) {
      alert("Please fill in both password fields.");
      return;
    }
    if (newPassword !== confirmPassword) {
      alert("Passwords do not match.");
      return;
    }
    if (newPassword.length < 6) {
      alert("Password must be at least 6 characters.");
      return;
    }

    // Build request data as FormData
    const data = new FormData();
    data.append("token", token);
    data.append("new_password", newPassword);
    data.append("confirm_password", confirmPassword);

    axios.post("/digital-wallet-platform/wallet-server/user/v1/reset_password.php", data)
      .then(response => {
        if (response.data.error) {
          alert(response.data.error);
        } else {
          alert(response.data.message);
          // Optionally, redirect to login page after reset:
          window.location.href = "login.html";
        }
      })
      .catch(error => {
        console.error("Error resetting password:", error);
        alert("An error occurred while resetting your password. Please try again later.");
      });
  });
});
