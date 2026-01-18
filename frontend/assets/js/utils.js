/**
 * Utility Functions
 * Helper functions used throughout the application
 * 
 * @package AnimalShelter
 */

const Utils = {
    /**
     * ==========================================
     * DOM UTILITIES
     * ==========================================
     */

    /**
     * Select single element
     * @param {string} selector - CSS selector
     * @param {Element} parent - Parent element
     * @returns {Element|null}
     */
    $(selector, parent = document) {
        return parent.querySelector(selector);
    },

    /**
     * Select multiple elements
     * @param {string} selector - CSS selector
     * @param {Element} parent - Parent element
     * @returns {NodeList}
     */
    $$(selector, parent = document) {
        return parent.querySelectorAll(selector);
    },

    /**
     * Create element with attributes and children
     * @param {string} tag - HTML tag name
     * @param {Object} attrs - Attributes object
     * @param {Array|string} children - Child elements or text
     * @returns {Element}
     */
    createElement(tag, attrs = {}, children = []) {
        const element = document.createElement(tag);

        // Set attributes
        Object.entries(attrs).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'dataset') {
                Object.entries(value).forEach(([dataKey, dataValue]) => {
                    element.dataset[dataKey] = dataValue;
                });
            } else if (key === 'style' && typeof value === 'object') {
                Object.assign(element.style, value);
            } else if (key.startsWith('on') && typeof value === 'function') {
                element.addEventListener(key.substring(2).toLowerCase(), value);
            } else {
                element.setAttribute(key, value);
            }
        });

        // Add children
        if (!Array.isArray(children)) {
            children = [children];
        }

        children.forEach(child => {
            if (typeof child === 'string') {
                element.appendChild(document.createTextNode(child));
            } else if (child instanceof Element) {
                element.appendChild(child);
            }
        });

        return element;
    },

    /**
     * Parse HTML string to element
     * @param {string} html - HTML string
     * @returns {Element}
     */
    parseHTML(html) {
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstChild;
    },

    /**
     * Empty element contents
     * @param {Element} element
     */
    empty(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    },

    /**
     * Show element
     * @param {Element} element
     */
    show(element) {
        element.classList.remove('hidden');
    },

    /**
     * Hide element
     * @param {Element} element
     */
    hide(element) {
        element.classList.add('hidden');
    },

    /**
     * Toggle element visibility
     * @param {Element} element
     * @param {boolean} force
     */
    toggle(element, force) {
        element.classList.toggle('hidden', force !== undefined ? !force : undefined);
    },

    /**
     * ==========================================
     * STRING UTILITIES
     * ==========================================
     */

    /**
     * Escape HTML special characters to prevent XSS attacks
     * Use this when inserting dynamic data into innerHTML
     * @param {string} str - String to escape
     * @returns {string} - Escaped string safe for HTML insertion
     */
    escapeHTML(str) {
        if (str === null || str === undefined) return '';
        if (typeof str !== 'string') str = String(str);

        const htmlEscapes = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };

        return str.replace(/[&<>"']/g, char => htmlEscapes[char]);
    },

    /**
     * Capitalize first letter
     * @param {string} str
     * @returns {string}
     */
    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    /**
     * Title case string
     * @param {string} str
     * @returns {string}
     */
    titleCase(str) {
        if (!str) return '';
        return str.replace(/\b\w/g, char => char.toUpperCase());
    },

    /**
     * Truncate string
     * @param {string} str
     * @param {number} length
     * @param {string} suffix
     * @returns {string}
     */
    truncate(str, length = 50, suffix = '...') {
        if (!str || str.length <= length) return str;
        return str.substring(0, length).trim() + suffix;
    },

    /**
     * Slugify string
     * @param {string} str
     * @returns {string}
     */
    slugify(str) {
        return str
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },

    /**
     * Generate random string
     * @param {number} length
     * @returns {string}
     */
    randomString(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    /**
     * Generate UUID
     * @returns {string}
     */
    uuid() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    },

    /**
     * ==========================================
     * NUMBER UTILITIES
     * ==========================================
     */

    /**
     * Format number with commas
     * @param {number} num
     * @returns {string}
     */
    formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return num.toLocaleString('en-US');
    },

    /**
     * Format currency
     * @param {number} amount
     * @param {string} currency
     * @returns {string}
     */
    formatCurrency(amount, currency = 'PHP') {
        if (amount === null || amount === undefined) return '₱0.00';
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Format percentage
     * @param {number} value
     * @param {number} decimals
     * @returns {string}
     */
    formatPercent(value, decimals = 1) {
        if (value === null || value === undefined) return '0%';
        return `${value.toFixed(decimals)}%`;
    },

    /**
     * Clamp number between min and max
     * @param {number} num
     * @param {number} min
     * @param {number} max
     * @returns {number}
     */
    clamp(num, min, max) {
        return Math.min(Math.max(num, min), max);
    },

    /**
     * ==========================================
     * DATE UTILITIES
     * ==========================================
     */

    /**
     * Format date
     * @param {string|Date} date
     * @param {string} format
     * @returns {string}
     */
    formatDate(date, format = 'medium') {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        // Check for user preference if using default format
        if (format === 'medium' && window.Auth && typeof window.Auth.currentUser === 'function') {
            const user = window.Auth.currentUser();
            const pref = user?.preferences?.date_format;

            if (pref) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');

                if (pref === 'MM/DD/YYYY') return `${month}/${day}/${year}`;
                if (pref === 'DD/MM/YYYY') return `${day}/${month}/${year}`;
                if (pref === 'YYYY-MM-DD') return `${year}-${month}-${day}`;
                if (pref === 'MMM DD, YYYY') return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                if (pref === 'MMMM DD, YYYY') return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            } else {
                // Default preference
                return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }
        }

        const options = {
            short: { month: 'short', day: 'numeric' },
            medium: { year: 'numeric', month: 'short', day: 'numeric' },
            long: { year: 'numeric', month: 'long', day: 'numeric' },
            full: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
        };

        return d.toLocaleDateString('en-US', options[format] || options.medium);
    },

    /**
     * Format date and time
     * @param {string|Date} date
     * @returns {string}
     */
    formatDateTime(date) {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        return d.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    },

    /**
     * Format time
     * @param {string|Date} date
     * @returns {string}
     */
    formatTime(date) {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        return d.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    },

    /**
     * Get relative time (e.g., "2 hours ago")
     * @param {string|Date} date
     * @returns {string}
     */
    timeAgo(date) {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        const seconds = Math.floor((new Date() - d) / 1000);

        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'week', seconds: 604800 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 },
            { label: 'second', seconds: 1 }
        ];

        for (const interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count >= 1) {
                return `${count} ${interval.label}${count !== 1 ? 's' : ''} ago`;
            }
        }

        return 'just now';
    },

    /**
     * Check if date is today
     * @param {string|Date} date
     * @returns {boolean}
     */
    isToday(date) {
        const d = new Date(date);
        const today = new Date();
        return d.toDateString() === today.toDateString();
    },

    /**
     * Get date for input[type="date"]
     * @param {string|Date} date
     * @returns {string}
     */
    toInputDate(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        return d.toISOString().split('T')[0];
    },

    /**
     * Get datetime for input[type="datetime-local"]
     * @param {string|Date} date
     * @returns {string}
     */
    toInputDateTime(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        // Adjust for timezone offset to keep local time
        const offset = d.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(d.getTime() - offset)).toISOString().slice(0, 16);
        return localISOTime;
    },

    /**
     * ==========================================
     * OBJECT UTILITIES
     * ==========================================
     */

    /**
     * Deep clone object
     * @param {Object} obj
     * @returns {Object}
     */
    deepClone(obj) {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj);
        if (obj instanceof Array) return obj.map(item => this.deepClone(item));
        if (obj instanceof Object) {
            const copy = {};
            Object.keys(obj).forEach(key => {
                copy[key] = this.deepClone(obj[key]);
            });
            return copy;
        }
        return obj;
    },

    /**
     * Check if object is empty
     * @param {Object} obj
     * @returns {boolean}
     */
    isEmpty(obj) {
        if (!obj) return true;
        return Object.keys(obj).length === 0;
    },

    /**
     * Pick specific keys from object
     * @param {Object} obj
     * @param {Array} keys
     * @returns {Object}
     */
    pick(obj, keys) {
        return keys.reduce((acc, key) => {
            if (obj.hasOwnProperty(key)) {
                acc[key] = obj[key];
            }
            return acc;
        }, {});
    },

    /**
     * Omit specific keys from object
     * @param {Object} obj
     * @param {Array} keys
     * @returns {Object}
     */
    omit(obj, keys) {
        const result = { ...obj };
        keys.forEach(key => delete result[key]);
        return result;
    },

    /**
     * Get nested property value
     * @param {Object} obj
     * @param {string} path
     * @param {*} defaultValue
     * @returns {*}
     */
    get(obj, path, defaultValue = undefined) {
        const keys = path.split('.');
        let result = obj;

        for (const key of keys) {
            if (result === null || result === undefined) {
                return defaultValue;
            }
            result = result[key];
        }

        return result !== undefined ? result : defaultValue;
    },

    /**
     * ==========================================
     * ARRAY UTILITIES
     * ==========================================
     */

    /**
     * Group array by key
     * @param {Array} array
     * @param {string|Function} key
     * @returns {Object}
     */
    groupBy(array, key) {
        return array.reduce((groups, item) => {
            const groupKey = typeof key === 'function' ? key(item) : item[key];
            (groups[groupKey] = groups[groupKey] || []).push(item);
            return groups;
        }, {});
    },

    /**
     * Sort array by key
     * @param {Array} array
     * @param {string} key
     * @param {string} order
     * @returns {Array}
     */
    sortBy(array, key, order = 'asc') {
        return [...array].sort((a, b) => {
            let aVal = a[key];
            let bVal = b[key];

            if (typeof aVal === 'string') aVal = aVal.toLowerCase();
            if (typeof bVal === 'string') bVal = bVal.toLowerCase();

            if (aVal < bVal) return order === 'asc' ? -1 : 1;
            if (aVal > bVal) return order === 'asc' ? 1 : -1;
            return 0;
        });
    },

    /**
     * Remove duplicates from array
     * @param {Array} array
     * @param {string} key
     * @returns {Array}
     */
    unique(array, key) {
        if (key) {
            const seen = new Set();
            return array.filter(item => {
                const val = item[key];
                if (seen.has(val)) return false;
                seen.add(val);
                return true;
            });
        }
        return [...new Set(array)];
    },

    /**
     * Chunk array into smaller arrays
     * @param {Array} array
     * @param {number} size
     * @returns {Array}
     */
    chunk(array, size) {
        const chunks = [];
        for (let i = 0; i < array.length; i += size) {
            chunks.push(array.slice(i, i + size));
        }
        return chunks;
    },

    /**
     * ==========================================
     * VALIDATION UTILITIES
     * ==========================================
     */

    /**
     * Validate email
     * @param {string} email
     * @returns {boolean}
     */
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    /**
     * Validate phone (Philippine format)
     * @param {string} phone
     * @returns {boolean}
     */
    isValidPhone(phone) {
        if (!phone || phone.trim() === '') return true; // Optional field
        const regex = /^(09|\+639)\d{9}$/;
        return regex.test(phone.replace(/[\s-]/g, ''));
    },

    /**
     * Validate password strength
     * @param {string} password
     * @returns {Object}
     */
    validatePassword(password) {
        const result = {
            isValid: false,
            strength: 0,
            message: ''
        };

        if (!password) {
            result.message = 'Password is required';
            return result;
        }

        if (password.length < 6) {
            result.message = 'Password must be at least 6 characters';
            return result;
        }

        // Calculate strength
        if (password.length >= 6) result.strength++;
        if (password.length >= 10) result.strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) result.strength++;
        if (/\d/.test(password)) result.strength++;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) result.strength++;

        result.isValid = result.strength >= 1;

        const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
        result.message = strengthLabels[result.strength - 1] || 'Very Weak';

        return result;
    },

    /**
     * Validate username
     * @param {string} username
     * @returns {Object} { isValid, message }
     */
    validateUsername(username) {
        if (!username || username.trim() === '') {
            return { isValid: false, message: 'Username is required' };
        }
        if (username.length < 3) {
            return { isValid: false, message: 'Username must be at least 3 characters' };
        }
        if (username.length > 50) {
            return { isValid: false, message: 'Username must not exceed 50 characters' };
        }
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            return { isValid: false, message: 'Username can only contain letters, numbers, and underscores' };
        }
        return { isValid: true, message: '' };
    },

    /**
     * Validate name (first name, last name)
     * @param {string} name
     * @param {string} fieldName
     * @returns {Object} { isValid, message }
     */
    validateName(name, fieldName = 'Name') {
        if (!name || name.trim() === '') {
            return { isValid: false, message: `${fieldName} is required` };
        }
        if (name.length < 2) {
            return { isValid: false, message: `${fieldName} must be at least 2 characters` };
        }
        if (name.length > 50) {
            return { isValid: false, message: `${fieldName} must not exceed 50 characters` };
        }
        if (!/^[a-zA-Z\s\-']+$/.test(name)) {
            return { isValid: false, message: `${fieldName} can only contain letters, spaces, hyphens, and apostrophes` };
        }
        return { isValid: true, message: '' };
    },

    /**
     * Validate email with detailed message
     * @param {string} email
     * @returns {Object} { isValid, message }
     */
    validateEmail(email) {
        if (!email || email.trim() === '') {
            return { isValid: false, message: 'Email is required' };
        }
        if (!this.isValidEmail(email)) {
            return { isValid: false, message: 'Please enter a valid email address' };
        }
        if (email.length > 100) {
            return { isValid: false, message: 'Email must not exceed 100 characters' };
        }
        return { isValid: true, message: '' };
    },

    /**
     * Validate contact number with detailed message
     * @param {string} phone
     * @returns {Object} { isValid, message }
     */
    validatePhone(phone) {
        if (!phone || phone.trim() === '') {
            return { isValid: true, message: '' }; // Optional field
        }
        if (!this.isValidPhone(phone)) {
            return { isValid: false, message: 'Please enter a valid Philippine phone number (e.g., 09171234567)' };
        }
        return { isValid: true, message: '' };
    },

    /**
     * Validate required field
     * @param {string} value
     * @param {string} fieldName
     * @returns {Object} { isValid, message }
     */
    validateRequired(value, fieldName = 'This field') {
        if (value === null || value === undefined || (typeof value === 'string' && value.trim() === '')) {
            return { isValid: false, message: `${fieldName} is required` };
        }
        return { isValid: true, message: '' };
    },

    /**
     * Validate number range
     * @param {number} value
     * @param {Object} options - { min, max, fieldName }
     * @returns {Object} { isValid, message }
     */
    validateNumber(value, options = {}) {
        const { min = null, max = null, fieldName = 'Value' } = options;

        if (value === null || value === undefined || value === '') {
            return { isValid: true, message: '' }; // Let required validation handle empty
        }

        const num = parseFloat(value);
        if (isNaN(num)) {
            return { isValid: false, message: `${fieldName} must be a valid number` };
        }
        if (min !== null && num < min) {
            return { isValid: false, message: `${fieldName} must be at least ${min}` };
        }
        if (max !== null && num > max) {
            return { isValid: false, message: `${fieldName} must not exceed ${max}` };
        }
        return { isValid: true, message: '' };
    },

    /**
     * Validate form data with rules
     * @param {Object} data - Form data object
     * @param {Object} rules - Validation rules { fieldName: [rules] }
     * @returns {Object} { isValid, errors }
     */
    validateForm(data, rules) {
        const errors = {};
        let isValid = true;

        for (const [field, fieldRules] of Object.entries(rules)) {
            const value = data[field];

            for (const rule of fieldRules) {
                let result = { isValid: true, message: '' };

                if (typeof rule === 'string') {
                    // Built-in rules
                    switch (rule) {
                        case 'required':
                            result = this.validateRequired(value, this.formatFieldLabel(field));
                            break;
                        case 'email':
                            result = this.validateEmail(value);
                            break;
                        case 'phone':
                            result = this.validatePhone(value);
                            break;
                        case 'username':
                            result = this.validateUsername(value);
                            break;
                    }
                } else if (typeof rule === 'object') {
                    // Custom rules with options
                    if (rule.type === 'min' && value) {
                        if (value.length < rule.value) {
                            result = { isValid: false, message: `${this.formatFieldLabel(field)} must be at least ${rule.value} characters` };
                        }
                    } else if (rule.type === 'max' && value) {
                        if (value.length > rule.value) {
                            result = { isValid: false, message: `${this.formatFieldLabel(field)} must not exceed ${rule.value} characters` };
                        }
                    } else if (rule.type === 'number') {
                        result = this.validateNumber(value, { ...rule, fieldName: this.formatFieldLabel(field) });
                    } else if (rule.type === 'match') {
                        if (value !== data[rule.field]) {
                            result = { isValid: false, message: rule.message || 'Values do not match' };
                        }
                    } else if (rule.type === 'password') {
                        result = this.validatePassword(value);
                    } else if (rule.type === 'name') {
                        result = this.validateName(value, this.formatFieldLabel(field));
                    }
                }

                if (!result.isValid) {
                    errors[field] = result.message;
                    isValid = false;
                    break; // Stop at first error for this field
                }
            }
        }

        return { isValid, errors };
    },

    /**
     * Format field name to label
     * @param {string} field
     * @returns {string}
     */
    formatFieldLabel(field) {
        return field
            .replace(/_/g, ' ')
            .replace(/([A-Z])/g, ' $1')
            .trim()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    },

    /**
     * Show form validation errors
     * @param {Object} errors - { fieldName: errorMessage }
     */
    showFormErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.form-error').forEach(el => el.remove());
        document.querySelectorAll('.form-input.error, .form-select.error, .form-textarea.error').forEach(el => {
            el.classList.remove('error');
        });

        // Show new errors
        for (const [field, message] of Object.entries(errors)) {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const errorEl = document.createElement('span');
                errorEl.className = 'form-error';
                errorEl.textContent = message;
                input.parentNode.appendChild(errorEl);
            }
        }

        // Show first error as toast
        const firstError = Object.values(errors)[0];
        if (firstError) {
            Toast.error(firstError);
        }
    },

    /**
     * Clear form validation errors
     */
    clearFormErrors() {
        document.querySelectorAll('.form-error').forEach(el => el.remove());
        document.querySelectorAll('.form-input.error, .form-select.error, .form-textarea.error').forEach(el => {
            el.classList.remove('error');
        });
    },

    /**
     * ==========================================
     * URL UTILITIES
     * ==========================================
     */

    /**
     * Get query parameters from URL
     * @param {string} url
     * @returns {Object}
     */
    getQueryParams(url = window.location.href) {
        const params = {};
        const searchParams = new URL(url).searchParams;
        searchParams.forEach((value, key) => {
            params[key] = value;
        });
        return params;
    },

    /**
     * Build URL with query parameters
     * @param {string} baseUrl
     * @param {Object} params
     * @returns {string}
     */
    buildUrl(baseUrl, params = {}) {
        const url = new URL(baseUrl, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.append(key, value);
            }
        });
        return url.toString();
    },

    /**
     * ==========================================
     * STORAGE UTILITIES
     * ==========================================
     */

    /**
     * Get item from sessionStorage (more secure - cleared when browser closes)
     * This prevents tokens from persisting and reduces XSS attack window
     * @param {string} key
     * @param {*} defaultValue
     * @returns {*}
     */
    getStorage(key, defaultValue = null) {
        try {
            const item = sessionStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('Error reading from sessionStorage:', e);
            return defaultValue;
        }
    },

    /**
     * Set item in sessionStorage (more secure - cleared when browser closes)
     * @param {string} key
     * @param {*} value
     */
    setStorage(key, value) {
        try {
            sessionStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Error writing to sessionStorage:', e);
        }
    },

    /**
     * Remove item from sessionStorage
     * @param {string} key
     */
    removeStorage(key) {
        try {
            sessionStorage.removeItem(key);
        } catch (e) {
            console.error('Error removing from sessionStorage:', e);
        }
    },

    /**
     * ==========================================
     * ASYNC UTILITIES
     * ==========================================
     */

    /**
     * Retry a function with exponential backoff
     * @param {Function} fn - Async function to retry
     * @param {Object} options - Retry options
     * @param {number} options.maxRetries - Maximum number of retries (default: 3)
     * @param {number} options.baseDelay - Base delay in ms (default: 1000)
     * @param {number} options.maxDelay - Maximum delay in ms (default: 10000)
     * @param {Function} options.shouldRetry - Function to determine if should retry (default: network errors only)
     * @param {Function} options.onRetry - Callback on each retry attempt
     * @returns {Promise<*>}
     */
    async retry(fn, options = {}) {
        const {
            maxRetries = 3,
            baseDelay = 1000,
            maxDelay = 10000,
            shouldRetry = (error) => {
                // Retry on network errors, timeouts, and 5xx server errors
                if (error instanceof APIError) {
                    return error.status === 0 || error.status === 408 || error.status >= 500;
                }
                return error.name === 'TypeError' || error.message.includes('network');
            },
            onRetry = null
        } = options;

        let lastError;

        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                return await fn();
            } catch (error) {
                lastError = error;

                // Don't retry if max retries reached or shouldn't retry this error
                if (attempt >= maxRetries || !shouldRetry(error)) {
                    throw error;
                }

                // Calculate delay with exponential backoff and jitter
                const delay = Math.min(
                    baseDelay * Math.pow(2, attempt) + Math.random() * 1000,
                    maxDelay
                );

                // Call retry callback if provided
                if (onRetry) {
                    onRetry(error, attempt + 1, delay);
                }

                await this.sleep(delay);
            }
        }

        throw lastError;
    },

    /**
     * Wrap an async function with error handling
     * Returns [error, result] tuple similar to Go-style error handling
     * @param {Promise} promise - Promise to wrap
     * @returns {Promise<[Error|null, *]>}
     */
    async safeAwait(promise) {
        try {
            const result = await promise;
            return [null, result];
        } catch (error) {
            return [error, null];
        }
    },

    /**
     * Execute a function with loading state management
     * @param {Function} fn - Async function to execute
     * @param {Object} options - Options
     * @param {string} options.loadingMessage - Message to show while loading
     * @param {string} options.successMessage - Message to show on success
     * @param {string} options.errorMessage - Custom error message (uses API error if not provided)
     * @param {boolean} options.showLoading - Whether to show loading toast (default: false)
     * @param {boolean} options.showSuccess - Whether to show success toast (default: true)
     * @param {boolean} options.showError - Whether to show error toast (default: true)
     * @returns {Promise<{success: boolean, data: *, error: Error|null}>}
     */
    async withLoadingState(fn, options = {}) {
        const {
            loadingMessage = 'Loading...',
            successMessage = null,
            errorMessage = null,
            showLoading = false,
            showSuccess = true,
            showError = true
        } = options;

        let loadingToast = null;

        try {
            if (showLoading && loadingMessage) {
                loadingToast = Toast.info(loadingMessage);
            }

            const data = await fn();

            if (loadingToast) {
                Toast.dismiss(loadingToast);
            }

            if (showSuccess && successMessage) {
                Toast.success(successMessage);
            }

            return { success: true, data, error: null };
        } catch (error) {
            if (loadingToast) {
                Toast.dismiss(loadingToast);
            }

            if (showError) {
                const message = errorMessage || error.message || 'An error occurred';
                Toast.error(message);
            }

            return { success: false, data: null, error };
        }
    },

    /**
     * Debounce function
     * @param {Function} func
     * @param {number} wait
     * @returns {Function}
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     * @param {Function} func
     * @param {number} limit
     * @returns {Function}
     */
    throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Sleep/delay
     * @param {number} ms
     * @returns {Promise}
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Wait alias for sleep
     * @param {number} ms
     * @returns {Promise}
     */
    wait(ms) {
        return this.sleep(ms);
    },

    /**
     * ==========================================
     * FILE UTILITIES
     * ==========================================
     */

    /**
     * Format file size
     * @param {number} bytes
     * @returns {string}
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Get file extension
     * @param {string} filename
     * @returns {string}
     */
    getFileExtension(filename) {
        return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2).toLowerCase();
    },

    /**
     * Check if file is image
     * @param {string} filename
     * @returns {boolean}
     */
    isImage(filename) {
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        return imageExtensions.includes(this.getFileExtension(filename));
    },

    /**
     * Convert file to base64
     * @param {File} file
     * @returns {Promise<string>}
     */
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    },

    /**
     * ==========================================
     * COLOR UTILITIES
     * ==========================================
     */

    /**
     * Generate color from string (for avatars)
     * @param {string} str
     * @returns {string}
     */
    stringToColor(str) {
        if (!str) return '#1DB954';

        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }

        const colors = [
            '#FF3B30', '#FF9500', '#FFCC00', '#34C759', '#00C7BE',
            '#30B0C7', '#32ADE6', '#1DB954', '#5856D6', '#AF52DE',
            '#FF2D55', '#A2845E'
        ];

        return colors[Math.abs(hash) % colors.length];
    },

    /**
     * Generate gradient from string (for enhanced avatars)
     * @param {string} str
     * @returns {string}
     */
    stringToGradient(str) {
        if (!str) return 'linear-gradient(135deg, #1DB954, #5856D6)';

        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }

        const gradients = [
            'linear-gradient(135deg, #FF3B30, #FF9500)',
            'linear-gradient(135deg, #FF9500, #FFCC00)',
            'linear-gradient(135deg, #34C759, #00C7BE)',
            'linear-gradient(135deg, #00C7BE, #30B0C7)',
            'linear-gradient(135deg, #32ADE6, #1DB954)',
            'linear-gradient(135deg, #1DB954, #5856D6)',
            'linear-gradient(135deg, #5856D6, #AF52DE)',
            'linear-gradient(135deg, #AF52DE, #FF2D55)',
            'linear-gradient(135deg, #FF2D55, #FF3B30)',
            'linear-gradient(135deg, #A2845E, #8E8E93)',
            'linear-gradient(135deg, #30B0C7, #5856D6)',
            'linear-gradient(135deg, #34C759, #1DB954)'
        ];

        return gradients[Math.abs(hash) % gradients.length];
    },

    /**
     * Get initials from name
     * @param {string} name
     * @returns {string}
     */
    getInitials(name) {
        if (!name) return '';

        const parts = name.trim().split(' ');
        if (parts.length === 1) {
            return parts[0].charAt(0).toUpperCase();
        }

        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    },

    /**
     * ==========================================
     * MISC UTILITIES
     * ==========================================
     */

    /**
     * Copy text to clipboard
     * @param {string} text
     * @returns {Promise<boolean>}
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                return true;
            } catch (e) {
                return false;
            } finally {
                document.body.removeChild(textarea);
            }
        }
    },

    /**
     * Download data as file
     * @param {string} data
     * @param {string} filename
     * @param {string} type
     */
    downloadFile(data, filename, type = 'text/plain') {
        const blob = new Blob([data], { type });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);
    },

    /**
     * Check if device is mobile
     * @returns {boolean}
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },

    /**
     * Check if device is touch
     * @returns {boolean}
     */
    isTouch() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    },

    /**
     * Escape HTML entities
     * @param {string} str
     * @returns {string}
     */
    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Generate status badge class
     * @param {string} status
     * @returns {string}
     */
    getStatusBadgeClass(status) {
        const statusMap = {
            // Animal statuses
            'Available': 'badge-success',
            'Reserved': 'badge-teal',
            'Adopted': 'badge-info',
            'In Treatment': 'badge-warning',
            'Quarantine': 'badge-danger',
            'Deceased': 'badge-gray',
            'Reclaimed': 'badge-secondary',

            // Adoption statuses
            'Pending': 'badge-warning',
            'Interview Scheduled': 'badge-info',
            'Seminar Scheduled': 'badge-purple',
            'Approved': 'badge-success',
            'Rejected': 'badge-danger',
            'Completed': 'badge-teal',
            'Cancelled': 'badge-orange',

            // Invoice statuses
            'Paid': 'badge-success',
            'Unpaid': 'badge-warning',

            // User statuses
            'Active': 'badge-success',
            'Inactive': 'badge-warning',
            'Banned': 'badge-danger'
        };

        return statusMap[status] || 'badge-gray';
    },

    /**
     * Announce message for screen readers
     * @param {string} message - Message to announce
     * @param {string} priority - 'polite' or 'assertive'
     */
    announce(message, priority = 'polite') {
        const announcer = document.getElementById('announcements');
        if (!announcer) return;

        announcer.setAttribute('aria-live', priority);
        announcer.textContent = message;

        // Clear after announcement
        setTimeout(() => {
            announcer.textContent = '';
        }, 1000);
    },

    /**
     * Create filter chips HTML
     * @param {Object} filters - Active filters object
     * @param {Function} onRemove - Callback when filter is removed
     * @returns {string}
     */
    renderFilterChips(filters, containerId = 'filter-chips') {
        const entries = Object.entries(filters).filter(([key, value]) => value && value !== '' && value !== 'all');

        if (entries.length === 0) return '';

        const chips = entries.map(([key, value]) => `
            <span class="filter-chip" data-filter="${key}">
                <span class="filter-chip-label">${this.formatFilterLabel(key)}: ${value}</span>
                <button class="filter-chip-remove" onclick="Utils.removeFilterChip('${containerId}', '${key}')" aria-label="Remove ${key} filter">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </span>
        `).join('');

        return `
            <div class="filter-chips-container" id="${containerId}">
                ${chips}
                <button class="btn btn-sm btn-ghost clear-all-filters" onclick="Utils.clearAllFilters('${containerId}')">
                    Clear all
                </button>
            </div>
        `;
    },

    /**
     * Format filter key to label
     * @param {string} key
     * @returns {string}
     */
    formatFilterLabel(key) {
        return key
            .replace(/_/g, ' ')
            .replace(/([A-Z])/g, ' $1')
            .trim()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    },

    /**
     * Remove a single filter chip
     * @param {string} containerId
     * @param {string} filterKey
     */
    removeFilterChip(containerId, filterKey) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Dispatch custom event for page to handle
        container.dispatchEvent(new CustomEvent('filterRemoved', {
            detail: { key: filterKey },
            bubbles: true
        }));
    },

    /**
     * Clear all filter chips
     * @param {string} containerId
     */
    clearAllFilters(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Dispatch custom event for page to handle
        container.dispatchEvent(new CustomEvent('filtersCleared', {
            bubbles: true
        }));
    },

    /**
     * Get placeholder image for animal based on type
     * @param {string} type - Animal type (Dog, Cat, Other)
     * @returns {string} - Path to placeholder image
     */
    getAnimalPlaceholder(type) {
        const typeMap = {
            'Dog': '/assets/images/placeholder-dog.png',
            'Cat': '/assets/images/placeholder-cat.png',
            'Other': '/assets/images/placeholder-other.png'
        };
        return typeMap[type] || '/assets/images/placeholder-other.png';
    },

    /**
     * Get badge class for animal type
     * @param {string} type - Animal type (Dog, Cat, Other)
     * @returns {string} - Badge class
     */
    getAnimalTypeBadgeClass(type) {
        const typeMap = {
            'Dog': 'badge-info',
            'Cat': 'badge-purple',
            'Other': 'badge-orange'
        };
        return typeMap[type] || 'badge-gray';
    }
};

