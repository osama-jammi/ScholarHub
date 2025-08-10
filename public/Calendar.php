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

// Récupérer les modules selon le rôle
$modules = [];
try {
    if ($role === 'prof') {
        $stmt = $pdo->prepare("SELECT A.module_code, M.nom AS module_name, A.annee_scolaire 
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

// Récupérer les travaux selon le rôle
$travaux = [];
foreach ($modules as $module) {
    try {
        if ($role === 'prof') {
            $stmt = $pdo->prepare("SELECT t.*, COUNT(r.id) AS rendu_count 
                                  FROM Travail t 
                                  LEFT JOIN Rendu r ON t.id = r.travail_id 
                                  WHERE t.module_code = ? 
                                  GROUP BY t.id 
                                  ORDER BY t.date_limite ASC");
            $stmt->execute([$module['module_code']]);
        } else {
            $stmt = $pdo->prepare("SELECT t.*, r.id AS rendu_id, r.fichier, r.date_soumission 
                                  FROM Travail t 
                                  LEFT JOIN Rendu r ON t.id = r.travail_id AND r.etudiant_id = ?
                                  WHERE t.module_code = ? 
                                  ORDER BY t.date_limite ASC");
            $stmt->execute([$userId, $module['code_inscription']]);
        }
        
        $moduleTravaux = $stmt->fetchAll();
        
        foreach ($moduleTravaux as $travail) {
            $travail['module_nom'] = $role === 'prof' ? $module['module_name'] : $module['nom'];
            $travail['module_code'] = $role === 'prof' ? $module['module_code'] : $module['code_inscription'];
            $travaux[] = $travail;
        }
    } catch (PDOException $e) {
        error_log("Erreur de récupération des travaux: " . $e->getMessage());
    }
}

// Formater les travaux pour le calendrier
$calendarEvents = [];
foreach ($travaux as $travail) {
    $now = new DateTime();
    $dateLimite = new DateTime($travail['date_limite']);
    $isLate = $now > $dateLimite;
    
    if ($role === 'prof') {
        $status = $travail['rendu_count'] > 0 ? 'soumis-prof' : 'a-faire-prof';
    } else {
        $hasSubmitted = !empty($travail['fichier']);
        $status = $hasSubmitted ? ($isLate ? 'rendu-en-retard' : 'rendu-soumis') : ($isLate ? 'en-retard' : 'a-faire');
    }
    
    $calendarEvents[] = [
        'title' => $travail['titre'] . ' (' . $travail['module_nom'] . ')',
        'start' => $travail['date_limite'],
        'end' => $travail['date_limite'],
        'module_code' => $travail['module_code'],
        'travail_id' => $travail['id'],
        'className' => $status,
        'extendedProps' => [
            'description' => $travail['description'],
            'status' => $status,
            'isLate' => $isLate,
            'role' => $role
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier - ScholarHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="./css/accueil.css?v=<?= filemtime('./css/accueil.css') ?>">
    <link rel="stylesheet" href="./css/calendar.css?v=<?= filemtime('./css/calendar.css') ?>">
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
                <?php
                $table = ($role === 'student') ? 'Etudiant' : 'Professeur';
                try {
                    $stmt = $pdo->prepare("SELECT photo FROM $table WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    $photoPath = !empty($user['photo']) ? 
                        'data:image/jpeg;base64,' . base64_encode($user['photo']) : 
                        './assets/default_user.jpg';
                } catch (PDOException $e) {
                    $photoPath = './assets/default_user.jpg';
                    error_log("Erreur de récupération de la photo: " . $e->getMessage());
                }
                ?>
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
            <h2><i class="fas fa-calendar-alt"></i> Calendrier des travaux</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="accueil.php">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Calendrier</li>
                </ol>
            </nav>
        </div>
        
        <div class="calendar-container">
            <div id="calendar"></div>
            
            <div id="eventDetails" class="event-details mt-4 p-4 rounded" style="display: none;">
                <h4 id="eventTitle" class="mb-3"></h4>
                <div class="d-flex align-items-center mb-3">
                    <span id="eventDate" class="me-3"></span>
                    <span id="eventStatus" class="badge"></span>
                </div>
                <p id="eventDescription" class="mb-3"></p>
                <div id="eventActions" class="d-flex gap-2"></div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/fr.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du dark mode
        const darkModeToggle = document.getElementById('darkModeToggle');
        const modeText = document.querySelector('.mode-text');
        
        function initDarkMode() {
            const savedMode = localStorage.getItem('darkMode');
            const isDarkMode = savedMode ? savedMode === 'true' : <?= $darkMode ? 'true' : 'false' ?>;
            
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
                modeText.textContent = 'Mode clair';
            } else {
                modeText.textContent = 'Mode sombre';
            }
        }
        
        initDarkMode();
        
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            localStorage.setItem('darkMode', isDarkMode);
            modeText.textContent = isDarkMode ? 'Mode clair' : 'Mode sombre';
            
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
        
        menuToggle.addEventListener('click', function() {
            document.body.classList.toggle('collapsed-sidebar');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('collapsed-sidebar'));
        });

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('collapsed-sidebar');
        }

        // Configuration du calendrier
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            firstDay: 1,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Aujourd\'hui',
                month: 'Mois',
                week: 'Semaine',
                day: 'Jour'
            },
            events: <?php echo json_encode($calendarEvents); ?>,
            eventClick: function(info) {
                const event = info.event;
                const detailsEl = document.getElementById('eventDetails');
                const statusMap = {
                    'a-faire': ['À faire', 'bg-warning text-dark'],
                    'en-retard': ['En retard', 'bg-danger'],
                    'rendu-soumis': ['Rendu', 'bg-success'],
                    'rendu-en-retard': ['Rendu en retard', 'bg-secondary'],
                    'a-faire-prof': ['À corriger', 'bg-warning text-dark'],
                    'soumis-prof': ['Soumis', 'bg-info text-dark']
                };
                
                document.getElementById('eventTitle').textContent = event.title;
                document.getElementById('eventDate').textContent = 
                    'Date limite: ' + event.start.toLocaleDateString('fr-FR');
                document.getElementById('eventDescription').textContent = 
                    event.extendedProps.description || 'Aucune description disponible';
                
                const [statusText, statusClass] = statusMap[event.extendedProps.status] || ['', ''];
                const statusEl = document.getElementById('eventStatus');
                statusEl.textContent = statusText;
                statusEl.className = 'badge ' + statusClass;
                
                const actionsEl = document.getElementById('eventActions');
                actionsEl.innerHTML = '';
                
                if (<?= $role === 'student' ? 'true' : 'false' ?>) {
                    if (!event.extendedProps.status.includes('rendu')) {
                        const submitBtn = document.createElement('a');
                        submitBtn.href = `Book.php?module_code=${event.extendedProps.module_code}&travail_id=${event.extendedProps.travail_id}`;
                        submitBtn.className = 'btn btn-primary btn-sm';
                        submitBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Soumettre';
                        actionsEl.appendChild(submitBtn);
                    }
                } else {
                    const viewBtn = document.createElement('a');
                    viewBtn.href = `Book.php?module_code=${event.extendedProps.module_code}&travail_id=${event.extendedProps.travail_id}`;
                    viewBtn.className = 'btn btn-primary btn-sm';
                    viewBtn.innerHTML = '<i class="fas fa-eye me-1"></i> Voir les rendus';
                    actionsEl.appendChild(viewBtn);
                }
                
                detailsEl.style.display = 'block';
                info.jsEvent.preventDefault();
            }
        });
        
        calendar.render();
    });
    </script>
</body>
</html>