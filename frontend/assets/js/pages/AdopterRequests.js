/**
 * Adopter Requests Page
 * Adopter's adoption requests tracking
 * 
 * @package AnimalShelter
 */

const AdopterRequestsPage = {
    /**
     * Page state
     */
    state: {
        requests: [],
        loading: false
    },

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">My Adoption Requests</h1>
                    <p class="page-subtitle">Track the status of your adoption applications</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="Router.navigate('/animals')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        Browse Animals
                    </button>
                </div>
            </div>
            
            <!-- Requests Container -->
            <div id="requests-container">
                ${Loading.skeleton('table', { rows: 5, cols: 4 })}
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        await this.loadRequests();
    },

    /**
     * Load adoption requests
     */
    async loadRequests() {
        this.state.loading = true;
        const container = document.getElementById('requests-container');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 4 });

        try {
            const user = Auth.currentUser();
            const response = await API.adoptions.list({ adopter_id: user?.id });

            if (response.success) {
                this.state.requests = Array.isArray(response.data) ? response.data : (response.data.data || []);
                this.state.loading = false;
                this.renderRequests();
            }
        } catch (error) {
            console.error('Failed to load requests:', error);
            Toast.error('Failed to load your adoption requests');
            this.state.loading = false;
            this.renderRequests();
        }
    },

    /**
     * Render requests
     */
    renderRequests() {
        const container = document.getElementById('requests-container');
        if (!container) return;

        if (this.state.requests.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">📋</div>
                    <h3 class="empty-state-title">No adoption requests yet</h3>
                    <p class="empty-state-description">
                        You haven't submitted any adoption requests. Browse our animals to find your new companion!
                    </p>
                    <button class="btn btn-primary mt-4" onclick="Router.navigate('/animals')">
                        Browse Animals
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="space-y-4">
                ${this.state.requests.map(request => this.renderRequestCard(request)).join('')}
            </div>
        `;
    },

    /**
     * Render single request card
     * @param {Object} request
     */
    renderRequestCard(request) {
        const statusClass = Utils.getStatusBadgeClass(request.Status);
        const placeholder = Utils.getAnimalPlaceholder(request.Animal_Type);
        const canCancel = ['Pending', 'Interview Scheduled', 'Seminar Scheduled'].includes(request.Status);

        let scheduledInfo = '';
        if (request.Status === 'Interview Scheduled' && request.Interview_Date) {
            scheduledInfo = `
                <div class="mt-3 p-3 bg-warning-subtle rounded-lg">
                    <p class="font-semibold text-warning text-sm">📅 Interview Scheduled</p>
                    <p class="font-medium">${Utils.formatDateTime(request.Interview_Date)}</p>
                </div>
            `;
        } else if (request.Status === 'Seminar Scheduled' && request.Seminar_Date) {
            scheduledInfo = `
                <div class="mt-3 p-3 bg-primary-subtle rounded-lg">
                    <p class="font-semibold text-primary text-sm">📅 Seminar Scheduled</p>
                    <p class="font-medium">${Utils.formatDateTime(request.Seminar_Date)}</p>
                </div>
            `;
        }

        return `
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        <img src="${request.Image_URL || placeholder}" 
                             alt="${request.Animal_Name}"
                             style="width: 80px; height: 80px; border-radius: var(--radius-lg); object-fit: cover;"
                             onerror="this.src='${placeholder}'">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h3 class="font-semibold text-lg">${request.Animal_Name}</h3>
                                    <p class="text-secondary text-sm">${request.Animal_Type} • ${request.Breed || 'Unknown breed'}</p>
                                </div>
                                <span class="badge ${statusClass}">${request.Status}</span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-tertiary">
                                <span>Requested: ${Utils.formatDate(request.Request_Date)}</span>
                                ${request.Updated_At ? `<span>Updated: ${Utils.timeAgo(request.Updated_At)}</span>` : ''}
                            </div>
                            ${request.Staff_Comments ? `
                                <div class="mt-2 p-2 bg-secondary rounded text-sm">
                                    <span class="text-tertiary">Staff note:</span> ${request.Staff_Comments}
                                </div>
                            ` : ''}
                            ${scheduledInfo}
                        </div>
                        <div class="flex flex-col gap-2">
                            <button class="btn btn-sm btn-ghost" onclick="AdopterRequestsPage.viewDetails(${request.RequestID})">
                                View Details
                            </button>
                            ${canCancel ? `
                                <button class="btn btn-sm btn-ghost text-danger" onclick="AdopterRequestsPage.cancelRequest(${request.RequestID}, '${request.Animal_Name}')">
                                    Cancel
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * View request details
     * @param {number} id
     */
    async viewDetails(id) {
        try {
            const response = await API.adoptions.get(id);

            if (response.success) {
                const adoption = response.data;
                const statusClass = Utils.getStatusBadgeClass(adoption.Status);
                const placeholder = Utils.getAnimalPlaceholder(adoption.Animal_Type);

                Modal.open({
                    title: 'Adoption Request Details',
                    size: 'lg',
                    content: `
                        <div class="space-y-6">
                            <!-- Animal Info -->
                            <div class="flex items-center gap-4 p-4 bg-secondary rounded-lg">
                                <img src="${adoption.Image_URL || placeholder}" 
                                     alt="${adoption.Animal_Name}"
                                     style="width: 80px; height: 80px; border-radius: var(--radius-lg); object-fit: cover;"
                                     onerror="this.src='${placeholder}'">
                                <div>
                                    <h3 class="font-semibold text-lg">${adoption.Animal_Name}</h3>
                                    <p class="text-secondary">${adoption.Animal_Type} • ${adoption.Breed || 'Unknown breed'}</p>
                                    <span class="badge ${Utils.getStatusBadgeClass(adoption.Animal_Status)} mt-2">${adoption.Animal_Status}</span>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div>
                                <h4 class="font-semibold mb-3">Request Status</h4>
                                <div class="flex items-center gap-4">
                                    <span class="badge ${statusClass}" style="font-size: var(--text-sm); padding: 8px 16px;">${adoption.Status}</span>
                                </div>
                                ${adoption.Status === 'Seminar Scheduled' && adoption.Seminar_Date ? `
                                    <div class="mt-4 p-3 bg-primary-subtle rounded-lg">
                                        <p class="font-semibold text-primary">📅 Seminar Scheduled</p>
                                        <p class="text-lg font-medium">${Utils.formatDateTime(adoption.Seminar_Date)}</p>
                                    </div>
                                ` : ''}
                                ${adoption.Status === 'Interview Scheduled' && adoption.Interview_Date ? `
                                    <div class="mt-4 p-3 bg-warning-subtle rounded-lg">
                                        <p class="font-semibold text-warning">📅 Interview Scheduled</p>
                                        <p class="text-lg font-medium">${Utils.formatDateTime(adoption.Interview_Date)}</p>
                                    </div>
                                ` : ''}
                                ${adoption.Staff_Comments ? `
                                    <div class="mt-4 p-3 bg-secondary rounded-lg">
                                        <p class="text-tertiary text-sm">Staff Comments</p>
                                        <p>${adoption.Staff_Comments}</p>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <!-- Timeline -->
                            <div>
                                <h4 class="font-semibold mb-3">Timeline</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-tertiary">Request Submitted</span>
                                        <span>${Utils.formatDateTime(adoption.Request_Date)}</span>
                                    </div>
                                    ${adoption.Updated_At ? `
                                        <div class="flex justify-between">
                                            <span class="text-tertiary">Last Updated</span>
                                            <span>${Utils.formatDateTime(adoption.Updated_At)}</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `,
                    footer: `<button class="btn btn-secondary" data-action="cancel">Close</button>`
                });
            }
        } catch (error) {
            Toast.error('Failed to load request details');
        }
    },

    /**
     * Cancel adoption request
     * @param {number} id
     * @param {string} animalName
     */
    async cancelRequest(id, animalName) {
        const confirmed = await Modal.confirm(
            `Are you sure you want to cancel your adoption request for ${animalName}?`,
            'Cancel Request'
        );

        if (confirmed) {
            try {
                const response = await API.adoptions.cancel(id);
                if (response.success) {
                    Toast.success('Adoption request cancelled');
                    this.loadRequests();
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to cancel request');
            }
        }
    }
};

// Make AdopterRequestsPage globally available
window.AdopterRequestsPage = AdopterRequestsPage;
