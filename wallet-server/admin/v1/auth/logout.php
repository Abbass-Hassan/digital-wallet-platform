<?php
// For JWT-based authentication, no session cleanup is needed on the server.
// Simply redirect the admin to the login page.
header("Location: /digital-wallet-platform/wallet-admin/login.html");
exit();
?>
