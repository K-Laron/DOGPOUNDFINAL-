# 📚 Backend Code Documentation

## Catarman Dog Pound Management System

This document provides a detailed explanation of every backend file, its purpose, and how it works.

---

## 📂 Directory Overview

```
backend/
├── .htaccess              # Apache URL rewriting rules
├── app/
│   ├── bootstrap.php      # Application initialization
│   ├── api/               # Route definitions
│   ├── config/            # Configuration files
│   ├── controllers/       # Business logic handlers
│   ├── middleware/        # Request interceptors
│   ├── models/            # Database entity classes
│   └── utils/             # Helper utilities (JWT, RateLimiter, Sanitizer, etc.)
├── logs/                  # Error logs & rate limit data
│   └── rate_limits/       # Rate limiting tracking files
└── public/
    ├── .htaccess          # Public URL rewriting
    ├── index.php          # Single entry point
    └── uploads/           # User-uploaded files
```

---

## 🚀 Entry Point

### `public/index.php`
**Purpose**: Single entry point for all API requests (Front Controller pattern)

**What it does**:
1. Prevents CLI execution (security)
2. Handles PHP built-in server for static files
3. Defines base paths (`BASE_PATH`, `APP_PATH`, `PUBLIC_PATH`)
4. Loads the bootstrap file
5. Creates and runs the `App` class
6. Catches all exceptions and returns proper JSON error responses
7. Shows debug info only in development mode

**Flow**:
```
HTTP Request
      │
      ▼
┌─────────────┐
│  index.php  │ 1. Entry Point
└──────┬──────┘
      │
      ▼
┌──────────────┐
│ bootstrap.php│ 2. Init & Config
└──────┬───────┘
      │
      ▼
┌──────────────┐
│  App Class   │ 3. Core Logic
└──────┬───────┘
      │
      ▼
┌──────────────┐      ┌────────────┐
│   Router     │─────►│ Controller │ 4. Process
└──────────────┘      └─────┬──────┘
                            │
      ┌─────────────────────┘
      │
      ▼
┌──────────────┐
│  Response    │ 5. JSON Output
└──────────────┘
```

---

## ⚙️ Core Application

### `app/bootstrap.php`
**Purpose**: Initializes all core components and handles application lifecycle

**What it does**:

1. **Loads Configuration**
   ```php
   require_once APP_PATH . '/config/config.php';
   ```

2. **Registers Autoloader**
   - Automatically loads classes from: `models/`, `controllers/`, `middleware/`, `utils/`
   - Uses `spl_autoload_register()` for lazy loading

3. **Loads Core Files**
   - Response.php, JWT.php, Validator.php, Router.php, database.php

4. **Defines the `App` Class**:
   - `__construct()`: Sets up CORS, initializes database, creates router, registers routes
   - `handleCors()`: Sets CORS headers for cross-origin requests
   - `initDatabase()`: Creates PDO database connection
   - `registerRoutes()`: Loads all route files from `api/` folder
   - `run()`: Dispatches the request to the router

**CORS Headers Set**:
- `Access-Control-Allow-Origin`: Allows specific frontend origins
- `Access-Control-Allow-Methods`: GET, POST, PUT, DELETE, PATCH, OPTIONS
- `Access-Control-Allow-Headers`: Content-Type, Authorization, etc.
- `Access-Control-Allow-Credentials`: true
- `Access-Control-Max-Age`: 86400 (24 hours cache)

---

## 🔧 Configuration

### `app/config/config.php`
**Purpose**: Central configuration for the entire application

**Sections**:

