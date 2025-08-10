
<?php
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/accueil.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
    

<!-- Barre horizontale -->
    <header class="top-nav">
        <i class="fas fa-bars menu-toggle"  id="sidebarToggle"></i>
        <h1>SCHOLARHUB > </h1>
        <h2><?= htmlspecialchars($nom) ?></h2>
        <div class="user-actions">
            <img src="<?php 
                if ($role === 'student') {
                    $table = 'Etudiant';
                } else {
                    $table = 'Professeur';
                }

                $stmt = $pdo->prepare("SELECT photo FROM $table WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if (!empty($user['photo'])) {
                    echo 'data:image/jpeg;base64,' . base64_encode($user['photo']);
                } else {
                    echo '../../public/assets/default_user.jpg';
                }
            ?>" alt="Photo utilisateur" class="user-photo">
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
</body>
</html>