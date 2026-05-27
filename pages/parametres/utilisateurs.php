<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

// Seuls les super_admins ou admins peuvent gérer les utilisateurs
if (!Auth::hasRole('super_admin') && !Auth::hasRole('admin')) {
    redirect(BASE_URL . '/errors/403.php');
}

$db = Database::getInstance()->getConnection();
$error = null;
$success = null;

// Gérer la mise à jour des rôles ou le blocage d'utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $action = clean_input($_POST['action']);
        $id_user = clean_input($_POST['id_utilisateur']);

        if ($action === 'change_role') {
            $nouveau_role = clean_input($_POST['role']);
            $stmt = $db->prepare("UPDATE utilisateurs SET role = :role, date_modification = NOW() WHERE id_utilisateur = :id");
            $res = $stmt->execute([':role' => $nouveau_role, ':id' => $id_user]);
            if ($res) {
                $success = "Rôle mis à jour avec succès !";
            } else {
                $error = "Impossible de mettre à jour le rôle.";
            }
        } elseif ($action === 'toggle_status') {
            $nouveau_statut = clean_input($_POST['statut']);
            $stmt = $db->prepare("UPDATE utilisateurs SET statut = :statut, date_modification = NOW() WHERE id_utilisateur = :id");
            $res = $stmt->execute([':statut' => $nouveau_statut, ':id' => $id_user]);
            if ($res) {
                $success = "Statut de l'utilisateur mis à jour !";
            } else {
                $error = "Impossible de mettre à jour le statut.";
            }
        }
    }
}

// Charger tous les utilisateurs
$stmt = $db->query("SELECT u.*, e.nom, e.prenom, e.matricule 
                    FROM utilisateurs u 
                    LEFT JOIN employes e ON u.id_employe = e.id_employe 
                    WHERE u.est_supprime = 0 
                    ORDER BY u.nom_utilisateur ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include ROOT_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Gestion des Utilisateurs</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
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
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
        <h5 class="fw-bold text-dark font-poppins mb-0">Comptes Utilisateurs Actifs</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nom d'utilisateur</th>
                        <th>Employé lié</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Dernière Connexion</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= escape($u['nom_utilisateur']) ?></td>
                        <td>
                            <?php if ($u['id_employe']): ?>
                            <div class="fw-bold text-dark"><?= escape($u['prenom'] . ' ' . $u['nom']) ?></div>
                            <small class="text-muted"><?= escape($u['matricule']) ?></small>
                            <?php else: ?>
                            <span class="text-muted">Aucun (Administrateur système)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary text-uppercase"><?= escape($u['role']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $u['statut'] === 'actif' ? 'success' : 'danger' ?>">
                                <?= escape($u['statut']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= $u['derniere_connexion'] ? format_date_fr($u['derniere_connexion'], 'd/m/Y H:i') : 'Jamais' ?></td>
                        <td class="text-end pe-4">
                            <form method="POST" action="" class="d-inline-block">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="id_utilisateur" value="<?= $u['id_utilisateur'] ?>">
                                
                                <select name="role" class="form-select form-select-sm d-inline-block w-auto me-1" onchange="this.form.submit()">
                                    <option value="">Changer rôle...</option>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                    <option value="rh">RH</option>
                                    <option value="manager">Manager</option>
                                    <option value="comptable">Comptable</option>
                                    <option value="employe">Employé</option>
                                </select>
                                <input type="hidden" name="action" value="change_role">
                            </form>

                            <form method="POST" action="" class="d-inline-block">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="id_utilisateur" value="<?= $u['id_utilisateur'] ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                
                                <?php if ($u['statut'] === 'actif'): ?>
                                <button type="submit" name="statut" value="inactif" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-lock me-1"></i> Désactiver
                                </button>
                                <?php else: ?>
                                <button type="submit" name="statut" value="actif" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-lock-open me-1"></i> Activer
                                </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
