<?php 
/**
 * Page de Test Flexcube API
 * 
 * Accessible via: http://localhost/account%20opening/cso/flexcube_test.php
 * 
 * ATTENTION: À désactiver en production (ne pas laisser cette page accessible)
 */

// Vérifier que l'on est en développement
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    http_response_code(403);
    die('Cette page n\'est accessible que en développement local');
}

include('../includes/config.php');
include('includes/flexcube_helpers.php');

$test_result = null;
$batch_results = null;
$account_to_test = isset($_GET['account']) ? trim($_GET['account']) : '37220020391';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action'])) {
        
        if ($_POST['action'] === 'test_connection') {
            // Test simple de connexion
            $test_result = testFlexcubeConnection();
        }
        
        elseif ($_POST['action'] === 'fetch_single') {
            // Récupérer un compte unique
            $account = trim($_POST['account'] ?? '');
            if ($account) {
                $account_to_test = $account;
                $response = getFlexcubeAPI()->getAccountInfo($account);
                $test_result = $response;
            }
        }
        
        elseif ($_POST['action'] === 'fetch_batch') {
            // Récupérer plusieurs comptes
            $accounts_text = $_POST['accounts'] ?? '';
            $accounts = array_filter(array_map('trim', explode("\n", $accounts_text)));
            
            if (!empty($accounts)) {
                $batch_results = [];
                $api = getFlexcubeAPI();
                
                foreach ($accounts as $acc) {
                    $batch_results[$acc] = $api->getAccountInfo($acc);
                }
            }
        }
        
        elseif ($_POST['action'] === 'test_with_fallback') {
            // Tester avec fallback BD
            $account = trim($_POST['account'] ?? '');
            if ($account) {
                $account_to_test = $account;
                $result = fetchAccountWithFallback($account, $conn);
                $test_result = [
                    'success' => $result['data'] !== null,
                    'source' => $result['source'],
                    'data' => $result['data'],
                    'error' => $result['data'] === null ? 'Pas trouvé' : null
                ];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Flexcube API - Ecobank</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .result-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .result-success {
            border-left: 4px solid #28a745;
            background: #f1f9f1;
        }
        
        .result-error {
            border-left: 4px solid #dc3545;
            background: #f9f1f1;
        }
        
        .result-info {
            border-left: 4px solid #17a2b8;
            background: #f1f8fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
            font-size: 13px;
        }
        
        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .code-block {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔍 Test Flexcube API Ecobank</h1>
            <p class="subtitle">Interface de test pour l'intégration de l'API Flexcube</p>
        </header>
        
        <div class="warning">
            ⚠️ <strong>Attention:</strong> Cette page est réservée au développement. 
            À désactiver en production.
        </div>
        
        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('connection')">Connexion</button>
            <button class="tab-btn" onclick="switchTab('single')">Un Compte</button>
            <button class="tab-btn" onclick="switchTab('batch')">Batch</button>
            <button class="tab-btn" onclick="switchTab('fallback')">Fallback BD</button>
            <button class="tab-btn" onclick="switchTab('info')">ℹ️ Infos</button>
        </div>
        
        <!-- TAB: CONNECTION -->
        <div id="connection" class="tab-content active">
            <div class="result-box result-info">
                <h3>Test de Connexion</h3>
                <p>Vérifiez si l'API Flexcube est accessible.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="test_connection">
                    <button type="submit" class="btn">Tester la Connexion</button>
                </form>
                
                <?php if ($test_result && isset($test_result['status'])): ?>
                    <div class="result-box <?php echo $test_result['status'] === 'OK' ? 'result-success' : 'result-error'; ?>" style="margin-top: 20px;">
                        <div class="badge <?php echo $test_result['status'] === 'OK' ? 'badge-success' : 'badge-error'; ?>">
                            Status: <?php echo $test_result['status']; ?>
                        </div>
                        <p><strong>Message:</strong> <?php echo htmlspecialchars($test_result['message']); ?></p>
                        <?php if (!empty($test_result['details']['error'])): ?>
                            <p><strong>Erreur:</strong> <?php echo htmlspecialchars($test_result['details']['error']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- TAB: SINGLE ACCOUNT -->
        <div id="single" class="tab-content">
            <div class="result-box result-info">
                <h3>Récupérer un Compte</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="fetch_single">
                    
                    <div class="form-group">
                        <label>Numéro de Compte:</label>
                        <input type="text" name="account" value="<?php echo htmlspecialchars($account_to_test); ?>" placeholder="ex: 37220020391" required>
                    </div>
                    
                    <button type="submit" class="btn">Récupérer le Compte</button>
                </form>
                
                <?php if ($test_result && !isset($test_result['status'])): ?>
                    <div class="result-box <?php echo $test_result['success'] ? 'result-success' : 'result-error'; ?>" style="margin-top: 20px;">
                        <div class="badge <?php echo $test_result['success'] ? 'badge-success' : 'badge-error'; ?>">
                            <?php echo $test_result['success'] ? '✓ Succès' : '✗ Erreur'; ?>
                        </div>
                        
                        <?php if ($test_result['success'] && $test_result['data']): ?>
                            <table class="data-table">
                                <tr>
                                    <th>Propriété</th>
                                    <th>Valeur</th>
                                </tr>
                                <?php foreach ($test_result['data'] as $key => $value): ?>
                                    <?php if ($key !== 'raw_response'): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                            <td><?php echo htmlspecialchars($value ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p><strong>Erreur:</strong> <?php echo htmlspecialchars($test_result['error'] ?? 'Compte non trouvé'); ?></p>
                        <?php endif; ?>
                        
                        <p style="margin-top: 10px; font-size: 12px; color: #666;">
                            <strong>Timestamp:</strong> <?php echo $test_result['timestamp'] ?? 'N/A'; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- TAB: BATCH -->
        <div id="batch" class="tab-content">
            <div class="result-box result-info">
                <h3>Récupérer Plusieurs Comptes</h3>
                <p style="margin-bottom: 15px; font-size: 13px; color: #666;">
                    Entrez un numéro de compte par ligne.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="fetch_batch">
                    
                    <div class="form-group">
                        <label>Numéros de Compte:</label>
                        <textarea name="accounts" placeholder="37220020391&#10;37220020392&#10;37220020393">37220020391
37220020392
37220020393</textarea>
                    </div>
                    
                    <button type="submit" class="btn">Récupérer les Comptes</button>
                </form>
                
                <?php if ($batch_results): ?>
                    <div class="result-box result-info" style="margin-top: 20px;">
                        <h4>Résultats du Batch (<?php echo count($batch_results); ?> comptes)</h4>
                        
                        <table class="data-table">
                            <tr>
                                <th>Numéro de Compte</th>
                                <th>Status</th>
                                <th>Nom du Compte</th>
                                <th>Détails</th>
                            </tr>
                            <?php foreach ($batch_results as $account => $result): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($account); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $result['success'] ? 'badge-success' : 'badge-error'; ?>">
                                            <?php echo $result['success'] ? '✓' : '✗'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $result['success'] ? htmlspecialchars($result['data']['account_name'] ?? 'N/A') : '-'; ?></td>
                                    <td><?php echo $result['success'] ? '' : htmlspecialchars(substr($result['error'], 0, 50)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- TAB: FALLBACK -->
        <div id="fallback" class="tab-content">
            <div class="result-box result-info">
                <h3>Test avec Fallback BD</h3>
                <p style="margin-bottom: 15px; font-size: 13px; color: #666;">
                    Essaye Flexcube d'abord, puis la BD locale.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="test_with_fallback">
                    
                    <div class="form-group">
                        <label>Numéro de Compte:</label>
                        <input type="text" name="account" value="<?php echo htmlspecialchars($account_to_test); ?>" placeholder="ex: 37220020391" required>
                    </div>
                    
                    <button type="submit" class="btn">Tester</button>
                </form>
                
                <?php if ($test_result && !isset($test_result['status'])): ?>
                    <div class="result-box <?php echo $test_result['success'] ? 'result-success' : 'result-error'; ?>" style="margin-top: 20px;">
                        <div class="badge <?php echo $test_result['success'] ? 'badge-success' : 'badge-error'; ?>">
                            Source: <?php echo htmlspecialchars($test_result['source'] ?? 'N/A'); ?>
                        </div>
                        
                        <?php if ($test_result['success'] && $test_result['data']): ?>
                            <table class="data-table">
                                <tr>
                                    <th>Propriété</th>
                                    <th>Valeur</th>
                                </tr>
                                <?php 
                                $data = $test_result['data'];
                                $keys_to_show = ['id', 'account_number', 'customer_name', 'status', 'balance', 'created_at'];
                                foreach ($keys_to_show as $key): 
                                    if (isset($data[$key])):
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                        <td><?php echo htmlspecialchars($data[$key] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </table>
                        <?php else: ?>
                            <p><strong>Erreur:</strong> <?php echo htmlspecialchars($test_result['error'] ?? 'Compte non trouvé'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- TAB: INFO -->
        <div id="info" class="tab-content">
            <div class="result-box result-info">
                <h3>Configuration & Infos</h3>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Paramètres Flexcube:</h4>
                <table class="data-table">
                    <tr>
                        <th>Paramètre</th>
                        <th>Valeur</th>
                    </tr>
                    <tr>
                        <td>URL API</td>
                        <td><code><?php echo defined('FLEXCUBE_API_URL') ? FLEXCUBE_API_URL : 'Non défini'; ?></code></td>
                    </tr>
                    <tr>
                        <td>Source Code</td>
                        <td><code><?php echo defined('FLEXCUBE_SOURCE_CODE') ? FLEXCUBE_SOURCE_CODE : 'ECOBANKMOBILE'; ?></code></td>
                    </tr>
                    <tr>
                        <td>Affiliate Code</td>
                        <td><code><?php echo defined('FLEXCUBE_AFFILIATE_CODE') ? FLEXCUBE_AFFILIATE_CODE : 'ECG'; ?></code></td>
                    </tr>
                    <tr>
                        <td>SSL Verify</td>
                        <td><code><?php echo defined('FLEXCUBE_VERIFY_SSL') ? (FLEXCUBE_VERIFY_SSL ? 'true' : 'false') : 'false'; ?></code></td>
                    </tr>
                </table>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Fichiers Créés:</h4>
                <ul style="margin-left: 20px;">
                    <li><code>cso/includes/FlexcubeAPI.php</code> - Classe principale</li>
                    <li><code>cso/includes/flexcube_helpers.php</code> - Fonctions utilitaires</li>
                    <li><code>cso/FLEXCUBE_INTEGRATION.md</code> - Documentation</li>
                    <li><code>cso/flexcube_examples.php</code> - Exemples d'utilisation</li>
                    <li><code>cso/flexcube_config.template.php</code> - Template configuration</li>
                    <li><code>cso/flexcube_test.php</code> - Cette page</li>
                </ul>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Documentation:</h4>
                <p>Voir <strong>FLEXCUBE_INTEGRATION.md</strong> pour la documentation complète.</p>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Masquer tous les tabs
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(c => c.classList.remove('active'));
            
            // Désactiver tous les boutons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(b => b.classList.remove('active'));
            
            // Afficher le tab sélectionné
            document.getElementById(tabName).classList.add('active');
            
            // Activer le bouton sélectionné
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
