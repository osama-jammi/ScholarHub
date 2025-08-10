<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Vérification des autorisations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Accès refusé']));
}

// Vérification des paramètres
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'ID travail invalide']));
}

$workId = intval($_GET['id']);

try {
    // Vérifier que le travail appartient à un module géré par le professeur
    $stmt = $pdo->prepare("SELECT t.id FROM Travail t
                          JOIN Avoir a ON t.module_code = a.module_code
                          WHERE t.id = ? AND a.professeur_id = ?");
    $stmt->execute([$workId, $_SESSION['user']['id']]);
    
    if (!$stmt->fetch()) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Travail non trouvé ou non autorisé']));
    }

    // Commencer une transaction
    $pdo->beginTransaction();

    // 1. Supprimer les rendus associés
    $stmt = $pdo->prepare("DELETE FROM Rendu WHERE travail_id = ?");
    $stmt->execute([$workId]);

    // 2. Supprimer le travail
    $stmt = $pdo->prepare("DELETE FROM Travail WHERE id = ?");
    $stmt->execute([$workId]);

    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Travail supprimé avec succès']);
} catch (PDOException $e) {
    $pdo->rollBack();
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'message' => 'Erreur de suppression']));
}
?>