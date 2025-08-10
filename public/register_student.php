<?php session_start();
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false; ?>
<!DOCTYPE html>
<html lang="fr" <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enregistrement Étudiant</title>
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
      --label-color: #555;
      --file-input-bg: #f0f0f0;
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
      --label-color: #b0b0b0;
      --file-input-bg: #333;
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

    .register-container {
      background-color: var(--card-bg);
      border-radius: 12px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 500px;
      padding: 40px;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      color: var(--primary-color);
    }

    .register-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    label {
      font-weight: 500;
      color: var(--label-color);
      font-size: 14px;
    }

    input[type="text"],
    select {
      padding: 14px 16px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 15px;
      background-color: var(--input-bg);
      color: var(--text-color);
      width: 100%;
      transition: all 0.3s;
    }

    input[type="text"]:focus,
    select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.2);
    }

    .file-input {
      position: relative;
      margin-bottom: 10px;
    }

    .file-input input[type="file"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      cursor: pointer;
    }

    .file-input-label {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px;
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      background-color: var(--file-input-bg);
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }

    .file-input-label:hover {
      border-color: var(--primary-color);
    }

    .file-input-icon {
      font-size: 24px;
      margin-bottom: 10px;
      color: var(--primary-color);
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
      margin-top: 10px;
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
      .register-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <button class="toggle-dark-mode" id="darkModeToggle">
    <?php echo $darkMode ? '☀️' : '🌙'; ?>
  </button>

  <div class="register-container">
    <h2>Enregistrement Étudiant</h2>
    <form class="register-form" action="../src/controllers/RegisterStudentController.php" method="POST" enctype="multipart/form-data">
      <div class="form-group file-input">
        <label>Photo de profil</label>
        <div class="file-input-label">
          <div class="file-input-icon">📷</div>
          <div>Cliquez pour sélectionner une image</div>
          <input type="file" name="photo" id="photo" accept="image/*" required>
        </div>
      </div>

      <div class="form-group">
        <label for="filiere">Filière</label>
        <input type="text" name="filiere" id="filiere" placeholder="Entrez votre filière" required>
      </div>

      <button type="submit">Enregistrer</button>
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

    // Afficher le nom du fichier sélectionné
    const fileInput = document.getElementById('photo');
    const fileInputLabel = document.querySelector('.file-input-label');
    
    fileInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        fileInputLabel.innerHTML = `
          <div class="file-input-icon">✓</div>
          <div>${this.files[0].name}</div>
          <small>Taille: ${Math.round(this.files[0].size / 1024)} Ko</small>
        `;
      }
    });
  </script>
</body>
</html>