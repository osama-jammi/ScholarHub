<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Vérification des autorisations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Accès refusé']));
}

// Vérification des paramètres
if (!isset($_GET['student_id']) || !isset($_GET['module_code'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Paramètres manquants']));
}

$studentId = intval($_GET['student_id']);
$moduleCode = $_GET['module_code'];

try {
    // Vérifier que le professeur gère bien ce module
    $stmt = $pdo->prepare("SELECT 1 FROM Avoir WHERE professeur_id = ? AND module_code = ?");
    $stmt->execute([$_SESSION['user']['id'], $moduleCode]);
    
    if (!$stmt->fetch()) {
        header('HTTP/1.1 403 Forbidden');
        exit(json_encode(['success' => false, 'message' => 'Vous ne gérez pas ce module']));
    }

    // Supprimer l'étudiant du module
    $stmt = $pdo->prepare("DELETE FROM Inscrit WHERE etudiant_id = ? AND module_code = ?");
    $stmt->execute([$studentId, $moduleCode]);

    // Supprimer les rendus associés dans ce module
    $stmt = $pdo->prepare("DELETE r FROM Rendu r 
                          JOIN Travail t ON r.travail_id = t.id 
                          WHERE r.etudiant_id = ? AND t.module_code = ?");
    $stmt->execute([$studentId, $moduleCode]);

    echo json_encode(['success' => true, 'message' => 'Étudiant supprimé du module avec succès']);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'message' => 'Erreur de base de données']));
}
?>