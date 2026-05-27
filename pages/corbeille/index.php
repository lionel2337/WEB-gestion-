<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Corbeille.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

// Seuls les administrateurs et super_admins ont accès à la corbeille
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect(BASE_URL . '/errors/403.php');
}

$corbeilleClass = new Corbeille();
$error = null;
$success = null;

// Gérer la restauration ou la purge
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $action = clean_input($_POST['action']);
        $id_corbeille = clean_input($_POST['id_corbeille']);
        $user_id = $_SESSION['user_id'];

        if ($action === 'restore') {
            $res = $corbeilleClass->restaurer($id_corbeille, $user_id);
            if ($res['success']) {
                $success = "L'élément a été restauré avec succès !";
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'purge') {
            // Seul super_admin peut purger définitivement
            if (!Auth::hasRole('super_admin')) {
                $error = "Seul le Super Administrateur peut supprimer définitivement des données.";
            } else {
                $res = $corbeilleClass->purger($id_corbeille, $user_id);
                if ($res['success']) {
                    $success = "L'élément a été définitivement supprimé de la base de données.";
                } else {
                    $error = $res['message'];
                }
            }
        }
    }
}

// Charger tous les éléments de la corbeille
$items = $corbeilleClass->getAll();

include ROOT_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Corbeille & Archivage</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active" aria-current="page">Corbeille</li>
        </ol>
    </nav>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark font-poppins mb-0">Éléments supprimés temporairement</h5>
        <span class="badge bg-danger rounded-pill"><?= count($items) ?> élément(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Type/Table</th>
                        <th>Description / ID</th>
                        <th>Supprimé par</th>
                        <th>Date de suppression</th>
                        <th>Expiration</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-trash-alt fs-1 mb-3 opacity-25"></i>
                            <p class="mb-0">La corbeille est vide.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-secondary text-uppercase"><?= escape($item['table_origine']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= escape($item['description']) ?></div>
                            <small class="text-muted">ID original: <?= $item['id_enregistrement_original'] ?></small>
                        </td>
                        <td><?= escape($item['nom_utilisateur'] ?? 'Système') ?></td>
                        <td><?= format_date_fr($item['date_suppression'], 'd/m/Y H:i') ?></td>
                        <td class="text-danger fw-bold"><?= format_date_fr($item['date_expiration_corbeille']) ?></td>
                        <td class="text-end pe-4">
                            <form method="POST" action="" class="d-inline-block">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="id_corbeille" value="<?= $item['id_corbeille'] ?>">
                                
                                <button type="submit" name="action" value="restore" class="btn btn-sm btn-outline-success me-1" title="Restaurer l'élément">
                                    <i class="fas fa-undo me-1"></i> Restaurer
                                </button>
                                
                                <?php if (Auth::hasRole('super_admin')): ?>
                                <button type="submit" name="action" value="purge" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer définitivement cet enregistrement ? Cette action est irréversible.')" title="Purger définitivement">
                                    <i class="fas fa-skull me-1"></i> Purger
                                </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
