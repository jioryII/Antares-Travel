<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_email'])) {
    $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
    header('Location: login.php?lang=' . $lang);
    exit();
}
?>