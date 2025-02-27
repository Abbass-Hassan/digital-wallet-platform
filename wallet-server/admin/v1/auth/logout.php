<?php
session_start();
session_unset(); // Clear session variables
session_destroy(); // Destroy the session

header("Location: /digital-wallet-platform/wallet-admin/login.html"); // Redirect to login page
exit();
?>