/**
 * ErrorBoundary - Catches and handles rendering errors gracefully
 * Provides fallback UI and error recovery options
 * 
 * @package AnimalShelter
 */
const ErrorBoundary = {
    /**
     * Wrap a render function with error handling
     * @param {Function} renderFn - Function that returns HTML string
     * @param {Object} options - Options
     * @param {string} options.fallback - Fallback HTML on error
     * @param {Function} options.onError - Error callback
     * @param {boolean} options.showRetry - Show retry button (default: true)
     * @returns {string} - HTML string
     */
    wrap(renderFn, options = {}) {
        const {
            fallback = null,
            onError = null,
            showRetry = true
        } = options;

        try {
            return renderFn();
        } catch (error) {
            console.error('Render error:', error);

            if (onError) {
                onError(error);
            }

            if (fallback) {
                return fallback;
            }

            return this.renderErrorState(error, showRetry);
        }
    },

    /**
     * Wrap an async render/load function with error handling
     * @param {Function} asyncFn - Async function to execute
     * @param {HTMLElement} container - Container to render into
     * @param {Object} options - Options
     * @param {string} options.loadingHTML - HTML to show while loading
     * @param {Function} options.onError - Error callback
     * @param {boolean} options.showRetry - Show retry button (default: true)
     * @returns {Promise<boolean>} - Success status
     */
    async wrapAsync(asyncFn, container, options = {}) {
        const {
            loadingHTML = Skeleton.page(),
            onError = null,
            showRetry = true,
            retryFn = null
        } = options;

        try {
            // Show loading state
            if (loadingHTML) {
                container.innerHTML = loadingHTML;
            }

            // Execute the async function
            await asyncFn();
            return true;
        } catch (error) {
            console.error('Async render error:', error);

            if (onError) {
                onError(error);
            }

            // Show error state
            container.innerHTML = this.renderErrorState(error, showRetry, retryFn);

            // Attach retry handler if provided
            if (showRetry && retryFn) {
                const retryBtn = container.querySelector('[data-error-retry]');
                if (retryBtn) {
                    retryBtn.addEventListener('click', async () => {
                        await this.wrapAsync(asyncFn, container, options);
                    });
                }
            }

            return false;
        }
    },

    /**
     * Render error state UI
     * @param {Error} error - The error that occurred
     * @param {boolean} showRetry - Whether to show retry button
     * @param {Function} retryFn - Retry function
     * @returns {string} - HTML string
     */
    renderErrorState(error, showRetry = true, retryFn = null) {
        const isNetworkError = error instanceof APIError && (error.status === 0 || error.status === 408);
        const isServerError = error instanceof APIError && error.status >= 500;

        let title = 'Something went wrong';
        let message = error.message || 'An unexpected error occurred.';
        let icon = 'alert-circle';

        if (isNetworkError) {
            title = 'Connection Error';
            message = 'Unable to connect to the server. Please check your internet connection.';
            icon = 'wifi-off';
        } else if (isServerError) {
            title = 'Server Error';
            message = 'The server encountered an error. Please try again later.';
            icon = 'server';
        }

        return `
            <div class="error-boundary">
                <div class="error-boundary-content">
                    <div class="error-boundary-icon">
                        ${this.getIcon(icon)}
                    </div>
                    <h3 class="error-boundary-title">${title}</h3>
                    <p class="error-boundary-message">${Utils.escapeHTML(message)}</p>
                    ${showRetry ? `
                        <button class="btn btn-primary" data-error-retry>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                            Try Again
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    },

    /**
     * Get icon SVG for error states
     * @param {string} name - Icon name
     * @returns {string} - SVG string
     */
    getIcon(name) {
        const icons = {
            'alert-circle': '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
            'wifi-off': '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path><path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>',
            'server': '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>'
        };
        return icons[name] || icons['alert-circle'];
    }
};

