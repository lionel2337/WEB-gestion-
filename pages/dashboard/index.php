<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

// On inclut le header global qui contient déjà l'ouverture HTML, le tag <body>, le wrapper et la top-navbar
include ROOT_PATH . '/includes/header.php';
?>

<!-- En-tête de la page -->
<div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
    <div>
        <h2 class="h4 mb-0 text-gray-800 font-poppins fw-bold text-primary">Tableau de bord</h2>
        <p class="text-muted mb-0">Bienvenue, <?= escape($_SESSION['username']) ?> ! Voici l'aperçu de votre entreprise.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="refreshDashboard()"><i class="fas fa-sync-alt me-2"></i>Rafraîchir</button>
    </div>
</div>

<!-- Section des KPIs (Cartes statistiques) -->
<div class="row g-4 mb-4 animate__animated animate__fadeInUp">
    <!-- Total Employés -->
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold mb-0 text-uppercase text-truncate">Total Employés</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" id="stat-total-employes">--</h3>
                <p class="mb-0 text-success small"><i class="fas fa-arrow-up me-1"></i> <span id="stat-employes-trend">+0%</span> depuis le mois dernier</p>
                <div class="position-absolute bottom-0 start-0 w-100" style="height: 4px; background: var(--primary);"></div>
            </div>
        </div>
    </div>

    <!-- En Congé -->
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold mb-0 text-uppercase text-truncate">En Congé</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-umbrella-beach fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" id="stat-en-conge">--</h3>
                <p class="mb-0 text-muted small">Actuellement hors bureau</p>
                <div class="position-absolute bottom-0 start-0 w-100" style="height: 4px; background: var(--accent);"></div>
            </div>
        </div>
    </div>

    <!-- En Mission -->
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold mb-0 text-uppercase text-truncate">En Mission</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-plane fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" id="stat-en-mission">--</h3>
                <p class="mb-0 text-muted small">Employés en déplacement</p>
                <div class="position-absolute bottom-0 start-0 w-100" style="height: 4px; background: #0dcaf0;"></div>
            </div>
        </div>
    </div>

    <!-- Alertes (Congés en attente, etc.) -->
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold mb-0 text-uppercase text-truncate">Congés en attente</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-clock fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" id="stat-conges-attente">--</h3>
                <p class="mb-0 text-danger small"><i class="fas fa-exclamation-circle me-1"></i> À valider rapidement</p>
                <div class="position-absolute bottom-0 start-0 w-100" style="height: 4px; background: var(--warning);"></div>
            </div>
        </div>
    </div>
</div>

<!-- Section Graphiques -->
<div class="row g-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
    <!-- Répartition par département -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-chart-bar me-2"></i>Effectifs par Département</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px; width: 100%; display: flex; align-items: center; justify-content: center;" id="chart-departements-container">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Répartition H/F -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-chart-pie me-2"></i>Répartition Genre</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="height: 250px; width: 100%; display: flex; align-items: center; justify-content: center;" id="chart-genre-container">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script spécifique pour cette page (ChartJS) -->
<script>
    let chartDepartements = null;
    let chartGenre = null;

    document.addEventListener("DOMContentLoaded", function() {
        refreshDashboard();
    });

    async function refreshDashboard() {
        try {
            // Appel AJAX via notre utilitaire fetch (ajax.js)
            const response = await Api.get('/ajax/dashboard/get_stats.php');
            
            if (response.success) {
                const data = response.data;
                
                // Mise à jour des compteurs avec animation
                animateValue("stat-total-employes", 0, data.total_employes, 1000);
                animateValue("stat-en-conge", 0, data.en_conge, 1000);
                animateValue("stat-en-mission", 0, data.en_mission, 1000);
                animateValue("stat-conges-attente", 0, data.conges_attente, 1000);

                // Initialisation des graphiques
                initCharts(data.graphiques);
            } else {
                showToast('error', response.message || 'Erreur lors du chargement des statistiques.');
            }
        } catch (error) {
            console.error("Erreur de chargement du dashboard:", error);
            showToast('error', 'Erreur serveur.');
        }
    }

    function animateValue(id, start, end, duration) {
        if (start === end) return;
        let range = end - start;
        let current = start;
        let increment = end > start ? 1 : -1;
        let stepTime = Math.abs(Math.floor(duration / range));
        let obj = document.getElementById(id);
        
        // Sécurité pour ne pas bloquer si range est très grand
        if (stepTime < 5) stepTime = 5; 
        
        let timer = setInterval(function() {
            current += increment;
            obj.innerHTML = current;
            if (current == end) {
                clearInterval(timer);
            }
        }, stepTime);
    }

    function initCharts(data) {
        // Paramètres de couleurs
        const bgColors = [
            'rgba(44, 123, 229, 0.8)', // Primary
            'rgba(0, 217, 126, 0.8)',  // Accent/Success
            'rgba(246, 195, 67, 0.8)', // Warning
            'rgba(230, 55, 87, 0.8)',  // Danger
            'rgba(13, 202, 240, 0.8)'  // Info
        ];
        
        // 1. Graphique Départements (Bar)
        const containerDept = document.getElementById('chart-departements-container');
        containerDept.innerHTML = '<canvas id="chartDept"></canvas>';
        const ctxDept = document.getElementById('chartDept').getContext('2d');
        
        if (chartDepartements) chartDepartements.destroy();
        
        chartDepartements = new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: data.departements.labels,
                datasets: [{
                    label: 'Nombre d\'employés',
                    data: data.departements.values,
                    backgroundColor: bgColors[0],
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Graphique Genre (Doughnut)
        const containerGenre = document.getElementById('chart-genre-container');
        containerGenre.innerHTML = '<canvas id="chartGenre"></canvas>';
        const ctxGenre = document.getElementById('chartGenre').getContext('2d');
        
        if (chartGenre) chartGenre.destroy();
        
        chartGenre = new Chart(ctxGenre, {
            type: 'doughnut',
            data: {
                labels: ['Hommes', 'Femmes'],
                datasets: [{
                    data: [data.genre.hommes, data.genre.femmes],
                    backgroundColor: [bgColors[0], bgColors[3]],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
</script>

<?php 
// Inclusion du footer global
include ROOT_PATH . '/includes/footer.php'; 
?>
