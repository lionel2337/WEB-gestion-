<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Contrat.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

if (!Auth::hasRole('rh') && !Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect(BASE_URL . '/errors/403.php');
}

$contratClass = new Contrat();
$db = Database::getInstance()->getConnection();

// Traitement de l'ajout d'un contrat
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $upload = handle_file_upload('document_contrat', ROOT_PATH . '/assets/uploads/documents/', ['application/pdf']);
        if (isset($upload['error'])) {
            $error = $upload['error'];
        } else {
            $ref = 'CTR-' . date('Ymd') . '-' . rand(100, 999);
            $stmt = $db->prepare("INSERT INTO contrats (reference_contrat, id_employe, type_contrat, date_debut, date_fin, salaire_base, devise, document_contrat, statut, est_supprime, cree_par, date_creation) VALUES (:ref, :id_emp, :type, :debut, :fin, :salaire, :devise, :doc, 'en_cours', 0, :cree_par, NOW())");
            
            $res = $stmt->execute([
                ':ref' => $ref,
                ':id_emp' => clean_input($_POST['id_employe']),
                ':type' => clean_input($_POST['type_contrat']),
                ':debut' => clean_input($_POST['date_debut']),
                ':fin' => !empty($_POST['date_fin']) ? clean_input($_POST['date_fin']) : null,
                ':salaire' => clean_input($_POST['salaire_base']),
                ':devise' => 'XAF',
                ':doc' => $upload['path'],
                ':cree_par' => $_SESSION['user_id']
            ]);

            if ($res) {
                $success = "Contrat enregistré avec succès !";
            } else {
                $error = "Une erreur est survenue lors de l'enregistrement.";
            }
        }
    }
}

// Charger tous les contrats avec les informations des employés
$stmt_list = $db->query("SELECT c.*, e.nom, e.prenom, e.matricule 
                         FROM contrats c 
                         JOIN employes e ON c.id_employe = e.id_employe 
                         WHERE c.est_supprime = 0 
                         ORDER BY c.date_creation DESC");
$contrats = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// Charger la liste des employés actifs pour le formulaire
$stmt_emp = $db->query("SELECT id_employe, nom, prenom FROM employes WHERE est_supprime = 0 ORDER BY nom ASC");
$employees = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Gestion des Contrats</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Contrats</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contratModal">
        <i class="fas fa-plus me-2"></i>Nouveau Contrat
    </button>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
        <h5 class="fw-bold text-dark font-poppins mb-0">Liste des Contrats Actifs</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Référence</th>
                        <th>Employé</th>
                        <th>Type</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Salaire de base</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contrats)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-file-signature fs-1 mb-3 opacity-25"></i>
                            <p class="mb-0">Aucun contrat enregistré.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($contrats as $c): ?>
                    <tr>
                        <td class="ps-4 fw-medium text-primary"><?= escape($c['reference_contrat']) ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= escape($c['prenom'] . ' ' . $c['nom']) ?></div>
                            <small class="text-muted"><?= escape($c['matricule']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= escape($c['type_contrat']) ?></span></td>
                        <td><?= format_date_fr($c['date_debut']) ?></td>
                        <td><?= $c['date_fin'] ? format_date_fr($c['date_fin']) : '<span class="text-muted">Indéterminé (CDI)</span>' ?></td>
                        <td class="fw-bold"><?= format_money($c['salaire_base']) ?></td>
                        <td>
                            <span class="badge bg-<?= $c['statut'] === 'en_cours' ? 'success' : 'secondary' ?>">
                                <?= $c['statut'] === 'en_cours' ? 'En cours' : escape($c['statut']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <?php if ($c['document_contrat']): ?>
                            <a href="<?= BASE_URL . '/assets/uploads/documents/' . basename($c['document_contrat']) ?>" target="_blank" class="btn btn-sm btn-light text-primary me-1" title="Télécharger le PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nouveau Contrat -->
<div class="modal fade" id="contratModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Enregistrer un Contrat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Employé <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_employe" required>
                            <option value="">Sélectionnez un employé</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id_employe'] ?>"><?= escape($emp['prenom'] . ' ' . $emp['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Type de contrat <span class="text-danger">*</span></label>
                        <select class="form-select" name="type_contrat" required>
                            <option value="CDI">CDI - Durée Indéterminée</option>
                            <option value="CDD">CDD - Durée Déterminée</option>
                            <option value="stage">Stage</option>
                            <option value="freelance">Freelance</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_debut" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Date de fin</label>
                            <input type="date" class="form-control" name="date_fin">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Salaire de base (XAF) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="salaire_base" required placeholder="Ex: 250000">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Document du contrat (PDF uniquement) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="document_contrat" accept="application/pdf" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