```
┌─────────────────────────┬──────────────────────────────┬──────────────────────────────────────────┐
│ Constant                │ Purpose                      │ Default Value                            │
├─────────────────────────┼──────────────────────────────┼──────────────────────────────────────────┤
│ APP_ENV                 │ Environment mode             │ 'development'                            │
│ APP_NAME                │ Application name             │ 'Catarman Dog Pound Management System'   │
│ APP_VERSION             │ Version number               │ '1.0.0'                                  │
│ BASE_URL                │ API base URL                 │ 'http://localhost:8000'                  │
│ FRONTEND_URL            │ Frontend URL for CORS        │ 'http://localhost:3000'                  │
│ JWT_SECRET              │ Token signing key            │ Should be changed in production!         │
│ JWT_EXPIRY              │ Access token life            │ 86400 (24 hours)                         │
│ JWT_REFRESH_EXPIRY      │ Refresh token life           │ 604800 (7 days)                          │
│ ALLOWED_ORIGINS         │ CORS whitelist               │ Array of allowed URLs                    │
│ UPLOAD_PATH             │ File upload directory        │ /uploads/                        │
│ MAX_FILE_SIZE           │ Max upload size              │ 5MB                                      │
│ ALLOWED_EXTENSIONS      │ Permitted file types         │ ['jpg', 'jpeg', 'png', 'gif', 'webp']    │
│ DEFAULT_PAGE_SIZE       │ Pagination default           │ 20                                       │
│ MAX_PAGE_SIZE           │ Pagination maximum           │ 100                                      │
│ PASSWORD_MIN_LENGTH     │ Minimum password             │ 8                                        │
│ MAX_LOGIN_ATTEMPTS      │ Before lockout               │ 5                                        │
│ LOCKOUT_TIME            │ Lockout duration             │ 900 (15 minutes)                         │
│ RATE_LIMIT_ENABLED      │ Enable/disable rate limiting │ true                                     │
│ RATE_LIMIT_LOGIN_MAX    │ Max login attempts per window│ 10                                       │
│ RATE_LIMIT_LOGIN_WINDOW │ Login rate limit window      │ 60 (1 minute)                            │
│ RATE_LIMIT_API_MAX      │ Max API requests per window  │ 100                                      │
│ RATE_LIMIT_API_WINDOW   │ API rate limit window        │ 60 (1 minute)                            │
│ ADOPTION_FEE_DOG        │ Dog adoption fee             │ 500.00                                   │
│ ADOPTION_FEE_CAT        │ Cat adoption fee             │ 300.00                                   │
└─────────────────────────┴──────────────────────────────┴──────────────────────────────────────────┘
```

---

### `app/config/database.php`
**Purpose**: Database connection configuration and PDO wrapper

**Class: `Database`**

**Properties**:
```php
private $host = "127.0.0.1";
private $port = "3307";
private $database_name = "catarman_dog_pound_db";
private $username = "root";
private $password = "";
private $charset = "utf8mb4";
```

**Methods**:

```
┌────────────────────┬────────────────────────────────────────────────────────┐
│ Method             │ Purpose                                                │
├────────────────────┼────────────────────────────────────────────────────────┤
│ getConnection()    │ Returns PDO instance (lazy loading)                    │
│ getInstance()      │ Singleton pattern - returns single Database instance     │
│ beginTransaction() │ Starts database transaction                            │
│ commit()           │ Commits current transaction                            │
│ rollback()         │ Rolls back current transaction                         │
└────────────────────┴────────────────────────────────────────────────────────┘
```

**PDO Options Set**:
- `ERRMODE_EXCEPTION`: Throws exceptions on errors
- `FETCH_ASSOC`: Returns associative arrays
- `EMULATE_PREPARES`: true (supports reused named parameters)
- `utf8mb4`: Full Unicode support including emojis

---

## 🛠️ Utilities

### `app/utils/Router.php`
**Purpose**: URL routing and request dispatching

**How Routing Works**:

1. **Route Registration**: Controllers register routes with HTTP method, path, handler, and required roles
   ```php
   $router->get('/animals', 'AnimalController@index', ['Admin', 'Staff']);
   $router->post('/auth/login', 'AuthController@login'); // No auth required
   ```

