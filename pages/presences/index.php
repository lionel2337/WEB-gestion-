<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Presence.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();

$presenceClass = new Presence();
$id_emp = $_SESSION['employe_id'];

$error = null;
$success = null;

// Gérer les actions de pointage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide.";
    } else {
        $action = clean_input($_POST['action']);
        if ($action === 'clock_in') {
            $lat = !empty($_POST['lat']) ? clean_input($_POST['lat']) : null;
            $lng = !empty($_POST['lng']) ? clean_input($_POST['lng']) : null;
            $res = $presenceClass->clockIn($id_emp, $lat, $lng);
            if ($res['success']) {
                $success = "Pointage d'arrivée enregistré à " . $res['heure'] . " !";
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'clock_out') {
            $res = $presenceClass->clockOut($id_emp);
            if ($res['success']) {
                $success = "Pointage de départ enregistré à " . $res['heure'] . " (Heures travaillées: " . $res['heures_travaillees'] . "h) !";
            } else {
                $error = $res['message'];
            }
        }
    }
}

// État de pointage de l'employé aujourd'hui
$today_state = $presenceClass->getTodayState($id_emp);

// Historique des pointages du mois en cours
$presences = $presenceClass->getPresences($id_emp, date('m'), date('Y'));

include ROOT_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="h4 mb-0 text-primary font-poppins fw-bold">Pointage & Présences</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active" aria-current="page">Présences</li>
        </ol>
    </nav>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Card de Pointage -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                <h5 class="fw-bold text-muted small text-uppercase mb-3">Horloge de pointage</h5>
                <h2 class="fw-bold text-primary font-poppins mb-1" id="live-time">00:00:00</h2>
                <p class="text-muted small mb-4" id="live-date"><?= format_date_fr(date('Y-m-d'), 'l d F Y') ?></p>

                <!-- État de pointage actuel -->
                <div class="mb-4">
                    <?php if (!$today_state): ?>
                    <span class="badge bg-secondary px-3 py-2 fs-7 rounded-pill">Non pointé (En attente d'arrivée)</span>
                    <?php elseif ($today_state['heure_depart'] === null): ?>
                    <span class="badge bg-success px-3 py-2 fs-7 rounded-pill">Présent (Arrivé à <?= $today_state['heure_arrivee'] ?>)</span>
                    <?php else: ?>
                    <span class="badge bg-info px-3 py-2 fs-7 rounded-pill">Journée terminée (Départ à <?= $today_state['heure_depart'] ?>)</span>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" id="clock-form">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" id="action-input" value="">
                    <input type="hidden" name="lat" id="lat-input" value="">
                    <input type="hidden" name="lng" id="lng-input" value="">

                    <div class="d-grid gap-2">
                        <?php if (!$today_state): ?>
                        <button type="button" class="btn btn-primary btn-lg rounded-3 py-3" onclick="submitClock('clock_in')">
                            <i class="fas fa-sign-in-alt me-2"></i> Pointer l'Arrivée
                        </button>
                        <?php elseif ($today_state['heure_depart'] === null): ?>
                        <button type="button" class="btn btn-danger btn-lg rounded-3 py-3" onclick="submitClock('clock_out')">
                            <i class="fas fa-sign-out-alt me-2"></i> Pointer le Départ
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg rounded-3 py-3" disabled>
                            <i class="fas fa-check-circle me-2"></i> Journée Complétée
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Rapides -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
                <h5 class="fw-bold text-dark font-poppins mb-0">Statistiques du Mois</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php
                    $presents = 0;
                    $retards = 0;
                    $total_heures = 0;
                    foreach ($presences as $p) {
                        if ($p['statut'] === 'present' || $p['statut'] === 'retard') $presents++;
                        if ($p['statut'] === 'retard') $retards++;
                        $total_heures += $p['heures_travaillees'];
                    }
                    ?>
                    <div class="col-6 col-md-4">
                        <div class="border rounded-4 p-3 bg-light text-center">
                            <h6 class="text-muted small mb-1">Jours Présents</h6>
                            <h3 class="fw-bold text-success mb-0"><?= $presents ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded-4 p-3 bg-light text-center">
                            <h6 class="text-muted small mb-1">Retards Enregistrés</h6>
                            <h3 class="fw-bold text-danger mb-0"><?= $retards ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-4 p-3 bg-light text-center">
                            <h6 class="text-muted small mb-1">Heures Travaillées</h6>
                            <h3 class="fw-bold text-primary mb-0"><?= $total_heures ?>h</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Historique des pointages -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
        <h5 class="fw-bold text-dark font-poppins mb-0">Historique des Pointages de ce Mois</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Arrivée</th>
                        <th>Départ</th>
                        <th>Heures</th>
                        <th>Sup.</th>
                        <th>Retard</th>
                        <th class="text-end pe-4">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($presences)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-user-clock fs-1 mb-3 opacity-25"></i>
                            <p class="mb-0">Aucun pointage ce mois-ci.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($presences as $p): ?>
                    <tr>
                        <td class="ps-4 fw-medium text-dark"><?= format_date_fr($p['date_presence']) ?></td>
                        <td class="fw-bold text-success"><?= $p['heure_arrivee'] ?: '-' ?></td>
                        <td class="fw-bold text-danger"><?= $p['heure_depart'] ?: '-' ?></td>
                        <td><?= $p['heures_travaillees'] ? $p['heures_travaillees'] . 'h' : '-' ?></td>
                        <td><?= $p['heures_supplementaires'] ? '+' . $p['heures_supplementaires'] . 'h' : '-' ?></td>
                        <td class="<?= $p['est_en_retard'] ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= $p['est_en_retard'] ? $p['retard_minutes'] . ' min' : 'Aucun' ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php
                            $bg = 'success';
                            if ($p['statut'] === 'retard') $bg = 'warning';
                            elseif ($p['statut'] === 'absent_non_justifie') $bg = 'danger';
                            ?>
                            <span class="badge bg-<?= $bg ?>"><?= escape($p['statut']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Mise à jour de l'horloge
    setInterval(function() {
        const now = new Date();
        const hrs = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        const secs = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('live-time').innerText = hrs + ':' + mins + ':' + secs;
    }, 1000);

    // Fonction de pointage géolocalisé
    function submitClock(action) {
        document.getElementById('action-input').value = action;
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('lat-input').value = pos.coords.latitude;
                document.getElementById('lng-input').value = pos.coords.longitude;
                document.getElementById('clock-form').submit();
            }, function() {
                // Si la géoloc échoue, on soumet quand même
                document.getElementById('clock-form').submit();
            });
        } else {
            document.getElementById('clock-form').submit();
        }
    }
</script>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
