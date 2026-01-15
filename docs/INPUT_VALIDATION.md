# Input Validation Summary

## Overview

The Catarman Dog Pound Management System implements comprehensive input validation to ensure data integrity and security. All user inputs are validated on both the frontend and backend.

---

## Validation Architecture

```
User Input → Frontend Validation → API Request → Backend Validation → Database
                    ↓                                    ↓
              Show Error                           Return 422
```

---

## Backend Validation (Validator Class)

Located in: `backend/app/utils/Validator.php`

### Available Validation Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must not be empty | `$v->required('name')` |
| `email` | Valid email format | `$v->email('email')` |
| `minLength` | Minimum string length | `$v->minLength('password', 8)` |
| `maxLength` | Maximum string length | `$v->maxLength('name', 100)` |
| `numeric` | Numeric value | `$v->numeric('age')` |
| `integer` | Integer value | `$v->integer('quantity')` |
| `positive` | Positive number | `$v->positive('price')` |
| `inArray` | Value in allowed list | `$v->inArray('status', ['Active', 'Inactive'])` |
| `date` | Valid date format | `$v->date('birth_date', 'Y-m-d')` |
| `phone` | Phone number format | `$v->phone('contact')` |
| `url` | Valid URL | `$v->url('website')` |
| `confirmed` | Field matches confirmation | `$v->confirmed('password')` |
| `unique` | Unique in database | `$v->unique('email', 'Users', 'Email')` |

---

## Validation by Endpoint

### Authentication

#### POST /auth/register
| Field | Rules |
|-------|-------|
| `first_name` | required, maxLength(50) |
| `last_name` | required, maxLength(50) |
| `email` | required, email, unique(Users) |
| `username` | required, minLength(3), maxLength(50), unique(Users) |
| `password` | required, minLength(8), confirmed |
| `contact_number` | phone |
| `address` | maxLength(255) |

#### POST /auth/login
| Field | Rules |
|-------|-------|
| `username` | required |
| `password` | required |

---

### Animals

#### POST /animals
| Field | Rules |
|-------|-------|
| `name` | required, maxLength(100) |
| `type` | required, inArray(['Dog', 'Cat', 'Other']) |
| `breed` | maxLength(50) |
| `gender` | inArray(['Male', 'Female', 'Unknown']) |
| `age_group` | inArray(['Puppy', 'Kitten', 'Young', 'Adult', 'Senior']) |
| `weight` | numeric, positive |
| `intake_status` | required, inArray(['Stray', 'Owner Surrender', 'Rescued', 'Born in Shelter']) |

#### PATCH /animals/{id}/status
| Field | Rules |
|-------|-------|
| `status` | required, inArray(['Available', 'Reserved', 'Adopted', 'Deceased', 'In Treatment', 'Quarantine', 'Reclaimed']) |

---

### Adoptions

#### POST /adoptions
| Field | Rules |
|-------|-------|
| `animal_id` | required, integer, exists(Animals) |
| `reason` | maxLength(500) |
| `living_situation` | maxLength(255) |
| `has_other_pets` | boolean |
| `has_children` | boolean |

#### PUT /adoptions/{id}/process
| Field | Rules |
|-------|-------|
| `status` | required, inArray(['Interview Scheduled', 'Seminar Scheduled', 'Approved', 'Rejected', 'Completed']) |
| `interview_date` | date (if status = Interview Scheduled) |
| `seminar_date` | date (if status = Seminar Scheduled) |
| `notes` | maxLength(500) |

---

### Medical Records

#### POST /medical
| Field | Rules |
|-------|-------|
| `animal_id` | required, integer, exists(Animals) |
| `diagnosis_type` | required, inArray(['Checkup', 'Vaccination', 'Surgery', 'Treatment', 'Emergency', 'Deworming', 'Spay/Neuter']) |
| `diagnosis` | required, maxLength(500) |
| `treatment` | maxLength(500) |
| `next_due_date` | date, afterToday |

---

### Inventory

#### POST /inventory
| Field | Rules |
|-------|-------|
| `item_name` | required, maxLength(100) |
| `category` | required, inArray(['Medical', 'Food', 'Cleaning', 'Supplies']) |
| `quantity` | required, integer, positive |
| `unit` | required, maxLength(20) |
| `reorder_level` | integer, positive |
| `expiration_date` | date, afterToday |

---

### Billing

#### POST /invoices
| Field | Rules |
|-------|-------|
| `user_id` | required, integer, exists(Users) |
| `transaction_type` | required, inArray(['Adoption', 'Reclaim']) |
| `animal_id` | integer, exists(Animals) |
| `amount` | required, numeric, positive |

#### POST /payments
| Field | Rules |
|-------|-------|
| `invoice_id` | required, integer, exists(Invoices) |
| `amount` | required, numeric, positive |
| `payment_method` | required, inArray(['Cash', 'GCash', 'Bank Transfer']) |

---

## Input Sanitization (Sanitizer Class)

Located in: `backend/app/utils/Sanitizer.php`

All inputs are sanitized before processing:

| Method | Description |
|--------|-------------|
| `string()` | Removes XSS, trims whitespace |
| `email()` | Validates and filters email |
| `integer()` | Converts to integer |
| `float()` | Converts to float |
| `boolean()` | Converts to boolean |
| `filename()` | Removes path traversal |
| `array()` | Recursively sanitizes arrays |

### XSS Prevention
- `<script>` tags removed
- Event handlers (onclick, onerror) stripped
- JavaScript URLs blocked
- HTML entities escaped

---

## Frontend Validation

Located in: `frontend/assets/js/utils.js`

- Real-time validation on input
- Pattern matching for emails, phones
- Length constraints
- Required field highlighting
- Confirmation field matching

---

## Error Responses

Validation failures return HTTP 422:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": "The email field must be a valid email address",
    "password": "The password must be at least 8 characters"
  }
}
```

---

## Testing

Validation is tested in:
- `tests/Unit/ValidatorTest.php` - 17 tests
- `tests/Unit/SanitizerTest.php` - 25 tests
