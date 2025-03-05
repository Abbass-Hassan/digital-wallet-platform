document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Build FormData from the form
    const formData = new FormData(this);

    try {
        const response = await axios.post(this.action, formData);
        // Log the response to debug if needed
        console.log("Register response:", response.data);

        if (response.data && response.data.message) {
            alert(response.data.message);

            if (response.data.status === 'success') {
                // If the server returned a token, store it
                if (response.data.token) {
                    localStorage.setItem('jwt', response.data.token);
                }
                // Redirect to verification page
                window.location.href = '/digital-wallet-platform/wallet-client/verification.html';
            }
        } else {
            alert("Unexpected response from server.");
        }
    } catch (error) {
        console.error("Error:", error);
        alert("An error occurred while processing your registration.");
    }
});
