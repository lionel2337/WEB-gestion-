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
if (!Auth::hasRole('rh') && !Auth::hasRole('manager')) {
    redirect(BASE_URL . '/errors/403.php');
}

$db = Database::getInstance()->getConnection();
$employeClass = new Employe();
$matricule_genere = $employeClass->genererMatricule();

// Récupération des données pour les listes déroulantes
$departements = $db->query("SELECT id_departement, nom_departement FROM departements WHERE est_supprime = 0 ORDER BY nom_departement")->fetchAll();
$succursales = $db->query("SELECT id_succursale, nom_succursale FROM succursales WHERE est_supprime = 0 ORDER BY nom_succursale")->fetchAll();
$pays = $db->query("SELECT id_pays, nom_pays FROM pays WHERE est_supprime = 0 ORDER BY nom_pays")->fetchAll();

// Traitement du formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', "Erreur de sécurité CSRF.");
    } else {
        // Nettoyage et préparation des données
        $data = [
            'matricule' => clean_input($_POST['matricule']),
            'nom' => clean_input($_POST['nom']),
            'prenom' => clean_input($_POST['prenom']),
            'date_naissance' => clean_input($_POST['date_naissance']),
            'lieu_naissance' => clean_input($_POST['lieu_naissance']),
            'sexe' => clean_input($_POST['sexe']),
            'nationalite' => clean_input($_POST['nationalite']),
            'situation_matrimoniale' => clean_input($_POST['situation_matrimoniale']),
            
            'adresse_domicile' => clean_input($_POST['adresse_domicile']),
            'ville_residence' => clean_input($_POST['ville_residence']),
            'pays_residence' => !empty($_POST['pays_residence']) ? (int)$_POST['pays_residence'] : null,
            'telephone_principal' => clean_input($_POST['telephone_principal']),
            'email_personnel' => clean_input($_POST['email_personnel']),
            'email_professionnel' => clean_input($_POST['email_professionnel']),
            
            'id_departement' => !empty($_POST['id_departement']) ? (int)$_POST['id_departement'] : null,
            'id_poste' => !empty($_POST['id_poste']) ? (int)$_POST['id_poste'] : null,
            'id_succursale' => !empty($_POST['id_succursale']) ? (int)$_POST['id_succursale'] : null,
            'date_embauche' => clean_input($_POST['date_embauche']),
            'type_employe' => clean_input($_POST['type_employe']),
            'statut_employe' => 'actif',
            
            'banque' => clean_input($_POST['banque']),
            'numero_compte_bancaire' => clean_input($_POST['numero_compte_bancaire']),
            'mode_paiement_prefere' => clean_input($_POST['mode_paiement_prefere']),
            
            'numero_cnps' => clean_input($_POST['numero_cnps']),
            'groupe_sanguin' => clean_input($_POST['groupe_sanguin']),
            'niveau_etude' => clean_input($_POST['niveau_etude']),
        ];
        
        // Upload Photo (Simplifié pour le moment, à intégrer selon la demande de la Phase 3)
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('emp_') . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], ASSETS_PATH . '/uploads/photos/' . $filename)) {
                $data['photo'] = $filename;
            }
        }

        $result = $employeClass->creer($data);
        
        if ($result['success']) {
            set_flash_message('success', "Employé ajouté avec succès. Matricule: " . $result['matricule']);
            redirect(BASE_URL . '/pages/employes/index.php');
        } else {
            set_flash_message('danger', "Erreur lors de l'enregistrement : " . $result['message']);
        }
    }
}

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Ajouter un Employé</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/employes/">Employés</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouveau</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/pages/employes/" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <!-- Progress Bar (Wizard) -->
        <div class="position-relative wizard-progress mb-4 px-3">
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 16%;" id="wizard-progress-bar"></div>
            </div>
            <div class="d-flex justify-content-between position-absolute top-0 w-100 px-3" style="margin-top: -12px; left: 0;">
                <button class="btn btn-sm btn-success rounded-circle wizard-step-btn active" data-step="1" style="width: 30px; height: 30px;">1</button>
                <button class="btn btn-sm btn-secondary rounded-circle wizard-step-btn" data-step="2" style="width: 30px; height: 30px;">2</button>
                <button class="btn btn-sm btn-secondary rounded-circle wizard-step-btn" data-step="3" style="width: 30px; height: 30px;">3</button>
                <button class="btn btn-sm btn-secondary rounded-circle wizard-step-btn" data-step="4" style="width: 30px; height: 30px;">4</button>
                <button class="btn btn-sm btn-secondary rounded-circle wizard-step-btn" data-step="5" style="width: 30px; height: 30px;">5</button>
                <button class="btn btn-sm btn-secondary rounded-circle wizard-step-btn" data-step="6" style="width: 30px; height: 30px;">6</button>
            </div>
        </div>
        <div class="text-center mb-4">
            <h5 class="fw-bold text-primary" id="step-title">Informations Personnelles</h5>
            <p class="text-muted small" id="step-desc">Veuillez renseigner les informations d'identité de l'employé.</p>
        </div>
    </div>

    <div class="card-body p-4 p-md-5">
        <form id="employee-form" method="POST" action="create.php" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
            
            <!-- ÉTAPE 1 : Informations Personnelles -->
            <div class="wizard-step" id="step-1">
                <div class="row g-4">
                    <div class="col-md-3 text-center">
                        <div class="mb-3">
                            <img src="<?= BASE_URL ?>/assets/images/default_avatar.png" id="photo-preview" class="rounded-circle img-thumbnail shadow-sm mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                            <br>
                            <label for="photo" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-camera me-1"></i> Choisir une photo
                            </label>
                            <input type="file" id="photo" name="photo" class="d-none" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control bg-light" id="matricule" name="matricule" value="<?= escape($matricule_genere) ?>" readonly>
                            <label for="matricule">Matricule Automatique</label>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                    <label for="nom">Nom de famille <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                                    <label for="prenom">Prénom(s) <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                                    <label for="date_naissance">Date de naissance <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance">
                                    <label for="lieu_naissance">Lieu de naissance</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="sexe" name="sexe" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="M">Masculin</option>
                                        <option value="F">Féminin</option>
                                    </select>
                                    <label for="sexe">Sexe <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="situation_matrimoniale" name="situation_matrimoniale">
                                        <option value="celibataire">Célibataire</option>
                                        <option value="marie">Marié(e)</option>
                                        <option value="divorce">Divorcé(e)</option>
                                        <option value="veuf">Veuf/Veuve</option>
                                    </select>
                                    <label for="situation_matrimoniale">Statut matrimonial</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nationalite" name="nationalite" value="Camerounaise">
                                    <label for="nationalite">Nationalité</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 2 : Coordonnées -->
            <div class="wizard-step d-none" id="step-2">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email_professionnel" name="email_professionnel" required>
                            <label for="email_professionnel">Email Professionnel <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email_personnel" name="email_personnel">
                            <label for="email_personnel">Email Personnel</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="tel" class="form-control" id="telephone_principal" name="telephone_principal" required>
                            <label for="telephone_principal">Téléphone Principal <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="pays_residence" name="pays_residence">
                                <?php foreach ($pays as $p): ?>
                                    <option value="<?= $p['id_pays'] ?>" <?= $p['nom_pays'] == 'Cameroun' ? 'selected' : '' ?>><?= escape($p['nom_pays']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="pays_residence">Pays de résidence</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="ville_residence" name="ville_residence">
                            <label for="ville_residence">Ville</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="adresse_domicile" name="adresse_domicile">
                            <label for="adresse_domicile">Adresse (Quartier, Rue)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 3 : Informations Professionnelles -->
            <div class="wizard-step d-none" id="step-3">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="id_succursale" name="id_succursale" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($succursales as $suc): ?>
                                    <option value="<?= $suc['id_succursale'] ?>"><?= escape($suc['nom_succursale']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="id_succursale">Succursale <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="id_departement" name="id_departement" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($departements as $dept): ?>
                                    <option value="<?= $dept['id_departement'] ?>"><?= escape($dept['nom_departement']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="id_departement">Département <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="id_poste" name="id_poste" required disabled>
                                <option value="">Sélectionner d'abord un département</option>
                            </select>
                            <label for="id_poste">Poste <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="type_employe" name="type_employe" required>
                                <option value="permanent">Permanent (CDI)</option>
                                <option value="contractuel">Contractuel (CDD)</option>
                                <option value="stagiaire">Stagiaire</option>
                                <option value="consultant">Consultant</option>
                            </select>
                            <label for="type_employe">Type de contrat <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="date_embauche" name="date_embauche" required>
                            <label for="date_embauche">Date d'embauche <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 4 : Banque -->
            <div class="wizard-step d-none" id="step-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="mode_paiement_prefere" name="mode_paiement_prefere">
                                <option value="virement">Virement Bancaire</option>
                                <option value="mobile_money">Mobile Money (Momo/OM)</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                            </select>
                            <label for="mode_paiement_prefere">Mode de paiement préféré</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="banque" name="banque">
                            <label for="banque">Nom de la Banque</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="numero_compte_bancaire" name="numero_compte_bancaire">
                            <label for="numero_compte_bancaire">Numéro de Compte (RIB/IBAN)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 5 : Informations Sociales -->
            <div class="wizard-step d-none" id="step-5">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="numero_cnps" name="numero_cnps">
                            <label for="numero_cnps">Numéro Sécurité Sociale (CNPS)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="groupe_sanguin" name="groupe_sanguin">
                                <option value="">Inconnu</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                            <label for="groupe_sanguin">Groupe Sanguin</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 6 : Documents & Finalisation -->
            <div class="wizard-step d-none" id="step-6">
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <select class="form-select" id="niveau_etude" name="niveau_etude">
                                <option value="BAC">BAC</option>
                                <option value="BTS_DUT">BTS / DUT</option>
                                <option value="LICENCE">Licence</option>
                                <option value="MASTER">Master / Ingénieur</option>
                                <option value="DOCTORAT">Doctorat</option>
                                <option value="AUTRE">Autre</option>
                            </select>
                            <label for="niveau_etude">Niveau d'étude le plus élevé</label>
                        </div>
                    </div>
                    <div class="col-md-12 text-center py-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">Tout est prêt !</h4>
                        <p class="text-muted">Vous êtes sur le point de créer un nouvel employé. Veuillez vérifier les informations avant de valider.</p>
                    </div>
                </div>
            </div>

            <!-- Contrôles du Wizard -->
            <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary px-4" id="btn-prev" style="display: none;">
                    <i class="fas fa-arrow-left me-2"></i>Précédent
                </button>
                <button type="button" class="btn btn-primary px-4 ms-auto" id="btn-next">
                    Suivant<i class="fas fa-arrow-right ms-2"></i>
                </button>
                <button type="submit" class="btn btn-success px-5 ms-auto" id="btn-submit" style="display: none;">
                    <i class="fas fa-save me-2"></i>Enregistrer l'employé
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentStep = 1;
    const totalSteps = 6;
    
    const stepTitles = [
        "Informations Personnelles",
        "Coordonnées",
        "Informations Professionnelles",
        "Informations Bancaires",
        "Informations Sociales",
        "Finalisation"
    ];

    document.addEventListener("DOMContentLoaded", function() {
        
        // Chargement dynamique des postes selon le département
        document.getElementById('id_departement').addEventListener('change', async function() {
            const deptId = this.value;
            const posteSelect = document.getElementById('id_poste');
            
            if (!deptId) {
                posteSelect.innerHTML = '<option value="">Sélectionner d\'abord un département</option>';
                posteSelect.disabled = true;
                return;
            }
            
            posteSelect.innerHTML = '<option value="">Chargement...</option>';
            posteSelect.disabled = true;
            
            const res = await Api.get('/ajax/common/get_postes.php?id_departement=' + deptId);
            
            if (res.success) {
                let html = '<option value="">Sélectionner un poste...</option>';
                res.data.forEach(p => {
                    html += `<option value="${p.id_poste}">${p.titre_poste}</option>`;
                });
                posteSelect.innerHTML = html;
                posteSelect.disabled = false;
            } else {
                posteSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            }
        });

        // Boutons de navigation
        document.getElementById('btn-next').addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    goToStep(currentStep + 1);
                }
            }
        });

        document.getElementById('btn-prev').addEventListener('click', function() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        });
    });

    function goToStep(step) {
        // Cacher tous les steps
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('d-none'));
        
        // Afficher le step cible
        document.getElementById('step-' + step).classList.remove('d-none');
        
        // Mettre à jour l'UI
        currentStep = step;
        
        // Progress bar
        const percent = ((step - 1) / (totalSteps - 1)) * 100;
        document.getElementById('wizard-progress-bar').style.width = percent + '%';
        
        // Cercles
        document.querySelectorAll('.wizard-step-btn').forEach(el => {
            if (parseInt(el.dataset.step) <= step) {
                el.classList.remove('btn-secondary');
                el.classList.add('btn-success');
            } else {
                el.classList.remove('btn-success');
                el.classList.add('btn-secondary');
            }
        });
        
        // Titre
        document.getElementById('step-title').innerText = stepTitles[step - 1];

        // Boutons
        document.getElementById('btn-prev').style.display = step === 1 ? 'none' : 'inline-block';
        
        if (step === totalSteps) {
            document.getElementById('btn-next').style.display = 'none';
            document.getElementById('btn-submit').style.display = 'inline-block';
        } else {
            document.getElementById('btn-next').style.display = 'inline-block';
            document.getElementById('btn-submit').style.display = 'none';
        }
    }

    function validateStep(step) {
        let valid = true;
        const form = document.getElementById('employee-form');
        const stepContainer = document.getElementById('step-' + step);
        const requiredInputs = stepContainer.querySelectorAll('[required]');
        
        requiredInputs.forEach(input => {
            if (!input.value) {
                input.classList.add('is-invalid');
                valid = false;
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });
        
        if (!valid) {
            showToast('error', 'Veuillez remplir tous les champs obligatoires.');
        }
        
        return valid;
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photo-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
