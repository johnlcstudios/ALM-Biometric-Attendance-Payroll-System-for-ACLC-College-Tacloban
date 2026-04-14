using System;
using System.IO;
using System.Drawing;
using System.Windows.Forms;
using System.Diagnostics;
using System.Threading;

namespace ALMInstaller
{
    public class InstallerForm : Form
    {
        private TextBox txtSource;
        private TextBox txtHtdocs;
        private Button btnInstall;
        private Label lblStatus;
        private ProgressBar progressBar;
        private CheckBox chkDatabase;

        public InstallerForm()
        {
            InitializeComponents();
        }

        private void InitializeComponents()
        {
            this.Text = "ALM Biometrics System Installer";
            this.Size = new Size(650, 620);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.StartPosition = FormStartPosition.CenterScreen;
            this.BackColor = Color.FromArgb(245, 247, 250);

            // Header Panel with gradient-like effect
            Panel headerPanel = new Panel();
            headerPanel.Size = new Size(650, 100);
            headerPanel.Location = new Point(0, 0);
            headerPanel.BackColor = Color.FromArgb(30, 1, 120);
            this.Controls.Add(headerPanel);

            // ALM Logo/Icon area
            Label lblLogo = new Label();
            lblLogo.Text = "ALM";
            lblLogo.Font = new Font("Segoe UI", 32, FontStyle.Bold);
            lblLogo.ForeColor = Color.White;
            lblLogo.Location = new Point(30, 20);
            lblLogo.AutoSize = true;
            headerPanel.Controls.Add(lblLogo);

            // Header title
            Label lblTitle = new Label();
            lblTitle.Text = "Biometrics Attendance & Payroll System";
            lblTitle.Font = new Font("Segoe UI", 14, FontStyle.Regular);
            lblTitle.ForeColor = Color.FromArgb(200, 200, 255);
            lblTitle.Location = new Point(30, 60);
            lblTitle.AutoSize = true;
            headerPanel.Controls.Add(lblTitle);

            // Version badge
            Label lblVersion = new Label();
            lblVersion.Text = "v2.3";
            lblVersion.Font = new Font("Segoe UI", 9, FontStyle.Regular);
            lblVersion.ForeColor = Color.FromArgb(150, 150, 220);
            lblVersion.Location = new Point(560, 70);
            lblVersion.AutoSize = true;
            headerPanel.Controls.Add(lblVersion);

            // Main content area
            int yOffset = 120;

            // Section: Installation Path
            Label lblSection1 = new Label { 
                Text = "INSTALLATION PATH", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 1, 120)
            };
            this.Controls.Add(lblSection1);
            yOffset += 30;

            // Source path
            Label lblSource = new Label { 
                Text = "Source Files:", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                ForeColor = Color.FromArgb(80, 80, 80)
            };
            txtSource = new TextBox { 
                Location = new Point(150, yOffset - 3), 
                Width = 380,
                Height = 28,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                BorderStyle = BorderStyle.FixedSingle
            };
            txtSource.BackColor = Color.White;
            
            // Default source is parent directory of the executable
            try {
                txtSource.Text = Path.GetFullPath(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "..\\"));
            } catch {
                txtSource.Text = AppDomain.CurrentDomain.BaseDirectory;
            }
            
