<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Vérification des autorisations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accès refusé');
}

// Vérification des paramètres
if (!isset($_GET['work_id']) || !is_numeric($_GET['work_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID travail invalide');
}

$workId = intval($_GET['work_id']);

try {
    // Vérifier que le travail appartient à un module géré par le professeur
    $stmt = $pdo->prepare("SELECT t.id, t.titre, m.code_inscription 
                          FROM Travail t
                          JOIN Module m ON t.module_code = m.code_inscription
                          JOIN Avoir a ON m.code_inscription = a.module_code
                          WHERE t.id = ? AND a.professeur_id = ?");
    $stmt->execute([$workId, $_SESSION['user']['id']]);
    $travail = $stmt->fetch();

    if (!$travail) {
        header('HTTP/1.1 404 Not Found');
        exit('Travail non trouvé ou non autorisé');
    }

    // Récupérer les rendus
    $stmt = $pdo->prepare("SELECT r.id, r.note, r.commentaire, r.date_soumission, 
                          r.nom_fichier, r.type_fichier,
                          e.id as etudiant_id, e.nom, e.prenom, e.photo
                          FROM Rendu r
                          JOIN Etudiant e ON r.etudiant_id = e.id
                          WHERE r.travail_id = ?
                          ORDER BY e.nom, e.prenom");
    $stmt->execute([$workId]);
    $rendus = $stmt->fetchAll();

    ?>
    <h4>Rendus pour: <?= htmlspecialchars($travail['titre']) ?></h4>
    <p class="text-muted">Module: <?= htmlspecialchars($travail['code_inscription']) ?></p>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Soumis le</th>
                    <th>Fichier</th>
                    <th>Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rendus as $rendu): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($rendu['photo'])): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($rendu['photo']) ?>" 
                                         class="rounded-circle me-2" width="30" height="30">
                                <?php endif; ?>
                                <?= htmlspecialchars($rendu['prenom'] . ' ' . $rendu['nom']) ?>
                            </div>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($rendu['date_soumission'])) ?></td>
                        <td>
                            <?php if ($rendu['nom_fichier']): ?>
                                <i class="fas fa-file"></i> <?= htmlspecialchars($rendu['nom_fichier']) ?>
                            <?php else: ?>
                                <span class="text-muted">Aucun fichier</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($rendu['note'] !== null): ?>
                                <span class="badge bg-<?= $rendu['note'] >= 10 ? 'success' : 'danger' ?>">
                                    <?= $rendu['note'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning">À noter</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($rendu['nom_fichier']): ?>
                                    <a href="../src/controllers/download_submission.php?id=<?= $rendu['id'] ?>" 
                                       class="btn btn-outline-primary" title="Télécharger">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-outline-success" 
                                        onclick="gradeSubmission(<?= $rendu['id'] ?>, '<?= htmlspecialchars(addslashes($rendu['prenom'] . ' ' . $rendu['nom'])) ?>')"
                                        title="Noter">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        <small class="text-muted">
            Total: <?= count($rendus) ?> rendu(s) - 
            <?= count(array_filter($rendus, fn($r) => $r['note'] !== null)) ?> noté(s)
        </small>
    </div>
    <?php
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur de base de données');
}
?>