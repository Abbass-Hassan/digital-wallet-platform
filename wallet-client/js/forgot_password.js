document.addEventListener("DOMContentLoaded", function () {
    const forgotPasswordForm = document.getElementById("forgotPasswordForm");
  
    forgotPasswordForm.addEventListener("submit", function (e) {
      e.preventDefault();
  
      const email = document.getElementById("email").value.trim();
  
      if (!email) {
        alert("Please enter your email address.");
        return;
      }
  
      // Build form data (you can also use FormData if needed)
      const data = new FormData();
      data.append("email", email);
  
      axios.post("/digital-wallet-platform/wallet-server/user/v1/request_password_reset.php", data)
        .then(response => {
          // For security, the response should be generic.
          if (response.data.error) {
            alert(response.data.error);
          } else {
            alert(response.data.message);
            // Optionally, redirect to login page:
            window.location.href = "login.html";
          }
        })
        .catch(error => {
          console.error("Error requesting password reset:", error);
          alert("An error occurred. Please try again later.");
        });
    });
  });
  