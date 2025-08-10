<?php session_start(); 
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;
?>
<!DOCTYPE html>
<html lang="fr" <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Choisissez votre rôle</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      /* Light mode colors */
      --bg-color: #f8f9fa;
      --text-color: #333;
      --card-bg: white;
      --border-color: #ddd;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --primary-color: #0077B6;
      --primary-hover: #005b8c;
      --button-text: white;
    }

    .dark-mode {
      /* Dark mode colors */
      --bg-color: #1a1a1a;
      --text-color: #e0e0e0;
      --card-bg: #2d2d2d;
      --border-color: #444;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
      --primary-color: #5a8fb4;
      --primary-hover: #4a7da0;
      --button-text: #e0e0e0;
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
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
      transition: background-color 0.3s, color 0.3s;
    }

    .role-container {
      background-color: var(--card-bg);
      border-radius: 12px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 450px;
      padding: 40px;
      text-align: center;
      transition: all 0.3s;
    }

    h2 {
      margin-bottom: 30px;
      font-weight: 600;
      color: var(--primary-color);
    }

    .role-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .role-button {
      background-color: var(--primary-color);
      color: var(--button-text);
      border: none;
      padding: 14px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
    }

    .role-button:hover {
      background-color: var(--primary-hover);
      transform: translateY(-2px);
    }

    .role-button:active {
      transform: translateY(0);
    }

    .toggle-dark-mode {
      position: absolute;
      top: 20px;
      right: 20px;
      background: none;
      border: none;
      color: var(--text-color);
      cursor: pointer;
      font-size: 1.5rem;
    }

    @media (max-width: 480px) {
      .role-container {
        padding: 30px 20px;
      }
      
      .role-button {
        padding: 12px;
      }
    }
  </style>
</head>
<body>
  <button class="toggle-dark-mode" id="darkModeToggle">
    <?php echo $darkMode ? '☀️' : '🌙'; ?>
  </button>

  <div class="role-container">
    <h2>Vous êtes :</h2>
    <form class="role-form" method="POST" action="redirect_role.php">
      <button class="role-button" type="submit" name="role" value="etudiant">Étudiant</button>
      <button class="role-button" type="submit" name="role" value="professeur">Professeur</button>
    </form>
  </div>

  <script>
    document.getElementById('darkModeToggle').addEventListener('click', function() {
      fetch('toggle_dark_mode.php')
        .then(response => response.text())
        .then(() => {
          location.reload();
        });
    });
  </script>
</body>
</html>