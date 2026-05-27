<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Departement.php';
require_once ROOT_PATH . '/classes/Poste.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
if (!Auth::hasRole('manager')) {
    redirect(BASE_URL . '/errors/403.php');
}

$deptClass = new Departement();
$departements = $deptClass->getAll();

include ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Structure de l'Entreprise</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Départements & Postes</li>
            </ol>
        </nav>
    </div>
    <?php if(Auth::hasRole('admin')): ?>
    <button class="btn btn-success" onclick="openDeptModal()">
        <i class="fas fa-plus me-2"></i>Nouveau Département
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Liste des Départements -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                <h6 class="fw-bold mb-0 text-uppercase text-muted small">Départements</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="dept-list">
                    <?php if(empty($departements)): ?>
                        <div class="text-center py-4 text-muted small">Aucun département configuré.</div>
                    <?php else: ?>
                        <?php foreach($departements as $index => $dept): ?>
                            <a href="#" class="list-group-item list-group-item-action border-0 py-3 <?= $index === 0 ? 'active-dept' : '' ?>" 
                               onclick="loadPostes(<?= $dept['id_departement'] ?>, this, '<?= escape(addslashes($dept['nom_departement'])) ?>')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold dept-name"><?= escape($dept['nom_departement']) ?></h6>
                                        <small class="text-muted"><i class="fas fa-users me-1"></i><?= $dept['nb_employes'] ?> employé(s)</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted opacity-50"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails et Liste des Postes -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100" id="postes-container">
            <!-- Rempli en AJAX -->
            <div class="card-body d-flex align-items-center justify-content-center text-muted" style="min-height: 400px;">
                <div class="text-center">
                    <i class="fas fa-sitemap fs-1 mb-3 opacity-25"></i>
                    <p>Sélectionnez un département pour voir ses postes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Département -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="deptModalLabel">Ajouter un Département</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="deptForm">
                    <input type="hidden" id="dept_id" name="id">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="dept_nom" name="nom_departement" required>
                        <label>Nom du département <span class="text-danger">*</span></label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="dept_desc" name="description" style="height: 100px"></textarea>
                        <label>Description</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveDept()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Poste -->
<div class="modal fade" id="posteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="posteModalLabel">Ajouter un Poste</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="posteForm">
                    <input type="hidden" id="poste_id" name="id">
                    <input type="hidden" id="poste_dept_id" name="id_departement">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="poste_titre" name="titre_poste" required>
                        <label>Titre du poste <span class="text-danger">*</span></label>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="poste_niv" name="niveau_hierarchique" value="5" min="1" max="10">
                                <label>Niveau (1-10)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="poste_smin" name="salaire_base_min" step="0.01">
                                <label>Salaire Min</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="poste_smax" name="salaire_base_max" step="0.01">
                                <label>Salaire Max</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-2">
                        <textarea class="form-control" id="poste_desc" name="description" style="height: 80px"></textarea>
                        <label>Description des missions</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary px-4" onclick="savePoste()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<style>
    .active-dept {
        background-color: var(--primary) !important;
        color: white !important;
        border-radius: 8px !important;
        margin: 4px 8px;
    }
    .active-dept .text-muted { color: rgba(255,255,255,0.8) !important; }
    .list-group-item:not(.active-dept):hover {
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 4px 8px;
    }
</style>

