<?php
// Supprime tout buffer et sortie antérieure
@ob_end_clean();
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/config.php';

// Vérifier que l'autoload existe
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("autoload.php non trouvé");
}
require_once $autoloadPath;

// Vérifier PhpSpreadsheet
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    die("PhpSpreadsheet non disponible");
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Récupérer les demandes
$query = "SELECT
            tc.id,
            tc.firstname as customer_name,
            tc.branch_code,
            tc.mobile1 as account_number,
            tc.email,
            tc.type_compte,
            tc.adr_rue as address,
            tc.nip as rib_key,
            tc.etabliss as quantity,
            tc.date_enregistrement as created_at,
            tc.emp_id,
            tb.DepartmentName as agency_name,
            te.FirstName,
            te.LastName,
            te.EmailId as cso_email
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
        WHERE tc.type_compte LIKE '%Feuilles%'
        ORDER BY tc.date_enregistrement DESC";

$res = mysqli_query($conn, $query);
$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}

// Créer le spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Commande chequiers');

// Vider le buffer
ob_clean();

// Envoyer les headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="commande_chequiers_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Police par défaut
$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Largeurs de colonnes
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(40);
$sheet->getColumnDimension('C')->setWidth(40);
$sheet->getColumnDimension('D')->setWidth(50);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(12);
$sheet->getColumnDimension('G')->setWidth(12);
$sheet->getColumnDimension('H')->setWidth(18);
$sheet->getColumnDimension('I')->setWidth(18);
$sheet->getColumnDimension('J')->setWidth(18);
$sheet->getColumnDimension('K')->setWidth(18);
$sheet->getColumnDimension('L')->setWidth(18);
$sheet->getColumnDimension('M')->setWidth(18);

// Titre
$agence_name = !empty($rows[0]['agency_name']) ? $rows[0]['agency_name'] : 'AGENCE';
$sheet->mergeCells('A1:M1');
$sheet->setCellValue('A1', 'COMMANDE DES CHEQUIERS ' . strtoupper($agence_name) . ' DU ' . date('d-m-Y'));
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getRowDimension(1)->setRowHeight(24);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

// En-têtes colonnes
$headers = ['N°','RIB','INTITULE DE COMPTE','ADRESSE','REFERENCE COMPTE','NB CARNETS','NB FEUILLES','N° DE SERIE (de)','N° DE SERIE (à)','DATE RETRAIT','Frais prélevés OUI / NON','Le client a une carte OUI / NON','Client enrôler oui/non'];
$sheet->getRowDimension(3)->setRowHeight(22);

for ($i = 0; $i < count($headers); $i++) {
    $cell = chr(ord('A') + $i) . '3';
    $sheet->setCellValue($cell, $headers[$i]);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
}

// Données
$total_carnets = 0;
$total_feuilles = 0;
$rowNum = 4;

foreach ($rows as $idx => $r) {
    $num = $idx + 1;
    $rib = $r['rib_key'] ?? '';
    $client = $r['customer_name'] ?? '';
    $address = $r['address'] ?? '';
    $ref = !empty($r['account_number']) ? 'Ref Int ' . $r['account_number'] : '';
    $nb_carnets = 1;
    $nb_feuilles = 50;
    
    if (!empty($r['type_compte']) && preg_match('/(25|50|100)/', $r['type_compte'], $m)) {
        $nb_feuilles = (int)$m[1];
    }

    $total_carnets += $nb_carnets;
    $total_feuilles += $nb_feuilles;

    $sheet->setCellValue('A' . $rowNum, $num);
    $sheet->setCellValue('B' . $rowNum, $rib);
    $sheet->setCellValue('C' . $rowNum, $client);
    $sheet->setCellValue('D' . $rowNum, $address);
    $sheet->setCellValue('E' . $rowNum, $ref);
    $sheet->setCellValue('F' . $rowNum, $nb_carnets);
    $sheet->setCellValue('G' . $rowNum, $nb_feuilles);
    $sheet->setCellValue('H' . $rowNum, '');
    $sheet->setCellValue('I' . $rowNum, '');
    $sheet->setCellValue('J' . $rowNum, '');
    $sheet->setCellValue('K' . $rowNum, '');
    $sheet->setCellValue('L' . $rowNum, '');
    $sheet->setCellValue('M' . $rowNum, '');

    $sheet->getStyle("A{$rowNum}:M{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($rowNum)->setRowHeight(18);
    $sheet->getStyle("A{$rowNum}:M{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("C{$rowNum}:D{$rowNum}")->getAlignment()->setWrapText(true);

    $rowNum++;
}

// Ligne totaux
$sheet->mergeCells("A{$rowNum}:E{$rowNum}");
$sheet->setCellValue("A{$rowNum}", 'TOTAL');
$sheet->setCellValue("F{$rowNum}", $total_carnets);
$sheet->setCellValue("G{$rowNum}", $total_feuilles);

$sheet->getStyle("A{$rowNum}:M{$rowNum}")->getFont()->setBold(true);
$sheet->getStyle("A{$rowNum}:M{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$rowNum}:M{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A{$rowNum}:M{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEEEEE');

// Écrire le fichier
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

