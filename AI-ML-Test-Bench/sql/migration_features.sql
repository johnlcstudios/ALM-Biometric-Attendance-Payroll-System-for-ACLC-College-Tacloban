-- Holidays table
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    type ENUM('Regular', 'Special', 'Non-Working') DEFAULT 'Regular',
    pay_rate DECIMAL(5,2) DEFAULT 200.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_date (company_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Overtime records table
CREATE TABLE IF NOT EXISTS overtime_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    ot_date DATE NOT NULL,
    hours DECIMAL(4,2) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_employee (company_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
