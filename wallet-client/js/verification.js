document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("idUpload");
    const submitBtn = document.getElementById("submitVerification");

    submitBtn.addEventListener("click", function () {
        if (!fileInput.files.length) {
            alert("Please select a document to upload.");
            return;
        }

        const formData = new FormData();
        formData.append("id_document", fileInput.files[0]);

        axios.post("http://localhost/digital-wallet-platform/wallet-server/user/v1/verification.php", formData, {
            headers: { "Content-Type": "multipart/form-data" }
        })
        .then(response => {
            alert(response.data.message);
            if (response.data.status === "success") {
                window.location.href = "/digital-wallet-platform/wallet-client/dashboard.html";
            }
        })
        .catch(error => {
            console.error("Upload error:", error);
            alert("An error occurred while uploading your document. Please try again.");
        });
    });
});
