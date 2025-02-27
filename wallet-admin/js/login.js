document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    axios.post(this.action, formData)
      .then(function(response) {
        if (response.data && response.data.message) {
          alert(response.data.message);
          if (response.data.status === 'success') {
            window.location.href = '/digital-wallet-platform/wallet-admin/dashboard.html';
          }
        } else {
          alert("Unexpected response from server.");
        }
      })
      .catch(function(error) {
        console.error("Error:", error);
        alert("An error occurred while processing your login.");
      });
  });
  