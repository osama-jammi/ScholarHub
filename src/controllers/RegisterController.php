<?php
// scr/controllers/RegisterController.php
session_start();
require_once('../models/User.php');
require_once __DIR__ . '/../includes/mailer.php';

require_once '../config/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Générer un code
    $code = rand(100000, 999999);
    $_SESSION['verification_code'] = $code;
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $_SESSION['user_temp'] = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'password' => $hashedPassword 
    ];
    


    // Envoyer l'email
   
    sendVerificationCode($email, $code);    



    header('Location: ../../docs/public/verify.php');
    exit();
}
