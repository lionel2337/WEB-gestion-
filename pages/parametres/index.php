<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

// Seuls les admins ou super_admins ont accès aux paramètres système
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect(BASE_URL . '/errors/403.php');
}

$db = Database::getInstance()->getConnection();
$error = null;
$success = null;

// Gérer la mise à jour des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        try {
            $db->beginTransaction();
            foreach ($_POST['params'] as $id => $valeur) {
                $stmt = $db->prepare("UPDATE parametres SET valeur_parametre = :valeur, date_modification = NOW(), modifie_par = :user WHERE id_parametre = :id AND est_modifiable = 1");
                $stmt->execute([
                    ':valeur' => clean_input($valeur),
                    ':user' => $_SESSION['user_id'],
                    ':id' => $id
                ]);
            }
            $db->commit();
            $success = "Les paramètres système ont été mis à jour avec succès !";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Une erreur est survenue lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// Charger tous les paramètres triés par catégorie
$stmt = $db->query("SELECT * FROM parametres WHERE est_visible = 1 ORDER BY categorie ASC, ordre_affichage ASC");
$parametres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les paramètres par catégorie
$categories = [];
foreach ($parametres as $p) {
    $categories[$p['categorie']][] = $p;
}

$cat_names = [
    'entreprise' => '🏢 Informations Entreprise',
    'general' => '⚙️ Paramètres Généraux',
    'paie' => '💳 Paramètres de Paie',
    'conge' => '📅 Paramètres de Congés',
    'presence' => '⏰ Paramètres de Présences',
    'securite' => '🔒 Sécurité & Accès',
    'notification' => '🔔 Notifications',
    'apparence' => '🎨 Apparence & Interface'
];

include ROOT_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Paramètres Système</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active" aria-current="page">Paramètres</li>
        </ol>
    </nav>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

    <div class="row g-4">
        <!-- Navigation latérale des onglets -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <?php $first = true; foreach ($categories as $cat => $list): ?>
                    <button class="nav-link text-start rounded-3 py-2.5 mb-1 fw-bold font-poppins <?= $first ? 'active' : '' ?>" 
                            id="v-pills-<?= $cat ?>-tab" data-bs-toggle="pill" data-bs-target="#v-pills-<?= $cat ?>" 
                            type="button" role="tab">
                        <?= $cat_names[$cat] ?? $cat ?>
                    </button>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Contenu des onglets -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="tab-content" id="v-pills-tabContent">
                    <?php $first = true; foreach ($categories as $cat => $list): ?>
                    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="v-pills-<?= $cat ?>" role="tabpanel">
                        <h5 class="fw-bold text-dark font-poppins border-bottom pb-2 mb-4"><?= $cat_names[$cat] ?? $cat ?></h5>
                        
                        <?php foreach ($list as $p): ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                <?= escape(str_replace('_', ' ', $p['cle_parametre'])) ?>
                            </label>
                            <p class="text-muted small mb-2"><?= escape($p['description']) ?></p>
                            
                            <?php if ($p['type_valeur'] === 'booleen'): ?>
                            <select class="form-select" name="params[<?= $p['id_parametre'] ?>]">
                                <option value="1" <?= $p['valeur_parametre'] == '1' ? 'selected' : '' ?>>Activé / Oui</option>
                                <option value="0" <?= $p['valeur_parametre'] == '0' ? 'selected' : '' ?>>Désactivé / Non</option>
                            </select>
                            <?php elseif ($p['type_valeur'] === 'nombre'): ?>
                            <input type="number" class="form-control" name="params[<?= $p['id_parametre'] ?>]" value="<?= escape($p['valeur_parametre']) ?>">
                            <?php else: ?>
                            <input type="text" class="form-control" name="params[<?= $p['id_parametre'] ?>]" value="<?= escape($p['valeur_parametre']) ?>">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>

                <div class="border-top pt-3 text-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i> Enregistrer les Modifications
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