<script>
    let currentDeptId = null;
    let currentDeptName = '';
    const isAdmin = <?= Auth::hasRole('admin') ? 'true' : 'false' ?>;

    document.addEventListener("DOMContentLoaded", function() {
        // Charger le premier département par défaut
        const firstDept = document.querySelector('#dept-list .list-group-item');
        if (firstDept) {
            firstDept.click();
        }
    });

    // --- LOGIQUE POSTES ---

    async function loadPostes(deptId, element, deptName) {
        currentDeptId = deptId;
        currentDeptName = deptName;

        // UI active state
        document.querySelectorAll('#dept-list .list-group-item').forEach(el => el.classList.remove('active-dept'));
        element.classList.add('active-dept');

        document.getElementById('postes-container').innerHTML = `
            <div class="card-body d-flex align-items-center justify-content-center text-muted" style="min-height: 400px;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `;

        const res = await Api.get('/ajax/postes/list.php?id_departement=' + deptId);
        
        if (res.success) {
            let html = `
                <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-primary mb-1">${deptName}</h5>
                        <p class="text-muted small mb-0"><i class="fas fa-layer-group me-1"></i>Postes associés</p>
                    </div>
                    ${isAdmin ? `
                        <button class="btn btn-sm btn-outline-primary" onclick="openPosteModal()">
                            <i class="fas fa-plus me-1"></i>Ajouter Poste
                        </button>
                    ` : ''}
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Titre du poste</th>
                                    <th>Niv.</th>
                                    <th>Employés actifs</th>
                                    ${isAdmin ? '<th class="text-end pe-4">Actions</th>' : ''}
                                </tr>
                            </thead>
                            <tbody>
            `;

            if (res.data.length === 0) {
                html += `<tr><td colspan="4" class="text-center py-4 text-muted">Aucun poste dans ce département.</td></tr>`;
            } else {
                res.data.forEach(p => {
                    html += `
                        <tr>
                            <td class="ps-4 fw-medium text-dark">${p.titre_poste}</td>
                            <td><span class="badge bg-secondary">N${p.niveau_hierarchique}</span></td>
                            <td><i class="fas fa-user me-1 text-muted"></i> ${p.nb_employes}</td>
                            ${isAdmin ? `
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-secondary me-1" onclick="editPoste(${p.id_poste}, '${p.titre_poste.replace(/'/g, "\\'")}', ${p.niveau_hierarchique}, ${p.salaire_base_min || "''"}, ${p.salaire_base_max || "''"}, '${(p.description || '').replace(/'/g, "\\'")}')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light text-danger" onclick="deletePoste(${p.id_poste})"><i class="fas fa-trash"></i></button>
                            </td>
                            ` : ''}
                        </tr>
                    `;
                });
            }

            html += `</tbody></table></div></div>`;
            
            // Footer avec action département
            if (isAdmin) {
                html += `
                    <div class="card-footer bg-light border-top text-end py-3 rounded-bottom-4">
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDept(${deptId})"><i class="fas fa-trash me-2"></i>Supprimer ce département</button>
                    </div>
                `;
            }
            
            document.getElementById('postes-container').innerHTML = html;
        } else {
            document.getElementById('postes-container').innerHTML = `<div class="card-body text-center text-danger py-5">${res.message}</div>`;
        }
    }

    // --- MODALS DEPARTEMENT ---
    
    function openDeptModal() {
        document.getElementById('deptForm').reset();
        document.getElementById('dept_id').value = '';
        document.getElementById('deptModalLabel').innerText = 'Ajouter un Département';
        new bootstrap.Modal(document.getElementById('deptModal')).show();
    }

    async function saveDept() {
        const id = document.getElementById('dept_id').value;
        const nom = document.getElementById('dept_nom').value;
        const desc = document.getElementById('dept_desc').value;

        if (!nom) return showToast('error', 'Le nom est requis');

        const endpoint = id ? '/ajax/departements/edit.php' : '/ajax/departements/create.php';
        const res = await Api.post(endpoint, { id: id, nom_departement: nom, description: desc });

        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('deptModal')).hide();
            showToast('success', 'Département enregistré.');
            setTimeout(() => window.location.reload(), 1000); // Reload pour simplifier la maj de la liste
        } else {
            showToast('error', res.message);
        }
    }

    async function deleteDept(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer ce département ? Cela archivera également tous les postes associés.")) {
            const res = await Api.post('/ajax/departements/delete.php', { id: id });
            if (res.success) {
                showToast('success', 'Département supprimé.');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('error', res.message);
            }
        }
    }

    // --- MODALS POSTE ---

    function openPosteModal() {
        if (!currentDeptId) return showToast('error', 'Sélectionnez un département.');
        document.getElementById('posteForm').reset();
        document.getElementById('poste_id').value = '';
        document.getElementById('poste_dept_id').value = currentDeptId;
        document.getElementById('posteModalLabel').innerText = 'Ajouter un Poste';
        new bootstrap.Modal(document.getElementById('posteModal')).show();
    }

    function editPoste(id, titre, niv, smin, smax, desc) {
        document.getElementById('poste_id').value = id;
        document.getElementById('poste_dept_id').value = currentDeptId;
        document.getElementById('poste_titre').value = titre;
        document.getElementById('poste_niv').value = niv;
        document.getElementById('poste_smin').value = smin;
        document.getElementById('poste_smax').value = smax;
        document.getElementById('poste_desc').value = desc;
        document.getElementById('posteModalLabel').innerText = 'Modifier le Poste';
        new bootstrap.Modal(document.getElementById('posteModal')).show();
    }

    async function savePoste() {
        const data = {
            id: document.getElementById('poste_id').value,
            id_departement: document.getElementById('poste_dept_id').value,
            titre_poste: document.getElementById('poste_titre').value,
            niveau_hierarchique: document.getElementById('poste_niv').value,
            salaire_base_min: document.getElementById('poste_smin').value,
            salaire_base_max: document.getElementById('poste_smax').value,
            description: document.getElementById('poste_desc').value
        };

        if (!data.titre_poste) return showToast('error', 'Le titre est requis');

        const endpoint = data.id ? '/ajax/postes/edit.php' : '/ajax/postes/create.php';
        const res = await Api.post(endpoint, data);

        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('posteModal')).hide();
            showToast('success', 'Poste enregistré.');
            // Recharger les postes
            const activeEl = document.querySelector('.active-dept');
            loadPostes(currentDeptId, activeEl, currentDeptName);
        } else {
            showToast('error', res.message);
        }
    }

    async function deletePoste(id) {
        if (confirm("Supprimer ce poste ?")) {
            const res = await Api.post('/ajax/postes/delete.php', { id: id });
            if (res.success) {
                showToast('success', 'Poste supprimé.');
                const activeEl = document.querySelector('.active-dept');
                loadPostes(currentDeptId, activeEl, currentDeptName);
            } else {
                showToast('error', res.message);
            }
        }
    }
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
