<?php
    include('../includes/config.php');
    include '../includes/session.php';

    $notification_id = $_GET['id'];

    // Récupérer les détails de la notification
    $query = "SELECT n.*, e.firstname, e.lastname 
            FROM tblnotification n 
            JOIN tblemployees e ON n.emp_id = e.emp_id 
            WHERE n.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notification = $result->fetch_assoc();

    // Si la notification n'existe pas
    if (!$notification) {
        header("Location: index.php");
        exit();
    }

    // Mettre à jour le statut is_read si ce n'est pas déjà fait
    if ($notification['is_read'] == 0) {
        $update = $conn->prepare("UPDATE tblnotification SET is_read = 1 WHERE id = ?");
        $update->bind_param("i", $notification_id);
        $update->execute();
    }

    $pageTitle = "Détails de la notification";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/notifications.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 px-6 py-4 flex items-center justify-between">
                <h1 class="text-white text-xl font-semibold">Détails de la notification</h1>
                <a href="javascript:history.back()" class="text-white hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            </div>

            <div class="px-6 py-4">
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700"><?php echo htmlspecialchars($notification['firstname'] . ' ' . $notification['lastname']); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php 
                                    $date = new DateTime($notification['created_at']);
                                    echo htmlspecialchars($date->format('d/m/Y à H:i'));
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Message :</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                    </div>

                    <?php if (!empty($notification['additional_data'])): ?>
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Informations supplémentaires :</h3>
                        <pre class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($notification['additional_data']); ?></pre>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex justify-between items-center border-t pt-4">
                    <button onclick="window.history.back()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Retour
                    </button>
                    <div class="flex space-x-2">
                        <a href="delete_notification.php?id=<?php echo $notification['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette notification ?');" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Supprimer
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    function markAsRead(notificationId) {
        // Envoi d'une requête AJAX pour mettre à jour la notification comme lue
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "/includes/mark_as_read.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    // Mise à jour du compteur de notifications
                    var currentCount = parseInt(document.getElementById("notification-count").innerText);
                    document.getElementById("notification-count").innerText = currentCount - 1;

                    // Optionnel : Retirer la notification de la liste
                    var notificationItem = document.querySelector(`a[href='notification_details?id=${notificationId}']`).parentElement;
                    notificationItem.remove();
                }
            }
        };
        xhr.send("id=" + notificationId);
    }
</script>
</body>
</html>
