/**
 * Medical Page
 * Medical records management
 * 
 * @package AnimalShelter
 */

const MedicalPage = {
    /**
     * Page state
     */
    state: {
        records: [],
        pagination: { page: 1, perPage: 20, total: 0 },
        filters: {
            animal_id: '',
            vet_id: '',
            diagnosis_type: '',
            date_from: '',
            date_to: '',
            search: ''
        },
        loading: false,
        animals: [],
        veterinarians: [],
        activeTab: 'all' // 'all', 'upcoming', 'overdue'
    },

    /**
     * Diagnosis types
     */
    diagnosisTypes: [
        'Checkup',
        'Vaccination',
        'Surgery',
        'Treatment',
        'Emergency',
        'Deworming',
        'Spay/Neuter'
    ],

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Medical Records</h1>
                    <p class="page-subtitle">Manage animal health records and treatments</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" onclick="MedicalPage.exportMedicalRecords()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Export PDF
                    </button>
                    <button class="btn btn-primary" onclick="MedicalPage.showAddModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Record
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid mb-6" id="medical-stats">
                ${Loading.skeleton('stats', { count: 4 })}
            </div>
            
            <!-- Tabs -->
            <div class="tabs-pills mb-6" id="medical-tabs">
                <button class="tab active" data-tab="all">All Records</button>
                <button class="tab" data-tab="upcoming">
                    Upcoming
                    <span class="badge badge-warning ml-2" id="upcoming-count" style="display: none;"></span>
                </button>
                <button class="tab" data-tab="overdue">
                    Overdue
                    <span class="badge badge-danger ml-2" id="overdue-count" style="display: none;"></span>
                </button>
            </div>
            
            <!-- Filters -->
            <div class="card mb-6" id="medical-filters-card">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4" id="filters-container">
                        <div class="flex-1" style="min-width: 200px;">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search records..." id="search-input">
                            </div>
                        </div>

                        <select class="form-select" id="filter-type" style="width: auto;">
                            <option value="">All Types</option>
                            ${this.diagnosisTypes.map(t => `<option value="${t}">${t}</option>`).join('')}
                        </select>
                        
                        <select class="form-select" id="filter-animal" style="width: auto;">
                            <option value="">All Animals</option>
                        </select>
                        
                        <select class="form-select" id="filter-vet" style="width: auto;">
                            <option value="">All Veterinarians</option>
                        </select>
                        
                        <button class="btn btn-ghost" onclick="MedicalPage.showDateFilter()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            Date Range
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Records -->
            <div class="card">
                <div id="records-container">
                    ${Loading.skeleton('table', { rows: 5, cols: 7 })}
                </div>
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        await Promise.all([
            this.loadDropdownData(),
            this.loadStats(),
            this.loadRecords()
        ]);

        this.setupEventListeners();
    },

    /**
     * Load dropdown data (animals, vets)
     */
    async loadDropdownData() {
        try {
            const [animalsResponse, vetsResponse] = await Promise.all([
                API.animals.list({ per_page: 1000 }),
                API.medical.veterinarians()
            ]);

            if (animalsResponse.success) {
                this.state.animals = animalsResponse.data.data || animalsResponse.data;
                this.populateAnimalDropdown();
            }

            if (vetsResponse.success) {
                this.state.veterinarians = vetsResponse.data;
                this.populateVetDropdown();
            }
        } catch (error) {
            console.error('Failed to load dropdown data:', error);
        }
    },

    /**
     * Populate animal dropdown
     */
    populateAnimalDropdown() {
        const select = document.getElementById('filter-animal');
        if (!select) return;

        select.innerHTML = `
            <option value="">All Animals</option>
            ${this.state.animals.map(a => `
                <option value="${a.AnimalID}">${a.Name} (${a.Type})</option>
            `).join('')}
        `;
    },

    /**
     * Populate veterinarian dropdown
     */
    populateVetDropdown() {
        const select = document.getElementById('filter-vet');
        if (!select) return;

        select.innerHTML = `
            <option value="">All Veterinarians</option>
            ${this.state.veterinarians.map(v => `
                <option value="${v.VetID}">Dr. ${v.FirstName} ${v.LastName}</option>
            `).join('')}
        `;
    },

    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const [statsResponse, upcomingResponse, overdueResponse] = await Promise.all([
                API.get('/medical/stats/summary'),
                API.medical.upcoming(7),
                API.medical.overdue()
            ]);

            const stats = statsResponse.success ? statsResponse.data : {};
            const upcoming = upcomingResponse.success ? upcomingResponse.data : [];
            const overdue = overdueResponse.success ? overdueResponse.data : [];

            // Update badge counts
            const upcomingBadge = document.getElementById('upcoming-count');
            const overdueBadge = document.getElementById('overdue-count');

            if (upcomingBadge && upcoming.length > 0) {
                upcomingBadge.textContent = upcoming.length;
                upcomingBadge.style.display = 'inline-flex';
            }

            if (overdueBadge && overdue.length > 0) {
                overdueBadge.textContent = overdue.length;
                overdueBadge.style.display = 'inline-flex';
            }

            this.renderStats({
                ...stats,
                upcoming_count: upcoming.length,
                overdue_count: overdue.length
            });

            // Store for tab switching
            this.state.upcomingRecords = upcoming;
            this.state.overdueRecords = overdue;

        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    },

    /**
     * Render statistics
     * @param {Object} stats
     */
    renderStats(stats) {
        const container = document.getElementById('medical-stats');
        if (!container) return;

        container.innerHTML = `
            ${Card.stat({
            title: 'Total Records',
            value: stats.total || 0,
            iconColor: 'primary',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>'
        })}
            ${Card.stat({
            title: 'This Month',
            value: stats.this_month || 0,
            iconColor: 'success',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
        })}
            ${Card.stat({
            title: 'Upcoming (7 days)',
            value: stats.upcoming_count || 0,
            iconColor: 'warning',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
        })}
            ${Card.stat({
            title: 'Overdue',
            value: stats.overdue_count || 0,
            iconColor: 'danger',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
        })}
        `;
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
                this.loadRecords();
            }, 300));
        }

        // Filter selects
        ['type', 'animal', 'vet'].forEach(filter => {
            const select = document.getElementById(`filter-${filter}`);
            if (select) {
                select.addEventListener('change', (e) => {
                    const filterKey = filter === 'type' ? 'diagnosis_type' :
                        filter === 'animal' ? 'animal_id' : 'vet_id';
                    this.state.filters[filterKey] = e.target.value;
                    this.state.pagination.page = 1;
                    this.loadRecords();
                });
            }
        });

        // Tabs
        const tabs = document.getElementById('medical-tabs');
        if (tabs) {
            tabs.addEventListener('click', (e) => {
                const tab = e.target.closest('.tab');
                if (tab) {
                    tabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    this.state.activeTab = tab.dataset.tab;
                    this.handleTabChange(tab.dataset.tab);
                }
            });
        }
    },

    /**
     * Handle tab change
     * @param {string} tab
     */
    handleTabChange(tab) {
        const filtersCard = document.getElementById('medical-filters-card');

        switch (tab) {
            case 'all':
                if (filtersCard) filtersCard.style.display = 'block';
                this.loadRecords();
                break;
            case 'upcoming':
                if (filtersCard) filtersCard.style.display = 'none';
                this.renderUpcomingRecords();
                break;
            case 'overdue':
                if (filtersCard) filtersCard.style.display = 'none';
                this.renderOverdueRecords();
                break;
        }
    },

    /**
     * Load medical records
     */
    async loadRecords() {
        if (this.state.activeTab !== 'all') return;

        this.state.loading = true;
        const container = document.getElementById('records-container');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 7 });

        try {
            const params = {
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage,
                ...this.state.filters
            };

            // Remove empty filters
            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            const response = await API.medical.list(params);

            if (response.success) {
                this.state.records = response.data.data || response.data;
                this.state.pagination.total = response.data.pagination?.total || this.state.records.length;
                this.renderRecords();
            }
        } catch (error) {
            console.error('Failed to load records:', error);
            Toast.error('Failed to load medical records');
        } finally {
            this.state.loading = false;
        }
    },

    /**
     * Render medical records table
     */
    renderRecords() {
        const container = document.getElementById('records-container');
        if (!container) return;

        if (this.state.records.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">💉</div>
                    <h3 class="empty-state-title">No medical records found</h3>
                    <p class="empty-state-description">Add medical records to track animal health.</p>
                    <button class="btn btn-primary mt-4" onclick="MedicalPage.showAddModal()">
                        Add Record
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = DataTable.render({
            id: 'medical-table',
            columns: [
                {
                    key: 'Animal_Name',
                    label: 'Animal',
                    render: (val, row) => `
                        <div>
                            <p class="font-semibold">${val}</p>
                            <p class="text-tertiary text-xs">${row.Animal_Type}</p>
                        </div>
                    `
                },
                {
                    key: 'Diagnosis_Type',
                    label: 'Type',
                    render: val => `<span class="badge badge-primary">${val}</span>`
                },
                {
                    key: 'Vaccine_Name',
                    label: 'Vaccine/Treatment',
                    render: val => val || '-'
                },
                {
                    key: 'Date_Performed',
                    label: 'Date',
                    type: 'date'
                },
                {
                    key: 'Vet_FirstName',
                    label: 'Veterinarian',
                    render: (val, row) => `Dr. ${val} ${row.Vet_LastName}`
                },
                {
                    key: 'Next_Due_Date',
                    label: 'Next Due',
                    render: val => {
                        if (!val) return '-';
                        const date = new Date(val);
                        const today = new Date();
                        const isOverdue = date < today;
                        const badgeClass = isOverdue ? 'badge-danger' : 'badge-warning';
                        return `<span class="badge ${badgeClass}">${Utils.formatDate(val, 'short')}</span>`;
                    }
                }
            ],
            data: this.state.records.map(r => ({ ...r, id: r.RecordID })),
            pagination: this.state.pagination,
            actions: {
                view: true,
                edit: true,
                delete: Auth.isAdmin()
            },
            onAction: (action, id, row) => this.handleAction(action, id, row),
            onPageChange: (page) => {
                this.state.pagination.page = page;
                this.loadRecords();
            },
            sortable: false
        });
    },

    /**
     * Render upcoming records
     */
    renderUpcomingRecords() {
        const container = document.getElementById('records-container');
        if (!container) return;

        const records = this.state.upcomingRecords || [];

        if (records.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">✅</div>
                    <h3 class="empty-state-title">No upcoming treatments</h3>
                    <p class="empty-state-description">All animals are up to date with their treatments.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="p-4">
                ${records.map(record => `
                    <div class="flex items-center gap-4 p-4 mb-3 bg-warning-bg rounded-lg border border-warning" style="border-color: rgba(255, 149, 0, 0.3);">
                        <div class="avatar" style="background: var(--color-warning);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-semibold">${record.Animal_Name}</h4>
                                <span class="badge badge-warning">${record.Days_Until_Due} days</span>
                            </div>
                            <p class="text-secondary text-sm">${record.Diagnosis_Type}${record.Vaccine_Name ? `: ${record.Vaccine_Name}` : ''}</p>
                            <p class="text-tertiary text-xs mt-1">Due: ${Utils.formatDate(record.Next_Due_Date)}</p>
                        </div>
                        <button class="btn btn-sm btn-warning" onclick="MedicalPage.showFollowUpModal(${record.RecordID})">
                            Schedule
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
    },

    /**
     * Render overdue records
     */
    renderOverdueRecords() {
        const container = document.getElementById('records-container');
        if (!container) return;

        const records = this.state.overdueRecords || [];

        if (records.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">🎉</div>
                    <h3 class="empty-state-title">No overdue treatments</h3>
                    <p class="empty-state-description">Great job! All treatments are on schedule.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="p-4">
                ${records.map(record => `
                    <div class="flex items-center gap-4 p-4 mb-3 bg-danger-bg rounded-lg border border-danger" style="border-color: rgba(255, 59, 48, 0.3);">
                        <div class="avatar" style="background: var(--color-danger);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-semibold">${record.Animal_Name}</h4>
                                <span class="badge badge-danger">${record.Days_Overdue} days overdue</span>
                            </div>
                            <p class="text-secondary text-sm">${record.Diagnosis_Type}${record.Vaccine_Name ? `: ${record.Vaccine_Name}` : ''}</p>
                            <p class="text-tertiary text-xs mt-1">Was due: ${Utils.formatDate(record.Next_Due_Date)}</p>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="MedicalPage.showFollowUpModal(${record.RecordID})">
                            Add Record
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
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
                this.showDetailModal(row);
                break;
            case 'edit':
                this.showEditModal(row);
                break;
            case 'delete':
                await this.deleteRecord(id);
                break;
        }
    },

    /**
     * Show follow up modal
     * @param {number} recordId
     */
    showFollowUpModal(recordId) {
        const record = [...(this.state.upcomingRecords || []), ...(this.state.overdueRecords || [])]
            .find(r => r.RecordID === recordId);

        if (!record) return;

        const prefillData = {
            animal_id: record.AnimalID,
            diagnosis_type: record.Diagnosis_Type,
            vaccine_name: record.Vaccine_Name || ''
        };

        this.showAddModal(null, prefillData);
    },

    /**
     * Show add medical record modal
     * @param {number} animalId - Pre-selected animal ID
     * @param {Object} prefillData - Data to pre-fill
     */
    showAddModal(animalId = null, prefillData = {}) {
        const fields = this.getFormFields(animalId || prefillData.animal_id);

        // Merge animalId with prefillData
        const values = {
            ...prefillData,
            animal_id: animalId || prefillData.animal_id
        };

        // If user is a vet, pre-select them if not already set
        const user = Auth.currentUser();
        if (Auth.isVeterinarian() && !values.vet_id) {
            const vet = this.state.veterinarians.find(v => v.UserID == (user.id || user.UserID));
            if (vet) {
                values.vet_id = vet.VetID;
            }
        }

        Modal.open({
            title: 'Add Medical Record',
            content: `<form id="add-medical-form">${Form.generate(fields, values)}</form>`,
            size: 'lg',
            confirmText: 'Add Record',
            onConfirm: async () => {
                const form = document.getElementById('add-medical-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                try {
                    const response = await API.medical.create(data);
                    if (response.success) {
                        Toast.success('Medical record added successfully');
                        this.loadRecords();
                        this.loadStats();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to add medical record');
                    return false;
                }
            }
        });
    },

    /**
     * Show edit modal
     * @param {Object} record
     */
    showEditModal(record) {
        const fields = this.getFormFields(record.AnimalID);

        Modal.open({
            title: 'Edit Medical Record',
            content: `<form id="edit-medical-form">${Form.generate(fields, {
                animal_id: record.AnimalID,
                vet_id: record.VetID,
                diagnosis_type: record.Diagnosis_Type,
                vaccine_name: record.Vaccine_Name,
                treatment_notes: record.Treatment_Notes,
                date_performed: Utils.toInputDateTime(record.Date_Performed),
                next_due_date: record.Next_Due_Date ? Utils.toInputDate(record.Next_Due_Date) : ''
            })}</form>`,
            size: 'lg',
            confirmText: 'Save Changes',
            onConfirm: async () => {
                const form = document.getElementById('edit-medical-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                try {
                    const response = await API.medical.update(record.RecordID, data);
                    if (response.success) {
                        Toast.success('Medical record updated successfully');
                        this.loadRecords();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to update medical record');
                    return false;
                }
            }
        });
    },

    /**
     * Show detail modal
     * @param {Object} record
     */
    showDetailModal(record) {
        Modal.open({
            title: 'Medical Record Details',
            content: `
                <div class="space-y-6">
                    <div class="flex items-center gap-4 p-4 bg-secondary rounded-lg">
                        <div class="avatar avatar-lg" style="background: ${Utils.stringToColor(record.Animal_Name)}">
                            ${record.Animal_Name.charAt(0)}
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">${record.Animal_Name}</h3>
                            <p class="text-secondary">${record.Animal_Type}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-tertiary text-sm">Type</p>
                            <p class="font-medium">${record.Diagnosis_Type}</p>
                        </div>
                        <div>
                            <p class="text-tertiary text-sm">Date Performed</p>
                            <p class="font-medium">${Utils.formatDateTime(record.Date_Performed)}</p>
                        </div>
                        ${record.Vaccine_Name ? `
                            <div>
                                <p class="text-tertiary text-sm">Vaccine</p>
                                <p class="font-medium">${record.Vaccine_Name}</p>
                            </div>
                        ` : ''}
                        <div>
                            <p class="text-tertiary text-sm">Veterinarian</p>
                            <p class="font-medium">Dr. ${record.Vet_FirstName} ${record.Vet_LastName}</p>
                        </div>
                        ${record.Next_Due_Date ? `
                            <div class="col-span-2">
                                <p class="text-tertiary text-sm">Next Due Date</p>
                                <p class="font-medium">${Utils.formatDate(record.Next_Due_Date)}</p>
                            </div>
                        ` : ''}
                    </div>
                    
                    ${record.Treatment_Notes ? `
                        <div>
                            <p class="text-tertiary text-sm mb-2">Treatment Notes</p>
                            <div class="p-4 bg-secondary rounded-lg">
                                <p>${record.Treatment_Notes}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `,
            footer: `
                <button class="btn btn-secondary" data-action="cancel">Close</button>
                <button class="btn btn-primary" onclick="Modal.closeAll(); MedicalPage.showEditModal(${JSON.stringify(record).replace(/"/g, '&quot;')})">
                    Edit Record
                </button>
            `
        });
    },

    /**
     * Delete record
     * @param {number} id
     */
    async deleteRecord(id) {
        const confirmed = await Modal.confirmDelete('this medical record');

        if (confirmed) {
            try {
                const response = await API.medical.delete(id);
                if (response.success) {
                    Toast.success('Medical record deleted successfully');
                    this.loadRecords();
                    this.loadStats();
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to delete medical record');
            }
        }
    },

    /**
     * Show date filter modal
     */
    showDateFilter() {
        Modal.open({
            title: 'Filter by Date',
            content: `
                <form id="date-filter-form">
                    ${Form.generate([
                { type: 'date', name: 'date_from', label: 'From Date' },
                { type: 'date', name: 'date_to', label: 'To Date' }
            ], {
                date_from: this.state.filters.date_from,
                date_to: this.state.filters.date_to
            })}
                </form>
            `,
            size: 'sm',
            confirmText: 'Apply',
            onConfirm: () => {
                const data = Form.getData('#date-filter-form');
                this.state.filters.date_from = data.date_from;
                this.state.filters.date_to = data.date_to;
                this.state.pagination.page = 1;
                this.loadRecords();
                return true;
            }
        });
    },

    /**
     * Export medical records
     */
    async exportMedicalRecords() {
        if (!window.jspdf) {
            Toast.error('PDF library not loaded');
            return;
        }

        Toast.info('Preparing PDF export...');

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(20);
            doc.setTextColor(40, 40, 40);
            doc.text('Catarman Dog Pound', 14, 22);

            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text('Medical Records Report', 14, 32);

            // Metadata
            const date = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            doc.setFontSize(10);
            doc.text(`Generated: ${date}`, 14, 40);
            doc.text(`Total Records: ${this.state.records.length}`, 14, 46);

            const user = Auth.currentUser();
            if (user) {
                doc.text(`Requested by: ${user.first_name || ''} ${user.last_name || ''} (${user.role || 'User'})`, 14, 52);
            }

            // Table Data
            const tableColumn = ["Date", "Animal", "Type", "Details", "Veterinarian", "Next Due"];
            const tableRows = [];

            this.state.records.forEach(record => {
                const itemData = [
                    Utils.formatDate(record.Date_Performed),
                    `${record.Animal_Name} (${record.Animal_Type})`,
                    record.Diagnosis_Type,
                    record.Vaccine_Name || record.Treatment_Notes || '-',
                    `Dr. ${record.Vet_FirstName} ${record.Vet_LastName}`,
                    record.Next_Due_Date ? Utils.formatDate(record.Next_Due_Date) : '-'
                ];
                tableRows.push(itemData);
            });

            // Generate Table
            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 60,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 3 },
                headStyles: { fillColor: [66, 66, 66], textColor: 255 },
                alternateRowStyles: { fillColor: [245, 245, 245] },
            });

            // Footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150);
                doc.text(`Page ${i} of ${pageCount}`, 196, 285, { align: 'right' });
            }

            // Save
            const filename = `medical-records-${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);

            Toast.success('Export downloaded');
        } catch (error) {
            console.error('Export failed:', error);
            Toast.error('Failed to generate PDF');
        }
    },

    /**
     * Get form fields
     * @param {number} animalId
     * @returns {Array}
     */
    getFormFields(animalId = null) {
        return [
            {
                type: 'select',
                name: 'animal_id',
                label: 'Animal',
                required: true,
                options: [
                    { value: '', label: 'Select Animal' },
                    ...this.state.animals.map(a => ({
                        value: a.AnimalID,
                        label: `${a.Name} (${a.Type})`
                    }))
                ]
            },
            {
                type: 'select',
                name: 'vet_id',
                label: 'Veterinarian',
                required: true,
                options: [
                    { value: '', label: 'Select Veterinarian' },
                    ...this.state.veterinarians.map(v => ({
                        value: v.VetID,
                        label: `Dr. ${v.FirstName} ${v.LastName}`
                    }))
                ]
            },
            {
                type: 'select',
                name: 'diagnosis_type',
                label: 'Diagnosis Type',
                required: true,
                options: [
                    { value: '', label: 'Select Type' },
                    ...this.diagnosisTypes.map(t => ({ value: t, label: t }))
                ]
            },
            {
                type: 'text',
                name: 'vaccine_name',
                label: 'Vaccine Name',
                placeholder: 'Enter vaccine name (if applicable)'
            },
            {
                type: 'datetime-local',
                name: 'date_performed',
                label: 'Date Performed',
                required: true
            },
            {
                type: 'textarea',
                name: 'treatment_notes',
                label: 'Treatment Notes',
                required: true,
                placeholder: 'Enter treatment details...',
                rows: 4
            },
            {
                type: 'date',
                name: 'next_due_date',
                label: 'Next Due Date',
                hint: 'Leave blank if no follow-up needed'
            }
        ];
    }
};

// Make MedicalPage globally available
window.MedicalPage = MedicalPage;