2. **Path Parameters**: `{id}` in path becomes regex capture group
   ```php
   '/animals/{id}' → '#^/animals/(?P<id>[^/]+)$#'
   ```

3. **Dispatch Process**:
   - Get HTTP method and URI
   - Loop through registered routes
   - Match method and pattern
   - Extract path parameters
   - Check authentication if roles specified
   - Call controller method

**Methods**:

```
┌─────────────────────────────────┬──────────────────────────────────────────┐
│ Method                          │ Purpose                                  │
├─────────────────────────────────┼──────────────────────────────────────────┤
│ get($path, $handler, $roles)    │ Register GET route                       │
│ post($path, $handler, $roles)   │ Register POST route                      │
│ put($path, $handler, $roles)    │ Register PUT route                       │
│ delete($path, $handler, $roles) │ Register DELETE route                    │
│ patch($path, $handler, $roles)  │ Register PATCH route                     │
│ dispatch()                      │ Process incoming request                 │
│ authenticate()                  │ Verify JWT token                         │
│ authorize($roles)               │ Check user has required role             │
└─────────────────────────────────┴──────────────────────────────────────────┘
```

**Role Authorization**:
- `null`: No authentication required (public route)
- `['*']`: Any authenticated user
- `['Admin']`: Only Admin role
- `['Admin', 'Staff']`: Admin OR Staff

---

### `app/utils/Response.php`
**Purpose**: Standardized JSON API response formatting

**Methods**:

```
┌──────────────────────────────────────────────┬───────────┬─────────────────────────┐
│ Method                                       │ HTTP Code │ Purpose                 │
├──────────────────────────────────────────────┼───────────┼─────────────────────────┤
│ success($data, $message, $code)              │ 200       │ Successful operation    │
│ created($data, $message)                     │ 201       │ Resource created        │
│ error($message, $code, $errors)              │ 4xx       │ Client error            │
│ validationError($errors)                     │ 422       │ Validation failed       │
│ unauthorized($message)                       │ 401       │ Authentication required │
│ forbidden($message)                          │ 403       │ Access denied           │
│ notFound($message)                           │ 404       │ Resource not found      │
│ serverError($message)                        │ 500       │ Server error            │
│ paginated($data, $page, $perPage, $total)    │ 200       │ Paginated list          │
└──────────────────────────────────────────────┴───────────┴─────────────────────────┘
```

**Response Format**:
```json
{
  "success": true,
  "message": "Success message",
  "data": { ... },
  "timestamp": "2025-12-25T10:30:00+08:00"
}
```

**Paginated Response**:
```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total_items": 150,
    "total_pages": 8,
    "has_next": true,
    "has_prev": false,
    "next_page": 2,
    "prev_page": null
  }
}
```

---

### `app/utils/JWT.php`
**Purpose**: JSON Web Token generation and verification

**How JWT Works**:

1. **Structure**: `header.payload.signature`
   - Header: `{"typ": "JWT", "alg": "HS256"}`
   - Payload: User data + claims (iat, exp, jti)
   - Signature: HMAC-SHA256 hash of header + payload

2. **Claims Used**:
   - `iat`: Issued at timestamp
   - `exp`: Expiration timestamp
   - `jti`: Unique token ID
   - `user_id`: User's database ID
   - `role`: User's role name

**Methods**:

```
┌─────────────────────────────────┬──────────────────────────────────────────┐
│ Method                          │ Purpose                                  │
├─────────────────────────────────┼──────────────────────────────────────────┤
│ generate($payload, $expiry)     │ Create access token                      │
│ verify($token)                  │ Verify and decode token                  │
│ decode($token)                  │ Decode without verification (debug only) │
│ generateRefreshToken($userId)   │ Create refresh token (7 day expiry)      │
│ isExpired($token)               │ Check if token expired                   │
│ getExpiresIn($token)            │ Seconds until expiration                 │
└─────────────────────────────────┴──────────────────────────────────────────┘
```

