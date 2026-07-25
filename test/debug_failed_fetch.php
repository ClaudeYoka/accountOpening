<?php
/**
 * Test Direct: Vérifie pourquoi "Failed to fetch" lors de l'appel à fetch_account_flexcube.php
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Failed to fetch</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
        .error { border-left-color: #ef4444; }
        .success { border-left-color: #22c55e; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        button { padding: 8px 15px; margin: 5px; cursor: pointer; }
        input { padding: 8px; width: 300px; }
    </style>
</head>
<body>
<h1>🔍 Debug: Failed to fetch</h1>

<div class="box">
    <h2>Test 1: Tester l'endpoint API directement</h2>
    <input type="text" id="account" placeholder="Numéro de compte" value="2100067842">
    <button onclick="testDirect()">Tester</button>
    <pre id="result1" style="display:none;"></pre>
</div>

<div class="box">
    <h2>Test 2: Vérifier les erreurs PHP</h2>
    <button onclick="checkErrors()">Vérifier error.log</button>
    <pre id="result2" style="display:none;"></pre>
</div>

<script>
function testDirect() {
    var account = document.getElementById('account').value;
    var pre = document.getElementById('result1');
    pre.textContent = 'Requête en cours...';
    pre.style.display = 'block';
    
    // Test avec GET (pour éviter les problèmes POST)
    fetch('cso/fetch_account_flexcube.php?account=' + encodeURIComponent(account))
    .then(r => {
        pre.textContent = 'Status: ' + r.status + '\n';
        pre.textContent += 'Headers:\n';
        r.headers.forEach((v, k) => {
            pre.textContent += '  ' + k + ': ' + v + '\n';
        });
        return r.text().then(text => {
            pre.textContent += '\nRéponse Brute:\n' + text;
            return { status: r.status, text: text };
        });
    })
    .catch(err => {
        pre.textContent = 'ERREUR NETWORK:\n' + err.message + '\n\nCela signifie:\n' +
            '- L\'endpoint est en erreur fatale\n' +
            '- Ou ne répond pas du tout\n' +
            '- Ou retourne une réponse corruptée';
    });
}

function checkErrors() {
    var pre = document.getElementById('result2');
    pre.textContent = 'Lecture du fichier error.log...';
    pre.style.display = 'block';
    
    fetch('check_error_log.php')
    .then(r => r.text())
    .then(text => {
        pre.textContent = text;
    })
    .catch(err => {
        pre.textContent = 'Erreur: ' + err.message;
    });
}
</script>
</body>
</html>
