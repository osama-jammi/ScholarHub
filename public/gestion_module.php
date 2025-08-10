<?php
session_start();

require __DIR__ . '/../src/config/db.php';
$darkMode = $_SESSION['darkMode'] ?? false;

// Vérification de l'authentification
if (!isset($_SESSION['role'])) {
    header("Location: connexion.php");
    exit();
}

// Vérification du code module
if (!isset($_GET['module_code'])) {
    header("Location: accueil.php");
    exit();
}

$moduleCode = $_GET['module_code'];
$role = $_SESSION['role'];
$userId = $_SESSION['user']['id'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Récupération des informations du module
$module = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM Module WHERE code_inscription = ?");
    $stmt->execute([$moduleCode]);
    $module = $stmt->fetch();
    
    if (!$module) {
        header("Location: accueil.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur de récupération du module: " . $e->getMessage());
}

// Récupération du professeur
$professeur = [];
if ($role === 'student') {
    try {
        $stmt = $pdo->prepare("SELECT p.* FROM Professeur p 
                              JOIN Avoir a ON p.id = a.professeur_id 
                              WHERE a.module_code = ?");
        $stmt->execute([$moduleCode]);
        $professeur = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur de récupération du professeur: " . $e->getMessage());
    }
}

// Récupération des étudiants
$etudiants = [];
try {
    $stmt = $pdo->prepare("SELECT e.* FROM Etudiant e 
                          JOIN Inscrit i ON e.id = i.etudiant_id 
                          WHERE i.module_code = ?");
    $stmt->execute([$moduleCode]);
    $etudiants = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur de récupération des étudiants: " . $e->getMessage());
}

// Récupération des travaux
$travaux = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM Travail WHERE module_code = ? ORDER BY date_creation DESC");
    $stmt->execute([$moduleCode]);
    $travaux = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur de récupération des travaux: " . $e->getMessage());
}

// Fonction pour calculer la moyenne
function calculerMoyenne($pdo, $etudiantId, $moduleCode) {
    $stmt = $pdo->prepare("SELECT AVG(note) as moyenne 
                          FROM Rendu r 
                          JOIN Travail t ON r.travail_id = t.id 
                          WHERE r.etudiant_id = ? AND t.module_code = ?");
    $stmt->execute([$etudiantId, $moduleCode]);
    $result = $stmt->fetch();
    return $result['moyenne'] ? round($result['moyenne'], 2) : "N/A";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($module['nom']) ?> - ScholarHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/accueil.css">
    <link rel="stylesheet" href="./css/gestion_module.css?v=<?= filemtime('./css/gestion_module.css') ?>">
</head>
<body <?= $darkMode ? 'class="dark-mode"' : '' ?>>
    <header class="top-nav">
        <i class="fas fa-bars menu-toggle" id="menuToggle" aria-label="Toggle menu"></i>
        <div class="logo-container">
            <h1>SCHOLARHUB</h1>
            <span class="separator">></span>
            <h2><?= $nom ?></h2>
        </div>
        
        <div class="user-actions">
            <button class="btn btn-sm btn-outline-secondary me-2" id="darkModeToggle" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i> <span class="mode-text"><?= $darkMode ? 'Mode clair' : 'Mode sombre' ?></span>
            </button>
            
            <i class="fas fa-plus-circle add-module" id="module-action-trigger" aria-label="Add module"></i>
            
            <div class="user-profile">
                <img src="<?= $photoPath ?>" alt="Photo de profil" class="user-photo">
            </div>
        </div>
    </header>
    <nav class="side-nav" id="sideNav">
        <ul>
            <li class="<?= ($currentPage === 'accueil.php') ? 'active' : '' ?>">
                <a href="./accueil.php">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
            </li>
            <li class="<?= ($currentPage === 'calendar.php') ? 'active' : '' ?>">
                <a href="./calendar.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendrier</span>
                </a>
            </li>
            <li class="<?= ($currentPage === 'Book.php') ? 'active' : '' ?>">
                <a href="./Book.php">
                    <i class="fas fa-book"></i>
                    <span>Cours</span>
                </a>
            </li>
            <li class="<?= ($currentPage === 'cog.php') ? 'active' : '' ?>">
                <a href="./cog.php">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </li>
        </ul>
        
        <!-- Version compacte -->
        <div class="compact-menu">
            <a href="./accueil.php" class="<?= ($currentPage === 'accueil.php') ? 'active' : '' ?>" title="Accueil">
                <i class="fas fa-home"></i>
            </a>
            <a href="./calendar.php" class="<?= ($currentPage === 'calendar.php') ? 'active' : '' ?>" title="Calendrier">
                <i class="fas fa-calendar-alt"></i>
            </a>
            <a href="./Book.php" class="<?= ($currentPage === 'Book.php') ? 'active' : '' ?>" title="Cours">
                <i class="fas fa-book"></i>
            </a>
            <a href="./cog.php" class="<?= ($currentPage === 'cog.php') ? 'active' : '' ?>" title="Paramètres">
                <i class="fas fa-cog"></i>
            </a>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="main-content">
        <div class="module-header">
            <div class="module-title">
                <h2>
                    <i class="fas fa-book-open"></i>
                    <?= htmlspecialchars($module['nom']) ?>
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="accueil.php">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($module['nom']) ?></li>
                    </ol>
                </nav>
            </div>
            
            <?php if ($role === 'prof'): ?>
            <div class="module-actions">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModuleModal">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                
                <div class="btn-group">
                    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-export"></i> Exporter
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../src/controllers/export_students.php?module_code=<?= $moduleCode ?>&format=excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </a></li>
                        <li><a class="dropdown-item" href="../src/controllers/export_students.php?module_code=<?= $moduleCode ?>&format=pdf">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="module-container">
            <!-- Section informations -->
            <div class="module-section">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Informations du module</h3>
                </div>
                
                <div class="section-body">
                    <div class="module-photo-container">
                        <?php if (!empty($module['photo'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($module['photo']) ?>" 
                                 class="module-photo" alt="Photo du module">
                        <?php else: ?>
                            <img src="./assets/default_module.jpg" class="module-photo" alt="Photo par défaut">
                        <?php endif; ?>
                    </div>
                    
                    <div class="module-details">
                        <?php if (!empty($module['syllabus'])): ?>
                            <div class="syllabus">
                                <h4>Description</h4>
                                <p><?= nl2br(htmlspecialchars($module['syllabus'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($professeur): ?>
                            <div class="professor-info">
                                <h4>Professeur</h4>
                                <div class="professor-card">
                                    <?php if (!empty($professeur['photo'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($professeur['photo']) ?>" 
                                             class="professor-photo" alt="Photo professeur">
                                    <?php else: ?>
                                        <img src="./assets/default_user.jpg" class="professor-photo" alt="Photo par défaut">
                                    <?php endif; ?>
                                    
                                    <div class="professor-details">
                                        <h5><?= htmlspecialchars($professeur['prenom'] . ' ' . $professeur['nom']) ?></h5>
                                        <?php if ($role === 'student'): ?>
                                            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($professeur['adresse'] ?? 'non disponible') ?></p>
                                            <p><i class="fas fa-info-circle"></i> <?= htmlspecialchars($professeur['biographie'] ?? 'Aucune biographie disponible') ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Onglets -->
            <div class="module-tabs">
                <ul class="nav nav-tabs" id="moduleTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button">
                            <i class="fas fa-users"></i> Étudiants
                        </button>
                    </li>
                    
                    <?php if ($role === 'prof'): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="works-tab" data-bs-toggle="tab" data-bs-target="#works" type="button">
                            <i class="fas fa-tasks"></i> Travaux
                        </button>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="moduleTabsContent">
                    <!-- Onglet Étudiants -->
                    <div class="tab-pane fade show active" id="students" role="tabpanel">
                        <div class="students-container">
                            <?php if (empty($etudiants)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-user-graduate"></i>
                                    <p>Aucun étudiant inscrit dans ce module</p>
                                </div>
                            <?php else: ?>
                                <div class="students-grid">
                                    <?php foreach ($etudiants as $etudiant): ?>
                                        <div class="student-card">
                                            <div class="student-header">
                                                <?php if (!empty($etudiant['photo'])): ?>
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($etudiant['photo']) ?>" 
                                                         class="student-photo" alt="Photo étudiant">
                                                <?php else: ?>
                                                    <img src="./assets/default_user.jpg" class="student-photo" alt="Photo par défaut">
                                                <?php endif; ?>
                                                
                                                <div class="student-actions">
                                                    <?php if ($role === 'prof'): ?>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="showStudentInfo(<?= $etudiant['id'] ?>)">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" 
                                                                onclick="sendMessage(<?= $etudiant['id'] ?>, '<?= htmlspecialchars(addslashes($etudiant['prenom'] . ' ' . $etudiant['nom'])) ?>')">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="confirmRemoveStudent(<?= $etudiant['id'] ?>, '<?= htmlspecialchars(addslashes($etudiant['prenom'] . ' ' . $etudiant['nom'])) ?>')">
                                                            <i class="fas fa-user-minus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="student-body">
                                                <h5><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h5>
                                                <p class="student-field">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <?= htmlspecialchars($etudiant['fillier'] ?? 'Non spécifiée') ?>
                                                </p>
                                                
                                                <?php if ($role === 'prof'): ?>
                                                    <div class="student-grades">
                                                        <h6>Notes</h6>
                                                        <?php 
                                                        $stmt = $pdo->prepare("SELECT t.titre, r.note 
                                                                              FROM Rendu r 
                                                                              JOIN Travail t ON r.travail_id = t.id 
                                                                              WHERE r.etudiant_id = ? AND t.module_code = ?");
                                                        $stmt->execute([$etudiant['id'], $moduleCode]);
                                                        $notes = $stmt->fetchAll();
                                                        
                                                        if (!empty($notes)): ?>
                                                            <table class="grades-table">
                                                                <tbody>
                                                                    <?php foreach ($notes as $note): ?>
                                                                        <tr>
                                                                            <td><?= htmlspecialchars($note['titre']) ?></td>
                                                                            <td><?= $note['note'] ? $note['note'] : 'N/A' ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        <?php else: ?>
                                                            <p class="no-grades">Aucune note disponible</p>
                                                        <?php endif; ?>
                                                        
                                                        <div class="student-average">
                                                            <span>Moyenne:</span>
                                                            <span class="average-value"><?= calculerMoyenne($pdo, $etudiant['id'], $moduleCode) ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Onglet Travaux (prof seulement) -->
                    <?php if ($role === 'prof'): ?>
                    <div class="tab-pane fade" id="works" role="tabpanel">
                        <div class="works-container">
                            <?php if (empty($travaux)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-tasks"></i>
                                    <p>Aucun travail pour ce module</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWorkModal">
                                        <i class="fas fa-plus"></i> Ajouter un travail
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="works-list">
                                    <?php foreach ($travaux as $travail): ?>
                                        <div class="work-card">
                                            <div class="work-header">
                                                <h5><?= htmlspecialchars($travail['titre']) ?></h5>
                                                <span class="work-deadline">
                                                    <i class="fas fa-clock"></i>
                                                    <?= date('d/m/Y H:i', strtotime($travail['date_limite'])) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="work-body">
                                                <p><?= nl2br(htmlspecialchars($travail['description'])) ?></p>
                                                
                                                <?php if (!empty($travail['fichier'])): ?>
                                                    <div class="work-file">
                                                        <i class="fas fa-paperclip"></i>
                                                        <a href="../src/controllers/download_file.php?type=work&id=<?= $travail['id'] ?>" 
                                                           target="_blank">
                                                            Télécharger le fichier joint
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="work-footer">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewSubmissions(<?= $travail['id'] ?>)">
                                                    <i class="fas fa-eye"></i> Voir les rendus
                                                </button>
                                                
                                                <div class="work-actions">
                                                    <button class="btn btn-sm btn-outline-secondary"
                                                            onclick="editWork(<?= $travail['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDeleteWork(<?= $travail['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Onglet Statistiques -->
                    <div class="tab-pane fade" id="stats" role="tabpanel">
                        <div class="stats-container">
                            <div class="stats-card">
                                <h5><i class="fas fa-users"></i> Participation</h5>
                                <div class="stats-content">
                                    <canvas id="participationChart"></canvas>
                                </div>
                            </div>
                            
                            <div class="stats-card">
                                <h5><i class="fas fa-chart-line"></i> Performances</h5>
                                <div class="stats-content">
                                    <canvas id="gradesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <?php if ($role === 'prof'): ?>
        <!-- Modal Ajouter un travail -->
        <div class="modal fade" id="addWorkModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nouveau travail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addWorkForm" action="../src/controllers/add_work.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="module_code" value="<?= $moduleCode ?>">
                            
                            <div class="mb-3">
                                <label for="workTitle" class="form-label">Titre *</label>
                                <input type="text" class="form-control" id="workTitle" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="workDescription" class="form-label">Description *</label>
                                <textarea class="form-control" id="workDescription" name="description" rows="5" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="workDeadline" class="form-label">Date limite *</label>
                                    <input type="datetime-local" class="form-control" id="workDeadline" name="deadline" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="workFile" class="form-label">Fichier joint</label>
                                    <input type="file" class="form-control" id="workFile" name="file">
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal Modifier le module -->
        <div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le module</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../src/controllers/update_module.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="module_code" value="<?= $moduleCode ?>">
                            
                            <div class="mb-3">
                                <label for="moduleName" class="form-label">Nom du module *</label>
                                <input type="text" class="form-control" id="moduleName" name="name" 
                                       value="<?= htmlspecialchars($module['nom']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="modulePhoto" class="form-label">Image du module</label>
                                <input type="file" class="form-control" id="modulePhoto" name="photo" accept="image/*">
                                <small class="text-muted">Laisser vide pour conserver l'image actuelle</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="moduleSyllabus" class="form-label">Description</label>
                                <textarea class="form-control" id="moduleSyllabus" name="syllabus" rows="8"><?= htmlspecialchars($module['syllabus']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Modal Informations étudiant -->
    <div class="modal fade" id="studentInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-graduate"></i> Informations étudiant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="studentInfoContent">
                    <!-- Chargé dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Rendu des travaux -->
    <div class="modal fade" id="submissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-upload"></i> Rendus des étudiants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="submissionsContent">
                    <!-- Chargé dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des graphiques
        initCharts();
        
        // Gestion du dark mode
        const darkModeToggle = document.getElementById('darkModeToggle');
        const modeText = document.querySelector('.mode-text');
        
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            // Sauvegarder le choix et mettre à jour le texte
            localStorage.setItem('darkMode', isDarkMode);
            modeText.textContent = isDarkMode ? 'Mode clair' : 'Mode sombre';
            
            // Envoyer la préférence au serveur
            fetch('update_darkmode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ darkMode: isDarkMode })
            }).catch(error => console.error('Erreur:', error));
        });

        // Gestion du menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            const sideNav = document.getElementById('sideNav');
            sideNav.classList.toggle('show');
            
            // Ajouter un overlay pour les mobiles
            if (window.innerWidth <= 768) {
                if (sideNav.classList.contains('show')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'side-nav-overlay';
                    overlay.onclick = function() {
                        sideNav.classList.remove('show');
                        this.remove();
                    };
                    document.body.appendChild(overlay);
                } else {
                    const overlay = document.querySelector('.side-nav-overlay');
                    if (overlay) overlay.remove();
                }
            }
        });
    });
    
    // Initialiser les graphiques
    function initCharts() {
        // Graphique de participation
        const participationCtx = document.getElementById('participationChart').getContext('2d');
        const participationChart = new Chart(participationCtx, {
            type: 'bar',
            data: {
                labels: ['Travail 1', 'Travail 2', 'Travail 3', 'Travail 4'],
                datasets: [{
                    label: 'Taux de participation',
                    data: [85, 72, 90, 68],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Pourcentage'
                        }
                    }
                }
            }
        });
        
        // Graphique des notes
        const gradesCtx = document.getElementById('gradesChart').getContext('2d');
        const gradesChart = new Chart(gradesCtx, {
            type: 'line',
            data: {
                labels: ['Travail 1', 'Travail 2', 'Travail 3', 'Travail 4'],
                datasets: [{
                    label: 'Moyenne de la classe',
                    data: [12.5, 14.2, 11.8, 13.6],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 20,
                        title: {
                            display: true,
                            text: 'Note /20'
                        }
                    }
                }
            }
        });
    }
    
    // Afficher les informations d'un étudiant
    function showStudentInfo(studentId) {
        fetch(`../src/controllers/get_student_info.php?id=${studentId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('studentInfoContent').innerHTML = data;
                const modal = new bootstrap.Modal(document.getElementById('studentInfoModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('danger', 'Erreur', 'Impossible de charger les informations');
            });
    }
    
    // Envoyer un message à un étudiant
    function sendMessage(studentId, studentName) {
        const professorName = '<?= htmlspecialchars($_SESSION['user']['prenom']) . ' ' . htmlspecialchars($_SESSION['user']['nom']) ?>';
        
        const modalHtml = `
        <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Envoyer un message</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="messageForm">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">De :</label>
                                    <input type="text" class="form-control" value="${professorName}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">À :</label>
                                    <input type="text" class="form-control" value="${studentName}" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="messageSubject" class="form-label">Sujet *</label>
                                <input type="text" class="form-control" id="messageSubject" required>
                            </div>
                            <div class="mb-3">
                                <label for="messageContent" class="form-label">Message *</label>
                                <textarea class="form-control" id="messageContent" rows="8" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" onclick="submitMessage(${studentId})">
                                <i class="fas fa-paper-plane"></i> Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
        messageModal.show();
        
        document.getElementById('messageModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
    
    function submitMessage(studentId) {
        const subject = document.getElementById('messageSubject').value;
        const content = document.getElementById('messageContent').value;
        
        if (!subject.trim() || !content.trim()) {
            showAlert('danger', 'Erreur', 'Veuillez remplir tous les champs');
            return;
        }
        
        fetch('../src/controllers/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                studentId: studentId,
                subject: subject,
                content: content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Succès', 'Message envoyé avec succès');
                bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
            } else {
                showAlert('danger', 'Erreur', data.message || 'Erreur lors de l\'envoi');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('danger', 'Erreur', 'Une erreur est survenue');
        });
    }
    
    // Supprimer un étudiant du module
    function confirmRemoveStudent(studentId, studentName) {
        if (confirm(`Voulez-vous vraiment supprimer ${studentName} de ce module ?`)) {
            fetch(`../src/controllers/remove_student.php?student_id=${studentId}&module_code=<?= $moduleCode ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Succès', `${studentName} a été supprimé du module`);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('danger', 'Erreur', data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert('danger', 'Erreur', 'Une erreur est survenue');
                });
        }
    }
    
    // Voir les rendus d'un travail
    function viewSubmissions(workId) {
        fetch(`../src/controllers/get_submissions.php?work_id=${workId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('submissionsContent').innerHTML = data;
                const modal = new bootstrap.Modal(document.getElementById('submissionsModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('danger', 'Erreur', 'Impossible de charger les rendus');
            });
    }
    
    // Modifier un travail
    function editWork(workId) {
        fetch(`../src/controllers/get_work_details.php?id=${workId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Créer un modal pour l'édition
                    const modalHtml = `
                    <div class="modal fade" id="editWorkModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le travail</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="editWorkForm" action="../src/controllers/update_work.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" name="work_id" value="${workId}">
                                        
                                        <div class="mb-3">
                                            <label for="editWorkTitle" class="form-label">Titre *</label>
                                            <input type="text" class="form-control" id="editWorkTitle" name="title" 
                                                   value="${escapeHtml(data.work.titre)}" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="editWorkDescription" class="form-label">Description *</label>
                                            <textarea class="form-control" id="editWorkDescription" name="description" 
                                                      rows="5" required>${escapeHtml(data.work.description)}</textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="editWorkDeadline" class="form-label">Date limite *</label>
                                                <input type="datetime-local" class="form-control" id="editWorkDeadline" 
                                                       name="deadline" value="${formatDateTimeForInput(data.work.date_limite)}" required>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="editWorkFile" class="form-label">Fichier joint</label>
                                                <input type="file" class="form-control" id="editWorkFile" name="file">
                                                ${data.work.fichier ? `
                                                <div class="mt-2">
                                                    <i class="fas fa-paperclip"></i>
                                                    <a href="../src/controllers/download_file.php?type=work&id=${workId}" target="_blank">
                                                        Fichier actuel
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" 
                                                            onclick="confirmDeleteFile(${workId})">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Enregistrer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>`;
                    
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    const modal = new bootstrap.Modal(document.getElementById('editWorkModal'));
                    modal.show();
                    
                    document.getElementById('editWorkModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                } else {
                    showAlert('danger', 'Erreur', data.message || 'Impossible de charger les détails');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('danger', 'Erreur', 'Une erreur est survenue');
            });
    }
    
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function formatDateTimeForInput(dateTimeString) {
        if (!dateTimeString) return '';
        const date = new Date(dateTimeString);
        return date.toISOString().slice(0, 16);
    }
    
    // Supprimer un travail
    function confirmDeleteWork(workId) {
        if (confirm("Voulez-vous vraiment supprimer ce travail ? Tous les rendus associés seront également supprimés.")) {
            fetch(`../src/controllers/delete_work.php?id=${workId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Succès', 'Travail supprimé avec succès');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('danger', 'Erreur', data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert('danger', 'Erreur', 'Une erreur est survenue');
                });
        }
    }
    
    // Supprimer le fichier d'un travail
    function confirmDeleteFile(workId) {
        if (confirm("Voulez-vous vraiment supprimer le fichier joint à ce travail ?")) {
            fetch(`../src/controllers/delete_work_file.php?id=${workId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Succès', 'Fichier supprimé avec succès');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('danger', 'Erreur', data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert('danger', 'Erreur', 'Une erreur est survenue');
                });
        }
    }
    
    // Noter un rendu
    function gradeSubmission(submissionId, studentName) {
        const note = prompt(`Entrez la note pour ${studentName} (0-20):`);
        if (note !== null && note >= 0 && note <= 20) {
            const commentaire = prompt("Commentaire (optionnel):");
            
            fetch('../src/controllers/grade_submission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    submission_id: submissionId,
                    note: note,
                    commentaire: commentaire || ''
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Succès', 'Note enregistrée avec succès');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', 'Erreur', data.message || 'Erreur lors de l\'enregistrement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('danger', 'Erreur', 'Une erreur est survenue');
            });
        } else if (note !== null) {
            showAlert('warning', 'Attention', 'La note doit être entre 0 et 20');
        }
    }
    
    // Afficher une notification
    function showAlert(type, title, message) {
        const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1100; min-width: 300px;">
            <strong>${title}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        
        document.body.insertAdjacentHTML('afterbegin', alertHtml);
        
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getInstance(alert).close();
            }
        }, 5000);
    }
    </script>
</body>
</html>