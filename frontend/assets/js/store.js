/**
 * Store - Simple State Management
 * Centralized state management with reactive updates
 * 
 * @package AnimalShelter
 */

const Store = {
    /**
     * Application state
     */
    state: {
        // User & Auth
        user: null,
        isAuthenticated: false,
        
        // UI State
        sidebarCollapsed: false,
        sidebarOpen: false,
        currentPage: null,
        pageTitle: '',
        isLoading: false,
        
        // Data caches
        animals: [],
        users: [],
        adoptions: [],
        inventory: [],
        invoices: [],
        
        // Dashboard stats
        dashboardStats: null,
        
        // Filters & pagination
        filters: {},
        pagination: {
            page: 1,
            perPage: 20,
            total: 0
        },
        
        // Settings
        theme: 'light',
        notifications: true
    },
    
    /**
     * Subscribers for state changes
     */
    subscribers: new Map(),
    
    /**
     * ==========================================
     * CORE METHODS
     * ==========================================
     */
    
    /**
     * Get state value
     * @param {string} key - Dot notation path (e.g., 'user.name')
     * @returns {*}
     */
    get(key) {
        return Utils.get(this.state, key);
    },
    
    /**
     * Set state value
     * @param {string} key - Dot notation path
     * @param {*} value - New value
     */
    set(key, value) {
        const keys = key.split('.');
        let current = this.state;
        
        // Navigate to parent
        for (let i = 0; i < keys.length - 1; i++) {
            if (!current[keys[i]]) {
                current[keys[i]] = {};
            }
            current = current[keys[i]];
        }
        
        // Set value
        const lastKey = keys[keys.length - 1];
        const oldValue = current[lastKey];
        current[lastKey] = value;
        
        // Notify subscribers
        this.notify(key, value, oldValue);
    },
    
    /**
     * Update multiple state values
     * @param {Object} updates - Key-value pairs to update
     */
    update(updates) {
        Object.entries(updates).forEach(([key, value]) => {
            this.set(key, value);
        });
    },
    
    /**
     * Reset state to initial values
     * @param {Array} keys - Keys to reset (all if empty)
     */
    reset(keys = []) {
        const initialState = {
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
            pagination: { page: 1, perPage: 20, total: 0 }
        };
        
        if (keys.length === 0) {
            keys = Object.keys(initialState);
        }
        
        keys.forEach(key => {
            if (initialState.hasOwnProperty(key)) {
                this.set(key, initialState[key]);
            }
        });
    },
    
    /**
     * ==========================================
     * SUBSCRIPTION METHODS
     * ==========================================
     */
    
    /**
     * Subscribe to state changes
     * @param {string} key - State key to watch
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    subscribe(key, callback) {
        if (!this.subscribers.has(key)) {
            this.subscribers.set(key, new Set());
        }
        
        this.subscribers.get(key).add(callback);
        
        // Return unsubscribe function
        return () => {
            this.subscribers.get(key).delete(callback);
        };
    },
    
    /**
     * Notify subscribers of state change
     * @param {string} key - Changed key
     * @param {*} newValue - New value
     * @param {*} oldValue - Old value
     */
    notify(key, newValue, oldValue) {
        // Notify exact key subscribers
        if (this.subscribers.has(key)) {
            this.subscribers.get(key).forEach(callback => {
                callback(newValue, oldValue, key);
            });
        }
        
        // Notify parent key subscribers
        const parts = key.split('.');
        for (let i = 1; i < parts.length; i++) {
            const parentKey = parts.slice(0, i).join('.');
            if (this.subscribers.has(parentKey)) {
                this.subscribers.get(parentKey).forEach(callback => {
                    callback(this.get(parentKey), null, key);
                });
            }
        }
        
        // Notify wildcard subscribers
        if (this.subscribers.has('*')) {
            this.subscribers.get('*').forEach(callback => {
                callback(this.state, key);
            });
        }
    },
    
    /**
     * ==========================================
     * USER & AUTH METHODS
     * ==========================================
     */
    
    /**
     * Set authenticated user
     * @param {Object} user
     */
    setUser(user) {
        this.update({
            user: user,
            isAuthenticated: !!user
        });
    },
    
    /**
     * Clear user session
     */
    clearUser() {
        this.update({
            user: null,
            isAuthenticated: false
        });
    },
    
    /**
     * Check if user has role
     * @param {string|Array} roles
     * @returns {boolean}
     */
    hasRole(roles) {
        if (!this.state.user) return false;
        
        if (!Array.isArray(roles)) {
            roles = [roles];
        }
        
        return roles.includes(this.state.user.role);
    },
    
    /**
     * Check if user is admin
     * @returns {boolean}
     */
    isAdmin() {
        return this.hasRole('Admin');
    },
    
    /**
     * Check if user is staff or admin
     * @returns {boolean}
     */
    isStaff() {
        return this.hasRole(['Admin', 'Staff']);
    },
    
    /**
     * ==========================================
     * UI STATE METHODS
     * ==========================================
     */
    
    /**
     * Toggle sidebar collapsed state
     */
    toggleSidebar() {
        this.set('sidebarCollapsed', !this.state.sidebarCollapsed);
        Utils.setStorage('sidebarCollapsed', this.state.sidebarCollapsed);
    },
    
    /**
     * Toggle mobile sidebar
     */
    toggleMobileSidebar() {
        this.set('sidebarOpen', !this.state.sidebarOpen);
    },
    
    /**
     * Close mobile sidebar
     */
    closeMobileSidebar() {
        this.set('sidebarOpen', false);
    },
    
    /**
     * Set loading state
     * @param {boolean} isLoading
     */
    setLoading(isLoading) {
        this.set('isLoading', isLoading);
    },
    
    /**
     * Set current page
     * @param {string} page
     * @param {string} title
     */
    setCurrentPage(page, title = '') {
        this.update({
            currentPage: page,
            pageTitle: title
        });
    },
    
    /**
     * Set theme with smooth transition
     * @param {string} theme
     */
    setTheme(theme) {
        // Add transition class for smooth color change
        document.documentElement.classList.add('theme-transitioning');
        
        this.set('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        Utils.setStorage('theme', theme);

        // Remove transition class after animation completes
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transitioning');
        }, 300);
    },
    
    /**
     * Toggle theme
     */
    toggleTheme() {
        const newTheme = this.state.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    },
    
    /**
     * ==========================================
     * DATA METHODS
     * ==========================================
     */
    
    /**
     * Set pagination
     * @param {Object} pagination
     */
    setPagination(pagination) {
        this.set('pagination', { ...this.state.pagination, ...pagination });
    },
    
    /**
     * Set filters
     * @param {Object} filters
     */
    setFilters(filters) {
        this.set('filters', { ...this.state.filters, ...filters });
    },
    
    /**
     * Clear filters
     */
    clearFilters() {
        this.set('filters', {});
    },
    
    /**
     * Cache data
     * @param {string} key
     * @param {*} data
     */
    cache(key, data) {
        this.set(key, data);
    },
    
    /**
     * Get cached data
     * @param {string} key
     * @returns {*}
     */
    getCache(key) {
        return this.get(key);
    },
    
    /**
     * Clear cache
     * @param {string} key
     */
    clearCache(key) {
        if (key) {
            this.set(key, Array.isArray(this.get(key)) ? [] : null);
        } else {
            this.reset(['animals', 'users', 'adoptions', 'inventory', 'invoices', 'dashboardStats']);
        }
    },
    
    /**
     * ==========================================
     * PERSISTENCE
     * ==========================================
     */
    
    /**
     * Load persisted state from storage
     */
    loadPersistedState() {
        // Load theme
        const theme = Utils.getStorage('theme', 'light');
        this.setTheme(theme);
        
        // Load sidebar state
        const sidebarCollapsed = Utils.getStorage('sidebarCollapsed', false);
        this.set('sidebarCollapsed', sidebarCollapsed);
        
        // Load notifications preference
        const notifications = Utils.getStorage('notifications', true);
        this.set('notifications', notifications);
    },
    
    /**
     * Save state to storage
     * @param {Array} keys - Keys to persist
     */
    persistState(keys) {
        keys.forEach(key => {
            Utils.setStorage(key, this.get(key));
        });
    },
    
    /**
     * ==========================================
     * DEBUG
     * ==========================================
     */
    
    /**
     * Get full state (for debugging)
     * @returns {Object}
     */
    getState() {
        return Utils.deepClone(this.state);
    },
    
    /**
     * Log state to console
     */
    debug() {
        console.group('Store State');
        console.log(JSON.parse(JSON.stringify(this.state)));
        console.groupEnd();
    }
};

// Make Store globally available
window.Store = Store;