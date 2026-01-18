/**
 * MobileNav Component
 * Bottom navigation for mobile devices
 * 
 * @package AnimalShelter
 */

const MobileNav = {
    /**
     * Navigation items based on role
     */
    getNavItems(userRole) {
        const baseItems = [
            {
                id: 'dashboard',
                label: 'Home',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
                path: '/dashboard',
                roles: ['Admin', 'Staff', 'Veterinarian', 'Adopter']
            },
            {
                id: 'animals',
                label: 'Animals',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5"></path><path d="M14.267 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.855-1.45-2.239-2.5"></path><path d="M8 14v.5"></path><path d="M16 14v.5"></path><path d="M11.25 16.25h1.5L12 17l-.75-.75Z"></path><path d="M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444c0-1.061-.162-2.2-.493-3.309m-9.243-6.082A8.801 8.801 0 0 1 12 5c.78 0 1.5.108 2.161.306"></path></svg>',
                path: '/animals',
                roles: ['Admin', 'Staff', 'Veterinarian', 'Adopter']
            },
            {
                id: 'adoptions',
                label: 'Adoptions',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                path: '/adoptions',
                roles: ['Admin', 'Staff', 'Veterinarian', 'Adopter'],
                badge: () => Store.get('pendingAdoptions') || 0
            }
        ];

        // Add role-specific items
        if (['Admin', 'Staff'].includes(userRole)) {
            baseItems.push({
                id: 'billing',
                label: 'Billing',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
                path: '/billing',
                roles: ['Admin', 'Staff']
            });
        }

        // More menu always last
        baseItems.push({
            id: 'more',
            label: 'More',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>',
            path: '#more',
            roles: ['Admin', 'Staff', 'Veterinarian', 'Adopter'],
            isMore: true
        });

        return baseItems.filter(item => item.roles.includes(userRole));
    },

    /**
     * Get more menu items
     */
    getMoreItems(userRole) {
        const items = [];

        if (['Admin', 'Staff', 'Veterinarian'].includes(userRole)) {
            items.push({
                id: 'medical',
                label: 'Medical Records',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
                path: '/medical'
            });
        }

        if (['Admin', 'Staff'].includes(userRole)) {
            items.push({
                id: 'inventory',
                label: 'Inventory',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
                path: '/inventory'
            });
        }

        if (userRole === 'Admin') {
            items.push({
                id: 'users',
                label: 'Users',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                path: '/users'
            });
        }

        items.push({
            id: 'profile',
            label: 'Profile',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
            path: '/profile'
        });

        return items;
    },

    /**
     * Render mobile navigation
     */
    render() {
        const user = Auth.currentUser();
        if (!user) return;

        const userRole = user.role || 'Adopter';
        const navItems = this.getNavItems(userRole);
        const moreItems = this.getMoreItems(userRole);

        // Check if mobile nav already exists
        let mobileNav = document.querySelector('#mobile-nav');
        if (!mobileNav) {
            mobileNav = document.createElement('nav');
            mobileNav.id = 'mobile-nav';
            mobileNav.className = 'mobile-bottom-nav';
            document.body.appendChild(mobileNav);
            document.body.classList.add('has-bottom-nav');
        }

        mobileNav.innerHTML = `
            <div class="mobile-bottom-nav-inner">
                ${navItems.map(item => this.renderNavItem(item, moreItems)).join('')}
            </div>
        `;

        this.setupEventListeners();
    },

    /**
     * Render single nav item
     */
    renderNavItem(item, moreItems = []) {
        const isActive = Router.isActive(item.path);
        const badge = typeof item.badge === 'function' ? item.badge() : (item.badge || 0);

        if (item.isMore) {
            return `
                <div class="mobile-nav-item more-menu ${isActive ? 'active' : ''}" data-id="${item.id}">
                    <div class="mobile-nav-icon">
                        ${item.icon}
                    </div>
                    <span class="mobile-nav-label">${item.label}</span>
                    <div class="mobile-nav-more-menu">
                        ${moreItems.map(mi => `
                            <a href="${mi.path}" class="mobile-nav-more-item" data-nav>
                                ${mi.icon}
                                <span>${mi.label}</span>
                            </a>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        return `
            <a href="${item.path}" class="mobile-nav-item ${isActive ? 'active' : ''}" data-id="${item.id}" data-nav>
                <div class="mobile-nav-icon">
                    ${item.icon}
                    ${badge > 0 ? `<span class="mobile-nav-badge">${badge > 99 ? '99+' : badge}</span>` : ''}
                </div>
                <span class="mobile-nav-label">${item.label}</span>
            </a>
        `;
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // More menu toggle
        const moreMenu = document.querySelector('.mobile-nav-item.more-menu');
        if (moreMenu) {
            moreMenu.addEventListener('click', (e) => {
                if (!e.target.closest('.mobile-nav-more-item')) {
                    e.preventDefault();
                    moreMenu.classList.toggle('open');
                }
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!moreMenu.contains(e.target)) {
                    moreMenu.classList.remove('open');
                }
            });
        }
    },

    /**
     * Update active state
     */
    updateActive() {
        const items = document.querySelectorAll('.mobile-nav-item[data-id]');
        items.forEach(item => {
            const path = item.getAttribute('href');
            if (path && path !== '#more') {
                item.classList.toggle('active', Router.isActive(path));
            }
        });
    },

    /**
     * Update badge count
     */
    updateBadge(itemId, count) {
        const item = document.querySelector(`.mobile-nav-item[data-id="${itemId}"]`);
        if (!item) return;

        const iconWrapper = item.querySelector('.mobile-nav-icon');
        let badge = iconWrapper.querySelector('.mobile-nav-badge');

        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'mobile-nav-badge';
                iconWrapper.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) {
            badge.remove();
        }
    },

    /**
     * Hide mobile nav
     */
    hide() {
        const mobileNav = document.querySelector('#mobile-nav');
        if (mobileNav) {
            mobileNav.style.display = 'none';
        }
    },

    /**
     * Show mobile nav
     */
    show() {
        const mobileNav = document.querySelector('#mobile-nav');
        if (mobileNav) {
            mobileNav.style.display = 'block';
        }
    }
};

// Make MobileNav globally available
window.MobileNav = MobileNav;
