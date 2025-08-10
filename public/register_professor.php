<?php session_start(); 
$darkMode = isset($_SESSION['darkMode']) ? $_SESSION['darkMode'] : false;
?>
<!DOCTYPE html>
<html lang="fr" <?php echo $darkMode ? 'class="dark-mode"' : ''; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enregistrement Professeur</title>
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
      --secondary-color: #6c757d;
      --success-color: #28a745;
      --input-bg: white;
      --label-color: #555;
      --readonly-bg: #f5f5f5;
      --checkbox-color: #0077B6;
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
      --secondary-color: #868e96;
      --success-color: #34ce57;
      --input-bg: #3d3d3d;
      --label-color: #b0b0b0;
      --readonly-bg: #333;
      --checkbox-color: #5a8fb4;
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
      max-width: 600px;
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
    input[type="email"],
    input[type="url"],
    textarea {
      padding: 14px 16px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 15px;
      background-color: var(--input-bg);
      color: var(--text-color);
      width: 100%;
      transition: all 0.3s;
    }

    input[readonly] {
      background-color: var(--readonly-bg);
    }

    input:focus,
    textarea:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.2);
    }

    textarea {
      height: 120px;
      resize: vertical;
    }

    .file-input-group {
      margin-top: 10px;
    }

    .photo-options {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin-bottom: 15px;
    }

    .google-photo-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .google-photo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--border-color);
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--checkbox-color);
    }

    .file-input {
      position: relative;
      margin-top: 10px;
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
      padding: 20px;
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      background-color: var(--readonly-bg);
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }

    .file-input-label:hover {
      border-color: var(--primary-color);
    }

    .file-input-icon {
      font-size: 24px;
      margin-bottom: 8px;
      color: var(--primary-color);
    }

    small {
      color: var(--secondary-color);
      font-size: 13px;
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

      .google-photo-container {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
  <button class="toggle-dark-mode" id="darkModeToggle">
    <?php echo $darkMode ? '☀️' : '🌙'; ?>
  </button>

  <div class="register-container">
    <h2>Enregistrement Professeur</h2>
    <form class="register-form" action="../src/controllers/RegisterProfessorController.php" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="nom">Nom</label>
        <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($_SESSION['user_temp']['nom'] ?? ''); ?>" readonly>
      </div>

      <div class="form-group">
        <label for="prenom">Prénom</label>
        <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($_SESSION['user_temp']['prenom'] ?? ''); ?>" readonly>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_SESSION['user_temp']['email'] ?? ''); ?>" readonly>
      </div>

      <div class="form-group">
        <label>Photo de profil</label>
        <?php if (isset($_SESSION['user_temp']['google_photo'])): ?>
        <div class="photo-options">
          <div class="google-photo-container">
            <img src="data:image/jpeg;base64,<?= base64_encode($_SESSION['user_temp']['google_photo']) ?>" class="google-photo">
            <div class="checkbox-group">
              <input type="checkbox" name="use_google_photo" id="use_google_photo" value="1" checked>
              <label for="use_google_photo">Utiliser ma photo Google</label>
            </div>
          </div>
          <small>Ou</small>
        </div>
        <?php endif; ?>

        <div class="file-input-group">
          <div class="file-input">
            <div class="file-input-label">
              <div class="file-input-icon">📷</div>
              <div>Télécharger une photo</div>
              <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif">
            </div>
          </div>
          <small>Formats acceptés : JPEG, PNG, GIF (max 2MB)</small>
        </div>
      </div>

      <div class="form-group">
        <label for="bio">Biographie</label>
        <textarea name="bio" id="bio" required placeholder="Décrivez votre parcours académique et vos domaines de recherche..."></textarea>
      </div>

      <div class="form-group">
        <label for="scholar_link">Lien Google Scholar</label>
        <input type="url" name="scholar_link" id="scholar_link" 
               placeholder="https://scholar.google.com/citations..." 
               required
               pattern="https?://scholar\.google\.com/.*">
        <small>Exemple : https://scholar.google.com/citations?user=ABCD123</small>
      </div>

      <button type="submit">Finaliser l'inscription</button>
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

    // Gestion de l'affichage du fichier sélectionné
    const photoInput = document.getElementById('photo');
    const fileInputLabel = document.querySelector('.file-input-label');
    
    if (photoInput) {
      photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          fileInputLabel.innerHTML = `
            <div class="file-input-icon">✓</div>
            <div>${this.files[0].name}</div>
            <small>Taille: ${Math.round(this.files[0].size / 1024)} Ko</small>
          `;
          
          // Décocher la photo Google si une nouvelle photo est sélectionnée
          const googlePhotoCheckbox = document.getElementById('use_google_photo');
          if (googlePhotoCheckbox) {
            googlePhotoCheckbox.checked = false;
          }
        }
      });
    }
  </script>
</body>
</html>