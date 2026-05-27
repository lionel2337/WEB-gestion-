<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Conge.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

$congeClass = new Conge();
$is_emp = ($_SESSION['role'] === 'employe');

// Si c'est un simple employé, il voit uniquement ses congés, sinon il voit tout
$id_emp = $is_emp ? $_SESSION['employe_id'] : null;
$conges = $congeClass->getConges($id_emp);
$types = $congeClass->getTypesConges();

// Solde de congés pour l'employé connecté
$soldes_details = [];
if ($_SESSION['employe_id']) {
    foreach ($types as $t) {
        $soldes_details[] = [
            'type' => $t['nom_type'],
            'couleur' => $t['couleur'],
            'solde' => $congeClass->getSolde($_SESSION['employe_id'], $t['id_type_conge'])
        ];
    }
}

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Gestion des Congés</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Congés</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <?php if (!Auth::hasRole('employe') || Auth::hasRole('manager') || Auth::hasRole('rh')): ?>
        <a href="<?= BASE_URL ?>/pages/conges/approve.php" class="btn btn-outline-primary">
            <i class="fas fa-check-double me-2"></i>Approbations
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/pages/conges/create.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nouvelle Demande
        </a>
    </div>
</div>

<!-- Section des Soldes -->
<?php if (!empty($soldes_details)): ?>
<div class="row g-3 mb-4">
    <?php foreach ($soldes_details as $sd): ?>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
            <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background-color: <?= $sd['couleur'] ?>;"></div>
            <div class="card-body ps-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase"><?= escape($sd['type']) ?></span>
                    <i class="fas fa-umbrella-beach opacity-50" style="color: <?= $sd['couleur'] ?>;"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= $sd['solde']['jours_restants'] ?> <span class="fs-6 text-muted font-poppins fw-normal">jours restants</span></h3>
                <div class="mt-2 small text-muted">
                    <span>Acquis: <strong><?= $sd['solde']['jours_acquis'] ?></strong></span> | 
                    <span>Pris: <strong><?= $sd['solde']['jours_pris'] ?></strong></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Liste des demandes -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
        <h5 class="fw-bold text-dark font-poppins mb-0">Demandes de Congés</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Référence</th>
                        <?php if (!$is_emp): ?>
                        <th>Employé</th>
                        <th>Département</th>
                        <?php endif; ?>
                        <th>Type</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Durée</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Date Demande</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conges)): ?>
                    <tr>
                        <td colspan="<?= $is_emp ? 7 : 9 ?>" class="text-center py-5 text-muted">
                            <i class="fas fa-umbrella-beach fs-1 mb-3 opacity-25"></i>
                            <p class="mb-0">Aucune demande de congé enregistrée.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($conges as $c): ?>
                    <tr>
                        <td class="ps-4 fw-medium text-primary">
                            <?= escape($c['reference_conge']) ?>
                        </td>
                        <?php if (!$is_emp): ?>
                        <td>
                            <div class="fw-bold text-dark"><?= escape($c['prenom'] . ' ' . $c['nom']) ?></div>
                            <small class="text-muted"><?= escape($c['matricule']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= escape($c['nom_departement'] ?? 'Non assigné') ?></span>
                        </td>
                        <?php endif; ?>
                        <td>
                            <span class="badge" style="background-color: <?= $c['couleur'] ?>20; color: <?= $c['couleur'] ?>;">
                                <?= escape($c['nom_type']) ?>
                            </span>
                        </td>
                        <td><?= format_date_fr($c['date_debut']) ?></td>
                        <td><?= format_date_fr($c['date_fin']) ?></td>
                        <td class="fw-bold"><?= $c['demi_journee'] ? '0.5' : $c['nombre_jours'] ?> jour(s)</td>
                        <td class="text-center">
                            <?php
                            $bg = 'secondary';
                            $label = $c['statut'];
                            if ($c['statut'] === 'approuve_final') { $bg = 'success'; $label = 'Approuvé'; }
                            elseif ($c['statut'] === 'rejete') { $bg = 'danger'; $label = 'Rejeté'; }
                            elseif ($c['statut'] === 'soumis') { $bg = 'warning'; $label = 'En attente'; }
                            elseif (strpos($c['statut'], 'approuve_') === 0) { $bg = 'info'; $label = 'Approuvé (RH/Manager)'; }
                            ?>
                            <span class="badge bg-<?= $bg ?>"><?= $label ?></span>
                        </td>
                        <td class="text-end pe-4 small text-muted">
                            <?= format_date_fr($c['date_creation'], 'd/m/Y H:i') ?>
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
