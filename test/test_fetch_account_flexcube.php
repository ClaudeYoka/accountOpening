<?php
/**
 * TEST ENDPOINT: Fetch Account Flexcube
 * Test l'API de récupération des données de compte depuis Flexcube
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Fetch Account Flexcube</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        input, button { padding: 10px; margin: 5px; font-size: 14px; }
        button { background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        .response { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        pre { overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Endpoint: Fetch Account Flexcube</h1>
        <p>Testez la récupération des données de compte depuis Flexcube</p>
        
        <div>
            <label>Numéro de Compte:</label><br>
            <input type="text" id="accountNumber" placeholder="Ex: 37155023238" value="37155023238">
            <button onclick="testFetch()">Rechercher</button>
        </div>
        
        <div class="response" id="response" style="display: none;">
            <strong>Réponse:</strong>
            <pre id="responseText"></pre>
        </div>
    </div>

    <script>
        function testFetch() {
            const accountNumber = document.getElementById('accountNumber').value;
            const responseDiv = document.getElementById('response');
            const responseText = document.getElementById('responseText');
            
            if (!accountNumber) {
                alert('Veuillez entrer un numéro de compte');
                return;
            }
            
            responseText.textContent = 'Chargement...';
            responseDiv.style.display = 'block';
            responseDiv.className = 'response';
            
            fetch('../fetch_account_flexcube.php?account=' + encodeURIComponent(accountNumber))
                .then(resp => resp.json())
                .then(data => {
                    console.log('Réponse:', data);
                    responseText.textContent = JSON.stringify(data, null, 2);
                    if (data.success || data.account_number) {
                        responseDiv.className = 'response success';
                    } else {
                        responseDiv.className = 'response error';
                    }
                })
                .catch(err => {
                    responseText.textContent = 'Erreur: ' + err.message;
                    responseDiv.className = 'response error';
                });
        }
        
        // Auto-test au chargement
        window.addEventListener('load', testFetch);
    </script>
</body>
</html>
