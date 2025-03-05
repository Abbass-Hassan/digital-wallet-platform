document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Build FormData from the form
    const formData = new FormData(this);

    try {
        const response = await axios.post(this.action, formData);
        console.log("Register response:", response.data);
        if (response.data && response.data.status === 'success') {
            // Store JWT if provided and redirect to verification page
            if (response.data.token) {
                localStorage.setItem('jwt', response.data.token);
            }
            window.location.href = '/digital-wallet-platform/wallet-client/verification.html';
        }
    } catch (error) {
        console.error("Error processing registration:", error);
    }
});
