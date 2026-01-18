/**
 * Loading Component
 * Various loading indicators, skeletons, and empty states
 * 
 * @package AnimalShelter
 */

const Loading = {
    /**
     * SVG Icons for empty states
     */
    emptyStateIcons: {
        noData: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15" opacity="0.5"/></svg>`,
        noAnimals: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5"/><path d="M14.267 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.855-1.45-2.239-2.5"/><path d="M8 14v.5"/><path d="M16 14v.5"/><path d="M11.25 16.25h1.5L12 17l-.75-.75Z"/><path d="M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444c0-1.061-.162-2.2-.493-3.309m-9.243-6.082A8.801 8.801 0 0 1 12 5c.78 0 1.5.108 2.161.306"/><line x1="4" y1="4" x2="20" y2="20" opacity="0.5"/></svg>`,
        noResults: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11" opacity="0.5"/></svg>`,
        noUsers: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><line x1="1" y1="1" x2="23" y2="23" opacity="0.3"/></svg>`,
        noMedical: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/><circle cx="12" cy="12" r="10" opacity="0.3"/></svg>`,
        noInventory: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96" opacity="0.5"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`,
        noBilling: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/><line x1="7" y1="15" x2="13" y2="15" opacity="0.5"/></svg>`,
        error: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        offline: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.58 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>`,
        success: `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`
    },

    /**
     * Render spinner
     * @param {Object} options
     * @returns {string}
     */
    spinner(options = {}) {
        const { size = 'md', color = 'primary', text = '' } = options;
        
        const sizes = {
            sm: 'width: 20px; height: 20px;',
            md: 'width: 32px; height: 32px;',
            lg: 'width: 48px; height: 48px;',
            xl: 'width: 64px; height: 64px;'
        };
        
        return `
            <div class="loading-spinner-container ${text ? 'has-text' : ''}">
                <div class="loading-spinner ${color}" style="${sizes[size] || sizes.md}">
                    <svg viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round">
                            <animate attributeName="stroke-dasharray" dur="1.5s" repeatCount="indefinite" values="1,150;90,150;90,150"/>
                            <animate attributeName="stroke-dashoffset" dur="1.5s" repeatCount="indefinite" values="0;-35;-124"/>
                        </circle>
                    </svg>
                </div>
                ${text ? `<p class="loading-text">${text}</p>` : ''}
            </div>
        `;
    },
    
    /**
     * Render dots loader
     * @param {Object} options
     * @returns {string}
     */
    dots(options = {}) {
        const { text = '' } = options;
        
        return `
            <div class="loading-dots-container">
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                ${text ? `<p class="loading-text">${text}</p>` : ''}
            </div>
        `;
    },
    
    /**
     * Render pulse loader
     * @param {Object} options
     * @returns {string}
     */
    pulse(options = {}) {
        const { size = 'md' } = options;
        
        return `
            <div class="loading-pulse ${size}">
                <div class="pulse-ring"></div>
                <div class="pulse-ring"></div>
                <div class="pulse-ring"></div>
            </div>
        `;
    },
    
    /**
     * Render skeleton loader
     * @param {string} type - Type of skeleton
     * @param {Object} options
     * @returns {string}
     */
    skeleton(type = 'text', options = {}) {
        const skeletons = {
            text: this.skeletonText(options),
            title: this.skeletonTitle(options),
            avatar: this.skeletonAvatar(options),
            image: this.skeletonImage(options),
            card: this.skeletonCard(options),
            table: this.skeletonTable(options),
            list: this.skeletonList(options),
            stats: this.skeletonStats(options)
        };
        
        return skeletons[type] || skeletons.text;
    },
    
    /**
     * Text skeleton
     */
    skeletonText(options = {}) {
        const { lines = 3, width = '100%' } = options;
        let html = '';
        
        for (let i = 0; i < lines; i++) {
            const lineWidth = i === lines - 1 ? '60%' : width;
            html += `<div class="skeleton skeleton-text" style="width: ${lineWidth}"></div>`;
        }
        
        return `<div class="skeleton-text-group">${html}</div>`;
    },
    
    /**
     * Title skeleton
     */
    skeletonTitle(options = {}) {
        const { width = '40%' } = options;
        return `<div class="skeleton skeleton-title" style="width: ${width}"></div>`;
    },
    
    /**
     * Avatar skeleton
     */
    skeletonAvatar(options = {}) {
        const { size = 40 } = options;
        return `<div class="skeleton skeleton-avatar" style="width: ${size}px; height: ${size}px"></div>`;
    },
    
    /**
     * Image skeleton
     */
    skeletonImage(options = {}) {
        const { height = 200, aspectRatio } = options;
        const style = aspectRatio 
            ? `aspect-ratio: ${aspectRatio}` 
            : `height: ${height}px`;
        return `<div class="skeleton skeleton-image" style="${style}"></div>`;
    },
    
    /**
     * Card skeleton
     */
    skeletonCard(options = {}) {
        const { hasImage = true, lines = 2 } = options;
        
        return `
            <div class="card skeleton-card">
                ${hasImage ? '<div class="skeleton skeleton-image" style="height: 180px"></div>' : ''}
                <div class="card-body">
                    <div class="skeleton skeleton-title" style="width: 70%"></div>
                    ${this.skeletonText({ lines })}
                </div>
            </div>
        `;
    },
    
    /**
     * Table skeleton
     */
    skeletonTable(options = {}) {
        const { rows = 5, cols = 4 } = options;
        
        let headerCells = '';
        for (let i = 0; i < cols; i++) {
            headerCells += '<th><div class="skeleton skeleton-text" style="width: 80%"></div></th>';
        }
        
        let bodyRows = '';
        for (let i = 0; i < rows; i++) {
            let cells = '';
            for (let j = 0; j < cols; j++) {
                const width = j === 0 ? '60%' : '80%';
                cells += `<td><div class="skeleton skeleton-text" style="width: ${width}"></div></td>`;
            }
            bodyRows += `<tr>${cells}</tr>`;
        }
        
        return `
            <div class="table-container">
                <table class="table">
                    <thead><tr>${headerCells}</tr></thead>
                    <tbody>${bodyRows}</tbody>
                </table>
            </div>
        `;
    },
    
    /**
     * List skeleton
     */
    skeletonList(options = {}) {
        const { items = 5, hasAvatar = true, hasSubtitle = true } = options;
        
        let html = '';
        for (let i = 0; i < items; i++) {
            html += `
                <div class="skeleton-list-item">
                    ${hasAvatar ? '<div class="skeleton skeleton-avatar"></div>' : ''}
                    <div class="skeleton-list-content">
                        <div class="skeleton skeleton-text" style="width: 50%"></div>
                        ${hasSubtitle ? '<div class="skeleton skeleton-text" style="width: 30%"></div>' : ''}
                    </div>
                </div>
            `;
        }
        
        return `<div class="skeleton-list">${html}</div>`;
    },
    
    /**
     * Stats skeleton
     */
    skeletonStats(options = {}) {
        const { count = 4 } = options;
        
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="stat-card skeleton-stat">
                    <div class="skeleton skeleton-avatar" style="width: 48px; height: 48px; border-radius: 12px"></div>
                    <div class="skeleton skeleton-title mt-4" style="width: 40%"></div>
                    <div class="skeleton skeleton-text mt-2" style="width: 60%"></div>
                </div>
            `;
        }
        
        return `<div class="stats-grid">${html}</div>`;
    },
    
    /**
     * Render inline loader
     * @param {string} text
     * @returns {string}
     */
    inline(text = 'Loading...') {
        return `
            <span class="inline-loader">
                <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                        <animate attributeName="stroke-dashoffset" dur="1s" repeatCount="indefinite" values="32;0;32"/>
                    </circle>
                </svg>
                <span>${text}</span>
            </span>
        `;
    },
    
    /**
     * Render button loader
     * @returns {string}
     */
    button() {
        return `
            <span class="btn-loader">
                <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.25"/>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                        <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                    </path>
                </svg>
            </span>
        `;
    },
    
    /**
     * Show full page loader
     * @param {string} message
     */
    showPage(message = 'Loading...') {
        let overlay = document.getElementById('page-loader');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'page-loader';
            overlay.className = 'page-loader-overlay';
            document.body.appendChild(overlay);
        }
        
        overlay.innerHTML = `
            <div class="page-loader-content">
                ${this.spinner({ size: 'lg', text: message })}
            </div>
        `;
        
        overlay.classList.add('visible');
    },
    
    /**
     * Hide full page loader
     */
    hidePage() {
        const overlay = document.getElementById('page-loader');
        if (overlay) {
            overlay.classList.remove('visible');
            setTimeout(() => overlay.remove(), 300);
        }
    },
    
    /**
     * Set button loading state
     * @param {HTMLElement} button
     * @param {boolean} isLoading
     * @param {string} loadingText
     */
    setButtonLoading(button, isLoading, loadingText = '') {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = this.button() + (loadingText || 'Loading...');
            button.classList.add('is-loading');
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
            button.classList.remove('is-loading');
        }
    },

    /**
     * Render enhanced empty state
     * @param {Object} options
     * @returns {string}
     */
    emptyState(options = {}) {
        const {
            type = 'noData',
            title = 'No data found',
            description = 'There are no items to display at the moment.',
            action = null,
            actionLabel = 'Add New',
            actionIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
            secondaryAction = null,
            secondaryLabel = 'Learn More'
        } = options;

        const icon = this.emptyStateIcons[type] || this.emptyStateIcons.noData;

        return `
            <div class="empty-state-enhanced animate-fade-in-up">
                <div class="empty-state-icon-wrapper">
                    <div class="empty-state-icon-bg"></div>
                    <div class="empty-state-icon">${icon}</div>
                </div>
                <h3 class="empty-state-title">${title}</h3>
                <p class="empty-state-description">${description}</p>
                ${action || secondaryAction ? `
                    <div class="empty-state-actions">
                        ${action ? `
                            <button class="btn btn-primary" onclick="${action}">
                                ${actionIcon}
                                <span>${actionLabel}</span>
                            </button>
                        ` : ''}
                        ${secondaryAction ? `
                            <button class="btn btn-ghost" onclick="${secondaryAction}">
                                ${secondaryLabel}
                            </button>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `;
    },

    /**
     * Render error state with retry
     * @param {Object} options
     * @returns {string}
     */
    errorState(options = {}) {
        const {
            title = 'Something went wrong',
            description = 'We encountered an error while loading. Please try again.',
            retryAction = null,
            retryLabel = 'Try Again',
            showSupport = false
        } = options;

        return `
            <div class="error-state-enhanced animate-fade-in-up">
                <div class="error-state-icon-wrapper">
                    <div class="error-state-icon-bg"></div>
                    <div class="error-state-icon">${this.emptyStateIcons.error}</div>
                </div>
                <h3 class="error-state-title">${title}</h3>
                <p class="error-state-description">${description}</p>
                <div class="error-state-actions">
                    ${retryAction ? `
                        <button class="btn btn-primary" onclick="${retryAction}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                            <span>${retryLabel}</span>
                        </button>
                    ` : ''}
                    ${showSupport ? `
                        <button class="btn btn-ghost" onclick="window.open('mailto:support@catarmandogpound.gov.ph')">
                            Contact Support
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    },

    /**
     * Render offline state
     * @param {Object} options
     * @returns {string}
     */
    offlineState(options = {}) {
        const {
            title = 'You\'re offline',
            description = 'Please check your internet connection and try again.',
            retryAction = 'location.reload()'
        } = options;

        return `
            <div class="offline-state-enhanced animate-fade-in-up">
                <div class="offline-state-icon-wrapper">
                    <div class="offline-state-icon-bg"></div>
                    <div class="offline-state-icon">${this.emptyStateIcons.offline}</div>
                </div>
                <h3 class="offline-state-title">${title}</h3>
                <p class="offline-state-description">${description}</p>
                <div class="offline-state-actions">
                    <button class="btn btn-primary" onclick="${retryAction}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                        <span>Retry</span>
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Render success state
     * @param {Object} options
     * @returns {string}
     */
    successState(options = {}) {
        const {
            title = 'Success!',
            description = 'The operation completed successfully.',
            action = null,
            actionLabel = 'Continue'
        } = options;

        return `
            <div class="success-state-enhanced animate-fade-in-up">
                <div class="success-state-icon-wrapper animate-celebrate">
                    <div class="success-state-icon-bg"></div>
                    <div class="success-state-icon">${this.emptyStateIcons.success}</div>
                </div>
                <h3 class="success-state-title">${title}</h3>
                <p class="success-state-description">${description}</p>
                ${action ? `
                    <div class="success-state-actions">
                        <button class="btn btn-primary" onclick="${action}">
                            ${actionLabel}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }
};

// Add CSS for loading components
const loadingStyles = document.createElement('style');
loadingStyles.textContent = `
    .loading-spinner-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: var(--space-4);
    }
    
    .loading-spinner {
        color: var(--color-primary);
    }
    
    .loading-spinner svg {
        width: 100%;
        height: 100%;
    }
    
    .loading-text {
        margin-top: var(--space-3);
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }
    
    .loading-dots {
        display: flex;
        gap: 6px;
    }
    
    .loading-dots span {
        width: 8px;
        height: 8px;
        background-color: var(--color-primary);
        border-radius: 50%;
        animation: dots-bounce 1.4s ease-in-out infinite both;
    }
    
    .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
    .loading-dots span:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes dots-bounce {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
        40% { transform: scale(1); opacity: 1; }
    }
    
    .skeleton-list-item {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-3) 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .skeleton-list-item:last-child {
        border-bottom: none;
    }
    
    .skeleton-list-content {
        flex: 1;
    }
    
    .skeleton-text-group .skeleton-text {
        margin-bottom: var(--space-2);
    }
    
    .skeleton-text-group .skeleton-text:last-child {
        margin-bottom: 0;
    }
    
    .inline-loader {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
    }
    
    .btn-loader {
        display: inline-flex;
        margin-right: var(--space-2);
    }
    
    .page-loader-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .page-loader-overlay.visible {
        opacity: 1;
        visibility: visible;
    }
    
    [data-theme="dark"] .page-loader-overlay {
        background-color: rgba(0, 0, 0, 0.9);
    }
`;

document.head.appendChild(loadingStyles);

// Make Loading globally available
window.Loading = Loading;