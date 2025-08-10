<?php
session_start();

if ($_POST['code'] == $_SESSION['verification_code']) {
    // Enregistrer les infos temporairement

    unset($_SESSION['verification_code']);

    header("Location: ../../public/choose_role.php");
    exit();
} else {
    echo "Code incorrect.";
}
