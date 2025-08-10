


<?php
session_start();
require_once '../config/db.php'; // fichier pour se connecter à la base de données

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Connexion à la base
    try {

        // Vérifier dans la table Professeur
        $stmt = $pdo->prepare("SELECT * FROM Professeur WHERE adresse = ?");
        $stmt->execute([$email]);
        $prof = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prof && password_verify($password, $prof['password'])) {
           // if ($prof && $password=== $prof['password']) {
            $_SESSION['user'] = [
                'id' => $prof['id'],
                'email' => $email,
                'role' => 'professeur',
                'nom' => $prof['nom'],
                'prenom' => $prof['prenom'] 
            ];
            $_SESSION['role'] = 'prof';
            header("Location: ../../public/accueil.php");
            //views
            exit;
        }

        // Vérifier dans la table Etudiant
        $stmt = $pdo->prepare("SELECT * FROM Etudiant WHERE adresse = ?");
        $stmt->execute([$email]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
        //
        if ($etudiant && password_verify($password, $etudiant['password'])) {
           // if ($etudiant && $password === $etudiant['password']) {
            $_SESSION['user'] = [
                'id' => $etudiant['id'],
                'email' => $email,
                'role' => 'etudiant',
                'nom' => $etudiant['nom'],
                'prenom' => $etudiant['prenom']
            ];
            $_SESSION['role'] = 'student';
            header("Location: ../../public/accueil.php");
            exit;
        }

        // Si aucune correspondance trouvée
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        header("Location: ../../public/connexion.php");
        echo  $_SESSION['error'] ;
        exit;

    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
