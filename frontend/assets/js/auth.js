/**
 * Auth - Authentication Handler
 * Manages user authentication, tokens, and session
 * 
 * @package AnimalShelter
 */

const Auth = {
    /**
     * Storage keys
     */
    TOKEN_KEY: 'access_token',
    REFRESH_TOKEN_KEY: 'refresh_token',
    USER_KEY: 'user',

    /**
     * Token refresh threshold (5 minutes before expiry)
     */
    REFRESH_THRESHOLD: 5 * 60 * 1000,

    /**
     * Refresh timer
     */
    refreshTimer: null,

    /**
     * ==========================================
     * INITIALIZATION
     * ==========================================
     */

    /**
     * Initialize auth
     * Check for existing session
     */
    async init() {
        const token = this.getToken();
        const user = this.getUser();

        if (token && user) {
            // Validate token
            try {
                const response = await API.users.profile();
                if (response.success) {
                    Store.setUser(response.data);
                    this.startRefreshTimer();
                    return true;
                }
            } catch (error) {
                // Token invalid, try refresh
                if (error.status === 401) {
                    const refreshed = await this.refreshToken();
                    if (refreshed) {
                        return true;
                    }
                }

                // Clear invalid session
                this.clearSession();
            }
        }

        return false;
    },

    /**
     * ==========================================
     * AUTHENTICATION METHODS
     * ==========================================
     */

    /**
     * Login user
     * @param {string} username - Username or email
     * @param {string} password
     * @returns {Promise<Object>}
     */
    async login(username, password) {
        try {
            const response = await API.auth.login({ username, password });

            if (response.success) {
                // Save tokens
                this.setToken(response.data.access_token);
                if (response.data.refresh_token) {
                    this.setRefreshToken(response.data.refresh_token);
                }

                // Save user
                this.setUser(response.data.user);
                Store.setUser(response.data.user);

                // Start refresh timer
                this.startRefreshTimer();

                return response;
            }

            throw new Error(response.message || 'Login failed');

        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    },

    /**
     * Register new user
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    async register(data) {
        try {
            const response = await API.auth.register(data);
            return response;
        } catch (error) {
            console.error('Registration error:', error);
            throw error;
        }
    },

    /**
     * Logout user
     */
    async logout() {
        try {
            // Call logout endpoint
            await API.auth.logout();
        } catch (error) {
            // Ignore errors, still clear local session
            console.error('Logout error:', error);
        } finally {
            this.clearSession();
            Store.clearUser();
            Store.clearCache();
            // Force refresh to clear all state
            window.location.href = '/login';
        }
    },

    /**
     * Refresh access token
     * @returns {Promise<boolean>}
     */
    async refreshToken() {
        const refreshToken = this.getRefreshToken();

        if (!refreshToken) {
            return false;
        }

        try {
            const response = await API.auth.refresh(refreshToken);

            if (response.success) {
                this.setToken(response.data.access_token);
                this.startRefreshTimer();
                return true;
            }

            return false;

        } catch (error) {
            console.error('Token refresh error:', error);
            return false;
        }
    },

    /**
     * ==========================================
     * TOKEN MANAGEMENT
     * ==========================================
     */

    /**
     * Get access token
     * @returns {string|null}
     */
    getToken() {
        return Utils.getStorage(this.TOKEN_KEY);
    },

    /**
     * Set access token
     * @param {string} token
     */
    setToken(token) {
        Utils.setStorage(this.TOKEN_KEY, token);
    },

    /**
     * Get refresh token
     * @returns {string|null}
     */
    getRefreshToken() {
        return Utils.getStorage(this.REFRESH_TOKEN_KEY);
    },

    /**
     * Set refresh token
     * @param {string} token
     */
    setRefreshToken(token) {
        Utils.setStorage(this.REFRESH_TOKEN_KEY, token);
    },

    /**
     * Parse JWT token
     * @param {string} token
     * @returns {Object|null}
     */
    parseToken(token) {
        if (!token) return null;

        try {
            const parts = token.split('.');
            if (parts.length !== 3) return null;

            const payload = JSON.parse(atob(parts[1]));
            return payload;
        } catch (error) {
            console.error('Token parse error:', error);
            return null;
        }
    },

    /**
     * Check if token is expired
     * @param {string} token
     * @returns {boolean}
     */
    isTokenExpired(token) {
        const payload = this.parseToken(token);

        if (!payload || !payload.exp) {
            return true;
        }

        // Check if expired (with threshold)
        const expiryTime = payload.exp * 1000;
        return Date.now() >= expiryTime - this.REFRESH_THRESHOLD;
    },

    /**
     * Get token expiry time
     * @returns {number|null}
     */
    getTokenExpiry() {
        const token = this.getToken();
        const payload = this.parseToken(token);

        if (!payload || !payload.exp) {
            return null;
        }

        return payload.exp * 1000;
    },

    /**
     * Start token refresh timer
     */
    startRefreshTimer() {
        // Clear existing timer
        this.stopRefreshTimer();

        const expiry = this.getTokenExpiry();
        if (!expiry) return;

        // Calculate time until refresh (5 minutes before expiry)
        const timeUntilRefresh = expiry - Date.now() - this.REFRESH_THRESHOLD;

        if (timeUntilRefresh > 0) {
            this.refreshTimer = setTimeout(async () => {
                const success = await this.refreshToken();
                if (!success) {
                    Toast.warning('Your session has expired. Please login again.');
                    this.logout();
                }
            }, timeUntilRefresh);
        }
    },

    /**
     * Stop token refresh timer
     */
    stopRefreshTimer() {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }
    },

    /**
     * ==========================================
     * USER MANAGEMENT
     * ==========================================
     */

    /**
     * Get user from storage
     * @returns {Object|null}
     */
    getUser() {
        return Utils.getStorage(this.USER_KEY);
    },

    /**
     * Set user in storage
     * @param {Object} user
     */
    setUser(user) {
        Utils.setStorage(this.USER_KEY, user);
    },

    /**
     * Get current user from store
     * @returns {Object|null}
     */
    currentUser() {
        return Store.get('user');
    },

    /**
     * Check if user is authenticated
     * @returns {boolean}
     */
    isAuthenticated() {
        return !!this.getToken() && !!this.getUser();
    },

    /**
     * Check if user has role
     * @param {string|Array} roles
     * @returns {boolean}
     */
    hasRole(roles) {
        const user = this.currentUser();
        if (!user) return false;

        if (!Array.isArray(roles)) {
            roles = [roles];
        }

        return roles.includes(user.role);
    },

    /**
     * Check if user is admin
     * @returns {boolean}
     */
    isAdmin() {
        return this.hasRole('Admin');
    },

    /**
     * Check if user is staff
     * @returns {boolean}
     */
    isStaff() {
        return this.hasRole(['Admin', 'Staff', 'Veterinarian']);
    },

    /**
     * Check if user is veterinarian
     * @returns {boolean}
     */
    isVeterinarian() {
        return this.hasRole(['Admin', 'Veterinarian']);
    },

    /**
     * Check if user is adopter
     * @returns {boolean}
     */
    isAdopter() {
        return this.hasRole('Adopter');
    },

    /**
     * ==========================================
     * SESSION MANAGEMENT
     * ==========================================
     */

    /**
     * Clear session data
     */
    clearSession() {
        this.stopRefreshTimer();
        Utils.removeStorage(this.TOKEN_KEY);
        Utils.removeStorage(this.REFRESH_TOKEN_KEY);
        Utils.removeStorage(this.USER_KEY);
    },

    /**
     * Update user profile in session
     * @param {Object} updates
     */
    updateUserProfile(updates) {
        const user = this.getUser();
        if (user) {
            const updatedUser = { ...user, ...updates };
            this.setUser(updatedUser);
            Store.setUser(updatedUser);
        }
    },

    /**
     * ==========================================
     * PASSWORD MANAGEMENT
     * ==========================================
     */

    /**
     * Change password
     * @param {string} currentPassword
     * @param {string} newPassword
     * @returns {Promise<Object>}
     */
    async changePassword(currentPassword, newPassword) {
        try {
            const response = await API.users.changePassword({
                current_password: currentPassword,
                new_password: newPassword
            });

            return response;
        } catch (error) {
            console.error('Change password error:', error);
            throw error;
        }
    },

    /**
     * Request password reset
     * @param {string} email
     * @returns {Promise<Object>}
     */
    async forgotPassword(email) {
        try {
            const response = await API.auth.forgotPassword(email);
            return response;
        } catch (error) {
            console.error('Forgot password error:', error);
            throw error;
        }
    },

    /**
     * Reset password with token
     * @param {string} token
     * @param {string} newPassword
     * @returns {Promise<Object>}
     */
    async resetPassword(token, newPassword) {
        try {
            const response = await API.auth.resetPassword({
                token,
                password: newPassword
            });
            return response;
        } catch (error) {
            console.error('Reset password error:', error);
            throw error;
        }
    },

    /**
     * ==========================================
     * ROUTE GUARDS
     * ==========================================
     */

    /**
     * Guard for authenticated routes
     * @returns {boolean}
     */
    requireAuth() {
        if (!this.isAuthenticated()) {
            Router.navigate('/login');
            return false;
        }
        return true;
    },

    /**
     * Guard for admin routes
     * @returns {boolean}
     */
    requireAdmin() {
        if (!this.requireAuth()) return false;

        if (!this.isAdmin()) {
            Toast.error('Access denied. Admin privileges required.');
            Router.navigate('/dashboard');
            return false;
        }
        return true;
    },

    /**
     * Guard for staff routes
     * @returns {boolean}
     */
    requireStaff() {
        if (!this.requireAuth()) return false;

        if (!this.isStaff()) {
            Toast.error('Access denied. Staff privileges required.');
            Router.navigate('/dashboard');
            return false;
        }
        return true;
    },

    /**
     * Guard for guest routes (login, register)
     * @returns {boolean}
     */
    requireGuest() {
        if (this.isAuthenticated()) {
            Router.navigate('/dashboard');
            return false;
        }
        return true;
    },

    /**
     * Guard for dashboard access (non-adopters)
     * @returns {boolean}
     */

};

// Make Auth globally available
window.Auth = Auth;