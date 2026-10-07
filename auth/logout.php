<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
setcookie(session_name(), '', time()-3600, '/');
header('Location: /online-book-resale/auth/login.php');
exit;
