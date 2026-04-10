<?php
include('../includes/session.php');
include('../includes/config.php');
require '../vendor/autoload.php'; // Inclure l'autoloader de Composer

use Dompdf\Dompdf;
use Dompdf\Options;

// Fonction pour traduire les mois en français
function translateMonthToFrench($date) {
    $months = [
        'January' => 'Janvier',
        'February' => 'Février',
        'March' => 'Mars',
        'April' => 'Avril',
        'May' => 'Mai',
        'June' => 'Juin',
        'July' => 'Juillet',
        'August' => 'Août',
        'September' => 'Septembre',
        'October' => 'Octobre',
        'November' => 'Novembre',
        'December' => 'Décembre'
    ];
    
    // Convertir la date en format DateTime
    $dateTime = new DateTime($date);
    
    // Formater la date
    $formattedDate = $dateTime->format('d F Y');
    
    // Remplacer le mois en anglais par le mois en français
    foreach ($months as $english => $french) {
        if (strpos($formattedDate, $english) !== false) {
            $formattedDate = str_replace($english, $french, $formattedDate);
            break;
        }
    }
    
    return $formattedDate;
}

// Récupération des données
$did = intval($_GET['leaveid']);
$sql = "SELECT tblleave.id as lid, tblemployees.FirstName, tblemployees.LastName, tblemployees.Department, tblemployees.cumulative_days, tblleave.LeaveType, tblleave.ToDate, tblleave.FromDate, tblleave.PostingDate, tblleave.RequestedDays, tblleave.DaysOutstand, tblleave.RemainingDays, tblleave.WorkCovered, tblleave.RegRemarks, tblleave.HodRemarks FROM tblleave JOIN tblemployees ON tblleave.empid = tblemployees.emp_id WHERE tblleave.id='$did'";
$query = mysqli_query($conn, $sql) or die(mysqli_error($conn));
$row = mysqli_fetch_array($query);

// Récupération des données
$firstname = $row['FirstName'];
$lastname = $row['LastName'];
$department = $row['Department'];
$leave_type = $row['LeaveType'];
$start_date = translateMonthToFrench($row['FromDate']); // Formatage en français
$end_date = translateMonthToFrench($row['ToDate']); // Formatage en français
$posted_date = translateMonthToFrench($row['PostingDate']); // Formatage en français
$num_days_requested = $row['RequestedDays'];
$outstanding_days = $row['DaysOutstand'];
$work_cover = $row['WorkCovered'];
$cumulative_days = $row['cumulative_days'];
$RemainingDays = $row['RemainingDays'];
$stats = $row['RegRemarks'];
$stats2 = $row['HodRemarks'];
$totalday = $RemainingDays + $num_days_requested;
$totaldayCumul = $RemainingDays + $num_days_requested + $cumulative_days;
$currentYear = date('Y');

// Création d'une instance de Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true); // Permettre les images distantes
$dompdf = new Dompdf($options);

// Chemin de votre image (à adapter)
$logoPath = '../vendors/images/ecobank-bg.png';


// Convertir l'image en base64 pour l'inclure directement
$logoData = base64_encode(file_get_contents($logoPath));
$logoMime = mime_content_type($logoPath);
$logoBase64 = 'data:'.$logoMime.';base64,'.$logoData;

// Contenu HTML du PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>FICHE DE DEMANDE DE CONGES</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            position: relative;
            margin: 0;
            padding: 0;
        }

        /* En-tête avec image à droite */
        .header {
            position: relative;
            margin-bottom: 0px; /* Espacement accru pour le titre */
        }

        .header img {
            width: 200px;
            height: auto;
            position: absolute;
            right: 0;
            top: 0;
        }
            

        /* Titre encadré */
        .title-box {
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            margin-top: 70px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .title-box h1 {
            margin: 0;
            color: #333;
        }

        .form-label {
            font-weight: bold;
            display: inline-block;
            min-width: 250px;
            margin: 10px;
            vertical-align: top;
        }


        .content {
            margin: 0px;
        }

        .section {
            margin-bottom: 15px;
        }

        .header-message {
            text-align: left; /* Centrer le texte */
            font-size: 18px; /* Taille de la police */
            font-weight: bold; /* Mettre le texte en gras */
            margin-top: 10px; /* Espacement au-dessus du message */
            color: #333; /* Couleur du texte */
        }

        /* Filigrane */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.1;
            font-size: 80px;
            color: #cccccc;
            z-index: -1;
        }

        /* Section des signatures */
        .signature-section {
            margin-top: 55px;
            width: 100%;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 50px;
        }

        .signature-block {
            width: 95%;
        }

        .signature-left {
            text-align: left;
        }

        .signature-right {
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 30px;
            padding-top: 10px;
            display: inline;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="'.$logoBase64.'" >
        Internal User Only
        <div class="title-box">
            <h1>FICHE DE DEMANDE DE CONGÉS</h1>
        </div>
    </div>
    <img class="watermark" src="'.$logoBase64.'" alt="Filigrane">
    <div class="content">
            <p class="form-label">DATE : ' . translateMonthToFrench(date('Y-m-d')) . '</p> <br>
        <div class="section">
            <p class="form-label">NOM DU DEMANDEUR : </p> ' . strtoupper($firstname . ' ' . $lastname) .'<br>
            <p class="form-label">DEPARTEMENT :</p> ' . strtoupper($department) . '<br>
            <p class="form-label">NATURE DU CONGE :</p>    ' . $leave_type . '<br>
            <p class="form-label">NOMBRE DE JOURS AUTORISES POUR L’ANNEE '.$currentYear.':   ' . $totalday . ' </p> <br>
            <p class="form-label">CUMULS ANTERIEURS :</p> ' . $cumulative_days . '<br>
            <p class="form-label">NOMBRE DE JOURS DEMANDES :    ' . $num_days_requested . '</p> <br>
            <p class="form-label">Date de Départ :</p> ' . $start_date . '<br>
            <p class="form-label">Date de Reprise :</p> ' . $end_date . '<br>
            <p class="form-label">RELIQUAT :</p> ' . $RemainingDays . '<br>
            <p class="form-label">TOTAL :</p> ' . $totaldayCumul . '<br>
            <p class="form-label">Décision du HEAD : </p> ' . ($stats2 == 1 ? 'Approuvée' : ($stats2 == 2 ? 'Rejtée' : 'En cours')) . '<br>
            <p class="form-label">Décision des Ressources Humaines :</p> '. ($stats == 1 ? 'Approuvée' : ($stats == 2 ? 'non Aprouvée' : 'En cours')) .  '<br>
            <p class="form-label">REMPLACEMENT PREVU PAR :</p> ' . $work_cover . '<br>
        </div>
        <div class="signature-section">
            <div class="signature-row">
                <div class="signature-block signature-left">
                    <div class="signature-line">L’EMPLOYE</div>
                </div>
                <div class="signature-block signature-right">
                    <div class="signature-line">LE REMPLACANT</div>
                </div>
            </div>
            <div class="signature-row">
                <div class="signature-block signature-left">
                    <div class="signature-line">LE CHEF DE SCE/DEPT</div>
                </div>
                <div class="signature-block signature-right">
                    <div class="signature-line">RESSOURCES HUMAINES</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Ajout optionnel d'un filigrane par dessin (alternative au filigrane HTML)
$canvas = $dompdf->getCanvas();
$canvas->set_opacity(.3,"Multiply");
$canvas->text(150, 400, "DOCUMENT INTERNE", null, 45, array(200,200,200), 0, 0, -45);

$dompdf->stream('fiche_conge_'.$lastname.'.pdf', ['Attachment' => false]);
