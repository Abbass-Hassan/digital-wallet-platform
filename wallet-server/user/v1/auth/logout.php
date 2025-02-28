<?php
session_start();
session_destroy();

header("Location: /digital-wallet-platform/wallet-client/login.html");

?>