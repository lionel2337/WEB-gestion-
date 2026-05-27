<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Employe.php';
require_once ROOT_PATH . '/config/functions.php';

// Vérification simple
if (!isset($_SESSION['user_id'])) {
    json_response(false, "Non autorisé", null, 401);
}

$employeClass = new Employe();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // 12 éléments par page (divisible par 3 ou 4 pour la grille)
$offset = ($page - 1) * $limit;

$view = $_GET['view'] ?? 'table';
$recherche = clean_input($_GET['search'] ?? '');
$sort_col = clean_input($_GET['sort_col'] ?? 'nom');
$sort_dir = clean_input($_GET['sort_dir'] ?? 'ASC');

$filtres = [];
if (!empty($_GET['departement'])) $filtres['id_departement'] = (int)$_GET['departement'];
if (!empty($_GET['statut'])) $filtres['statut_employe'] = clean_input($_GET['statut']);

// Récupération
$total_records = $employeClass->countEmployes($filtres, $recherche);
$employes = $employeClass->getEmployes($filtres, $recherche, $limit, $offset, $sort_col, $sort_dir);
$total_pages = ceil($total_records / $limit);

// Génération du HTML
ob_start();

if (empty($employes)) {
    echo '<div class="text-center py-5">';
    echo '<img src="' . BASE_URL . '/assets/images/no_data.svg" alt="Aucun résultat" style="width: 150px; opacity: 0.5; margin-bottom: 20px;">';
    echo '<h5 class="text-muted">Aucun employé trouvé.</h5>';
    echo '<p class="text-muted small">Essayez de modifier vos filtres ou votre recherche.</p>';
    echo '</div>';
} else {
    if ($view === 'grid') {
        echo '<div class="row g-4">';
        foreach ($employes as $emp) {
            $photo = !empty($emp['photo']) ? BASE_URL . '/assets/uploads/photos/' . $emp['photo'] : BASE_URL . '/assets/images/default_avatar.png';
            $badgeClass = 'bg-success';
            if ($emp['statut_employe'] == 'en_conge') $badgeClass = 'bg-warning text-dark';
            if ($emp['statut_employe'] == 'en_mission') $badgeClass = 'bg-info text-dark';
            if ($emp['statut_employe'] == 'suspendu') $badgeClass = 'bg-danger';

            echo '<div class="col-md-4 col-lg-3">';
            echo '<div class="card h-100 border-0 shadow-sm rounded-4 text-center employee-card overflow-hidden">';
            echo '<div class="card-body pt-4 position-relative">';
            echo '<span class="badge ' . $badgeClass . ' position-absolute top-0 end-0 m-3">' . escape(ucfirst(str_replace('_', ' ', $emp['statut_employe']))) . '</span>';
            echo '<img src="' . $photo . '" class="rounded-circle mb-3 border shadow-sm" width="80" height="80" style="object-fit:cover;">';
            echo '<h6 class="fw-bold mb-1">' . escape($emp['nom'] . ' ' . $emp['prenom']) . '</h6>';
            echo '<p class="text-muted small mb-2">' . escape($emp['matricule']) . '</p>';
            echo '<p class="text-primary small fw-medium mb-1"><i class="fas fa-briefcase me-1"></i> ' . escape($emp['titre_poste'] ?? 'Non assigné') . '</p>';
            echo '<p class="text-muted small mb-3"><i class="fas fa-building me-1"></i> ' . escape($emp['nom_departement'] ?? 'Non assigné') . '</p>';
            echo '</div>';
            echo '<div class="card-footer bg-light border-0 py-3 d-flex justify-content-around">';
            echo '<a href="' . BASE_URL . '/pages/employes/view.php?id=' . $emp['id_employe'] . '" class="btn btn-sm btn-outline-primary rounded-circle" title="Voir"><i class="fas fa-eye"></i></a>';
            echo '<a href="' . BASE_URL . '/pages/employes/edit.php?id=' . $emp['id_employe'] . '" class="btn btn-sm btn-outline-secondary rounded-circle" title="Modifier"><i class="fas fa-edit"></i></a>';
            echo '<button onclick="confirmDelete(' . $emp['id_employe'] . ', \'' . escape(addslashes($emp['nom'] . ' ' . $emp['prenom'])) . '\')" class="btn btn-sm btn-outline-danger rounded-circle" title="Archiver"><i class="fas fa-archive"></i></button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        // Table view
        echo '<div class="table-responsive bg-white rounded-4 shadow-sm border-0">';
        echo '<table class="table table-hover align-middle mb-0">';
        echo '<thead class="table-light">';
        echo '<tr>';
        
        $cols = [
            'matricule' => 'Matricule',
            'nom' => 'Employé',
            'titre_poste' => 'Poste',
            'nom_departement' => 'Département',
            'statut_employe' => 'Statut',
            'date_embauche' => 'Embauche',
            '' => 'Actions'
        ];
        
        foreach ($cols as $key => $label) {
            if ($key !== '') {
                $icon = '';
                if ($sort_col === $key) {
                    $icon = $sort_dir === 'ASC' ? '<i class="fas fa-sort-up ms-1"></i>' : '<i class="fas fa-sort-down ms-1"></i>';
                } else {
                    $icon = '<i class="fas fa-sort text-muted ms-1 opacity-25"></i>';
                }
                echo '<th class="sortable" data-col="' . $key . '" style="cursor:pointer">' . $label . $icon . '</th>';
            } else {
                echo '<th class="text-end">' . $label . '</th>';
            }
        }
        echo '</tr></thead><tbody>';
        
        foreach ($employes as $emp) {
            $photo = !empty($emp['photo']) ? BASE_URL . '/assets/uploads/photos/' . $emp['photo'] : BASE_URL . '/assets/images/default_avatar.png';
            $badgeClass = 'bg-success';
            if ($emp['statut_employe'] == 'en_conge') $badgeClass = 'bg-warning text-dark';
            if ($emp['statut_employe'] == 'en_mission') $badgeClass = 'bg-info text-dark';
            if ($emp['statut_employe'] == 'suspendu') $badgeClass = 'bg-danger';

            echo '<tr>';
            echo '<td><span class="fw-medium text-muted">' . escape($emp['matricule']) . '</span></td>';
            echo '<td>';
            echo '<div class="d-flex align-items-center">';
            echo '<img src="' . $photo . '" class="rounded-circle me-3" width="40" height="40" style="object-fit:cover;">';
            echo '<div><div class="fw-bold text-dark">' . escape($emp['nom'] . ' ' . $emp['prenom']) . '</div><div class="small text-muted">' . escape($emp['email_professionnel']) . '</div></div>';
            echo '</div></td>';
            echo '<td>' . escape($emp['titre_poste'] ?? '-') . '</td>';
            echo '<td>' . escape($emp['nom_departement'] ?? '-') . '</td>';
            echo '<td><span class="badge ' . $badgeClass . '">' . escape(ucfirst(str_replace('_', ' ', $emp['statut_employe']))) . '</span></td>';
            echo '<td>' . format_date_fr($emp['date_embauche']) . '</td>';
            echo '<td class="text-end">';
            echo '<a href="' . BASE_URL . '/pages/employes/view.php?id=' . $emp['id_employe'] . '" class="btn btn-sm btn-light text-primary me-1" title="Voir"><i class="fas fa-eye"></i></a>';
            echo '<a href="' . BASE_URL . '/pages/employes/edit.php?id=' . $emp['id_employe'] . '" class="btn btn-sm btn-light text-secondary me-1" title="Modifier"><i class="fas fa-edit"></i></a>';
            echo '<button onclick="confirmDelete(' . $emp['id_employe'] . ', \'' . escape(addslashes($emp['nom'] . ' ' . $emp['prenom'])) . '\')" class="btn btn-sm btn-light text-danger" title="Archiver"><i class="fas fa-archive"></i></button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
    }
}

$html = ob_get_clean();

json_response(true, "Succès", [
    'html' => $html,
    'total_records' => $total_records,
    'total_pages' => $total_pages,
    'limit' => $limit,
    'offset' => $offset
]);
