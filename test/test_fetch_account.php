<?php
/**
 * Test Script - fetch_account_flexcube.php
 * 
 * Tests the fixed fetch_account_flexcube.php endpoint
 * Run from: http://localhost/account_opening/test_fetch_account.php?account=ACCOUNT_NUMBER
 */

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test fetch_account_flexcube.php</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007db8;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        button {
            background: #007db8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #005a8b;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
        }
        .result.success {
            display: block;
            border-color: #4CAF50;
            background: #f0f8f5;
        }
        .result.error {
            display: block;
            border-color: #f44336;
            background: #fef5f5;
        }
        .result h2 {
            margin-top: 0;
            color: #333;
        }
        pre {
            background: white;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .loading.active {
            display: block;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007db8;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test fetch_account_flexcube.php</h1>
        
        <form id="testForm">
            <div class="form-group">
                <label for="account">Numéro de compte à tester:</label>
                <input type="text" id="account" name="account" placeholder="e.g., 1234567890" required>
            </div>
            <button type="submit">Tester Fetch Account</button>
        </form>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Recherche en cours...</p>
        </div>
        
        <div class="result" id="result">
            <h2 id="resultTitle">Résultat</h2>
            <pre id="resultContent"></pre>
        </div>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const account = document.getElementById('account').value.trim();
            if (!account) {
                alert('Veuillez entrer un numéro de compte');
                return;
            }
            
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            const resultTitle = document.getElementById('resultTitle');
            const resultContent = document.getElementById('resultContent');
            
            // Reset display
            result.classList.remove('success', 'error');
            loading.classList.add('active');
            
            try {
                const response = await fetch('cso/fetch_account_flexcube.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'account=' + encodeURIComponent(account)
                });
                
                const data = await response.json();
                
                loading.classList.remove('active');
                result.classList.add(response.ok && data.success ? 'success' : 'error');
                resultTitle.textContent = response.ok && data.success ? '✓ Succès' : '✗ Erreur';
                resultContent.textContent = JSON.stringify(data, null, 2);
                
            } catch (error) {
                loading.classList.remove('active');
                result.classList.add('error');
                resultTitle.textContent = '✗ Erreur Réseau';
                resultContent.textContent = 'Erreur: ' + error.message + '\n\nAssurez-vous que:\n1. Le serveur est démarré\n2. Le chemin URL est correct\n3. L\'extension OCI8 est chargée\n4. La base de données Oracle est accessible';
            }
        });
    </script>
</body>
</html>
