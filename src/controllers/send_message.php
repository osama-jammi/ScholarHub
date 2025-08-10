<?php
// Désactiver l'affichage des erreurs directement
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Définir le header JSON en premier
header('Content-Type: application/json');

// Démarrer la session après les headers
session_start();

// Vérifier la session et le rôle
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Session non initialisée']);
    exit;
}

if ($_SESSION['role'] !== 'prof') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès réservé aux professeurs']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Lire les données JSON
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput === false) {
        throw new Exception('Impossible de lire les données d\'entrée');
    }

    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }

    // Valider les champs (en utilisant les mêmes noms que côté JavaScript)
    $requiredFields = ['studentId', 'subject', 'content', 'sendCopy'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Champ requis manquant: $field");
        }
    }

    // Connexion à la base de données
    require_once __DIR__ . '/../config/db.php';
    $pdo = getPDO();
    
    // Récupérer l'étudiant
    $stmt = $pdo->prepare("SELECT id, nom, prenom, adresse FROM Etudiant WHERE id = ?");
    $stmt->execute([$data['studentId']]); // Notez studentId ici
    $student = $stmt->fetch();

    if (!$student) {
        throw new Exception('Étudiant non trouvé');
    }

    // Configuration de l'email
    $mail = configurerMailer();
    $mail->addAddress($student['adresse']);
    $mail->Subject = $data['subject'];
    
    // Construire le contenu HTML de l'email
    $logoUrl = 'http://127.0.0.1/ScholarHub/public/assets/logo.png';
    $currentYear = date('Y');
    $studentName = $student['prenom'] . ' ' . $student['nom'];
    
    $mail->Body = <<<HTML
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Message de votre professeur - ScholarHub</title>
        <style>
            body {
                font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                background-color: #f8fafc;
                margin: 0;
                padding: 0;
                color: #334155;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 24px rgba(0,0,0,0.05);
            }
            .email-header {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                padding: 30px;
                text-align: center;
            }
            .email-header img {
                height: 40px;
            }
            .email-content {
                padding: 32px;
            }
            h1 {
                color: #1e293b;
                font-size: 24px;
                margin-top: 0;
                margin-bottom: 20px;
                font-weight: 600;
            }
            .message-card {
                background: #f1f5f9;
                border-radius: 8px;
                padding: 24px;
                margin: 20px 0;
                border-left: 4px solid #3b82f6;
            }
            .message-subject {
                font-size: 20px;
                margin-top: 0;
                color: #1e293b;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 13px;
                color: #64748b;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #3b82f6;
                color: white !important;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 500;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <img src="$logoUrl" alt="ScholarHub">
            </div>
            
            <div class="email-content">
                <h1>Message de votre professeur</h1>
                <p>Bonjour $studentName,</p>
                
                <div class="message-card">
                    <h2 class="message-subject">{$data['subject']}</h2>
                    <div>{$data['content']}</div>
                </div>
                
                <p>Vous pouvez répondre directement à cet email pour contacter votre professeur.</p>
                
                <p style="margin-top: 30px;">Cordialement,<br>$professorName</p>
            </div>
            
            <div class="footer">
                <p>© $currentYear ScholarHub. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>
HTML;

    // Version texte alternative
    $mail->AltBody = "Bonjour $studentName,\n\n"
                   . "Vous avez reçu un message de votre professeur $professorName :\n\n"
                   . "Sujet: {$data['subject']}\n\n"
                   . "Message:\n{$data['content']}\n\n"
                   . "Vous pouvez répondre directement à cet email.\n\n"
                   . "Cordialement,\n$professorName";

    // Envoyer une copie au professeur si demandé
    if ($data['send_copy']) {
        $mail->addCC($professorEmail);
    }

    $mail->send();

    echo json_encode([
        'success' => true, 
        'message' => 'Message envoyé avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'error_details' => 'Fichier: ' . $e->getFile() . ' - Ligne: ' . $e->getLine()
    ]);
}