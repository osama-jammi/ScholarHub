<?php
// src/includes/mailer.php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function configurerMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jammiosama@gmail.com';
    $mail->Password   = 'oygh mpmp uiph dakt';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;
    $mail->setFrom('jammiosama@gmail.com', 'SCHOLARHUB');
    $mail->isHTML(true);
    return $mail;
}

function sendVerificationCode($email, $code) {
    $mail = configurerMailer();
    try {
        $mail->addAddress($email);
        $mail->Subject = 'Code de vérification - ScholarHub';
        
        $logoUrl = 'http://127.0.0.1/ScholarHub/public/assets/logo.png';
        $currentYear = date('Y');
        
        $mail->Body = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Code de vérification - ScholarHub</title>
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
                .code-container {
                    background: #f1f5f9;
                    border-radius: 8px;
                    padding: 24px;
                    text-align: center;
                    margin: 25px 0;
                    border: 1px dashed #cbd5e1;
                }
                .verification-code {
                    font-size: 32px;
                    font-weight: bold;
                    color: #2563eb;
                    letter-spacing: 3px;
                    font-family: monospace;
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
                    <h1>Vérification de votre compte</h1>
                    <p>Pour compléter votre inscription sur ScholarHub, veuillez utiliser le code de vérification suivant :</p>
                    
                    <div class="code-container">
                        <div class="verification-code">$code</div>
                    </div>
                    
                    <p>Ce code est valable pendant <strong>15 minutes</strong>. Ne le partagez avec personne.</p>
                    
                    <p style="margin-top: 30px;">Merci,<br>L'équipe ScholarHub</p>
                </div>
                
                <div class="footer">
                    <p>© $currentYear ScholarHub. Tous droits réservés.</p>
                    <p><a href="mailto:support@scholarhub.com" style="color: #64748b;">Support technique</a></p>
                </div>
            </div>
        </body>
        </html>
HTML;

        $mail->AltBody = "Bonjour,\n\nVotre code de vérification ScholarHub est : $code\n\nCe code expirera dans 15 minutes.\n\nCordialement,\nL'équipe ScholarHub";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi à $email : " . $mail->ErrorInfo);
        return false;
    }
}

function envoyerAnnonceAuxEtudiants($pdo, $moduleCode, $titre, $contenu, $lien) {
    $stmt = $pdo->prepare("SELECT E.nom, E.prenom, E.adresse 
                           FROM Etudiant E
                           JOIN Inscrit I ON E.id = I.etudiant_id 
                           WHERE I.module_code = ?");
    $stmt->execute([$moduleCode]);
    $etudiants = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT nom FROM Module WHERE code_inscription = ?");
    $stmt->execute([$moduleCode]);
    $module = $stmt->fetch();

    $moduleNom = $module['nom'] ?? 'le module';
    $currentYear = date('Y');
    $logoUrl = 'http://127.0.0.1/ScholarHub/public/assets/logo.png';

    foreach ($etudiants as $etudiant) {
        $mail = configurerMailer();
        try {
            $mail->addAddress($etudiant['adresse']);
            $mail->Subject = "Nouvelle annonce dans $moduleNom - ScholarHub";
            $mail->Body = <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Nouvelle annonce - ScholarHub</title>
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
                    .annonce-card {
                        background: #f1f5f9;
                        border-radius: 8px;
                        padding: 24px;
                        margin: 20px 0;
                        border-left: 4px solid #3b82f6;
                    }
                    .annonce-title {
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
                        <h1>Bonjour {$etudiant['prenom']},</h1>
                        <p>Une nouvelle annonce a été publiée dans le module <strong>$moduleNom</strong>.</p>
                        
                        <div class="annonce-card">
                            <h2 class="annonce-title">$titre</h2>
                            <div>{$contenu}</div>
                        </div>
                        
                        <a href="$lien" class="button">Voir l'annonce complète</a>
                        
                        <p style="margin-top: 30px;">Cordialement,<br>L'équipe ScholarHub</p>
                    </div>
                    
                    <div class="footer">
                        <p>© $currentYear ScholarHub. Tous droits réservés.</p>
                    </div>
                </div>
            </body>
            </html>
HTML;
            $mail->isHTML(true);
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi de mail à {$etudiant['adresse']}: " . $mail->ErrorInfo);
        }
    }
}

function envoyerNotificationTravail($pdo, $moduleCode, $titre, $description, $dateLimite) {
    $stmt = $pdo->prepare("SELECT E.nom, E.prenom, E.adresse 
                           FROM Etudiant E
                           JOIN Inscrit I ON E.id = I.etudiant_id 
                           WHERE I.module_code = ?");
    $stmt->execute([$moduleCode]);
    $etudiants = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT nom FROM Module WHERE code_inscription = ?");
    $stmt->execute([$moduleCode]);
    $module = $stmt->fetch();

    $moduleNom = $module['nom'] ?? 'le module';
    $dateFormatee = date('d/m/Y à H:i', strtotime($dateLimite));
    $currentYear = date('Y');
    $logoUrl = 'http://127.0.0.1/ScholarHub/public/assets/logo.png';

    foreach ($etudiants as $etudiant) {
        $mail = configurerMailer();
        try {
            $mail->addAddress($etudiant['adresse']);
            $mail->Subject = "Nouveau travail à rendre dans $moduleNom - ScholarHub";
            $mail->Body = <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Nouveau travail - ScholarHub</title>
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
                    .assignment-card {
                        background: #f1f5f9;
                        border-radius: 8px;
                        padding: 24px;
                        margin: 20px 0;
                        border-left: 4px solid #ef4444;
                    }
                    .assignment-title {
                        font-size: 20px;
                        margin-top: 0;
                        color: #1e293b;
                    }
                    .deadline {
                        margin-top: 16px;
                        padding: 12px;
                        background: #fee2e2;
                        border-radius: 6px;
                        color: #b91c1c;
                        font-weight: 500;
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
                        <h1>Bonjour {$etudiant['prenom']},</h1>
                        <p>Un nouveau travail a été ajouté dans le module <strong>$moduleNom</strong>.</p>
                        
                        <div class="assignment-card">
                            <h2 class="assignment-title">$titre</h2>
                            <div>{$description}</div>
                            
                            <div class="deadline">
                                ⏰ Date limite : $dateFormatee
                            </div>
                        </div>
                        
                        <p>Vous avez jusqu'à cette date pour soumettre votre travail.</p>
                        
                        <a href="http://127.0.0.1/ScholarHub/public/Book.php?module_code=$moduleCode" class="button">Accéder au module</a>
                        
                        <p style="margin-top: 30px;">Cordialement,<br>L'équipe ScholarHub</p>
                    </div>
                    
                    <div class="footer">
                        <p>© $currentYear ScholarHub. Tous droits réservés.</p>
                    </div>
                </div>
            </body>
            </html>
HTML;
            $mail->isHTML(true);
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi de mail à {$etudiant['adresse']}: " . $mail->ErrorInfo);
        }
    }
}