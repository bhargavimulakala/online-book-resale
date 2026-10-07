<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['user_role']);
session_destroy();
header('Location: /online-book-resale/admin/login.php');
exit;
