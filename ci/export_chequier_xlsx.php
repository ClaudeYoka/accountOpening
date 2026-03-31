<?php

@ob_end_clean();
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/audit_helpers.php';

// Vérifier que l'autoload existe
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("autoload.php non trouvé");
}
require_once $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Log export start
// Log export start
log_admin_action('export_chequier_excel', 0, [
    'export_type' => 'excel',
    'action' => 'start'
]);

// Récupérer les demandes
$query = "SELECT
            tc.id,
            tc.firstname as customer_name,
            tc.account_number,
            tc.adr_rue as address,
            tc.nip as rib_key,
            tc.type_compte,
            tc.etabliss as quantity,
            tc.mobile1 AS phone_number,
            tc.titre as has_card,
            tc.objectif as fees,
            tc.devise_pref as enrolled,
            tc.ident_etud as serial_number,
            COALESCE(tb.DepartmentName, tc.branch_code) as agency_name,
            tc.date_enregistrement as date_retrait,
            tc.branch_code,
            COALESCE(cs.status, 'encours') as current_status
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        LEFT JOIN (
            SELECT request_id, status
            FROM chequier_status cs1
            WHERE cs1.changed_at = (
                SELECT MAX(cs2.changed_at)
                FROM chequier_status cs2
                WHERE cs2.request_id = cs1.request_id
            )
        ) cs ON tc.id = cs.request_id
        WHERE tc.date_enregistrement IS NOT NULL
        AND tc.type_compte IS NOT NULL
        AND tc.type_compte != ''
        AND (cs.status IS NULL OR cs.status = 'encours' OR tc.access = 'encours')
        ORDER BY tc.branch_code, tc.date_enregistrement DESC";

$result = $conn->query($query);

if (!$result) {
    die("Erreur requête: " . $conn->error);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (empty($rows)) {
    die("Aucune donnée disponible (0 lignes)");
}

// Grouper par agence
$agency_groups = [];
foreach ($rows as $row) {
    $agency = humanReadableAgency($row['agency_name'] ?? $row['branch_code'] ?? '');
    if (!isset($agency_groups[$agency])) {
        $agency_groups[$agency] = [];
    }
    $agency_groups[$agency][] = $row;
}

// Créer le spreadsheet AVANT les headers
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Police par défaut
$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

// Largeurs de colonnes
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(40);
$sheet->getColumnDimension('D')->setWidth(40);
$sheet->getColumnDimension('E')->setWidth(50);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(12);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(18);
$sheet->getColumnDimension('J')->setWidth(18);
$sheet->getColumnDimension('K')->setWidth(18);
$sheet->getColumnDimension('L')->setWidth(18);
$sheet->getColumnDimension('M')->setWidth(18);
$sheet->getColumnDimension('N')->setWidth(18);
$sheet->getColumnDimension('O')->setWidth(18);

// Titre
$sheet->mergeCells('A1:O1');
$sheet->setCellValue('A1', 'COMMANDE DES CHEQUIERS DU ' . date('d-m-Y'));
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getRowDimension(1)->setRowHeight(24);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

// En-têtes colonnes
$headers = ['N°','AGENCE','RIB','INTITULE DE COMPTE','ADRESSE','REFERENCE COMPTE','NB CARNETS','NB FEUILLES','N° DE SERIE (de)','N° DE SERIE (à)','DATE RETRAIT','BARRE','Frais prélevés OUI / NON','Le client a une carte OUI / NON','Client enrôler oui/non'];
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
$rowNum = 4;

function humanReadableAgency($codeOrName) {
    $map = [
        'T31' => 'Siège',
        'T32' => 'Lumumba',
        'T33' => 'Atlantic',
        'T34' => 'Poto-Poto',
        'T38' => 'Dolisie',
        'T39' => 'Ouesso',
        'T41' => 'Bacongo',
    ];
    $norm = trim(strtoupper($codeOrName));
    if (isset($map[$norm])) {
        return $map[$norm];
    }
    return $codeOrName;
}

$global_num = 1;
foreach ($agency_groups as $agency_name => $agency_rows) {
    $agency_total_carnets = 0;
    
    foreach ($agency_rows as $idx => $r) {
        $agence = $agency_name;
        $rib = $r['rib_key'] ?? '';
        $client = $r['customer_name'] ?? '';
        $address = $r['address'] ?? '';
        $ref = !empty($r['account_number']) ? 'Ref Int ' . $r['account_number'] : '';
        $nb_carnets = (int)($r['quantity'] ?? 1); // Utiliser la quantité de la base de données
        $nb_feuilles = $r['type_compte'] ?? '';
        
        $agency_total_carnets += $nb_carnets;
        $total_carnets += $nb_carnets;

        $sheet->setCellValue('A' . $rowNum, $global_num);
        $sheet->setCellValue('B' . $rowNum, $agence);
        $sheet->setCellValue('C' . $rowNum, $rib);
        $sheet->setCellValue('D' . $rowNum, $client);
        $sheet->setCellValue('E' . $rowNum, $address);
        $sheet->setCellValue('F' . $rowNum, $ref);
        $sheet->setCellValue('G' . $rowNum, $nb_carnets);
        $sheet->setCellValue('H' . $rowNum, $nb_feuilles);
        $sheet->setCellValue('I' . $rowNum, $r['serial_number'] ?? '');
        $sheet->setCellValue('J' . $rowNum, '=G'.$rowNum.'*H'.$rowNum.'+I'.$rowNum.'-1');
        $sheet->setCellValue('K' . $rowNum, '');
        $sheet->setCellValue('L' . $rowNum, 'BARRE');
        $sheet->setCellValue('M' . $rowNum, $r['fees'] ?? '');
        $sheet->setCellValue('N' . $rowNum, $r['has_card'] ?? '');
        $sheet->setCellValue('O' . $rowNum, $r['enrolled'] ?? '');

        $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($rowNum)->setRowHeight(18);
        $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("D{$rowNum}:E{$rowNum}")->getAlignment()->setWrapText(true);

        $rowNum++;
        $global_num++;
    }
    
    // Sous-total par agence
    $sheet->mergeCells("A{$rowNum}:F{$rowNum}");
    $sheet->setCellValue("A{$rowNum}", 'SOUS-TOTAL ' . strtoupper($agency_name));
    $sheet->setCellValue("G{$rowNum}", $agency_total_carnets);
    
    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getFont()->setBold(true);
    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
    
    $rowNum++;
}

// Ligne totaux généraux
$sheet->mergeCells("A{$rowNum}:F{$rowNum}");
$sheet->setCellValue("A{$rowNum}", 'TOTAL GÉNÉRAL');
$sheet->setCellValue("G{$rowNum}", $total_carnets);

$sheet->getStyle("A{$rowNum}:O{$rowNum}")->getFont()->setBold(true);
$sheet->getStyle("A{$rowNum}:O{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$rowNum}:O{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A{$rowNum}:O{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEEEEE');

// Envoyer les headers MAINTENANT avant d'écrire
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="commande_chequiers_' . date('Ymd_s') . '.xlsx"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: -1');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: ' . md5(microtime()));
header('Connection: close');

// Écrire le fichier
$writer = new Xlsx($spreadsheet);

// Log successful export completion
// Log successful export completion
log_admin_action('export_chequier_excel', 0, [
    'export_type' => 'excel',
    'action' => 'complete',
    'total_records' => count($rows),
    'total_carnets' => $total_carnets
]);

ob_end_clean(); // Clear buffer for binary output
$writer->save('php://output');
exit;

