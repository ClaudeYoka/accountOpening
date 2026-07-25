<?php
/**
 * Test pour rib_lookup.php
 * Teste si le formulaire produits reçoit bien l'account_title depuis rib_lookup
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test RIB Lookup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { border: 1px solid #333; padding: 15px; margin: 10px 0; }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        .info { background: #e3f2fd; color: #1565c0; }
        .warning { background: #fff3e0; color: #e65100; }
        button { padding: 8px 15px; cursor: pointer; }
        input { padding: 8px; width: 200px; }
        .result { background: #f5f5f5; padding: 10px; margin-top: 10px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
<h1>🧪 Test: cso/rib_lookup.php</h1>

<div class="test-box info">
    <h2>Informations</h2>
    <p>Ce formulaire teste l'endpoint <strong>cso/rib_lookup.php</strong> qui remplit le formulaire des produits</p>
    <p>L'API doit retourner:</p>
    <ul>
        <li><strong>account.account_title</strong> - Le nom du compte (intitulé)</li>
        <li><strong>account.account_number</strong> - Le numéro de compte</li>
        <li><strong>customer.customer_name</strong> - Le nom du client</li>
    </ul>
</div>

<div class="test-box">
    <h2>Test RIB Lookup</h2>
    <label>Numéro de compte: <input type="text" id="account" placeholder="Ex: 2100067842" value=""></label>
    <button onclick="testRIBLookup()">🧪 Tester RIB Lookup</button>
    <button onclick="clearResult()">✕ Effacer</button>
    
    <div id="result" class="result" style="display:none;"></div>
</div>

<script>
function testRIBLookup() {
    var account = document.getElementById('account').value.trim();
    if (!account) {
        alert('Veuillez entrer un numéro de compte');
        return;
    }
    
    var resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '⏳ Requête en cours...';
    resultDiv.style.display = 'block';
    
    fetch('cso/rib_lookup.php?account=' + encodeURIComponent(account))
    .then(r => {
        console.log('Response status:', r.status);
        return r.text().then(text => {
            return { status: r.status, text: text };
        });
    })
    .then(result => {
        var html = '<div class="' + (result.status === 200 ? 'success' : 'error') + '">';
        html += '<strong>HTTP Status: ' + result.status + '</strong></div>';
        
        try {
            var json = JSON.parse(result.text);
            html += '<div class="info"><strong>JSON Response:</strong></div>';
            html += '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
            
            if (json.status === 'ok' && json.account) {
                html += '<div class="success"><strong>✓ Réponse valide reçue</strong></div>';
                
                if (json.account.account_title) {
                    html += '<div class="success"><strong>✓ account_title présent:</strong> ' + json.account.account_title + '</div>';
                } else {
                    html += '<div class="error"><strong>✗ account_title manquant ou vide!</strong></div>';
                }
                
                if (json.customer?.customer_name) {
                    html += '<div class="info"><strong>customer_name:</strong> ' + json.customer.customer_name + '</div>';
                }
                
                if (json.account.account_number) {
                    html += '<div class="info"><strong>account_number:</strong> ' + json.account.account_number + '</div>';
                }
            } else if (json.status === 'error') {
                html += '<div class="error"><strong>Erreur API:</strong> ' + json.message + '</div>';
            }
        } catch (e) {
            html += '<div class="error"><strong>Erreur JSON:</strong> ' + e.message + '</div>';
            html += '<pre>Réponse brute:</pre>';
            html += '<pre>' + result.text + '</pre>';
        }
        
        resultDiv.innerHTML = html;
    })
    .catch(err => {
        resultDiv.innerHTML = '<div class="error"><strong>Erreur network:</strong> ' + err.message + '</div>';
        resultDiv.style.display = 'block';
    });
}

function clearResult() {
    document.getElementById('result').style.display = 'none';
}

// Test automatique si param en URL
var params = new URLSearchParams(window.location.search);
if (params.has('account')) {
    document.getElementById('account').value = params.get('account');
    setTimeout(testRIBLookup, 500);
}
</script>
</body>
</html>
