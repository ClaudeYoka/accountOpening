<?php
// API endpoint pour récupérer les statistiques mensuelles des demandes de chéquiers par agence (Admin)

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';


header('Content-Type: application/json');

$year = isset($_GET['year']) ? max(2025, intval($_GET['year'])) : date('Y');

try {
    $availableYears = array();
    $yearsQuery = mysqli_query($conn, "
        SELECT DISTINCT YEAR(tc.date_enregistrement) AS yr
        FROM tblcompte tc
        WHERE tc.date_enregistrement IS NOT NULL
        AND tc.type_compte IS NOT NULL
        AND TRIM(tc.type_compte) <> ''
        ORDER BY yr
    ");

    if ($yearsQuery) {
        while ($row = mysqli_fetch_assoc($yearsQuery)) {
            $availableYears[] = (int) $row['yr'];
        }
    }

    if (empty($availableYears)) {
        $availableYears[] = $year;
    }

    if (!in_array($year, $availableYears, true)) {
        $year = end($availableYears);
    }

    $query = mysqli_query($conn, "
        SELECT 
            UPPER(TRIM(tc.branch_code)) AS branch_code,
            MONTH(tc.date_enregistrement) AS month_num,
            COUNT(*) AS count
        FROM tblcompte tc
        WHERE YEAR(tc.date_enregistrement) = $year
        AND tc.type_compte IS NOT NULL
        AND TRIM(tc.type_compte) <> ''
        AND UPPER(TRIM(tc.branch_code)) IN ('T31', 'T32', 'T33', 'T34', 'T38', 'T39', 'T41')
        GROUP BY UPPER(TRIM(tc.branch_code)), MONTH(tc.date_enregistrement)
        ORDER BY UPPER(TRIM(tc.branch_code)), MONTH(tc.date_enregistrement)
    ");

    if (!$query) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    $agencies = array('T31', 'T32', 'T33', 'T34', 'T38', 'T39', 'T41');
    $data = array();
    foreach ($agencies as $agency) {
        $data[$agency] = array_fill(1, 12, 0);
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $agency = strtoupper(trim($row['branch_code'] ?? ''));
        $month = (int) $row['month_num'];
        $count = (int) $row['count'];

        if (isset($data[$agency])) {
            $data[$agency][$month] = $count;
        }
    }

    $colors = array(
        'T31' => 'rgba(211, 47, 47, 0.8)',
        'T32' => 'rgba(25, 118, 210, 0.8)',
        'T33' => 'rgba(10, 173, 179, 0.8)',
        'T34' => 'rgba(245, 124, 0, 0.8)',
        'T38' => 'rgba(248, 205, 15, 0.8)',
        'T39' => 'rgba(123, 31, 162, 0.8)',
        'T41' => 'rgba(56, 142, 60, 0.8)'
    );

    $agencyNames = array(
        'T31' => 'SIÈGE',
        'T32' => 'LUMUMBA',
        'T33' => 'ATLANTIC',
        'T34' => 'POTO-POTO',
        'T38' => 'DOLISIE',
        'T39' => 'OUESSO',
        'T41' => 'BACONGO'
    );

    $datasets = array();
    foreach ($agencies as $agency) {
        $monthData = array();
        for ($m = 1; $m <= 12; $m++) {
            $monthData[] = $data[$agency][$m];
        }

        $datasets[] = array(
            'label' => 'Agence ' . $agencyNames[$agency],
            'data' => $monthData,
            'borderColor' => $colors[$agency],
            'backgroundColor' => str_replace('0.8', '0.1', $colors[$agency]),
            'borderWidth' => 2,
            'fill' => true,
            'tension' => 0.4,
            'pointRadius' => 4,
            'pointHoverRadius' => 6,
            'pointBackgroundColor' => $colors[$agency],
            'pointBorderColor' => '#fff',
            'pointBorderWidth' => 2
        );
    }

    echo json_encode(array(
        'success' => true,
        'year' => $year,
        'labels' => array('Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'),
        'datasets' => $datasets
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
