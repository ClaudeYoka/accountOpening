<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
// Vérifier les droits d'accès
if (!isset($_SESSION['alogin']) || $_SESSION['arole'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}
?>

<body>
    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="title">
                            <h2 class="h3 mb-0">📊 Centre de Monitoring</h2>
                        </div>
                        <nav aria-label="breadcrumb" role="navigation">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Monitoring</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-md-6 col-sm-12 text-right">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadDashboard('overview')">
                                <i class="fa fa-tachometer-alt"></i> Vue d'ensemble
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="loadDashboard('system')">
                                <i class="fa fa-server"></i> Système
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="loadDashboard('security')">
                                <i class="fa fa-shield-alt"></i> Sécurité
                            </button>
                            <a href="http://localhost:3000/?orgId=1" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-external-link-alt"></i> Grafana
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring Status Info -->
            <div class="alert alert-info mb-20" id="monitoring-info">
                <h5><i class="fa fa-info-circle"></i> Statut du système de monitoring</h5>
                <p class="mb-2">Le système de monitoring utilise Docker pour exécuter Prometheus et Grafana. Si les métriques ne se chargent pas, vérifiez que Docker est en cours d'exécution.</p>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Pour démarrer le monitoring :</strong>
                        <ol class="mb-0">
                            <li>Assurez-vous que Docker Desktop est installé et démarré</li>
                            <li>Exécutez le script <code>start_monitoring.bat</code> à la racine du projet</li>
                            <li>Actualisez cette page</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <strong>URLs directes :</strong>
                        <ul class="mb-0">
                            <li><a href="http://localhost:3000" target="_blank">Grafana (admin/admin)</a></li>
                            <li><a href="http://localhost:9090" target="_blank">Prometheus</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="row mb-20">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card-box p-3 bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fa fa-server fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="app-status">Vérification...</h5>
                                <small>Statut Application</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card-box p-3 bg-info text-white">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fa fa-docker fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="docker-status">Vérification...</h5>
                                <small>Statut Monitoring</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card-box p-3 bg-success text-white">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fa fa-users fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="active-users">-</h5>
                                <small>Utilisateurs actifs</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card-box p-3 bg-warning text-white">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fa fa-database fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="db-size">-</h5>
                                <small>Base de données</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card-box p-3 bg-danger text-white">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fa fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="recent-errors">-</h5>
                                <small>Erreurs récentes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Container -->
            <div class="card-box mb-30">
                <div class="pd-20">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="dashboard-container" style="min-height: 600px;">
                                <div class="text-center p-5">
                                    <i class="fa fa-chart-line fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">Sélectionnez un dashboard</h4>
                                    <p class="text-muted">Cliquez sur l'un des boutons ci-dessus pour afficher les métriques</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card-box mb-30">
                        <h5 class="card-title">Actions Rapides</h5>
                        <div class="pd-20">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="monitoring_overview" target="_blank" class="btn btn-primary btn-block mb-2">
                                        <i class="fa fa-tachometer-alt"></i> Vue d'ensemble
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="monitoring_system" target="_blank" class="btn btn-info btn-block mb-2">
                                        <i class="fa fa-server"></i> Métriques système
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="monitoring_security" target="_blank" class="btn btn-warning btn-block mb-2">
                                        <i class="fa fa-shield-alt"></i> Sécurité
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="http://localhost:3000/?orgId=1" target="_blank" class="btn btn-secondary btn-block mb-2">
                                        <i class="fa fa-external-link-alt"></i> Grafana complet
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-box mb-30">
                        <h5 class="card-title">Informations Système</h5>
                        <div class="pd-20">
                            <div class="mb-3">
                                <strong>URLs de monitoring :</strong>
                                <ul class="list-unstyled mt-2">
                                    <li><i class="fa fa-globe text-primary"></i> <a href="http://localhost:3000" target="_blank">Grafana</a></li>
                                    <li><i class="fa fa-chart-bar text-info"></i> <a href="http://localhost:9090" target="_blank">Prometheus</a></li>
                                    <li><i class="fa fa-server text-success"></i> <a href="http://localhost:8080/metrics.php" target="_blank">Métriques PHP</a></li>
                                    <li><i class="fa fa-database text-warning"></i> <a href="http://localhost:8080/business_metrics.php" target="_blank">Métriques Business</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <?php include('includes/scriptJs.php')?>

    <script>
        // URLs des dashboards Grafana
        const dashboardUrls = {
            overview: 'http://localhost:3000/d/account-opening-overview?orgId=1&kiosk=1&refresh=30s',
            system: 'http://localhost:3000/d/system-metrics?orgId=1&kiosk=1&refresh=30s',
            security: 'http://localhost:3000/d/security-dashboard?orgId=1&kiosk=1&refresh=30s'
        };

        // Charger un dashboard dans l'iframe
        function loadDashboard(type) {
            const container = document.getElementById('dashboard-container');
            const url = dashboardUrls[type];

            if (url) {
                container.innerHTML = `
                    <iframe src="${url}"
                            width="100%"
                            height="600"
                            frameborder="0"
                            style="border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    </iframe>
                `;

                // Mettre à jour les boutons actifs
                document.querySelectorAll('.btn-group .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.classList.add('active');
            }
        }

        // Charger les métriques en temps réel
        async function loadMetrics() {
            try {
                // Charger les métriques business
                const response = await fetch('../business_metrics.php');
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const metricsText = await response.text();

                // Parser les métriques (format Prometheus)
                const metrics = parsePrometheusMetrics(metricsText);

                // Mettre à jour l'interface
                document.getElementById('active-users').textContent = metrics.app_active_sessions || '0';
                document.getElementById('db-size').textContent = metrics.app_database_size_mb ? metrics.app_database_size_mb + ' MB' : 'N/A';
                document.getElementById('recent-errors').textContent = metrics.app_recent_errors || '0';

                // Vérifier le statut de l'application
                const appStatus = metrics.db_connection_status === '1' ? '✅ Good' : '❌ Problème DB';
                document.getElementById('app-status').textContent = appStatus;

                // Mettre à jour le statut Docker
                document.getElementById('docker-status').textContent = '✅ Métriques PHP OK';

                // Masquer l'alerte d'information si tout fonctionne
                document.getElementById('monitoring-info').style.display = 'none';

            } catch (error) {
                console.error('Erreur lors du chargement des métriques:', error);
                document.getElementById('app-status').textContent = '❌ Erreur chargement';
                document.getElementById('docker-status').textContent = '❌ Vérifiez Docker';
            }
        }

        // Parser les métriques au format Prometheus
        function parsePrometheusMetrics(text) {
            const metrics = {};
            const lines = text.split('\n');

            lines.forEach(line => {
                if (line.startsWith('#')) return; // Ignorer les commentaires

                const parts = line.split(' ');
                if (parts.length >= 2) {
                    const name = parts[0];
                    const value = parts[1];
                    metrics[name] = value;
                }
            });

            return metrics;
        }

        // Charger les métriques au démarrage et toutes les 30 secondes
        document.addEventListener('DOMContentLoaded', function() {
            loadMetrics();
            setInterval(loadMetrics, 30000);
        });

        // Auto-refresh des dashboards toutes les 30 secondes
        setInterval(function() {
            const iframe = document.querySelector('#dashboard-container iframe');
            if (iframe) {
                iframe.src = iframe.src;
            }
        }, 30000);
    </script>
</body>
</html>