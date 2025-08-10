<?php
session_start();

require __DIR__ . '/../src/config/db.php';

// Vérifier que l'utilisateur est un professeur connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof' || !isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit();
}

$profId = $_SESSION['user']['id'];
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;

// Récupérer les modules enseignés par le professeur
$stmt = $pdo->prepare("SELECT A.module_code, M.nom AS module_name, M.photo 
                       FROM Avoir A 
                       JOIN Module M ON A.module_code = M.code_inscription 
                       WHERE A.professeur_id = ?");
$stmt->execute([$profId]);
$modules = $stmt->fetchAll();

// Si un module est sélectionné
$selectedModule = null;
$travaux = [];
$rendus = [];

if (isset($_GET['module_code'])) {
    $moduleCode = $_GET['module_code'];
    
    // Vérifier que le professeur a bien accès à ce module
    $hasAccess = false;
    foreach ($modules as $module) {
        if ($module['module_code'] == $moduleCode) {
            $hasAccess = true;
            $selectedModule = $module;
            break;
        }
    }
    
    if ($hasAccess) {
        // Récupérer les travaux du module
        $stmt = $pdo->prepare("SELECT * FROM Travail 
                              WHERE module_code = ? 
                              ORDER BY date_limite DESC");
        $stmt->execute([$moduleCode]);
        $travaux = $stmt->fetchAll();
        
        // Si un travail est sélectionné, récupérer les rendus
        if (isset($_GET['travail_id'])) {
            $travailId = (int)$_GET['travail_id'];
            
            $stmt = $pdo->prepare("SELECT t.* FROM Travail t WHERE t.id = ? AND t.module_code = ?");
            $stmt->execute([$travailId, $moduleCode]);
            $selectedTravail = $stmt->fetch();
            
            if ($selectedTravail) {
                $stmt = $pdo->prepare("SELECT r.*, e.nom, e.prenom, e.id as etudiant_id
                                      FROM Rendu r
                                      JOIN Etudiant e ON r.etudiant_id = e.id
                                      WHERE r.travail_id = ?
                                      ORDER BY e.nom, e.prenom");
                $stmt->execute([$travailId]);
                $rendus = $stmt->fetchAll();
            }
        }
    }
}

// Traitement de la notation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_rendu'])) {
    $renduId = (int)$_POST['rendu_id'];
    $note = (float)$_POST['note'];
    $commentaire = trim($_POST['commentaire']);
    $travailId = (int)$_POST['travail_id'];
    $moduleCode = $_POST['module_code'];
    
    try {
        $pdo->beginTransaction();
        
        // Vérifier que le rendu appartient bien à un travail du professeur
        $stmt = $pdo->prepare("SELECT r.id 
                              FROM Rendu r
                              JOIN Travail t ON r.travail_id = t.id
                              JOIN Avoir a ON t.module_code = a.module_code
                              WHERE r.id = ? AND a.professeur_id = ?");
        $stmt->execute([$renduId, $profId]);
        
        if ($stmt->fetch()) {
            // Mettre à jour la note et le commentaire
            $stmt = $pdo->prepare("UPDATE Rendu 
                                  SET note = ?, commentaire = ?, date_correction = NOW()
                                  WHERE id = ?");
            $stmt->execute([$note, $commentaire, $renduId]);
            
            $pdo->commit();
            $_SESSION['success'] = "Note enregistrée avec succès";
        } else {
            throw new Exception("Vous n'êtes pas autorisé à noter ce rendu");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
    
    header("Location: view_rendus.php?module_code=$moduleCode&travail_id=$travailId");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Travaux - ScholarHub</title>
    <link rel="stylesheet" href="./css/accueil.css">
    <link rel="stylesheet" href="./css/book.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/dark-mode.css"> 
    <style>
        .container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 300px;
            background: #f5f5f5;
            padding: 20px;
            overflow-y: auto;
        }
        
        .dark-mode .sidebar {
            background: #2c3e50;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .module-card {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .dark-mode .module-card {
            background: #34495e;
        }
        
        .module-card:hover {
            background: #e0e0e0;
        }
        
        .dark-mode .module-card:hover {
            background: #3d566e;
        }
        
        .module-card.active {
            background: #3498db;
            color: white;
        }
        
        .module-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .travail-card {
            background: white;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dark-mode .travail-card {
            background: #34495e;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .travail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .travail-title {
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .travail-date {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .dark-mode .travail-date {
            color: #bdc3c7;
        }
        
        .rendu-list {
            margin-top: 20px;
        }
        
        .rendu-item {
            background: #f9f9f9;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dark-mode .rendu-item {
            background: #3d566e;
        }
        
        .rendu-info {
            flex: 1;
        }
        
        .rendu-student {
            font-weight: bold;
        }
        
        .rendu-date {
            font-size: 0.8em;
            color: #7f8c8d;
        }
        
        .dark-mode .rendu-date {
            color: #bdc3c7;
        }
        
        .rendu-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .note-form {
            margin-top: 10px;
            padding: 15px;
            background: #ecf0f1;
            border-radius: 5px;
        }
        
        .dark-mode .note-form {
            background: #3d566e;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .dark-mode .form-group input,
        .dark-mode .form-group textarea {
            background: #2c3e50;
            border-color: #34495e;
            color: white;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .note-display {
            font-weight: bold;
            margin-top: 5px;
        }
        
        .note-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .dark-mode .note-label {
            color: #bdc3c7;
        }
    </style>
</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
    <!-- Barre horizontale -->
    <header class="top-nav">
        <i class="fas fa-bars menu-toggle" id="sidebarToggle"></i>
        <h1>SCHOLARHUB > </h1>
        <h2>Gestion des Travaux</h2>
        <div class="user-actions">
            <?php
                $stmt = $pdo->prepare("SELECT photo FROM Professeur WHERE id = ?");
                $stmt->execute([$profId]);
                $user = $stmt->fetch();
            ?>
            <img src="<?php echo !empty($user['photo']) ? 'data:image/jpeg;base64,' . base64_encode($user['photo']) : '../../public/assets/default_user.jpg'; ?>" 
                 alt="Photo utilisateur" class="user-photo">
        </div>
    </header>

    <!-- Barre verticale gauche -->
    <nav class="side-nav">
        <ul>
            <a href="./accueil.php"><li>
                <img src="./assets/home.png" alt="">
                <span>Home</span>
            </li></a>
            <a href="./Calendar.php"><li>
                <img src="./assets/calendar.png" alt="">
                <span>Calendar</span>
            </li></a>
            <a href="./Book.php"><li>
                <img src="./assets/book.png" alt="">
                <span>Courses</span>
            </li></a>
            <a href="./cog.php"><li>
                <img src="./assets/cog.png" alt="">
                <span>Settings</span>
            </li></a>
        </ul>
    </nav>

    <!-- Contenu principal -->
    <div class="container">
        <!-- Sidebar avec les modules -->
        <div class="sidebar">
            <h3>Mes Modules</h3>
            <?php foreach ($modules as $module): ?>
                <?php $isActive = isset($_GET['module_code']) && $_GET['module_code'] == $module['module_code']; ?>
                <div class="module-card <?= $isActive ? 'active' : '' ?>" 
                     onclick="window.location='view_rendus.php?module_code=<?= $module['module_code'] ?>'">
                    <div style="display: flex; align-items: center;">
                        <?php if (!empty($module['photo'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($module['photo']) ?>" 
                                 alt="Photo du module" class="module-image">
                        <?php else: ?>
                            <img src="default_module.jpg" alt="Module" class="module-image">
                        <?php endif; ?>
                        <div>
                            <h4><?= htmlspecialchars($module['module_name']) ?></h4>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Contenu du module sélectionné -->
        <div class="main-content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if ($selectedModule): ?>
                <h2><?= htmlspecialchars($selectedModule['module_name']) ?></h2>
                
                <?php if (isset($_GET['travail_id']) && !empty($selectedTravail)): ?>
                    <!-- Vue des rendus pour un travail spécifique -->
                    <div class="travail-card">
                        <div class="travail-header">
                            <div class="travail-title"><?= htmlspecialchars($selectedTravail['titre']) ?></div>
                            <div class="travail-date">
                                À rendre avant le <?= date('d/m/Y H:i', strtotime($selectedTravail['date_limite'])) ?>
                            </div>
                        </div>
                        <p><?= nl2br(htmlspecialchars($selectedTravail['description'])) ?></p>
                        
                        <div class="rendu-list">
                            <h3>Rendus des étudiants (<?= count($rendus) ?>)</h3>
                            
                            <?php if (empty($rendus)): ?>
                                <p>Aucun rendu pour le moment.</p>
                            <?php else: ?>
                                <?php foreach ($rendus as $rendu): ?>
                                    <div class="rendu-item">
                                        <div class="rendu-info">
                                            <div class="rendu-student">
                                                <?= htmlspecialchars($rendu['prenom']) ?> <?= htmlspecialchars($rendu['nom']) ?>
                                            </div>
                                            <div class="rendu-date">
                                                Soumis le <?= date('d/m/Y H:i', strtotime($rendu['date_soumission'])) ?>
                                            </div>
                                            
                                            <?php if (!is_null($rendu['note'])): ?>
                                                <div class="note-display">
                                                    Note: <?= $rendu['note'] ?>/20
                                                </div>
                                                <?php if (!empty($rendu['commentaire'])): ?>
                                                    <div class="note-comment">
                                                        Commentaire: <?= nl2br(htmlspecialchars($rendu['commentaire'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="rendu-actions">
                                        <a href="download.php?type=rendu&rendu_id=<?= $rendu['id'] ?>" 
                                            class="btn btn-primary" download>
                                                <i class="fas fa-download"></i> Télécharger
                                            </a>
                                                    
                                            <?php if (is_null($rendu['note'])): ?>
                                                <button onclick="toggleNoteForm(<?= $rendu['id'] ?>)" 
                                                        class="btn btn-success">
                                                    <i class="fas fa-edit"></i> Noter
                                                </button>
                                            <?php else: ?>
                                                <button onclick="toggleNoteForm(<?= $rendu['id'] ?>)" 
                                                        class="btn btn-secondary">
                                                    <i class="fas fa-edit"></i> Modifier note
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Formulaire de notation (caché par défaut) -->
                                    <div id="note-form-<?= $rendu['id'] ?>" class="note-form" style="display: none;">
                                        <form method="POST">
                                            <input type="hidden" name="rendu_id" value="<?= $rendu['id'] ?>">
                                            <input type="hidden" name="travail_id" value="<?= $selectedTravail['id'] ?>">
                                            <input type="hidden" name="module_code" value="<?= $selectedModule['module_code'] ?>">
                                            
                                            <div class="form-group">
                                                <label for="note-<?= $rendu['id'] ?>">Note (/20)</label>
                                                <input type="number" id="note-<?= $rendu['id'] ?>" name="note" 
                                                       min="0" max="20" step="0.5" 
                                                       value="<?= $rendu['note'] ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="commentaire-<?= $rendu['id'] ?>">Commentaire</label>
                                                <textarea id="commentaire-<?= $rendu['id'] ?>" name="commentaire" 
                                                          rows="3"><?= htmlspecialchars($rendu['commentaire'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="button" onclick="toggleNoteForm(<?= $rendu['id'] ?>)" 
                                                        class="btn btn-danger">
                                                    Annuler
                                                </button>
                                                <button type="submit" name="note_rendu" class="btn btn-success">
                                                    Enregistrer
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <a href="view_rendus.php?module_code=<?= $selectedModule['module_code'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Retour aux travaux
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Liste des travaux du module -->
                    <div class="travaux-list">
                        <h3>Travaux à faire</h3>
                        
                        <?php if (empty($travaux)): ?>
                            <p>Aucun travail pour ce module.</p>
                        <?php else: ?>
                            <?php foreach ($travaux as $travail): ?>
                                <div class="travail-card">
                                    <div class="travail-header">
                                        <div class="travail-title"><?= htmlspecialchars($travail['titre']) ?></div>
                                        <div class="travail-date">
                                            À rendre avant le <?= date('d/m/Y H:i', strtotime($travail['date_limite'])) ?>
                                        </div>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($travail['description'])) ?></p>
                                    
                                    <div style="margin-top: 15px;">
                                        <?php
                                            // Compter le nombre de rendus pour ce travail
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Rendu WHERE travail_id = ?");
                                            $stmt->execute([$travail['id']]);
                                            $nbRendus = $stmt->fetchColumn();
                                        ?>
                                        
                                        <a href="view_rendus.php?module_code=<?= $selectedModule['module_code'] ?>&travail_id=<?= $travail['id'] ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-users"></i> Voir les rendus (<?= $nbRendus ?>)
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h2>Gestion des Travaux</h2>
                <p>Sélectionnez un module pour voir les travaux et les rendus des étudiants.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fonction pour afficher/masquer le formulaire de notation
        function toggleNoteForm(renduId) {
            const form = document.getElementById('note-form-' + renduId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        // Toggle le menu latéral sur mobile
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.side-nav').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    </script>
</body>
</html>