**Security Features**:
- Uses `hash_equals()` for timing-safe signature comparison
- Random `jti` prevents token replay attacks
- Checks `exp` and `nbf` (not before) claims

---

### `app/utils/Validator.php`
**Purpose**: Input validation with chainable methods

**Usage**:
```php
$validator = Validator::make($data, [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'age' => 'integer|min:18'
]);

if ($validator->fails()) {
    Response::validationError($validator->getErrors());
}
```

**Available Rules**:

```
┌───────────────────────┬────────────────────────────────┬────────────────────────────────────┐
│ Rule                  │ Purpose                        │ Example                            │
├───────────────────────┼────────────────────────────────┼────────────────────────────────────┤
│ required              │ Field must exist and not empty │ 'name' => 'required'               │
│ email                 │ Valid email format             │ 'email' => 'email'                 │
│ min:n                 │ Minimum length/value           │ 'password' => 'min:8'              │
│ max:n                 │ Maximum length/value           │ 'name' => 'max:50'                 │
│ numeric               │ Must be numeric                │ 'price' => 'numeric'               │
│ integer               │ Must be integer                │ 'age' => 'integer'                 │
│ positive              │ Must be > 0                    │ 'quantity' => 'positive'           │
│ in:a,b,c              │ Must be in list                │ 'status' => 'in:active,inactive'   │
│ date                  │ Valid date format              │ 'birth_date' => 'date'             │
│ phone                 │ Valid phone format             │ 'contact' => 'phone'               │
│ url                   │ Valid URL format               │ 'website' => 'url'                 │
│ alpha                 │ Letters only                   │ 'name' => 'alpha'                  │
│ alphanumeric          │ Letters and numbers            │ 'username' => 'alphanumeric'       │
│ unique:table,column   │ Unique in database             │ 'email' => 'unique:Users,Email'    │
└───────────────────────┴────────────────────────────────┴────────────────────────────────────┘
```

---

### `app/utils/RateLimiter.php`
**Purpose**: Rate limiting utility to prevent brute force attacks and API abuse

**Features**:
- File-based storage (no database required)
- Configurable limits per endpoint type
- IP-based tracking for unauthenticated requests
- Automatic cleanup of expired entries

**Methods**:

```
┌───────────────────────────────────────────┬────────────────────────────────────┐
│ Method                                    │ Purpose                            │
├───────────────────────────────────────────┼────────────────────────────────────┤
│ check($type, $id, $max, $window)          │ Check and enforce rate limit       │
│ checkGlobal()                             │ Apply global API rate limiting     │
│ checkLogin($identifier)                   │ Apply stricter login rate limiting │
│ getRemaining($type, $id, $max, $window)  │ Get remaining attempts             │
│ reset($type, $identifier)                 │ Reset rate limit for identifier    │
│ cleanup($maxAge)                          │ Clear expired rate limit data      │
└───────────────────────────────────────────┴────────────────────────────────────┘
```

**Usage**:
```php
// Check login rate limit (10 attempts per 60 seconds)
RateLimiter::checkLogin();

// Custom rate limit
RateLimiter::check('api', $userId, 100, 60);
```

**Response on Limit Exceeded** (HTTP 429):
```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again in 45 seconds.",
  "retry_after": 45
}
```

---

### `app/utils/Sanitizer.php`
**Purpose**: Input sanitization utility to prevent XSS and injection attacks

**Features**:
- Automatic HTML entity escaping
- Control character removal
- Recursive array sanitization
- Type-specific sanitization (email, integer, URL, filename)
- Password field preservation (not sanitized)

**Methods**:

