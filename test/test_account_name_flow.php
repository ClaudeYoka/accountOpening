<?php
/**
 * Test Synthèse: Vérification complète du flux intitulé_compte
 * Vérifie l'ensemble du parcours: Oracle → flexcube_helpers.php → fetch_account_flexcube.php → Frontend
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Synthèse - Intitulé Compte</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 5px; }
        .test-section { background: white; margin: 20px 0; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .test-box { border-left: 4px solid #667eea; padding: 15px; margin: 10px 0; }
        .success { border-left-color: #22c55e; background: #f0fdf4; }
        .error { border-left-color: #ef4444; background: #fef2f2; }
        .info { border-left-color: #3b82f6; background: #eff6ff; }
        .warning { border-left-color: #f59e0b; background: #fffbeb; }
        button { padding: 10px 20px; cursor: pointer; background: #667eea; color: white; border: none; border-radius: 3px; margin-right: 5px; }
        button:hover { background: #764ba2; }
        input { padding: 8px; width: 250px; border: 1px solid #ddd; border-radius: 3px; }
        .result { background: #f9fafb; padding: 10px; margin-top: 10px; font-family: monospace; overflow-y: auto; max-height: 200px; border: 1px solid #e5e7eb; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; font-weight: bold; }
        .status-ok { color: #22c55e; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        small { color: #6b7280; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🧪 Test Synthèse: Flux Intitulé Compte (account_name)</h1>
        <p>Vérifie que account_name (intitulé du compte) circule correctement dans l'application</p>
    </div>

    <div class="test-section">
        <h2>Configuration du Test</h2>
        <div>
            <label>Numéro de compte à tester:</label>
            <input type="text" id="account" placeholder="Ex: 2100067842" value="">
            <button onclick="runCompleteTest()">🚀 Lancer Test Complet</button>
            <button onclick="clearAllResults()">✕ Effacer tous les résultats</button>
        </div>
    </div>

    <!-- Étape 1: OCI8 Connection -->
    <div class="test-section">
        <h2>Étape 1️⃣: Connexion OCI8 à Oracle</h2>
        <button onclick="testOCI8Connection()">Tester Connexion OCI8</button>
        <div id="result-oci8" class="result" style="display:none;"></div>
    </div>

    <!-- Étape 2: Oracle Query -->
    <div class="test-section">
        <h2>Étape 2️⃣: Requête Oracle (account_name)</h2>
        <button onclick="testOracleQuery()">Tester Requête Oracle</button>
        <div id="result-oracle" class="result" style="display:none;"></div>
    </div>

    <!-- Étape 3: flexcube_helpers.php -->
    <div class="test-section">
        <h2>Étape 3️⃣: flexcube_helpers.php - fetchAccountFromOracleDatabase()</h2>
        <p><small>Cette fonction doit retourner account_name</small></p>
        <button onclick="testFlexcubeHelpers()">Tester flexcube_helpers</button>
        <div id="result-helpers" class="result" style="display:none;"></div>
    </div>

    <!-- Étape 4: fetch_account_flexcube.php -->
    <div class="test-section">
        <h2>Étape 4️⃣: fetch_account_flexcube.php - API Principal</h2>
        <p><small>Cette API doit retourner account_name, account-title, et intitule_compte</small></p>
        <button onclick="testMainAPI()">Tester API fetch_account_flexcube</button>
        <div id="result-api" class="result" style="display:none;"></div>
    </div>

    <!-- Étape 5: rib_lookup.php -->
    <div class="test-section">
        <h2>Étape 5️⃣: rib_lookup.php - API RIB</h2>
        <p><small>Cette API doit retourner account.account_title</small></p>
        <button onclick="testRIBAPI()">Tester API rib_lookup</button>
        <div id="result-rib" class="result" style="display:none;"></div>
    </div>

    <!-- Résumé -->
    <div class="test-section">
        <h2>📊 Résumé des Tests</h2>
        <div id="summary" class="result" style="display:none;"></div>
        <p id="summary-text" style="display:none;"></p>
    </div>
</div>

<script>
function setAccountNumber() {
    var account = document.getElementById('account').value.trim();
    if (!account) {
        alert('Veuillez entrer un numéro de compte');
        return null;
    }
    return account;
}

function showResult(elementId, content, type = 'info') {
    var div = document.getElementById(elementId);
    div.innerHTML = '<div class="test-box ' + type + '">' + content + '</div>';
    div.style.display = 'block';
}

function testOCI8Connection() {
    showResult('result-oci8', '⏳ Test en cours...', 'info');
    fetch('diagnostic.php?test=oci8')
    .then(r => r.text())
    .then(html => {
        showResult('result-oci8', html, 'info');
    })
    .catch(err => {
        showResult('result-oci8', '❌ Erreur: ' + err.message, 'error');
    });
}

function testOracleQuery() {
    var account = setAccountNumber();
    if (!account) return;
    
    showResult('result-oracle', '⏳ Requête Oracle en cours...', 'info');
    fetch('test_oci8_direct.php?account=' + encodeURIComponent(account))
    .then(r => r.text())
    .then(html => {
        if (html.includes('account_name') && !html.includes('not found')) {
            showResult('result-oracle', '✅ ' + html, 'success');
        } else {
            showResult('result-oracle', '⚠️ ' + html, 'warning');
        }
    })
    .catch(err => {
        showResult('result-oracle', '❌ Erreur: ' + err.message, 'error');
    });
}

function testFlexcubeHelpers() {
    var account = setAccountNumber();
    if (!account) return;
    
    showResult('result-helpers', '⏳ Test flexcube_helpers en cours...', 'info');
    fetch('cso/test_api_response.php?account=' + encodeURIComponent(account))
    .then(r => r.text())
    .then(html => {
        if (html.includes('account_name') && !html.includes('error')) {
            showResult('result-helpers', '✅ ' + html, 'success');
        } else {
            showResult('result-helpers', '⚠️ ' + html, 'warning');
        }
    })
    .catch(err => {
        showResult('result-helpers', '❌ Erreur: ' + err.message, 'error');
    });
}

function testMainAPI() {
    var account = setAccountNumber();
    if (!account) return;
    
    showResult('result-api', '⏳ Test API en cours...', 'info');
    fetch('test_main_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'account=' + encodeURIComponent(account)
    })
    .then(r => r.json().then(json => ({ ok: r.ok, status: r.status, data: json })))
    .then(result => {
        var html = '<table>';
        html += '<tr><th>Champ</th><th>Valeur</th><th>Statut</th></tr>';
        
        var criticalFields = ['account_name', 'account-title', 'intitule_compte', 'account_number'];
        var allOk = true;
        
        if (result.data.data) {
            criticalFields.forEach(field => {
                var value = result.data.data[field];
                var status = value ? '<span class="status-ok">✓</span>' : '<span class="status-error">✗</span>';
                html += '<tr><td>' + field + '</td><td>' + (value || '(vide)') + '</td><td>' + status + '</td></tr>';
                if (!value) allOk = false;
            });
        }
        
        html += '</table>';
        showResult('result-api', html, allOk ? 'success' : 'warning');
    })
    .catch(err => {
        showResult('result-api', '❌ Erreur: ' + err.message, 'error');
    });
}

function testRIBAPI() {
    var account = setAccountNumber();
    if (!account) return;
    
    showResult('result-rib', '⏳ Test RIB API en cours...', 'info');
    fetch('cso/rib_lookup.php?account=' + encodeURIComponent(account))
    .then(r => r.json())
    .then(json => {
        var html = '<table>';
        html += '<tr><th>Champ</th><th>Valeur</th><th>Statut</th></tr>';
        
        var accountTitle = json.account?.account_title;
        var accountNumber = json.account?.account_number;
        var customerName = json.customer?.customer_name;
        
        html += '<tr><td>account.account_title</td><td>' + (accountTitle || '(vide)') + '</td><td>' + (accountTitle ? '<span class="status-ok">✓</span>' : '<span class="status-error">✗</span>') + '</td></tr>';
        html += '<tr><td>account.account_number</td><td>' + (accountNumber || '(vide)') + '</td><td>' + (accountNumber ? '<span class="status-ok">✓</span>' : '<span class="status-error">✗</span>') + '</td></tr>';
        html += '<tr><td>customer.customer_name</td><td>' + (customerName || '(vide)') + '</td><td>' + (customerName ? '<span class="status-ok">✓</span>' : '<span class="status-error">✗</span>') + '</td></tr>';
        html += '</table>';
        
        var allOk = accountTitle && accountNumber && customerName;
        showResult('result-rib', html, allOk ? 'success' : 'warning');
    })
    .catch(err => {
        showResult('result-rib', '❌ Erreur: ' + err.message, 'error');
    });
}

function runCompleteTest() {
    var account = setAccountNumber();
    if (!account) return;
    
    document.getElementById('summary').style.display = 'none';
    document.getElementById('summary-text').style.display = 'none';
    
    testMainAPI();
    testRIBAPI();
    
    setTimeout(() => {
        document.getElementById('summary').style.display = 'block';
        document.getElementById('summary-text').innerHTML = '<strong>Résumé:</strong> Tests terminés. Vérifiez les résultats ci-dessus.';
        document.getElementById('summary-text').style.display = 'block';
    }, 1000);
}

function clearAllResults() {
    document.getElementById('result-oci8').style.display = 'none';
    document.getElementById('result-oracle').style.display = 'none';
    document.getElementById('result-helpers').style.display = 'none';
    document.getElementById('result-api').style.display = 'none';
    document.getElementById('result-rib').style.display = 'none';
    document.getElementById('summary').style.display = 'none';
    document.getElementById('summary-text').style.display = 'none';
}

// Auto-lancer si param en URL
var params = new URLSearchParams(window.location.search);
if (params.has('account')) {
    document.getElementById('account').value = params.get('account');
    setTimeout(runCompleteTest, 500);
}
</script>
</body>
</html>
