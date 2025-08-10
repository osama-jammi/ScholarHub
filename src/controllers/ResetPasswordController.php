<?php
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../models/UserModel.php';

$host = 'localhost';
$db = 'SCHOLARHUB';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = filter_input(INPUT_POST, 'verification_code', FILTER_SANITIZE_STRING);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header('Location: /ScholarHub/views/reset_password.php');
        exit();
    }
    
    if (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_email']) || !isset($_SESSION['code_expiry'])) {
        $_SESSION['error'] = "Session invalide. Veuillez recommencer.";
        header('Location: /ScholarHub/views/forgot_password.php');
        exit();
    }
    
    if (time() > $_SESSION['code_expiry']) {
        $_SESSION['error'] = "Le code a expiré. Veuillez en demander un nouveau.";
        header('Location: /ScholarHub/views/forgot_password.php');
        exit();
    }
    
    if ($code != $_SESSION['reset_code']) {
        $_SESSION['error'] = "Code de vérification incorrect.";
        header('Location: /ScholarHub/views/reset_password.php');
        exit();
    }
    
    // Utiliser la connexion PDO créée dans db.php
    $userModel = new UserModel($pdo);
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $success = $userModel->updatePassword($_SESSION['reset_email'], $hashedPassword);
    
    if ($success) {
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['code_expiry']);
        
        $_SESSION['success'] = "Votre mot de passe a été réinitialisé avec succès.";
        header('Location: /ScholarHub/public/connexion.php');
        exit();
    } else {
        $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
        header('Location: /ScholarHub/views/reset_password.php');
        exit();
    }
}