using System;
using System.Diagnostics;
using System.IO;
using System.Threading;
using System.Drawing;
using System.Windows.Forms;

namespace ALMLauncher
{
    static class Program
    {
        [STAThread]
        static void Main(string[] args)
        {
            // The launcher is expected to be in xampp\htdocs\ALM-Biometrics
            string currentDir = AppDomain.CurrentDomain.BaseDirectory;
            string htdocsDir = Path.GetFullPath(Path.Combine(currentDir, "..\\"));
            string xamppDir = Path.GetFullPath(Path.Combine(htdocsDir, "..\\"));

            // Check if XAMPP is running by looking for httpd
            Process[] httpd = Process.GetProcessesByName("httpd");
            if (httpd.Length == 0)
            {
                string xamppStart = Path.Combine(xamppDir, "xampp_start.exe");
                if (File.Exists(xamppStart))
                {
                    var startInfo = new ProcessStartInfo {
                        FileName = xamppStart,
                        WindowStyle = ProcessWindowStyle.Hidden,
                        CreateNoWindow = true
                    };
                    Process.Start(startInfo);
                    Thread.Sleep(2500); // Wait for Apache & MySQL to boot up
                }
            }

            // Launch the app as an independent standalone window using Edge or Chrome App mode
            string url = "http://localhost/ALM-Biometrics/";
            string windowTitle = "ALM Biometrics v2.5.0 - BSIT 3A";

            string edgePath = @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe";
            string chromePath = @"C:\Program Files\Google\Chrome\Application\chrome.exe";

            try {
                if (File.Exists(edgePath)) {
                    Process.Start(edgePath, string.Format("--app=\"{0}\"", url));
                } else if (File.Exists(chromePath)) {
                    Process.Start(chromePath, string.Format("--app=\"{0}\"", url));
                } else {
                    // Fallback to default browser
                    Process.Start(url);
                }
            } 
            catch (Exception) {
                // If anything fails, attempt a generic Process.Start which opens via OS default
                try {
                    Process.Start(new ProcessStartInfo(url) { UseShellExecute = true });
                } catch { } // completely swallow errors since it's a silent launcher
            }
        }
    }
}
