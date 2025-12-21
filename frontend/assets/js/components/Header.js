/**
 * Header Component
 * Top navigation header
 * 
 * @package AnimalShelter
 */

const Header = {
    /**
     * Icons
     */
    icons: {
        menu: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',
        search: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
        bell: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
        sun: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>',
        moon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>',
        plus: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>'
    },

    /**
     * Render header
     */
    render() {
        const header = document.getElementById('header');
        if (!header) return;

        const pageTitle = Store.get('pageTitle') || 'Dashboard';
        const user = Auth.currentUser();
        const theme = Store.get('theme');

        header.innerHTML = `
            <div class="header-left">
                <button class="header-toggle btn-icon btn-ghost" onclick="Header.toggleMobileSidebar()">
                    ${this.icons.menu}
                </button>
                <div>
                    <h1 class="header-title">${pageTitle}</h1>
                </div>
            </div>
            
            <div class="header-right">

                
                <div class="header-actions">
                    ${Auth.isStaff() ? `
                        <div class="dropdown" id="quick-actions-dropdown">
                            <button class="btn btn-primary btn-sm" style="gap: 4px;">
                                ${this.icons.plus}
                                <span>Quick Add</span>
                            </button>
                            <div class="dropdown-menu">
                                <button class="dropdown-item" onclick="Header.quickAction('animal')">
                                    New Animal
                                </button>
                                <button class="dropdown-item" onclick="Header.quickAction('medical')">
                                    Medical Record
                                </button>
                                ${!Auth.isVeterinarian() ? `
                                <button class="dropdown-item" onclick="Header.quickAction('inventory')">
                                    Inventory Item
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    <button class="btn-icon btn-ghost" onclick="Header.toggleTheme()" title="${theme === 'dark' ? 'Switch to Light Theme' : 'Switch to Dark Theme'}">
                        ${theme === 'dark' ? this.icons.sun : this.icons.moon}
                    </button>
                    

                    
                    <div class="avatar avatar-sm" style="cursor: pointer; background: ${user?.avatar_url ? 'transparent' : Utils.stringToColor(user?.email || '')}" onclick="Router.navigate('/profile')">
                        ${user?.avatar_url
                ? `<img src="${user.avatar_url}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">`
                : Utils.getInitials(`${user?.first_name || ''} ${user?.last_name || ''}`)}
                    </div>
                </div>
            </div>
        `;


        this.setupEventListeners();
    },

    /**
     * Render using data from store
     */


    /**
     * Setup event listeners
     */
    setupEventListeners() {


        // Dropdowns
        const dropdowns = document.querySelectorAll('.header-actions .dropdown');
        dropdowns.forEach(dropdown => {
            const button = dropdown.querySelector('button');
            if (button) {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Close other dropdowns
                    dropdowns.forEach(d => {
                        if (d !== dropdown) d.classList.remove('open');
                    });
                    dropdown.classList.toggle('open');
                });
            }
        });

        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            dropdowns.forEach(d => d.classList.remove('open'));
        });
    },

    /**
     * Toggle mobile sidebar
     */
    toggleMobileSidebar() {
        Store.toggleMobileSidebar();
    },

    /**
     * Toggle theme
     */
    toggleTheme() {
        Store.toggleTheme();
        this.render(); // Re-render to update icon
    },



    /**
     * Quick action handlers
     * @param {string} action
     */
    quickAction(action) {
        switch (action) {
            case 'animal':
                Router.navigate('/animals');
                setTimeout(() => {
                    if (typeof AnimalsPage !== 'undefined' && AnimalsPage.showAddModal) {
                        AnimalsPage.showAddModal();
                    }
                }, 300);
                break;
            case 'medical':
                Router.navigate('/medical');
                setTimeout(() => {
                    if (typeof MedicalPage !== 'undefined' && MedicalPage.showAddModal) {
                        MedicalPage.showAddModal();
                    }
                }, 300);
                break;
            case 'inventory':
                Router.navigate('/inventory');
                setTimeout(() => {
                    if (typeof InventoryPage !== 'undefined' && InventoryPage.showAddModal) {
                        InventoryPage.showAddModal();
                    }
                }, 300);
                break;
        }
    },

    /**
     * Update page title
     * @param {string} title
     */
    updateTitle(title) {
        const titleEl = document.querySelector('.header-title');
        if (titleEl) {
            titleEl.textContent = title;
        }
    },

    /**
     * Update notification badge
     * @param {number} count
     */


    /**
     * Show breadcrumb
     * @param {Array} items - [{label, path}]
     */
    showBreadcrumb(items) {
        const titleContainer = document.querySelector('.header-left > div');
        if (!titleContainer) return;

        const breadcrumbHtml = items.map((item, index) => {
            const isLast = index === items.length - 1;
            return isLast
                ? `<span>${item.label}</span>`
                : `<a href="${item.path}">${item.label}</a><span class="header-breadcrumb-separator">/</span>`;
        }).join('');

        titleContainer.innerHTML = `
            <nav class="header-breadcrumb">${breadcrumbHtml}</nav>
            <h1 class="header-title">${items[items.length - 1]?.label || ''}</h1>
        `;
    }
};

// Subscribe to title changes
Store.subscribe('pageTitle', (title) => {
    Header.updateTitle(title);
});

// Subscribe to user changes
Store.subscribe('user', () => {
    Header.render();
});

// Make Header globally available
window.Header = Header;