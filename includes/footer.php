            </div> <!-- End Main Content Area -->
            
            <footer class="footer mt-auto py-3 bg-light text-center border-top">
                <div class="container-fluid">
                    <span class="text-muted small">&copy; <?= date('Y') ?> <?= APP_NAME ?>. Tous droits réservés. (Version <?= APP_VERSION ?>)</span>
                </div>
            </footer>
        </div> <!-- End Content -->
    </div> <!-- End Wrapper -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Chart.js (chargé globalement pour le dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Global JS variables from PHP -->
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>

    <!-- Custom Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/ajax.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