```
┌───────────────────────────────┬───────────────────────────────────────────┐
│ Method                        │ Purpose                                   │
├───────────────────────────────┼───────────────────────────────────────────┤
│ string($value, $allowHtml)    │ Sanitize string, escape HTML              │
│ email($value)                 │ Validate and normalize email              │
│ integer($value, $default)     │ Extract integer value                     │
│ float($value, $default)       │ Extract decimal value                     │
│ boolean($value, $default)     │ Parse boolean value                       │
│ url($value)                   │ Validate and sanitize URL                 │
│ stripTags($value, $allowed)   │ Remove HTML tags                          │
│ stripDangerousTags($value)    │ Remove only dangerous HTML                │
│ filename($value)              │ Sanitize filename (prevent traversal)     │
│ array($data, $options)        │ Recursively sanitize array                │
│ request($data)                │ Sanitize entire request data              │
└───────────────────────────────┴───────────────────────────────────────────┘
```

**Usage**:
```php
// Sanitize entire request automatically (done in BaseController)
$data = Sanitizer::request($_POST);

// Sanitize specific value
$name = Sanitizer::string($input['name']);
$email = Sanitizer::email($input['email']);
$id = Sanitizer::integer($input['id']);
```

**Note**: The `Sanitizer` is automatically applied to all request data in `BaseController::getRequestData()`.

---

## 🔐 Middleware

### `app/middleware/AuthMiddleware.php`
**Purpose**: JWT authentication and role-based access control

**Methods**:

```
┌───────────────────────┬──────────────────────────────────────┐
│ Method                │ Purpose                              │
├───────────────────────┼──────────────────────────────────────┤
│ authenticate()        │ Verify token, load user from database│
│ requireRole($roles)   │ Ensure user has required role        │
│ hasRole($roles)       │ Check role without error             │
│ isAdmin()             │ Check if user is Admin               │
│ isStaff()             │ Check if user is Staff               │
│ isVeterinarian()      │ Check if user is Veterinarian        │
│ isAdopter()           │ Check if user is Adopter             │
│ getCurrentUser()      │ Get authenticated user data          │
│ getUserId()           │ Get current user's ID                │
└───────────────────────┴──────────────────────────────────────┘
```

**Authentication Flow**:
1. Extract Bearer token from `Authorization` header
2. Verify JWT signature and expiration
3. Query database to ensure user exists and is active
4. Check account status (Active, Suspended, Pending)
5. Store user data for controller access

---

## 🎮 Controllers

### `app/controllers/BaseController.php`
**Purpose**: Abstract base class with common functionality for all controllers

**Properties**:
- `$db`: PDO database connection
- `$user`: Current authenticated user
- `$requestData`: Parsed request body

**Helper Methods**:

```
┌─────────────────────────────────┬─────────────────────────────────┐
│ Method                          │ Purpose                         │
├─────────────────────────────────┼─────────────────────────────────┤
│ query($key, $default)           │ Get URL query parameter         │
│ input($key, $default)           │ Get request body field          │
│ all()                           │ Get all request data            │
│ only(['field1', 'field2'])      │ Get specific fields only        │
│ except(['password'])            │ Get all except specified fields │
│ has($key)                       │ Check if field exists           │
│ getPagination()                 │ Extract page/per_page params    │
│ validate($rules)                │ Validate input data             │
│ logActivity($type, $desc)       │ Log to Activity_Logs table      │
│ isOwner($userId)                │ Check if user owns resource     │
│ hasRole($roles)                 │ Check user role                 │
│ handleFileUpload($field, $folder)│ Process uploaded file          │
└─────────────────────────────────┴─────────────────────────────────┘
```

---

### `app/controllers/AuthController.php`
**Purpose**: Handles authentication (login, register, tokens)

**Endpoints**:

