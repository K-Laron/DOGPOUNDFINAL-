# API Documentation

## Catarman Dog Pound Management System - API Reference

**Base URL:** `http://localhost:8000/api/v1`  
**Version:** 1.2.0  
**Authentication:** Bearer Token (JWT)

---

## Authentication

All protected endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer <access_token>
```

---

## Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/auth/login` | User login | ❌ |
| POST | `/auth/register` | User registration | ❌ |
| POST | `/auth/logout` | User logout | ✅ |
| POST | `/auth/refresh` | Refresh access token | ❌ |

#### POST /auth/login
```json
Request:
{
  "username": "string",
  "password": "string"
}

Response (200):
{
  "success": true,
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "UserID": 1,
      "FirstName": "Admin",
      "LastName": "User",
      "Email": "admin@example.com",
      "Role_Name": "Admin"
    }
  }
}
```

---

### Users

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/users` | List all users | Admin |
| GET | `/users/{id}` | Get user by ID | Admin |
| POST | `/users` | Create user | Admin |
| PUT | `/users/{id}` | Update user | Admin |
| DELETE | `/users/{id}` | Delete user | Admin |
| GET | `/profile` | Get current user profile | Any |
| PUT | `/profile` | Update profile | Any |
| PUT | `/profile/password` | Change password | Any |
| GET | `/roles` | List all roles | Admin |

---

### Animals

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/animals/available` | List available animals | Public |
| GET | `/animals/{id}` | Get animal details | Public |
| GET | `/animals` | List all animals | Admin, Staff, Vet |
| GET | `/animals/stats/summary` | Get statistics | Admin, Staff, Vet |
| POST | `/animals` | Create animal | Admin, Staff, Vet |
| PUT | `/animals/{id}` | Update animal | Admin, Staff, Vet |
| DELETE | `/animals/{id}` | Delete animal | Admin |
| PATCH | `/animals/{id}/status` | Update status | Admin, Staff, Vet |

#### Animal Types
- `Dog`, `Cat`, `Other`

#### Animal Statuses
- `Available`, `Reserved`, `Adopted`, `Deceased`, `In Treatment`, `Quarantine`, `Reclaimed`

---

### Adoptions

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/adoptions` | List adoptions | Admin, Staff, Vet |
| GET | `/adoptions/{id}` | Get adoption details | Any |
| POST | `/adoptions` | Submit request | Any |
| PUT | `/adoptions/{id}/process` | Process request | Admin, Staff |
| PUT | `/adoptions/{id}/cancel` | Cancel request | Any |
| GET | `/adoptions/stats/summary` | Statistics | Admin, Staff |

#### Adoption Statuses
- `Pending` → `Interview Scheduled` → `Seminar Scheduled` → `Approved` → `Completed`
- Can be `Rejected` or `Cancelled` at any stage

---

### Medical Records

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/medical` | List records | Admin, Staff, Vet |
| GET | `/medical/{id}` | Get record | Admin, Staff, Vet |
| GET | `/medical/animal/{id}` | Records by animal | Admin, Staff, Vet |
| POST | `/medical` | Create record | Vet |
| PUT | `/medical/{id}` | Update record | Vet |
| DELETE | `/medical/{id}` | Delete record | Admin, Vet |
| GET | `/medical/upcoming` | Upcoming treatments | Admin, Staff, Vet |
| GET | `/medical/overdue` | Overdue treatments | Admin, Staff, Vet |

---

### Inventory

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/inventory` | List items | Admin, Staff |
| GET | `/inventory/{id}` | Get item | Admin, Staff |
| POST | `/inventory` | Create item | Admin, Staff |
| PUT | `/inventory/{id}` | Update item | Admin, Staff |
| DELETE | `/inventory/{id}` | Delete item | Admin |
| PATCH | `/inventory/{id}/adjust` | Adjust stock | Admin, Staff |
| GET | `/inventory/low-stock` | Low stock alerts | Admin, Staff |
| GET | `/inventory/expiring` | Expiring items | Admin, Staff |

---

### Billing

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/invoices` | List invoices | Admin, Staff |
| GET | `/invoices/{id}` | Get invoice | Admin, Staff |
| POST | `/invoices` | Create invoice | Admin, Staff |
| PUT | `/invoices/{id}/cancel` | Cancel invoice | Admin |
| GET | `/payments` | List payments | Admin, Staff |
| POST | `/payments` | Record payment | Admin, Staff |
| GET | `/billing/summary` | Billing summary | Admin, Staff |

---

### Dashboard

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/dashboard/stats` | Full statistics | Any |
| GET | `/dashboard/quick-stats` | Quick stats | Any |
| GET | `/dashboard/activity` | Recent activity | Admin, Staff |
| GET | `/dashboard/intake` | Intake trends | Admin, Staff |

---

### System

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | `/health` | Health check | Public |
| GET | `/system/info` | System info | Admin |

#### GET /health
```json
Response (200):
{
  "status": "healthy",
  "timestamp": "2026-01-16T05:00:00Z",
  "version": "1.2.0",
  "checks": {
    "database": { "status": "up" },
    "disk": { "status": "ok", "free_percent": 45.2 },
    "memory": { "status": "ok", "used_mb": 12.5 },
    "php": { "version": "8.2.0", "status": "ok" }
  }
}
```

---

## Error Responses

| Status | Description |
|--------|-------------|
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 422 | Validation Error - Invalid data |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Server Error - Internal error |

### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": "Specific field error"
  }
}
```

---

## Rate Limiting

- **Login:** 10 requests per minute per IP
- **API:** 100 requests per minute per IP

Rate limit headers:
```
Retry-After: <seconds>
X-RateLimit-Reset: <timestamp>
```

---

## Pagination

List endpoints support pagination:

```
GET /animals?page=1&per_page=20
```

Response includes:
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  }
}
```
