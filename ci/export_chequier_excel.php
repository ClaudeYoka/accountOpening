<?php
include('../includes/session.php');
include('../includes/config.php');

// Query tblcompte for chequier requests
$query = "SELECT
            tc.id,
            tc.firstname as customer_name,
            tc.branch_code,
            tc.account_number as account_number,
            tc.mobile1 as phone_number,
            tc.email,
            tc.type_compte,
            tc.adr_rue as address,
            tc.nip as rib_key,
            tc.etabliss as quantity,
            tc.date_enregistrement as created_at,
            tc.emp_id,
            tc.titre as has_card,
            tc.objectif as fees,
            tc.devise_pref as enrolled,
            tc.ident_etud as serial_number,
            COALESCE(tb.DepartmentName, tc.branch_code) as agency_name,
            te.FirstName,
            te.LastName,
            te.EmailId as cso_email
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
        WHERE tc.type_compte IS NOT NULL AND tc.type_compte != ''
        ORDER BY tc.date_enregistrement DESC";;

$result = mysqli_query($conn, $query);

$rows = array();
if ($result && mysqli_num_rows($result) > 0) {
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
}

// Prepare Excel (HTML) output
$filename = 'commande_chequiers_' . date('Ymd') . '.xls';
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename={$filename}");
// UTF-8 BOM
echo "\xEF\xBB\xBF";

function extract_first_feuille($type_compte) {
    if (empty($type_compte)) return '';
    if (preg_match('/(25|50|100)/', $type_compte, $m)) return $m[1];
    return '';
}

// Build HTML table matching the requested layout
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style>
    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size:12px; }
    td, th { border: 1px solid #000; padding: 6px; }
    .no-border { border: none; }
    .title { font-weight: bold; font-size:14px; }
    .center { text-align:center; }
</style>
</head>
<body>
<table width="100%">
    <tr>
        <td colspan="13" style="border:none; font-size:16px; font-weight:bold;">COMMANDE DES CHEQUIERS</td>
        <td style="border:none; text-align:right;"><?php echo date('d-m-Y'); ?></td>
    </tr>
</table>
<br>
<table>
    <tr>
        <th style="width:30px;">N°</th>
        <th style="width:200px;">AGENCE</th>
        <th style="width:220px;">RIB</th>
        <th style="width:300px;">INTITULE DE COMPTE</th>
        <th style="width:300px;">ADRESSE</th>
        <th style="width:160px;">REFERENCE COMPTE</th>
        <th style="width:80px;">NB CARNETS</th>
        <th style="width:80px;">NB FEUILLES</th>
        <th style="width:120px;">N° DE SERIE (de)</th>
        <th style="width:120px;">N° DE SERIE (à)</th>
        <th style="width:120px;">DATE RETRAIT</th>
        <th style="width:120px;">Frais prélevés OUI / NON</th>
        <th style="width:120px;">Le client a une carte OUI / NON</th>
        <th style="width:120px;">Client enrôler oui/non</th>
    </tr>
<?php
$i = 1;
foreach ($rows as $r) {
    $agence = htmlspecialchars($r['agency_name'] ?? '');
    $rib = htmlspecialchars($r['rib_key'] ?? '');
    $client = htmlspecialchars($r['customer_name'] ?? '');
    $address = htmlspecialchars($r['address'] ?? '');
    $ref = !empty($r['account_number']) ? 'Ref Int ' . htmlspecialchars($r['account_number']) : '';
    $nb_carnets = 1;
    $nb_feuilles = '';
    // Fill with actual data from new columns
    $serie_de = htmlspecialchars($r['serial_number'] ?? '');
    $serie_a = '';
    $date_retrait = '';
    $frais = htmlspecialchars($r['fees'] ?? '');
    $has_card = htmlspecialchars($r['has_card'] ?? '');
    $enrolled = htmlspecialchars($r['enrolled'] ?? '');
    echo "<tr>\n";
    // compute feuilles display from type_compte
    // Afficher directement la valeur de type_compte sans transformation
    $nb_feuilles = $r['type_compte'] ?? '';

    echo "<td class=\"center\">{$i}</td>\n";
    echo "<td>" . $agence . "</td>\n";
    echo "<td>" . $rib . "</td>\n";
    echo "<td>" . $client . "</td>\n";
    echo "<td>" . $address . "</td>\n";
    echo "<td>" . $ref . "</td>\n";
    echo "<td class=\"center\">" . $nb_carnets . "</td>\n";
    echo "<td class=\"center\">" . $nb_feuilles . "</td>\n";
    echo "<td class=\"center\">" . $serie_de . "</td>\n";
    echo "<td class=\"center\">" . $serie_a . "</td>\n";
    echo "<td class=\"center\">" . $date_retrait . "</td>\n";
    echo "<td class=\"center\">" . $frais . "</td>\n";
    echo "<td class=\"center\">" . $has_card . "</td>\n";
    echo "<td class=\"center\">" . $enrolled . "</td>\n";
    echo "</tr>\n";
    $i++;
}
?>
</table>
</body>
</html>