```
┌────────┬─────────────────────────┬───────────────────────────────────────┐
│ Method │ Endpoint                │ Purpose                               │
├────────┼─────────────────────────┼───────────────────────────────────────┤
│ POST   │ /auth/login             │ User login                            │
│ POST   │ /auth/register          │ Public registration (creates Adopter) │
│ POST   │ /auth/refresh           │ Refresh access token                  │
│ POST   │ /auth/logout            │ Logout (for logging)                  │
│ POST   │ /auth/logout-all        │ Invalidate all sessions               │
│ POST   │ /auth/forgot-password   │ Request password reset                │
│ POST   │ /auth/reset-password    │ Reset with token                      │
└────────┴─────────────────────────┴───────────────────────────────────────┘
```

**Login Flow**:
1. Validate input (email/username + password)
2. Find user in database
3. Verify password with `password_verify()`
4. Check account status
5. Generate access + refresh tokens
6. Log successful login
7. Return tokens + user data

---

### `app/controllers/UserController.php`
**Purpose**: User CRUD operations and profile management

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬───────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose               │
├────────┼─────────────────────────┼──────────────┼───────────────────────┤
│ GET    │ /users                  │ Admin        │ List all users        │
│ GET    │ /users/{id}             │ Admin/Owner  │ Get user details      │
│ POST   │ /users                  │ Admin        │ Create new user       │
│ PUT    │ /users/{id}             │ Admin/Owner  │ Update user           │
│ DELETE │ /users/{id}             │ Admin        │ Soft delete user      │
│ GET    │ /users/profile/me       │ Any          │ Get own profile       │
│ PUT    │ /users/profile/me       │ Any          │ Update own profile    │
│ POST   │ /users/profile/avatar   │ Any          │ Upload avatar         │
│ PUT    │ /users/{id}/status      │ Admin        │ Change account status │
│ PUT    │ /users/{id}/role        │ Admin        │ Change user role      │
└────────┴─────────────────────────┴──────────────┴───────────────────────┘
```

---

### `app/controllers/AnimalController.php`
**Purpose**: Animal record management

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬────────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose                │
├────────┼─────────────────────────┼──────────────┼────────────────────────┤
│ GET    │ /animals/available      │ Public       │ Available for adoption │
│ GET    │ /animals/{id}           │ Public       │ Single animal details  │
│ GET    │ /animals                │ Staff+       │ List all animals       │
│ GET    │ /animals/stats/summary  │ Staff+       │ Statistics             │
│ POST   │ /animals                │ Staff+       │ Create animal          │
│ PUT    │ /animals/{id}           │ Staff+       │ Update animal          │
│ DELETE │ /animals/{id}             │ Admin        │ Soft delete            │
│ PATCH  │ /animals/{id}/status    │ Staff+       │ Update status only     │
│ POST   │ /animals/{id}/image     │ Staff+       │ Upload image           │
│ POST   │ /animals/{id}/impound   │ Staff+       │ Add impound record     │
└────────┴─────────────────────────┴──────────────┴────────────────────────┘
```

---

### `app/controllers/AdoptionController.php`
**Purpose**: Adoption request management

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬─────────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose                 │
├────────┼─────────────────────────┼──────────────┼─────────────────────────┤
│ GET    │ /adoptions              │ Staff+       │ List all requests       │
│ GET    │ /adoptions/{id}         │ Any          │ Get request details     │
│ POST   │ /adoptions              │ Any          │ Submit adoption request │
│ PUT    │ /adoptions/{id}         │ Staff+       │ Update request          │
│ DELETE │ /adoptions/{id}         │ Admin        │ Delete request          │
│ PATCH  │ /adoptions/{id}/status  │ Staff+       │ Approve/reject          │
│ GET    │ /adoptions/my/requests  │ Adopter      │ Own requests            │
└────────┴─────────────────────────┴──────────────┴─────────────────────────┘
```

---

### `app/controllers/MedicalController.php`
**Purpose**: Medical record management

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose            │
├────────┼─────────────────────────┼──────────────┼────────────────────┤
│ GET    │ /medical                │ Staff+       │ List all records   │
│ GET    │ /medical/{id}           │ Staff+       │ Get record details │
│ POST   │ /medical                │ Vet          │ Create record      │
│ PUT    │ /medical/{id}           │ Vet          │ Update record      │
│ DELETE │ /medical/{id}             │ Admin        │ Delete record      │
│ GET    │ /medical/animal/{id}    │ Staff+       │ Records for animal │
│ GET    │ /medical/veterinarians  │ Staff+       │ List veterinarians │
└────────┴─────────────────────────┴──────────────┴────────────────────┘
```

