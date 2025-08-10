<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_temp'])) {
        die("Erreur: Données d'inscription manquantes.");
    }

    $filiere = $_POST['filiere'];
    $photo = $_FILES['photo'];

    // Utiliser la photo Google si disponible et si l'utilisateur n'en upload pas
    $photoBlob = null;
    if (isset($_SESSION['user_temp']['google_photo']) && $photo['error'] === UPLOAD_ERR_NO_FILE) {
        $photoBlob = $_SESSION['user_temp']['google_photo'];
    } elseif ($photo['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($photo['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            die("Erreur: Type de fichier non autorisé.");
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($photo['size'] > $maxSize) {
            die("Erreur: La taille de l'image ne doit pas dépasser 2MB.");
        }

        $photoBlob = file_get_contents($photo['tmp_name']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Etudiant (nom, prenom, adresse, password, photo, fillier) VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_SESSION['user_temp']['nom'],
            $_SESSION['user_temp']['prenom'],
            $_SESSION['user_temp']['email'],
            $_SESSION['user_temp']['password'], // Null si Google Auth
            $photoBlob,
            $filiere
        ]);

        $studentId = $pdo->lastInsertId();

        $_SESSION['user'] = [
            'id' => $studentId,
            'email' => $_SESSION['user_temp']['email'],
            'role' => 'student',
            'nom' => $_SESSION['user_temp']['nom'],
            'prenom' => $_SESSION['user_temp']['prenom'],
            'photo' => $photoBlob ? base64_encode($photoBlob) : null
        ];

        unset($_SESSION['user_temp']);

        header("Location: ../../public/connexion.php");
        exit();

    } catch (PDOException $e) {
        die("Erreur de base de données: " . $e->getMessage());
    }
}
?>