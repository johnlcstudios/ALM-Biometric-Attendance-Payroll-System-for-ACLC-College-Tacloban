# Attendance Analytics Script
# Used for generating reports on employee presence trends

library(ggplot2)

analyze_attendance <- function(csv_path) {
  data <- read.csv(csv_path)
  # Basic statistical summary
  summary(data)
  
  # Generate attendance distribution plot
  plot <- ggplot(data, aes(x=Date, fill=Status)) + 
          geom_bar(position="dodge") +
          theme_minimal() +
          labs(title="Weekly Attendance Trends", x="Date", y="Employee Count")
          
  return(plot)
}

# Usage: analyze_attendance("logs/attendance_march.csv")
