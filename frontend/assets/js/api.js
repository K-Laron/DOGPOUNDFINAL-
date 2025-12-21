/**
 * API Client
 * Handles all HTTP requests to the backend
 * 
 * @package AnimalShelter
 */

const API = {
    /**
     * Base URL for API requests
     */
    baseURL: `http://${window.location.hostname}:8000`,

    /**
     * Default headers
     */
    defaultHeaders: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },

    /**
     * Request timeout in milliseconds
     */
    timeout: 30000,

    /**
     * ==========================================
     * CORE REQUEST METHOD
     * ==========================================
     */

    /**
     * Make HTTP request
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>}
     */
    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseURL}${endpoint}`;

        // Build headers
        const headers = { ...this.defaultHeaders };

        // Add auth token if available
        const token = Auth.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        // Merge custom headers
        if (options.headers) {
            Object.assign(headers, options.headers);
        }

        // Build fetch options
        const fetchOptions = {
            method: method.toUpperCase(),
            headers
        };

        // Add body for non-GET requests
        if (data && method.toUpperCase() !== 'GET') {
            if (data instanceof FormData) {
                delete headers['Content-Type']; // Let browser set it for FormData
                fetchOptions.body = data;
            } else {
                fetchOptions.body = JSON.stringify(data);
            }
        }

        // Add query params for GET requests
        let requestUrl = url;
        if (data && method.toUpperCase() === 'GET') {
            const params = new URLSearchParams();
            Object.entries(data).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    params.append(key, value);
                }
            });
            const queryString = params.toString();
            if (queryString) {
                requestUrl = `${url}?${queryString}`;
            }
        }

        try {
            // Create abort controller for timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.timeout);
            fetchOptions.signal = controller.signal;

            // Make request
            const response = await fetch(requestUrl, fetchOptions);
            clearTimeout(timeoutId);

            // Parse response
            const contentType = response.headers.get('content-type');
            let responseData;

            if (contentType && contentType.includes('application/json')) {
                responseData = await response.json();
            } else {
                responseData = await response.text();
            }

            // Handle errors
            if (!response.ok) {
                return this.handleError(response, responseData);
            }

            return responseData;

        } catch (error) {
            // Rethrow API errors
            if (error instanceof APIError) {
                throw error;
            }

            // Handle network errors
            if (error.name === 'AbortError') {
                throw new APIError('Request timeout. Please try again.', 408);
            }

            if (!navigator.onLine) {
                throw new APIError('No internet connection. Please check your network.', 0);
            }

            console.error('API Error:', error);
            throw new APIError('An unexpected error occurred. Please try again.', 500);
        }
    },

    /**
     * Handle API errors
     * @param {Response} response
     * @param {Object} data
     */
    handleError(response, data) {
        const status = response.status;
        let message = data?.message || 'An error occurred';

        // Handle specific status codes
        switch (status) {
            case 401:
                // Unauthorized - clear auth and redirect
                if (!window.location.pathname.includes('login')) {
                    Auth.logout();
                    Toast.error('Session expired. Please login again.');
                    Router.navigate('/login');
                }
                break;

            case 403:
                message = data?.message || 'You do not have permission to perform this action.';
                break;

            case 404:
                message = data?.message || 'The requested resource was not found.';
                break;

            case 422:
                // Validation errors
                if (data?.errors) {
                    const errors = Object.values(data.errors).flat();
                    message = errors.join(', ');
                }
                break;

            case 429:
                message = 'Too many requests. Please wait a moment and try again.';
                break;

            case 500:
                message = data?.message || 'Server error. Please try again later.';
                break;
        }

        throw new APIError(message, status, data);
    },

    /**
     * ==========================================
     * HTTP METHOD SHORTCUTS
     * ==========================================
     */

    /**
     * GET request
     * @param {string} endpoint
     * @param {Object} params
     * @param {Object} options
     * @returns {Promise<Object>}
     */
    get(endpoint, params = null, options = {}) {
        return this.request('GET', endpoint, params, options);
    },

    /**
     * POST request
     * @param {string} endpoint
     * @param {Object} data
     * @param {Object} options
     * @returns {Promise<Object>}
     */
    post(endpoint, data = null, options = {}) {
        return this.request('POST', endpoint, data, options);
    },

    /**
     * PUT request
     * @param {string} endpoint
     * @param {Object} data
     * @param {Object} options
     * @returns {Promise<Object>}
     */
    put(endpoint, data = null, options = {}) {
        return this.request('PUT', endpoint, data, options);
    },

    /**
     * PATCH request
     * @param {string} endpoint
     * @param {Object} data
     * @param {Object} options
     * @returns {Promise<Object>}
     */
    patch(endpoint, data = null, options = {}) {
        return this.request('PATCH', endpoint, data, options);
    },

    /**
     * DELETE request
     * @param {string} endpoint
     * @param {Object} data
     * @param {Object} options
     * @returns {Promise<Object>}
     */
    delete(endpoint, data = null, options = {}) {
        return this.request('DELETE', endpoint, data, options);
    },

    /**
     * Upload file
     * @param {string} endpoint
     * @param {File} file
     * @param {string} fieldName
     * @param {Object} additionalData
     * @returns {Promise<Object>}
     */
    upload(endpoint, file, fieldName = 'file', additionalData = {}) {
        const formData = new FormData();
        formData.append(fieldName, file);

        Object.entries(additionalData).forEach(([key, value]) => {
            formData.append(key, value);
        });

        return this.request('POST', endpoint, formData);
    },

    /**
     * ==========================================
     * API ENDPOINTS
     * ==========================================
     */

    /**
     * Authentication endpoints
     */
    auth: {
        login(credentials) {
            return API.post('/auth/login', credentials);
        },

        register(data) {
            return API.post('/auth/register', data);
        },

        logout() {
            return API.post('/auth/logout');
        },

        refresh(refreshToken) {
            return API.post('/auth/refresh', { refresh_token: refreshToken });
        },

        forgotPassword(email) {
            return API.post('/auth/forgot-password', { email });
        },

        resetPassword(data) {
            return API.post('/auth/reset-password', data);
        }
    },

    /**
     * User endpoints
     */
    users: {
        list(params = {}) {
            return API.get('/users', params);
        },

        get(id) {
            return API.get(`/users/${id}`);
        },

        create(data) {
            return API.post('/users', data);
        },

        update(id, data) {
            return API.put(`/users/${id}`, data);
        },

        delete(id) {
            return API.delete(`/users/${id}`);
        },

        profile() {
            return API.get('/profile');
        },

        updateProfile(data) {
            return API.put('/profile', data);
        },

        changePassword(data) {
            return API.put('/profile/password', data);
        },

        roles() {
            return API.get('/roles');
        }
    },

    /**
     * Animal endpoints
     */
    animals: {
        list(params = {}) {
            return API.get('/animals', params);
        },

        available(params = {}) {
            return API.get('/animals/available', params);
        },

        get(id) {
            return API.get(`/animals/${id}`);
        },

        create(data) {
            return API.post('/animals', data);
        },

        update(id, data) {
            return API.put(`/animals/${id}`, data);
        },

        delete(id) {
            return API.delete(`/animals/${id}`);
        },

        updateStatus(id, status) {
            return API.patch(`/animals/${id}/status`, { status });
        },

        uploadImage(id, file) {
            return API.upload(`/animals/${id}/image`, file, 'image');
        },

        statistics() {
            return API.get('/animals/stats/summary');
        },

        // Impound records
        getImpound(animalId) {
            return API.get(`/animals/${animalId}/impound`);
        },

        addImpound(animalId, data) {
            return API.post(`/animals/${animalId}/impound`, data);
        },

        updateImpound(animalId, data) {
            return API.put(`/animals/${animalId}/impound`, data);
        }
    },

    /**
     * Medical endpoints
     */
    medical: {
        list(params = {}) {
            return API.get('/medical', params);
        },

        get(id) {
            return API.get(`/medical/${id}`);
        },

        byAnimal(animalId, params = {}) {
            return API.get(`/medical/animal/${animalId}`, params);
        },

        create(data) {
            return API.post('/medical', data);
        },

        update(id, data) {
            return API.put(`/medical/${id}`, data);
        },

        delete(id) {
            return API.delete(`/medical/${id}`);
        },

        upcoming(days = 7) {
            return API.get('/medical/upcoming', { days });
        },

        overdue() {
            return API.get('/medical/overdue');
        },

        // Veterinarians
        veterinarians() {
            return API.get('/veterinarians');
        },

        veterinarian(id) {
            return API.get(`/veterinarians/${id}`);
        }
    },

    /**
     * Feeding endpoints
     */
    feeding: {
        byAnimal(animalId, params = {}) {
            return API.get(`/feeding/animal/${animalId}`, params);
        },

        today() {
            return API.get('/feeding/today');
        },

        record(data) {
            return API.post('/feeding', data);
        }
    },

    /**
     * Adoption endpoints
     */
    adoptions: {
        list(params = {}) {
            return API.get('/adoptions', params);
        },

        get(id) {
            return API.get(`/adoptions/${id}`);
        },

        create(data) {
            return API.post('/adoptions', data);
        },

        process(id, data) {
            return API.put(`/adoptions/${id}/process`, data);
        },

        cancel(id) {
            return API.put(`/adoptions/${id}/cancel`);
        },

        statistics() {
            return API.get('/adoptions/stats/summary');
        },

        animalHistory(animalId) {
            return API.get(`/adoptions/animal/${animalId}`);
        },

        userHistory(userId) {
            return API.get(`/adoptions/user/${userId}`);
        }
    },

    /**
     * Inventory endpoints
     */
    inventory: {
        list(params = {}) {
            return API.get('/inventory', params);
        },

        get(id) {
            return API.get(`/inventory/${id}`);
        },

        create(data) {
            return API.post('/inventory', data);
        },

        update(id, data) {
            return API.put(`/inventory/${id}`, data);
        },

        delete(id) {
            return API.delete(`/inventory/${id}`);
        },

        adjustStock(id, data) {
            return API.patch(`/inventory/${id}/adjust`, data);
        },

        alerts() {
            return API.get('/inventory/alerts');
        },

        lowStock() {
            return API.get('/inventory/low-stock');
        },

        expiring(days = 30) {
            return API.get('/inventory/expiring', { days });
        },

        statistics() {
            return API.get('/inventory/stats/summary');
        },

        byCategory(category, params = {}) {
            return API.get(`/inventory/category/${category}`, params);
        }
    },

    /**
     * Billing endpoints
     */
    billing: {
        // Invoices
        invoices(params = {}) {
            return API.get('/invoices', params);
        },

        invoice(id) {
            return API.get(`/invoices/${id}`);
        },

        createInvoice(data) {
            return API.post('/invoices', data);
        },

        cancelInvoice(id) {
            return API.put(`/invoices/${id}/cancel`);
        },

        invoiceStats() {
            return API.get('/invoices/stats/summary');
        },

        // Payments
        payments(params = {}) {
            return API.get('/payments', params);
        },

        payment(id) {
            return API.get(`/payments/${id}`);
        },

        recordPayment(data) {
            return API.post('/payments', data);
        },

        // Reports
        summary() {
            return API.get('/billing/summary');
        },

        report(params) {
            return API.get('/billing/report', params);
        }
    },

    /**
     * Dashboard endpoints
     */
    dashboard: {
        stats() {
            return API.get('/dashboard/stats');
        },

        quickStats() {
            return API.get('/dashboard/quick-stats');
        },

        activity(limit = 10) {
            return API.get('/dashboard/activity', { limit });
        },

        intake(period = 'year') {
            return API.get('/dashboard/intake', { period });
        }
    },

    /**
     * Notification endpoints
     */
    notifications: {
        list: () => API.request('/notifications')
    },

    /**
     * System endpoints
     */
    system: {
        health() {
            return API.get('/system/health');
        },

        info() {
            return API.get('/system/info');
        },

        logs(params = {}) {
            return API.get('/logs', params);
        },

        userLogs(userId, params = {}) {
            return API.get(`/logs/user/${userId}`, params);
        }
    }
};

/**
 * Custom API Error class
 */
class APIError extends Error {
    constructor(message, status, data = null) {
        super(message);
        this.name = 'APIError';
        this.status = status;
        this.data = data;
    }
}

// Make API globally available
window.API = API;
window.APIError = APIError;