<?php
/**
 * Utilitaire de réinitialisation du mot de passe Admin
 * À SUPPRIMER APRÈS UTILISATION !
 */

// Configuration de la base de données
$host = 'localhost';
$dbname = 'gestion_personnel';
$user = 'root';
$password = '';

$message = '';
$message_type = '';

try {
    // Connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Informations du compte
    $username = 'admin';
    $new_password_plain = 'Admin@2025';
    
    // Génération du hash
    $new_hash = password_hash($new_password_plain, PASSWORD_DEFAULT);
    
    // Mise à jour de la base de données
    $sql = "UPDATE utilisateurs SET 
            mot_de_passe = :hash,
            tentatives_echec = 0,
            compte_verrouille = 0,
            doit_changer_mdp = 0,
            statut = 'actif'
            WHERE nom_utilisateur = :username";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':hash' => $new_hash,
        ':username' => $username
    ]);

    // Vérification du nombre de lignes affectées
    if ($stmt->rowCount() > 0) {
        // Test de vérification
        if (password_verify($new_password_plain, $new_hash)) {
            $message = "Mot de passe réinitialisé avec succès et test de hachage validé !";
            $message_type = "success";
        } else {
            $message = "Le mot de passe a été mis à jour, mais le test de vérification a échoué.";
            $message_type = "warning";
        }
    } else {
        $message = "Aucun utilisateur trouvé avec le nom d'utilisateur '$username', ou le mot de passe était déjà le même.";
        $message_type = "warning";
    }

} catch (PDOException $e) {
    $message = "Erreur de base de données : " . $e->getMessage();
    $message_type = "error";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation Admin - Global Enterprise Corp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3a5f;
            --accent: #00d97e;
            --danger: #e63757;
            --warning: #f6c343;
            --bg-color: #f5f7fa;
            --text-main: #1e2e40;
            --text-muted: #6b7b8d;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, rgba(30,58,95,0.95) 0%, rgba(11,23,39,0.95) 100%);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        h1 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .subtitle {
            color: var(--text-muted);
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-success {
            background-color: rgba(0, 217, 126, 0.1);
            color: #00b368;
            border-left: 4px solid var(--accent);
        }

        .alert-warning {
            background-color: rgba(246, 195, 67, 0.1);
            color: #d39e00;
            border-left: 4px solid var(--warning);
        }

        .alert-error {
            background-color: rgba(230, 55, 87, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .credentials-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        .credential-label {
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .credential-value {
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            background: rgba(30,58,95,0.05);
            padding: 4px 12px;
            border-radius: 4px;
        }

        .danger-note {
            background-color: rgba(230, 55, 87, 0.1);
            color: var(--danger);
            padding: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #1e3a5f 0%, #2c7be5 100%);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(44, 123, 229, 0.4);
        }

    </style>
</head>
<body>

    <div class="container">
        <i class="fas fa-shield-halved logo-icon"></i>
        <h1>Utilitaire Admin</h1>
        <p class="subtitle">Global Enterprise Corp - Réinitialisation d'accès</p>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php if($message_type === 'success'): ?>
                    <i class="fas fa-check-circle fa-2x"></i>
                <?php elseif($message_type === 'error'): ?>
                    <i class="fas fa-times-circle fa-2x"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                <?php endif; ?>
                <div><?php echo $message; ?></div>
            </div>
        <?php endif; ?>

        <?php if($message_type === 'success' || $message_type === 'warning'): ?>
            <div class="credentials-box">
                <div class="credential-item">
                    <span class="credential-label">URL de l'application :</span>
                    <span class="credential-value">http://localhost/gestion_personnel/</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Nom d'utilisateur :</span>
                    <span class="credential-value">admin</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Mot de passe :</span>
                    <span class="credential-value">Admin@2025</span>
                </div>
            </div>

            <div class="danger-note">
                <i class="fas fa-triangle-exclamation" style="margin-top: 3px;"></i>
                <div>
                    <strong>ACTION REQUISE :</strong><br>
                    Pour des raisons de sécurité, vous devez IMPÉRATIVEMENT supprimer ce fichier (<code>generer_password.php</code>) de votre serveur après avoir noté ces accès.
                </div>
            </div>
        <?php endif; ?>

        <a href="index.php" class="btn">
            <i class="fas fa-arrow-right-to-bracket"></i> Aller à l'application
        </a>
    </div>

</body>
</html>
