<?php 
include "../../Base-donnee/index.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int) $_POST["id"]; // Assurez-vous que l'ID est bien un entier

    if (isset($_POST["like_submit"])) {
        $like = (int) $_POST["like"] + 1;
        $sql = "UPDATE comment SET likee = ? WHERE id = ?";
        
        // Utilisation d'une requête préparée pour la sécurité
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $like, $id);
        
        if ($stmt->execute()) {
            echo "Like mis à jour avec succès.";
        } else {
            echo "Erreur lors de la mise à jour : " . $conn->error;
        }
        $stmt->close();
    }

    if (isset($_POST["deslike_submit"])) {
        $deslike = (int) $_POST["deslike"] + 1;
        $sql = "UPDATE comment SET deslike = ? WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $deslike, $id);
        
        if ($stmt->execute()) {
            echo "Dislike mis à jour avec succès.";
        } else {
            echo "Erreur lors de la mise à jour : " . $conn->error;
        }
        $stmt->close();
    }
}

// Redirection après traitement
header("Location: ../index.php");
exit();
?>