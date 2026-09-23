<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
session_start();
$_SESSION['flash_msg'] = "You have been safely logged out.";
$_SESSION['flash_type'] = "info";
header("Location: login.php");
exit;
