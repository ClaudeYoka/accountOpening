<?php
/**
 * Test Principal pour fetch_account_flexcube.php
 * Teste l'API principal qui remplit le formulaire d'ouverture de compte
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test API Fetch Account Flexcube</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { border: 1px solid #333; padding: 15px; margin: 10px 0; }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        .info { background: #e3f2fd; color: #1565c0; }
        button { padding: 8px 15px; cursor: pointer; }
        input { padding: 8px; width: 200px; }
        .result { background: #f5f5f5; padding: 10px; margin-top: 10px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
<h1>🧪 Test: fetch_account_flexcube.php</h1>

<div class="test-box info">
    <h2>Informations</h2>
    <p>Ce formulaire teste l'endpoint <strong>cso/fetch_account_flexcube.php</strong> qui est utilisé par le formulaire d'ouverture de compte</p>
    <p>L'API doit retourner les champs: account_name, account_number, first_name, last_name, middle_name, customer_address, telephone, etc.</p>
</div>

<div class="test-box">
    <h2>Test API</h2>
    <label>Numéro de compte: <input type="text" id="account" placeholder="Ex: 2100067842" value=""></label>
    <button onclick="testAPI()">🧪 Tester API</button>
    <button onclick="clearResult()">✕ Effacer</button>
    
    <div id="result" class="result" style="display:none;"></div>
</div>

<script>
function testAPI() {
    var account = document.getElementById('account').value.trim();
    if (!account) {
        alert('Veuillez entrer un numéro de compte');
        return;
    }
    
    var resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '⏳ Requête en cours...';
    resultDiv.style.display = 'block';
    
    fetch('cso/fetch_account_flexcube.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'account=' + encodeURIComponent(account)
    })
    .then(r => {
        console.log('Response status:', r.status);
        console.log('Response headers:', r.headers);
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
            
            if (json.success && json.data) {
                html += '<div class="success"><strong>Champs reçus:</strong><ul>';
                for (var key in json.data) {
                    html += '<li><strong>' + key + '</strong>: ' +  (json.data[key] ? json.data[key] : '(vide)') + '</li>';
                }
                html += '</ul></div>';
                
                // Vérifier les champs critiques
                var criticalFields = ['account_name', 'account_number', 'first_name', 'last_name', 'middle_name'];
                var missing = [];
                criticalFields.forEach(f => {
                    if (!json.data[f]) missing.push(f);
                });
                
                if (missing.length > 0) {
                    html += '<div class="error"><strong>⚠️ Champs manquants:</strong> ' + missing.join(', ') + '</div>';
                }
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
    setTimeout(testAPI, 500);
}
</script>
</body>
</html>
