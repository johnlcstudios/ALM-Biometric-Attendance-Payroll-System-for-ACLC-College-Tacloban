# Payroll Projections and Forecasting
# Analyzes historical payroll data to project future expenditures

library(forecast)

project_payroll <- function(history_csv) {
  # Load historical data
  payroll_data <- read.csv(history_csv)
  
  # Convert to time series (monthly)
  ts_data <- ts(payroll_data$TotalAmount, frequency = 12)
  
  # Fit ARIMA model
  fit <- auto.arima(ts_data)
  
  # Forecast next 6 months
  fore <- forecast(fit, h = 6)
  
  # Export results
  write.csv(as.data.frame(fore), "payroll_forecast_next_6m.csv")
  
  return(fore)
}

# Example: project_payroll("data/payroll_history.csv")
