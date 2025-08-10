<?php
require __DIR__ . '/../src/config/db.php';

// Activer le reporting d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour déterminer le type MIME d'un fichier
function getContentTypeForFile($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'zip' => 'application/zip'
    ];
    
    return $types[$extension] ?? 'application/octet-stream';
}

// Démarrer la session
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['role'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès interdit");
}

// Vérifier les paramètres requis
if (!isset($_GET['type'])) {
    header("HTTP/1.1 400 Bad Request");
    exit("Type de fichier non spécifié");
}

$type = $_GET['type'];

try {
    // Configuration PDO
    $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($type === 'publication') {
        // Téléchargement d'une publication
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header("HTTP/1.1 400 Bad Request");
            exit("ID de publication manquant ou invalide");
        }

        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT ressource, nom_fichier, type_fichier, LENGTH(ressource) as file_size 
                              FROM Publication WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file || empty($file['ressource'])) {
            header("HTTP/1.0 404 Not Found");
            exit("Publication non trouvée");
        }

        // Nettoyer les buffers de sortie
        while (ob_get_level()) ob_end_clean();

        // Envoyer les en-têtes et le fichier
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($file['type_fichier'] ?? getContentTypeForFile($file['nom_fichier'])));
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($file['nom_fichier'] ?? 'document') . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        echo $file['ressource'];
        exit;

    } elseif ($type === 'rendu') {
        // Téléchargement d'un rendu étudiant
        if (!isset($_GET['rendu_id']) || !is_numeric($_GET['rendu_id'])) {
            header("HTTP/1.1 400 Bad Request");
            exit("ID de rendu manquant ou invalide");
        }
    
        $renduId = (int)$_GET['rendu_id'];
        $userId = $_SESSION['user']['id'] ?? null;
    
        if ($_SESSION['role'] === 'prof') {
            // Vérifier que le professeur a bien accès à ce rendu
            $stmt = $pdo->prepare("SELECT r.fichier, 
                                 CONCAT('rendu_', e.prenom, '_', e.nom, '_', t.titre, 
                                 CASE WHEN r.nom_fichier IS NOT NULL THEN 
                                     CONCAT('.', SUBSTRING_INDEX(r.nom_fichier, '.', -1)) 
                                 ELSE '.pdf' END) as nom_fichier, 
                                 LENGTH(r.fichier) as file_size,
                                 COALESCE(r.type_fichier, 'application/octet-stream') as type_fichier
                                 FROM Rendu r
                                 JOIN Etudiant e ON r.etudiant_id = e.id
                                 JOIN Travail t ON r.travail_id = t.id
                                 JOIN Avoir a ON t.module_code = a.module_code
                                 WHERE r.id = ? AND a.professeur_id = ?");
            $stmt->execute([$renduId, $userId]);
        } else {
            // Étudiant ne peut télécharger que ses propres rendus
            if (!$userId) {
                header("HTTP/1.1 403 Forbidden");
                exit("Accès interdit");
            }
            
            $stmt = $pdo->prepare("SELECT r.fichier, 
                                 COALESCE(r.nom_fichier, CONCAT('rendu_', t.titre, '.pdf')) as nom_fichier, 
                                 LENGTH(r.fichier) as file_size,
                                 COALESCE(r.type_fichier, 'application/octet-stream') as type_fichier
                                 FROM Rendu r
                                 JOIN Travail t ON r.travail_id = t.id
                                 WHERE r.id = ? AND r.etudiant_id = ?");
            $stmt->execute([$renduId, $userId]);
        }
    
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$file || empty($file['fichier'])) {
            header("HTTP/1.0 404 Not Found");
            exit("Fichier non trouvé ou vous n'avez pas les permissions nécessaires");
        }
    
        // Nettoyer les buffers de sortie
        while (ob_get_level()) ob_end_clean();
    
        // Déterminer le type MIME
        $contentType = $file['type_fichier'] ?? getContentTypeForFile($file['nom_fichier']);
    
        // Envoyer les en-têtes et le fichier
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($file['nom_fichier']) . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        echo $file['fichier'];
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur téléchargement: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    exit("Une erreur est survenue lors du téléchargement");
}