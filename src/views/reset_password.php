<?php session_start(); 
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réinitialisation du mot de passe - ScholarHub</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap">
  <link rel="stylesheet" href="css/style.css">
  <style>
    :root {
      --primary-color: #0077B6;
      --primary-hover: #005b8c;
      --error-color: #ef4444;
      --text-color: #333;
      --bg-color: #f9fafb;
      --card-bg: #ffffff;
      --border-color: #e5e7eb;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .dark-mode {
      --text-color: #f3f4f6;
      --bg-color: #1a1a1a;
      --card-bg: #2d2d2d;
      --border-color: #444;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.25), 0 2px 4px -1px rgba(0, 0, 0, 0.15);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      transition: background-color 0.3s, color 0.3s;
    }

    .form-container {
      background-color: var(--card-bg);
      border-radius: 12px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 450px;
      padding: 40px;
      transition: all 0.3s;
    }

    h2 {
      text-align: center;
      margin-bottom: 24px;
      font-weight: 600;
      color: var(--primary-color);
    }

    .error {
      color: var(--error-color);
      background-color: rgba(239, 68, 68, 0.1);
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    input {
      padding: 14px 16px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 15px;
      background-color: var(--card-bg);
      color: var(--text-color);
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    button {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 14px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
      margin-top: 8px;
    }

    button:hover {
      background-color: var(--primary-hover);
    }

    .password-strength {
      margin-top: -10px;
      font-size: 13px;
      color: #6b7280;
    }

    .strength-weak {
      color: var(--error-color);
    }

    .strength-medium {
      color: #f59e0b;
    }

    .strength-strong {
      color: #10b981;
    }

    @media (max-width: 480px) {
      .form-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body <?php echo isset($darkMode) && $darkMode ? 'class="dark-mode"' : ''; ?>>
  <div class="form-container">
    <h2>Réinitialisation du mot de passe</h2>
    <?php if (isset($_SESSION['error'])): ?>
      <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    
    <form action="../controllers/ResetPasswordController.php" method="POST" id="resetForm">
      <input type="text" 
             name="verification_code" 
             placeholder="Code de vérification" 
             required
             autocomplete="off"
             autofocus>
      
      <input type="password" 
             name="new_password" 
             id="newPassword"
             placeholder="Nouveau mot de passe (min. 8 caractères)" 
             required
             minlength="8">
      <p class="password-strength" id="passwordStrength"></p>
      
      <input type="password" 
             name="confirm_password" 
             id="confirmPassword"
             placeholder="Confirmer le mot de passe" 
             required>
      
      <button type="submit">Réinitialiser le mot de passe</button>
    </form>
  </div>

  <script>
    // Password strength indicator
    const newPassword = document.getElementById('newPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    
    newPassword.addEventListener('input', function() {
      const password = this.value;
      let strength = '';
      let strengthClass = '';
      
      if (password.length === 0) {
        strength = '';
      } else if (password.length < 6) {
        strength = 'Faible';
        strengthClass = 'strength-weak';
      } else if (password.length < 10 || !/\d/.test(password) || !/[A-Z]/.test(password)) {
        strength = 'Moyen';
        strengthClass = 'strength-medium';
      } else {
        strength = 'Fort';
        strengthClass = 'strength-strong';
      }
      
      passwordStrength.textContent = strength;
      passwordStrength.className = 'password-strength ' + strengthClass;
    });
    
    // Confirm password validation
    const resetForm = document.getElementById('resetForm');
    const confirmPassword = document.getElementById('confirmPassword');
    
    resetForm.addEventListener('submit', function(e) {
      if (newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
        confirmPassword.focus();
      }
    });
  </script>
</body>
</html>