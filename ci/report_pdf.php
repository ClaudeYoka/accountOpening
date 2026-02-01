<?php
include('../includes/session.php');
include('../includes/config.php');
require_once('../TCPDF-main/tcpdf.php');

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
$did = intval($_GET['leave_id']);
$sql = "SELECT tblleave.id as lid, tblemployees.FirstName, tblemployees.LastName, tblemployees.Department,tblemployees.cumulative_days, tblleave.LeaveType, tblleave.ToDate, tblleave.FromDate, tblleave.PostingDate, tblleave.RequestedDays, tblleave.DaysOutstand,tblleave.RemainingDays, tblleave.WorkCovered FROM tblleave JOIN tblemployees ON tblleave.empid = tblemployees.emp_id WHERE tblleave.id='$did'";
$query = mysqli_query($conn, $sql) or die(mysqli_error());
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
$totalday= $cumulative_days + $num_days_requested;

// Classe PDF
class PDF extends TCPDF
{
    public function Header()
    {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 10, 'FICHE DE DEMANDE DE CONGES', 0, 1, 'C');
        $this->Ln(5);
    }

	
}

// Création du PDF
$pdf = new PDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('AO & KYC');
$pdf->SetTitle('FICHE DE DEMANDE DE CONGES');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();


// Contenu du PDF
$pdf->SetFont('times', '', 12);

// Date
$pdf->Cell(0, 10, '                                               DATE : ' . translateMonthToFrench(date('Y-m-d')), 0, 1);
$pdf->Ln(5);

// Nom du demandeur
$pdf->Cell(0, 10, 'NOM DU DEMANDEUR : ' . strtoupper($firstname . ' ' . $lastname), 0, 1);
$pdf->Cell(0, 10, 'DEPARTEMENT : ' . strtoupper($department), 0, 1);
$pdf->Ln(5);

// Nature du congé
$pdf->Cell(0, 10, 'NATURE DU CONGE : ' . $leave_type, 0, 1);
// $pdf->Cell(0, 10, 'CONGES PAYES ANNEE : ', 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'NOMBRE DE JOURS AUTORISES POUR L’ANNEE : ' .$outstanding_days, 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'CUMULS ANTERIEURS : ' . $cumulative_days, 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'NOMBRE DE JOURS DEMANDES : ' . $num_days_requested, 0, 1);
$pdf->Cell(0, 10, 'Date de Départ : ' . $start_date, 0, 1);
$pdf->Cell(0, 10, 'Date de fin : ' . $end_date, 0, 1);
$pdf->Cell(0, 10, 'Date de Reprise : ' . translateMonthToFrench((new DateTime($row['ToDate']))->modify('+14 days')->format('Y-m-d')), 0, 1); // Exemple de date de reprise
$pdf->Cell(0, 10, 'NOMBRE DE JOURS CONSOMMES : ' . $num_days_requested, 0, 1);
$pdf->Cell(0, 10, 'RELIQUAT : ' . $RemainingDays, 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'TOTAL : ' . $totalday, 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'CONGES EXCEPTIONNELS : OUI         NON', 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'CONGES SANS SOLDE : OUI            NON', 0, 1); // Remplacez par la valeur réelle
$pdf->Cell(0, 10, 'REMPLACEMENT PREVU PAR : ' . $work_cover, 0, 1);
$pdf->Ln(10);
$pdf->SetFont('times','B', 12);
$pdf->Cell(130, 6, 'L’EMPLOYE  ', 0, 0);
$pdf->Cell(59, 6, 'LE REMPLACANT  ');
$pdf->Ln(30);
$pdf->Cell(130, 6, 'LE CHEF DE SCE/DEPT  ', 0, 0);
$pdf->Cell(59, 6, 'RESSOURCES HUMAINES  ');
// Sortie du PDF
$pdf->Output('fiche_demande_conge.pdf', 'I');
