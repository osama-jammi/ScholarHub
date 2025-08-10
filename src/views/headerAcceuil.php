<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil - ScholarHub</title>
  <link rel="stylesheet" href="/css/accueil.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/your-kit.js" crossorigin="anonymous"></script> <!-- pour les icônes -->
</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
  <header class="top-nav">
    <i class="fas fa-bars menu-toggle"></i>
    <h1>SCHOLARHUB ></h1>
    <h2><?= htmlspecialchars($nom ?? 'Utilisateur') ?></h2>
    <div class="user-actions">
      <i class="fas fa-plus add-module" title="Créer/Rejoindre un module">+</i>
      <img src="/images/user_photo.jpg" alt="Photo utilisateur" class="user-photo">
    </div>
  </header>

  <nav class="side-nav">
    <ul>
      <li><i class="fas fa-home"></i> <span>Accueil</span></li>
      <li><i class="fas fa-calendar-alt"></i> <span>Calendrier</span></li>
      <li><i class="fas fa-book"></i> <span>Cours</span></li>
      <li><i class="fas fa-cog"></i> <span>Paramètres</span></li>
    </ul>
  </nav>
