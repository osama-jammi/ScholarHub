<?php
session_start();

require __DIR__ . '/../src/config/db.php';

if (!isset($_SESSION['darkMode'])) {
    $_SESSION['darkMode'] = false; // Valeur par défaut
}

$darkMode = $_SESSION['darkMode'];
// Vérification de session et redirection si non connecté
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: connexion.php");
    exit();
}

// Configuration du dark mode
$darkMode = $_SESSION['darkMode'] ?? false;
$role = $_SESSION['role'];
$userId = $_SESSION['user']['id'];
$nom = htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur');
$currentPage = basename($_SERVER['PHP_SELF']);

// Récupération de la photo de profil
$photoPath = './assets/default_user.jpg';
$table = ($role === 'student') ? 'Etudiant' : 'Professeur';
try {
    $stmt = $pdo->prepare("SELECT photo FROM $table WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!empty($user['photo'])) {
        $photoPath = 'data:image/jpeg;base64,' . base64_encode($user['photo']);
    }
} catch (PDOException $e) {
    error_log("Erreur de récupération de la photo: " . $e->getMessage());
}

// Récupération des modules selon le rôle
$modules = [];
try {
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
} catch (PDOException $e) {
    error_log("Erreur de récupération des modules: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - ScholarHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Essayez un chemin absolu -->
    <link rel="stylesheet" href="/css/accueil.css">

    <!-- Ou vérifiez le chemin relatif -->
    <link rel="stylesheet" href="css/accueil.css">

    <!-- Ou avec la structure complète -->
    <link rel="stylesheet" href="<?= $_SERVER['DOCUMENT_ROOT'] ?>/css/accueil.css">
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
            
            <i class="fas fa-plus-circle add-module" id="module-action-trigger" aria-label="Add module"></i>
            
            <div class="user-profile">
                <img src="<?= $photoPath ?>" alt="Photo de profil" class="user-photo">
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
            <h2><i class="fas fa-home"></i> Tableau de bord</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">Accueil</li>
                </ol>
            </nav>
        </div>
        
        <div class="module-grid">
            <?php if (empty($modules)): ?>
                <div class="no-modules">
                    <i class="fas fa-book-open"></i>
                    <p>Vous n'avez pas encore de modules</p>
                </div>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                    <?php if ($role === 'prof'): ?>
                        <div class="module-card">
                            <a href="gestion_module.php?module_code=<?= urlencode($module['module_code']) ?>" class="module-link">
                                <div class="module-image-container">
                                    <?php if (!empty($module['photo'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($module['photo']) ?>" 
                                             alt="<?= htmlspecialchars($module['module_name']) ?>">
                                    <?php else: ?>
                                        <img src="./assets/default_module.jpg" alt="Module par défaut">
                                    <?php endif; ?>
                                    <div class="module-overlay">
                                        <span>Voir le module</span>
                                    </div>
                                </div>
                                <div class="module-info">
                                    <h3><?= htmlspecialchars($module['module_name']) ?></h3>
                                    <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($module['annee_scolaire']) ?></p>
                                </div>
                            </a>
                            <div class="module-actions">
                                <button class="module-options-btn" data-module="<?= htmlspecialchars($module['module_code']) ?>">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="module-options-menu" id="options-<?= htmlspecialchars($module['module_code']) ?>">
                                    <button onclick="copyModuleCode('<?= htmlspecialchars($module['module_code']) ?>')">
                                        <i class="fas fa-copy"></i> Copier le code
                                    </button>
                                    <form method="POST" action="delete_module.php" 
                                          onsubmit="return confirm('Voulez-vous vraiment supprimer ce module?')">
                                        <input type="hidden" name="code_module" value="<?= htmlspecialchars($module['module_code']) ?>">
                                        <button type="submit">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($role === 'student'): ?>
                        <?php
                        $professeur = null;
                        try {
                            $stmt2 = $pdo->prepare("SELECT P.nom, P.prenom 
                                                  FROM Professeur P 
                                                  JOIN Avoir A ON P.id = A.professeur_id 
                                                  WHERE A.module_code = ?");
                            $stmt2->execute([$module['code_inscription']]);
                            $professeur = $stmt2->fetch();
                        } catch (PDOException $e) {
                            error_log("Erreur de récupération du professeur: " . $e->getMessage());
                        }
                        ?>
                        <div class="module-card">
                            <a href="gestion_module.php?module_code=<?= urlencode($module['code_inscription']) ?>" class="module-link">
                                <div class="module-image-container">
                                    <?php if (!empty($module['photo'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($module['photo']) ?>" 
                                             alt="<?= htmlspecialchars($module['nom']) ?>">
                                    <?php else: ?>
                                        <img src="./assets/default_module.jpg" alt="Module par défaut">
                                    <?php endif; ?>
                                    <div class="module-overlay">
                                        <span>Accéder au cours</span>
                                    </div>
                                </div>
                                <div class="module-info">
                                    <h3><?= htmlspecialchars($module['nom']) ?></h3>
                                    <?php if ($professeur): ?>
                                        <p><i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($professeur['prenom'] . ' ' . $professeur['nom']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Carte pour créer/rejoindre un module -->
            <div class="module-card add-module-card" id="addModuleCard">
                <div class="add-module-content">
                    <i class="fas fa-plus-circle"></i>
                    <h3><?= ($role === 'prof') ? 'Créer un module' : 'Rejoindre un module' ?></h3>
                </div>
            </div>
        </div>
    </main>

    <!-- Popups -->
    <?php if ($role === 'student'): ?>
        <div id="join-module-popup" class="module-popup">
            <div class="popup-content">
                <span class="close-popup" aria-label="Close popup">&times;</span>
                <h3><i class="fas fa-sign-in-alt"></i> Rejoindre un module</h3>
                <form action="../src/controllers/join_module.php" method="POST" class="module-form">
                    <div class="form-group">
                        <label for="code_module">Code du module</label>
                        <div class="input-with-icon">
                            <i class="fas fa-key"></i>
                            <input type="text" id="code_module" name="code_module" required 
                                   placeholder="Entrez le code du module">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Rejoindre
                    </button>
                </form>
            </div>
        </div>
    <?php elseif ($role === 'prof'): ?>
        <div id="create-module-popup" class="module-popup">
            <div class="popup-content">
                <span class="close-popup" aria-label="Close popup">&times;</span>
                <h3><i class="fas fa-plus-circle"></i> Créer un nouveau module</h3>
                <form action="create_module.php" method="POST" enctype="multipart/form-data" class="module-form">
                    <div class="form-group">
                        <label for="module_name">Nom du module</label>
                        <div class="input-with-icon">
                            <i class="fas fa-book"></i>
                            <input type="text" id="module_name" name="module_name" required 
                                   placeholder="Nom du module">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="photo_modul"><i class="fas fa-image"></i> Image du module</label>
                        <div class="file-upload">
                            <label for="photo_modul" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choisir une image</span>
                            </label>
                            <input type="file" id="photo_modul" name="photo_modul" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="syllabus_modul"><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="syllabus_modul" name="syllabus_modul" rows="3"
                                  placeholder="Description du module..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Créer
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Déclarer les variables en premier
    const darkModeToggle = document.getElementById('darkModeToggle');
    const modeText = document.querySelector('.mode-text');
    const menuToggle = document.getElementById('menuToggle');
    const sideNav = document.getElementById('sideNav');

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

    // Initialisation
    initDarkMode();

    // Gestion du clic
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            localStorage.setItem('darkMode', isDarkMode);
            if (modeText) {
                modeText.textContent = isDarkMode ? 'Mode clair' : 'Mode sombre';
            }
            
            fetch('update_darkmode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ darkMode: isDarkMode })
            }).catch(error => console.error('Erreur:', error));
        });
    }

        // Gestion du menu latéral
        const menuToggle = document.getElementById('menuToggle');
        const sideNav = document.getElementById('sideNav');
        const body = document.body;

        function toggleSidebar() {
            body.classList.toggle('collapsed-sidebar');
            localStorage.setItem('sidebarCollapsed', body.classList.contains('collapsed-sidebar'));
        }

        menuToggle.addEventListener('click', toggleSidebar);

        // Initialisation de l'état du sidebar
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            body.classList.add('collapsed-sidebar');
        }

        // Gestion des popups
        function openModulePopup() {
            <?php if ($role === 'student'): ?>
                document.getElementById('join-module-popup').style.display = 'flex';
            <?php elseif ($role === 'prof'): ?>
                document.getElementById('create-module-popup').style.display = 'flex';
            <?php endif; ?>
        }

        document.getElementById('module-action-trigger')?.addEventListener('click', function(e) {
            e.stopPropagation();
            openModulePopup();
        });

        document.getElementById('addModuleCard')?.addEventListener('click', function(e) {
            e.stopPropagation();
            openModulePopup();
        });
        
        // Fermer les popups
        document.querySelectorAll('.close-popup').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.module-popup').style.display = 'none';
            });
        });

        // Fermer quand on clique en dehors
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('module-popup')) {
                e.target.style.display = 'none';
            }
            
            // Fermer les menus d'options
            document.querySelectorAll('.module-options-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        });

        // Gestion des options des modules
        document.querySelectorAll('.module-options-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const moduleCode = this.getAttribute('data-module');
                const menu = document.getElementById(`options-${moduleCode}`);
                
                // Fermer tous les autres menus
                document.querySelectorAll('.module-options-menu').forEach(m => {
                    if (m !== menu) m.style.display = 'none';
                });
                
                // Basculer le menu actuel
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
        });

        // Copier le code du module
        window.copyModuleCode = function(code) {
            navigator.clipboard.writeText(code).then(() => {
                // Créer une notification
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = `<i class="fas fa-check-circle"></i> Code copié : ${code}`;
                document.body.appendChild(notification);
                
                // Afficher la notification
                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);
                
                // Cacher la notification après 3 secondes
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
            });
        }
    });
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