---

### `app/controllers/InventoryController.php`
**Purpose**: Inventory/supplies management

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose            │
├────────┼─────────────────────────┼──────────────┼────────────────────┤
│ GET    │ /inventory              │ Staff+       │ List all items     │
│ GET    │ /inventory/{id}         │ Staff+       │ Get item details   │
│ POST   │ /inventory              │ Staff+       │ Add item           │
│ PUT    │ /inventory/{id}         │ Staff+       │ Update item        │
│ DELETE │ /inventory/{id}         │ Admin        │ Delete item        │
│ POST   │ /inventory/{id}/adjust  │ Staff+       │ Adjust quantity    │
│ GET    │ /inventory/low-stock    │ Staff+       │ Low stock alerts   │
└────────┴─────────────────────────┴──────────────┴────────────────────┘
```

---

### `app/controllers/BillingController.php`
**Purpose**: Invoice and payment management

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose            │
├────────┼─────────────────────────┼──────────────┼────────────────────┤
│ GET    │ /billing/invoices       │ Staff+       │ List invoices      │
│ GET    │ /billing/invoices/{id}  │ Staff+       │ Invoice details    │
│ POST   │ /billing/invoices       │ Staff+       │ Create invoice     │
│ PUT    │ /billing/invoices/{id}  │ Staff+       │ Update invoice     │
│ DELETE │ /billing/invoices/{id}  │ Admin        │ Delete invoice     │
│ GET    │ /billing/payments       │ Staff+       │ List payments      │
│ POST   │ /billing/payments       │ Staff+       │ Record payment     │
│ GET    │ /billing/reports        │ Admin        │ Financial reports  │
└────────┴─────────────────────────┴──────────────┴────────────────────┘
```

---

### `app/controllers/DashboardController.php`
**Purpose**: Dashboard statistics and activity logs

**Endpoints**:

```
┌────────┬─────────────────────────┬──────────────┬────────────────────┐
│ Method │ Endpoint                │ Auth         │ Purpose            │
├────────┼─────────────────────────┼──────────────┼────────────────────┤
│ GET    │ /dashboard/stats        │ Staff+       │ Statistics summary │
│ GET    │ /dashboard/activities   │ Staff+       │ Recent activities  │
│ GET    │ /dashboard/charts       │ Staff+       │ Chart data         │
│ GET    │ /dashboard/overdue      │ Staff+       │ Overdue tasks      │
└────────┴─────────────────────────┴──────────────┴────────────────────┘
```

---

### `app/controllers/NotificationController.php`
**Purpose**: User notification management

**Endpoints**:

```
┌────────┬────────────────────────────┬──────────┬─────────────────────┐
│ Method │ Endpoint                   │ Auth     │ Purpose             │
├────────┼────────────────────────────┼──────────┼─────────────────────┤
│ GET    │ /notifications             │ Any      │ User's notifications│
│ GET    │ /notifications/unread-count│ Any      │ Unread count        │
│ PUT    │ /notifications/{id}/read   │ Any      │ Mark as read        │
│ PUT    │ /notifications/read-all    │ Any      │ Mark all read       │
│ DELETE │ /notifications/{id}        │ Any      │ Delete notification │
└────────┴────────────────────────────┴──────────┴─────────────────────┘
```

---

## 📊 Models

### `app/models/User.php`
**Purpose**: User database operations

