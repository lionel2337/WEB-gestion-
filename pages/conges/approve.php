<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Conge.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

// Interdit aux simples employés
if ($_SESSION['role'] === 'employe') {
    redirect(BASE_URL . '/errors/403.php');
}

$congeClass = new Conge();

// Déterminer le rôle actif pour l'approbation
$is_admin = ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin');
$role_approbateur = 'manager';
if ($is_admin || Auth::hasRole('directeur')) {
    $role_approbateur = 'directeur';
} elseif (Auth::hasRole('rh')) {
    $role_approbateur = 'rh';
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $id_conge = clean_input($_POST['id_conge']);
        $decision = clean_input($_POST['decision']); // 'approuve' or 'rejete'
        $commentaire = clean_input($_POST['commentaire']);
        $user_id = $_SESSION['user_id'];

        $res = $congeClass->approve($id_conge, $role_approbateur, $decision, $commentaire, $user_id);
        if ($res['success']) {
            $success = "La décision a été enregistrée avec succès !";
        } else {
            $error = $res['message'];
        }
    }
}

// Charger toutes les demandes en attente
$all_conges = $congeClass->getConges();
$pending_conges = [];

foreach ($all_conges as $c) {
    if ($is_admin && !in_array($c['statut'], ['approuve_final', 'rejete', 'annule'])) {
        $pending_conges[] = $c;
    } elseif ($role_approbateur === 'manager' && $c['statut_manager'] === 'en_attente') {
        $pending_conges[] = $c;
    } elseif ($role_approbateur === 'rh' && $c['statut_rh'] === 'en_attente' && $c['statut_manager'] === 'approuve') {
        $pending_conges[] = $c;
    } elseif ($role_approbateur === 'directeur' && $c['statut_direction'] === 'en_attente' && $c['statut_rh'] === 'approuve') {
        $pending_conges[] = $c;
    }
}

include ROOT_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Interface d'Approbation des Congés</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/conges/">Congés</a></li>
            <li class="breadcrumb-item active" aria-current="page">Approbations</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
        <h5 class="fw-bold text-dark font-poppins mb-0">Demandes en attente d'approbation (Rôle: <span class="text-primary text-uppercase"><?= $role_approbateur ?></span>)</h5>
    </div>
    <div class="card-body p-4">
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
        <?php endif; ?>

        <?php if (empty($pending_conges)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-check-circle fs-1 mb-3 text-success opacity-75"></i>
            <p class="mb-0">Félicitations ! Aucune demande de congé n'est en attente pour votre rôle.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($pending_conges as $c): ?>
            <div class="col-12">
                <div class="border rounded-4 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-primary text-uppercase mb-2">Réf: <?= $c['reference_conge'] ?></span>
                            <h6 class="fw-bold text-dark mb-1"><?= escape($c['prenom'] . ' ' . $c['nom']) ?> (Matricule: <?= escape($c['matricule']) ?>)</h6>
                            <small class="text-muted"><i class="fas fa-building me-1"></i>Département: <?= escape($c['nom_departement']) ?></small>
                        </div>
                        <span class="badge" style="background-color: <?= $c['couleur'] ?>20; color: <?= $c['couleur'] ?>;">
                            <?= escape($c['nom_type']) ?> (<?= $c['nombre_jours'] ?> jours)
                        </span>
                    </div>

                    <div class="row g-2 mb-3 bg-white p-2 rounded-3 border small">
                        <div class="col-md-4">
                            <strong>Date de début:</strong> <?= format_date_fr($c['date_debut']) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Date de fin:</strong> <?= format_date_fr($c['date_fin']) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Motif:</strong> <?= escape($c['motif']) ?>
                        </div>
                    </div>

                    <!-- Formulaire décision -->
                    <form method="POST" action="" class="row g-2 align-items-center">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="id_conge" value="<?= $c['id_conge'] ?>">
                        
                        <div class="col-md-8">
                            <input type="text" class="form-control form-control-sm" name="commentaire" placeholder="Ajouter un commentaire de décision..." required>
                        </div>
                        <div class="col-md-4 d-flex gap-2 justify-content-end">
                            <button type="submit" name="decision" value="rejete" class="btn btn-sm btn-danger px-3">
                                <i class="fas fa-times me-1"></i> Rejeter
                            </button>
                            <button type="submit" name="decision" value="approuve" class="btn btn-sm btn-success px-3">
                                <i class="fas fa-check me-1"></i> Approuver
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
