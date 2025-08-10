<?php 
session_start();
// Récupérer le mode depuis la session ou les cookies
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : (isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : false);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer un compte - ScholarHub</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/dark-mode.css"> 
  <script src="js/darkmode.js" defer></script>

</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
  <div class="form-container">
    <h2>Créer un compte</h2>
    <form action="../src/controllers/RegisterController.php" method="POST" class="register-form">
      <input type="text" name="nom" placeholder="Nom" required><br>
      <input type="text" name="prenom" placeholder="Prénom" required><br>
      <input type="email" name="email" placeholder="Email" required><br>
      <input type="password" name="password" placeholder="Mot de passe" required><br>
      <button type="submit">Envoyer le code de vérification</button>
      
      <div class="separator">
        <span>ou</span>
      </div>
      
      <button type="button" class="google-btn" onclick="window.location.href='../src/controllers/GoogleAuthController.php'">
        <i class="fab fa-google"></i> Se connecter avec Google
      </button>
      
      <p class="login-link">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
    </form>
  </div>
</body>
</html>