**Methods**:
- `find($id)` - Find by ID
- `findByEmail($email)` - Find by email
- `findByUsername($username)` - Find by username
- `paginate($page, $perPage, $filters)` - Get paginated list
- `create($data)` - Create new user
- `update($id, $data)` - Update user
- `delete($id)` - Soft delete
- `updatePassword($id, $hash)` - Change password
- `updateStatus($id, $status)` - Change account status

---

### `app/models/Animal.php`
**Purpose**: Animal database operations

**Methods**:
- `find($id)` - Find by ID
- `findWithRelations($id)` - Find with medical, impound, feeding records
- `paginate($page, $perPage, $filters)` - Get paginated list
- `getAvailable($page, $perPage)` - Available for adoption
- `create($data)` - Create new animal
- `update($id, $data)` - Update animal
- `delete($id)` - Soft delete
- `updateStatus($id, $status)` - Change status
- `getStatistics()` - Count by status/type

---

### Other Models

```
┌─────────────────────┬───────────────────┬───────────────────────┐
│ Model               │ Table             │ Purpose               │
├─────────────────────┼───────────────────┼───────────────────────┤
│ AdoptionRequest.php │ Adoption_Requests │ Adoption applications │
│ MedicalRecord.php   │ Medical_Records   │ Veterinary records    │
│ Inventory.php       │ Inventory         │ Supplies tracking     │
│ Invoice.php         │ Invoices          │ Billing invoices      │
│ Payment.php         │ Payments          │ Payment records       │
│ ActivityLog.php     │ Activity_Logs     │ User activity tracking│
│ ImpoundRecord.php   │ Impound_Records   │ Animal intake records │
│ FeedingRecord.php   │ Feeding_Records   │ Feeding logs          │
│ Veterinarian.php    │ Veterinarians     │ Vet information       │
│ Role.php            │ Roles             │ User roles            │
└─────────────────────┴───────────────────┴───────────────────────┘
```

---

## 🛣️ API Routes

### `app/api/auth.php`
Defines authentication routes (login, register, tokens)

### `app/api/users.php`
Defines user management routes

### `app/api/animals.php`
Defines animal CRUD routes

### `app/api/adoptions.php`
Defines adoption request routes

### `app/api/medical.php`
Defines medical record routes

### `app/api/inventory.php`
Defines inventory management routes

### `app/api/billing.php`
Defines invoice and payment routes

### `app/api/dashboard.php`
Defines dashboard statistics routes

### `app/api/notifications.php`
Defines notification routes

---

## 🔒 Security Summary

```
┌────────────────────┬─────────────────────────────────────────────────────────┐
│ Feature            │ Implementation                                          │
├────────────────────┼─────────────────────────────────────────────────────────┤
│ Password Storage   │ password_hash() with bcrypt                             │
│ Authentication     │ JWT tokens (HS256)                                      │
│ SQL Injection      │ PDO prepared statements                                 │
│ XSS Prevention     │ Sanitizer class auto-sanitizes all input                │
│ CORS               │ Whitelist of allowed origins                            │
│ Rate Limiting      │ RateLimiter class (10 login/min, 100 API/min)           │
│ Session Security   │ Stateless (JWT-based)                                   │
│ Input Validation   │ Validator class with comprehensive rules                │
│ Input Sanitization │ Sanitizer class (HTML escaping, control char removal)   │
└────────────────────┴─────────────────────────────────────────────────────────┘
```

---

## 📝 Request/Response Examples

### Login Request
```http
POST /auth/login
Content-Type: application/json

{
  "email": "admin@dogpound.com",
  "password": "password123"
}
```

### Login Response
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User",
      "email": "admin@dogpound.com",
      "role": "Admin"
    }
  },
  "timestamp": "2025-12-25T10:00:00+08:00"
}
```

### Authenticated Request
```http
GET /animals
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Error Response
```json
{
  "success": false,
  "message": "Invalid or expired token",
  "timestamp": "2025-12-25T10:00:00+08:00"
}
```
