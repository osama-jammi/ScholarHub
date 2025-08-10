<?php
session_start();


require __DIR__ . '/../src/config/db.php';

// Vérification de session et redirection si non connecté
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: connexion.php");
    exit();
}

$darkMode = $_SESSION['darkMode'] ?? false;
$role = $_SESSION['role'];
$userId = $_SESSION['user']['id'];
$nom = htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur');
$currentPage = basename($_SERVER['PHP_SELF']);
$table = ($role === 'student') ? 'Etudiant' : 'Professeur';

// Récupérer les informations actuelles de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erreur de récupération des données utilisateur: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la récupération des données";
    header("Location: accueil.php");
    exit();
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $adresse = trim($_POST['adresse']);
    $biographie = ($role === 'prof') ? trim($_POST['biographie']) : null;
    $lien_google_scholar = ($role === 'prof') ? trim($_POST['lien_google_scholar']) : null;
    $fillier = ($role === 'student') ? trim($_POST['fillier']) : null;
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    try {
        $pdo->beginTransaction();

        // Vérifier le mot de passe actuel si changement demandé
        if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("Tous les champs du mot de passe doivent être remplis pour le changer");
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas");
            }

            // Vérifier l'ancien mot de passe
            $stmt = $pdo->prepare("SELECT password FROM $table WHERE id = ?");
            $stmt->execute([$userId]);
            $dbPassword = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $dbPassword)) {
                throw new Exception("Mot de passe actuel incorrect");
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Gestion de la photo de profil
        $photo = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Valider le type de fichier
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['photo']['tmp_name']);
            
            if (!in_array($mime, ['image/jpeg', 'image/png'])) {
                throw new Exception("Seuls les fichiers JPEG et PNG sont autorisés");
            }

            // Lire le contenu du fichier
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        }

        // Mise à jour des informations
        if ($role === 'prof') {
            $sql = "UPDATE Professeur SET 
                    nom = ?, 
                    prenom = ?, 
                    adresse = ?, 
                    biographie = ?, 
                    lien_google_scholar = ?, 
                    photo = ?" . 
                    (isset($hashedPassword) ? ", password = ?" : "") . 
                    " WHERE id = ?";
            
            $params = [
                $nom, 
                $prenom, 
                $adresse, 
                $biographie, 
                $lien_google_scholar, 
                $photo
            ];
        } else {
            $sql = "UPDATE Etudiant SET 
                    nom = ?, 
                    prenom = ?, 
                    adresse = ?, 
                    fillier = ?, 
                    photo = ?" . 
                    (isset($hashedPassword) ? ", password = ?" : "") . 
                    " WHERE id = ?";
            
            $params = [
                $nom, 
                $prenom, 
                $adresse, 
                $fillier, 
                $photo
            ];
        }

        if (isset($hashedPassword)) {
            $params[] = $hashedPassword;
        }
        $params[] = $userId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->commit();
        $_SESSION['success'] = "Profil mis à jour avec succès";
        
        // Mettre à jour les informations dans la session
        $_SESSION['user']['nom'] = $nom;
        $_SESSION['user']['prenom'] = $prenom;
        
        // Rafraîchir la page
        header("Location: cog.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de la mise à jour: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - ScholarHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/accueil.css?v=<?= filemtime('./css/accueil.css') ?>">
    <link rel="stylesheet" href="./css/cog.css?v=<?= filemtime('./css/cog.css') ?>">
</head>
<body <?= $darkMode ? 'class="dark-mode"' : '' ?>>
    <!-- Barre de navigation supérieure -->
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
            
            <div class="user-profile">
                <img src="<?= !empty($user['photo']) ? 'data:image/jpeg;base64,' . base64_encode($user['photo']) : './assets/default_user.jpg' ?>" 
                     alt="Photo de profil" class="user-photo">
            </div>
        </div>
    </header>

    <!-- Barre latérale -->
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
        <div class="page-header">
            <h2><i class="fas fa-cog"></i> Paramètres du compte</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="accueil.php">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Paramètres</li>
                </ol>
            </nav>
        </div>
        
        <div class="settings-container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="settings-form">
                <div class="form-section">
                    <h3>Photo de profil</h3>
                    <div class="photo-upload">
                        <div class="photo-preview">
                            <img src="<?= !empty($user['photo']) ? 'data:image/jpeg;base64,' . base64_encode($user['photo']) : './assets/default_user.jpg' ?>" 
                                 alt="Photo de profil" id="photo-preview">
                        </div>
                        <div class="upload-controls">
                            <label for="photo" class="btn btn-outline-primary">
                                <i class="fas fa-camera"></i> Changer de photo
                            </label>
                            <input type="file" name="photo" id="photo" accept="image/jpeg, image/png" class="d-none">
                            <button type="button" class="btn btn-outline-danger" id="remove-photo">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Informations personnelles</h3>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" id="nom" 
                               value="<?= htmlspecialchars($user['nom']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" name="prenom" id="prenom" 
                               value="<?= htmlspecialchars($user['prenom']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" name="adresse" id="adresse" rows="2"><?= htmlspecialchars($user['adresse']) ?></textarea>
                    </div>
                    
                    <?php if ($role === 'prof'): ?>
                        <div class="mb-3">
                            <label for="biographie" class="form-label">Biographie</label>
                            <textarea class="form-control" name="biographie" id="biographie" rows="4"><?= htmlspecialchars($user['biographie']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="lien_google_scholar" class="form-label">Lien Google Scholar</label>
                            <input type="url" class="form-control" name="lien_google_scholar" id="lien_google_scholar" 
                                   value="<?= htmlspecialchars($user['lien_google_scholar']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="fillier" class="form-label">Filière</label>
                            <input type="text" class="form-control" name="fillier" id="fillier" 
                                   value="<?= htmlspecialchars($user['fillier']) ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-section">
                    <h3>Sécurité</h3>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" name="current_password" id="current_password">
                        <div class="form-text">Laissez vide pour ne pas changer</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" name="new_password" id="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Gestion du menu latéral
        const menuToggle = document.getElementById('menuToggle');
        const sideNav = document.getElementById('sideNav');

        menuToggle.addEventListener('click', function() {
            document.body.classList.toggle('collapsed-sidebar');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('collapsed-sidebar'));
        });

        // Initialisation de l'état du sidebar
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('collapsed-sidebar');
        }

        // Prévisualisation de la photo
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) { 
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Suppression de la photo
        document.getElementById('remove-photo').addEventListener('click', function() {
            document.getElementById('photo-preview').src = './assets/default_user.jpg';
            document.getElementById('photo').value = '';
        });
    });

    // Dans la partie JavaScript commune
    function initDarkMode() {
        const savedMode = localStorage.getItem('darkMode');
        const isDarkMode = savedMode ? savedMode === 'true' : <?= $darkMode ? 'true' : 'false' ?>;
        
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            if (modeText) modeText.textContent = 'Mode clair';
        } else if (modeText) {
            modeText.textContent = 'Mode sombre';
        }
    }

    // Appel initial
    initDarkMode();

    // Gestion du clic sur le bouton
    darkModeToggle?.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        const isDarkMode = document.body.classList.contains('dark-mode');
        
        // Sauvegarder en local
        localStorage.setItem('darkMode', isDarkMode);
        
        // Mettre à jour le texte du bouton
        if (modeText) {
            modeText.textContent = isDarkMode ? 'Mode clair' : 'Mode sombre';
        }
        
        // Envoyer au serveur
        fetch('update_darkmode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ darkMode: isDarkMode })
        }).catch(error => console.error('Erreur:', error));
});
    </script>
</body>
</html>