            Button btnBrowseSource = new Button { 
                Text = "Browse", 
                Location = new Point(540, yOffset - 4), 
                Width = 80,
                Height = 28,
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(79, 172, 254),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9, FontStyle.Regular)
            };
            btnBrowseSource.FlatAppearance.BorderSize = 0;
            btnBrowseSource.Click += (s, e) => {
                using (FolderBrowserDialog fbd = new FolderBrowserDialog()) {
                    fbd.SelectedPath = txtSource.Text;
                    if (fbd.ShowDialog() == DialogResult.OK) txtSource.Text = fbd.SelectedPath;
                }
            };
            this.Controls.Add(lblSource);
            this.Controls.Add(txtSource);
            this.Controls.Add(btnBrowseSource);
            yOffset += 40;

            // Target path
            Label lblHtdocs = new Label { 
                Text = "XAMPP htdocs:", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                ForeColor = Color.FromArgb(80, 80, 80)
            };
            txtHtdocs = new TextBox { 
                Text = @"C:\xampp\htdocs", 
                Location = new Point(150, yOffset - 3), 
                Width = 380,
                Height = 28,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                BorderStyle = BorderStyle.FixedSingle
            };
            txtHtdocs.BackColor = Color.White;
            
            Button btnBrowseHtdocs = new Button { 
                Text = "Browse", 
                Location = new Point(540, yOffset - 4), 
                Width = 80,
                Height = 28,
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(79, 172, 254),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9, FontStyle.Regular)
            };
            btnBrowseHtdocs.FlatAppearance.BorderSize = 0;
            btnBrowseHtdocs.Click += (s, e) => {
                using (FolderBrowserDialog fbd = new FolderBrowserDialog()) {
                    fbd.SelectedPath = txtHtdocs.Text;
                    if (fbd.ShowDialog() == DialogResult.OK) txtHtdocs.Text = fbd.SelectedPath;
                }
            };
            this.Controls.Add(lblHtdocs);
            this.Controls.Add(txtHtdocs);
            this.Controls.Add(btnBrowseHtdocs);
            yOffset += 50;

            // Separator line
            Panel separator1 = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(590, 1),
                BackColor = Color.FromArgb(220, 220, 230)
            };
            this.Controls.Add(separator1);
            yOffset += 25;

            // Section: Database Setup
            Label lblSection2 = new Label { 
                Text = "DATABASE CONFIGURATION", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 1, 120)
            };
            this.Controls.Add(lblSection2);
            yOffset += 30;

            // Database setup checkbox with description
            Panel dbPanel = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(590, 90),
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle
            };
            
            chkDatabase = new CheckBox { 
                Text = "Automatic Database Setup",
                Location = new Point(15, 12), 
                Width = 560,
                Checked = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 1, 120)
            };
            
            Label lblDbDesc = new Label {
                Text = "• Create database and run schema\n• Apply all migrations (001-003)\n• Setup encryption keys and security features",
                Location = new Point(40, 38),
                Width = 530,
                AutoSize = false,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Regular),
                ForeColor = Color.FromArgb(120, 120, 120)
            };

            dbPanel.Controls.Add(chkDatabase);
            dbPanel.Controls.Add(lblDbDesc);
            this.Controls.Add(dbPanel);
            yOffset += 105;

            // Separator line
            Panel separator2 = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(590, 1),
                BackColor = Color.FromArgb(220, 220, 230)
            };
            this.Controls.Add(separator2);
            yOffset += 25;

            // Features list panel
            Label lblSection3 = new Label { 
                Text = "NEW FEATURES IN v2.3", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 1, 120)
            };
            this.Controls.Add(lblSection3);
            yOffset += 30;

            Panel featuresPanel = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(590, 110),
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle
            };
            
            Label lblFeatures = new Label {
                Text = "✓ Frontal Face Detection - Kiosk only scans when looking straight\n" +
                       "✓ Fast Face Enrollment - 2x faster with quality scoring\n" +
                       "✓ Auto Payroll Calculation - Faculty & Utility auto-calculated\n" +
                       "✓ One-Click All Payrolls - Process General, Faculty & Utility together\n" +
                       "✓ Enhanced Security - 2FA, encryption, audit trail, rate limiting\n" +
                       "✓ DTR Generation - Employees can generate Daily Time Records\n" +
                       "✓ Editable Payroll Cells - Double-click to modify values in real-time",
                Location = new Point(15, 10),
                Width = 560,
                AutoSize = false,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Regular),
                ForeColor = Color.FromArgb(60, 60, 60)
            };

            featuresPanel.Controls.Add(lblFeatures);
            this.Controls.Add(featuresPanel);
            yOffset += 125;

            // Install button - Modern style
            btnInstall = new Button { 
                Text = "⬇  Install Application", 
                Location = new Point(30, yOffset), 
                Size = new Size(590, 48), 
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(30, 1, 120),
                ForeColor = Color.White,
                Cursor = Cursors.Hand
            };
            btnInstall.FlatAppearance.BorderSize = 0;
            btnInstall.Click += BtnInstall_Click;
            this.Controls.Add(btnInstall);
            yOffset += 60;

            // Progress section
            Label lblProgress = new Label {
                Text = "Installation Progress:",
                Location = new Point(30, yOffset),
                AutoSize = true,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                ForeColor = Color.FromArgb(80, 80, 80)
            };
            this.Controls.Add(lblProgress);
            yOffset += 22;

            progressBar = new ProgressBar { 
                Location = new Point(30, yOffset), 
                Width = 590, 
                Height = 8,
                Style = ProgressBarStyle.Continuous
            };
            this.Controls.Add(progressBar);
            yOffset += 18;

            lblStatus = new Label { 
                Text = "● Ready to install", 
                Location = new Point(30, yOffset), 
                AutoSize = true, 
                ForeColor = Color.FromArgb(100, 100, 100),
                Font = new Font("Segoe UI", 8.5F, FontStyle.Regular)
            };
            this.Controls.Add(lblStatus);

            // Footer
            Panel footerPanel = new Panel {
                Location = new Point(0, 570),
                Size = new Size(650, 50),
                BackColor = Color.FromArgb(240, 240, 245)
            };
            
            Label lblFooter = new Label {
                Text = "© 2026 ALM Biometrics System v2.3  •  Secure Attendance & Payroll Management",
                Location = new Point(30, 15),
                AutoSize = true,
                Font = new Font("Segoe UI", 8, FontStyle.Regular),
                ForeColor = Color.FromArgb(150, 150, 150)
            };
            footerPanel.Controls.Add(lblFooter);
            this.Controls.Add(footerPanel);
        }

        private void BtnInstall_Click(object sender, EventArgs e)
        {
            string sourcePath = txtSource.Text.Trim();
            string htdocsPath = txtHtdocs.Text.Trim();

            if (!Directory.Exists(sourcePath)) {
                MessageBox.Show("Source directory does not exist.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            if (!Directory.Exists(htdocsPath)) {
                MessageBox.Show("XAMPP htdocs directory does not exist.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            btnInstall.Enabled = false;
            progressBar.Style = ProgressBarStyle.Marquee;
            lblStatus.Text = "● Copying files...";
            lblStatus.ForeColor = Color.FromArgb(79, 172, 254);

            string targetPath = Path.Combine(htdocsPath, "ALM-Biometrics");

            // Run copy in background
            System.Threading.ThreadPool.QueueUserWorkItem(state => {
                try {
                    CopyDirectory(sourcePath, targetPath);
                    
                    // Copy launcher
                    string currentLauncher = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "ALM-Launcher.exe");
                    if (File.Exists(currentLauncher)) {
                        File.Copy(currentLauncher, Path.Combine(targetPath, "ALM-Launcher.exe"), true);
                    }

                    CreateShortcut(Path.Combine(targetPath, "ALM-Launcher.exe"));

                    // Setup database if checkbox is checked
                    if (chkDatabase.Checked) {
                        this.Invoke((MethodInvoker)delegate {
                            lblStatus.Text = "● Setting up database...";
                            lblStatus.ForeColor = Color.FromArgb(79, 172, 254);
                        });
                        
                        SetupDatabase(targetPath);
                    }

                    this.Invoke((MethodInvoker)delegate {
                        progressBar.Style = ProgressBarStyle.Continuous;
                        progressBar.Value = 100;
                        lblStatus.Text = "✓ Installation Complete!";
                        lblStatus.ForeColor = Color.FromArgb(40, 167, 69);
                        string msg = string.Format("ALM Biometrics v2.3 installed successfully!\n\n");
                        msg += "NEW FEATURES:\n";
                        msg += "• Frontal Face Detection for accurate kiosk scanning\n";
                        msg += "• Fast Face Enrollment (2x faster with quality scoring)\n";
                        msg += "• Automatic Faculty & Utility Payroll Calculation\n";
                        msg += "• One-Click processes all three payroll types\n";
                        msg += "• Enhanced Security (2FA, encryption, audit trail)\n";
                        msg += "• Employee DTR Generation\n";
                        msg += "• Editable Payroll Cells with real-time calculations\n\n";
                        msg += "You can now launch it from the Desktop shortcut.";
                        if (chkDatabase.Checked) {
                            msg += "\n\nDatabase and migrations have been set up automatically.";
                        }
                        MessageBox.Show(msg, "Installation Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        btnInstall.Enabled = true;
                    });
                }
                catch (Exception ex) {
                    this.Invoke((MethodInvoker)delegate {
                        progressBar.Style = ProgressBarStyle.Continuous;
                        lblStatus.Text = "✗ Installation Error";
                        lblStatus.ForeColor = Color.FromArgb(220, 53, 69);
                        MessageBox.Show(ex.Message, "Installation Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        btnInstall.Enabled = true;
                    });
                }
            });
        }

        private void CopyDirectory(string sourceDir, string targetDir)
        {
            Directory.CreateDirectory(targetDir);
            foreach (var file in Directory.GetFiles(sourceDir)) {
                // skip the installer exe and launcher if they are in the source root
                if (file.EndsWith("ALM-Installer.exe", StringComparison.OrdinalIgnoreCase)) continue;
                File.Copy(file, Path.Combine(targetDir, Path.GetFileName(file)), true);
            }

            foreach (var dir in Directory.GetDirectories(sourceDir)) {
                string dirName = new DirectoryInfo(dir).Name;
                if (dirName.Equals(".git", StringComparison.OrdinalIgnoreCase) || 
                    dirName.Equals(".agent", StringComparison.OrdinalIgnoreCase)) continue;
                
                CopyDirectory(dir, Path.Combine(targetDir, dirName));
            }
        }

        private void SetupDatabase(string targetPath)
        {
            try {
                // Find MySQL executable
                string mysqlPath = FindMySQL();
                if (string.IsNullOrEmpty(mysqlPath)) {
                    throw new Exception("MySQL not found. Please ensure XAMPP MySQL is running.");
                }

                string sqlDir = Path.Combine(targetPath, "AI-ML-Test-Bench", "sql");
                
                // Run schema.sql
                string schemaFile = Path.Combine(sqlDir, "schema.sql");
                if (File.Exists(schemaFile)) {
                    this.Invoke((MethodInvoker)delegate {
                        lblStatus.Text = "● Creating database schema...";
                        lblStatus.ForeColor = Color.FromArgb(79, 172, 254);
                    });
                    RunSqlFile(mysqlPath, schemaFile);
                }

                // Run migrations 001 to 003
                string migrationsDir = Path.Combine(sqlDir, "migrations");
                if (Directory.Exists(migrationsDir)) {
                    string[] migrationFiles = Directory.GetFiles(migrationsDir, "*.sql");
                    Array.Sort(migrationFiles); // Ensure order: 001, 002, 003

                    foreach (string migrationFile in migrationFiles) {
                        string fileName = Path.GetFileName(migrationFile);
                        this.Invoke((MethodInvoker)delegate {
                            lblStatus.Text = string.Format("● Applying migration: {0}", fileName);
                            lblStatus.ForeColor = Color.FromArgb(79, 172, 254);
                        });
                        RunSqlFile(mysqlPath, migrationFile);
                        Thread.Sleep(500); // Small delay between migrations
                    }
                }
            }
            catch (Exception ex) {
                throw new Exception(string.Format("Database setup failed: {0}", ex.Message));
            }
        }

        private string FindMySQL()
        {
            // Common MySQL paths in XAMPP
            string[] possiblePaths = new string[] {
                @"C:\xampp\mysql\bin\mysql.exe",
                @"D:\xampp\mysql\bin\mysql.exe",
                @"E:\xampp\mysql\bin\mysql.exe"
            };

            foreach (string path in possiblePaths) {
                if (File.Exists(path)) {
                    return path;
                }
            }

            return null;
        }

        private void RunSqlFile(string mysqlExe, string sqlFile)
        {
            ProcessStartInfo psi = new ProcessStartInfo {
                FileName = mysqlExe,
                Arguments = "--user=root --database=alm_biometrics",
                UseShellExecute = false,
                RedirectStandardInput = true,
                RedirectStandardError = true,
                RedirectStandardOutput = true,
                CreateNoWindow = true
            };

            using (Process process = Process.Start(psi)) {
                // Read SQL file and send to stdin
                string sqlContent = File.ReadAllText(sqlFile);
                process.StandardInput.Write(sqlContent);
                process.StandardInput.Close();
                
                string error = process.StandardError.ReadToEnd();
                process.WaitForExit();

                if (process.ExitCode != 0 && !string.IsNullOrEmpty(error)) {
                    // Some migrations might have warnings that are not critical
                    if (error.Contains("ERROR") && error.Contains("1062")) {
                        // Duplicate entry - migration already applied
                        return;
                    }
                    throw new Exception(string.Format("SQL execution error: {0}", error));
                }
            }
        }

        private void CreateShortcut(string targetExePath)
        {
            try {
                // Use a temporary VBScript to create the shortcut without needing COM dependencies
                string desktopMsg = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
                string shortcutPath = Path.Combine(desktopMsg, "ALM Biometrics.lnk");
                
                string vbsPath = Path.Combine(Path.GetTempPath(), "createshortcut.vbs");
                string vbs = string.Format(@"
Set oWS = WScript.CreateObject(""WScript.Shell"")
sLinkFile = ""{0}""
Set oLink = oWS.CreateShortcut(sLinkFile)
oLink.TargetPath = ""{1}""
oLink.WorkingDirectory = ""{2}""
oLink.Description = ""ALM Biometrics Attendance & Payroll System""
oLink.Save
", shortcutPath, targetExePath, Path.GetDirectoryName(targetExePath));
                File.WriteAllText(vbsPath, vbs);
                Process.Start("cscript.exe", string.Format("//Nologo \"{0}\"", vbsPath)).WaitForExit();
                File.Delete(vbsPath);
            } catch {
                // Ignore shortcut errors
            }
        }
    }

    static class Program
    {
        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new InstallerForm());
        }
    }
}