/**
 * Skeleton - Loading placeholder utilities
 * Creates skeleton loading states for better UX
 * 
 * @package AnimalShelter
 */
const Skeleton = {
    /**
     * Create a skeleton line
     * @param {string} width - Width (e.g., '100%', '200px')
     * @param {string} height - Height (default: '1rem')
     * @returns {string}
     */
    line(width = '100%', height = '1rem') {
        return `<div class="skeleton skeleton-line" style="width: ${width}; height: ${height};"></div>`;
    },

    /**
     * Create a skeleton circle (for avatars)
     * @param {string} size - Size (default: '40px')
     * @returns {string}
     */
    circle(size = '40px') {
        return `<div class="skeleton skeleton-circle" style="width: ${size}; height: ${size};"></div>`;
    },

    /**
     * Create a skeleton rectangle (for images, cards)
     * @param {string} width - Width
     * @param {string} height - Height
     * @returns {string}
     */
    rect(width = '100%', height = '200px') {
        return `<div class="skeleton skeleton-rect" style="width: ${width}; height: ${height};"></div>`;
    },

    /**
     * Create a skeleton card
     * @returns {string}
     */
    card() {
        return `
            <div class="skeleton-card">
                ${this.rect('100%', '150px')}
                <div class="skeleton-card-content">
                    ${this.line('70%')}
                    ${this.line('90%')}
                    ${this.line('50%')}
                </div>
            </div>
        `;
    },

    /**
     * Create skeleton table rows
     * @param {number} rows - Number of rows
     * @param {number} cols - Number of columns
     * @returns {string}
     */
    table(rows = 5, cols = 5) {
        let html = '<div class="skeleton-table">';

        // Header
        html += '<div class="skeleton-table-header">';
        for (let c = 0; c < cols; c++) {
            html += `<div class="skeleton-table-cell">${this.line('80%', '0.875rem')}</div>`;
        }
        html += '</div>';

        // Rows
        for (let r = 0; r < rows; r++) {
            html += '<div class="skeleton-table-row">';
            for (let c = 0; c < cols; c++) {
                const width = c === 0 ? '60%' : (c === cols - 1 ? '40%' : '70%');
                html += `<div class="skeleton-table-cell">${this.line(width)}</div>`;
            }
            html += '</div>';
        }

        html += '</div>';
        return html;
    },

    /**
     * Create skeleton list items
     * @param {number} count - Number of items
     * @param {boolean} withAvatar - Include avatar
     * @returns {string}
     */
    list(count = 5, withAvatar = false) {
        let html = '<div class="skeleton-list">';

        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-list-item">
                    ${withAvatar ? this.circle('40px') : ''}
                    <div class="skeleton-list-content">
                        ${this.line('60%')}
                        ${this.line('80%', '0.75rem')}
                    </div>
                </div>
            `;
        }

        html += '</div>';
        return html;
    },

    /**
     * Create skeleton stat cards
     * @param {number} count - Number of stat cards
     * @returns {string}
     */
    stats(count = 4) {
        let html = '<div class="skeleton-stats">';

        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-stat-card">
                    <div class="skeleton-stat-header">
                        ${this.circle('32px')}
                        ${this.line('60%', '0.75rem')}
                    </div>
                    ${this.line('40%', '2rem')}
                    ${this.line('70%', '0.625rem')}
                </div>
            `;
        }

        html += '</div>';
        return html;
    },

    /**
     * Create a full page skeleton
     * @returns {string}
     */
    page() {
        return `
            <div class="skeleton-page">
                <div class="skeleton-page-header">
                    ${this.line('200px', '1.5rem')}
                    ${this.line('300px', '1rem')}
                </div>
                ${this.stats(4)}
                <div class="skeleton-page-content">
                    ${this.table(8, 6)}
                </div>
            </div>
        `;
    },

    /**
     * Create skeleton for animal grid
     * @param {number} count - Number of cards
     * @returns {string}
     */
    animalGrid(count = 8) {
        let html = '<div class="skeleton-animal-grid">';

        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-animal-card">
                    ${this.rect('100%', '180px')}
                    <div class="skeleton-animal-content">
                        ${this.line('70%', '1.25rem')}
                        <div class="skeleton-animal-meta">
                            ${this.line('40%', '0.75rem')}
                            ${this.line('30%', '0.75rem')}
                        </div>
                        ${this.line('50%', '1.5rem')}
                    </div>
                </div>
            `;
        }

        html += '</div>';
        return html;
    },

    /**
     * Create skeleton for form
     * @param {number} fields - Number of fields
     * @returns {string}
     */
    form(fields = 4) {
        let html = '<div class="skeleton-form">';

        for (let i = 0; i < fields; i++) {
            html += `
                <div class="skeleton-form-field">
                    ${this.line('30%', '0.875rem')}
                    ${this.line('100%', '2.5rem')}
                </div>
            `;
        }

        html += `
            <div class="skeleton-form-actions">
                ${this.line('100px', '2.5rem')}
                ${this.line('80px', '2.5rem')}
            </div>
        `;

        html += '</div>';
        return html;
    }
};

// Make ErrorBoundary globally available
window.ErrorBoundary = ErrorBoundary;

// Make Skeleton globally available
window.Skeleton = Skeleton;

/**
 * SSE (Server-Sent Events) Client
 * Handles real-time updates from the server
 *
 * @package AnimalShelter
 */
const SSE = {
    /**
     * EventSource instance
     */
    connection: null,

    /**
     * Event handlers
     */
    handlers: {},

    /**
     * Connection state
     */
    state: {
        connected: false,
        reconnecting: false,
        retryCount: 0,
        lastCheck: null
    },

    /**
     * Reconnect timeout ID (for cleanup)
     */
    _reconnectTimeout: null,

    /**
     * Configuration
     */
    config: {
        // SSE runs on separate port to avoid blocking main API (PHP dev server is single-threaded)
        baseUrl: 'http://localhost:8001',
        maxRetries: 5,
        retryDelay: 3000, // 3 seconds
        maxRetryDelay: 30000 // 30 seconds max
    },

    /**
     * Connect to SSE endpoint
     */
    connect() {
        // Don't connect if not authenticated
        if (!Auth.isAuthenticated()) {
            return;
        }

        // Don't reconnect if already connected
        if (this.connection && this.state.connected) {
            return;
        }

        try {
            const url = new URL(this.config.baseUrl);
            if (this.state.lastCheck) {
                url.searchParams.set('last_check', this.state.lastCheck);
            }

            // Add auth token as query param since EventSource doesn't support headers
            const token = Auth.getToken();
            if (token) {
                url.searchParams.set('token', token);
            }

            this.connection = new EventSource(url.toString(), { withCredentials: true });

            // Connection opened
            this.connection.addEventListener('open', () => {
                this.state.connected = true;
                this.state.reconnecting = false;
                this.state.retryCount = 0;
                this.trigger('connected', { time: new Date() });
            });

            // Handle connected event from server
            this.connection.addEventListener('connected', (e) => {
                const data = JSON.parse(e.data);
                this.state.lastCheck = new Date().toISOString();
            });

            // Handle heartbeat
            this.connection.addEventListener('heartbeat', (e) => {
                // Keep-alive, no action needed
            });

            // Handle reconnect instruction
            this.connection.addEventListener('reconnect', (e) => {
                const data = JSON.parse(e.data);
                this.state.lastCheck = data.last_check;
                this.reconnect();
            });

            // Handle data update events
            const dataEvents = [
                'animals_updated',
                'adoptions_updated',
                'inventory_updated',
                'medical_updated',
                'billing_updated'
            ];

            dataEvents.forEach(eventType => {
                this.connection.addEventListener(eventType, (e) => {
                    const data = JSON.parse(e.data);
                    this.handleDataUpdate(eventType, data);
                });
            });

            // Handle errors
            this.connection.addEventListener('error', (e) => {
                this.state.connected = false;

                if (this.connection.readyState === EventSource.CLOSED) {
                    this.reconnect();
                }
            });

        } catch (error) {
            console.error('SSE connection error:', error);
            this.reconnect();
        }
    },

    /**
     * Disconnect from SSE
     */
    disconnect() {
        // Clear any pending reconnect timeout
        if (this._reconnectTimeout) {
            clearTimeout(this._reconnectTimeout);
            this._reconnectTimeout = null;
        }

        if (this.connection) {
            this.connection.close();
            this.connection = null;
        }
        this.state.connected = false;
        this.state.reconnecting = false;
    },

    /**
     * Full cleanup - disconnect and clear all handlers
     * Call this on logout
     */
    cleanup() {
        this.disconnect();
        this.handlers = {};
        this.pendingUpdates = {};
        this.state.retryCount = 0;
        this.state.lastCheck = null;
        this.hideUpdateIndicator();
    },

    /**
     * Reconnect with exponential backoff
     */
    reconnect() {
        if (this.state.reconnecting) return;
        if (this.state.retryCount >= this.config.maxRetries) {
            console.warn('SSE max retries reached, stopping reconnection');
            return;
        }

        this.state.reconnecting = true;
        this.disconnect();

        // Exponential backoff
        const delay = Math.min(
            this.config.retryDelay * Math.pow(2, this.state.retryCount),
            this.config.maxRetryDelay
        );

        this.state.retryCount++;

        // Store timeout ID for cleanup
        this._reconnectTimeout = setTimeout(() => {
            this._reconnectTimeout = null;
            this.state.reconnecting = false;
            this.connect();
        }, delay);
    },

    /**
     * Handle data update from server
     * 
     * @param {string} eventType
     * @param {object} data
     */
    handleDataUpdate(eventType, data) {
        // Check if user is busy (modal open, typing, etc.)
        if (this.isUserBusy()) {
            // Queue the update for later
            this.queueUpdate(eventType, data);
            return;
        }

        // Trigger event handlers
        this.trigger(eventType, data);
    },

    /**
     * Check if user is currently busy and shouldn't be interrupted
     * 
     * @returns {boolean}
     */
    isUserBusy() {
        // Check if any modal is open
        const modalOpen = document.querySelector('.modal-overlay:not(.hidden)') !== null;
        if (modalOpen) return true;

        // Check if user is typing in an input
        const activeElement = document.activeElement;
        if (activeElement) {
            const isTyping =
                activeElement.tagName === 'INPUT' ||
                activeElement.tagName === 'TEXTAREA' ||
                activeElement.isContentEditable;
            if (isTyping) return true;
        }

        // Check if dropdown is open
        const dropdownOpen = document.querySelector('.dropdown.open') !== null;
        if (dropdownOpen) return true;

        return false;
    },

    /**
     * Queued updates when user is busy
     */
    pendingUpdates: {},

    /**
     * Queue an update for later
     * 
     * @param {string} eventType
     * @param {object} data
     */
    queueUpdate(eventType, data) {
        this.pendingUpdates[eventType] = data;

        // Show subtle indicator that updates are available
        this.showUpdateIndicator();
    },

    /**
     * Process queued updates
     */
    processQueue() {
        const updates = { ...this.pendingUpdates };
        this.pendingUpdates = {};

        Object.entries(updates).forEach(([eventType, data]) => {
            this.trigger(eventType, data);
        });

        this.hideUpdateIndicator();
    },

    /**
     * Show subtle indicator that updates are pending
     */
    showUpdateIndicator() {
        let indicator = document.getElementById('sse-update-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'sse-update-indicator';
            indicator.className = 'sse-update-indicator';
            indicator.innerHTML = `
                <span class="sse-indicator-dot"></span>
                <span class="sse-indicator-text">New updates available</span>
            `;
            indicator.addEventListener('click', () => {
                this.processQueue();
            });
            document.body.appendChild(indicator);
        }
        indicator.classList.add('visible');
    },

    /**
     * Hide update indicator
     */
    hideUpdateIndicator() {
        const indicator = document.getElementById('sse-update-indicator');
        if (indicator) {
            indicator.classList.remove('visible');
        }
    },

    /**
     * Register event handler
     * 
     * @param {string} eventType
     * @param {function} callback
     */
    on(eventType, callback) {
        if (!this.handlers[eventType]) {
            this.handlers[eventType] = [];
        }
        this.handlers[eventType].push(callback);
    },

    /**
     * Remove event handler
     * 
     * @param {string} eventType
     * @param {function} callback
     */
    off(eventType, callback) {
        if (!this.handlers[eventType]) return;

        if (callback) {
            this.handlers[eventType] = this.handlers[eventType].filter(h => h !== callback);
        } else {
            delete this.handlers[eventType];
        }
    },

    /**
     * Remove all handlers for specified event types
     * Useful for component cleanup
     * 
     * @param {string[]} eventTypes - Array of event types to clear
     */
    offAll(eventTypes) {
        if (!eventTypes || !Array.isArray(eventTypes)) {
            // Clear all handlers
            this.handlers = {};
            return;
        }

        eventTypes.forEach(eventType => {
            delete this.handlers[eventType];
        });
    },

    /**
     * Trigger event handlers
     * 
     * @param {string} eventType
     * @param {object} data
     */
    trigger(eventType, data) {
        if (!this.handlers[eventType]) return;

        this.handlers[eventType].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error('SSE handler error:', error);
            }
        });
    }
};

// Make Utils globally available
window.Utils = Utils;

// Make SSE globally available
window.SSE = SSE;

// Auto-connect SSE when page loads (if authenticated)
// SSE runs on separate port 8001 so it won't block main API requests
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (Auth.isAuthenticated()) {
            SSE.connect();
        }
    }, 1000);
});

// Cleanup SSE on logout
window.addEventListener('logout', () => {
    SSE.cleanup();
});

// Process pending updates when user finishes being busy
document.addEventListener('click', (e) => {
    // Check if modal was closed or dropdown closed
    setTimeout(() => {
        if (!SSE.isUserBusy() && Object.keys(SSE.pendingUpdates).length > 0) {
            SSE.processQueue();
        }
    }, 100);
});

// Reconnect SSE when tab becomes visible again
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && Auth.isAuthenticated()) {
        if (!SSE.state.connected) {
            SSE.connect();
        }
    }
});