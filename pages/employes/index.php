<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

// Vérification des droits
if (!Auth::hasRole('manager')) {
    redirect(BASE_URL . '/errors/403.php');
}

// Récupération des filtres pour les dropdowns
$db = Database::getInstance()->getConnection();
$departements = $db->query("SELECT id_departement, nom_departement FROM departements WHERE est_supprime = 0 ORDER BY nom_departement")->fetchAll();

include ROOT_PATH . '/includes/header.php';
?>

<!-- En-tête de la page -->
<div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
    <div>
        <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Gestion des Employés</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Employés</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/pages/employes/export_xml.php" class="btn btn-outline-secondary">
            <i class="fas fa-file-code me-2"></i>XML
        </a>
        <a href="#" class="btn btn-outline-danger" onclick="alert('Export PDF en cours de développement')">
            <i class="fas fa-file-pdf me-2"></i>PDF
        </a>
        <a href="<?= BASE_URL ?>/pages/employes/create.php" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Nouvel Employé
        </a>
    </div>
</div>

<!-- Barre d'outils et Filtres -->
<div class="card border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeInUp">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <!-- Recherche -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="search-input" class="form-control bg-light border-start-0" placeholder="Rechercher nom, matricule, email...">
                </div>
            </div>
            
            <!-- Filtre Département -->
            <div class="col-md-3">
                <select id="filter-departement" class="form-select bg-light">
                    <option value="">Tous les départements</option>
                    <?php foreach ($departements as $dept): ?>
                        <option value="<?= $dept['id_departement'] ?>"><?= escape($dept['nom_departement']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtre Statut -->
            <div class="col-md-3">
                <select id="filter-statut" class="form-select bg-light">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="en_conge">En congé</option>
                    <option value="en_mission">En mission</option>
                    <option value="suspendu">Suspendu</option>
                </select>
            </div>
            
            <!-- Toggle Vue -->
            <div class="col-md-2 text-end">
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check view-toggle" name="viewType" id="viewTable" value="table" autocomplete="off" checked>
                    <label class="btn btn-outline-primary px-3" for="viewTable" title="Vue Tableau"><i class="fas fa-list"></i></label>

                    <input type="radio" class="btn-check view-toggle" name="viewType" id="viewGrid" value="grid" autocomplete="off">
                    <label class="btn btn-outline-primary px-3" for="viewGrid" title="Vue Grille"><i class="fas fa-th-large"></i></label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Conteneur pour le loader -->
<div id="loader" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Chargement...</span>
    </div>
    <p class="mt-2 text-muted">Chargement des données...</p>
</div>

<!-- Conteneur pour les résultats (Tableau ou Grille) -->
<div id="results-container" class="animate__animated animate__fadeIn">
    <!-- Le contenu sera injecté ici par AJAX -->
</div>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-4 mb-5" id="pagination-container" style="display: none !important;">
    <div class="text-muted small" id="pagination-info">
        Affichage de 0 à 0 sur 0 employés
    </div>
    <nav>
        <ul class="pagination pagination-sm mb-0" id="pagination-links">
            <!-- Les liens seront injectés ici -->
        </ul>
    </nav>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pb-4">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem; animation: pulse 2s infinite;"></i>
                </div>
                <h4 class="fw-bold mb-3">Archiver cet employé ?</h4>
                <p class="text-muted mb-4">L'employé <strong id="delete-employe-nom"></strong> sera déplacé vers les archives et n'aura plus accès au système.</p>
                
                <form id="deleteForm">
                    <input type="hidden" id="delete-id">
                    <div class="form-floating mb-3 text-start">
                        <select class="form-select" id="delete-motif" required>
                            <option value="">Sélectionner un motif...</option>
                            <option value="demission">Démission</option>
                            <option value="licenciement">Licenciement</option>
                            <option value="fin_contrat">Fin de contrat</option>
                            <option value="retraite">Retraite</option>
                            <option value="autre">Autre</option>
                        </select>
                        <label for="delete-motif">Motif de départ</label>
                    </div>
                    <div class="form-floating mb-4 text-start">
                        <textarea class="form-control" id="delete-commentaire" style="height: 80px"></textarea>
                        <label for="delete-commentaire">Commentaire optionnel</label>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger px-4" id="btn-confirm-delete">
                            <i class="fas fa-archive me-2"></i>Confirmer l'archivage
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>

<script>
    let currentPage = 1;
    let currentView = 'table';
    let sortCol = 'nom';
    let sortDir = 'ASC';
    let searchTimer = null;

    document.addEventListener("DOMContentLoaded", function() {
        loadEmployes();

        // Écouteur pour la recherche avec Debounce (300ms)
        document.getElementById('search-input').addEventListener('keyup', function(e) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentPage = 1;
                loadEmployes();
            }, 300);
        });

        // Écouteurs pour les filtres
        document.getElementById('filter-departement').addEventListener('change', () => { currentPage = 1; loadEmployes(); });
        document.getElementById('filter-statut').addEventListener('change', () => { currentPage = 1; loadEmployes(); });

        // Écouteur pour le toggle de vue
        const viewToggles = document.querySelectorAll('.view-toggle');
        viewToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                currentView = this.value;
                loadEmployes();
            });
        });

        // Formulaire de suppression
        document.getElementById('deleteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('delete-id').value;
            const motif = document.getElementById('delete-motif').value;
            const comm = document.getElementById('delete-commentaire').value;
            
            const btn = document.getElementById('btn-confirm-delete');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Archivage...';
            btn.disabled = true;

            const response = await Api.post('/ajax/employes/delete.php', {
                id_employe: id,
                motif_depart: motif,
                commentaire: comm
            });

            btn.innerHTML = originalText;
            btn.disabled = false;

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                showToast('success', 'Employé archivé avec succès.');
                loadEmployes(); // Recharger la liste
            } else {
                showToast('error', response.message || 'Erreur lors de l\'archivage.');
            }
        });
    });

    async function loadEmployes() {
        document.getElementById('loader').style.display = 'block';
        document.getElementById('results-container').style.display = 'none';
        document.getElementById('pagination-container').style.display = 'none';

        const params = new URLSearchParams({
            page: currentPage,
            view: currentView,
            search: document.getElementById('search-input').value,
            departement: document.getElementById('filter-departement').value,
            statut: document.getElementById('filter-statut').value,
            sort_col: sortCol,
            sort_dir: sortDir
        });

        try {
            // Note: Api.get retourne du JSON contenant le HTML pré-rendu ou les données brutes. 
            // Ici le script PHP retournera le HTML dans response.data.html
            const response = await Api.get('/ajax/employes/search.php?' + params.toString());
            
            document.getElementById('loader').style.display = 'none';
            document.getElementById('results-container').style.display = 'block';
            
            if (response.success) {
                document.getElementById('results-container').innerHTML = response.data.html;
                
                // Mettre à jour la pagination
                if (response.data.total_pages > 0) {
                    document.getElementById('pagination-container').style.display = 'flex';
                    document.getElementById('pagination-info').innerText = 
                        `Affichage de ${response.data.offset + 1} à ${Math.min(response.data.offset + response.data.limit, response.data.total_records)} sur ${response.data.total_records} employés`;
                    
                    renderPagination(response.data.total_pages);
                    bindSortEvents();
                }
            } else {
                showToast('error', response.message);
            }
        } catch (error) {
            console.error(error);
            document.getElementById('loader').style.display = 'none';
            showToast('error', 'Erreur réseau.');
        }
    }

    function renderPagination(totalPages) {
        let html = '';
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Précédent</a></li>`;
        
        for (let i = 1; i <= totalPages; i++) {
            // Logique basique pour ne pas tout afficher si > 10 pages
            if (totalPages > 7) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a></li>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            } else {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a></li>`;
            }
        }
        
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Suivant</a></li>`;
        document.getElementById('pagination-links').innerHTML = html;
    }

    function changePage(page) {
        currentPage = page;
        loadEmployes();
    }

    function handleSort(col) {
        if (sortCol === col) {
            sortDir = sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            sortCol = col;
            sortDir = 'ASC';
        }
        currentPage = 1;
        loadEmployes();
    }

    function bindSortEvents() {
        document.querySelectorAll('th.sortable').forEach(th => {
            th.addEventListener('click', function() {
                handleSort(this.dataset.col);
            });
        });
    }

    function confirmDelete(id, nom) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-employe-nom').innerText = nom;
        document.getElementById('delete-motif').value = '';
        document.getElementById('delete-commentaire').value = '';
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
