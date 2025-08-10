<?php session_start(); 
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion - ScholarHub</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/dark-mode.css"> 
    <script src="js/darkmode.js" defer></script>

</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
  <div class="form-container">
    <h2>Connexion</h2>
    <form action="../src/controllers/LoginController.php" method="POST" class="login-form">
      <input type="email" name="email" placeholder="Email" required><br>
      <input type="password" name="password" placeholder="Mot de passe" required><br>
      <button type="submit">Se connecter</button>
      <p class="register-link">Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
      <p class="forgot-password"><a href="forgot_password.php">Mot de passe oublié ?</a></p>
    </form>
  </div>
</body>
</html>