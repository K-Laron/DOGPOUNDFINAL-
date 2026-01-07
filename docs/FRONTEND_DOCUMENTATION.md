# 📚 Frontend Code Documentation

## Catarman Dog Pound Management System

This document provides a detailed explanation of every frontend file, its purpose, and how it works.

---

## 📂 Directory Overview

```text
frontend/
├── index.html                 # SPA entry point
└── assets/
    ├── css/                   # Stylesheets
    │   ├── variables.css      # Design system tokens
    │   ├── main.css           # Base styles & reset
    │   ├── components.css     # UI component styles
    │   ├── layouts.css        # Page layouts
    │   ├── animations.css     # Transitions & effects
    │   ├── responsive.css     # Media queries
    │   └── enhancements.css   # Enhanced UI features
    │
    ├── images/                # Static images
    │
    └── js/                    # JavaScript
        ├── app.js             # Main application bootstrap
        ├── api.js             # HTTP client for backend
        ├── auth.js            # Authentication handler
        ├── router.js          # Client-side routing
        ├── store.js           # State management
        ├── utils.js           # Helper functions
        │
        ├── components/        # Reusable UI components
        │   ├── Card.js
        │   ├── Charts.js
        │   ├── DataTable.js
        │   ├── Form.js
        │   ├── Header.js
        │   ├── HoverPreview.js
        │   ├── Loading.js
        │   ├── Modal.js
        │   ├── PDFPreview.js
        │   ├── Sidebar.js
        │   └── Toast.js
        │
        └── pages/             # Page controllers
            ├── Login.js
            ├── Dashboard.js
            ├── Animals.js
            ├── AnimalDetail.js
            ├── Adoptions.js
            ├── Medical.js
            ├── Inventory.js
            ├── Billing.js
            ├── Users.js
            ├── Profile.js
            └── Settings.js
```

---

## 🏗️ Frontend Architecture

```text
┌──────────────────────────────────────────────────────────┐
│                   SINGLE PAGE APPLICATION                 │
└────────────────────────────┬─────────────────────────────┘
                             │
     ┌───────────────────────▼───────────────────────┐
     │                  main.js                      │
     │         (Bootstrap & Initialization)          │
     └───────────┬───────────────────────┬───────────┘
                 │                       │
      ┌──────────▼─────────┐   ┌─────────▼──────────┐
      │     Router         │   │      Store         │
      │ (URL Management)   │   │ (State Management) │
      └──────────┬─────────┘   └─────────┬──────────┘
                 │                       │
      ┌──────────▼───────────────────────▼──────────┐
      │               Page Components               │
      │   (Dashboard, Animals, Adoptions, etc.)     │
      └──────────────────────┬──────────────────────┘
                             │
                  ┌──────────▼──────────┐
                  │       API Client    │
                  │   (Fetch Wrapper)   │
                  └──────────┬──────────┘
                             │
                             ▼
                    Backend API (JSON)
```

---

## 🏠 Entry Point

### `index.html`

**Purpose**: Single Page Application (SPA) entry point

**What it includes**:

