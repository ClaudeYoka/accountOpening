<?php
    function buildDateString($day, $month, $year)
    {
        $d = (int) $day;
        $m = (int) $month;
        $y = (int) $year;

        if ($d <= 0 || $m <= 0 || $y <= 0) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    function computeMonthsBetweenDates($startDate, $endDate)
    {
        if ($startDate === '' || $endDate === '') {
            return 1;
        }

        try {
            $start = new DateTimeImmutable($startDate);
            $end = new DateTimeImmutable($endDate);
        } catch (Exception $e) {
            return 1;
        }

        if ($end < $start) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }

        $months = ((int) $end->format('Y') - (int) $start->format('Y')) * 12 + ((int) $end->format('n') - (int) $start->format('n'));

        if ($end->format('d') < $start->format('d')) {
            $months = max(1, $months - 1);
        }

        return max(1, $months);
    }

$account = isset($_GET['account']) ? trim((string) $_GET['account']) : '';
$startDay = isset($_GET['de_jour']) ? trim((string) $_GET['de_jour']) : '';
$startMonth = isset($_GET['de_mois']) ? trim((string) $_GET['de_mois']) : '';
$startYear = isset($_GET['de_annee']) ? trim((string) $_GET['de_annee']) : '';
$endDay = isset($_GET['a_jour']) ? trim((string) $_GET['a_jour']) : '';
$endMonth = isset($_GET['a_mois']) ? trim((string) $_GET['a_mois']) : '';
$endYear = isset($_GET['a_annee']) ? trim((string) $_GET['a_annee']) : '';

$startDate = buildDateString($startDay, $startMonth, $startYear);
$endDate = buildDateString($endDay, $endMonth, $endYear);
$months = computeMonthsBetweenDates($startDate, $endDate);
$total = $months * 2000;

