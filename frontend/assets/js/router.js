/**
 * Router - Client-Side Routing
 * Handles navigation and page rendering
 * 
 * @package AnimalShelter
 */

const Router = {
    /**
     * Registered routes
     */
    routes: {},

    /**
     * Current route
     */
    currentRoute: null,

    /**
     * Previous route
     */
    previousRoute: null,

    /**
     * Route history
     */
    history: [],

    /**
     * Before route change hooks
     */
    beforeHooks: [],

    /**
     * After route change hooks
     */
    afterHooks: [],

    /**
     * ==========================================
     * INITIALIZATION
     * ==========================================
     */

    /**
     * Initialize router
     */
    init() {
        // Register default routes
        this.registerRoutes();

        // Listen for popstate (back/forward buttons)
        window.addEventListener('popstate', (e) => {
            this.handleRoute(window.location.pathname, false);
        });

        // Handle initial route
        this.handleRoute(window.location.pathname, false);
    },

    /**
     * Register all routes
     */
    registerRoutes() {
        // Auth routes
        this.register('/login', {
            page: 'login',
            title: 'Login',
            component: LoginPage,
            guard: () => Auth.requireGuest(),
            layout: 'auth'
        });

        this.register('/register', {
            page: 'register',
            title: 'Register',
            component: LoginPage,
            guard: () => Auth.requireGuest(),
            layout: 'auth'
        });

        // Dashboard
        this.register('/', {
            page: 'dashboard',
            title: 'Dashboard',
            component: DashboardPage,
            guard: () => Auth.requireAuth()
        });

        this.register('/dashboard', {
            page: 'dashboard',
            title: 'Dashboard',
            component: DashboardPage,
            guard: () => Auth.requireAuth()
        });

        // Animals
        this.register('/animals', {
            page: 'animals',
            title: 'Animals',
            component: AnimalsPage,
            guard: () => Auth.requireAuth()
        });

        this.register('/animals/:id', {
            page: 'animal-detail',
            title: 'Animal Details',
            component: AnimalDetailPage,
            guard: () => Auth.requireAuth()
        });

        // Adoptions
        this.register('/adoptions', {
            page: 'adoptions',
            title: 'Adoptions',
            component: AdoptionsPage,
            guard: () => Auth.requireAuth()
        });

        // Medical
        this.register('/medical', {
            page: 'medical',
            title: 'Medical Records',
            component: MedicalPage,
            guard: () => Auth.requireStaff()
        });

        // Inventory
        this.register('/inventory', {
            page: 'inventory',
            title: 'Inventory',
            component: InventoryPage,
            guard: () => Auth.requireStaff()
        });

        // Billing
        this.register('/billing', {
            page: 'billing',
            title: 'Billing',
            component: BillingPage,
            guard: () => Auth.requireStaff()
        });

        // Users (Admin only)
        this.register('/users', {
            page: 'users',
            title: 'User Management',
            component: UsersPage,
            guard: () => Auth.requireAdmin()
        });

        // Profile
        this.register('/profile', {
            page: 'profile',
            title: 'My Profile',
            component: ProfilePage,
            guard: () => Auth.requireAuth()
        });

        // 404
        this.register('/404', {
            page: 'not-found',
            title: 'Page Not Found',
            component: NotFoundPage,
            layout: 'auth'
        });

    },

    /**
     * ==========================================
     * ROUTE REGISTRATION
     * ==========================================
     */

    /**
     * Register a route
     * @param {string} path - Route path (can include :params)
     * @param {Object} config - Route configuration
     */
    register(path, config) {
        this.routes[path] = {
            path,
            page: config.page,
            title: config.title,
            component: config.component,
            guard: config.guard || (() => true),
            layout: config.layout || 'main',
            meta: config.meta || {}
        };
    },

    /**
     * ==========================================
     * NAVIGATION
     * ==========================================
     */

    /**
     * Navigate to a route
     * @param {string} path - Route path
     * @param {Object} options - Navigation options
     */
    navigate(path, options = {}) {
        const { replace = false, params = {}, query = {} } = options;

        // Build URL with query string
        let url = path;
        if (Object.keys(query).length > 0) {
            const queryString = new URLSearchParams(query).toString();
            url = `${path}?${queryString}`;
        }

        // Update browser history
        if (replace) {
            window.history.replaceState({ path, params }, '', url);
        } else {
            window.history.pushState({ path, params }, '', url);
        }

        // Handle the route
        this.handleRoute(path, true, params);
    },

    /**
     * Navigate back
     */
    back() {
        window.history.back();
    },

    /**
     * Navigate forward
     */
    forward() {
        window.history.forward();
    },

    /**
     * Refresh current route
     */
    refresh() {
        if (this.currentRoute) {
            this.handleRoute(this.currentRoute.path, false);
        }
    },

    /**
     * ==========================================
     * ROUTE HANDLING
     * ==========================================
     */

    /**
     * Handle route change
     * @param {string} path - Route path
     * @param {boolean} isNavigate - If this is a programmatic navigation
     * @param {Object} params - Route params
     */
    async handleRoute(path, isNavigate = true, params = {}) {
        // Find matching route
        const { route, routeParams } = this.matchRoute(path);

        if (!route) {
            this.navigate('/404', { replace: true });
            return;
        }

        // Merge params
        const allParams = { ...routeParams, ...params };

        // Run before hooks
        for (const hook of this.beforeHooks) {
            const result = await hook(route, this.currentRoute);
            if (result === false) return;
        }

        // Run route guard
        if (route.guard && !route.guard()) {
            return;
        }

        // Update previous and current route
        this.previousRoute = this.currentRoute;
        this.currentRoute = { ...route, params: allParams };

        // Add to history
        this.history.push(this.currentRoute);
        if (this.history.length > 50) {
            this.history.shift();
        }

        // Update page title
        document.title = `${route.title} | Catarman Dog Pound`;

        // Update store
        Store.setCurrentPage(route.page, route.title);

        // Render the route
        await this.render(route, allParams);

        // Run after hooks
        for (const hook of this.afterHooks) {
            await hook(route, this.previousRoute);
        }

        // Update sidebar active state
        if (window.Sidebar) {
            Sidebar.updateActive();
        }

        // Scroll to top
        window.scrollTo(0, 0);
    },

    /**
     * Match path to route
     * @param {string} path - URL path
     * @returns {Object} - Matched route and params
     */
    matchRoute(path) {
        // Remove query string
        path = path.split('?')[0];

        // Remove trailing slash
        path = path.replace(/\/$/, '') || '/';

        // Check for exact match first
        if (this.routes[path]) {
            return { route: this.routes[path], routeParams: {} };
        }

        // Check for parameterized routes
        for (const routePath in this.routes) {
            const route = this.routes[routePath];
            const params = this.extractParams(routePath, path);

            if (params !== null) {
                return { route, routeParams: params };
            }
        }

        return { route: null, routeParams: {} };
    },

    /**
     * Extract params from path
     * @param {string} routePath - Route definition path
     * @param {string} actualPath - Actual URL path
     * @returns {Object|null} - Params object or null if no match
     */
    extractParams(routePath, actualPath) {
        const routeParts = routePath.split('/');
        const pathParts = actualPath.split('/');

        if (routeParts.length !== pathParts.length) {
            return null;
        }

        const params = {};

        for (let i = 0; i < routeParts.length; i++) {
            if (routeParts[i].startsWith(':')) {
                // This is a parameter
                const paramName = routeParts[i].slice(1);
                params[paramName] = pathParts[i];
            } else if (routeParts[i] !== pathParts[i]) {
                // Parts don't match
                return null;
            }
        }

        return params;
    },

    /**
     * ==========================================
     * RENDERING
     * ==========================================
     */

    /**
     * Render route
     * @param {Object} route - Route config
     * @param {Object} params - Route params
     */
    async render(route, params) {
        try {
            // Show loading
            Store.setLoading(true);

            // Get containers
            const authContainer = Utils.$('#auth-container');
            const mainContainer = Utils.$('#main-container');
            const pageContent = Utils.$('#page-content');



            // Switch layout
            if (route.layout === 'auth') {
                Utils.hide(mainContainer);
                Utils.show(authContainer);

                // Render auth page
                if (route.component && typeof route.component.render === 'function') {
                    authContainer.innerHTML = await route.component.render(params);
                    if (typeof route.component.afterRender === 'function') {
                        await route.component.afterRender(params);
                    }
                }
            } else {
                Utils.hide(authContainer);
                Utils.show(mainContainer);

                // Render sidebar and header if not already rendered
                this.renderLayout(route.layout);

                // Render page content
                if (route.component && typeof route.component.render === 'function') {
                    pageContent.innerHTML = await route.component.render(params);
                    if (typeof route.component.afterRender === 'function') {
                        await route.component.afterRender(params);
                    }
                }
            }

        } catch (error) {
            console.error('Render error:', error);
            Toast.error(`Failed to load page: ${error.message}`);
        } finally {
            Store.setLoading(false);
        }
    },

    /**
     * Render main layout (sidebar & header)
     * @param {string} layoutType
     */
    renderLayout(layoutType) {
        const sidebar = Utils.$('#sidebar');
        const header = Utils.$('#header');

        // Render sidebar if empty and NOT landing layout
        if (layoutType !== 'landing') {
            if (sidebar) {
                sidebar.style.display = ''; // Reset display
                if (!sidebar.innerHTML.trim()) {
                    Sidebar.render();
                }
            }
        } else {
            // Hide sidebar for landing layout
            if (sidebar) sidebar.style.display = 'none';
        }

        // Render header if empty
        if (header && !header.innerHTML.trim()) {
            Header.render();
        }
    },

    /**
     * ==========================================
     * HOOKS
     * ==========================================
     */

    /**
     * Add before route change hook
     * @param {Function} hook
     */
    beforeEach(hook) {
        this.beforeHooks.push(hook);
    },

    /**
     * Add after route change hook
     * @param {Function} hook
     */
    afterEach(hook) {
        this.afterHooks.push(hook);
    },

    /**
     * ==========================================
     * UTILITIES
     * ==========================================
     */

    /**
     * Get current path
     * @returns {string}
     */
    getCurrentPath() {
        return window.location.pathname;
    },

    /**
     * Get query parameters
     * @returns {Object}
     */
    getQuery() {
        return Utils.getQueryParams();
    },

    /**
     * Get specific query parameter
     * @param {string} key
     * @returns {string|null}
     */
    getQueryParam(key) {
        return new URLSearchParams(window.location.search).get(key);
    },

    /**
     * Check if current route matches path
     * @param {string} path
     * @returns {boolean}
     */
    isActive(path) {
        if (!this.currentRoute) return false;

        // Exact match
        if (this.currentRoute.path === path) return true;

        // Handle Dashboard alias
        if ((this.currentRoute.path === '/' && path === '/dashboard') ||
            (this.currentRoute.path === '/dashboard' && path === '/')) {
            return true;
        }

        // Parent match (e.g., /animals/1 matches /animals)
        const currentPath = this.getCurrentPath();
        return currentPath.startsWith(path) && path !== '/';
    },

    /**
     * Get route by page name
     * @param {string} page
     * @returns {Object|null}
     */
    getRouteByPage(page) {
        for (const path in this.routes) {
            if (this.routes[path].page === page) {
                return this.routes[path];
            }
        }
        return null;
    },

    /**
     * Generate URL for route
     * @param {string} page - Page name
     * @param {Object} params - Route params
     * @returns {string}
     */
    url(page, params = {}) {
        const route = this.getRouteByPage(page);
        if (!route) return '/';

        let url = route.path;

        // Replace params
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(`:${key}`, value);
        }

        return url;
    }
};

/**
 * Simple 404 Page Component
 */
const NotFoundPage = {
    render() {
        return `
            <div class="auth-card" style="text-align: center;">
                <div style="font-size: 72px; margin-bottom: 16px;">🔍</div>
                <h1 style="margin-bottom: 8px;">404</h1>
                <p style="color: var(--text-secondary); margin-bottom: 24px;">
                    The page you're looking for doesn't exist.
                </p>
                <button class="btn btn-primary" onclick="Router.navigate('/dashboard')">
                    Go to Dashboard
                </button>
            </div>
        `;
    }
};

// Make Router globally available
window.Router = Router;