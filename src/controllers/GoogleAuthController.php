<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

class GoogleAuthController {
    private $client;
    
    public function __construct() {
        $this->initializeClient();
    }
    
    private function initializeClient() {
        $this->client = new Google\Client();
        
        // Configuration des identifiants OAuth
       // $this->client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ??
       // $this->client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ??
        
        // URL de redirection - doit correspondre à celle enregistrée dans Google Cloud Console
        $this->client->setRedirectUri('http://127.0.0.1/ScholarHub/src/controllers/GoogleAuthController.php');
        
        // Scopes demandés
        $this->client->addScope('email');
        $this->client->addScope('profile');
        $this->client->setAccessType('offline'); // Pour obtenir un refresh token
        $this->client->setPrompt('consent'); // Force la demande de permissions à chaque fois
    }
    
    public function handleRequest() {
        try {
            if (isset($_GET['code'])) {
                $this->handleCallback();
            } elseif (isset($_GET['error'])) {
                $this->handleError();
            } else {
                $this->redirectToGoogle();
            }
        } catch (Exception $e) {
            error_log('Google Auth Error: ' . $e->getMessage());
            $this->redirectWithError('google_auth_error');
        }
    }
    
    private function redirectToGoogle() {
        // Génération et stockage du token CSRF
        $_SESSION['oauth2state'] = bin2hex(random_bytes(32));
        $this->client->setState($_SESSION['oauth2state']);
        
        $authUrl = $this->client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }
    
    private function handleCallback() {
        // Vérification CSRF
        if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || 
            ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            throw new Exception('Invalid state parameter');
        }
    
        try {
            // Échange du code d'autorisation contre un token
            $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            // Debug logging
            error_log('Token response: ' . print_r($token, true));
            
            if (!is_array($token)) {
                throw new Exception('Invalid token response format');
            }
            
            if (isset($token['error'])) {
                throw new Exception($token['error_description'] ?? $token['error']);
            }
            
            $this->client->setAccessToken($token);


                    // Récupération des informations utilisateur
        $oauth2 = new Google\Service\Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();
        
        // Stockage des informations utilisateur en session
        $_SESSION['user_temp'] = [
            'nom' => $userInfo->familyName ?? '',
            'prenom' => $userInfo->givenName ?? '',
            'email' => $userInfo->email,
            'password' => null,
            'auth_provider' => 'google',
            'google_id' => $userInfo->id,
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? null
        ];

        // Téléchargement de la photo de profil si disponible
        if (!empty($userInfo->picture)) {
            try {
                $photoContent = file_get_contents($userInfo->picture);
                $_SESSION['user_temp']['google_photo'] = base64_encode($photoContent);
            } catch (Exception $e) {
                error_log('Failed to download Google profile picture: ' . $e->getMessage());
            }
        }

        // Redirection vers la page de choix de rôle
        $this->redirectToRoleSelection();
            
           
        } catch (Exception $e) {
            error_log('Google Auth Token Exchange Error: ' . $e->getMessage());
            throw new Exception('Failed to authenticate with Google: ' . $e->getMessage());
        }
    }


    
    private function handleError() {
        $error = $_GET['error'];
        $errorDescription = $_GET['error_description'] ?? 'Unknown error';
        
        error_log("Google OAuth Error: $error - $errorDescription");
        $this->redirectWithError('google_auth_failed');
    }
    
    private function redirectToRoleSelection() {
        header('Location: /ScholarHub/docs/public/choose_role.php');
        exit;
    }
    
    private function redirectWithError($errorCode) {
        header('Location: /ScholarHub/public/connexion.php?error=' . urlencode($errorCode));
        exit;
    }
}

// Exécution du contrôleur
try {
    $authController = new GoogleAuthController();
    $authController->handleRequest();
} catch (Exception $e) {
    error_log('Fatal error in GoogleAuthController: ' . $e->getMessage());
    header('Location: /ScholarHub/public/connexion.php?error=system_error');
    exit;
}