# Data Archiver
# Compresses and moves old attendance logs to secondary storage

require 'zlib'
require 'fileutils'

module DataArchiver
  SOURCE_DIR = "./logs"
  ARCHIVE_DIR = "./archives"

  def self.archive_old_logs(days_threshold = 90)
    FileUtils.mkdir_p(ARCHIVE_DIR)
    
    Dir.glob(File.join(SOURCE_DIR, "*.csv")).each do |file|
      if File.mtime(file) < Time.now - (days_threshold * 24 * 60 * 60)
        archive_name = "#{File.basename(file)}.gz"
        dest_path = File.join(ARCHIVE_DIR, archive_name)
        
        puts "Archiving #{file} -> #{dest_path}"
        
        Zlib::GzipWriter.open(dest_path) do |gz|
          gz.write File.read(file)
        end
        
        # FileUtils.rm(file) # Uncomment after verification
      end
    end
  end
end

# DataArchiver.archive_old_logs(30)
