<?php
require __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header("Location: ../../connexion.php");
    exit();
}

$moduleCode = $_POST['module_code'] ?? '';
$moduleName = $_POST['name'] ?? '';
$syllabus = $_POST['syllabus'] ?? '';

// Vérifier si le module existe
$stmt = $pdo->prepare("SELECT * FROM Module WHERE code_inscription = ?");
$stmt->execute([$moduleCode]);
$module = $stmt->fetch();

if (!$module) {
    header("Location: ../../accueil.php");
    exit();
}

// Gérer l'upload de la photo si fournie
$photo = $module['photo']; // Conserver la photo actuelle par défaut
if (!empty($_FILES['photo']['name'])) {
    $photo = file_get_contents($_FILES['photo']['tmp_name']);
}

// Mettre à jour le module
$stmt = $pdo->prepare("UPDATE Module SET nom = ?, syllabus = ?, photo = ? WHERE code_inscription = ?");
$success = $stmt->execute([$moduleName, $syllabus, $photo, $moduleCode]);

if ($success) {
    $_SESSION['success_message'] = "Module mis à jour avec succès";
} else {
    $_SESSION['error_message'] = "Erreur lors de la mise à jour du module";
}

header("Location: ../../public/gestion_module.php?module_code=" . urlencode($moduleCode));
exit();