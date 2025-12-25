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
     * Debug mode - set to false for production
     */
    debug: false,

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

            // Initialize scroll-to-top button
            this.initScrollToTop();

            // Initialize pull-to-refresh for mobile
            this.initPullToRefresh();

            // Initialize onboarding for new users (only if authenticated)
            if (isAuthenticated) {
                this.initOnboarding();
            }

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

        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            const activeElement = document.activeElement;
            const isInputFocused = activeElement && (
                activeElement.tagName === 'INPUT' || 
                activeElement.tagName === 'TEXTAREA' || 
                activeElement.isContentEditable
            );

            if (e.key === 'Escape') {
                // Close any open modals
                Modal.closeAll();

                // Close dropdowns
                Utils.$$('.dropdown.open').forEach(dropdown => {
                    dropdown.classList.remove('open');
                });

                // Close mobile sidebar
                Store.closeMobileSidebar();

                // Blur focused input
                if (isInputFocused) {
                    activeElement.blur();
                }
            }

            // Don't trigger shortcuts when typing in inputs
            if (isInputFocused) return;

            // "/" - Focus search input
            if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
                const searchInput = Utils.$('.search-input, input[type="search"], #global-search');
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }

            // "Ctrl/Cmd + K" - Focus search (alternative)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = Utils.$('.search-input, input[type="search"], #global-search');
                if (searchInput) {
                    searchInput.focus();
                }
            }

            // "?" - Show keyboard shortcuts help
            if (e.key === '?' && e.shiftKey) {
                e.preventDefault();
                this.showKeyboardShortcuts();
            }

            // "g + h" - Go home (dashboard)
            if (e.key === 'g') {
                this._gPressed = true;
                setTimeout(() => { this._gPressed = false; }, 1000);
            }
            if (this._gPressed && e.key === 'h') {
                e.preventDefault();
                Router.navigate('/dashboard');
                this._gPressed = false;
            }

            // "g + a" - Go to animals
            if (this._gPressed && e.key === 'a') {
                e.preventDefault();
                Router.navigate('/animals');
                this._gPressed = false;
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
                overlay.classList.toggle('visible', open);
            }

            // Add body class to prevent scrolling when sidebar is open on mobile
            document.body.classList.toggle('sidebar-open', open);
            if (open) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        Store.subscribe('isLoading', (isLoading) => {
            // Could show/hide a loading indicator
            document.body.classList.toggle('is-loading', isLoading);
        });

        // Subscribe to user changes for global UI updates
        Store.subscribe('user', (newUser) => {
            if (newUser) {
                // Update sidebar profile info
                if (window.Sidebar) {
                    Sidebar.updateProfile();
                }

                // Refresh current route content
                Router.refresh();
            }
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
    },

    /**
     * Show keyboard shortcuts help modal
     */
    showKeyboardShortcuts() {
        Modal.open({
            title: 'Keyboard Shortcuts',
            size: 'sm',
            content: `
                <div class="shortcuts-list">
                    <div class="shortcut-group">
                        <h4>Navigation</h4>
                        <div class="shortcut-item">
                            <span class="shortcut-keys"><kbd>g</kbd> then <kbd>h</kbd></span>
                            <span class="shortcut-desc">Go to Dashboard</span>
                        </div>
                        <div class="shortcut-item">
                            <span class="shortcut-keys"><kbd>g</kbd> then <kbd>a</kbd></span>
                            <span class="shortcut-desc">Go to Animals</span>
                        </div>
                    </div>
                    <div class="shortcut-group">
                        <h4>Search</h4>
                        <div class="shortcut-item">
                            <span class="shortcut-keys"><kbd>/</kbd></span>
                            <span class="shortcut-desc">Focus search</span>
                        </div>
                        <div class="shortcut-item">
                            <span class="shortcut-keys"><kbd>Ctrl</kbd> + <kbd>K</kbd></span>
                            <span class="shortcut-desc">Focus search (alt)</span>
                        </div>
                    </div>
                    <div class="shortcut-group">
                        <h4>General</h4>
                        <div class="shortcut-item">
                            <span class="shortcut-keys"><kbd>Esc</kbd></span>
                            <span class="shortcut-desc">Close modal/dropdown</span>
                        </div>
                        <div class="shortcut-item">
                            <span class="shortcut-keys"><kbd>?</kbd></span>
                            <span class="shortcut-desc">Show this help</span>
                        </div>
                    </div>
                </div>
            `,
            footer: '<button class="btn btn-primary" data-action="cancel">Close</button>'
        });
    },

    /**
     * Initialize scroll-to-top button
     */
    initScrollToTop() {
        // Create scroll-to-top button
        const scrollBtn = document.createElement('button');
        scrollBtn.id = 'scroll-to-top';
        scrollBtn.className = 'scroll-to-top-btn';
        scrollBtn.setAttribute('aria-label', 'Scroll to top');
        scrollBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        `;
        document.body.appendChild(scrollBtn);

        // Show/hide based on scroll position
        const mainContent = Utils.$('.main-content') || window;
        const scrollContainer = Utils.$('.main-content') || document.documentElement;

        const handleScroll = Utils.throttle(() => {
            const scrollTop = scrollContainer.scrollTop || window.pageYOffset;
            if (scrollTop > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        }, 100);

        if (Utils.$('.main-content')) {
            Utils.$('.main-content').addEventListener('scroll', handleScroll);
        } else {
            window.addEventListener('scroll', handleScroll);
        }

        // Handle click
        scrollBtn.addEventListener('click', () => {
            if (Utils.$('.main-content')) {
                Utils.$('.main-content').scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    },

    /**
     * Initialize pull-to-refresh for mobile
     */
    initPullToRefresh() {
        if (!('ontouchstart' in window)) return;

        const mainContent = Utils.$('.main-content');
        if (!mainContent) return;

        let touchStartY = 0;
        let touchMoveY = 0;
        let isPulling = false;
        let pullIndicator = null;

        // Create pull indicator
        pullIndicator = document.createElement('div');
        pullIndicator.className = 'pull-to-refresh-indicator';
        pullIndicator.innerHTML = `
            <div class="pull-spinner">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
            </div>
            <span class="pull-text">Pull to refresh</span>
        `;
        mainContent.insertBefore(pullIndicator, mainContent.firstChild);

        mainContent.addEventListener('touchstart', (e) => {
            if (mainContent.scrollTop === 0) {
                touchStartY = e.touches[0].clientY;
                isPulling = true;
            }
        }, { passive: true });

        mainContent.addEventListener('touchmove', (e) => {
            if (!isPulling) return;

            touchMoveY = e.touches[0].clientY;
            const pullDistance = touchMoveY - touchStartY;

            if (pullDistance > 0 && pullDistance < 150) {
                pullIndicator.style.height = `${Math.min(pullDistance, 80)}px`;
                pullIndicator.style.opacity = Math.min(pullDistance / 80, 1);

                if (pullDistance > 60) {
                    pullIndicator.classList.add('ready');
                    pullIndicator.querySelector('.pull-text').textContent = 'Release to refresh';
                } else {
                    pullIndicator.classList.remove('ready');
                    pullIndicator.querySelector('.pull-text').textContent = 'Pull to refresh';
                }
            }
        }, { passive: true });

        mainContent.addEventListener('touchend', async () => {
            if (!isPulling) return;

            const pullDistance = touchMoveY - touchStartY;

            if (pullDistance > 60) {
                pullIndicator.classList.add('refreshing');
                pullIndicator.querySelector('.pull-text').textContent = 'Refreshing...';

                // Refresh current route
                await Router.refresh();

                pullIndicator.classList.remove('refreshing');
            }

            // Reset
            isPulling = false;
            touchStartY = 0;
            touchMoveY = 0;
            pullIndicator.style.height = '0';
            pullIndicator.style.opacity = '0';
            pullIndicator.classList.remove('ready');
        });
    },

    /**
     * Initialize onboarding for first-time users
     */
    initOnboarding() {
        const hasSeenOnboarding = localStorage.getItem('dogpound_onboarding_complete');
        if (hasSeenOnboarding) return;

        // Show onboarding after a short delay
        setTimeout(() => {
            this.showOnboardingTip(0);
        }, 1500);
    },

    /**
     * Show onboarding tooltip
     * @param {number} step - Current step index
     */
    showOnboardingTip(step) {
        const tips = [
            {
                target: '.sidebar',
                message: 'Navigate through different sections using the sidebar menu.',
                position: 'right'
            },
            {
                target: '.header-actions .avatar',
                message: 'Access your profile and settings here.',
                position: 'bottom'
            },
            {
                target: '.search-input',
                message: 'Quickly find animals, users, or records using search (press "/" to focus).',
                position: 'bottom'
            }
        ];

        if (step >= tips.length) {
            localStorage.setItem('dogpound_onboarding_complete', 'true');
            return;
        }

        const tip = tips[step];
        const target = Utils.$(tip.target);

        if (!target) {
            this.showOnboardingTip(step + 1);
            return;
        }

        // Remove existing tooltip
        const existing = Utils.$('.onboarding-tooltip');
        if (existing) existing.remove();

        // Create tooltip
        const tooltip = document.createElement('div');
        tooltip.className = `onboarding-tooltip onboarding-${tip.position}`;
        tooltip.innerHTML = `
            <div class="onboarding-content">
                <p>${tip.message}</p>
                <div class="onboarding-footer">
                    <span class="onboarding-progress">${step + 1} of ${tips.length}</span>
                    <div class="onboarding-actions">
                        <button class="btn btn-sm btn-ghost" data-action="skip">Skip</button>
                        <button class="btn btn-sm btn-primary" data-action="next">
                            ${step === tips.length - 1 ? 'Got it!' : 'Next'}
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(tooltip);

        // Position tooltip
        const rect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        switch (tip.position) {
            case 'right':
                tooltip.style.top = `${rect.top + rect.height / 2 - tooltipRect.height / 2}px`;
                tooltip.style.left = `${rect.right + 10}px`;
                break;
            case 'bottom':
                tooltip.style.top = `${rect.bottom + 10}px`;
                tooltip.style.left = `${rect.left + rect.width / 2 - tooltipRect.width / 2}px`;
                break;
            case 'left':
                tooltip.style.top = `${rect.top + rect.height / 2 - tooltipRect.height / 2}px`;
                tooltip.style.right = `${window.innerWidth - rect.left + 10}px`;
                break;
        }

        // Add highlight to target
        target.classList.add('onboarding-highlight');

        // Handle actions
        tooltip.querySelector('[data-action="next"]').addEventListener('click', () => {
            target.classList.remove('onboarding-highlight');
            tooltip.remove();
            this.showOnboardingTip(step + 1);
        });

        tooltip.querySelector('[data-action="skip"]').addEventListener('click', () => {
            target.classList.remove('onboarding-highlight');
            tooltip.remove();
            localStorage.setItem('dogpound_onboarding_complete', 'true');
        });
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