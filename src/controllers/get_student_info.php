<?php
require_once __DIR__ . '/../config/db.php';

session_start();

// Vérifier que l'utilisateur est connecté et a les droits
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accès refusé');
}

// Vérifier que l'ID étudiant est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID étudiant invalide');
}

$studentId = intval($_GET['id']);

try {
    // Récupérer les informations de base de l'étudiant
    $stmt = $pdo->prepare("SELECT * FROM Etudiant WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        header('HTTP/1.1 404 Not Found');
        exit('Étudiant non trouvé');
    }

    // Récupérer les modules de l'étudiant
    $stmt = $pdo->prepare("SELECT m.nom, m.code_inscription, i.annee_scolaire 
                          FROM Module m 
                          JOIN Inscrit i ON m.code_inscription = i.module_code 
                          WHERE i.etudiant_id = ?");
    $stmt->execute([$studentId]);
    $modules = $stmt->fetchAll();

    // Récupérer les statistiques des notes
    $stmt = $pdo->prepare("SELECT 
                          COUNT(r.id) as travaux_soumis,
                          COUNT(CASE WHEN r.note IS NOT NULL THEN 1 END) as travaux_notes,
                          AVG(r.note) as moyenne_generale
                          FROM Rendu r
                          JOIN Travail t ON r.travail_id = t.id
                          WHERE r.etudiant_id = ?");
    $stmt->execute([$studentId]);
    $stats = $stmt->fetch();

    // Afficher les informations
    ?>
    <div class="student-details">
        <div class="row">
            <div class="col-md-4 text-center">
                <?php if (!empty($student['photo'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($student['photo']) ?>" 
                         class="img-thumbnail mb-3" style="max-width: 200px;">
                <?php else: ?>
                    <img src="../../public/assets/default_user.jpg" class="img-thumbnail mb-3" style="max-width: 200px;">
                <?php endif; ?>
                
                <h4><?= htmlspecialchars($student['prenom'] . ' ' . $student['nom']) ?></h4>    
            </div>
            
            <div class="col-md-8">
                <h5>Informations personnelles</h5>
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item">
                        <strong>Filière:</strong> <?= htmlspecialchars($student['fillier'] ?? 'Non spécifiée') ?>
                    </li>
                    <li class="list-group-item">
                    
                    <li class="list-group-item">
                        <strong>Email:</strong> 
                        <a href="mailto:<?= htmlspecialchars($student['email'] ?? '') ?>">
                            <?= htmlspecialchars($student['adresse'] ?? 'Non spécifié') ?>
                        </a>
                    </li>
                </ul>
                
                <h5>Statistiques académiques</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Travaux soumis</h6>
                                <p class="card-text display-6"><?= $stats['travaux_soumis'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Travaux notés</h6>
                                <p class="card-text display-6"><?= $stats['travaux_notes'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Moyenne générale</h6>
                                <p class="card-text display-6">
                                    <?= isset($stats['moyenne_generale']) ? round($stats['moyenne_generale'], 2) : 'N/A' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
    </div>
    <?php
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur de base de données: ' . $e->getMessage());
}
?>