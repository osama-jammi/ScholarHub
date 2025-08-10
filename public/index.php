<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ScholarHub - Plateforme Éducative Collaborative</title>
  <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="./js/darkmode.js" defer></script>
  
</head>
<body >

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">
        <i class="fas fa-graduation-cap me-2"></i>ScholarHub
      </a>
      <div class="ms-auto d-flex align-items-center">
      <button class="btn btn-sm btn-outline-secondary me-3" id="darkModeToggle">
          <i class="fas fa-moon"></i> <span class="mode-text">Mode Sombre</span>
      </button>
        <a href="connexion.php" class="btn btn-outline-primary me-2">Connexion</a>
        <a href="register.php" class="btn btn-primary">Inscription</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="hero-content">
        <h1 class="hero-title">Bienvenue sur ScholarHub</h1>
        <p class="hero-subtitle">La plateforme qui révolutionne l'apprentissage collaboratif</p>
        <div class="hero-cta">
          <a href="register.php" class="btn btn-primary btn-lg me-3">Commencer maintenant</a>
          <a href="#features" class="btn btn-outline-primary btn-lg">Découvrir</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section py-5" id="features">
    <div class="container">
      <h2 class="section-title text-center mb-5">Nos Fonctionnalités</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-users"></i>
            </div>
            <h3>Communauté</h3>
            <p>Connectez-vous avec des étudiants et professeurs partageant les mêmes intérêts.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-book"></i>
            </div>
            <h3>Ressources</h3>
            <p>Accédez à une bibliothèque de ressources éducatives partagées.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-comments"></i>
            </div>
            <h3>Collaboration</h3>
            <p>Travaillez en équipe sur des projets et échangez des idées.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section class="about-section py-5 bg-light" id="about">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <h2 class="section-title">À propos de ScholarHub</h2>
          <p class="lead">Une initiative visant à offrir aux étudiants une plateforme éducative collaborative et moderne.</p>
          <p>ScholarHub a été créé pour répondre aux besoins des étudiants et enseignants en fournissant un espace centralisé pour l'apprentissage, le partage et la collaboration.</p>
          <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
        </div>
        <div class="col-lg-6">
          <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" 
               alt="Étudiants collaborant" class="img-fluid rounded shadow">
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer py-4 bg-dark text-white">
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
          <h5>ScholarHub</h5>
          <p>La plateforme éducative nouvelle génération.</p>
        </div>
        <div class="col-md-2 mb-4 mb-md-0">
          <h5>Liens</h5>
          <ul class="list-unstyled">
            <li><a href="#" class="text-white">Accueil</a></li>
            <li><a href="#features" class="text-white">Fonctionnalités</a></li>
            <li><a href="#about" class="text-white">À propos</a></li>
          </ul>
        </div>
        <div class="col-md-2 mb-4 mb-md-0">
          <h5>Legal</h5>
          <ul class="list-unstyled">
            <li><a href="#" class="text-white">CGU</a></li>
            <li><a href="#" class="text-white">Confidentialité</a></li>
          </ul>
        </div>
        <div class="col-md-4">
          <h5>Contact</h5>
          <p><i class="fas fa-envelope me-2"></i> contact@scholarhub.edu</p>
          <div class="social-links">
            <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </div>
      <hr class="my-4 bg-light">
      <div class="text-center">
        <p class="mb-0">&copy; 2023 ScholarHub. Tous droits réservés.</p>
      </div>
    </div>
  </footer>

</body>
</html>