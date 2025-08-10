<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Vérification des autorisations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Accès refusé']));
}

// Vérification des données POST
if (!isset($_POST['submission_id']) || !isset($_POST['note'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Données manquantes']));
}

$submissionId = intval($_POST['submission_id']);
$note = floatval($_POST['note']);
$commentaire = $_POST['commentaire'] ?? '';

// Validation de la note
if ($note < 0 || $note > 20) {
    exit(json_encode(['success' => false, 'message' => 'La note doit être entre 0 et 20']));
}

try {
    // Vérifier que le rendu appartient à un travail géré par le professeur
    $stmt = $pdo->prepare("SELECT r.id FROM Rendu r
                          JOIN Travail t ON r.travail_id = t.id
                          JOIN Avoir a ON t.module_code = a.module_code
                          WHERE r.id = ? AND a.professeur_id = ?");
    $stmt->execute([$submissionId, $_SESSION['user']['id']]);
    
    if (!$stmt->fetch()) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Rendu non trouvé ou non autorisé']));
    }

    // Mettre à jour la note
    $stmt = $pdo->prepare("UPDATE Rendu 
                          SET note = ?, commentaire = ?, date_correction = NOW()
                          WHERE id = ?");
    $stmt->execute([$note, $commentaire, $submissionId]);

    echo json_encode([
        'success' => true, 
        'message' => 'Note enregistrée',
        'note' => $note,
        'date_correction' => date('d/m/Y H:i')
    ]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['success' => false, 'message' => 'Erreur d\'enregistrement']));
}
?>