/**
 * Hover Preview Component
 * Shows preview cards when hovering over animal/user links
 * 
 * @package AnimalShelter
 */

const HoverPreview = {
    /**
     * Current active preview
     */
    activePreview: null,

    /**
     * Hover delay timer
     */
    hoverTimer: null,

    /**
     * Cache for loaded data
     */
    cache: new Map(),

    /**
     * Initialize hover previews
     */
    init() {
        // Delegate event listeners
        document.addEventListener('mouseover', this.handleMouseOver.bind(this));
        document.addEventListener('mouseout', this.handleMouseOut.bind(this));
    },

    /**
     * Handle mouse over event
     * @param {Event} e
     */
    handleMouseOver(e) {
        const target = e.target.closest('[data-preview]');
        if (!target) return;

        // Clear any existing timer
        clearTimeout(this.hoverTimer);

        // Set delay before showing preview
        this.hoverTimer = setTimeout(() => {
            this.showPreview(target);
        }, 300);
    },

    /**
     * Handle mouse out event
     * @param {Event} e
     */
    handleMouseOut(e) {
        const target = e.target.closest('[data-preview]');
        if (!target) return;

        // Clear timer
        clearTimeout(this.hoverTimer);

        // Check if moving to preview card
        const relatedTarget = e.relatedTarget;
        if (relatedTarget && relatedTarget.closest('.hover-preview-card')) {
            return;
        }

        // Hide preview with delay
        setTimeout(() => {
            this.hidePreview();
        }, 100);
    },

    /**
     * Show preview card
     * @param {HTMLElement} target
     */
    async showPreview(target) {
        const type = target.dataset.preview;
        const id = target.dataset.previewId;

        if (!type || !id) return;

        // Get or create preview element
        let preview = document.getElementById('hover-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'hover-preview';
            preview.className = 'hover-preview-card';
            document.body.appendChild(preview);

            // Keep preview open when hovering over it
            preview.addEventListener('mouseenter', () => {
                clearTimeout(this.hoverTimer);
            });
            preview.addEventListener('mouseleave', () => {
                this.hidePreview();
            });
        }

        // Show loading state
        preview.innerHTML = `
            <div class="hover-preview-loading">
                <div class="loading-spinner-sm"></div>
            </div>
        `;
        preview.classList.add('visible');

        // Position preview
        this.positionPreview(preview, target);

        // Load and render content
        try {
            const content = await this.loadContent(type, id);
            preview.innerHTML = content;
        } catch (error) {
            preview.innerHTML = `
                <div class="hover-preview-error">
                    <p>Failed to load preview</p>
                </div>
            `;
        }

        this.activePreview = preview;
    },

    /**
     * Hide preview card
     */
    hidePreview() {
        const preview = document.getElementById('hover-preview');
        if (preview) {
            preview.classList.remove('visible');
        }
        this.activePreview = null;
    },

    /**
     * Position preview relative to target
     * @param {HTMLElement} preview
     * @param {HTMLElement} target
     */
    positionPreview(preview, target) {
        const targetRect = target.getBoundingClientRect();
        const previewWidth = 320;
        const previewHeight = 200; // Approximate

        let left = targetRect.left + (targetRect.width / 2) - (previewWidth / 2);
        let top = targetRect.bottom + 10;

        // Adjust if going off screen right
        if (left + previewWidth > window.innerWidth - 20) {
            left = window.innerWidth - previewWidth - 20;
        }

        // Adjust if going off screen left
        if (left < 20) {
            left = 20;
        }

        // Adjust if going off screen bottom
        if (top + previewHeight > window.innerHeight - 20) {
            top = targetRect.top - previewHeight - 10;
        }

        preview.style.left = `${left}px`;
        preview.style.top = `${top}px`;
        preview.style.width = `${previewWidth}px`;
    },

    /**
     * Load content for preview
     * @param {string} type
     * @param {string} id
     * @returns {Promise<string>}
     */
    async loadContent(type, id) {
        const cacheKey = `${type}-${id}`;

        // Check cache
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        let content = '';

        try {
            switch (type) {
                case 'animal':
                    content = await this.loadAnimalPreview(id);
                    break;
                case 'user':
                    content = await this.loadUserPreview(id);
                    break;
                case 'adoption':
                    content = await this.loadAdoptionPreview(id);
                    break;
                default:
                    content = '<p>Unknown preview type</p>';
            }

            // Cache for 5 minutes
            this.cache.set(cacheKey, content);
            setTimeout(() => {
                this.cache.delete(cacheKey);
            }, 5 * 60 * 1000);

        } catch (error) {
            console.error('Preview load error:', error);
            throw error;
        }

        return content;
    },

    /**
     * Load animal preview
     * @param {string} id
     * @returns {Promise<string>}
     */
    async loadAnimalPreview(id) {
        const response = await API.get(`/animals.php?id=${id}`);
        const animal = response.animal || response;

        const imageUrl = animal.Image_URL || '/assets/images/default-animal.jpg';
        const statusClass = Utils.getStatusBadgeClass(animal.Status);

        return `
            <div class="hover-preview-header">
                <div class="hover-preview-image">
                    <img src="${imageUrl}" alt="${animal.Name}" onerror="this.src='/assets/images/default-animal.jpg'">
                </div>
                <div class="hover-preview-info">
                    <h4>${animal.Name}</h4>
                    <span class="badge ${statusClass}">${animal.Status}</span>
                </div>
            </div>
            <div class="hover-preview-body">
                <div class="hover-preview-meta">
                    <span><strong>Species:</strong> ${animal.Species || 'N/A'}</span>
                    <span><strong>Breed:</strong> ${animal.Breed || 'Unknown'}</span>
                    <span><strong>Age:</strong> ${animal.Age || 'Unknown'}</span>
                    <span><strong>Gender:</strong> ${animal.Gender || 'N/A'}</span>
                </div>
            </div>
            <div class="hover-preview-footer">
                <a href="#" onclick="Router.navigate('/animals/${id}'); return false;" class="btn btn-sm btn-primary">
                    View Details
                </a>
            </div>
        `;
    },

    /**
     * Load user preview
     * @param {string} id
     * @returns {Promise<string>}
     */
    async loadUserPreview(id) {
        const response = await API.get(`/users.php?id=${id}`);
        const user = response.user || response;

        const fullName = `${user.First_Name || ''} ${user.Last_Name || ''}`.trim() || 'Unknown';
        const initials = Utils.getInitials(fullName);
        const gradient = Utils.stringToGradient(user.Email || fullName);

        return `
            <div class="hover-preview-header">
                <div class="hover-preview-avatar" style="background: ${gradient}">
                    ${user.Avatar_URL ? 
                        `<img src="${user.Avatar_URL}" alt="${fullName}">` : 
                        `<span>${initials}</span>`
                    }
                </div>
                <div class="hover-preview-info">
                    <h4>${fullName}</h4>
                    <span class="text-secondary">${user.Role_Name || user.Role || 'User'}</span>
                </div>
            </div>
            <div class="hover-preview-body">
                <div class="hover-preview-meta">
                    <span><strong>Email:</strong> ${user.Email || 'N/A'}</span>
                    <span><strong>Phone:</strong> ${user.Phone || 'N/A'}</span>
                    <span><strong>Status:</strong> ${user.Status || 'Active'}</span>
                </div>
            </div>
            <div class="hover-preview-footer">
                <a href="#" onclick="Router.navigate('/users/${id}'); return false;" class="btn btn-sm btn-primary">
                    View Profile
                </a>
            </div>
        `;
    },

    /**
     * Load adoption preview
     * @param {string} id
     * @returns {Promise<string>}
     */
    async loadAdoptionPreview(id) {
        const response = await API.get(`/adoptions.php?id=${id}`);
        const adoption = response.adoption || response;

        const statusClass = Utils.getStatusBadgeClass(adoption.Status);
        const date = Utils.formatDate(adoption.Request_Date || adoption.Created_At);

        return `
            <div class="hover-preview-header">
                <div class="hover-preview-info" style="flex: 1;">
                    <h4>Adoption Request #${id}</h4>
                    <span class="badge ${statusClass}">${adoption.Status}</span>
                </div>
            </div>
            <div class="hover-preview-body">
                <div class="hover-preview-meta">
                    <span><strong>Animal:</strong> ${adoption.Animal_Name || 'N/A'}</span>
                    <span><strong>Adopter:</strong> ${adoption.Adopter_Name || adoption.First_Name + ' ' + adoption.Last_Name || 'N/A'}</span>
                    <span><strong>Date:</strong> ${date}</span>
                </div>
            </div>
            <div class="hover-preview-footer">
                <a href="#" onclick="Router.navigate('/adoptions/${id}'); return false;" class="btn btn-sm btn-primary">
                    View Details
                </a>
            </div>
        `;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    HoverPreview.init();
});

// Make HoverPreview globally available
window.HoverPreview = HoverPreview;
