/**
 * Loading Component
 * Various loading indicators and skeletons
 * 
 * @package AnimalShelter
 */

const Loading = {
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