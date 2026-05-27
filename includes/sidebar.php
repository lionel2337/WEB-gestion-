<?php
// On s'assure que le fichier est inclus et non appelé directement
if (!defined('ROOT_PATH')) exit;
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-center">
        <i class="fas fa-building logo-icon fs-2 text-white"></i>
        <span class="logo-text ms-2 fw-bold text-white font-poppins">GEC ORG</span>
    </div>

    <div class="user-info text-center p-3 border-bottom border-secondary mb-3">
        <img src="<?= BASE_URL ?>/assets/images/default_avatar.png" alt="User" class="rounded-circle img-thumbnail mb-2" width="60" height="60">
        <h6 class="text-white mb-0"><?= escape($_SESSION['username']) ?></h6>
        <span class="badge bg-primary text-uppercase mt-1"><?= escape($_SESSION['role']) ?></span>
    </div>

    <ul class="list-unstyled components">
        <li class="<?= ($current_dir == 'dashboard') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/dashboard/index.php">
                <i class="fas fa-home"></i> <span>Tableau de bord</span>
            </a>
        </li>
        
        <?php if (Auth::hasRole('manager')): ?>
        <li class="<?= ($current_dir == 'employes') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/employes/index.php">
                <i class="fas fa-users"></i> <span>Employés</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::hasRole('rh')): ?>
        <li class="<?= ($current_dir == 'departements') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/departements/index.php">
                <i class="fas fa-sitemap"></i> <span>Départements</span>
            </a>
        </li>
        <li class="<?= ($current_dir == 'contrats') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/contrats/index.php">
                <i class="fas fa-file-signature"></i> <span>Contrats</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="<?= ($current_dir == 'conges') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/conges/index.php">
                <i class="fas fa-umbrella-beach"></i> <span>Congés</span>
                <?php if(Auth::hasRole('manager')): ?><span class="badge bg-danger ms-2">2</span><?php endif; ?>
            </a>
        </li>

        <li class="<?= ($current_dir == 'presences') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/presences/index.php">
                <i class="fas fa-user-clock"></i> <span>Présences</span>
            </a>
        </li>

        <?php if (Auth::hasRole('comptable')): ?>
        <li class="<?= ($current_dir == 'paie') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/paie/index.php">
                <i class="fas fa-money-check-alt"></i> <span>Paie</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::hasRole('super_admin') || Auth::hasRole('admin')): ?>
        <li>
            <a href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fas fa-cogs"></i> <span>Administration</span>
            </a>
            <ul class="collapse list-unstyled" id="adminSubmenu">
                <li>
                    <a href="<?= BASE_URL ?>/pages/parametres/utilisateurs.php"><i class="fas fa-users-cog"></i> Utilisateurs</a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/pages/parametres/index.php"><i class="fas fa-sliders-h"></i> Paramètres</a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/pages/corbeille/index.php"><i class="fas fa-trash-restore"></i> Corbeille</a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer mt-auto p-3 text-center">
        <a href="<?= BASE_URL ?>/pages/auth/logout.php" class="btn btn-outline-light btn-sm w-100">
            <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
        </a>
    </div>
</nav>
