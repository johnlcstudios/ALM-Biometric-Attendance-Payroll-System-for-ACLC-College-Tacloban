<?php
// payroll_tax.php - Government Contributions & BIR Tax Engine (2024/2025)
// SSS MSC-based, PhilHealth 5%, Pag-IBIG tiered, BIR monthly withholding tax

class PayrollTaxEngine {
    private $pdo;
    private $company_id;

    // SSS 2024 Monthly Salary Credit Table (all 57 brackets)
    // Each entry: [min_salary, max_salary, msc, employee_share, employer_share]
    private $sss_table = [
        [0, 4249.99, 4000, 180.00, 520.00],
        [4250, 4749.99, 4500, 202.50, 585.00],
        [4750, 5249.99, 5000, 225.00, 650.00],
        [5250, 5749.99, 5500, 247.50, 715.00],
        [5750, 6249.99, 6000, 270.00, 780.00],
        [6250, 6749.99, 6500, 292.50, 845.00],
        [6750, 7249.99, 7000, 315.00, 910.00],
        [7250, 7749.99, 7500, 337.50, 975.00],
        [7750, 8249.99, 8000, 360.00, 1040.00],
        [8250, 8749.99, 8500, 382.50, 1105.00],
        [8750, 9249.99, 9000, 405.00, 1170.00],
        [9250, 9749.99, 9500, 427.50, 1235.00],
        [9750, 10249.99, 10000, 450.00, 1300.00],
        [10250, 10749.99, 10500, 472.50, 1365.00],
        [10750, 11249.99, 11000, 495.00, 1430.00],
        [11250, 11749.99, 11500, 517.50, 1495.00],
        [11750, 12249.99, 12000, 540.00, 1560.00],
        [12250, 12749.99, 12500, 562.50, 1625.00],
        [12750, 13249.99, 13000, 585.00, 1690.00],
        [13250, 13749.99, 13500, 607.50, 1755.00],
        [13750, 14249.99, 14000, 630.00, 1820.00],
        [14250, 14749.99, 14500, 652.50, 1885.00],
        [14750, 15249.99, 15000, 675.00, 1950.00],
        [15250, 15749.99, 15500, 697.50, 2015.00],
        [15750, 16249.99, 16000, 720.00, 2080.00],
        [16250, 16749.99, 16500, 742.50, 2145.00],
        [16750, 17249.99, 17000, 765.00, 2210.00],
        [17250, 17749.99, 17500, 787.50, 2275.00],
        [17750, 18249.99, 18000, 810.00, 2340.00],
        [18250, 18749.99, 18500, 832.50, 2405.00],
        [18750, 19249.99, 19000, 855.00, 2470.00],
        [19250, 19749.99, 19500, 877.50, 2535.00],
        [19750, 20249.99, 20000, 900.00, 2600.00],
        [20250, 20749.99, 20500, 922.50, 2665.00],
        [20750, 21249.99, 21000, 945.00, 2730.00],
        [21250, 21749.99, 21500, 967.50, 2795.00],
        [21750, 22249.99, 22000, 990.00, 2860.00],
        [22250, 22749.99, 22500, 1012.50, 2925.00],
        [22750, 23249.99, 23000, 1035.00, 2990.00],
        [23250, 23749.99, 23500, 1057.50, 3055.00],
        [23750, 24249.99, 24000, 1080.00, 3120.00],
        [24250, 24749.99, 24500, 1102.50, 3185.00],
        [24750, 25249.99, 25000, 1125.00, 3250.00],
        [25250, 25749.99, 25500, 1147.50, 3315.00],
        [25750, 26249.99, 26000, 1170.00, 3380.00],
        [26250, 26749.99, 26500, 1192.50, 3445.00],
        [26750, 27249.99, 27000, 1215.00, 3510.00],
        [27250, 27749.99, 27500, 1237.50, 3575.00],
        [27750, 28249.99, 28000, 1260.00, 3640.00],
        [28250, 28749.99, 28500, 1282.50, 3705.00],
        [28750, 29249.99, 29000, 1305.00, 3770.00],
        [29250, 29749.99, 29500, 1327.50, 3835.00],
        [29750, PHP_INT_MAX, 30000, 1350.00, 3900.00],
    ];

    // BIR 2024 Monthly Withholding Tax Table
    // Each entry: [bracket_min, bracket_max, rate, fixed_amount]
    private $bir_table = [
        [0, 20833, 0, 0],
        [20833, 33333, 0.15, 0],
        [33333, 66667, 0.20, 1875],
        [66667, 166667, 0.25, 8750],
        [166667, 666667, 0.30, 33750],
        [666667, PHP_INT_MAX, 0.35, 183750],
    ];

    public function __construct($pdo, $company_id) {
        $this->pdo = $pdo;
        $this->company_id = $company_id;
    }

