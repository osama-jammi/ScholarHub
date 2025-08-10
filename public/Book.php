<?php
session_start();

require __DIR__ . '/../src/config/db.php';
require __DIR__ . '/../src/includes/mailer.php';

$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;


// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prof') {
//     header("Location: connexion.php");
//     exit();
// }


if (!isset($_SESSION['role']) ) {
    header("Location: connexion.php");
    exit();
}

$role = $_SESSION['role'];
$userId = $_SESSION['user']['id'];
$nom = $_SESSION['user']['nom'];

// Récupérer les modules selon le rôle
if ($role === 'prof') {
    $stmt = $pdo->prepare("SELECT A.module_code, M.nom AS module_name, A.annee_scolaire, M.photo 
                         FROM Avoir A 
                         JOIN Module M ON A.module_code = M.code_inscription 
                         WHERE A.professeur_id = ?");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
} elseif ($role === 'student') {
    $stmt = $pdo->prepare("SELECT M.*, I.annee_scolaire 
                          FROM Module M 
                          JOIN Inscrit I ON M.code_inscription = I.module_code 
                          WHERE I.etudiant_id = ?");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}


$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = $searchTerm ? "%$searchTerm%" : '%%';

// Si un module est sélectionné
$selectedModule = null;
$publications = [];
$travaux = [];

if (isset($_GET['module_code'])) {
    $moduleCode = $_GET['module_code'];
    $hasAccess = false;
    
    foreach ($modules as $module) {
        $currentModuleCode = ($role === 'prof') ? $module['module_code'] : $module['code_inscription'];
        if ($currentModuleCode == $moduleCode) {
            $hasAccess = true;
            $selectedModule = $module;
            break;
        }
    }
    
    if ($hasAccess) {
        // Récupérer les cours du module (pour le select)
        $stmt = $pdo->prepare("SELECT c.id, c.nom 
            FROM Cours c
            JOIN Contient2 ct ON c.id = ct.cours_id
            WHERE ct.module_code = ?");
        $stmt->execute([$moduleCode]);
        $coursDuModule = $stmt->fetchAll();
        // Récupérer les publications du module
        if ($searchTerm) {
            $stmt = $pdo->prepare("SELECT p.*, pr.nom AS prof_nom, pr.prenom AS prof_prenom
                      FROM Publication p
                      JOIN Publier pu ON p.id = pu.publication_id
                      JOIN Professeur pr ON pu.professeur_id = pr.id
                      JOIN Contient2 c ON p.cours_id = c.cours_id
                      WHERE c.module_code = ?
                      AND (p.titre LIKE ? OR p.description LIKE ?)
                      ORDER BY p.date_publication DESC");
            $stmt->execute([$moduleCode, $searchCondition, $searchCondition]);
        } else {
            $stmt = $pdo->prepare("SELECT p.*, pr.nom AS prof_nom, pr.prenom AS prof_prenom
                      FROM Publication p
                      JOIN Publier pu ON p.id = pu.publication_id
                      JOIN Professeur pr ON pu.professeur_id = pr.id
                      JOIN Contient2 c ON p.cours_id = c.cours_id
                      WHERE c.module_code = ?
                      ORDER BY p.date_publication DESC");
            $stmt->execute([$moduleCode]);
        }
        $publications = $stmt->fetchAll();

        // Récupérer les travaux du module
        if ($role === 'prof') {
           if ($searchTerm) {
                $stmt = $pdo->prepare("SELECT t.*, COUNT(r.id) AS nb_rendus
                                      FROM Travail t
                                      LEFT JOIN Rendu r ON t.id = r.travail_id
                                      WHERE t.module_code = ?
                                      AND (t.titre LIKE ? OR t.description LIKE ?)
                                      GROUP BY t.id
                                      ORDER BY t.date_limite DESC");
                $stmt->execute([$moduleCode, $searchCondition, $searchCondition]);
            } else {
                $stmt = $pdo->prepare("SELECT t.*, COUNT(r.id) AS nb_rendus
                                      FROM Travail t
                                      LEFT JOIN Rendu r ON t.id = r.travail_id
                                      WHERE t.module_code = ?
                                      GROUP BY t.id
                                      ORDER BY t.date_limite DESC");
                $stmt->execute([$moduleCode]);
            }
            $travaux = $stmt->fetchAll();
        } else {
            if ($searchTerm) {
                $stmt = $pdo->prepare("SELECT t.*, r.id AS rendu_id, r.fichier, r.date_soumission 
                                      FROM Travail t 
                                      LEFT JOIN Rendu r ON t.id = r.travail_id AND r.etudiant_id = ?
                                      WHERE t.module_code = ? 
                                      AND (t.titre LIKE ? OR t.description LIKE ?)
                                      ORDER BY t.date_limite DESC");
                $stmt->execute([$userId, $moduleCode, $searchCondition, $searchCondition]);
            } else {
                $stmt = $pdo->prepare("SELECT t.*, r.id AS rendu_id, r.fichier, r.date_soumission 
                                      FROM Travail t 
                                      LEFT JOIN Rendu r ON t.id = r.travail_id AND r.etudiant_id = ?
                                      WHERE t.module_code = ? 
                                      ORDER BY t.date_limite DESC");
                $stmt->execute([$userId, $moduleCode]);
            }
            $travaux = $stmt->fetchAll();
        }
    }
}




if (isset($_POST['add_cours']) && isset($_POST['module_code'])) {
    $nom = trim($_POST['nom_cours']);
    $description = trim($_POST['description_cours']);
    $moduleCode = $_POST['module_code'];
    $profId = $_SESSION['user']['id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Créer le cours
        $stmt = $pdo->prepare("INSERT INTO Cours (nom, description) VALUES (?, ?)");
        $stmt->execute([$nom, $description]);
        $coursId = $pdo->lastInsertId();
        
        // 2. Lier le cours au module
        $stmt = $pdo->prepare("INSERT INTO Contient2 (module_code, cours_id) VALUES (?, ?)");
        $stmt->execute([$moduleCode, $coursId]);
        
        $pdo->commit();
        $_SESSION['success'] = "Cours ajouté avec succès";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de l'ajout du cours: " . $e->getMessage();
    }
    
    header("Location: Book.php?module_code=" . $moduleCode);
    exit();
}

// Traitement du formulaire d'ajout de travail (prof)
if ($role === 'prof' && isset($_POST['add_travail']) && isset($_POST['module_code'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $dateLimite = $_POST['date_limite'];
    $moduleCode = $_POST['module_code'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO Travail (titre, description, date_limite, module_code) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $dateLimite, $moduleCode]);
        
        $pdo->commit();
        $_SESSION['success'] = "Travail ajouté avec succès";
        envoyerNotificationTravail($pdo, $moduleCode, $titre, $description, $dateLimite);
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de l'ajout du travail: " . $e->getMessage();
    }
    header("Location: Book.php?module_code=" . $moduleCode);
    exit();
}

// Traitement du formulaire de publication (prof)
if ($role === 'prof' && isset($_POST['add_publication']) && isset($_POST['cours_id'])) {
    $contenu = trim($_POST['contenu']);
    $coursId = (int)$_POST['cours_id'];
    $moduleCode = $_POST['module_code'];
    
    try {
        $pdo->beginTransaction();

        // Vérifier que le professeur existe
        $stmt = $pdo->prepare("SELECT id FROM Professeur WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            throw new Exception("Professeur non trouvé");
        }

        // Insérer la publication (avec ou sans fichier)
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['fichier']['tmp_name']);
            $allowedTypes = [
                'application/pdf', 
                'image/jpeg', 
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Type de fichier non autorisé");
            }

            $fileContent = file_get_contents($_FILES['fichier']['tmp_name']);
            $nomFichier = basename($_FILES['fichier']['name']);

            $stmt = $pdo->prepare("INSERT INTO Publication 
                                 (titre, description, ressource, type_fichier, nom_fichier, cours_id) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, substr($contenu, 0, 100));
            $stmt->bindValue(2, $contenu);
            $stmt->bindValue(3, $fileContent, PDO::PARAM_LOB);
            $stmt->bindValue(4, $mimeType);
            $stmt->bindValue(5, $nomFichier);
            $stmt->bindValue(6, $coursId);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("INSERT INTO Publication 
                                 (titre, description, cours_id) 
                                 VALUES (?, ?, ?)");
            $stmt->execute([
                substr($contenu, 0, 100),
                $contenu,
                $coursId
            ]);
        }

        $publicationId = $pdo->lastInsertId();

        // Créer la relation dans Publier
        $stmt = $pdo->prepare("INSERT INTO Publier (professeur_id, publication_id) VALUES (?, ?)");
        $stmt->execute([$userId, $publicationId]);
        
        $pdo->commit();
        $_SESSION['success'] = "Publication ajoutée avec succès";
        $lienPublication = "http://127.0.0.1/ScholarHub/public/Book.php?module_code=" . $moduleCode;
        envoyerAnnonceAuxEtudiants($pdo, $moduleCode, substr($contenu, 0, 100), $contenu, $lienPublication);
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        error_log("Erreur publication: " . $e->getMessage());
    }
    
    header("Location: Book.php?module_code=" . $moduleCode);
    exit();
}


// Traitement du formulaire de rendu (étudiant)
// Traitement du formulaire de rendu (étudiant)
if ($role === 'student' && isset($_POST['submit_rendu']) && isset($_POST['travail_id'])) {
    $travailId = (int)$_POST['travail_id'];
    $moduleCode = $_POST['module_code'];
    $inTransaction = false;
    
    try {
        // Vérifier si la date limite est dépassée
        $stmt = $pdo->prepare("SELECT date_limite FROM Travail WHERE id = ?");
        $stmt->execute([$travailId]);
        $travail = $stmt->fetch();
        
        if (!$travail) {
            throw new Exception("Travail non trouvé");
        }
        
        $dateLimite = new DateTime($travail['date_limite']);
        $now = new DateTime();
        
        if ($now > $dateLimite) {
            throw new Exception("La date limite est dépassée, vous ne pouvez plus soumettre ce travail");
        }
        
        // Vérifier le fichier
        if ($_FILES['rendu_fichier']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors du téléchargement du fichier");
        }

        // Vérifier la taille du fichier (max 10MB)
        if ($_FILES['rendu_fichier']['size'] > 10 * 1024 * 1024) {
            throw new Exception("Le fichier est trop volumineux (max 10MB)");
        }

        // Démarrer la transaction seulement si tout est OK jusqu'ici
        $pdo->beginTransaction();
        $inTransaction = true;

        // Récupérer les infos du fichier
        $fichier = file_get_contents($_FILES['rendu_fichier']['tmp_name']);
        $nomFichier = basename($_FILES['rendu_fichier']['name']);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $typeFichier = $finfo->file($_FILES['rendu_fichier']['tmp_name']);
        
        // Vérifier si un rendu existe déjà
        $stmt = $pdo->prepare("SELECT id FROM Rendu WHERE travail_id = ? AND etudiant_id = ?");
        $stmt->execute([$travailId, $userId]);
        
        if ($stmt->fetch()) {
            // Mise à jour du rendu existant
            $stmt = $pdo->prepare("UPDATE Rendu 
                                 SET fichier = ?, 
                                     nom_fichier = ?, 
                                     type_fichier = ?, 
                                     date_soumission = NOW(),
                                     note = NULL,
                                     commentaire = NULL,
                                     date_correction = NULL
                                 WHERE travail_id = ? AND etudiant_id = ?");
            $stmt->execute([
                $fichier, 
                $nomFichier, 
                $typeFichier,
                $travailId, 
                $userId
            ]);
        } else {
            // Insertion d'un nouveau rendu
            $stmt = $pdo->prepare("INSERT INTO Rendu 
                                 (fichier, nom_fichier, type_fichier, travail_id, etudiant_id) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $fichier, 
                $nomFichier, 
                $typeFichier,
                $travailId, 
                $userId
            ]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Rendu soumis avec succès";
    } catch (Exception $e) {
        if ($inTransaction) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Erreur lors de la soumission du rendu: " . $e->getMessage();
    }
    
    header("Location: Book.php?module_code=" . $moduleCode);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cours - ScholarHub</title>
    <link rel="stylesheet" href="./css/accueil.css">
    <link rel="stylesheet" href="./css/book.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/dark-mode.css"> 
    <script src="js/darkmode.js" defer></script>
    <style>
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-success {
            background-color: #2ecc71;
        }
        .btn-danger {
            background-color: #e74c3c;
        }
        .btn-secondary {
            background-color: #95a5a6;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-right: 10px;
        }
        .status-soumis {
            background-color: #d4edda;
            color: #155724;
        }
        .status-en-retard {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-a-faire {
            background-color: #fff3cd;
            color: #856404;
        }
        .item-meta {
            font-size: 0.9em;
            color: #6c757d;
            margin: 5px 0;
        }

        .search-container {
            display: flex;
            margin-bottom: 15px;
            max-width: 500px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 1em;
        }

        .search-btn {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #2980b9;
        }

         /* Animation pour le chargement */
    @keyframes pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }
    
    .loading {
        animation: pulse 1.5s infinite;
    }
    
    /* Style pour les icônes dans les boutons */
    .btn-icon {
        margin-right: 8px;
    }
    
    /* Style pour les cartes vides */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-light);
    }
    
    .empty-state i {
        font-size: 3em;
        margin-bottom: 15px;
        color: var(--secondary-color);
    }
    </style>
</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
    <?php //include __DIR__ . '/src/views/header.php'; 
     include __DIR__ . '/../src/views/header.php';

    ?>

    <!-- Contenu principal -->
    <div class="container">
        <!-- Sidebar avec les modules -->
        <div class="sidebar">
            <h3>Mes Modules</h3>
            <?php foreach ($modules as $module): ?>
                <?php 
                    $moduleCode = $role === 'prof' ? $module['module_code'] : $module['code_inscription'];
                    $isActive = isset($_GET['module_code']) && $_GET['module_code'] == $moduleCode;
                ?>
                <div class="module-card <?= $isActive ? 'active' : '' ?>" 
                     onclick="window.location='Book.php?module_code=<?= $moduleCode ?>'">
                    <div style="display: flex; align-items: center;">
                        <?php if (!empty($module['photo'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($module['photo']) ?>" 
                                 alt="Photo du module" class="module-image">
                        <?php else: ?>
                            <img src="default_module.jpg" alt="Module" class="module-image">
                        <?php endif; ?>
                        <div>
                            <h4><?= htmlspecialchars($role === 'prof' ? $module['module_name'] : $module['nom']) ?></h4>
                            <?php if ($role === 'student'): ?>
                                <?php
                                    $stmt = $pdo->prepare("SELECT P.nom, P.prenom 
                                                          FROM Professeur P 
                                                          JOIN Avoir A ON P.id = A.professeur_id 
                                                          WHERE A.module_code = ?");
                                    $stmt->execute([$moduleCode]);
                                    $prof = $stmt->fetch();
                                ?>
                                <p style="font-size: 0.8em; color: #666;">Prof: <?= $prof ? htmlspecialchars($prof['prenom']) . ' ' . htmlspecialchars($prof['nom']) : 'N/A' ?></p>
                            <?php endif; ?>
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
                <h2><?= htmlspecialchars($role === 'prof' ? $selectedModule['module_name'] : $selectedModule['nom']) ?></h2>
                

                <div class="search-container">
                    <form method="GET" action="Book.php" style="display: flex; width: 100%; position: relative;">
                        <input type="hidden" name="module_code" value="<?= $moduleCode ?>">
                        <input type="text" name="search" id="searchInput" 
                            placeholder="Rechercher dans les cours..." class="search-input"
                            value="<?= htmlspecialchars($searchTerm) ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="Book.php?module_code=<?= $moduleCode ?>" class="search-btn" 
                            style="right: 45px; background-color: var(--danger-color);">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <small id="coursResultCount" style="display: block; margin-bottom: 15px; color: #666;">
                    <?= count($coursDuModule) ?> cours trouvés
                </small>   


                <?php if ($role === 'prof'): ?>
                    <!-- Formulaire d'ajout de cours (optionnel) -->
                        <div class="form-container">
                            <h3 class="section-title">Ajouter un nouveau cours</h3>
                            <form method="POST" action="ajouter_cours.php">
                                <input type="hidden" name="module_code" value="<?= $selectedModule['module_code'] ?>">
                                
                                <div class="form-group">
                                    <label for="nom_cours">Nom du cours :</label>
                                    <input type="text" name="nom_cours" id="nom_cours" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description_cours">Description :</label>
                                    <textarea name="description_cours" id="description_cours" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" name="add_cours" class="submit-btn">
                                    <i class="fas fa-plus"></i> Ajouter le cours
                                </button>
                            </form>
                        </div>
                    <!-- Formulaire d'ajout de publication -->
                    <div class="form-container">
                        <h3 class="section-title">Publier une annonce</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="module_code" value="<?= $selectedModule['module_code'] ?>">
                            
                            <!-- Ajoutez ce bloc pour sélectionner le cours -->
                            <div class="form-group">
                                <label for="cours_id">Sélectionnez un cours :</label>
                                <select name="cours_id" id="cours_id" required>
                                    <option value="">-- Choisissez un cours --</option>
                                    <?php foreach ($coursDuModule as $cours): ?>
                                        <option value="<?= $cours['id'] ?>">
                                            <?= htmlspecialchars($cours['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <textarea name="contenu" placeholder="Contenu de l'annonce..." required rows="4"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="fichier">Ajouter un fichier (optionnel) :</label>
                                <input type="file" name="fichier" id="fichier">
                            </div>
                            
                            <button type="submit" name="add_publication" class="submit-btn">
                                <i class="fas fa-paper-plane"></i> Publier
                            </button>
                        </form>
                    </div>
                    
                    <!-- Formulaire d'ajout de travail -->
                    <div class="form-container">
                        <h3 class="section-title">Ajouter un travail à faire</h3>
                        <form method="POST">
                            <input type="hidden" name="module_code" value="<?= $selectedModule['module_code'] ?>">
                            <div class="form-group">
                                <label for="titre">Titre :</label>
                                <input type="text" name="titre" id="titre" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description :</label>
                                <textarea name="description" id="description" required rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="date_limite">Date limite :</label>
                                <input type="datetime-local" name="date_limite" id="date_limite" required>
                            </div>
                            <button type="submit" name="add_travail" class="submit-btn">
                                <i class="fas fa-tasks"></i> Ajouter le travail
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Liste des publications et travaux combinés et triés par date -->
                <div class="content-section">
                    <h3 class="section-title">Activités récentes</h3>


                    

                    <?php 
                        // Combiner publications et travaux dans un seul tableau
                        $activites = [];
                        
                        foreach ($publications as $pub) {
                            $activites[] = [
                                'type' => 'publication',
                                'date' => $pub['date_publication'],
                                'data' => $pub
                            ];
                        }
                        
                        foreach ($travaux as $trav) {
                            $activites[] = [
                                'type' => 'travail',
                                'date' => $trav['date_limite'],
                                'data' => $trav
                            ];
                        }
                        
                        // Trier par date décroissante
                        usort($activites, function($a, $b) {
                            return strtotime($b['date']) - strtotime($a['date']);
                        });
                    ?>
                    
                    <?php if (empty($activites)): ?>
                        <p>Aucune activité récente.</p>
                    <?php else: ?>
                        <?php foreach ($activites as $activite): ?>
                            <?php if ($activite['type'] === 'publication'): ?>
                                <!-- Affichage d'une publication -->
                                <div class="publication-item">
                                    <div class="publication-header">
                                        <img src="data:image/jpeg;base64,<?= base64_encode($selectedModule['photo']) ?>" 
                                            alt="Photo du module" class="module-image">
                                        <div>
                                            <h4 class="item-title"><?= htmlspecialchars($activite['data']['titre']) ?></h4>
                                            <div class="item-meta">
                                                <span class="author">Par <?= htmlspecialchars($activite['data']['prof_prenom']) ?> <?= htmlspecialchars($activite['data']['prof_nom']) ?></span>
                                                <span class="date"><?= date('d/m/Y H:i', strtotime($activite['data']['date_publication'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="publication-content">
                                        <p><?= nl2br(htmlspecialchars($activite['data']['description'])) ?></p>
                                        
                                        <?php if (!empty($activite['data']['ressource'])): ?>
                                            <div class="publication-attachment">
                                                <?php
                                                $fileType = $activite['data']['type_fichier'] ?? '';
                                                $isImage = strpos($fileType, 'image/') === 0;
                                                $fileExt = pathinfo($activite['data']['nom_fichier'] ?? '', PATHINFO_EXTENSION);
                                                ?>
                                                
                                                <?php if ($isImage): ?>
                                                    <div class="attachment-preview">
                                                        <img src="data:<?= $fileType ?>;base64,<?= base64_encode($activite['data']['ressource']) ?>" 
                                                            alt="<?= htmlspecialchars($activite['data']['nom_fichier'] ?? 'Image jointe') ?>"
                                                            class="attachment-image">
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="attachment-details">
                                                    <?php if (in_array($fileExt, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'sql'])): ?>
                                                        <div class="file-icon">
                                                            <?php if ($fileExt === 'pdf'): ?>
                                                                <i class="fas fa-file-pdf" style="color: #e74c3c; font-size: 1em;"></i>
                                                            <?php elseif (in_array($fileExt, ['doc', 'docx'])): ?>
                                                                <i class="fas fa-file-word" style="color: #2b579a; font-size: 1em;"></i>
                                                            <?php elseif (in_array($fileExt, ['xls', 'xlsx'])): ?>
                                                                <i class="fas fa-file-excel" style="color: #217346; font-size: 1em;"></i>
                                                            <?php elseif (in_array($fileExt, ['ppt', 'pptx'])): ?>
                                                                <i class="fas fa-file-powerpoint" style="color: #d24726; font-size: 1em;"></i>
                                                            <?php elseif ($fileExt === 'sql'): ?>
                                                                <i class="fas fa-database" style="color: #f39c12; font-size: 1em;"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="file-info">
                                                        <div class="file-name"><?= htmlspecialchars($activite['data']['nom_fichier'] ?? 'Fichier joint') ?></div>
                                                        <div class="file-meta">
                                                            <span class="file-type"><?= strtoupper($fileExt) ?></span>
                                                            <span class="file-size"><?= round(strlen($activite['data']['ressource'])/1024) ?> KB</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <a href="download.php?id=<?= $activite['data']['id'] ?>&type=publication" 
                                                    class="download-btn" 
                                                    download="<?= htmlspecialchars($activite['data']['nom_fichier'] ?? 'fichier') ?>">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Affichage d'un travail -->
                                <div class="travail-item" data-travail-id="<?= $activite['data']['id'] ?>>
                                    <h4 class="item-title"><?= htmlspecialchars($activite['data']['titre']) ?></h4>
                                    <div class="item-meta">
                                        À rendre avant le <?= date('d/m/Y H:i', strtotime($activite['data']['date_limite'])) ?>
                                        <?php if ($role === 'prof' && isset($activite['data']['nb_rendus'])): ?>
                                            - <?= $activite['data']['nb_rendus'] ?> rendu(s)
                                        <?php endif; ?>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($activite['data']['description'])) ?></p>
                                    
                                    <?php if ($role === 'prof'): ?>
                                            <div class="action-buttons">
                                                <a href="view_rendus.php?travail_id=<?= $activite['data']['id'] ?>" 
                                                class="action-btn btn-primary">
                                                    <i class="fas fa-users btn-icon"></i> Voir les rendus
                                                </a>
                                                <a href="edit_travail.php?id=<?= $activite['data']['id'] ?>" 
                                                class="action-btn btn-secondary">
                                                    <i class="fas fa-edit btn-icon"></i> Modifier
                                                </a>
                                            </div>
                                    <?php else: ?>
                                        <?php
                                            $now = new DateTime();
                                            $dateLimite = new DateTime($activite['data']['date_limite']);
                                            $isLate = $now > $dateLimite;
                                            $hasSubmitted = !empty($activite['data']['fichier']);
                                        ?>
                                        
                                        <div class="action-buttons">
                                            <?php if ($hasSubmitted): ?>
                                                <span class="status-badge <?= $isLate ? 'status-en-retard' : 'status-soumis' ?>">
                                                    <?= $isLate ? 'Rendu en retard' : 'Rendu soumis' ?>
                                                </span>
                                                <span class="item-meta"><?= date('d/m/Y H:i', strtotime($activite['data']['date_soumission'])) ?></span>
                                                <a href="download.php?type=rendu&rendu_id=<?= $activite['data']['rendu_id'] ?>" 
                                                    class="action-btn btn-success"
                                                    download="<?= htmlspecialchars($activite['data']['nom_fichier'] ?? 'rendu') ?>">
                                                        <i class="fas fa-file-download"></i> Voir mon rendu
                                                    </a>
                                                <a href="Book.php?module_code=<?= $moduleCode ?>&delete_rendu=<?= $activite['data']['rendu_id'] ?>" 
                                                   class="action-btn btn-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rendu ?')">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </a>
                                            <?php else: ?>
                                                <span class="status-badge status-a-faire">
                                                    À faire
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!$hasSubmitted || !$isLate): ?>
                                                <button onclick="document.getElementById('rendu-form-<?= $activite['data']['id'] ?>').style.display='block'" 
                                                        class="action-btn btn-primary">
                                                    <i class="fas fa-upload"></i> <?= $hasSubmitted ? 'Modifier' : 'Soumettre' ?>
                                                </button>
                                                
                                                <div id="rendu-form-<?= $activite['data']['id'] ?>" style="display: none; margin-top: 10px;">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="travail_id" value="<?= $activite['data']['id'] ?>">
                                                    <input type="hidden" name="module_code" value="<?= $selectedModule['code_inscription'] ?>">
                                                    <div class="form-group">
                                                        <label for="rendu_fichier">Fichier de rendu :</label>
                                                        <input type="file" name="rendu_fichier" id="rendu_fichier" required>
                                                    </div>
                                                    <div class="form-buttons">
                                                        <button type="submit" name="submit_rendu" class="submit-btn">
                                                            <i class="fas fa-check"></i> Envoyer
                                                        </button>
                                                        <button type="button" onclick="document.getElementById('rendu-form-<?= $activite['data']['id'] ?>').style.display='none'" 
                                                                class="submit-btn btn-secondary">
                                                            <i class="fas fa-times"></i> Annuler
                                                        </button>
                                                    </div>
                                                </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h2>Mes Cours</h2>
                <p>Sélectionnez un module pour voir son contenu.</p>
            <?php endif; ?>
        </div>
    </div>

 
   <script>
        // Toggle le menu latéral sur mobile
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.side-nav').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
        
        // Gestion des formulaires de rendu
        document.querySelectorAll('[id^="rendu-form-"]').forEach(form => {
            form.style.display = 'none';
        });

        // Confirmation avant suppression
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce rendu ?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
    <script>
        // Toggle le menu latéral sur mobile
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.side-nav').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
        
        // Gestion des formulaires de rendu
        document.querySelectorAll('[id^="rendu-form-"]').forEach(form => {
            form.style.display = 'none';
        });
    </script>
    <script>
    // Toggle le menu latéral
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        // Pour les écrans larges
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        // Pour les mobiles
        sidebar.classList.toggle('show');
        
        // Sauvegarder l'état dans localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    // Vérifier l'état au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        // Vérifier si l'état est sauvegardé
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
        
        // Pour les mobiles, le sidebar est caché par défaut
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('show');
        }
    });
    // verification avant la soumettre de la formulaire 
        document.querySelectorAll('form[method="POST"][name="submit_rendu"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const dateLimiteInput = this.querySelector('input[name="date_limite"]');
                if (dateLimiteInput) {
                    const dateLimite = new Date(dateLimiteInput.value);
                    const now = new Date();
                    
                    if (now > dateLimite) {
                        e.preventDefault();
                        alert("La date limite est dépassée, vous ne pouvez plus soumettre ce travail");
                        return false;
                    }
                }
                return true;
            });
        });

    // Gestion des formulaires de rendu
    document.querySelectorAll('[id^="rendu-form-"]').forEach(form => {
        form.style.display = 'none';
    });

    // Adapter le sidebar lors du redimensionnement
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
        } else {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.remove('show');
            }
        }
    });
</script>

<?php if (isset($_GET['focus_travail'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const travailId = <?php echo json_encode($_GET['focus_travail']); ?>;
        const travailElement = document.querySelector(`[data-travail-id="${travailId}"]`);
        
        if (travailElement) {
            // Ajouter une classe pour mettre en évidence
            travailElement.classList.add('highlighted-travail');
            
            // Faire défiler jusqu'à l'élément
            travailElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Supprimer le paramètre de l'URL sans recharger la page
            if (history.replaceState) {
                const newUrl = window.location.pathname + 
                             '?module_code=' + <?php echo json_encode($_GET['module_code']); ?>;
                history.replaceState(null, '', newUrl);
            }
        }
    });
</script>
<script>
function filterActivities() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const items = document.querySelectorAll('.publication-item, .travail-item');
    
    items.forEach(item => {
        const title = item.querySelector('.item-title').textContent.toUpperCase();
        const description = item.querySelector('p') ? item.querySelector('p').textContent.toUpperCase() : '';
        
        if (title.includes(filter) || description.includes(filter)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Permettre la recherche avec la touche Entrée
document.getElementById('searchInput').addEventListener('keyup', function(event) {
    if (event.key === 'Enter') {
        filterActivities();
    }
});

function resetSearch() {
    document.getElementById('searchInput').value = '';
    filterActivities();
}
</script>
<script>
// Animation au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Ajoute un effet de chargement progressif
    const items = document.querySelectorAll('.publication-item, .travail-item');
    items.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Gestion améliorée des formulaires de rendu
    document.querySelectorAll('[id^="rendu-form-"]').forEach(form => {
        form.style.display = 'none';
        
        // Ajoute une animation lors de l'ouverture
        const toggleBtn = form.previousElementSibling;
        if (toggleBtn && toggleBtn.classList.contains('action-btn')) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
                
                if (form.style.display === 'block') {
                    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }
    });
    
    // Amélioration de la recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.publication-item, .travail-item').forEach(item => {
                const title = item.querySelector('.item-title').textContent.toLowerCase();
                const content = item.querySelector('p') ? item.querySelector('p').textContent.toLowerCase() : '';
                item.style.display = (title.includes(term) || content.includes(term)) ? 'block' : 'none';
            });
        });
    }
});

// Confirmation avant suppression
document.querySelectorAll('.btn-danger').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
            e.preventDefault();
        }
    });
});
</script>
<style>
    .highlighted-travail {
        animation: highlight 2s ease-in-out;
        border-left: 4px solid #3498db;
        padding-left: 10px;
    }
    
    @keyframes highlight {
        0% { background-color: rgba(52, 152, 219, 0.3); }
        100% { background-color: transparent; }
    }
</style>
<?php endif; ?>
<script>

const AppConfig = {
    userRole: '<?php echo $role; ?>',

};
</script>
<script haref="./js/sidebar.js"></script>
</body>
</html>