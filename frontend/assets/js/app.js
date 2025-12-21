/**
 * App - Main Application
 * Initializes and bootstraps the application
 * 
 * @package AnimalShelter
 */

const App = {
    /**
     * Application version
     */
    version: '1.0.0',
    
    /**
     * Debug mode
     */
    debug: true,
    
    /**
     * Initialize the application
     */
    async init() {
        try {
            this.log('Initializing application...');
            
            // Show loading screen
            this.showLoading();
            
            // Load persisted state
            Store.loadPersistedState();
            
            // Initialize authentication
            const isAuthenticated = await Auth.init();
            this.log('Authentication status:', isAuthenticated);
            
            // Initialize router
            Router.init();
            
            // Setup global event listeners
            this.setupEventListeners();
            
            // Setup global error handlers
            this.setupErrorHandlers();
            
            // Hide loading screen
            await Utils.sleep(500); // Smooth transition
            this.hideLoading();
            
            this.log('Application initialized successfully');
            
        } catch (error) {
            console.error('Application initialization failed:', error);
            this.hideLoading();
            Toast.error('Failed to initialize application. Please refresh the page.');
        }
    },
    
    /**
     * Show loading screen
     */
    showLoading() {
        const loadingScreen = Utils.$('#loading-screen');
        if (loadingScreen) {
            Utils.show(loadingScreen);
        }
    },
    
    /**
     * Hide loading screen
     */
    hideLoading() {
        const loadingScreen = Utils.$('#loading-screen');
        if (loadingScreen) {
            loadingScreen.classList.add('hidden');
        }
    },
    
    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Handle all link clicks for SPA navigation
        document.addEventListener('click', (e) => {
            // Find closest anchor tag
            const link = e.target.closest('a');
            
            if (link) {
                const href = link.getAttribute('href');
                
                // Check if it's an internal link
                if (href && href.startsWith('/') && !href.startsWith('//')) {
                    e.preventDefault();
                    Router.navigate(href);
                }
            }
        });
        
        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Close any open modals
                Modal.closeAll();
                
                // Close dropdowns
                Utils.$$('.dropdown.open').forEach(dropdown => {
                    dropdown.classList.remove('open');
                });
                
                // Close mobile sidebar
                Store.closeMobileSidebar();
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', Utils.debounce(() => {
            // Close mobile sidebar on desktop
            if (window.innerWidth >= 1024) {
                Store.closeMobileSidebar();
            }
        }, 250));
        
        // Handle online/offline status
        window.addEventListener('online', () => {
            Toast.success('Connection restored');
        });
        
        window.addEventListener('offline', () => {
            Toast.warning('You are offline. Some features may be unavailable.');
        });
        
        // Handle visibility change (tab switching)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                // Could refresh data here
                this.log('Tab became visible');
            }
        });
        
        // Subscribe to store changes
        Store.subscribe('sidebarCollapsed', (collapsed) => {
            const sidebar = Utils.$('#sidebar');
            if (sidebar) {
                sidebar.classList.toggle('collapsed', collapsed);
            }
        });
        
        Store.subscribe('sidebarOpen', (open) => {
            const sidebar = Utils.$('#sidebar');
            const overlay = Utils.$('.sidebar-overlay');
            
            if (sidebar) {
                sidebar.classList.toggle('open', open);
            }
            
            if (overlay) {
                overlay.classList.toggle('hidden', !open);
            }
        });
        
        Store.subscribe('isLoading', (isLoading) => {
            // Could show/hide a loading indicator
            document.body.classList.toggle('is-loading', isLoading);
        });
    },
    
    /**
     * Setup global error handlers
     */
    setupErrorHandlers() {
        // Handle uncaught errors
        window.addEventListener('error', (e) => {
            console.error('Uncaught error:', e.error);
            
            if (this.debug) {
                Toast.error(`Error: ${e.message}`);
            }
        });
        
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
            
            if (e.reason instanceof APIError) {
                // API errors are already handled
                return;
            }
            
            if (this.debug) {
                Toast.error('An unexpected error occurred');
            }
        });
    },
    
    /**
     * Log message (only in debug mode)
     * @param {...any} args
     */
    log(...args) {
        if (this.debug) {
            console.log('[App]', ...args);
        }
    },
    
    /**
     * Get application info
     * @returns {Object}
     */
    getInfo() {
        return {
            version: this.version,
            debug: this.debug,
            user: Auth.currentUser(),
            isAuthenticated: Auth.isAuthenticated(),
            currentRoute: Router.currentRoute
        };
    }
};

/**
 * Initialize app when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Make App globally available
window.App = App;