<?php
session_start();

// Se l'utente è già autenticato reindirizza alla dashboard, altrimenti alla pagina di login
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>