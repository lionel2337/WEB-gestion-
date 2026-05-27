<?php
require_once dirname(__DIR__) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include ROOT_PATH . '/includes/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="content" class="active">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white top-navbar">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary btn-sm">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="ms-3 d-none d-md-block flex-grow-1">
                        <!-- Breadcrumb générique (sera dynamique selon la page) -->
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Accueil</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                            </ol>
                        </nav>
                    </div>

                    <div class="d-flex align-items-center ms-auto">
                        <!-- Search -->
                        <div class="nav-item me-3 d-none d-lg-block">
                            <div class="input-group search-bar">
                                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" placeholder="Rechercher...">
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="nav-item dropdown me-3">
                            <a class="nav-link dropdown-toggle hidden-arrow" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell text-secondary" style="font-size: 1.2rem;"></i>
                                <span class="badge rounded-pill badge-notification bg-danger">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarDropdownMenuLink">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><a class="dropdown-item" href="#">Aucune nouvelle notification</a></li>
                            </ul>
                        </div>

                        <!-- User Profile -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= BASE_URL ?>/assets/images/default_avatar.png" class="rounded-circle avatar-sm border" height="35" width="35" alt="Avatar">
                                <span class="ms-2 d-none d-md-block text-dark fw-medium"><?= escape($_SESSION['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarDropdownUser">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-circle me-2"></i> Mon Profil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Paramètres</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/pages/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="container-fluid p-4 main-content-area">
                <?= display_flash_messages(); ?>
