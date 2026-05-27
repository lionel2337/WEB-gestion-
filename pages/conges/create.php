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
$types = $congeClass->getTypesConges();

// Charger les autres employés actifs pour l'intérim
$db = Database::getInstance()->getConnection();
$stmt_emp = $db->prepare("SELECT id_employe, nom, prenom FROM employes WHERE est_supprime = 0 AND id_employe != :my_id ORDER BY nom ASC");
$stmt_emp->execute([':my_id' => $_SESSION['employe_id'] ?? 0]);
$employees = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $data = [
            'id_employe' => $_SESSION['employe_id'],
            'id_type_conge' => clean_input($_POST['id_type_conge']),
            'date_debut' => clean_input($_POST['date_debut']),
            'date_fin' => clean_input($_POST['date_fin']),
            'demi_journee' => isset($_POST['demi_journee']) ? 1 : 0,
            'motif' => clean_input($_POST['motif']),
            'adresse_pendant_conge' => clean_input($_POST['adresse']),
            'telephone_pendant_conge' => clean_input($_POST['telephone']),
            'id_interim' => !empty($_POST['id_interim']) ? clean_input($_POST['id_interim']) : null
        ];

        // Calculer la durée en jours
        $start = new DateTime($data['date_debut']);
        $end = new DateTime($data['date_fin']);
        $interval = $start->diff($end);
        $jours = $interval->days + 1;

        if ($start > $end) {
            $error = "La date de début ne peut pas être supérieure à la date de fin.";
        } else {
            $data['nombre_jours'] = $jours;

            // Vérification du solde restant
            $solde = $congeClass->getSolde($_SESSION['employe_id'], $data['id_type_conge']);
            if ($solde['jours_restants'] < $jours) {
                $error = "Solde insuffisant. Vous demandez $jours jour(s) mais il ne vous reste que {$solde['jours_restants']} jour(s).";
            } else {
                $res = $congeClass->create($data);
                if ($res['success']) {
                    $success = "Votre demande de congé a été soumise avec succès ! Réf : " . $res['reference'];
                    // Redirection après 1.5s
                    header("Refresh: 1.5; URL=" . BASE_URL . "/pages/conges/");
                } else {
                    $error = $res['message'];
                }
            }
        }
    }
}

include ROOT_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Demander un Congé</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/conges/">Congés</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nouvelle Demande</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type de congé <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_type_conge" required>
                                <option value="">Sélectionnez un type</option>
                                <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id_type_conge'] ?>"><?= escape($t['nom_type']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="demi_journee" id="demi_journee">
                                <label class="form-check-label fw-bold" for="demi_journee">
                                    Demi-journée
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_debut" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_fin" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Employé assurant l'intérim</label>
                        <select class="form-select" name="id_interim">
                            <option value="">Sélectionnez un collègue</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id_employe'] ?>"><?= escape($emp['prenom'] . ' ' . $emp['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Motif du congé <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motif" rows="3" required placeholder="Veuillez décrire le motif de votre absence..."></textarea>
                    </div>

                    <h5 class="border-bottom pb-2 mb-3 fw-bold text-muted small text-uppercase">Contacts pendant l'absence</h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Adresse</label>
                            <input type="text" class="form-control" name="adresse" placeholder="Adresse complète de résidence...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Téléphone de contact</label>
                            <input type="tel" class="form-control" name="telephone" placeholder="Numéro joignable...">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>/pages/conges/" class="btn btn-light">Annuler</a>
                        <button type="submit" class="btn btn-primary px-4">Soumettre la Demande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
