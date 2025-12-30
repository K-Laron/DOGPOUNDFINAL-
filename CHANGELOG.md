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

## [Unreleased]

### Planned
- Email notifications for adoption status changes
- Password reset via email
- Multi-language support
- Mobile app version
