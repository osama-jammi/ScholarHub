<?php session_start(); 
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;
?>
<!DOCTYPE html>
<html lang="fr" <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérification Email</title>
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
      --input-bg: white;
      --input-text: #333;
      --placeholder-color: #9ca3af;
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
      --input-bg: #3d3d3d;
      --input-text: #e0e0e0;
      --placeholder-color: #a0a0a0;
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
      transition: all 0.3s ease;
    }

    .verify-container {
      background-color: var(--card-bg);
      border-radius: 12px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 450px;
      padding: 40px;
      text-align: center;
    }

    h2 {
      margin-bottom: 20px;
      font-weight: 600;
      color: var(--primary-color);
    }

    p {
      margin-bottom: 25px;
      color: var(--text-color);
      opacity: 0.9;
    }

    .verify-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    input {
      padding: 14px 16px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 16px;
      background-color: var(--input-bg);
      color: var(--input-text);
      width: 100%;
      transition: all 0.3s;
    }

    input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.2);
    }

    input::placeholder {
      color: var(--placeholder-color);
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
      transition: all 0.3s;
    }

    button:hover {
      background-color: var(--primary-hover);
      transform: translateY(-2px);
    }

    button:active {
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
      .verify-container {
        padding: 30px 20px;
      }
      
      input, button {
        padding: 12px 14px;
      }
    }
  </style>
</head>
<body>
  <button class="toggle-dark-mode" id="darkModeToggle">
    <?php echo $darkMode ? '☀️' : '🌙'; ?>
  </button>

  <div class="verify-container">
    <h2>Vérifiez votre email</h2>
    <p>Un code de vérification a été envoyé à votre email.</p>
    
    <form class="verify-form" action="../../src/controllers/VerifyCode.php" method="POST">
      <input 
        type="number" 
        name="code" 
        placeholder="Entrez le code reçu" 
        required
        min="100000" 
        max="999999"
        oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
        maxlength="6"
      >
      <button type="submit">Vérifier</button>
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

    // Focus sur le champ de code automatiquement
    document.querySelector('input[name="code"]').focus();
  </script>
</body>
</html>