1. **Meta Tags**
   - Viewport settings for mobile
   - PWA capabilities (mobile web app)
   - Theme color (#007AFF)

2. **CSS Files** (loaded in order):

   ```html
   <link rel="stylesheet" href="assets/css/variables.css">
   <link rel="stylesheet" href="assets/css/main.css">
   <link rel="stylesheet" href="assets/css/components.css">
   <link rel="stylesheet" href="assets/css/layouts.css">
   <link rel="stylesheet" href="assets/css/animations.css">
   <link rel="stylesheet" href="assets/css/responsive.css">
   <link rel="stylesheet" href="assets/css/enhancements.css">
   ```

3. **External Libraries**:
   - Chart.js - For dashboard charts
   - jsPDF - For PDF report generation
   - jsPDF-AutoTable - For PDF tables

4. **DOM Structure**:

   ```html
   <div id="loading-screen">     <!-- Initial loading -->
   <div id="auth-container">     <!-- Login/Register pages -->
   <div id="main-container">     <!-- Authenticated app -->
       <header id="header">      <!-- Top navigation -->
       <aside id="sidebar">      <!-- Side navigation -->
       <main id="page-content">  <!-- Dynamic content -->
   </div>
   <div id="modal-container">    <!-- Modals render here -->
   <div id="toast-container">    <!-- Notifications -->
   ```

5. **Script Loading Order**:
   - Core: utils → api → store → auth → router
   - Components: Toast → Modal → Loading → ...
   - Pages: Login → Dashboard → Animals → ...
   - App initialization last

---

## 🚀 Core JavaScript Files

### `assets/js/app.js`

**Purpose**: Main application bootstrap and initialization

**Properties**:

```text
┌─────────────┬────────────────────────────────────────┐
│ Property    │ Purpose                                │
├─────────────┼────────────────────────────────────────┤
│ version     │ Application version ('1.0.0')          │
│ debug       │ Debug mode flag (false for production) │
└─────────────┴────────────────────────────────────────┘
```

**Methods**:

```text
┌─────────────────────────┬────────────────────────┐
│ Method                  │ Purpose                │
├─────────────────────────┼────────────────────────┤
│ init()                  │ Initialize entire app  │
│ showLoading()           │ Show loading screen    │
│ hideLoading()           │ Hide loading screen    │
│ setupEventListeners()   │ Global event handlers  │
│ setupErrorHandlers()    │ Catch unhandled errors │
│ initScrollToTop()       │ Scroll-to-top button   │
│ initPullToRefresh()     │ Mobile pull-to-refresh │
│ initOnboarding()        │ New user onboarding    │
│ log()                   │ Debug logging          │
└─────────────────────────┴────────────────────────┘
```

**Initialization Flow**:

```text
1. Show loading screen
2. Load persisted state (localStorage)
3. Initialize Auth (check tokens)
4. Initialize Router
5. Setup event listeners
6. Setup error handlers
7. Hide loading screen
```

---

### `assets/js/api.js`

**Purpose**: HTTP client for all backend API calls

**Configuration**:

```javascript
baseURL: 'http://localhost:8000'
timeout: 30000 (30 seconds)
defaultHeaders: { 'Content-Type': 'application/json' }
```

**Core Methods**:

```text
┌─────────────────────────────────────────────┬───────────────────┐
│ Method                                      │ Purpose           │
├─────────────────────────────────────────────┼───────────────────┤
│ request(method, endpoint, data, options)    │ Base HTTP request │
│ get(endpoint, params)                       │ GET request       │
│ post(endpoint, data)                        │ POST request      │
│ put(endpoint, data)                         │ PUT request       │
│ delete(endpoint)                            │ DELETE request    │
│ patch(endpoint, data)                       │ PATCH request     │
│ upload(endpoint, formData)                  │ File upload       │
│ handleError(response, data)                 │ Error handler     │
└─────────────────────────────────────────────┴───────────────────┘
```

**API Namespaces**:

```text
┌─────────────────────┬────────────────────────────────────────────┐
│ Namespace           │ Endpoints                                  │
├─────────────────────┼────────────────────────────────────────────┤
│ API.auth            │ login, register, refresh, logout           │
│ API.users           │ profile, list, create, update, delete      │
│ API.animals         │ list, get, create, update, delete, upload  │
│ API.adoptions       │ list, get, create, update, approve, reject │
│ API.medical         │ list, get, create, update, delete          │
│ API.inventory       │ list, get, create, update, adjust          │
│ API.billing         │ invoices, payments, reports                │
│ API.dashboard       │ stats, activities, charts                  │
│ API.notifications   │ list, markRead, delete                     │
└─────────────────────┴────────────────────────────────────────────┘
```

**Usage Example**:

```javascript
// Get all animals
const response = await API.animals.list({ page: 1, status: 'Available' });

// Create animal
const animal = await API.animals.create({
    name: 'Max',
    type: 'Dog',
    breed: 'Golden Retriever'
});
```

**Features**:

- Automatic JWT token injection
- Request timeout with AbortController
- Cache busting for GET requests
- FormData support for file uploads
- Automatic token refresh on 401

---

### `assets/js/auth.js`

**Purpose**: Authentication handler for login, logout, and session management

**Storage Keys**:

```javascript
TOKEN_KEY: 'access_token'
REFRESH_TOKEN_KEY: 'refresh_token'
USER_KEY: 'user'
```

**Methods**:

```text
┌─────────────────────────────┬────────────────────────────────────────┐
│ Method                      │ Purpose                                │
├─────────────────────────────┼────────────────────────────────────────┤
│ init()                      │ Initialize auth, validate session      │
│ login(username, password)   │ User login                             │
│ register(data)              │ User registration                      │
│ logout()                    │ Clear session, redirect to login       │
│ refreshToken()              │ Refresh access token                   │
│ getToken()                  │ Get access token from storage          │
│ setToken(token)             │ Save access token                      │
│ getUser()                   │ Get current user data                  │
│ setUser(user)               │ Save user data                         │
│ clearSession()              │ Clear all auth data                    │
│ isAuthenticated()           │ Check if logged in                     │
│ currentUser()               │ Get current user object                │
│ isAdmin()                   │ Check if user is Admin                 │
│ isStaff()                   │ Check if Admin or Staff                │
│ isVeterinarian()            │ Check if Veterinarian                  │
│ isAdopter()                 │ Check if Adopter                       │
└─────────────────────────────┴────────────────────────────────────────┘
```

**Route Guards**:

```javascript
Auth.requireAuth()    // Must be logged in
Auth.requireGuest()   // Must NOT be logged in
Auth.requireAdmin()   // Must be Admin
Auth.requireStaff()   // Must be Admin or Staff
```

**Token Refresh Flow**:

1. Access token expires after 24 hours
2. Auto-refresh 5 minutes before expiry
3. Uses refresh token (7 day expiry)
4. If refresh fails, logout user

---

### `assets/js/router.js`

**Purpose**: Client-side routing for SPA navigation

**Route Structure**:

```javascript
{
    page: 'dashboard',           // Page identifier
    title: 'Dashboard',          // Browser title
    component: DashboardPage,    // Page component object
    guard: () => Auth.requireAuth(),  // Access guard
    layout: 'default'            // Layout type
}
```

**Registered Routes**:

```text
┌────────────────┬───────────────────┬───────┬──────────┐
│ Path           │ Component         │ Guard │ Layout   │
├────────────────┼───────────────────┼───────┼──────────┤
│ /login         │ LoginPage         │ Guest │ auth     │
│ /register      │ LoginPage         │ Guest │ auth     │
│ /              │ DashboardPage     │ Auth  │ default  │
│ /dashboard     │ DashboardPage     │ Auth  │ default  │
│ /animals       │ AnimalsPage       │ Auth  │ default  │
│ /animals/:id   │ AnimalDetailPage  │ Auth  │ default  │
│ /adoptions     │ AdoptionsPage     │ Auth  │ default  │
│ /medical       │ MedicalPage       │ Staff │ default  │
│ /inventory     │ InventoryPage     │ Staff │ default  │
│ /billing       │ BillingPage       │ Staff │ default  │
│ /users         │ UsersPage         │ Admin │ default  │
│ /profile       │ ProfilePage       │ Auth  │ default  │
│ /settings      │ SettingsPage      │ Auth  │ default  │
└────────────────┴───────────────────┴───────┴──────────┘
```

**Methods**:

```text
┌─────────────────────────┬──────────────────────┐
│ Method                  │ Purpose              │
├─────────────────────────┼──────────────────────┤
│ init()                  │ Initialize/handle URL│
│ register(path, config)  │ Register a route     │
│ navigate(path, replace) │ Navigate to route    │
│ handleRoute(path)       │ Process route change │
│ back()                  │ Go to previous page  │
│ getCurrentPath()        │ Get current URL path │
│ getParams()             │ Get route parameters │
│ refresh()               │ Re-render page       │
└─────────────────────────┴──────────────────────┘
```

**Navigation Flow**:

```text
1. User clicks link or calls navigate()
2. Router matches path to registered route
3. Check route guard (authentication)
4. If guard fails, redirect to /login
5. If guard passes, render page component
6. Update browser history
7. Call afterHooks
```

---

### `assets/js/store.js`

**Purpose**: Centralized state management with reactive updates

**Initial State**:

```javascript
{
    user: null,
    isAuthenticated: false,
    sidebarCollapsed: false,
    sidebarOpen: false,
    currentPage: null,
    pageTitle: '',
    isLoading: false,
    animals: [],
    users: [],
    adoptions: [],
    inventory: [],
    invoices: [],
    dashboardStats: null,
    filters: {},
    pagination: { page: 1, perPage: 20, total: 0 },
    theme: 'light',
    notifications: true
}
```

**Core Methods**:

```text
┌───────────────────┬───────────────────────────────────────┐
│ Method            │ Purpose                               │
├───────────────────┼───────────────────────────────────────┤
│ get(key)          │ Get state value (supports dot notation)│
│ set(key, value)   │ Set state value                       │
│ update(updates)   │ Update multiple values                │
│ reset(keys)       │ Reset to initial values               │
└───────────────────┴───────────────────────────────────────┘
```

**Subscription Methods**:

```text
┌──────────────────────────────┬────────────────────────────┐
│ Method                       │ Purpose                    │
├──────────────────────────────┼────────────────────────────┤
│ subscribe(key, callback)     │ Subscribe to state changes │
│ unsubscribe(key, callback)   │ Remove subscription        │
│ notify(key, value, oldValue) │ Notify subscribers         │
└──────────────────────────────┴────────────────────────────┘
```

**Persistence Methods**:

```text
┌──────────────────────┬────────────────────────┐
│ Method               │ Purpose                │
├──────────────────────┼────────────────────────┤
│ persist(keys)        │ Save to localStorage   │
│ loadPersistedState() │ Load from localStorage │
│ clearCache()         │ Clear all cached data  │
└──────────────────────┴────────────────────────┘
```

**Usage Example**:

```javascript
// Get value
const user = Store.get('user');
const name = Store.get('user.first_name');

// Set value
Store.set('isLoading', true);

// Subscribe to changes
Store.subscribe('user', (newUser, oldUser) => {
    console.log('User changed:', newUser);
});
```

---

### `assets/js/utils.js`

**Purpose**: Helper utility functions used throughout the application

**DOM Utilities**:

```text
┌──────────────────────────────────────┬──────────────────────┐
│ Method                               │ Purpose              │
├──────────────────────────────────────┼──────────────────────┤
│ $(selector)                          │ Query single element │
│ $$(selector)                         │ Query multiple       │
│ createElement(tag, attrs, children)  │ Create DOM element   │
│ parseHTML(html)                      │ Parse HTML string    │
│ empty(element)                       │ Clear contents       │
│ show(element)                        │ Show element         │
│ hide(element)                        │ Hide element         │
│ toggle(element)                      │ Toggle visibility    │
└──────────────────────────────────────┴──────────────────────┘
```

**String Utilities**:

```text
┌────────────────────────┬──────────────────────┐
│ Method                 │ Purpose              │
├────────────────────────┼──────────────────────┤
│ capitalize(str)        │ Capitalize 1st letter│
│ titleCase(str)         │ Title Case String    │
│ truncate(str, length)  │ Truncate w/ ellipsis │
│ slugify(str)           │ URL-friendly slug    │
│ randomString(length)   │ Random alphanumeric  │
│ uuid()                 │ Generate UUID        │
└────────────────────────┴──────────────────────┘
```

**Number/Currency**:

```text
┌────────────────────────┬──────────────────────┐
│ Method                 │ Purpose              │
├────────────────────────┼──────────────────────┤
│ formatNumber(num)      │ Format with commas   │
│ formatCurrency(amount) │ Format as ₱1,234.00  │
│ formatPercent(value)   │ Format as percentage │
└────────────────────────┴──────────────────────┘
```

**Date/Time**:

```text
┌────────────────────────────┬──────────────────────┐
│ Method                     │ Purpose              │
├────────────────────────────┼──────────────────────┤
│ formatDate(date)           │ Format as Dec 25     │
│ formatDateTime(date)       │ Format with time     │
│ formatRelativeTime(date)   │ "2 hours ago"        │
│ daysBetween(date1, date2)  │ Days between dates   │
└────────────────────────────┴──────────────────────┘
```

**Object Utilities**:

```text
┌────────────────────────┬──────────────────────┐
│ Method                 │ Purpose              │
├────────────────────────┼──────────────────────┤
│ get(obj, path)         │ Get nested property  │
│ set(obj, path, value)  │ Set nested property  │
│ clone(obj)             │ Deep clone object    │
│ isEmpty(value)         │ Check if empty       │
│ debounce(fn, wait)     │ Debounce function    │
│ throttle(fn, wait)     │ Throttle function    │
└────────────────────────┴──────────────────────┘
```

**Validation**:

```text
┌──────────────┬────────────────┐
│ Method       │ Purpose        │
├──────────────┼────────────────┤
│ isEmail(str) │ Validate email │
│ isPhone(str) │ Validate phone │
│ isURL(str)   │ Validate URL   │
└──────────────┴────────────────┘
```

**UI Helpers**:

```text
┌──────────────────────────────────┬────────────────────────────────────┐
│ Method                           │ Purpose                            │
├──────────────────────────────────┼────────────────────────────────────┤
│ getStatusBadgeClass(status)      │ Status CSS class (includes 'Reserved') │
│ getAnimalTypeBadgeClass(type)    │ Animal type badge class (Dog/Cat/Other) │
│ getInitials(name)                │ Get "JD" from "John Doe"           │
│ stringToColor(str)               │ Generate color from string         │
│ getAnimalPlaceholder(type)       │ Get placeholder image path         │
│ sleep(ms)                        │ Async delay                        │
│ announce(message)                │ Screen reader announcement         │
└──────────────────────────────────┴────────────────────────────────────┘
```

---

## 🧩 Components

### `components/Toast.js`

**Purpose**: Notification system for displaying messages

**Types**: `success`, `error`, `warning`, `info`

**Methods**:

```javascript
Toast.show({ type, title, message, duration })
Toast.success(message)
Toast.error(message)
Toast.warning(message)
Toast.info(message)
Toast.dismiss(id)
Toast.dismissAll()
```

**Options**:

```text
┌──────────────┬─────────────┬────────────────────────┐
│ Option       │ Default     │ Purpose                │
├──────────────┼─────────────┼────────────────────────┤
│ duration     │ 4000        │ Auto-dismiss time (ms) │
│ position     │ 'top-right' │ Toast position         │
│ closable     │ true        │ Show close button      │
│ pauseOnHover │ true        │ Pause timer on hover   │
└──────────────┴─────────────┴────────────────────────┘
```

---

### `components/Modal.js`

**Purpose**: Modal dialog system

**Methods**:

```javascript
Modal.open({ title, content, footer, size, onConfirm, onCancel })
Modal.close(id)
Modal.closeAll()
Modal.confirm({ title, message, confirmText, dangerMode })
Modal.alert({ title, message })
```

**Sizes**: `sm`, `default`, `lg`, `xl`, `full`

**Options**:

```text
┌────────────────┬─────────┬─────────────────────────┐
│ Option         │ Default │ Purpose                 │
├────────────────┼─────────┼─────────────────────────┤
│ closable       │ true    │ Can be closed           │
│ closeOnOverlay │ true    │ Close on backdrop click │
│ closeOnEscape  │ true    │ Close on Escape key     │
│ showClose      │ true    │ Show X button           │
└────────────────┴─────────┴─────────────────────────┘
```

---

### `components/DataTable.js`

**Purpose**: Data table with sorting, pagination, and actions

**Usage**:

```javascript
DataTable.render({
    id: 'animals-table',
    columns: [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'type', label: 'Type' },
        { key: 'status', label: 'Status', render: (val) => `<span class="badge">${val}</span>` }
    ],
    data: animals,
    pagination: { page: 1, perPage: 20, total: 100 },
    onRowClick: (id) => Router.navigate(`/animals/${id}`),
    actions: {
        edit: (row) => editAnimal(row.id),
        delete: (row) => deleteAnimal(row.id)
    }
});
```

**Features**:

- Sortable columns
- Row selection (checkbox)
- Custom cell rendering
- Pagination controls
- Row click handlers
- Action buttons

---

### `components/Form.js`

**Purpose**: Form generation and validation

**Field Types**:

- `text`, `email`, `password`, `number`, `tel`, `url`
- `date`, `datetime-local`, `time`
- `textarea`, `select`, `checkbox`, `radio`
- `file`, `hidden`

**Usage**:

```javascript
const fields = [
    { name: 'name', label: 'Name', type: 'text', required: true },
    { name: 'email', label: 'Email', type: 'email', required: true },
    { name: 'role', label: 'Role', type: 'select', options: ['Admin', 'Staff'] }
];

const html = Form.generate(fields, existingData);
```

**Validation**:

```javascript
const { isValid, data, errors } = Form.validate(formElement, rules);
```

---

### `components/Card.js`

**Purpose**: Card UI components

**Card Types**:

```text
┌─────────────────────────┬─────────────────────────┐
│ Method                  │ Purpose                 │
├─────────────────────────┼─────────────────────────┤
│ Card.render(options)    │ Basic card              │
│ Card.stat(options)      │ Statistics card         │
│ Card.animal(animal)     │ Animal card (grid view) │
│ Card.user(user)         │ User profile card       │
│ Card.activity(activity) │ Activity feed item      │
└─────────────────────────┴─────────────────────────┘
```

---

### `components/Charts.js`

**Purpose**: Chart.js wrapper with consistent styling

**Chart Types**:

```javascript
Charts.line(canvasId, { labels, datasets })
Charts.bar(canvasId, { labels, datasets })
Charts.doughnut(canvasId, { labels, data })
Charts.pie(canvasId, { labels, data })
Charts.area(canvasId, { labels, datasets })
```

**Color Palette**:

```javascript
{
    primary: '#007AFF',
    secondary: '#5856D6',
    success: '#34C759',
    warning: '#FF9500',
    danger: '#FF3B30',
    info: '#5AC8FA'
}
```

---

### `components/Header.js`

**Purpose**: Top navigation header

**Features**:

- Page title display
- Quick action dropdown (Add Animal, Medical Record)
- Theme toggle (light/dark)
- User avatar with profile link

---

### `components/Sidebar.js`

**Purpose**: Main navigation sidebar

**Navigation Structure**:

```javascript
[
    {
        section: 'Main',
        items: [
            { id: 'dashboard', label: 'Dashboard', path: '/dashboard', roles: ['Admin', 'Staff', 'Veterinarian'] },
            { id: 'animals', label: 'Animals', path: '/animals', roles: ['*'] }
        ]
    },
    {
        section: 'Management',
        roles: ['Admin', 'Staff', 'Veterinarian'],
        items: [
            { id: 'adoptions', label: 'Adoptions', path: '/adoptions', badge: () => pendingCount },
            { id: 'medical', label: 'Medical Records', path: '/medical' },
            { id: 'inventory', label: 'Inventory', path: '/inventory' },
            { id: 'billing', label: 'Billing', path: '/billing' }
        ]
    },
    {
        section: 'Administration',
        roles: ['Admin'],
        items: [
            { id: 'users', label: 'Users', path: '/users' }
        ]
    }
]
```

---

### `components/Loading.js`

**Purpose**: Loading indicators and skeleton screens

**Methods**:

```javascript
Loading.spinner({ size: 'md', text: 'Loading...' })
Loading.dots()
Loading.pulse()
Loading.skeleton('card')
Loading.skeleton('table', { rows: 5, cols: 4 })
Loading.skeleton('list', { items: 5 })
Loading.skeleton('stats', { count: 4 })
```

---

### `components/PDFPreview.js`

**Purpose**: PDF preview modal with Print and Download options

**Methods**:

```javascript
PDFPreview.show(doc, filename)   // Show PDF preview modal
PDFPreview.print()                // Print current PDF
PDFPreview.download()             // Download current PDF
```

**Usage**:

```javascript
// Generate PDF with jsPDF
const doc = new jsPDF();
doc.text('Hello World', 10, 10);

// Show preview instead of direct download
const filename = 'Invoice_John_Doe_2025-12-27.pdf';
PDFPreview.show(doc, filename);
```

**Features**:

- Embedded PDF viewer in modal
- Print button opens print dialog
- Download button saves PDF with custom filename
- Close button dismisses modal
- Blob URL automatically cleaned up on close

---

## 📄 Pages

### `pages/Login.js`

**Purpose**: Login and registration page

**Modes**: `login`, `register`

**Methods**:

```text
┌──────────────────┬────────────────────────────┐
│ Method           │ Purpose                    │
├──────────────────┼────────────────────────────┤
│ render()         │ Render login/register form │
│ handleSubmit(e)  │ Form submission            │
│ togglePassword() │ Show/hide password         │
│ afterMount()     │ Setup form listeners       │
└──────────────────┴────────────────────────────┘
```

---

### `pages/Dashboard.js`

**Purpose**: Main dashboard with statistics

**Sections**:

- Statistics cards (total animals, adoptions, etc.)
- Intake chart (line/bar)
- Status distribution (doughnut)
- Recent animals list
- Pending adoptions
- Activity feed
- Quick actions

**Methods**:

```text
┌────────────────┬──────────────────────┐
│ Method         │ Purpose              │
├────────────────┼──────────────────────┤
│ render()       │ Render dashboard     │
│ loadData()     │ Fetch dashboard data │
│ renderStats()  │ Render stat cards    │
│ renderCharts() │ Initialize charts    │
│ refresh()      │ Refresh all data     │
└────────────────┴──────────────────────┘
```

---

### `pages/Animals.js`

**Purpose**: Animal listing and management

**Features**:

- Grid/table view toggle
- Filters (type, status, gender, search)
- Pagination
- Add/edit animal modal
- Image upload

**State**:

```javascript
{
    animals: [],
    pagination: { page: 1, perPage: 20, total: 0 },
    filters: { type: '', status: '', gender: '', search: '' },
    viewMode: 'grid',
    loading: false
}
```

---

### `pages/AnimalDetail.js`

**Purpose**: Single animal details page

**Sections**:

- Image gallery
- Basic info
- Medical records
- Adoption history
- Feeding records
- Actions (edit, adopt, etc.)

---

### `pages/Adoptions.js`

**Purpose**: Adoption request management

**Features**:

- List all adoption requests
- Filter by status
- Approve/reject requests
- View request details
- Submit new request (Adopter)
- **Conditional Actions**: 'Process' button hidden for final statuses (Completed, Cancelled, Rejected)

---

### `pages/Medical.js`

**Purpose**: Medical record management

**Features**:

- List medical records
- Filter by animal, type, date
- Add new record (Veterinarian)
- View record details
- Print records

---

### `pages/Inventory.js`

**Purpose**: Inventory/supplies management

**Features**:

- List inventory items
- Low stock alerts
- Add/edit items
- Adjust quantities
- Category filtering

---

### `pages/Billing.js`

**Purpose**: Invoice and payment management

**Tabs**: `invoices`, `payments`

**Features**:

- Invoice creation
- Payment recording
- PDF report generation with preview (summary, detailed, unpaid)
- Individual invoice PDF print/download
- Filter by status/type/date
- Filename format: `ReportType_FirstName_LastName_Date.pdf`

---

### `pages/Users.js`

**Purpose**: User management (Admin only)

**Features**:

- List all users
- Create user accounts
- Edit user details
- Change user roles
- Activate/deactivate accounts

---

### `pages/Profile.js`

**Purpose**: User profile management

**Features**:

- View profile info
- Edit profile
- Upload avatar
- Change password
- View activity history

---

### `pages/Settings.js`

**Purpose**: Application settings

**Features**:

- Theme toggle (light/dark)
- Notification preferences
- Language settings
- Account settings

---

## 🎨 CSS Files

### `css/variables.css`

**Purpose**: CSS custom properties (design tokens)

**Categories**:

- **Colors**: Primary, secondary, semantic (success, warning, danger, info)
- **Backgrounds**: Primary, secondary, elevated, sidebar
- **Text**: Primary, secondary, tertiary
- **Typography**: Font families, sizes, weights
- **Spacing**: Gap, padding, margin scales
- **Borders**: Radius, widths
- **Shadows**: Elevation levels
- **Transitions**: Timing functions
- **Z-index**: Layer management

**Dark Mode**:

```css
[data-theme="dark"] {
    --bg-primary: #1C1C1E;
    --bg-secondary: #000000;
    --text-primary: #FFFFFF;
    /* ... */
}
```

---

### `css/main.css`

**Purpose**: Base styles and CSS reset

**Includes**:

- Box-sizing reset
- Typography defaults
- Link styles
- Image handling
- Button/input resets

---

### `css/components.css`

**Purpose**: UI component styles

**Components Styled**:

- Buttons (`.btn`, `.btn-primary`, `.btn-secondary`)
- Forms (`.form-input`, `.form-select`, `.form-group`)
- Cards (`.card`, `.stat-card`, `.animal-card`)
- Tables (`.table`, `.table-container`)
- Badges (`.badge`, `.badge-success`)
- Avatars (`.avatar`, `.avatar-sm`, `.avatar-lg`)
- Dropdowns (`.dropdown`, `.dropdown-menu`)
- Tabs (`.tabs`, `.tab`)

---

### `css/layouts.css`

**Purpose**: Page layout structures

**Layouts**:

- Auth layout (centered login page)
- Main layout (sidebar + header + content)
- Grid layouts (`.stats-grid`, `.content-grid`)
- Flex utilities

---

### `css/animations.css`

**Purpose**: Transitions and animations

**Animations**:

- `animate-fade-in`
- `animate-slide-up`
- `animate-slide-down`
- `animate-scale`
- `animate-pulse`
- `animate-spin`
- Page transitions
- Modal animations
- Toast animations

---

### `css/responsive.css`

**Purpose**: Media queries for responsive design

**Breakpoints**:

```text
┌──────┬────────┐
│ Size │ Width  │
├──────┼────────┤
│ sm   │ 640px  │
│ md   │ 768px  │
│ lg   │ 1024px │
│ xl   │ 1280px │
└──────┴────────┘
```

---

## 🔄 Application Flow

### Page Load

```text
1. index.html loads
2. CSS files load (variables → main → components → layouts → animations → responsive)
3. External libraries load (Chart.js, jsPDF)
4. Core JS loads (utils → api → store → auth → router)
5. Components load
6. Pages load
7. App.init() runs
```

### Authentication Flow

```text
1. App.init() calls Auth.init()
2. Auth checks for stored token
3. If token exists, validate with API
4. If valid, load user data into Store
5. If invalid, try refresh token
6. If refresh fails, clear session
7. Router redirects based on auth state
```

### Navigation Flow

```text
1. User clicks link or calls Router.navigate()
2. Router.handleRoute() is called
3. Route guard checks (requireAuth, requireAdmin, etc.)
4. If guard fails, redirect to /login
5. If guard passes, call page.render()
6. Inject HTML into #page-content
7. Call page.afterMount() for event listeners
8. Update browser history
```

### Data Fetching Pattern

```javascript
async loadData() {
    this.state.loading = true;
    this.renderLoading();
    
    try {
        const response = await API.animals.list(this.state.filters);
        this.state.animals = response.data;
        this.state.pagination = response.pagination;
        this.renderContent();
    } catch (error) {
        Toast.error(error.message);
    } finally {
        this.state.loading = false;
    }
}
```

---

## 📝 Code Conventions

### Page Component Structure

```javascript
const ExamplePage = {
    // State
    state: { ... },
    
    // Render HTML
    async render() { ... },
    
    // After DOM is ready
    afterMount() { ... },
    
    // Load data from API
    async loadData() { ... },
    
    // Re-render specific sections
    renderContent() { ... },
    
    // Event handlers
    handleSubmit(e) { ... },
    handleClick(id) { ... },
    
    // Modal methods
    showAddModal() { ... },
    showEditModal(id) { ... },
    
    // CRUD operations
    async create(data) { ... },
    async update(id, data) { ... },
    async delete(id) { ... }
};
```

### Event Handler Pattern

```javascript
afterMount() {
    // Form submission
    Utils.$('#my-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleSubmit(e);
    });
    
    // Input changes (debounced)
    Utils.$('#search-input')?.addEventListener('input', 
        Utils.debounce((e) => this.handleSearch(e.target.value), 300)
    );
    
    // Button clicks
    Utils.$$('.action-btn').forEach(btn => {
        btn.addEventListener('click', () => this.handleAction(btn.dataset.id));
    });
}
```

---

## ⚡ Performance Optimizations (v1.0.7)

The system implements several advanced performance patterns inspired by high-traffic web applications like McMaster-Carr to reduce perceived latency and server load.

### 1. Smart Caching (`api.js`)

**Purpose**: Reduce redundant network requests for semi-static data.

**How it works**:

- The `API.get()` method accepts an `options.cache` flag.
- When `cache: true`, the default timestamp cache-buster (`_t=[time]`) is omitted from the URL.
- This allows the browser to serve the response from its internal cache (304 Not Modified or Memory Cache).

### 2. Predictive Prefetching (`HoverPreview.js`)

**Purpose**: Predict user intent and "warm" the cache before a click occurs.

**Implementation**:

- Monitors `mouseover` events on links/cards with `data-preview` attributes.
- When a user hovers for >200ms, `prefetchDetail(type, id)` is triggered.
- It initiates a background `API.get()` request with `cache: true`.
- The response is stored in `HoverPreview.cache` (a Map) and the browser's network cache.

### 3. Optimistic UI (`AnimalDetail.js`)

**Purpose**: Instant page transitions without loading spinners.

**Implementation**:

- The `afterRender` lifecycle hook checks for prefetched data in `HoverPreview.cache`.
- If valid data exists, it renders the core profile **immediately** before any new network requests.
- Related data (medical, feeding, etc.) continues to load in the background to ensure consistency.

### 4. Zero Layout Shift (CLS)

**Purpose**: Prevent visual "jank" as data loads.

**Implementation**:

- Skeleton loaders (`Loading.skeleton`) use fixed aspect ratios and `min-height` properties.
- CSS variables ensure that placeholders exactly match the dimensions of the final rendered content.

---

## 📱 Mobile Responsive Design

### Overview

The application is fully responsive with special attention to data tables on mobile devices.

### Responsive CSS (`responsive.css`)

**Breakpoints**:

```text
┌──────────────┬─────────────────────────────────────────────┐
│ Breakpoint   │ Target                                      │
├──────────────┼─────────────────────────────────────────────┤
│ ≤1024px      │ Tablets - Sidebar becomes drawer            │
│ ≤768px       │ Mobile - Tables convert to card layouts     │
│ ≤480px       │ Small mobile - Compact spacing              │
│ ≤360px       │ Extra small - Hide non-essential elements   │
└──────────────┴─────────────────────────────────────────────┘
```

### Mobile Table Card Layout

On screens ≤768px, data tables transform into stacked card layouts:

**Affected Pages**:

- Adoptions (`#adoptions-container`)
- Medical Records (`#records-container`)
- Billing (`#billing-content`)
- Inventory (`#inventory-container`)
- Users (`#users-container`)

**CSS Transformation**:

```css
/* Tables become vertical cards */
table tbody { display: flex; flex-direction: column; }
table tr { display: flex; flex-direction: column; padding: 16px; }
table td { display: flex; justify-content: space-between; }
table td::before { content: attr(data-label); } /* Column labels */
```

### Animal Cards (Mobile)

On mobile, animal cards display with:

- Full-width images (200px height)
- Vertical stacking (image on top, info below)
- Proper border radius on top corners only

### Touch Enhancements (`main.css`)

- `-webkit-overflow-scrolling: touch` for momentum scrolling
- Minimum 44px touch targets on interactive elements
- Safe area insets for notched devices
- Disabled hover effects on touch devices

---

## ♿ Accessibility Features

### ARIA Support

All interactive elements include proper ARIA attributes:

```text
┌───────────────────────────────┬────────────────────────────────────┐
│ Element                       │ ARIA Attributes                    │
├───────────────────────────────┼────────────────────────────────────┤
│ Icon Buttons                  │ aria-label, title                  │
│ Profile Avatar                │ role="button", tabindex="0"        │
│ Theme Toggle                  │ aria-label (dynamic for state)     │
│ DataTable Actions             │ aria-label with item name          │
│ Decorative SVGs               │ aria-hidden="true"                 │
│ Empty States                  │ role="status", aria-label          │
│ Loading States                │ aria-busy="true"                   │
└───────────────────────────────┴────────────────────────────────────┘
```

### Focus States (`enhancements.css`)

Clear visible focus indicators for all interactive elements:

- Icon buttons: 2px outline with 4px primary color ring
- Pagination buttons: Same ring style
- Dropdown items: Background color change
- Tabs: Inset 2px primary ring
- Avatar buttons: 3px primary ring
- Table rows: Primary color left border

### Motion Preferences

```css
@media (prefers-reduced-motion: reduce) {
    /* All animations reduced to 0.01ms */
}
```

### Screen Reader Utilities

```css
.visually-hidden   /* Hidden visually but accessible */
.sr-only           /* Screen reader only content */
```

---

## 🎨 UI Polish Features

### CSS Tooltips (`enhancements.css`)

Pure CSS tooltips for buttons with `title` attribute:

- Appears 8px above element on hover/focus
- 150ms fade-in animation
- Styled with theme colors
- Arrow indicator pointing to element

### Card Animations

Staggered fade-in animations for grid layouts:

```text
┌────────────────────┬─────────────────────────────────────────┐
│ Component          │ Animation                               │
├────────────────────┼─────────────────────────────────────────┤
│ Stat Cards         │ 50ms delay between each (0-150ms)       │
│ Animal Grid Cards  │ 30ms delay between each (0-210ms)       │
│ Table Rows         │ 200ms fade-in                           │
│ Empty State Icons  │ 3s floating bounce animation            │
└────────────────────┴─────────────────────────────────────────┘
```

### Button Loading State

Add `is-loading` class to show spinner:

```javascript
button.classList.add('is-loading');
// Button text hidden, spinner appears
```

### Enhanced Empty States (`Card.js`)

Improved `Card.empty()` method supports:

- `icon` - Emoji or HTML icon
- `title` - Main heading
- `description` - Explanatory text
- `hint` - Additional help text with links
- `action` - Primary action button with icon
- `secondaryAction` - Secondary action button

---

## ⌨️ Keyboard Shortcuts

### Global Shortcuts (`app.js`)

```text
┌─────────────────────┬──────────────────────────────────────┐
│ Shortcut            │ Action                               │
├─────────────────────┼──────────────────────────────────────┤
│ /                   │ Focus search input                   │
│ Ctrl/Cmd + K        │ Focus search (alternative)           │
│ Escape              │ Close modals, dropdowns, blur inputs │
│ ?                   │ Show keyboard shortcuts modal        │
│ g then h            │ Navigate to Dashboard (home)         │
│ g then a            │ Navigate to Animals                  │
└─────────────────────┴──────────────────────────────────────┘
```

### Implementation

Shortcuts are handled in `App.setupEventListeners()`:

- Checks if input is focused before triggering shortcuts
- Uses `_gPressed` flag for chord shortcuts (g+h, g+a)
- 1 second timeout for chord completion
