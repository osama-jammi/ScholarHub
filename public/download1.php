<?php
require __DIR__ . '/../src/config/db.php';

// Activer le reporting d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour déterminer le type MIME
function getContentTypeForFile($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4'
    ];
    
    return $types[$extension] ?? 'application/octet-stream';
}

// Fonction pour créer des archives ZIP à la volée
function createZipArchive($files, $zipName) {
    $zip = new ZipArchive();
    if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
        return false;
    }

    foreach ($files as $file) {
        if (file_exists($file['path'])) {
            $zip->addFile($file['path'], $file['name']);
        }
    }
    
    $zip->close();
    return file_exists($zipName);
}

session_start();

// Vérifier l'authentification
if (!isset($_SESSION['role'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès interdit");
}

// Vérifier les paramètres
if (!isset($_GET['type'])) {
    header("HTTP/1.1 400 Bad Request");
    exit("Type de téléchargement non spécifié");
}

$type = $_GET['type'];
$userId = $_SESSION['user']['id'] ?? null;

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($type === 'chat_file') {
        // Téléchargement depuis le chat
        if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id'])) {
            header("HTTP/1.1 400 Bad Request");
            exit("ID de message invalide");
        }

        $messageId = (int)$_GET['message_id'];
        
        // Récupérer le message et vérifier les permissions
        $stmt = $pdo->prepare("SELECT gm.content, gm.file_name, gm.file_mime, gm.file_size, 
                              gm.message_type, g.id as group_id
                              FROM GroupMessages gm
                              JOIN Groupes g ON gm.group_id = g.id
                              JOIN GroupMembers gmemb ON g.id = gmemb.group_id
                              WHERE gm.id = ? AND gmemb.user_id = ?");
        $stmt->execute([$messageId, $userId]);
        $message = $stmt->fetch();

        if (!$message) {
            header("HTTP/1.1 404 Not Found");
            exit("Message non trouvé ou accès refusé");
        }

        // Traitement différent selon le type
        if ($message['message_type'] === 'zip' || $message['message_type'] === 'folder') {
            // Décoder les fichiers de l'archive
            $files = json_decode($message['content'], true);
            $fileNames = json_decode($message['file_name'], true);
            
            if (!is_array($files) || empty($files)) {
                header("HTTP/1.1 404 Not Found");
                exit("Aucun fichier trouvé dans cette archive");
            }

            // Créer une archive temporaire
            $tempZip = tempnam(sys_get_temp_dir(), 'chat_zip_') . '.zip';
            $zipFiles = [];
            
            foreach ($files as $index => $filePath) {
                if (file_exists($filePath)) {
                    $zipFiles[] = [
                        'path' => $filePath,
                        'name' => $fileNames[$index] ?? basename($filePath)
                    ];
                }
            }

            if (!createZipArchive($zipFiles, $tempZip)) {
                header("HTTP/1.1 500 Internal Server Error");
                exit("Erreur lors de la création de l'archive");
            }

            // Envoyer l'archive
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($tempZip) . '"');
            header('Content-Length: ' . filesize($tempZip));
            readfile($tempZip);
            
            // Supprimer le fichier temporaire
            unlink($tempZip);
            exit;

        } else {
            // Fichier simple
            if (!file_exists($message['content'])) {
                header("HTTP/1.1 404 Not Found");
                exit("Fichier non trouvé sur le serveur");
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($message['file_mime'] ?: getContentTypeForFile($message['file_name'])));
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($message['file_name']) . '"');
            header('Content-Length: ' . $message['file_size']);
            readfile($message['content']);
            exit;
        }

    } elseif ($type === 'publication') {
        // [Garder votre code existant pour les publications]
        // ... (votre code original)

    } elseif ($type === 'rendu') {
        // [Garder votre code existant pour les rendus]
        // ... (votre code original)
    }

} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit("Erreur de base de données");
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit("Erreur lors du téléchargement");
}