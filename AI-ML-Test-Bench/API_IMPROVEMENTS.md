# API Improvements Documentation

## Overview
This document describes the API improvements implemented for the ALM Biometric Attendance Payroll System.

## API Versioning

### Implementation
- API versioning is now supported through query parameters
- Supported parameters: `v` or `version`
- Default version: `1`
- Currently supported versions: `1`

### Usage Examples
```
GET /backend/api.php?action=login&v=1
GET /backend/api.php?action=health&version=1
```

### Version Validation
- Requests with unsupported versions return HTTP 400 with error message
- Version validation occurs before any other processing

## Health Check Endpoint

### Endpoint
```
GET /backend/api.php?action=health
```

### Response Format
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00+00:00",
  "version": "1",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful"
    },
    "php": {
      "status": "healthy",
      "message": "PHP 8.1.0 is running"
    },
    "memory": {
      "status": "healthy",
      "message": "Peak memory usage: 12.45 MB"
    }
  }
}
```

### Health Checks Performed
1. **Database Connectivity**: Tests connection to the database
2. **PHP Version**: Reports current PHP version
3. **Memory Usage**: Reports peak memory usage

### HTTP Status Codes
- `200 OK`: All checks passed (healthy)
- `503 Service Unavailable`: One or more checks failed (unhealthy)

### Monitoring Integration
The health check endpoint is designed for integration with monitoring systems like:
- Load balancers
- Kubernetes liveness/readiness probes
- Application monitoring tools (DataDog, New Relic, etc.)
- CI/CD pipelines

## Future Enhancements
- Support for additional API versions
- More detailed health checks (disk space, external services)
- Authentication for health checks in production environments
- Caching of health check results to reduce database load