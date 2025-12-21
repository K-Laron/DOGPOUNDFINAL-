/**
 * Modal Component
 * Reusable modal dialog system
 * 
 * @package AnimalShelter
 */

const Modal = {
    /**
     * Active modals stack
     */
    modals: [],

    /**
     * Modal counter for unique IDs
     */
    counter: 0,

    /**
     * Default options
     */
    defaults: {
        size: 'default', // 'sm', 'default', 'lg', 'xl', 'full'
        closable: true,
        closeOnOverlay: true,
        closeOnEscape: true,
        showClose: true,
        animation: true
    },

    /**
     * Open modal
     * @param {Object} options - Modal options
     * @returns {Object} Modal instance
     */
    open(options) {
        const config = { ...this.defaults, ...options };
        const id = `modal-${++this.counter}`;

        // Create modal element
        const overlay = this.createModalElement(id, config);

        // Get container
        const container = document.getElementById('modal-container') || document.body;
        container.appendChild(overlay);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Track modal
        const modalObj = {
            id,
            overlay,
            modal: overlay.querySelector('.modal'),
            config,
            onClose: config.onClose
        };

        this.modals.push(modalObj);

        // Animate in
        requestAnimationFrame(() => {
            overlay.classList.add('open');
        });

        // Setup event listeners
        this.setupEventListeners(modalObj);

        // Focus first input
        setTimeout(() => {
            const firstInput = modalObj.modal.querySelector('input, select, textarea, button');
            if (firstInput) firstInput.focus();
        }, 300);

        return {
            id,
            close: () => this.close(id),
            setContent: (content) => this.setContent(id, content),
            setTitle: (title) => this.setTitle(id, title)
        };
    },

    /**
     * Create modal element
     * @param {string} id - Modal ID
     * @param {Object} config - Modal configuration
     * @returns {HTMLElement}
     */
    createModalElement(id, config) {
        const overlay = document.createElement('div');
        overlay.id = id;
        overlay.className = 'modal-overlay';

        const sizeClass = config.size !== 'default' ? `modal-${config.size}` : '';

        overlay.innerHTML = `
            <div class="modal ${sizeClass}" role="dialog" aria-modal="true" aria-labelledby="${id}-title">
                ${config.title !== false ? `
                    <div class="modal-header">
                        <h3 class="modal-title" id="${id}-title">${config.title || ''}</h3>
                        ${config.showClose ? `
                            <button class="modal-close" data-action="close" aria-label="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        ` : ''}
                    </div>
                ` : ''}
                <div class="modal-body">
                    ${config.content || ''}
                </div>
                ${config.footer !== false ? `
                    <div class="modal-footer">
                        ${config.footer || this.getDefaultFooter(config)}
                    </div>
                ` : ''}
            </div>
        `;

        return overlay;
    },

    /**
     * Get default footer buttons
     * @param {Object} config
     * @returns {string}
     */
    getDefaultFooter(config) {
        const buttons = [];

        if (config.cancelText !== false) {
            buttons.push(`
                <button class="btn btn-secondary" data-action="cancel">
                    ${config.cancelText || 'Cancel'}
                </button>
            `);
        }

        if (config.confirmText !== false) {
            buttons.push(`
                <button class="btn btn-primary" data-action="confirm">
                    ${config.confirmText || 'Confirm'}
                </button>
            `);
        }

        return buttons.join('');
    },

    /**
     * Setup event listeners for modal
     * @param {Object} modalObj
     */
    setupEventListeners(modalObj) {
        const { overlay, modal, config } = modalObj;

        // Close button
        overlay.addEventListener('click', async (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;

            if (action === 'close' || action === 'cancel') {
                if (config.onCancel) config.onCancel();
                this.close(modalObj.id);
            } else if (action === 'confirm') {
                if (config.onConfirm) {
                    const btn = e.target.closest('[data-action="confirm"]');
                    if (btn && window.Loading) window.Loading.setButtonLoading(btn, true);

                    try {
                        const result = await config.onConfirm();
                        // If onConfirm returns false, don't close
                        if (result !== false) {
                            this.close(modalObj.id);
                        }
                    } catch (error) {
                        console.error('Modal confirm error:', error);
                    } finally {
                        if (btn && window.Loading) window.Loading.setButtonLoading(btn, false);
                    }
                } else {
                    this.close(modalObj.id);
                }
            }
        });

        // Close on overlay click
        if (config.closeOnOverlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.close(modalObj.id);
                }
            });
        }

        // Close on escape
        if (config.closeOnEscape) {
            const escHandler = (e) => {
                if (e.key === 'Escape' && this.modals[this.modals.length - 1]?.id === modalObj.id) {
                    this.close(modalObj.id);
                }
            };
            document.addEventListener('keydown', escHandler);
            modalObj.escHandler = escHandler;
        }
    },

    /**
     * Close modal
     * @param {string} id - Modal ID
     */
    close(id) {
        const index = this.modals.findIndex(m => m.id === id);
        if (index === -1) return;

        const modalObj = this.modals[index];
        const { overlay, escHandler, onClose } = modalObj;

        // Remove escape handler
        if (escHandler) {
            document.removeEventListener('keydown', escHandler);
        }

        // Animate out
        overlay.classList.remove('open');

        // Remove after animation
        setTimeout(() => {
            overlay.remove();
            this.modals.splice(index, 1);

            // Restore body scroll if no more modals
            if (this.modals.length === 0) {
                document.body.style.overflow = '';
            }

            // Call onClose callback
            if (onClose) onClose();
        }, 300);
    },

    /**
     * Close all modals
     */
    closeAll() {
        [...this.modals].forEach(modal => this.close(modal.id));
    },

    /**
     * Set modal content
     * @param {string} id
     * @param {string} content
     */
    setContent(id, content) {
        const modalObj = this.modals.find(m => m.id === id);
        if (modalObj) {
            const body = modalObj.modal.querySelector('.modal-body');
            if (body) body.innerHTML = content;
        }
    },

    /**
     * Set modal title
     * @param {string} id
     * @param {string} title
     */
    setTitle(id, title) {
        const modalObj = this.modals.find(m => m.id === id);
        if (modalObj) {
            const titleEl = modalObj.modal.querySelector('.modal-title');
            if (titleEl) titleEl.textContent = title;
        }
    },

    /**
     * ==========================================
     * CONVENIENCE METHODS
     * ==========================================
     */

    /**
     * Show alert modal
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     */
    alert(message, title = 'Alert', options = {}) {
        return this.open({
            title,
            content: `<p>${message}</p>`,
            cancelText: false,
            confirmText: 'OK',
            size: 'sm',
            ...options
        });
    },

    /**
     * Show confirm modal
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     * @returns {Promise<boolean>}
     */
    confirm(message, title = 'Confirm', options = {}) {
        return new Promise((resolve) => {
            this.open({
                title,
                content: `<p>${message}</p>`,
                cancelText: options.cancelText || 'Cancel',
                confirmText: options.confirmText || 'Confirm',
                size: 'sm',
                onConfirm: () => {
                    resolve(true);
                },
                onCancel: () => {
                    resolve(false);
                },
                onClose: () => {
                    resolve(false);
                },
                ...options
            });
        });
    },

    /**
     * Show delete confirmation modal
     * @param {string} itemName
     * @param {Object} options
     * @returns {Promise<boolean>}
     */
    confirmDelete(itemName = 'this item', options = {}) {
        return this.confirm(
            `Are you sure you want to delete ${itemName}? This action cannot be undone.`,
            'Delete Confirmation',
            {
                confirmText: 'Delete',
                footer: `
                    <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                    <button class="btn btn-danger" data-action="confirm">Delete</button>
                `,
                ...options
            }
        );
    },

    /**
     * Show prompt modal
     * @param {string} message
     * @param {string} title
     * @param {Object} options
     * @returns {Promise<string|null>}
     */
    prompt(message, title = 'Input', options = {}) {
        return new Promise((resolve) => {
            const inputId = `prompt-input-${this.counter}`;

            this.open({
                title,
                content: `
                    <p class="mb-4">${message}</p>
                    <div class="form-group mb-0">
                        <input 
                            type="${options.type || 'text'}" 
                            id="${inputId}"
                            class="form-input" 
                            placeholder="${options.placeholder || ''}"
                            value="${options.defaultValue || ''}"
                        >
                    </div>
                `,
                size: 'sm',
                onConfirm: () => {
                    const input = document.getElementById(inputId);
                    resolve(input?.value || '');
                },
                onCancel: () => {
                    resolve(null);
                },
                onClose: () => {
                    resolve(null);
                },
                ...options
            });
        });
    },

    /**
     * Show form modal
     * @param {Object} options
     * @returns {Object} Modal instance
     */
    form(options) {
        const { fields, onSubmit, ...modalOptions } = options;

        const formHtml = Form.generate(fields);

        return this.open({
            content: `<form id="modal-form" class="modal-form">${formHtml}</form>`,
            onConfirm: () => {
                const form = document.getElementById('modal-form');
                const data = Form.getData(form);

                if (Form.validate(form)) {
                    if (onSubmit) {
                        return onSubmit(data);
                    }
                }
                return false; // Don't close if validation fails
            },
            ...modalOptions
        });
    },

    /**
     * Show loading modal
     * @param {string} message
     * @returns {Object} Modal instance
     */
    loading(message = 'Loading...') {
        return this.open({
            title: false,
            content: `
                <div class="flex flex-col items-center justify-center p-6">
                    <div class="loading-spinner mb-4"></div>
                    <p class="text-secondary">${message}</p>
                </div>
            `,
            footer: false,
            closable: false,
            closeOnOverlay: false,
            closeOnEscape: false,
            size: 'sm'
        });
    }
};

// Make Modal globally available
window.Modal = Modal;