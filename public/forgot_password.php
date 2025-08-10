<?php session_start(); 
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Réinitialisation du mot de passe - ScholarHub</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/dark-mode.css"> 
    <script src="js/darkmode.js" defer></script>

</head>
<body <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
  <div class="form-container">
    <h2>Mot de passe oublié</h2>
    <form action="../src/controllers/ForgotPasswordController.php" method="POST">
      <input type="email" name="email" placeholder="Votre email" required><br>
      <button type="submit">Envoyer le code de réinitialisation</button>
      <p><a href="login.php">Retour à la connexion</a></p>
    </form>
  </div>
</body>
</html>