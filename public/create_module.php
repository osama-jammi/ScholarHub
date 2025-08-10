<?php
require __DIR__ . '/../src/config/db.php';
session_start();


// Vérification que l'utilisateur est un professeur
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'prof') {
    header("Location: connexion.php");
    exit();
}

$userId = $_SESSION['user']['id'];

function generateRandomCode($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $moduleName = $_POST['module_name'];
    $syllabus_modul = $_POST['syllabus_modul'];
    $codeInscription = generateRandomCode();
    
    //date
    $anneeActuelle = date('Y');
    $anneeSuivante = $anneeActuelle + 1;
    $anneeScolaire = $anneeActuelle . '-' . $anneeSuivante;
    
    // Gestion de l'image
    $photoBlob = null;
    if(isset($_FILES['photo_modul']) && $_FILES['photo_modul']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo_modul'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($photo['tmp_name']);

        if(!in_array($fileType, $allowedTypes)) {
            die("Erreur: Type de fichier non autorisé.");
        }
        
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($photo['size'] > $maxSize) {
            die("Erreur: La taille de l'image ne doit pas dépasser 2MB.");
        }
        
        $photoBlob = file_get_contents($photo['tmp_name']);
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO Module (code_inscription, photo, syllabus, nom) VALUES (?, ?, ?, ?)");
        $stmt->execute([$codeInscription, $photoBlob, $syllabus_modul, $moduleName]);

        $stmt2 = $pdo->prepare("INSERT INTO Avoir (professeur_id, module_code, annee_scolaire) VALUES (?, ?, ?)");
        $stmt2->execute([$userId, $codeInscription, $anneeScolaire]);
        
        $pdo->commit();
        
        header("Location: accueil.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la création du module: " . $e->getMessage());
    }
}
?>