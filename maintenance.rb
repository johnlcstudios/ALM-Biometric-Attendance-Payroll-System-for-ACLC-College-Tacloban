# Database Maintenance and Log Management
require 'logger'
require 'fileutils'

class MaintenanceTask
  def initialize(log_dir)
    @logger = Logger.new(STDOUT)
    @log_dir = log_dir
  end

  def rotate_logs
    @logger.info("Starting log rotation in #{@log_dir}...")
    # Logic to archive logs older than 30 days
    Dir.glob(File.join(@log_dir, "*.log")).each do |file|
      if File.mtime(file) < Time.now - (30 * 24 * 60 * 60)
        FileUtils.mv(file, File.join(@log_dir, "archive", File.basename(file)))
        @logger.info("Archived: #{File.basename(file)}")
      end
    end
  end

  def cleanup_temp_files
    @logger.info("Cleaning up temporary biometric frames...")
    # Cleanup logic here
  end
end

# task = MaintenanceTask.new("./logs")
# task.rotate_logs
