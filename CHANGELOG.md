# Changelog

All notable changes to the Catarman Dog Pound Management System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-30

### Added

#### User Management
- Role-based access control (Admin, Staff, Veterinarian, Adopter)
- JWT authentication with token refresh
- User profile management with avatar upload
- Password change functionality
- Account status management (Active, Inactive, Banned)

#### Animal Management
- Complete animal lifecycle tracking (intake to adoption)
- Animal types: Dog, Cat, Other
- Status tracking: Available, Adopted, Deceased, In Treatment, Quarantine, Reclaimed
- Image upload with type-specific placeholders
- Impound record management

#### Adoption System
- Adoption request submission by any authenticated user
- Workflow: Pending → Interview Scheduled → Approved/Rejected → Completed
- Staff/Admin approval process
- Automatic fee calculation based on animal type

#### Medical Records
- Veterinary treatment logging
- Diagnosis types: Checkup, Vaccination, Surgery, Treatment, Emergency, Deworming, Spay/Neuter
- Next due date tracking for follow-ups
- Overdue treatment notifications
- PDF export with preview

#### Inventory Management
- Stock tracking for Medical, Food, Cleaning, Supplies
- Low stock alerts based on reorder levels
- Expiration date tracking
- PDF inventory reports

#### Billing System
- Invoice generation for Adoption and Reclaim fees
- Payment tracking (Cash, GCash, Bank Transfer)
- Payment status: Unpaid, Paid, Cancelled
- Individual invoice printing
- PDF reports with preview

#### Dashboard
- Real-time statistics for all modules
- Activity log feed
- Charts: Intake trends, Status distribution
- Different views for Staff vs Adopter roles

#### UI/UX Features
- Dark/Light mode with system preference detection
- Responsive design for mobile devices
- Keyboard shortcuts (? for help)
- Pull-to-refresh on mobile
- Onboarding tooltips for new users
- PDF preview before download/print

### Security
- JWT-based authentication with access and refresh tokens
- Password hashing using bcrypt (`password_hash()`)
- PDO prepared statements for all database queries
- Rate limiting: 10 login attempts/min, 100 API requests/min
- Input sanitization for XSS prevention
- CORS protection with whitelisted origins
- Role-based middleware for API endpoints

### Technical
- Custom PHP MVC framework
- Single Page Application (SPA) frontend
- RESTful API design
- MySQL database with optimized indexes
- Activity logging with IP tracking

---

## [1.0.1] - 2025-12-30

### Added

#### Accessibility
- ARIA labels on all interactive elements (icon buttons, avatar, theme toggle)
- Keyboard-accessible profile avatar with `role="button"` and `tabindex`
- Screen reader support with `aria-hidden` on decorative icons
- `visually-hidden` and `sr-only` CSS utility classes
- High contrast mode support via `@media (prefers-contrast: high)`

#### UI Polish
- CSS-based tooltips for icon buttons (faster than native)
- Staggered fade-in animations for stat cards (50ms delay)
- Staggered fade-in for animal grid cards (30ms delay)
- Floating bounce animation on empty state icons
- Button loading state with spinner (`is-loading` class)
- Table action buttons fade in on row hover
- Scale animation on icon button hover

### Changed
- Enhanced focus states for all interactive elements (buttons, pagination, tabs, dropdowns)
- Improved empty state component with hint text support
- Context-aware empty states on Animals page (detects active filters)

### Fixed
- Empty CSS ruleset lint warning in enhancements.css

---

## [1.0.2] - 2025-12-30

### Fixed
- Removed horizontal scrolling on desktop tables (Billing, Inventory, Users)
- Tables now fit within container without overflow on desktop

### Changed
- Increased table action button size from 28px to 32px for better clickability

---

## [1.0.3] - 2025-12-30

### Changed
- Inventory "Expiring Soon" threshold reduced from 30 days to 7 days for more accurate alerts
- Stat card now displays "Expiring Soon (7 days)" label for clarity
- Synchronized `APP_VERSION` to 1.0.3 across `config.php` and backend documentation

### Added
- Comprehensive SYSTEM_DIAGRAMS.md with 10 diagram sections (System Architecture, ERD, Use Cases, Event Flows, etc.)
- Standard CSS `line-clamp` property for better browser compatibility

### Fixed
- Cleaned up `.gitignore` and removed legacy `USE_CASE_DIAGRAM.md` references
- Secured sensitive system diagrams and defense guides by handling them as private local files

---

## [1.0.4] - 2025-12-31

### Added

#### UI Enhancements
- Smooth scroll-to-top button with gradient background, bounce animation, and ripple effect
- Enhanced table row hover effects with subtle highlighting and action button fade-in
- Color-coded action button hover states (blue for edit, cyan for view, red for delete)
- Alternating row backgrounds for improved table readability
- Color-coded animal type badges on cards (Dog=blue, Cat=green, Other=orange)
- Consistent pagination across all pages (numbered buttons with first/last navigation)

#### Dashboard Improvements
- Real-time trend percentage calculations for "Total Animals" (compares current vs last month intakes)
- Real-time trend percentage calculations for "Revenue This Month" (compares current vs last month collections)
- Added "Others" animal type to Intake Overview chart (now shows Dogs, Cats, and Others)

### Fixed
- Header tooltips (theme switcher, etc.) now appear below buttons instead of above to prevent cutoff

### Changed
- Table row hover now uses primary color accent instead of generic gray
- Scroll-to-top button uses premium gradient styling with shadow effects

---

## [1.0.5] - 2026-01-01

### Security Enhancements

#### Environment Configuration
- Added `.env` file support for secure configuration management
- Created `Env.php` utility class to load environment variables
- JWT secret now configurable via `JWT_SECRET` environment variable
- Database credentials now configurable via `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Added `TRUSTED_PROXY` option for deployments behind reverse proxies
- Added `CORS_ORIGINS` option for production CORS configuration

#### Security Headers
- Added `X-Content-Type-Options: nosniff` to prevent MIME sniffing
- Added `X-Frame-Options: DENY` to prevent clickjacking
- Added `X-XSS-Protection: 1; mode=block` for XSS filtering
- Added `Referrer-Policy: strict-origin-when-cross-origin`
- Added `Cache-Control: no-store, no-cache` for sensitive responses

#### File Upload Hardening
- Added MIME type verification using `finfo` extension
- Added image validation with `getimagesize()` for image uploads
- Replaced `uniqid()` with cryptographically secure `random_bytes()` for filenames

#### Rate Limiter Security
- IP detection now only trusts proxy headers when `TRUSTED_PROXY=true`
- Prevents IP spoofing attacks to bypass rate limiting

#### Production Mode
- Application now defaults to production mode
- Debug information hidden from error responses by default
- Set `APP_ENV=development` in `.env` to enable debugging

### Added
- `.env.example` - Template for environment configuration
- `backend/app/utils/Env.php` - Environment variable loader

### Changed
- `config.php` - Uses environment variables with fallbacks
- `database.php` - Uses environment variables with fallbacks
- `bootstrap.php` - Loads .env file and adds security headers
- `BaseController.php` - Enhanced file upload security
- `RateLimiter.php` - Hardened IP detection

---

## [Unreleased]

### Planned
- Email notifications for adoption status changes
- Password reset via email
- Multi-language support
- Mobile app version
