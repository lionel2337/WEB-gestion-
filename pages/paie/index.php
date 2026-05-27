<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Paie.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

if (!Auth::hasRole('comptable') && !Auth::hasRole('rh') && !Auth::hasRole('admin') && !Auth::hasRole('super_admin') && !Auth::hasRole('employe')) {
    redirect(BASE_URL . '/errors/403.php');
}

$is_emp = ($_SESSION['role'] === 'employe');
$db = Database::getInstance()->getConnection();

$error = null;
$success = null;

// Génération de bulletin (Comptable/Admin/RH uniquement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_emp) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $id_emp = clean_input($_POST['id_employe']);
        $mois = clean_input($_POST['mois']);
        $annee = clean_input($_POST['annee']);

        // Vérifier si un bulletin existe déjà pour ce mois/année
        $stmt_check = $db->prepare("SELECT id_paie FROM paie WHERE id_employe = :id_emp AND mois = :mois AND annee = :annee AND est_annule = 0");
        $stmt_check->execute([':id_emp' => $id_emp, ':mois' => $mois, ':annee' => $annee]);
        if ($stmt_check->fetch()) {
            $error = "Un bulletin de paie existe déjà pour cet employé ce mois-ci.";
        } else {
            // Récupérer le contrat en cours
            $stmt_ctr = $db->prepare("SELECT id_contrat, salaire_base FROM contrats WHERE id_employe = :id_emp AND statut = 'en_cours' AND est_supprime = 0");
            $stmt_ctr->execute([':id_emp' => $id_emp]);
            $contrat = $stmt_ctr->fetch(PDO::FETCH_ASSOC);

            if (!$contrat) {
                $error = "Cet employé n'a aucun contrat actif ('en_cours') en cours de validité.";
            } else {
                $sal_base = $contrat['salaire_base'];
                $prime_trans = 25000;
                $prime_log = 30000;
                $total_brut = $sal_base + $prime_trans + $prime_log;

                // Retenues
                $cnps = round($total_brut * 0.042, 2);
                $irpp = round($total_brut * 0.08, 2);
                $total_ret = $cnps + $irpp;

                $net = $total_brut - $total_ret;
                $ref = 'PAY-' . $annee . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . rand(100, 999);

                $stmt_ins = $db->prepare("INSERT INTO paie (reference_bulletin, id_employe, id_contrat, mois, annee, salaire_base, prime_transport, prime_logement, total_brut, retenue_cnps_employe, retenue_irpp, total_retenues_salariales, salaire_net, statut_paiement, mode_paiement, date_paiement, est_annule, genere_par, date_creation) VALUES (:ref, :id_emp, :id_ctr, :mois, :annee, :sal_base, :trans, :log, :brut, :cnps, :irpp, :tot_ret, :net, 'calcule', 'virement', NOW(), 0, :user, NOW())");
                
                $res = $stmt_ins->execute([
                    ':ref' => $ref,
                    ':id_emp' => $id_emp,
                    ':id_ctr' => $contrat['id_contrat'],
                    ':mois' => $mois,
                    ':annee' => $annee,
                    ':sal_base' => $sal_base,
                    ':trans' => $prime_trans,
                    ':log' => $prime_log,
                    ':brut' => $total_brut,
                    ':cnps' => $cnps,
                    ':irpp' => $irpp,
                    ':tot_ret' => $total_ret,
                    ':net' => $net,
                    ':user' => $_SESSION['user_id']
                ]);

                if ($res) {
                    $success = "Bulletin de paie généré avec succès ! Réf: $ref";
                } else {
                    $error = "Une erreur est survenue lors du calcul.";
                }
            }
        }
    }
}

// Charger tous les bulletins de paie
$sql_bulletins = "SELECT p.*, e.nom, e.prenom, e.matricule 
                  FROM paie p 
                  JOIN employes e ON p.id_employe = e.id_employe 
                  WHERE p.est_annule = 0";
$params = [];
if ($is_emp) {
    $sql_bulletins .= " AND p.id_employe = :my_id";
    $params[':my_id'] = $_SESSION['employe_id'];
}
$sql_bulletins .= " ORDER BY p.annee DESC, p.mois DESC";

$stmt_bulletins = $db->prepare($sql_bulletins);
$stmt_bulletins->execute($params);
$bulletins = $stmt_bulletins->fetchAll(PDO::FETCH_ASSOC);

// Charger les employés pour le formulaire
$stmt_emp = $db->query("SELECT id_employe, nom, prenom FROM employes WHERE est_supprime = 0 ORDER BY nom ASC");
$employees = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

$noms_mois = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Gestion de la Paie</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Paie</li>
            </ol>
        </nav>
    </div>
    <?php if (!$is_emp): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generatePaieModal">
        <i class="fas fa-calculator me-2"></i>Calculer Bulletin
    </button>
    <?php endif; ?>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
        <h5 class="fw-bold text-dark font-poppins mb-0">Historique des Bulletins de Paie</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Référence</th>
                        <?php if (!$is_emp): ?>
                        <th>Employé</th>
                        <?php endif; ?>
                        <th>Mois/Année</th>
                        <th>Salaire Base</th>
                        <th>Total Brut</th>
                        <th>Total Retenues</th>
                        <th>Salaire Net</th>
                        <th class="text-end pe-4">Date de Calcul</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bulletins)): ?>
                    <tr>
                        <td colspan="<?= $is_emp ? 7 : 8 ?>" class="text-center py-5 text-muted">
                            <i class="fas fa-money-check-alt fs-1 mb-3 opacity-25"></i>
                            <p class="mb-0">Aucun bulletin de paie généré pour le moment.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($bulletins as $b): ?>
                    <tr>
                        <td class="ps-4 fw-medium text-primary"><?= escape($b['reference_bulletin']) ?></td>
                        <?php if (!$is_emp): ?>
                        <td>
                            <div class="fw-bold text-dark"><?= escape($b['prenom'] . ' ' . $b['nom']) ?></div>
                            <small class="text-muted"><?= escape($b['matricule']) ?></small>
                        </td>
                        <?php endif; ?>
                        <td><?= $noms_mois[$b['mois']] . ' ' . $b['annee'] ?></td>
                        <td><?= format_money($b['salaire_base']) ?></td>
                        <td class="text-dark fw-bold"><?= format_money($b['total_brut']) ?></td>
                        <td class="text-danger fw-medium"><?= format_money($b['total_retenues_salariales']) ?></td>
                        <td class="text-success fw-bold"><?= format_money($b['salaire_net']) ?></td>
                        <td class="text-end pe-4 small text-muted"><?= format_date_fr($b['date_creation'], 'd/m/Y H:i') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Calculer Bulletin -->
<?php if (!$is_emp): ?>
<div class="modal fade" id="generatePaieModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Générer un Bulletin de Paie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
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

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-bold">Mois <span class="text-danger">*</span></label>
                            <select class="form-select" name="mois" required>
                                <?php foreach ($noms_mois as $num => $nom): ?>
                                <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>><?= $nom ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Année <span class="text-danger">*</span></label>
                            <select class="form-select" name="annee" required>
                                <option value="2025" <?= date('Y') == '2025' ? 'selected' : '' ?>>2025</option>
                                <option value="2026" <?= date('Y') == '2026' ? 'selected' : '' ?>>2026</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4">Calculer et Générer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
