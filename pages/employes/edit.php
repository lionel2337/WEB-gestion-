<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Employe.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
if (!Auth::hasRole('rh') && !Auth::hasRole('admin')) {
    redirect(BASE_URL . '/errors/403.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect(BASE_URL . '/pages/employes/');

$db = Database::getInstance()->getConnection();
$employeClass = new Employe();
$emp = $employeClass->getById($id);

if (!$emp) {
    set_flash_message('danger', 'Employé introuvable.');
    redirect(BASE_URL . '/pages/employes/');
}

// Récupération des données pour les listes
$departements = $db->query("SELECT id_departement, nom_departement FROM departements WHERE est_supprime = 0 ORDER BY nom_departement")->fetchAll();
$succursales = $db->query("SELECT id_succursale, nom_succursale FROM succursales WHERE est_supprime = 0 ORDER BY nom_succursale")->fetchAll();
$pays = $db->query("SELECT id_pays, nom_pays FROM pays WHERE est_supprime = 0 ORDER BY nom_pays")->fetchAll();
// Pour les postes, on récupère ceux du département actuel de l'employé
$postes = [];
if ($emp['id_departement']) {
    $stmt_postes = $db->prepare("SELECT id_poste, titre_poste FROM postes WHERE id_departement = :id AND est_supprime = 0 ORDER BY titre_poste");
    $stmt_postes->execute([':id' => $emp['id_departement']]);
    $postes = $stmt_postes->fetchAll();
}

// Traitement du formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', "Erreur de sécurité CSRF.");
    } else {
        try {
            // Pour simplifier l'exemple, on met à jour uniquement les champs envoyés.
            // Dans une vraie application POO, on utiliserait une méthode update() dans la classe Employe.
            
            $sql = "UPDATE employes SET 
                    nom = :nom, prenom = :prenom, date_naissance = :date_naissance, sexe = :sexe,
                    email_professionnel = :email_professionnel, telephone_principal = :telephone_principal,
                    id_departement = :id_departement, id_poste = :id_poste, id_succursale = :id_succursale,
                    statut_employe = :statut_employe, date_modification = NOW()
                    WHERE id_employe = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nom' => clean_input($_POST['nom']),
                ':prenom' => clean_input($_POST['prenom']),
                ':date_naissance' => clean_input($_POST['date_naissance']),
                ':sexe' => clean_input($_POST['sexe']),
                ':email_professionnel' => clean_input($_POST['email_professionnel']),
                ':telephone_principal' => clean_input($_POST['telephone_principal']),
                ':id_departement' => !empty($_POST['id_departement']) ? (int)$_POST['id_departement'] : null,
                ':id_poste' => !empty($_POST['id_poste']) ? (int)$_POST['id_poste'] : null,
                ':id_succursale' => !empty($_POST['id_succursale']) ? (int)$_POST['id_succursale'] : null,
                ':statut_employe' => clean_input($_POST['statut_employe']),
                ':id' => $id
            ]);

            Logger::log("Modification employé", "modification", "employes", $id);
            set_flash_message('success', "Informations de l'employé mises à jour avec succès.");
            redirect(BASE_URL . '/pages/employes/view.php?id=' . $id);
            
        } catch (PDOException $e) {
            set_flash_message('danger', "Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }
}

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Modifier l'Employé</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/employes/">Employés</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/employes/view.php?id=<?= $id ?>"><?= escape($emp['matricule']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/pages/employes/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
        <i class="fas fa-times me-2"></i>Annuler
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="card-body p-4 p-md-5">
        <form method="POST" action="edit.php?id=<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
            
            <h5 class="fw-bold mb-4 border-bottom pb-2">Informations Générales</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control bg-light" id="matricule" value="<?= escape($emp['matricule']) ?>" readonly disabled>
                        <label>Matricule</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="statut_employe" name="statut_employe" required>
                            <option value="actif" <?= $emp['statut_employe'] == 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="en_conge" <?= $emp['statut_employe'] == 'en_conge' ? 'selected' : '' ?>>En congé</option>
                            <option value="en_mission" <?= $emp['statut_employe'] == 'en_mission' ? 'selected' : '' ?>>En mission</option>
                            <option value="suspendu" <?= $emp['statut_employe'] == 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                        </select>
                        <label>Statut de l'employé</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="nom" id="nom" value="<?= escape($emp['nom']) ?>" required>
                        <label>Nom de famille <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="prenom" id="prenom" value="<?= escape($emp['prenom']) ?>" required>
                        <label>Prénom(s) <span class="text-danger">*</span></label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="date_naissance" id="date_naissance" value="<?= escape($emp['date_naissance']) ?>" required>
                        <label>Date de naissance <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" name="sexe" required>
                            <option value="M" <?= $emp['sexe'] == 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $emp['sexe'] == 'F' ? 'selected' : '' ?>>Féminin</option>
                        </select>
                        <label>Sexe <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-4 border-bottom pb-2">Affectation Professionnelle</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="id_succursale" name="id_succursale" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($succursales as $suc): ?>
                                <option value="<?= $suc['id_succursale'] ?>" <?= $emp['id_succursale'] == $suc['id_succursale'] ? 'selected' : '' ?>><?= escape($suc['nom_succursale']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Succursale <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="id_departement" name="id_departement" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($departements as $dept): ?>
                                <option value="<?= $dept['id_departement'] ?>" <?= $emp['id_departement'] == $dept['id_departement'] ? 'selected' : '' ?>><?= escape($dept['nom_departement']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Département <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="id_poste" name="id_poste" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($postes as $p): ?>
                                <option value="<?= $p['id_poste'] ?>" <?= $emp['id_poste'] == $p['id_poste'] ? 'selected' : '' ?>><?= escape($p['titre_poste']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Poste <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-4 border-bottom pb-2">Coordonnées (Contact)</h5>
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="email" class="form-control" name="email_professionnel" id="email_professionnel" value="<?= escape($emp['email_professionnel']) ?>" required>
                        <label>Email Professionnel <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="tel" class="form-control" name="telephone_principal" id="telephone_principal" value="<?= escape($emp['telephone_principal']) ?>" required>
                        <label>Téléphone Principal <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end border-top pt-4">
                <button type="submit" class="btn btn-primary px-5 py-2">
                    <i class="fas fa-save me-2"></i>Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Chargement dynamique des postes si on change de département
        document.getElementById('id_departement').addEventListener('change', async function() {
            const deptId = this.value;
            const posteSelect = document.getElementById('id_poste');
            
            if (!deptId) {
                posteSelect.innerHTML = '<option value="">Sélectionner d\'abord un département</option>';
                return;
            }
            
            posteSelect.innerHTML = '<option value="">Chargement...</option>';
            
            const res = await Api.get('/ajax/common/get_postes.php?id_departement=' + deptId);
            
            if (res.success) {
                let html = '<option value="">Sélectionner un poste...</option>';
                res.data.forEach(p => {
                    html += `<option value="${p.id_poste}">${p.titre_poste}</option>`;
                });
                posteSelect.innerHTML = html;
            } else {
                posteSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            }
        });
    });
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
