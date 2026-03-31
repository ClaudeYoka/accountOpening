<?php
    include('includes/header.php');
    include('../includes/session.php');

    // Validation de l'ID de la notification
    if (!isset($_GET['id'])) {
        header("Location: index.php");
        exit();
    }

    $notification_id = intval($_GET['id']);

    // Vérifier que l'ID est valide
    if ($notification_id <= 0) {
        header("Location: index.php");
        exit();
    }

    // Récupérer les détails de la notification
    $stmt = mysqli_prepare($conn, "SELECT n.*, e.FirstName, e.LastName 
            FROM tblnotification n 
            LEFT JOIN tblemployees e ON n.emp_id = e.emp_id 
            WHERE n.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $notification_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notification = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Si la notification n'existe pas
    if (!$notification) {
        header("Location: index.php");
        exit();
    }

    // Mettre à jour le statut is_read si ce n'est pas déjà fait
    if ($notification['is_read'] == 0) {
        $update_stmt = mysqli_prepare($conn, "UPDATE tblnotification SET is_read = 1 WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "i", $notification_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }

    $pageTitle = "Détails de la notification";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .notification-container {
            max-width: 900px;
            margin: 40px auto;
        }

        .notification-header {
            background: linear-gradient(135deg, #042852 0%, #017ac5 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .notification-header a {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.1);
        }

        .notification-header a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .notification-content {
            background: white;
            padding: 40px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .notification-sender {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .sender-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }

        .sender-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .sender-info p {
            font-size: 13px;
            color: #888;
        }

        .notification-section {
            margin-bottom: 30px;
        }

        .notification-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin-right: 10px;
            border-radius: 2px;
        }

        .notification-message {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #017ac5;
            color: #555;
            line-height: 1.6;
            font-size: 14px;
        }

        .notification-metadata {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .metadata-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #017ac5;
        }

        .metadata-label {
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .metadata-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .additional-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #17a2b8;
        }

        .additional-info pre {
            background: white;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
            color: #555;
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .notification-type {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .read-status {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
            margin-left: 8px;
        }

        @media (max-width: 768px) {
            .notification-header {
                flex-direction: column;
                gap: 15px;
            }

            .notification-content {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .metadata-item {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="notification-container">
        <div class="notification-header">
            <h1>📬 Détails de la notification</h1>
            <a href="javascript:history.back()" title="Retour">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
        </div>

        <div class="notification-content">
            <!-- Informations de l'expéditeur -->
            <div class="notification-sender">
                <div class="sender-avatar">
                    <?php echo htmlspecialchars(strtoupper(substr($notification['FirstName'] ?? 'N', 0, 1))); ?>
                </div>
                <div class="sender-info">
                    <h3><?php echo htmlspecialchars($notification['FirstName'] . ' ' . $notification['LastName']); ?></h3>
                    <p>
                        <?php 
                            $date = new DateTime($notification['created_at']);
                            echo htmlspecialchars($date->format('d/m/Y à H:i'));
                        ?>
                        <span class="read-status"></span>
                    </p>
                </div>
            </div>

            <!-- Type de notification -->
            <?php if (!empty($notification['type'])): ?>
            <div class="notification-type">
                <?php 
                    $type_labels = [
                        'chequier_request' => '📋 Demande de Chéquier',
                        'account_opening' => '🏦 Ouverture de Compte',
                        'approval' => '✅ Approbation',
                        'rejection' => '❌ Rejet',
                        'update' => '🔄 Mise à Jour',
                        'alert' => '⚠️ Alerte',
                        'info' => 'ℹ️ Information'
                    ];
                    echo htmlspecialchars($type_labels[$notification['type']] ?? ucfirst($notification['type']));
                ?>
            </div>
            <?php endif; ?>

            <!-- Message principal -->
            <div class="notification-section">
                <div class="section-title">Message</div>
                <div class="notification-message">
                    <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                </div>
            </div>

            <!-- Informations metadata -->
            <div class="notification-section">
                <div class="notification-metadata">
                    <div class="metadata-item">
                        <div class="metadata-label">📅 Date de création</div>
                        <div class="metadata-value">
                            <?php 
                                $date = new DateTime($notification['created_at']);
                                echo htmlspecialchars($date->format('d/m/Y H:i:s'));
                            ?>
                        </div>
                    </div>
                    <div class="metadata-item">
                        <div class="metadata-label">👤 ID Employé</div>
                        <div class="metadata-value"><?php echo htmlspecialchars($notification['emp_id'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="metadata-item">
                        <div class="metadata-label">📌 ID Notification</div>
                        <div class="metadata-value"><?php echo htmlspecialchars($notification['id']); ?></div>
                    </div>
                    <div class="metadata-item">
                        <div class="metadata-label">✓ État de lecture</div>
                        <div class="metadata-value">
                            <?php echo $notification['is_read'] ? '✅ Lu' : '⭕ Non lu'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Données additionnelles si présentes -->
            <?php if (!empty($notification['additional_data'])): ?>
            <div class="notification-section">
                <div class="section-title">Informations Supplémentaires</div>
                <div class="additional-info">
                    <pre><?php echo htmlspecialchars($notification['additional_data']); ?></pre>
                </div>
            </div>
            <?php endif; ?>

            <!-- Boutons d'action -->
            <div class="action-buttons">
                <button onclick="window.history.back()" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Retour
                </button>
                <a href="delete_notification.php?id=<?php echo htmlspecialchars($notification['id']); ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette notification ?');" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14zM10 11v6M14 11v6"/>
                    </svg>
                    Supprimer
                </a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    function markAsRead(notificationId) {
        // La notification est déjà marquée comme lue au chargement
        // Cette fonction est conservée pour compatibilité
    }

    // Animation au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.notification-container');
        container.style.animation = 'slideUp 0.4s ease-out';
    });
    </script>

    <style>
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>
