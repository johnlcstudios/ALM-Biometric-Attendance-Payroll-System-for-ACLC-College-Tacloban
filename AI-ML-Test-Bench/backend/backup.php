<?php
/**
 * Backup/Restore Tooling - backend/backup.php
 * Admin-only DB backup/restore with CLI/web support
 */

if (php_sapi_name() === 'cli') {
    define('CLI_MODE', true);
} else {
    define('CLI_MODE', false);
    session_start();
    require_once 'db.php';
}

class BackupManager {
    private $pdo, $company_id, $backups_dir;

    public function __construct() {
        global $pdo;
        $this->pdo = CLI_MODE ? $this->getPdoCli() : $pdo;
        $this->company_id = CLI_MODE ? $this->getCliCompanyId() : ($_SESSION['company_id'] ?? 1);
        $this->backups_dir = __DIR__ . '/../backups/';
        if (!is_dir($this->backups_dir)) mkdir($this->backups_dir, 0755, true);
    }

    private function getPdoCli() {
        $dsn = "mysql:host=localhost;dbname=alm_biometrics;charset=utf8mb4";
        return new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    private function getCliCompanyId() {
        return $_SERVER['COMPANY_ID'] ?? $GLOBALS['argv'][1] ?? die("Usage: COMPANY_ID=1 php backup.php\n");
    }

    private function isAdmin() {
        return CLI_MODE || ($_SESSION['role'] ?? '' === 'Admin');
    }

    public function execute() {
        $action = CLI_MODE ? ($GLOBALS['argv'][1] ?? 'backup') : ($_GET['action'] ?? '');

        // Set JSON header only for non-download actions
        if (!CLI_MODE && $action !== 'download') {
            header('Content-Type: application/json');
        }

        switch ($action) {
            case 'backup':
                $file = $this->backup();
                CLI_MODE ? print("Backup: $file\n") : print(json_encode(['file' => basename($file)]));
                break;
            case 'list':
                CLI_MODE ? print_r($this->listBackups()) : print(json_encode(['files' => $this->listBackups()]));
                break;
            case 'download':
                try {
                    $file = $this->backup();
                    if (file_exists($file)) {
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                        header('Content-Length: ' . filesize($file));
                        ob_clean();
                        flush();
                        readfile($file);
                        exit;
                    } else {
                        header('Content-Type: application/json');
                        exit(json_encode(['error' => 'Backup file was not created', 'path' => $file]));
                    }
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    exit(json_encode(['error' => $e->getMessage()]));
                }
                break;
            case 'restore':
                $this->restore();
                break;
            default:
                CLI_MODE ? exit("Actions: backup, list, restore\n") : exit(json_encode(['error' => 'Invalid action']));
        }
    }

    private function backup() {
        $ts = date('YmdHis');
        $file = $this->backups_dir . "backup_{$this->company_id}_$ts.sql.gz";
        $dump = "-- Company {$this->company_id} Backup " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n";

        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $hasCompany = $this->pdo->query("SHOW COLUMNS FROM `$table` LIKE 'company_id'")->rowCount();
            $dump .= $this->pdo->query("SHOW CREATE TABLE `$table`")->fetchColumn(1) . ";\n\n";
            $where = $hasCompany ? " WHERE company_id = {$this->company_id}" : '';
            foreach ($this->pdo->query("SELECT * FROM `$table`$where") as $row) {
                $dump .= "INSERT INTO `$table` VALUES (" . implode(',', array_map([$this->pdo, 'quote'], $row)) . ");\n";
            }
            $dump .= "\n";
        }
        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $gz = gzopen($file, 'w9');
        gzwrite($gz, $dump);
        gzclose($gz);
        return $file;
    }

    private function listBackups() {
        $pattern = $this->backups_dir . "backup_{$this->company_id}_*.sql.gz";
        $files = glob($pattern);
        usort($files, 'filemtime');
        return array_map('basename', array_reverse($files));
    }

    private function download($specific = null) {
        $files = $this->listBackups();
        $file = $specific ?: ($files[0] ?? null);
        if (!$file) exit(CLI_MODE ? "No backups\n" : json_encode(['error' => 'No backups']));
        $path = $this->backups_dir . $file;
        if (CLI_MODE) {
            readfile($path);
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Content-Length: ' . filesize($path));
            ob_clean();
            flush();
            readfile($path);
            exit;
        }
    }

    private function restore() {
        if (CLI_MODE || !isset($_FILES['file'])) exit(json_encode(['error' => 'POST file']));
        $tmp = $_FILES['file']['tmp_name'];
        if ($_FILES['file']['error'] || substr($_FILES['file']['name'], -3) !== '.gz') {
            exit(json_encode(['error' => 'Invalid .sql.gz']));
        }
        $sql = '';
        $handle = gzopen($tmp, 'r');
        while (!gzeof($handle)) $sql .= gzread($handle, 4096);
        gzclose($handle);
        try {
            $this->pdo->beginTransaction();
            $this->pdo->exec($sql);
            $this->pdo->commit();
            exit(json_encode(['success' => true, 'verify' => $this->verify()]));
        } catch (Exception $e) {
            $this->pdo->rollBack();
            exit(json_encode(['error' => $e->getMessage()]));
        }
    }

    private function verify() {
        $tables = ['employees', 'attendance', 'payroll'];
        $report = [];
        foreach ($tables as $t) {
            $cnt = $this->pdo->query("SELECT COUNT(*) FROM `$t` WHERE company_id = {$this->company_id}")->fetchColumn();
            $report[$t] = $cnt ? "$cnt rows OK" : 'EMPTY';
        }
        return $report;
    }
}

(new BackupManager())->execute();
?>