<?php
// payroll_tax.php - Government Contributions & BIR Tax Engine
// Step 1.4: Auto-calculate SSS, PhilHealth, Pag-IBIG, BIR Withholding Tax

class PayrollTaxEngine {
    private $pdo;
    private $company_id;
    
    // Government Contribution Rates (2024 PH Rates - UPDATE YEARLY)
    private $tax_tables = [
        'sss_employee' => 0.045, // 4.5% Employee Share
        'sss_employer' => 0.095, // 9.5% Employer Share
        'philhealth_employee' => 0.025, // 2.5% Employee (2024)
        'philhealth_employer' => 0.025, // 2.5% Employer
        'pagibig_employee' => 0.02, // 2% Employee
        'pagibig_employer' => 0.02, // 2% Employer
        'bir_tax_table' => [ // Monthly Withholding Tax Table 2024 (PH)
            [0, 0, 0],
            [833.33, 20833.33, 0],
            [20833.34, 33333.33, 0.15],
            [33333.34, 66666.67, 3125],
            [66666.68, 166666.67, 9375],
            [166666.68, 333333.33, 26250],
            [333333.34, PHP_INT_MAX, 62500]
        ]
    ];
    
    public function __construct($pdo, $company_id) {
        $this->pdo = $pdo;
        $this->company_id = $company_id;
    }
    
    public function calculateGovContributions($gross_pay, $employee_data = []) {
        $results = [
            'sss_employee' => 0,
            'sss_employer' => 0,
            'philhealth_employee' => 0,
            'philhealth_employer' => 0,
            'pagibig_employee' => 0,
            'pagibig_employer' => 0,
            'bir_tax' => 0
        ];
        
        $gross = max(0, (float)$gross_pay);
        
        // SSS (Monthly Salary Credit up to ₱30,000 cap)
        $msc = min($gross, 30000);
        $results['sss_employee'] = $msc * $this->tax_tables['sss_employee'];
        $results['sss_employer'] = $msc * $this->tax_tables['sss_employer'];
        
        // PhilHealth (up to ₱100,000 cap)
        $ph_cap = min($gross, 100000);
        $results['philhealth_employee'] = $ph_cap * $this->tax_tables['philhealth_employee'];
        $results['philhealth_employer'] = $ph_cap * $this->tax_tables['philhealth_employer'];
        
        // Pag-IBIG (up to ₱5,000 cap)
        $pagibig_cap = min($gross, 5000);
        $results['pagibig_employee'] = $pagibig_cap * $this->tax_tables['pagibig_employee'];
        $results['pagibig_employer'] = $pagibig_cap * $this->tax_tables['pagibig_employer'];
        
        // BIR Withholding Tax
        $results['bir_tax'] = $this->calculateBIRTax($gross, $employee_data);
        
        return $results;
    }
    
    private function calculateBIRTax($gross, $emp_data = []) {
        $taxable = $gross;
        
        // Tax Exemptions (if any - configurable)
        $exemptions = $this->getTaxExemptions($emp_data);
        $taxable -= $exemptions;
        $taxable = max(0, $taxable);
        
        foreach ($this->tax_tables['bir_tax_table'] as $bracket) {
            [$min, $max, $rate_or_excess] = $bracket;
            if ($taxable >= $min && $taxable < $max) {
                return $taxable * $rate_or_excess;
            }
        }
        return 0;
    }
    
    private function getTaxExemptions($emp_data) {
        // TODO: Implement configurable exemptions (dependents, etc.)
        return 0;
    }
    
    // Update payroll with tax calculations
    public function applyTaxesToPayroll($payroll_id) {
        $stmt = $this->pdo->prepare("SELECT p.*, e.* FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.id = ? AND p.company_id = ?");
        $stmt->execute([$payroll_id, $this->company_id]);
        $payroll = $stmt->fetch();
        
        if (!$payroll) return false;
        
        $gross_pay = $payroll['basic_pay'] + ($payroll['breakdown'] ? json_decode($payroll['breakdown'], true)['total_allowances'] ?? 0 : 0);
        $taxes = $this->calculateGovContributions($gross_pay, $payroll);
        
        // Update payroll record with tax breakdown
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
        
        // Log tax application
        logAudit($this->pdo, $this->company_id, null, 'PAYROLL_TAX_APPLIED', 'payroll', $payroll_id);
        
        return true;
    }
}

// API Endpoints
header('Content-Type: application/json');

require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'calculate_taxes':
        $company_id = $_SESSION['company_id'] ?? $_GET['company_id'] ?? 1;
        $gross_pay = $_POST['gross_pay'] ?? 0;
        $employee_data = $_POST['employee_data'] ?? [];
        
        $engine = new PayrollTaxEngine($pdo, $company_id);
        $taxes = $engine->calculateGovContributions($gross_pay, $employee_data);
        echo json_encode(['success' => true, 'taxes' => $taxes]);
        break;
        
    case 'apply_payroll_taxes':
        if (!isPayrollOrHigher()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $payroll_id = $_POST['payroll_id'] ?? 0;
        if (!$payroll_id) {
            echo json_encode(['success' => false, 'message' => 'Payroll ID required']);
            break;
        }
        
        $company_id = $_SESSION['company_id'];
        $engine = new PayrollTaxEngine($pdo, $company_id);
        $success = $engine->applyTaxesToPayroll($payroll_id);
        
        echo json_encode(['success' => $success]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

