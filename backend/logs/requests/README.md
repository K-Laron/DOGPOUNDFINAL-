# Request Logs Directory

This directory contains structured JSON request logs from the RequestLogger middleware.

## File Format

Log files are named: `requests_YYYY-MM-DD.json`

Each line is a JSON object containing:

- `request_id` - Unique request identifier
- `timestamp` - ISO 8601 timestamp
- `method` - HTTP method (GET, POST, PUT, DELETE, PATCH)
- `path` - Request path
- `ip` - Client IP address
- `user_agent` - Browser/client user agent
- `user_id` - Authenticated user ID (if logged in)
- `user_role` - User role (if authenticated)
- `status_code` - HTTP response status code
- `duration_ms` - Request duration in milliseconds
- `memory_peak_mb` - Peak memory usage

## Log Rotation

Logs are automatically rotated daily. Use `RequestLogger::cleanup(30)` to remove logs older than 30 days.

## Excluded from Git

This directory is excluded from version control. Log files should not be committed.