    // Look up SSS contribution based on gross monthly salary
    private function lookupSSS($gross) {
        foreach ($this->sss_table as $row) {
            if ($gross >= $row[0] && $gross <= $row[1]) {
                return [
                    'msc' => $row[2],
                    'employee' => $row[3],
                    'employer' => $row[4],
                ];
            }
        }
        // Fallback: highest bracket
        $last = end($this->sss_table);
        return [
            'msc' => $last[2],
            'employee' => $last[3],
            'employer' => $last[4],
        ];
    }

    // Calculate PhilHealth contribution
    private function calculatePhilHealth($gross) {
        $floor = 10000;
        $ceiling = 100000;
        $employee_rate = 0.0225; // 2.25%
        $employer_rate = 0.0275; // 2.75%

        if ($gross <= $floor) {
            $salary_base = $floor;
        } elseif ($gross >= $ceiling) {
            $salary_base = $ceiling;
        } else {
            $salary_base = $gross;
        }

        return [
            'employee' => round($salary_base * $employee_rate, 2),
            'employer' => round($salary_base * $employer_rate, 2),
        ];
    }

    // Calculate Pag-IBIG (HDMF) contribution
    private function calculatePagIBIG($gross) {
        if ($gross <= 5000) {
            // Salary <= 5,000: employee 1%, employer 2%
            $employee = round($gross * 0.01, 2);
            $employer = round($gross * 0.02, 2);
        } else {
            // Salary > 5,000: employee 2% capped at 200, employer 2% capped at 200
            $employee = min(round($gross * 0.02, 2), 200);
            $employer = min(round($gross * 0.02, 2), 200);
        }

        return [
            'employee' => $employee,
            'employer' => $employer,
        ];
    }

    // Calculate BIR withholding tax using correct formula:
    // tax = fixed_amount + (taxable_income - bracket_min) * rate
    private function calculateBIRTax($gross, $emp_data = []) {
        // Compute employee-side government contributions to deduct
        $sss = $this->lookupSSS($gross);
        $philhealth = $this->calculatePhilHealth($gross);
        $pagibig = $this->calculatePagIBIG($gross);

        $total_deductions = $sss['employee'] + $philhealth['employee'] + $pagibig['employee'];
        $taxable_income = max(0, $gross - $total_deductions);

        foreach ($this->bir_table as $bracket) {
            [$bracket_min, $bracket_max, $rate, $fixed_amount] = $bracket;
            if ($taxable_income > $bracket_min && $taxable_income <= $bracket_max) {
                return round($fixed_amount + ($taxable_income - $bracket_min) * $rate, 2);
            }
        }

        // Taxable income is zero or in the first (0%) bracket
        return 0;
    }

    public function calculateGovContributions($gross_pay, $employee_data = []) {
        $gross = max(0, (float)$gross_pay);

        $sss = $this->lookupSSS($gross);
        $philhealth = $this->calculatePhilHealth($gross);
        $pagibig = $this->calculatePagIBIG($gross);
        $bir_tax = $this->calculateBIRTax($gross, $employee_data);

        return [
            'sss_employee' => $sss['employee'],
            'sss_employer' => $sss['employer'],
            'sss_msc' => $sss['msc'],
            'philhealth_employee' => $philhealth['employee'],
            'philhealth_employer' => $philhealth['employer'],
            'pagibig_employee' => $pagibig['employee'],
            'pagibig_employer' => $pagibig['employer'],
            'bir_tax' => $bir_tax,
        ];
    }

    public function applyTaxesToPayroll($payroll_id) {
        $stmt = $this->pdo->prepare("SELECT p.*, e.* FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.id = ? AND p.company_id = ?");
        $stmt->execute([$payroll_id, $this->company_id]);
        $payroll = $stmt->fetch();

        if (!$payroll) return false;

        $gross_pay = $payroll['basic_pay'] + ($payroll['breakdown'] ? json_decode($payroll['breakdown'], true)['total_allowances'] ?? 0 : 0);
        $taxes = $this->calculateGovContributions($gross_pay, $payroll);

        $tax_breakdown = json_encode($taxes);
        $total_tax_deductions = array_sum(array_filter([
            $taxes['sss_employee'],
            $taxes['philhealth_employee'],
            $taxes['pagibig_employee'],
            $taxes['bir_tax']
        ]));

        $stmt_update = $this->pdo->prepare("UPDATE payroll SET
            deductions = deductions + ?,
            net_pay = net_pay - ?,
            breakdown = JSON_SET(COALESCE(breakdown, '{}'), '$.tax_breakdown', ?)
            WHERE id = ?");
        $stmt_update->execute([$total_tax_deductions, $total_tax_deductions, $tax_breakdown, $payroll_id]);

        logAudit($this->pdo, $this->company_id, null, 'PAYROLL_TAX_APPLIED', 'payroll', $payroll_id);

        return true;
    }
}
?>
