using System;
using System.IO;
using System.Drawing;
using System.Windows.Forms;
using System.Diagnostics;

namespace ALMInstaller
{
    public class InstallerForm : Form
    {
        private TextBox txtSource;
        private TextBox txtHtdocs;
        private Button btnInstall;
        private Label lblStatus;
        private ProgressBar progressBar;

        public InstallerForm()
        {
            InitializeComponents();
        }

        private void InitializeComponents()
        {
            this.Text = "ALM Biometrics System Installer";
            this.Size = new Size(500, 320);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.StartPosition = FormStartPosition.CenterScreen;

            Label lblTitle = new Label();
            lblTitle.Text = "Install ALM Biometrics to XAMPP";
            lblTitle.Font = new Font("Segoe UI", 12, FontStyle.Bold);
            lblTitle.Location = new Point(20, 20);
            lblTitle.AutoSize = true;
            this.Controls.Add(lblTitle);

            Label lblSource = new Label { Text = "Source System Files:", Location = new Point(20, 60), AutoSize = true };
            txtSource = new TextBox { Location = new Point(20, 80), Width = 360 };
            
            // Default source is parent directory of the executable
            try {
                txtSource.Text = Path.GetFullPath(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "..\\"));
            } catch {
                txtSource.Text = AppDomain.CurrentDomain.BaseDirectory;
            }
            
            Button btnBrowseSource = new Button { Text = "Browse", Location = new Point(390, 78), Width = 70 };
            btnBrowseSource.Click += (s, e) => {
                using (FolderBrowserDialog fbd = new FolderBrowserDialog()) {
                    fbd.SelectedPath = txtSource.Text;
                    if (fbd.ShowDialog() == DialogResult.OK) txtSource.Text = fbd.SelectedPath;
                }
            };
            this.Controls.Add(lblSource);
            this.Controls.Add(txtSource);
            this.Controls.Add(btnBrowseSource);

            Label lblHtdocs = new Label { Text = "XAMPP htdocs Path:", Location = new Point(20, 110), AutoSize = true };
            txtHtdocs = new TextBox { Text = @"C:\xampp\htdocs", Location = new Point(20, 130), Width = 360 };
            Button btnBrowseHtdocs = new Button { Text = "Browse", Location = new Point(390, 128), Width = 70 };
            btnBrowseHtdocs.Click += (s, e) => {
                using (FolderBrowserDialog fbd = new FolderBrowserDialog()) {
                    fbd.SelectedPath = txtHtdocs.Text;
                    if (fbd.ShowDialog() == DialogResult.OK) txtHtdocs.Text = fbd.SelectedPath;
                }
            };
            this.Controls.Add(lblHtdocs);
            this.Controls.Add(txtHtdocs);
            this.Controls.Add(btnBrowseHtdocs);

            btnInstall = new Button { Text = "Install Application", Location = new Point(20, 180), Size = new Size(440, 40), Font = new Font("Segoe UI", 10, FontStyle.Bold) };
            btnInstall.Click += BtnInstall_Click;
            this.Controls.Add(btnInstall);

            progressBar = new ProgressBar { Location = new Point(20, 230), Width = 440, Height = 10, Style = ProgressBarStyle.Continuous };
            this.Controls.Add(progressBar);

            lblStatus = new Label { Text = "Ready to install.", Location = new Point(20, 250), AutoSize = true, ForeColor = Color.Gray };
            this.Controls.Add(lblStatus);
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
            lblStatus.Text = "Installing files...";

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

                    this.Invoke((MethodInvoker)delegate {
                        progressBar.Style = ProgressBarStyle.Continuous;
                        progressBar.Value = 100;
                        lblStatus.Text = "Installation Complete!";
                        MessageBox.Show("ALM Biometrics installed successfully!\nYou can now launch it from the Desktop shortcut.", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        btnInstall.Enabled = true;
                    });
                }
                catch (Exception ex) {
                    this.Invoke((MethodInvoker)delegate {
                        progressBar.Style = ProgressBarStyle.Continuous;
                        lblStatus.Text = "Error during installation.";
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
