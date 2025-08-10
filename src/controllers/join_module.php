<?php
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_SESSION['user']) && $_SESSION['role'] === 'student') {
    $etudiant_id = $_SESSION['user']['id'];
    $code_module = trim($_POST['code_module']);

    // Vérifier si le module existe
    $stmt = $pdo->prepare("SELECT code_inscription FROM Module WHERE code_inscription = ?");
    $stmt->execute([$code_module]);

    if ($stmt->rowCount() > 0) {
        // Vérifier si déjà inscrit
        $check = $pdo->prepare("SELECT * FROM Inscrit WHERE etudiant_id = ? AND module_code = ?");
        $check->execute([$etudiant_id, $code_module]);

        if ($check->rowCount() === 0) {
            $insert = $pdo->prepare("INSERT INTO Inscrit (etudiant_id, module_code) VALUES (?, ?)");
            $insert->execute([$etudiant_id, $code_module]);
        }
    }

    header("Location: ../../public/accueil.php");
    exit();
}
?>
