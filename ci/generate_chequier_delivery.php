<?php
include('../includes/session.php');
include('../includes/config.php');

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$request_id) {
    die('ID invalide');
}

// Récupérer les informations de la demande de chéquier
$query = "SELECT 
            efs.id,
            efs.customer_name,
            efs.branch_code,
            efs.account_number,
            efs.email,
            efs.data,
            efs.created_at,
            efs.emp_id,
            tb.DepartmentName as agency_name,
            te.FirstName,
            te.LastName,
            te.EmailId as cso_email
        FROM ecobank_form_submissions efs
        LEFT JOIN tbldepartments tb ON efs.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        LEFT JOIN tblemployees te ON efs.emp_id = te.emp_id
        WHERE efs.id = $request_id";

$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die('Demande non trouvée');
}

$row = mysqli_fetch_assoc($result);

// Extraire les infos de chéquier du JSON
$data = json_decode($row['data'], true);
$chequier_types = array();
$chequier_fields = array('25 Feuilles', '50 Feuilles');

foreach ($chequier_fields as $type) {
    if (isset($data[$type]) && ($data[$type] === 'on' || $data[$type] === true)) {
        $chequier_types[] = $type;
    }
}

$total_quantity = count($chequier_types);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de Livraison Chéquier #<?php echo $request_id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #D32F2F;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #D32F2F;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 12px;
        }
        
        .doc-number {
            text-align: right;
            margin-bottom: 20px;
            font-size: 14px;
            color: #999;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            background: #D32F2F;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            width: 200px;
            color: #333;
        }
        
        .info-value {
            flex: 1;
            color: #666;
        }
        
        .chequier-list {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #D32F2F;
        }
        
        .chequier-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .chequier-item:last-child {
            border-bottom: none;
        }
        
        .chequier-type {
            font-weight: bold;
            color: #D32F2F;
        }
        
        .quantity {
            background: #D32F2F;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            min-width: 60px;
            text-align: center;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
            font-size: 12px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .badge {
            display: inline-block;
            background: #007db8;
            color: white;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BON DE LIVRAISON CHÉQUIER</h1>
            <p>Ecobank Congo</p>
        </div>
        
        <div class="doc-number">
            <strong>Bon #<?php echo str_pad($request_id, 6, '0', STR_PAD_LEFT); ?></strong> | 
            <strong><?php echo date('d/m/Y'); ?></strong>
        </div>
        
        <div class="section">
            <div class="section-title">INFORMATIONS AGENCE</div>
            <div class="info-row">
                <div class="info-label">Agence :</div>
                <div class="info-value">
                    <span class="badge"><?php echo htmlspecialchars($row['branch_code']); ?></span>
                    <?php echo htmlspecialchars($row['agency_name'] ?? ''); ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Responsable (CSO) :</div>
                <div class="info-value"><?php echo htmlspecialchars(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? '')); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email CSO :</div>
                <div class="info-value"><?php echo htmlspecialchars($row['cso_email'] ?? ''); ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">INFORMATIONS CLIENT</div>
            <div class="info-row">
                <div class="info-label">Nom du Client :</div>
                <div class="info-value"><?php echo htmlspecialchars($row['customer_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Numéro de Compte :</div>
                <div class="info-value"><code style="background: #f5f5f5; padding: 4px 8px; border-radius: 3px;"><?php echo htmlspecialchars($row['account_number']); ?></code></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email Client :</div>
                <div class="info-value"><?php echo htmlspecialchars($row['email']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">DÉTAIL DES CHÉQUIERS DEMANDÉS</div>
            <div class="chequier-list">
                <?php foreach ($chequier_types as $type): ?>
                    <div class="chequier-item">
                        <span class="chequier-type"><?php echo htmlspecialchars($type); ?></span>
                        <span class="quantity">1</span>
                    </div>
                <?php endforeach; ?>
                <div class="chequier-item" style="border-bottom: 2px solid #D32F2F; font-weight: bold;">
                    <span>TOTAL CHÉQUIERS</span>
                    <span class="quantity"><?php echo $total_quantity; ?></span>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">DATES</div>
            <div class="info-row">
                <div class="info-label">Date de la Demande :</div>
                <div class="info-value"><?php echo date('d/m/Y à H:i', strtotime($row['created_at'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de Livraison Prévue :</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime('+3 days')); ?></div>
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <strong>Responsable CI</strong>
                <p style="margin-top: 30px; height: 40px;"></p>
            </div>
            <div class="signature-box">
                <strong>Responsable Logistique</strong>
                <p style="margin-top: 30px; height: 40px;"></p>
            </div>
        </div>
        
        <div class="footer">
            <p>Ce bon de livraison est un document officiel. Conservez-le pour vos archives.</p>
            <p>Généré le <?php echo date('d/m/Y à H:i'); ?></p>
        </div>
    </div>
    
    <script>
        window.print();
    </script>
</body>
</html>
