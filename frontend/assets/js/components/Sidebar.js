/**
 * Sidebar Component
 * Main navigation sidebar
 * 
 * @package AnimalShelter
 */

const Sidebar = {
    /**
     * Navigation items
     */
    navItems: [
        {
            section: 'Main',
            items: [
                {
                    id: 'dashboard',
                    label: 'Dashboard',
                    icon: 'home',
                    path: '/dashboard',
                    roles: ['Admin', 'Staff', 'Veterinarian']
                },
                {
                    id: 'dashboard-adopter',
                    label: 'Adoption Hub',
                    icon: 'home',
                    path: '/dashboard',
                    roles: ['Adopter']
                },
                {
                    id: 'animals',
                    label: 'Animals',
                    icon: 'heart',
                    path: '/animals',
                    roles: ['Admin', 'Staff', 'Veterinarian', 'Adopter']
                }
            ]
        },
        {
            section: 'Management',
            roles: ['Admin', 'Staff', 'Veterinarian'],
            items: [
                {
                    id: 'adoptions',
                    label: 'Adoptions',
                    icon: 'file-text',
                    path: '/adoptions',
                    roles: ['Admin', 'Staff', 'Veterinarian', 'Adopter'],
                    badge: () => Store.get('pendingAdoptions') || 0
                },
                {
                    id: 'medical',
                    label: 'Medical Records',
                    icon: 'activity',
                    path: '/medical',
                    roles: ['Admin', 'Staff', 'Veterinarian']
                },
                {
                    id: 'inventory',
                    label: 'Inventory',
                    icon: 'package',
                    path: '/inventory',
                    roles: ['Admin', 'Staff'],
                    badge: () => {
                        const alerts = Store.get('inventoryAlerts');
                        return alerts?.low_stock_count || 0;
                    }
                },
                {
                    id: 'billing',
                    label: 'Billing',
                    icon: 'credit-card',
                    path: '/billing',
                    roles: ['Admin', 'Staff']
                }
            ]
        },
        {
            section: 'Administration',
            roles: ['Admin'],
            items: [
                {
                    id: 'users',
                    label: 'Users',
                    icon: 'users',
                    path: '/users',
                    roles: ['Admin']
                }
            ]
        }
    ],

    /**
     * Icons SVG
     */
    icons: {
        home: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        heart: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
        'file-text': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
        activity: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
        package: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
        'credit-card': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
        users: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        settings: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'chevron-left': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>',
        'chevron-right': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>',
        'log-out': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>',
        'more-vertical': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>'
    },

    /**
     * Render sidebar
     */
    render() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        const user = Auth.currentUser();
        const isCollapsed = true; // Always collapsed by default to allow hover expansion

        sidebar.className = isCollapsed ? 'sidebar collapsed' : 'sidebar';

        sidebar.innerHTML = `
            ${this.renderHeader()}
            ${this.renderNav(user)}
            ${this.renderFooter(user)}
        `;

        // Add sidebar overlay for mobile
        this.addMobileOverlay();

        // Setup event listeners
        this.setupEventListeners();
    },

    /**
     * Render sidebar header
     */
    renderHeader() {
        return `
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5"></path>
                        <path d="M14.267 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.855-1.45-2.239-2.5"></path>
                        <path d="M8 14v.5"></path>
                        <path d="M16 14v.5"></path>
                        <path d="M11.25 16.25h1.5L12 17l-.75-.75Z"></path>
                        <path d="M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444c0-1.061-.162-2.2-.493-3.309m-9.243-6.082A8.801 8.801 0 0 1 12 5c.78 0 1.5.108 2.161.306"></path>
                    </svg>
                </div>
                <span class="sidebar-brand">Catarman Dog Pound</span>
            </div>
        `;
    },

    /**
     * Render navigation
     */
    renderNav(user) {
        const userRole = user?.role || 'Adopter';

        let navHtml = '<nav class="sidebar-nav">';

        this.navItems.forEach(section => {
            // Check if section is visible for user's role
            if (section.roles && !section.roles.includes(userRole)) {
                return;
            }

            // Filter items by role
            const visibleItems = section.items.filter(item =>
                !item.roles || item.roles.includes(userRole)
            );

            if (visibleItems.length === 0) return;

            navHtml += `
                <div class="sidebar-section">
                    <div class="sidebar-section-title">${section.section}</div>
                    <div class="sidebar-menu">
                        ${visibleItems.map(item => this.renderNavItem(item)).join('')}
                    </div>
                </div>
            `;
        });

        navHtml += '</nav>';
        return navHtml;
    },

    /**
     * Render single navigation item
     */
    renderNavItem(item) {
        const isActive = Router.isActive(item.path);
        const badge = typeof item.badge === 'function' ? item.badge() : item.badge;

        return `
            <a href="${item.path}" class="sidebar-link${isActive ? ' active' : ''}" data-page="${item.id}">
                <span class="sidebar-link-icon">${this.icons[item.icon] || ''}</span>
                <span class="sidebar-link-text">${item.label}</span>
                ${badge > 0 ? `<span class="sidebar-link-badge">${badge > 99 ? '99+' : badge}</span>` : ''}
            </a>
        `;
    },

    /**
     * Render sidebar footer
     */
    renderFooter(user) {
        const initials = Utils.getInitials(`${user?.first_name || ''} ${user?.last_name || ''}`);
        const fullName = `${user?.first_name || ''} ${user?.last_name || ''}`.trim() || 'User';

        let avatarHtml;
        if (user?.avatar_url) {
            avatarHtml = `<img src="${user.avatar_url}" class="avatar" alt="${fullName}" style="object-fit: cover;">`;
        } else {
            avatarHtml = `<div class="avatar" style="background: ${Utils.stringToColor(fullName)}">${initials}</div>`;
        }

        return `
            <div class="sidebar-footer">
                <div class="sidebar-user dropdown" id="sidebar-user-dropdown">
                    ${avatarHtml}
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">${fullName}</div>
                        <div class="sidebar-user-role">${user?.role || 'User'}</div>
                    </div>
                    <button class="btn-icon btn-ghost">
                        ${this.icons['more-vertical']}
                    </button>
                    <div class="dropdown-menu">
                        <a href="/profile" class="dropdown-item">
                            ${this.icons.settings}
                            <span>Profile Settings</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item danger" onclick="Auth.logout()">
                            ${this.icons['log-out']}
                            <span>Sign Out</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Update sidebar profile info directly
     */
    updateProfile() {
        const user = Auth.currentUser();
        const footerContainer = document.querySelector('.sidebar-footer');
        if (footerContainer) {
            footerContainer.outerHTML = this.renderFooter(user);
            this.setupEventListeners(); // Re-attach dropdown listeners
        }
    },

    /**
     * Add mobile overlay
     */
    addMobileOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay hidden';
            overlay.onclick = () => Store.closeMobileSidebar();
            document.body.appendChild(overlay);
        }
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // User dropdown
        const userDropdown = document.getElementById('sidebar-user-dropdown');
        if (userDropdown) {
            userDropdown.addEventListener('click', (e) => {
                if (!e.target.closest('.dropdown-menu')) {
                    userDropdown.classList.toggle('open');
                }
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('open');
                }
            });
        }
    },

    /**
     * Toggle sidebar collapse
     */
    toggle() {
        Store.toggleSidebar();

        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('collapsed');

            // Update toggle icon
            const toggleBtn = sidebar.querySelector('.sidebar-toggle');
            if (toggleBtn) {
                toggleBtn.innerHTML = Store.get('sidebarCollapsed')
                    ? this.icons['chevron-right']
                    : this.icons['chevron-left'];
            }
        }
    },

    /**
     * Update active state
     */
    updateActive() {
        const links = document.querySelectorAll('.sidebar-link');

        links.forEach(link => {
            const path = link.getAttribute('href');
            link.classList.toggle('active', Router.isActive(path));
        });
    },

    /**
     * Update badge
     * @param {string} itemId
     * @param {number} count
     */
    updateBadge(itemId, count) {
        const link = document.querySelector(`.sidebar-link[data-page="${itemId}"]`);
        if (!link) return;

        let badge = link.querySelector('.sidebar-link-badge');

        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'sidebar-link-badge';
                link.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) {
            badge.remove();
        }
    }
};

// Make Sidebar globally available
window.Sidebar = Sidebar;