<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../crm/config/database.php';
include_once __DIR__ . '/../../crm/includes/auth.php';
requireFeature('payroll');



$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);

use Mpdf\Mpdf;

/**
 * Récupère les employés actifs (filtrés par customer_id si présent)
 */
function getEmployees(PDO $pdo): array {
    global $customer_id;
    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, base_salary, job_title, email FROM erp_employees WHERE status='active' AND customer_id = ? ORDER BY last_name, first_name");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $pdo->query("SELECT id, first_name, last_name, base_salary, job_title, email FROM erp_employees WHERE status='active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les sociétés (filtrées par customer_id si présent)
 */
function getCompanies(PDO $pdo): array {
    global $customer_id;
    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT id, name FROM erp_companies WHERE customer_id = ? ORDER BY name");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $pdo->query("SELECT id, name FROM erp_companies ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

/* ...existing helper functions calculatePayslip, renderPayslipHtml ... */
/* (le corps des fonctions calculatePayslip et renderPayslipHtml reste identique
   mais renderPayslipHtml accepte maintenant un paramètre optionnel $companyName) */

function calculatePayslip(array $employee, array $inputs): array {
    // ...existing code...
    $gross = (float)($inputs['gross_salary'] ?? $employee['base_salary'] ?? 0.0);
    $bonus = (float)($inputs['bonus'] ?? 0.0);
    $overtime = (float)($inputs['overtime'] ?? 0.0);
    $deductions = (float)($inputs['deductions'] ?? 0.0);

    $brut = round($gross + $bonus + $overtime, 2);

    $contributions = [
        ['key'=>'sante', 'label'=>'Sécurité Sociale - Maladie / Maternité / Invalidité / Décès', 'base_fraction'=>1.0, 'employee_rate'=>0.0700, 'employer_rate'=>0.0700],
        ['key'=>'accident', 'label'=>'Accident du travail / Maladies professionnelles', 'base_fraction'=>1.0, 'employee_rate'=>0.0000, 'employer_rate'=>0.0396],
        ['key'=>'retraite_plaf', 'label'=>'Retraite (Sécurité Sociale - plafonnée)', 'base_fraction'=>1.0, 'employee_rate'=>0.0690, 'employer_rate'=>0.0855],
        ['key'=>'retraite_deplaf', 'label'=>'Retraite (Sécurité Sociale - déplafonnée)', 'base_fraction'=>1.0, 'employee_rate'=>0.0040, 'employer_rate'=>0.0202],
        ['key'=>'compl_tr1', 'label'=>'Complémentaire - Tranche 1', 'base_fraction'=>1.0, 'employee_rate'=>0.0401, 'employer_rate'=>0.0601],
        ['key'=>'assurance_chomage', 'label'=>'Assurance chômage', 'base_fraction'=>1.0, 'employee_rate'=>0.0425, 'employer_rate'=>0.0425],
        ['key'=>'csg_ded', 'label'=>'CSG déductible', 'base_fraction'=>1.0, 'employee_rate'=>0.0680, 'employer_rate'=>0.0000],
        ['key'=>'csg_non_ded', 'label'=>'CSG/CRDS non déductible', 'base_fraction'=>1.0, 'employee_rate'=>0.0290, 'employer_rate'=>0.0000],
    ];

    $detail = [];
    $totalEmployeeContrib = 0.0;
    $totalEmployerContrib = 0.0;

    foreach ($contributions as $c) {
        $base = round($brut * $c['base_fraction'], 2);
        $empC = round($base * $c['employee_rate'], 2);
        $erC = round($base * $c['employer_rate'], 2);
        $detail[] = [
            'key' => $c['key'],
            'label' => $c['label'],
            'base' => $base,
            'employee_rate' => $c['employee_rate'],
            'employer_rate' => $c['employer_rate'],
            'employee_contrib' => $empC,
            'employer_contrib' => $erC,
        ];
        $totalEmployeeContrib += $empC;
        $totalEmployerContrib += $erC;
    }

    $net_before_taxes = round($brut - $totalEmployeeContrib - $deductions, 2);
    $net = $net_before_taxes;

    return [
        'gross' => round($gross, 2),
        'bonus' => round($bonus, 2),
        'overtime' => round($overtime, 2),
        'brut' => $brut,
        'deductions' => round($deductions, 2),
        'contributions' => $detail,
        'total_employee_contrib' => round($totalEmployeeContrib, 2),
        'total_employer_contrib' => round($totalEmployerContrib, 2),
        'net' => round($net, 2),
        'net_before_taxes' => round($net_before_taxes, 2),
    ];
}

function renderPayslipHtml(array $employee, array $calc, string $period, ?string $companyName = null): string {
    $name = htmlspecialchars($employee['last_name'] . ' ' . $employee['first_name']);
    $role = htmlspecialchars($employee['job_title'] ?? '');
    $email = htmlspecialchars($employee['email'] ?? '');
    $company = $companyName ? $companyName : "Société : ";

    $html = '<html><head><meta charset="utf-8"><style>
        body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#111; padding:18px; background:#fff}
        .header-table{width:100%;border-collapse:collapse;margin-bottom:12px}
        .box{padding:10px;border-radius:4px;background:#fff}
        .company-name{font-weight:800;font-size:14px;color:#0b1220;margin-bottom:6px}
        .meta{font-size:11px;color:#444;line-height:1.35}
        h1{font-size:16px;margin:10px 0 12px}
        table.main{border-collapse:collapse;width:100%;font-size:11px;margin-top:8px}
        th,td{border:1px solid #ccc;padding:6px;vertical-align:top}
        th{background:#f5f5f5;text-align:left}
        .right{text-align:right}
        .muted{color:#666;font-size:11px}
        .summary{margin-top:12px;padding:10px;background:#f8f9fb;border:1px solid #e6e9ef;border-radius:6px}
        .total-row{font-weight:700;background:#f1f5f9}
        .kv{display:flex;justify-content:space-between;align-items:center}
        @media print { .kv{display:block} }
    </style></head><body>';

    $html .= '<table class="header-table"><tr>';
    $html .= '<td style="width:58%;vertical-align:top"><div class="box">';
    $html .= '<div class="company-name">' . htmlspecialchars($company) . '</div>';
    $html .= '<div class="meta">Adresse société - <br>SIRET :<br>Téléphone :</div>';
    $html .= '</div></td>';

    $html .= '<td style="width:42%;vertical-align:top"><div class="box">';
    $html .= '<div class="company-name">Employé</div>';
    $html .= '<div class="meta"><strong>' . $name . '</strong><br>' . ($role ? $role . '<br>' : '') . ($email ? $email . '<br>' : '') . 'Matricule : <br>Entrée : ' . (isset($employee['hire_date']) ? htmlspecialchars($employee['hire_date']) : '—') . '<br></div>';
    $html .= '</div></td>';
    $html .= '</tr></table>';

    $html .= '<h1>Bulletin de paie — ' . htmlspecialchars($period) . '</h1>';
    $html .= '<table class="main"><thead><tr><th>Rubrique</th><th class="right">Base (€)</th><th class="right">Taux</th><th class="right">Cotisation salarié (€)</th><th class="right">Cotisation employeur (€)</th></tr></thead><tbody>';

    $html .= '<tr><td>Salaire de base</td><td class="right">' . number_format($calc['gross'], 2, ',', ' ') . '</td><td class="right">—</td><td class="right">—</td><td class="right">—</td></tr>';
    $html .= '<tr><td>Primes</td><td class="right">' . number_format($calc['bonus'], 2, ',', ' ') . '</td><td class="right">—</td><td class="right">—</td><td class="right">—</td></tr>';
    $html .= '<tr><td>Heures supplémentaires</td><td class="right">' . number_format($calc['overtime'], 2, ',', ' ') . '</td><td class="right">—</td><td class="right">—</td><td class="right">—</td></tr>';
    $html .= '<tr class="total-row"><td>Total brut</td><td class="right">' . number_format($calc['brut'], 2, ',', ' ') . '</td><td class="right">—</td><td class="right">' . number_format($calc['total_employee_contrib'], 2, ',', ' ') . '</td><td class="right">' . number_format($calc['total_employer_contrib'], 2, ',', ' ') . '</td></tr>';

    foreach ($calc['contributions'] as $c) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($c['label']) . '</td>';
        $html .= '<td class="right">' . number_format($c['base'], 2, ',', ' ') . '</td>';
        $html .= '<td class="right">' . number_format($c['employee_rate'] * 100, 2, ',', ' ') . ' % / ' . number_format($c['employer_rate'] * 100, 2, ',', ' ') . ' %</td>';
        $html .= '<td class="right">-' . number_format($c['employee_contrib'], 2, ',', ' ') . '</td>';
        $html .= '<td class="right">' . number_format($c['employer_contrib'], 2, ',', ' ') . '</td>';
        $html .= '</tr>';
    }

    $html .= '<tr><td>Retenues (autres)</td><td class="right">' . number_format($calc['deductions'], 2, ',', ' ') . '</td><td class="right">—</td><td class="right">-' . number_format($calc['deductions'], 2, ',', ' ') . '</td><td class="right">—</td></tr>';
    $html .= '<tr class="total-row"><td>Net à payer avant impôt</td><td class="right">—</td><td class="right">—</td><td class="right">' . number_format($calc['net'], 2, ',', ' ') . '</td><td class="right">—</td></tr>';

    $html .= '</tbody></table>';

    $html .= '<div class="summary">';
    $html .= '<div class="kv"><div class="muted">Total cotisations salarié</div><div><strong>-' . number_format($calc['total_employee_contrib'], 2, ',', ' ') . ' €</strong></div></div>';
    $html .= '<div class="kv" style="margin-top:6px"><div class="muted">Total cotisations employeur</div><div><strong>' . number_format($calc['total_employer_contrib'], 2, ',', ' ') . ' €</strong></div></div>';
    $html .= '</div>';

    $html .= '</body></html>';
    return $html;
}

/* Traitement formulaire / génération PDF et stockage en base */
$employees = getEmployees($pdo);
$companies = getCompanies($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $period = trim($_POST['period'] ?? '');
    $companyId = isset($_POST['company_id']) && $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null;

    if ($employeeId <= 0 || $period === '') {
        die('Employé et période requis.');
    }

    // retrouver l'employé
    $employee = null;
    foreach ($employees as $e) { if ((int)$e['id'] === $employeeId) { $employee = $e; break; } }
    if (!$employee) { die('Employé introuvable'); }

    $calc = calculatePayslip($employee, [
        'gross_salary' => $_POST['gross_salary'] ?? null,
        'bonus' => $_POST['bonus'] ?? null,
        'overtime' => $_POST['overtime'] ?? null,
        'deductions' => $_POST['deductions'] ?? null,
    ]);

    // Enregistrer / mettre à jour en base la fiche de paie (valeurs agrégées)
    // NOTE: la colonne company_id doit exister dans erp_payrolls
    if ($companyId !== null) {
        $stmt = $pdo->prepare(
            "INSERT INTO erp_payrolls (employee_id, period, gross_salary, bonus, overtime, deductions, employee_contrib, employer_contrib, net_pay, company_id, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE
               gross_salary=VALUES(gross_salary),
               bonus=VALUES(bonus),
               overtime=VALUES(overtime),
               deductions=VALUES(deductions),
               employee_contrib=VALUES(employee_contrib),
               employer_contrib=VALUES(employer_contrib),
               net_pay=VALUES(net_pay),
               company_id=VALUES(company_id),
               created_at=NOW()"
        );
        $stmt->execute([
            $employeeId,
            $period,
            $calc['brut'],
            $calc['bonus'],
            $calc['overtime'],
            $calc['deductions'],
            $calc['total_employee_contrib'],
            $calc['total_employer_contrib'],
            $calc['net'],
            $companyId
        ]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO erp_payrolls (employee_id, period, gross_salary, bonus, overtime, deductions, employee_contrib, employer_contrib, net_pay, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE
               gross_salary=VALUES(gross_salary),
               bonus=VALUES(bonus),
               overtime=VALUES(overtime),
               deductions=VALUES(deductions),
               employee_contrib=VALUES(employee_contrib),
               employer_contrib=VALUES(employer_contrib),
               net_pay=VALUES(net_pay),
               created_at=NOW()"
        );
        $stmt->execute([
            $employeeId,
            $period,
            $calc['brut'],
            $calc['bonus'],
            $calc['overtime'],
            $calc['deductions'],
            $calc['total_employee_contrib'],
            $calc['total_employer_contrib'],
            $calc['net'],
        ]);
    }

    // Récupérer le nom de la société sélectionnée pour l'afficher dans le PDF
    $companyName = null;
    if ($companyId !== null) {
        $cstmt = $pdo->prepare("SELECT name FROM erp_companies WHERE id = ?");
        $cstmt->execute([$companyId]);
        $companyName = $cstmt->fetchColumn() ?: null;
    }

    // Générer PDF
    $html = renderPayslipHtml($employee, $calc, $period, $companyName);
    $mpdf = new Mpdf(['tempDir' => sys_get_temp_dir(), 'mode' => 'utf-8']);
    $mpdf->WriteHTML($html);
    $filename = 'fiche_paie_' . preg_replace('/[^0-9A-Za-z_-]+/','_', $employee['last_name'].'_'.$employee['first_name'].'_'.$period) . '.pdf';
    $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    exit;
}