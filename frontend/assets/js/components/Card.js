/**
 * Card Component
 * Reusable card components
 * 
 * @package AnimalShelter
 */

const Card = {
    /**
     * Render basic card
     * @param {Object} options
     * @returns {string}
     */
    render(options = {}) {
        const {
            title = '',
            subtitle = '',
            content = '',
            footer = '',
            headerActions = '',
            className = '',
            id = '',
            hoverable = false
        } = options;

        return `
            <div class="card ${hoverable ? 'card-hover' : ''} ${className}" ${id ? `id="${id}"` : ''}>
                ${title || headerActions ? `
                    <div class="card-header">
                        <div>
                            ${title ? `<h3 class="card-title">${title}</h3>` : ''}
                            ${subtitle ? `<p class="text-secondary" style="font-size: var(--text-sm); margin-top: 2px;">${subtitle}</p>` : ''}
                        </div>
                        ${headerActions ? `<div class="card-actions">${headerActions}</div>` : ''}
                    </div>
                ` : ''}
                <div class="card-body">
                    ${content}
                </div>
                ${footer ? `<div class="card-footer">${footer}</div>` : ''}
            </div>
        `;
    },

    /**
     * Render stat card
     * @param {Object} options
     * @returns {string}
     */
    stat(options = {}) {
        const {
            title = '',
            value = 0,
            icon = '',
            iconColor = 'primary',
            trend = null,
            trendLabel = '',
            subtitle = ''
        } = options;

        const trendHtml = trend !== null ? `
            <div class="stat-card-trend ${trend >= 0 ? 'up' : 'down'}">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    ${trend >= 0
                ? '<polyline points="18 15 12 9 6 15"></polyline>'
                : '<polyline points="6 9 12 15 18 9"></polyline>'
            }
                </svg>
                <span>${Math.abs(trend)}% ${trendLabel}</span>
            </div>
        ` : '';

        return `
            <div class="stat-card">
                ${icon ? `
                    <div class="stat-card-icon ${iconColor}">
                        ${icon}
                    </div>
                ` : ''}
                <div class="stat-card-value">${typeof value === 'number' ? Utils.formatNumber(value) : value}</div>
                <div class="stat-card-label">${title}</div>
                ${subtitle ? `<div class="text-tertiary" style="font-size: var(--text-xs); margin-top: 4px;">${subtitle}</div>` : ''}
                ${trendHtml}
            </div>
        `;
    },

    /**
     * Render animal card
     * @param {Object} animal
     * @returns {string}
     */
    animal(animal) {
        const statusClass = Utils.getStatusBadgeClass(animal.Current_Status);
        const imageUrl = animal.Image_URL || 'assets/images/placeholder-animal.svg';

        return `
            <div class="card card-hover animal-card" onclick="Router.navigate('/animals/${animal.AnimalID}')">
                <div class="animal-card-image">
                    <img src="${imageUrl}" alt="${animal.Name}" onerror="this.src='assets/images/placeholder-animal.svg'">
                    <span class="badge ${statusClass}" style="position: absolute; top: 12px; right: 12px;">
                        ${animal.Current_Status}
                    </span>
                </div>
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="card-title" style="margin: 0;">${animal.Name}</h3>
                        <span class="badge badge-gray">${animal.Type}</span>
                    </div>
                    <p class="text-secondary" style="font-size: var(--text-sm); margin-bottom: 12px;">
                        ${animal.Breed || 'Unknown breed'} • ${animal.Gender} • ${animal.Age_Group || 'Unknown age'}
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="text-tertiary" style="font-size: var(--text-xs);">
                            Intake: ${Utils.formatDate(animal.Intake_Date, 'short')}
                        </span>
                        ${animal.Current_Status === 'Available' ? `
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); AnimalsPage.requestAdoption(${animal.AnimalID})">
                                Adopt
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render user card
     * @param {Object} user
     * @returns {string}
     */
    user(user) {
        const statusClass = Utils.getStatusBadgeClass(user.Account_Status);
        const fullName = `${user.FirstName} ${user.LastName}`;

        return `
            <div class="card card-hover user-card" style="cursor: pointer;" onclick="UsersPage.showDetail(${user.UserID})">
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        <div class="avatar avatar-lg" style="background: ${Utils.stringToColor(fullName)}">
                            ${Utils.getInitials(fullName)}
                        </div>
                        <div class="flex-1">
                            <h3 style="font-weight: var(--font-semibold); margin-bottom: 4px;">${fullName}</h3>
                            <p class="text-secondary" style="font-size: var(--text-sm);">${user.Email}</p>
                        </div>
                        <span class="badge ${statusClass}">${user.Account_Status}</span>
                    </div>
                    <div class="flex items-center justify-between mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                        <span class="badge badge-gray">${user.Role_Name}</span>
                        <span class="text-tertiary" style="font-size: var(--text-xs);">
                            Joined ${Utils.formatDate(user.Created_At, 'short')}
                        </span>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render adoption request card
     * @param {Object} request
     * @returns {string}
     */
    adoption(request) {
        const statusClass = Utils.getStatusBadgeClass(request.Status);

        return `
            <div class="card card-hover" style="cursor: pointer;" onclick="AdoptionsPage.showDetail(${request.RequestID})">
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 style="font-weight: var(--font-semibold);">${request.Animal_Name}</h4>
                            <p class="text-secondary" style="font-size: var(--text-sm);">
                                ${request.Animal_Type} • ${request.Breed || 'Unknown breed'}
                            </p>
                        </div>
                        <span class="badge ${statusClass}">${request.Status}</span>
                    </div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="avatar avatar-sm" style="background: ${Utils.stringToColor(request.Adopter_Email)}">
                            ${Utils.getInitials(`${request.FirstName} ${request.LastName}`)}
                        </div>
                        <div>
                            <p style="font-size: var(--text-sm); font-weight: var(--font-medium);">
                                ${request.FirstName} ${request.LastName}
                            </p>
                            <p class="text-tertiary" style="font-size: var(--text-xs);">${request.Email}</p>
                        </div>
                    </div>
                    <div class="text-tertiary" style="font-size: var(--text-xs);">
                        Requested ${Utils.timeAgo(request.Request_Date)}
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render inventory item card
     * @param {Object} item
     * @returns {string}
     */
    inventory(item) {
        const isLowStock = item.Quantity_On_Hand <= item.Reorder_Level;
        const isExpiringSoon = item.Is_Expiring_Soon;
        const stockPercentage = item.Reorder_Level > 0
            ? Math.min((item.Quantity_On_Hand / item.Reorder_Level) * 100, 100)
            : 100;

        let progressClass = 'primary';
        if (stockPercentage <= 25) progressClass = 'danger';
        else if (stockPercentage <= 50) progressClass = 'warning';
        else if (stockPercentage >= 100) progressClass = 'success';

        return `
            <div class="card card-hover">
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 style="font-weight: var(--font-semibold);">${item.Item_Name}</h4>
                            <span class="badge badge-gray">${item.Category}</span>
                        </div>
                        ${isLowStock ? '<span class="badge badge-danger">Low Stock</span>' : ''}
                        ${isExpiringSoon ? '<span class="badge badge-warning">Expiring Soon</span>' : ''}
                    </div>
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-secondary" style="font-size: var(--text-sm);">Stock Level</span>
                            <span style="font-weight: var(--font-semibold);">${item.Quantity_On_Hand} / ${item.Reorder_Level}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar ${progressClass}" style="width: ${stockPercentage}%"></div>
                        </div>
                    </div>
                    ${item.Expiration_Date ? `
                        <p class="text-tertiary" style="font-size: var(--text-xs);">
                            Expires: ${Utils.formatDate(item.Expiration_Date, 'medium')}
                        </p>
                    ` : ''}
                </div>
            </div>
        `;
    },

    /**
     * Render activity card
     * @param {Object} activity
     * @returns {string}
     */
    activity(activity) {
        const iconMap = {
            'LOGIN': '🔐',
            'CREATE_ANIMAL': '🐕',
            'UPDATE_ANIMAL': '✏️',
            'CREATE_ADOPTION': '📝',
            'PROCESS_ADOPTION': '✅',
            'CREATE_MEDICAL': '💉',
            'ADJUST_INVENTORY': '📦',
            'CREATE_INVOICE': '💰',
            'RECORD_PAYMENT': '💳'
        };

        const icon = iconMap[activity.Action_Type] || '📌';

        return `
            <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-hover" style="transition: background-color 0.2s;">
                <div style="font-size: 20px;">${icon}</div>
                <div class="flex-1 min-w-0">
                    <p style="font-size: var(--text-sm);">
                        <strong>${activity.FirstName || 'System'} ${activity.LastName || ''}</strong>
                        <span class="text-secondary"> ${this.formatActivityAction(activity.Action_Type)}</span>
                    </p>
                    ${activity.Description ? `
                        <p class="text-tertiary truncate" style="font-size: var(--text-xs); margin-top: 2px;">
                            ${activity.Description}
                        </p>
                    ` : ''}
                    <p class="text-tertiary" style="font-size: var(--text-xs); margin-top: 4px;">
                        ${Utils.timeAgo(activity.Log_Date)}
                    </p>
                </div>
            </div>
        `;
    },

    /**
     * Format activity action type
     * @param {string} actionType
     * @returns {string}
     */
    formatActivityAction(actionType) {
        const actionMap = {
            'LOGIN': 'logged in',
            'LOGOUT': 'logged out',
            'CREATE_ANIMAL': 'added a new animal',
            'UPDATE_ANIMAL': 'updated an animal',
            'DELETE_ANIMAL': 'deleted an animal',
            'CREATE_ADOPTION': 'submitted an adoption request',
            'PROCESS_ADOPTION': 'processed an adoption',
            'CREATE_MEDICAL': 'added a medical record',
            'CREATE_INVENTORY': 'added an inventory item',
            'ADJUST_INVENTORY': 'adjusted inventory',
            'CREATE_INVOICE': 'created an invoice',
            'RECORD_PAYMENT': 'recorded a payment'
        };

        return actionMap[actionType] || actionType.toLowerCase().replace(/_/g, ' ');
    },

    /**
     * Render empty card
     * @param {Object} options
     * @returns {string}
     */
    empty(options = {}) {
        const {
            icon = '📭',
            title = 'No data found',
            description = 'There are no items to display.',
            action = null
        } = options;

        return `
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state-icon" style="font-size: 48px;">${icon}</div>
                    <h3 class="empty-state-title">${title}</h3>
                    <p class="empty-state-description">${description}</p>
                    ${action ? `
                        <button class="btn btn-primary" onclick="${action.onClick}">
                            ${action.label}
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }
};

// Add card-specific styles
const cardStyles = document.createElement('style');
cardStyles.textContent = `
    .animal-card {
        cursor: pointer;
    }
    
    .animal-card-image {
        position: relative;
        height: 240px;
        overflow: hidden;
        background-color: var(--bg-secondary);
    }
    
    .animal-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .animal-card:hover .animal-card-image img {
        transform: scale(1.05);
    }
`;

document.head.appendChild(cardStyles);

// Make Card globally available
window.Card = Card;