$periodLabel = '';
if ($startDate !== '' && $endDate !== '') {
    $periodLabel = date('d/m/Y', strtotime($startDate)) . ' à ' . date('d/m/Y', strtotime($endDate));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de relevé - Ecobank</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #edf2f7;
            color: #1b1b1b;
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 18px;
        }
        .card {
            width: 100%;
            max-width: 760px;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
            padding: 28px 24px;
        }
        h1 {
            margin: 0 0 10px;
            color: #0e5d88;
            font-size: 30px;
            text-align: center;
        }
        .subtitle {
            margin: 0 0 24px;
            text-align: center;
            color: #555;
            font-size: 15px;
        }
        .field {
            margin-bottom: 22px;
        }
        .field-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(80px, 1fr));
            gap: 12px;
        }
        .label-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: 700;
            color: #0d2e47;
        }
        .small-label {
            font-size: 12px;
            color: #4d4d4d;
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cfe0ec;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .date-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .box {
            padding: 18px 16px;
            border: 1px solid #dfeaf3;
            background: #f6fafd;
            border-radius: 10px;
        }
        .amount-box {
            margin-top: 6px;
            padding: 14px 16px;
            border-radius: 8px;
            background: #eef8fb;
            border: 1px solid #d8eaf2;
            color: #0e587b;
            font-weight: 700;
        }
        .btn {
            width: 100%;
            padding: 14px 18px;
            border: none;
            border-radius: 10px;
            background: #0e5d88;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 14px;
            transition: background 0.2s ease;
        }
        .btn:hover {
            background: #0a4b70;
        }
        .back-btn {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 11px 18px;
            border: 1px solid #0e5d88;
            border-radius: 10px;
            background: #fff;
            color: #0e5d88;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
        }
        .hint {
            margin-top: 10px;
            font-size: 13px;
            color: #5a5a5a;
        }
        .loader {
            position: fixed;
            inset: 0;
            background: rgba(12, 28, 37, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loader.show {
            display: flex;
        }
        .spinner {
            width: 68px;
            height: 68px;
            border: 6px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 640px) {
            .date-row {
                grid-template-columns: 1fr;
            }
            .field-grid {
                grid-template-columns: repeat(3, minmax(60px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="loader" id="loader">
        <div class="spinner" aria-label="Chargement"></div>
    </div>

    <div class="wrap">
        <div class="card">
            <h1>Demande de relevé</h1>
            <p class="subtitle">Saisissez le compte et la période demandée</p>

            <form id="releve-form" method="get" action="demande_releve.html">
                <div class="field">
                    <label class="small-label" for="account">Numéro de compte</label>
                    <input id="account" name="account" type="text" value="<?php echo htmlspecialchars($account, ENT_QUOTES); ?>" placeholder="Ex : 0012345678" required>
                </div>

                <div class="field">
                    <div class="date-row">
                        <div class="box">
                            <div class="label-wrap">
                                <span>De</span>
                            </div>
                            <div class="field-grid">
                                <div>
                                    <label class="small-label" for="de_jour">Jour</label>
                                    <input id="de_jour" name="de_jour" type="number" min="1" max="31" placeholder="JJ" value="<?php echo htmlspecialchars((string) ($_GET['de_jour'] ?? ''), ENT_QUOTES); ?>" required>
                                </div>
                                <div>
                                    <label class="small-label" for="de_mois">Mois</label>
                                    <input id="de_mois" name="de_mois" type="number" min="1" max="12" placeholder="MM" value="<?php echo htmlspecialchars((string) ($_GET['de_mois'] ?? ''), ENT_QUOTES); ?>" required>
                                </div>
                                <div>
                                    <label class="small-label" for="de_annee">Année</label>
                                    <input id="de_annee" name="de_annee" type="number" min="2000" max="2100" placeholder="AAAA" value="<?php echo htmlspecialchars((string) ($_GET['de_annee'] ?? ''), ENT_QUOTES); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="box">
                            <div class="label-wrap">
                                <span>À</span>
                            </div>
                            <div class="field-grid">
                                <div>
                                    <label class="small-label" for="a_jour">Jour</label>
                                    <input id="a_jour" name="a_jour" type="number" min="1" max="31" placeholder="JJ" value="<?php echo htmlspecialchars((string) ($_GET['a_jour'] ?? ''), ENT_QUOTES); ?>" required>
                                </div>
                                <div>
                                    <label class="small-label" for="a_mois">Mois</label>
                                    <input id="a_mois" name="a_mois" type="number" min="1" max="12" placeholder="MM" value="<?php echo htmlspecialchars((string) ($_GET['a_mois'] ?? ''), ENT_QUOTES); ?>" required>
                                </div>
                                <div>
                                    <label class="small-label" for="a_annee">Année</label>
                                    <input id="a_annee" name="a_annee" type="number" min="2000" max="2100" placeholder="AAAA" value="<?php echo htmlspecialchars((string) ($_GET['a_annee'] ?? ''), ENT_QUOTES); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="months" name="months" value="<?php echo (int) $months; ?>">
                <input type="hidden" id="total" name="total" value="<?php echo (int) $total; ?>">
                <input type="hidden" id="period" name="period" value="<?php echo htmlspecialchars($periodLabel, ENT_QUOTES); ?>">

                <div class="amount-box" id="amountPreview">
                    Montant estimé : <?php echo number_format($total, 0, ',', ' '); ?> FCFA
                </div>

                <div class="hint">Le montant total est calculé selon : 2 000 FCFA × nombre de mois entre la date de début et la date de fin.</div>

                <button type="submit" class="btn">Générer</button>
                <a class="back-btn" href="javascript:history.back()">Retour</a>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('releve-form');
            const loader = document.getElementById('loader');
            const monthHidden = document.getElementById('months');
            const totalHidden = document.getElementById('total');
            const periodHidden = document.getElementById('period');
            const amountPreview = document.getElementById('amountPreview');

            function computeMonths() {
                const start = new Date(
                    document.getElementById('de_annee').value,
                    Number(document.getElementById('de_mois').value) - 1,
                    Number(document.getElementById('de_jour').value)
                );
                const end = new Date(
                    document.getElementById('a_annee').value,
                    Number(document.getElementById('a_mois').value) - 1,
                    Number(document.getElementById('a_jour').value)
                );

                if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
                    return 1;
                }

                let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                if (end.getDate() < start.getDate()) {
                    months = Math.max(1, months - 1);
                }

                return Math.max(1, months);
            }

            function formatMoney(value) {
                return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
            }

            function updateSummary() {
                const dDay = document.getElementById('de_jour').value;
                const dMonth = document.getElementById('de_mois').value;
                const dYear = document.getElementById('de_annee').value;
                const aDay = document.getElementById('a_jour').value;
                const aMonth = document.getElementById('a_mois').value;
                const aYear = document.getElementById('a_annee').value;

                if (!dDay || !dMonth || !dYear || !aDay || !aMonth || !aYear) {
                    return;
                }

                const months = computeMonths();
                const total = months * 2000;
                monthHidden.value = String(months);
                totalHidden.value = String(total);
                periodHidden.value = dDay + '/' + dMonth + '/' + dYear + ' à ' + aDay + '/' + aMonth + '/' + aYear;
                amountPreview.textContent = 'Montant estimé : ' + formatMoney(total);
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const accountValue = document.getElementById('account').value.trim();
                const start = new Date(
                    document.getElementById('de_annee').value,
                    Number(document.getElementById('de_mois').value) - 1,
                    Number(document.getElementById('de_jour').value)
                );
                const end = new Date(
                    document.getElementById('a_annee').value,
                    Number(document.getElementById('a_mois').value) - 1,
                    Number(document.getElementById('a_jour').value)
                );

                if (!accountValue || Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
                    return;
                }

                updateSummary();
                loader.classList.add('show');

                const params = new URLSearchParams(new FormData(form));
                const url = 'demande_releve.html?' + params.toString();

                setTimeout(function () {
                    window.location.href = url;
                }, 600);
            });

            ['de_jour', 'de_mois', 'de_annee', 'a_jour', 'a_mois', 'a_annee'].forEach(function (fieldId) {
                document.getElementById(fieldId).addEventListener('input', updateSummary);
            });

            updateSummary();
        })();
    </script>
</body>
</html>
