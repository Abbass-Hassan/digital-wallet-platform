<?php
session_start();
session_unset();
session_destroy();

header("Location: /digital-wallet-platform/wallet-admin/login.html");
exit();
?>
