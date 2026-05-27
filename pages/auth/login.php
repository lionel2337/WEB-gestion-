<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/functions.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/pages/dashboard/index.php');
}

$error = '';

// Timeout message
if (isset($_GET['timeout'])) {
    $error = "Votre session a expiré. Veuillez vous reconnecter.";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session invalide. Veuillez réessayer.";
    } else {
        $username = clean_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            $result = Auth::login($username, $password);
            if ($result['success']) {
                redirect(BASE_URL . '/pages/dashboard/index.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= APP_NAME ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c7be5;
            --accent: #00d97e;
        }
        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(30,58,95,0.9) 0%, rgba(11,23,39,0.9) 100%), url('<?= BASE_URL ?>/assets/images/login_bg.jpg') center/cover;
            margin: 0;
            color: #1e2e40;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-area i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .logo-area h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--primary);
            font-size: 1.5rem;
            margin: 0;
        }
        .form-floating > .form-control {
            border-radius: 8px;
            border: 1px solid #d8e2ef;
        }
        .form-floating > .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(44, 123, 229, 0.25);
        }
        .input-group-text {
            background: transparent;
            border-right: none;
            color: #6b7b8d;
        }
        .form-control-with-icon {
            border-left: none;
        }
        .input-group:focus-within .input-group-text {
            border-color: var(--secondary);
            color: var(--secondary);
        }
        .input-group:focus-within .form-control-with-icon {
            border-color: var(--secondary);
            box-shadow: none;
        }
        .input-group {
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .input-group .form-control {
            padding-left: 0;
        }
        .btn-login {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 123, 229, 0.4);
        }
        .forgot-link {
            color: var(--secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s;
        }
        .forgot-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="login-card animate__animated animate__fadeInUp">
    <div class="logo-area">
        <i class="fas fa-building"></i>
        <h2>GLOBAL ENTERPRISE</h2>
        <p class="text-muted small">Système de Gestion du Personnel</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger animate__animated animate__shakeX">
            <i class="fas fa-exclamation-circle me-2"></i> <?= escape($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <div class="form-floating flex-grow-1">
                <input type="text" class="form-control form-control-with-icon" id="username" name="username" placeholder="Nom d'utilisateur" required autofocus>
                <label for="username">Nom d'utilisateur</label>
            </div>
        </div>

        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <div class="form-floating flex-grow-1">
                <input type="password" class="form-control form-control-with-icon" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label text-muted small" for="remember">
                    Se souvenir de moi
                </label>
            </div>
            <a href="#" class="forgot-link">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-login">
            <i class="fas fa-sign-in-alt me-2"></i> CONNEXION
        </button>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
