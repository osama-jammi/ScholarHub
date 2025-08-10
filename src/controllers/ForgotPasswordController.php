<?php
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once '../config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Utiliser la connexion PDO créée dans db.php
    $userModel = new UserModel($pdo);
    $user = $userModel->getUserByEmail($email);
    
    if ($user) {
        $verificationCode = rand(100000, 999999);
        $_SESSION['reset_code'] = $verificationCode;
        $_SESSION['reset_email'] = $email;
        $_SESSION['code_expiry'] = time() + 3600;
        
        sendVerificationCode($email, $verificationCode);
        
        header('Location: /ScholarHub/src/views/reset_password.php');
        exit();
    } else {
        $_SESSION['error'] = "Aucun compte trouvé avec cet email.";
        header('Location: /ScholarHub/public/forgot_password.php');
        exit();
    }
}