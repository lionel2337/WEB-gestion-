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
if (!Auth::hasRole('manager') && $_SESSION['employe_id'] != $_GET['id']) {
    // Seul un manager, RH, admin ou l'employé lui-même peut voir sa fiche
    redirect(BASE_URL . '/errors/403.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect(BASE_URL . '/pages/employes/');

$employeClass = new Employe();
$emp = $employeClass->getById($id);

if (!$emp) {
    set_flash_message('danger', 'Employé introuvable.');
    redirect(BASE_URL . '/pages/employes/');
}

$photo = !empty($emp['photo']) ? BASE_URL . '/assets/uploads/photos/' . $emp['photo'] : BASE_URL . '/assets/images/default_avatar.png';

$badgeClass = 'bg-success';
if ($emp['statut_employe'] == 'en_conge') $badgeClass = 'bg-warning text-dark';
if ($emp['statut_employe'] == 'en_mission') $badgeClass = 'bg-info text-dark';
if ($emp['statut_employe'] == 'suspendu') $badgeClass = 'bg-danger';

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Profil de l'Employé</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/employes/">Employés</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= escape($emp['matricule']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/pages/employes/" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
        <?php if(Auth::hasRole('rh') || Auth::hasRole('admin')): ?>
            <a href="<?= BASE_URL ?>/pages/employes/edit.php?id=<?= $emp['id_employe'] ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Modifier
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne Gauche (Résumé) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 text-center">
            <div class="card-body pt-5 pb-4 position-relative">
                <span class="badge <?= $badgeClass ?> position-absolute top-0 end-0 m-3"><?= escape(ucfirst(str_replace('_', ' ', $emp['statut_employe']))) ?></span>
                
                <img src="<?= $photo ?>" class="rounded-circle mb-3 border shadow-sm" width="150" height="150" style="object-fit:cover;">
                
                <h4 class="fw-bold mb-1"><?= escape($emp['nom'] . ' ' . $emp['prenom']) ?></h4>
                <p class="text-muted mb-2"><?= escape($emp['matricule']) ?></p>
                <p class="text-primary fw-medium mb-1"><i class="fas fa-briefcase me-2"></i><?= escape($emp['titre_poste'] ?? 'Poste non assigné') ?></p>
                <p class="text-muted mb-4"><i class="fas fa-building me-2"></i><?= escape($emp['nom_departement'] ?? 'Département non assigné') ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <a href="mailto:<?= escape($emp['email_professionnel']) ?>" class="btn btn-outline-primary btn-sm rounded-circle" title="Envoyer Email"><i class="fas fa-envelope"></i></a>
                    <a href="tel:<?= escape($emp['telephone_principal']) ?>" class="btn btn-outline-success btn-sm rounded-circle" title="Appeler"><i class="fas fa-phone"></i></a>
                </div>
                
                <hr class="bg-light">
                
                <div class="text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Embauché le</span>
                        <span class="fw-medium small"><?= format_date_fr($emp['date_embauche']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Type de contrat</span>
                        <span class="fw-medium small text-uppercase"><?= escape($emp['type_employe']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Succursale</span>
                        <span class="fw-medium small"><?= escape($emp['nom_succursale']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne Droite (Onglets détaillés) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-medium" id="perso-tab" data-bs-toggle="tab" data-bs-target="#perso" type="button" role="tab"><i class="fas fa-user me-2"></i>Personnel</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-medium" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab"><i class="fas fa-address-book me-2"></i>Contact</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-medium" id="banque-tab" data-bs-toggle="tab" data-bs-target="#banque" type="button" role="tab"><i class="fas fa-university me-2"></i>Banque & Social</button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content" id="myTabContent">
                    
                    <!-- TAB PERSONNEL -->
                    <div class="tab-pane fade show active" id="perso" role="tabpanel">
                        <h6 class="text-uppercase text-muted fw-bold mb-3 small">Informations Générales</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="text-muted small">Date de naissance</label>
                                <div class="fw-medium"><?= format_date_fr($emp['date_naissance']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Lieu de naissance</label>
                                <div class="fw-medium"><?= escape($emp['lieu_naissance'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Sexe</label>
                                <div class="fw-medium"><?= $emp['sexe'] == 'M' ? 'Masculin' : 'Féminin' ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Nationalité</label>
                                <div class="fw-medium"><?= escape($emp['nationalite'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Situation Matrimoniale</label>
                                <div class="fw-medium text-capitalize"><?= escape($emp['situation_matrimoniale'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Niveau d'étude</label>
                                <div class="fw-medium text-uppercase"><?= escape($emp['niveau_etude'] ?: '-') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB CONTACT -->
                    <div class="tab-pane fade" id="contact" role="tabpanel">
                        <h6 class="text-uppercase text-muted fw-bold mb-3 small">Coordonnées</h6>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="text-muted small">Email Pro</label>
                                <div class="fw-medium"><a href="mailto:<?= escape($emp['email_professionnel']) ?>"><?= escape($emp['email_professionnel']) ?></a></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Email Perso</label>
                                <div class="fw-medium"><?= escape($emp['email_personnel'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Téléphone Principal</label>
                                <div class="fw-medium"><?= escape($emp['telephone_principal'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Ville & Pays</label>
                                <div class="fw-medium"><?= escape($emp['ville_residence'] ?: '-') ?> <?= escape($emp['pays_residence_nom'] ? ', ' . $emp['pays_residence_nom'] : '') ?></div>
                            </div>
                            <div class="col-sm-12">
                                <label class="text-muted small">Adresse Domicile</label>
                                <div class="fw-medium"><?= escape($emp['adresse_domicile'] ?: '-') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB BANQUE & SOCIAL -->
                    <div class="tab-pane fade" id="banque" role="tabpanel">
                        <h6 class="text-uppercase text-muted fw-bold mb-3 small">Informations Bancaires</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="text-muted small">Banque</label>
                                <div class="fw-medium"><?= escape($emp['banque'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Numéro de Compte</label>
                                <div class="fw-medium font-monospace"><?= escape($emp['numero_compte_bancaire'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Mode de Paiement Préféré</label>
                                <div class="fw-medium text-capitalize"><?= escape(str_replace('_', ' ', $emp['mode_paiement_prefere'] ?: '-')) ?></div>
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted fw-bold mb-3 small">Sécurité Sociale & Santé</h6>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="text-muted small">Numéro CNPS</label>
                                <div class="fw-medium"><?= escape($emp['numero_cnps'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small">Groupe Sanguin</label>
                                <div class="fw-medium text-danger fw-bold"><?= escape($emp['groupe_sanguin'] ?: '-') ?></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
