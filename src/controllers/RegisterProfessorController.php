<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_SESSION['user_temp'])){
        die("Erreur : données inscription manquants.");
    }
    
    $photo = $_FILES['photo'];
    $bio = $_POST['bio'];
    $scholarLink = $_POST['scholar_link'];

    // Utiliser la photo Google si disponible
    $photoBlob = null;
    if (isset($_SESSION['user_temp']['google_photo']) && $photo['error'] === UPLOAD_ERR_NO_FILE) {
        $photoBlob = $_SESSION['user_temp']['google_photo'];
    } elseif ($photo['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg','image/png','image/gif'];
        $fileType = mime_content_type($photo['tmp_name']);
    
        if(!in_array($fileType,$allowedTypes)){
            die("Erreur : type de fichier non autorisé.");
        }

        $maxSize = 2*1024 * 1024;
        if($photo['size']>$maxSize){
            die("Erreur : la taille de l'image ne doit pas dépasser 2MB");
        }
        $photoBlob = file_get_contents($photo['tmp_name']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Professeur (nom, prenom, adresse, biographie, lien_google_scholar, password, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_temp']['nom'],
            $_SESSION['user_temp']['prenom'],
            $_SESSION['user_temp']['email'],
            $bio,
            $scholarLink,
            $_SESSION['user_temp']['password'], // Null si Google Auth
            $photoBlob
        ]);

        $profId = $pdo->lastInsertId();

        unset($_SESSION['user_temp']);
        $_SESSION['user'] = [
            'id' => $profId,
            'email' => $_SESSION['user_temp']['email'],
            'role' => 'prof',
            'nom' => $_SESSION['user_temp']['nom'],
            'prenom' => $_SESSION['user_temp']['prenom'],
            'photo' => $photoBlob ? base64_encode($photoBlob) : null
        ];

        header("Location: ../../public/connexion.php");
        exit();

    } catch(PDOException $e) {
        die("Erreur de base de données : ".$e->getMessage());
    }
}
?>