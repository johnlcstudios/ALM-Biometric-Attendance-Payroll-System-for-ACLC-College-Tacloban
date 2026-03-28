# API Health Monitor
# Periodically checks the status of the biometric and payroll APIs

require 'net/http'
require 'json'

class APIMonitor
  ENDPOINTS = [
    "http://localhost/backend/api.php?action=status",
    "http://localhost:8081/match"
  ]

  def check_health
    puts "Starting API health check at #{Time.now}"
    
    ENDPOINTS.each do |url|
      uri = URI(url)
      begin
        response = Net::HTTP.get_response(uri)
        if response.is_a?(Net::HTTPSuccess)
          puts "[OK] #{url} - Status: #{response.code}"
        else
          puts "[FAIL] #{url} - Status: #{response.code}"
        end
      rescue => e
        puts "[ERROR] #{url} - #{e.message}"
      end
    end
  end
end

# monitor = APIMonitor.new
# monitor.check_health
