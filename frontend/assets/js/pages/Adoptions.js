/**
 * Adoptions Page
 * Adoption request management
 * 
 * @package AnimalShelter
 */

const AdoptionsPage = {
    /**
     * Page state
     */
    state: {
        adoptions: [],
        pagination: { page: 1, perPage: 20, total: 0 },
        filters: {
            status: '',
            search: ''
        },
        loading: false
    },

    /**
     * Status options
     */
    statuses: ['Pending', 'Interview Scheduled', 'Approved', 'Rejected', 'Completed', 'Cancelled'],

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        const isStaff = Auth.isStaff();

        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">${isStaff ? 'Adoption Requests' : 'My Adoptions'}</h1>
                    <p class="page-subtitle">${isStaff ? 'Manage adoption applications' : 'Track your adoption requests'}</p>
                </div>
            </div>
            
            <!-- Stats (Staff only) -->
            ${isStaff ? `
                <div class="stats-grid mb-6" id="adoption-stats">
                    ${Loading.skeleton('stats', { count: 4 })}
                </div>
            ` : ''}
            
            <!-- Filters -->
            <div class="card mb-6">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1" style="min-width: 200px;">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search by name or animal..." id="search-input">
                            </div>
                        </div>
                        
                        <select class="form-select" id="filter-status" style="width: auto;">
                            <option value="">All Statuses</option>
                            ${this.statuses.map(s => `<option value="${s}">${s}</option>`).join('')}
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Adoptions Table -->
            <div id="adoptions-container">
                ${Loading.skeleton('table', { rows: 5, cols: 6 })}
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        this.setupEventListeners();

        if (Auth.isStaff()) {
            await this.loadStats();
        }

        await this.loadAdoptions();
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Search
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.state.filters.search = e.target.value;
                this.state.pagination.page = 1;
                this.loadAdoptions();
            }, 300));
        }

        // Status filter
        const statusFilter = document.getElementById('filter-status');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.state.filters.status = e.target.value;
                this.state.pagination.page = 1;
                this.loadAdoptions();
            });
        }
    },

    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const response = await API.adoptions.statistics();

            if (response.success) {
                this.renderStats(response.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    },

    /**
     * Render statistics
     * @param {Object} stats
     */
    renderStats(stats) {
        const container = document.getElementById('adoption-stats');
        if (!container) return;

        container.innerHTML = `
            ${Card.stat({
            title: 'Pending',
            value: stats.pending || 0,
            iconColor: 'warning',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
        })}
            ${Card.stat({
            title: 'Approved',
            value: stats.approved || 0,
            iconColor: 'success',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
        })}
            ${Card.stat({
            title: 'Completed',
            value: stats.completed || 0,
            iconColor: 'primary',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>'
        })}
            ${Card.stat({
            title: 'Total Requests',
            value: stats.total || 0,
            iconColor: 'info',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'
        })}
        `;
    },

    /**
     * Load adoptions
     */
    async loadAdoptions() {
        this.state.loading = true;
        const container = document.getElementById('adoptions-container');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 6 });

        try {
            const params = {
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage,
                ...this.state.filters
            };

            // If not staff, only show own adoptions
            if (!Auth.isStaff()) {
                params.adopter_id = Auth.currentUser()?.id;
            }

            // Remove empty filters
            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            const response = await API.adoptions.list(params);

            if (response.success) {
                this.state.adoptions = Array.isArray(response.data) ? response.data : (response.data.data || []);

                // Handle pagination
                if (response.pagination) {
                    this.state.pagination.total = response.pagination.total_items;
                } else if (response.data && response.data.pagination) {
                    this.state.pagination.total = response.data.pagination.total;
                } else {
                    this.state.pagination.total = this.state.adoptions.length;
                }

                this.state.loading = false;
                this.renderAdoptions();
            }
        } catch (error) {
            console.error('Failed to load adoptions:', error);
            Toast.error('Failed to load adoptions');
            this.state.loading = false;
            this.renderAdoptions();
        }
    },

    /**
     * Render adoptions table
     */
    renderAdoptions() {
        const container = document.getElementById('adoptions-container');
        if (!container) return;

        if (this.state.adoptions.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">📋</div>
                    <h3 class="empty-state-title">No adoption requests found</h3>
                    <p class="empty-state-description">
                        ${Auth.isStaff()
                    ? 'There are no adoption requests matching your filters.'
                    : 'You haven\'t made any adoption requests yet. Browse our animals to find your new companion!'}
                    </p>
                    ${!Auth.isStaff() ? `
                        <button class="btn btn-primary mt-4" onclick="Router.navigate('/animals')">
                            Browse Animals
                        </button>
                    ` : ''}
                </div>
            `;
            return;
        }

        const isStaff = Auth.isStaff();

        container.innerHTML = DataTable.render({
            id: 'adoptions-table',
            columns: [
                {
                    key: 'Animal_Name',
                    label: 'Animal',
                    render: (val, row) => {
                        const placeholder = Utils.getAnimalPlaceholder(row.Animal_Type);
                        return `
                        <div class="flex items-center gap-3">
                            <img src="${row.Image_URL || placeholder}" 
                                 alt="${val}" 
                                 style="width: 40px; height: 40px; border-radius: var(--radius-md); object-fit: cover;"
                                 onerror="this.src='${placeholder}'">
                            <div>
                                <p class="font-semibold">${val}</p>
                                <p class="text-tertiary text-xs">${row.Animal_Type} • ${row.Breed || ''}</p>
                            </div>
                        </div>
                    `}
                },
                ...(isStaff ? [{
                    key: 'FirstName',
                    label: 'Applicant',
                    render: (val, row) => `
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-sm" style="background: ${Utils.stringToColor(row.Email || '')}">
                                ${Utils.getInitials(`${val} ${row.LastName}`)}
                            </div>
                            <div>
                                <p class="font-medium">${val} ${row.LastName}</p>
                                <p class="text-tertiary text-xs">${row.Email}</p>
                            </div>
                        </div>
                    `
                }] : []),
                { key: 'Request_Date', label: 'Request Date', type: 'date' },
                { key: 'Status', label: 'Status', type: 'badge' },
                {
                    key: 'Updated_At',
                    label: 'Last Updated',
                    render: val => val ? Utils.timeAgo(val) : '-'
                }
            ],
            data: this.state.adoptions.map(a => ({ ...a, id: a.RequestID })),
            pagination: this.state.pagination,
            actions: isStaff ? {
                view: true,
                custom: [
                    {
                        name: 'process',
                        label: 'Process',
                        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>'
                    }
                ]
            } : {
                view: true
            },
            onAction: (action, id, row) => this.handleAction(action, id, row),
            onPageChange: (page) => {
                this.state.pagination.page = page;
                this.loadAdoptions();
            },
            sortable: false
        });
    },

    /**
     * Handle table actions
     * @param {string} action
     * @param {number} id
     * @param {Object} row
     */
    async handleAction(action, id, row) {
        switch (action) {
            case 'view':
                this.showDetail(id);
                break;
            case 'process':
                this.showProcessModal(row);
                break;
        }
    },

    /**
     * Show adoption detail
     * @param {number} id
     */
    async showDetail(id) {
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
                            
                            <!-- Applicant Info -->
                            <div>
                                <h4 class="font-semibold mb-3">Applicant Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-tertiary text-sm">Name</p>
                                        <p class="font-medium">${adoption.Adopter_FirstName} ${adoption.Adopter_LastName}</p>
                                    </div>
                                    <div>
                                        <p class="text-tertiary text-sm">Email</p>
                                        <p class="font-medium">${adoption.Adopter_Email}</p>
                                    </div>
                                    <div>
                                        <p class="text-tertiary text-sm">Contact</p>
                                        <p class="font-medium">${adoption.Adopter_Contact || '-'}</p>
                                    </div>
                                    <div>
                                        <p class="text-tertiary text-sm">Request Date</p>
                                        <p class="font-medium">${Utils.formatDateTime(adoption.Request_Date)}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div>
                                <h4 class="font-semibold mb-3">Request Status</h4>
                                <div class="flex items-center gap-4">
                                    <span class="badge ${statusClass}" style="font-size: var(--text-sm); padding: 8px 16px;">${adoption.Status}</span>
                                    ${adoption.Staff_Comments ? `
                                        <span class="text-secondary">- ${adoption.Staff_Comments}</span>
                                    ` : ''}
                                </div>
                                ${adoption.Staff_FirstName ? `
                                    <p class="text-tertiary text-sm mt-2">
                                        Processed by ${adoption.Staff_FirstName} ${adoption.Staff_LastName}
                                    </p>
                                ` : ''}
                            </div>
                        </div>
                    `,
                    footer: Auth.isStaff() && ['Pending', 'Interview Scheduled', 'Approved'].includes(adoption.Status) ? `
                        <button class="btn btn-secondary" data-action="cancel">Close</button>
                        <button class="btn btn-primary" onclick="Modal.closeAll(); AdoptionsPage.showProcessModal(${JSON.stringify(adoption).replace(/"/g, '&quot;')})">
                            Process Request
                        </button>
                    ` : `<button class="btn btn-secondary" data-action="cancel">Close</button>`
                });
            }
        } catch (error) {
            Toast.error('Failed to load adoption details');
        }
    },

    /**
     * Show process modal
     * @param {Object} adoption
     */
    showProcessModal(adoption) {
        const currentStatus = adoption.Status;
        const availableStatuses = this.getAvailableStatuses(currentStatus);

        Modal.open({
            title: 'Process Adoption Request',
            content: `
                <form id="process-form">
                    <div class="mb-4 p-4 bg-secondary rounded-lg">
                        <p class="text-sm text-secondary">Processing request for <strong>${adoption.Animal_Name}</strong> by <strong>${adoption.FirstName || adoption.Adopter_FirstName} ${adoption.LastName || adoption.Adopter_LastName}</strong></p>
                        <p class="text-sm text-secondary mt-1">Current status: <span class="badge ${Utils.getStatusBadgeClass(currentStatus)}">${currentStatus}</span></p>
                    </div>
                    
                    ${Form.generate([
                {
                    type: 'select',
                    name: 'status',
                    label: 'New Status',
                    required: true,
                    options: availableStatuses.map(s => ({ value: s, label: s }))
                }
            ])}
                    
                    <div id="interview-date-container" class="form-group" style="display: none;">
                        <label class="form-label" for="interview_date">Interview Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" 
                               id="interview_date" 
                               name="interview_date" 
                               class="form-input"
                               min="${new Date().toISOString().slice(0, 16)}">
                        <span class="form-hint">Select when the interview will be held</span>
                    </div>
                    
                    ${Form.generate([
                {
                    type: 'textarea',
                    name: 'comments',
                    label: 'Comments',
                    placeholder: 'Add any notes or comments...',
                    rows: 3
                }
            ])}
                </form>
            `,
            confirmText: 'Update Status',
            onConfirm: async () => {
                const form = document.getElementById('process-form');
                const statusSelect = form.querySelector('[name="status"]');
                const interviewDateInput = form.querySelector('[name="interview_date"]');

                // Validate interview date if Interview Scheduled is selected
                if (statusSelect.value === 'Interview Scheduled' && !interviewDateInput.value) {
                    Toast.error('Please select an interview date and time');
                    interviewDateInput.focus();
                    return false;
                }

                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                try {
                    const requestData = {
                        status: data.status,
                        comments: data.comments
                    };

                    // Include interview date if applicable
                    if (data.status === 'Interview Scheduled' && data.interview_date) {
                        requestData.interview_date = data.interview_date;
                    }

                    const response = await API.adoptions.process(adoption.RequestID || adoption.id, requestData);

                    if (response.success) {
                        Toast.success('Adoption request updated successfully');
                        this.loadAdoptions();
                        if (Auth.isStaff()) this.loadStats();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to update adoption request');
                    return false;
                }
            }
        });

        // Setup dynamic show/hide for interview date field
        setTimeout(() => {
            const statusSelect = document.querySelector('#process-form [name="status"]');
            const interviewContainer = document.getElementById('interview-date-container');

            if (statusSelect && interviewContainer) {
                // Check initial value
                if (statusSelect.value === 'Interview Scheduled') {
                    interviewContainer.style.display = 'block';
                }

                // Listen for changes
                statusSelect.addEventListener('change', (e) => {
                    if (e.target.value === 'Interview Scheduled') {
                        interviewContainer.style.display = 'block';
                    } else {
                        interviewContainer.style.display = 'none';
                    }
                });
            }
        }, 100);
    },

    /**
     * Get available status transitions
     * @param {string} currentStatus
     * @returns {Array}
     */
    getAvailableStatuses(currentStatus) {
        const transitions = {
            'Pending': ['Interview Scheduled', 'Approved', 'Rejected'],
            'Interview Scheduled': ['Approved', 'Rejected'],
            'Approved': ['Completed', 'Cancelled'],
            'Rejected': [],
            'Completed': [],
            'Cancelled': []
        };

        return transitions[currentStatus] || [];
    }
};

// Make AdoptionsPage globally available
window.AdoptionsPage = AdoptionsPage;