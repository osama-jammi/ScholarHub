<?php
class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserByEmail(string $email): ?array {
        try {
            // D'abord chercher dans la table Professeur
            $stmt = $this->pdo->prepare("
                SELECT id, nom, prenom, adresse, 'professeur' AS role 
                FROM Professeur 
                WHERE adresse = ? 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si pas trouvé, chercher dans la table Etudiant
            if (!$user) {
                $stmt = $this->pdo->prepare("
                    SELECT id, nom, prenom, adresse, 'etudiant' AS role 
                    FROM Etudiant 
                    WHERE adresse = ? 
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Erreur getUserByEmail: " . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(string $email, string $hashedPassword): bool {
        try {
            // D'abord essayer de mettre à jour dans Professeur
            $stmt = $this->pdo->prepare("UPDATE Professeur SET password = ? WHERE adresse = ?");
            $stmt->execute([$hashedPassword, $email]);
            $rowsAffected = $stmt->rowCount();

            // Si aucun professeur mis à jour, essayer avec Etudiant
            if ($rowsAffected === 0) {
                $stmt = $this->pdo->prepare("UPDATE Etudiant SET password = ? WHERE adresse = ?");
                $stmt->execute([$hashedPassword, $email]);
                $rowsAffected = $stmt->rowCount();
            }

            return $rowsAffected > 0;
        } catch (PDOException $e) {
            error_log("Erreur updatePassword: " . $e->getMessage());
            return false;
        }
    }
}