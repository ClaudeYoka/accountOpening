<?php
/**
 * TEST ENDPOINT: Chequier Details
 * Test la récupération des détails d'une demande de chéquier
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Get Chequier Details</title>
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
        <h1>Test Endpoint: Get Chequier Details</h1>
        <p>Récupérez les détails d'une demande de chéquier par ID</p>
        
        <div>
            <label>ID de la Demande (request_id):</label><br>
            <input type="number" id="requestId" placeholder="Ex: 1" value="1" min="1">
            <button onclick="testFetch()">Récupérer</button>
        </div>
        
        <div class="response" id="response" style="display: none;">
            <strong>Réponse:</strong>
            <pre id="responseText"></pre>
        </div>
    </div>

    <script>
        function testFetch() {
            const requestId = document.getElementById('requestId').value;
            const responseDiv = document.getElementById('response');
            const responseText = document.getElementById('responseText');
            
            if (!requestId) {
                alert('Veuillez entrer un ID de demande');
                return;
            }
            
            responseText.textContent = 'Chargement...';
            responseDiv.style.display = 'block';
            responseDiv.className = 'response';
            
            fetch('../get_chequier_details.php?request_id=' + encodeURIComponent(requestId))
                .then(resp => resp.json())
                .then(data => {
                    console.log('Réponse:', data);
                    responseText.textContent = JSON.stringify(data, null, 2);
                    if (data.success) {
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
    </script>
</body>
</html>
