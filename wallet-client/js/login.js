document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
  
    const formData = new FormData(this);
  
    try {
      const response = await axios.post(this.action, formData);
      if (response.data && response.data.message) {
        alert(response.data.message);
        
        if (response.data.status === 'success') {
          // The updated login.php should return a JWT as "token"
          // and user details in "user"
          const token = response.data.token;
          const user = response.data.user;
  
          if (token && user) {
            // Store the JWT in localStorage
            localStorage.setItem('jwt', token);
  
            // Optionally store user info in localStorage
            localStorage.setItem('userId', user.id);
            localStorage.setItem('userEmail', user.email);
            localStorage.setItem('userRole', user.role);
            // localStorage.setItem('isValidated', user.is_validated);
  
            // Redirect to dashboard (or wherever you want)
            window.location.href = '/digital-wallet-platform/wallet-client/dashboard.html';
          } else {
            // If token or user info is missing, handle error
            alert('Login response did not contain a valid token/user object.');
          }
        }
      } else {
        alert("Unexpected response from server.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred while processing your login.");
    }
  });
  