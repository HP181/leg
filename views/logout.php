<?php
session_start();
session_unset(); // Remove session variables
session_destroy(); // Destroy the session

header("Location: login.php"); // Redirect to login page
exit;
?>
