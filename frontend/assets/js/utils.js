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
     * Get item from localStorage
     * @param {string} key
     * @param {*} defaultValue
     * @returns {*}
     */
    getStorage(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('Error reading from localStorage:', e);
            return defaultValue;
        }
    },

    /**
     * Set item in localStorage
     * @param {string} key
     * @param {*} value
     */
    setStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Error writing to localStorage:', e);
        }
    },

    /**
     * Remove item from localStorage
     * @param {string} key
     */
    removeStorage(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error('Error removing from localStorage:', e);
        }
    },

    /**
     * ==========================================
     * ASYNC UTILITIES
     * ==========================================
     */

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
        if (!str) return '#007AFF';

        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }

        const colors = [
            '#FF3B30', '#FF9500', '#FFCC00', '#34C759', '#00C7BE',
            '#30B0C7', '#32ADE6', '#007AFF', '#5856D6', '#AF52DE',
            '#FF2D55', '#A2845E'
        ];

        return colors[Math.abs(hash) % colors.length];
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
            'Adopted': 'badge-info',
            'In Treatment': 'badge-warning',
            'Quarantine': 'badge-danger',
            'Deceased': 'badge-gray',
            'Reclaimed': 'badge-info',

            // Adoption statuses
            'Pending': 'badge-warning',
            'Interview Scheduled': 'badge-info',
            'Approved': 'badge-success',
            'Rejected': 'badge-danger',
            'Completed': 'badge-success',
            'Cancelled': 'badge-gray',

            // Invoice statuses
            'Paid': 'badge-success',
            'Unpaid': 'badge-warning',

            // User statuses
            'Active': 'badge-success',
            'Inactive': 'badge-warning',
            'Banned': 'badge-danger'
        };

        return statusMap[status] || 'badge-gray';
    }
};

// Make Utils globally available
window.Utils = Utils;