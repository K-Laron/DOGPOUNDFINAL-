/**
 * Toast Component
 * Notification system for displaying messages
 * 
 * @package AnimalShelter
 */

const Toast = {
    /**
     * Default options
     */
    defaults: {
        duration: 4000,
        position: 'top-right',
        closable: true,
        pauseOnHover: true
    },
    
    /**
     * Active toasts
     */
    toasts: [],
    
    /**
     * Toast counter for unique IDs
     */
    counter: 0,
    
    /**
     * Icons for each type
     */
    icons: {
        success: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon-animated"><circle cx="12" cy="12" r="10" class="toast-icon-circle"></circle><polyline points="9 12 12 15 16 10" class="toast-icon-check"></polyline></svg>`,
        error: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon-animated"><circle cx="12" cy="12" r="10" class="toast-icon-circle"></circle><line x1="15" y1="9" x2="9" y2="15" class="toast-icon-x"></line><line x1="9" y1="9" x2="15" y2="15" class="toast-icon-x"></line></svg>`,
        warning: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon-animated"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" class="toast-icon-triangle"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
        info: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon-animated"><circle cx="12" cy="12" r="10" class="toast-icon-circle"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
    },
    
    /**
     * Show toast notification
     * @param {Object} options - Toast options
     * @returns {string} Toast ID
     */
    show(options) {
        const config = { ...this.defaults, ...options };
        const id = `toast-${++this.counter}`;
        
        // Create toast element
        const toast = this.createToastElement(id, config);
        
        // Get container
        const container = this.getContainer();
        container.appendChild(toast);
        
        // Track toast
        const toastObj = {
            id,
            element: toast,
            config,
            timer: null,
            remaining: config.duration
        };
        
        this.toasts.push(toastObj);
        
        // Start auto-dismiss timer
        if (config.duration > 0) {
            this.startTimer(toastObj);
        }
        
        // Pause on hover
        if (config.pauseOnHover) {
            toast.addEventListener('mouseenter', () => this.pauseTimer(toastObj));
            toast.addEventListener('mouseleave', () => this.resumeTimer(toastObj));
        }

        // Announce for screen readers
        const priority = config.type === 'error' ? 'assertive' : 'polite';
        const announcement = config.title ? `${config.type}: ${config.title}. ${config.message}` : `${config.type}: ${config.message}`;
        if (window.Utils && window.Utils.announce) {
            Utils.announce(announcement, priority);
        }
        
        return id;
    },
    
    /**
     * Create toast element
     * @param {string} id - Toast ID
     * @param {Object} config - Toast configuration
     * @returns {HTMLElement}
     */
    createToastElement(id, config) {
        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `toast ${config.type || 'info'}`;
        
        toast.innerHTML = `
            <div class="toast-icon">
                ${this.icons[config.type] || this.icons.info}
            </div>
            <div class="toast-content">
                ${config.title ? `<div class="toast-title">${config.title}</div>` : ''}
                <div class="toast-message">${config.message}</div>
            </div>
            ${config.closable ? `
                <button class="toast-close" onclick="Toast.dismiss('${id}')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            ` : ''}
        `;
        
        return toast;
    },
    
    /**
     * Get or create toast container
     * @returns {HTMLElement}
     */
    getContainer() {
        let container = document.getElementById('toast-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        return container;
    },
    
    /**
     * Dismiss toast
     * @param {string} id - Toast ID
     */
    dismiss(id) {
        const index = this.toasts.findIndex(t => t.id === id);
        
        if (index === -1) return;
        
        const toastObj = this.toasts[index];
        const { element, timer } = toastObj;
        
        // Clear timer
        if (timer) {
            clearTimeout(timer);
        }
        
        // Animate out
        element.classList.add('removing');
        
        // Remove after animation
        setTimeout(() => {
            element.remove();
            this.toasts.splice(index, 1);
        }, 300);
    },
    
    /**
     * Dismiss all toasts
     */
    dismissAll() {
        [...this.toasts].forEach(toast => this.dismiss(toast.id));
    },
    
    /**
     * Start auto-dismiss timer
     * @param {Object} toastObj
     */
    startTimer(toastObj) {
        toastObj.startTime = Date.now();
        toastObj.timer = setTimeout(() => {
            this.dismiss(toastObj.id);
        }, toastObj.remaining);
    },
    
    /**
     * Pause timer
     * @param {Object} toastObj
     */
    pauseTimer(toastObj) {
        if (toastObj.timer) {
            clearTimeout(toastObj.timer);
            toastObj.remaining -= Date.now() - toastObj.startTime;
        }
    },
    
    /**
     * Resume timer
     * @param {Object} toastObj
     */
    resumeTimer(toastObj) {
        if (toastObj.remaining > 0) {
            this.startTimer(toastObj);
        }
    },
    
    /**
     * ==========================================
     * CONVENIENCE METHODS
     * ==========================================
     */
    
    /**
     * Show success toast
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     */
    success(message, title = '', options = {}) {
        return this.show({
            type: 'success',
            message,
            title: title || 'Success',
            ...options
        });
    },
    
    /**
     * Show error toast
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     */
    error(message, title = '', options = {}) {
        return this.show({
            type: 'error',
            message,
            title: title || 'Error',
            duration: 6000, // Longer for errors
            ...options
        });
    },
    
    /**
     * Show warning toast
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     */
    warning(message, title = '', options = {}) {
        return this.show({
            type: 'warning',
            message,
            title: title || 'Warning',
            ...options
        });
    },
    
    /**
     * Show info toast
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     */
    info(message, title = '', options = {}) {
        return this.show({
            type: 'info',
            message,
            title: title || 'Info',
            ...options
        });
    },
    
    /**
     * Show promise toast (loading -> success/error)
     * @param {Promise} promise
     * @param {Object} messages
     * @param {Object} options
     */
    async promise(promise, messages = {}, options = {}) {
        const {
            loading = 'Loading...',
            success = 'Completed successfully',
            error = 'Something went wrong'
        } = messages;
        
        // Show loading toast
        const id = this.show({
            type: 'info',
            message: loading,
            duration: 0, // Don't auto-dismiss
            closable: false,
            ...options
        });
        
        try {
            const result = await promise;
            this.dismiss(id);
            this.success(typeof success === 'function' ? success(result) : success);
            return result;
        } catch (err) {
            this.dismiss(id);
            this.error(typeof error === 'function' ? error(err) : (err.message || error));
            throw err;
        }
    }
};

// Make Toast globally available
window.Toast = Toast;