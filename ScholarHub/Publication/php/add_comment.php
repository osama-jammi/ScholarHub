<?php 
include "../../Base-donnee/index.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id =  $_POST["id"]; // Assurez-vous que l'ID est bien un entier
    $content =  $_POST["content"] ;
    if (isset($_POST["comment"]) && !empty($_POST["content"])) {
        $sql = "INSERT INTO comment (id_annonce, descreption) VALUES (?, ?)";
          
        // Utilisation d'une requête préparée pour la sécurité
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $content);
        
        if ($stmt->execute()) {
            echo "Like mis à jour avec succès.";
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