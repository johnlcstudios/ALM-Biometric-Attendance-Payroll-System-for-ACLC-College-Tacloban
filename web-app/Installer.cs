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
        private CheckBox chkDependencies;

        public InstallerForm()
        {
            InitializeComponents();
        }

        private void InitializeComponents()
        {
            // Professional corporate design - compact single-screen layout
            this.Text = "ALM Biometrics System - Installer";
            this.Size = new Size(680, 620);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.StartPosition = FormStartPosition.CenterScreen;
            this.BackColor = Color.FromArgb(255, 255, 255);

            // Professional header with brand color
            Panel headerPanel = new Panel();
            headerPanel.Size = new Size(680, 90);
            headerPanel.Location = new Point(0, 0);
            headerPanel.BackColor = Color.FromArgb(0, 51, 102); // Corporate blue
            this.Controls.Add(headerPanel);

            // Brand logo
            Label lblLogo = new Label();
            lblLogo.Text = "ALM";
            lblLogo.Font = new Font("Segoe UI", 32, FontStyle.Bold);
            lblLogo.ForeColor = Color.FromArgb(255, 255, 255);
            lblLogo.Location = new Point(30, 15);
            lblLogo.AutoSize = true;
            lblLogo.BackColor = Color.Transparent;
            headerPanel.Controls.Add(lblLogo);

            // Subtitle
            Label lblTitle = new Label();
            lblTitle.Text = "Biometric Attendance & Payroll System";
            lblTitle.Font = new Font("Segoe UI", 10, FontStyle.Regular);
            lblTitle.ForeColor = Color.FromArgb(200, 220, 255);
            lblTitle.Location = new Point(30, 52);
            lblTitle.AutoSize = true;
            lblTitle.BackColor = Color.Transparent;
            headerPanel.Controls.Add(lblTitle);

            // Version badge
            Label lblVersion = new Label();
            lblVersion.Text = "Version 2.4.0";
            lblVersion.Font = new Font("Segoe UI", 9, FontStyle.Bold);
            lblVersion.ForeColor = Color.FromArgb(180, 200, 230);
            lblVersion.Location = new Point(560, 55);
            lblVersion.AutoSize = true;
            lblVersion.BackColor = Color.Transparent;
            headerPanel.Controls.Add(lblVersion);

            // Main content area
            int yOffset = 110;

            // Installation Path Section
            Label lblSection1 = new Label { 
                Text = "Installation Path", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(0, 51, 102)
            };
            this.Controls.Add(lblSection1);
            yOffset += 28;

            // Source Directory
            Label lblSource = new Label { 
                Text = "Source Directory:", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Regular),
                ForeColor = Color.FromArgb(60, 60, 60)
            };
            txtSource = new TextBox { 
                Location = new Point(30, yOffset + 20), 
                Width = 510,
                Height = 30,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                BorderStyle = BorderStyle.FixedSingle
            };
            txtSource.BackColor = Color.FromArgb(248, 248, 248);
            
            // Auto-detect source path (parent directory of installer)
            try {
                string installerDir = AppDomain.CurrentDomain.BaseDirectory;
                string parentDir = Path.GetFullPath(Path.Combine(installerDir, ".."));
                
                // Check if AI-ML-Test-Bench exists in parent directory
                if (Directory.Exists(Path.Combine(parentDir, "AI-ML-Test-Bench"))) {
                    txtSource.Text = parentDir;
                } else {
                    txtSource.Text = installerDir;
                }
            } catch {
                txtSource.Text = AppDomain.CurrentDomain.BaseDirectory;
            }
            
            Button btnBrowseSource = new Button { 
                Text = "Browse", 
                Location = new Point(550, yOffset + 19), 
                Width = 100,
                Height = 30,
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(0, 51, 102),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold)
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
            yOffset += 65;

            // Target path
            Label lblHtdocs = new Label { 
                Text = "XAMPP htdocs Path:", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Regular),
                ForeColor = Color.FromArgb(60, 60, 60)
            };
            txtHtdocs = new TextBox { 
                Text = @"C:\xampp\htdocs", 
                Location = new Point(30, yOffset + 20), 
                Width = 510,
                Height = 30,
                Font = new Font("Segoe UI", 9, FontStyle.Regular),
                BorderStyle = BorderStyle.FixedSingle
            };
            txtHtdocs.BackColor = Color.FromArgb(248, 248, 248);
            
            Button btnBrowseHtdocs = new Button { 
                Text = "Browse", 
                Location = new Point(550, yOffset + 19), 
                Width = 100,
                Height = 30,
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(0, 51, 102),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold)
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
            yOffset += 65;

            // Separator line
            Panel separator1 = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(620, 1),
                BackColor = Color.FromArgb(220, 220, 220)
            };
            this.Controls.Add(separator1);
            yOffset += 20;
            
            // Configuration Section
            Label lblSection2 = new Label { 
                Text = "Configuration Options", 
                Location = new Point(30, yOffset), 
                AutoSize = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(0, 51, 102)
            };
            this.Controls.Add(lblSection2);
            yOffset += 26;
            
            // Database setup checkbox
            Panel dbPanel = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(620, 70),
                BackColor = Color.FromArgb(248, 248, 248),
                BorderStyle = BorderStyle.FixedSingle
            };
                        
            chkDatabase = new CheckBox { 
                Text = "Setup database automatically",
                Location = new Point(15, 10), 
                Width = 590,
                Checked = true,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = Color.FromArgb(40, 40, 40)
            };
                        
            Label lblDbDesc = new Label {
                Text = "Creates database, runs schema, and applies all migrations (requires XAMPP MySQL running)",
                Location = new Point(40, 34),
                Width = 565,
                AutoSize = false,
                Font = new Font("Segoe UI", 8F, FontStyle.Regular),
                ForeColor = Color.FromArgb(100, 100, 100)
            };

            dbPanel.Controls.Add(chkDatabase);
            dbPanel.Controls.Add(lblDbDesc);
            this.Controls.Add(dbPanel);
            yOffset += 80;

            // Dependencies checkbox
            Panel depPanel = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(620, 70),
                BackColor = Color.FromArgb(248, 248, 248),
                BorderStyle = BorderStyle.FixedSingle
            };

            chkDependencies = new CheckBox {
                Text = "Download offline dependencies",
                Location = new Point(15, 10),
                Width = 590,
                Checked = true,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = Color.FromArgb(40, 40, 40)
            };

            Label lblDepDesc = new Label {
                Text = "Downloads Font Awesome, SweetAlert2, and fonts for offline use (~520KB, requires internet)",
                Location = new Point(40, 34),
                Width = 565,
                AutoSize = false,
                Font = new Font("Segoe UI", 8F, FontStyle.Regular),
                ForeColor = Color.FromArgb(100, 100, 100)
            };

            depPanel.Controls.Add(chkDependencies);
            depPanel.Controls.Add(lblDepDesc);
            this.Controls.Add(depPanel);
            yOffset += 85;

            // Separator line
            Panel separator2 = new Panel {
                Location = new Point(30, yOffset),
                Size = new Size(620, 1),
                BackColor = Color.FromArgb(220, 220, 220)
            };
            this.Controls.Add(separator2);
            yOffset += 20;

            // Install button
            btnInstall = new Button { 
                Text = "Install Now", 
                Location = new Point(30, yOffset), 
                Size = new Size(620, 45), 
                Font = new Font("Segoe UI", 11, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(0, 102, 204),
                ForeColor = Color.White,
                Cursor = Cursors.Hand
            };
            btnInstall.FlatAppearance.BorderSize = 0;
            
            // Hover effect
            btnInstall.MouseEnter += (s, e) => {
                btnInstall.BackColor = Color.FromArgb(0, 80, 180);
            };
            btnInstall.MouseLeave += (s, e) => {
                btnInstall.BackColor = Color.FromArgb(0, 102, 204);
            };
            
            btnInstall.Click += BtnInstall_Click;
            this.Controls.Add(btnInstall);
            yOffset += 58;
        
            // Progress section
            Label lblProgress = new Label {
                Text = "Installation Progress:",
                Location = new Point(30, yOffset),
                AutoSize = true,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(60, 60, 60)
            };
            this.Controls.Add(lblProgress);
            yOffset += 20;
        
            progressBar = new ProgressBar { 
                Location = new Point(30, yOffset), 
                Width = 620, 
                Height = 6,
                Style = ProgressBarStyle.Continuous
            };
            this.Controls.Add(progressBar);
            yOffset += 12;
        
            lblStatus = new Label { 
                Text = "Ready to install", 
                Location = new Point(30, yOffset), 
                AutoSize = true, 
                ForeColor = Color.FromArgb(100, 100, 100),
                Font = new Font("Segoe UI", 8.5F, FontStyle.Regular)
            };
            this.Controls.Add(lblStatus);
        
            // Footer
            Panel footerPanel = new Panel {
                Location = new Point(0, 570),
                Size = new Size(680, 50),
                BackColor = Color.FromArgb(245, 245, 245)
            };
            
            Label lblFooter = new Label {
                Text = "\u00A9 2026 ALM Biometrics System  |  Built with dedication by BSIT 3A Batch 2027",
                Location = new Point(30, 16),
                AutoSize = true,
                Font = new Font("Segoe UI", 7.5F, FontStyle.Regular),
                ForeColor = Color.FromArgb(120, 120, 120)
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
            btnInstall.Text = "Installing...";
            btnInstall.BackColor = Color.FromArgb(80, 80, 80);
            progressBar.Style = ProgressBarStyle.Continuous;
            progressBar.Value = 0;
            lblStatus.Text = "Copying files...";
            lblStatus.ForeColor = Color.FromArgb(0, 0, 0);

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
                            lblStatus.Text = "Setting up database...";
                            lblStatus.ForeColor = Color.FromArgb(0, 0, 0);
                        });
                        
                        SetupDatabase(targetPath);
                    }

                    // Download dependencies if checkbox is checked
                    if (chkDependencies.Checked) {
                        this.Invoke((MethodInvoker)delegate {
                            lblStatus.Text = "Downloading offline dependencies...";
                            lblStatus.ForeColor = Color.FromArgb(0, 0, 0);
                        });
                        
                        DownloadDependencies(targetPath);
                    }

                    this.Invoke((MethodInvoker)delegate {
                        progressBar.Style = ProgressBarStyle.Continuous;
                        progressBar.Value = 100;
                        lblStatus.Text = "Installation Complete!";
                        lblStatus.ForeColor = Color.FromArgb(40, 167, 69);
                        string msg = string.Format("ALM Biometrics v2.4.0 Build9 installed successfully!\n\n");
                        msg += "NEW FEATURES:\n";
                        msg += "• Faculty Level Tracking (SHS, College, Both)\n";
                        msg += "• Hire Date Management for payroll protection\n";
                        msg += "• Resignation Decline functionality\n";
                        msg += "• Employee Reinstatement capability\n";
                        msg += "• Enhanced Face Enrollment (cross-device stable)\n";
                        msg += "• Separate Name Fields (First, Last, Middle Initial)\n";
                        msg += "• Animated Splash Screen on startup\n";
                        msg += "• Complete Database Schema (all migrations)\n";
                        msg += "• Frontal Face Detection for accurate kiosk scanning\n";
                        msg += "• Enhanced Security (2FA, encryption, audit trail)\n";
                        msg += "• Automatic Faculty & Utility Payroll Calculation\n\n";
                        msg += "You can now launch it from the Desktop shortcut.";
                        if (chkDatabase.Checked) {
                            msg += "\n\nDatabase and all migrations (001-004) have been set up automatically.";
                        }
                        if (chkDependencies.Checked) {
                            msg += "\n\nOffline dependencies (Font Awesome, SweetAlert2, Google Fonts) have been downloaded.";
                        }
                        MessageBox.Show(msg, "Installation Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        btnInstall.Enabled = true;
                        btnInstall.Text = "Install Now";
                        btnInstall.BackColor = Color.FromArgb(0, 0, 0);
                    });
                }
                catch (Exception ex) {
                    this.Invoke((MethodInvoker)delegate {
                        progressBar.Style = ProgressBarStyle.Continuous;
                        lblStatus.Text = "Installation Failed";
                        lblStatus.ForeColor = Color.FromArgb(220, 53, 69);
                        MessageBox.Show(ex.Message, "Installation Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        btnInstall.Enabled = true;
                        btnInstall.Text = "Install Now";
                        btnInstall.BackColor = Color.FromArgb(0, 0, 0);
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
                
                // Try complete_schema.sql first (v2.4 - all-in-one file)
                string completeSchemaFile = Path.Combine(sqlDir, "complete_schema.sql");
                if (File.Exists(completeSchemaFile)) {
                    this.Invoke((MethodInvoker)delegate {
                        lblStatus.Text = "Creating database with complete schema...";
                        lblStatus.ForeColor = Color.FromArgb(0, 0, 0);
                    });
                    RunSqlFile(mysqlPath, completeSchemaFile);
                }
                else {
                    // Fallback to schema.sql + migrations (legacy)
                    string schemaFile = Path.Combine(sqlDir, "schema.sql");
                    if (File.Exists(schemaFile)) {
                        this.Invoke((MethodInvoker)delegate {
                            lblStatus.Text = "Creating database schema...";
                            lblStatus.ForeColor = Color.FromArgb(0, 0, 0);
                        });
                        RunSqlFile(mysqlPath, schemaFile);
                    }

                    // Run migrations 001 to 004
                    string migrationsDir = Path.Combine(sqlDir, "migrations");
                    if (Directory.Exists(migrationsDir)) {
                        string[] migrationFiles = Directory.GetFiles(migrationsDir, "*.sql");
                        Array.Sort(migrationFiles); // Ensure order: 001, 002, 003, 004

                        foreach (string migrationFile in migrationFiles) {
                            string fileName = Path.GetFileName(migrationFile);
                            this.Invoke((MethodInvoker)delegate {
                                lblStatus.Text = string.Format("Applying migration: {0}", fileName);
                                lblStatus.ForeColor = Color.FromArgb(0, 0, 0);
                            });
                            RunSqlFile(mysqlPath, migrationFile);
                            Thread.Sleep(500); // Small delay between migrations
                        }
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

        private void DownloadDependencies(string targetPath)
        {
            try {
                string jsDir = Path.Combine(targetPath, "AI-ML-Test-Bench", "js");
                string cssDir = Path.Combine(targetPath, "AI-ML-Test-Bench", "css");
                string webfontsDir = Path.Combine(targetPath, "AI-ML-Test-Bench", "webfonts");

                // Create directories if they don't exist
                Directory.CreateDirectory(jsDir);
                Directory.CreateDirectory(cssDir);
                Directory.CreateDirectory(webfontsDir);

                // Download SweetAlert2
                string swalPath = Path.Combine(jsDir, "sweetalert2.all.min.js");
                if (!File.Exists(swalPath)) {
                    this.Invoke((MethodInvoker)delegate {
                        lblStatus.Text = "Downloading SweetAlert2...";
                    });
                    DownloadFile("https://cdn.jsdelivr.net/npm/sweetalert2@11", swalPath);
                }

                // Download Font Awesome CSS
                string faCssPath = Path.Combine(cssDir, "all.min.css");
                if (!File.Exists(faCssPath)) {
                    this.Invoke((MethodInvoker)delegate {
                        lblStatus.Text = "Downloading Font Awesome...";
                    });
                    DownloadFile("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css", faCssPath);
                }

                // Download Font Awesome webfonts
                string[] fonts = new string[] {
                    "fa-solid-900.woff2",
                    "fa-regular-400.woff2",
                    "fa-brands-400.woff2"
                };

                foreach (string font in fonts) {
                    string fontPath = Path.Combine(webfontsDir, font);
                    if (!File.Exists(fontPath)) {
                        this.Invoke((MethodInvoker)delegate {
                            lblStatus.Text = string.Format("Downloading font: {0}", font);
                        });
                        string fontUrl = string.Format("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/{0}", font);
                        DownloadFile(fontUrl, fontPath);
                    }
                }
            }
            catch (Exception ex) {
                // Don't fail installation if dependencies fail to download
                // User can download them manually later
                this.Invoke((MethodInvoker)delegate {
                    lblStatus.Text = "Warning: Some dependencies failed to download (check internet connection)";
                    lblStatus.ForeColor = Color.FromArgb(255, 193, 7); // Yellow warning
                });
            }
        }

        private void DownloadFile(string url, string destination)
        {
            try {
                using (System.Net.WebClient client = new System.Net.WebClient()) {
                    client.DownloadFile(url, destination);
                }
            }
            catch (Exception ex) {
                throw new Exception(string.Format("Failed to download {0}: {1}", Path.GetFileName(destination), ex.Message));
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
