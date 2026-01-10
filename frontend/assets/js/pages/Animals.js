/**
 * Animals Page
 * Animal listing and management
 * 
 * @package AnimalShelter
 */

const AnimalsPage = {
    /**
     * Page state
     */
    state: {
        animals: [],
        pagination: { page: 1, perPage: 20, total: 0 },
        filters: {
            type: '',
            status: '',
            gender: '',
            search: ''
        },
        viewMode: 'grid', // 'grid' or 'table'
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
                    <h1 class="page-title">Animals</h1>
                    <p class="page-subtitle">Manage shelter animals and their records</p>
                </div>
                <div class="page-actions">
                    ${Auth.isStaff() ? `
                        <button class="btn btn-primary" onclick="AnimalsPage.showAddModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Add Animal
                        </button>
                    ` : ''}
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-6">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1" style="min-width: 200px;">
                            <div class="input-wrapper search-input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search animals..." id="search-input" value="${this.state.filters.search}">
                                <span class="search-keyboard-hint"><kbd>/</kbd></span>
                            </div>
                        </div>
                        
                        <select class="form-select" id="filter-type" style="width: auto;">
                            <option value="">All Types</option>
                            <option value="Dog" ${this.state.filters.type === 'Dog' ? 'selected' : ''}>Dogs</option>
                            <option value="Cat" ${this.state.filters.type === 'Cat' ? 'selected' : ''}>Cats</option>
                            <option value="Other" ${this.state.filters.type === 'Other' ? 'selected' : ''}>Other</option>
                        </select>
                        
                        <select class="form-select" id="filter-status" style="width: auto;">
                            <option value="">All Statuses</option>
                            <option value="Available" ${this.state.filters.status === 'Available' ? 'selected' : ''}>Available</option>
                            <option value="Reserved" ${this.state.filters.status === 'Reserved' ? 'selected' : ''}>Reserved</option>
                            <option value="Adopted" ${this.state.filters.status === 'Adopted' ? 'selected' : ''}>Adopted</option>
                            <option value="In Treatment" ${this.state.filters.status === 'In Treatment' ? 'selected' : ''}>In Treatment</option>
                            <option value="Quarantine" ${this.state.filters.status === 'Quarantine' ? 'selected' : ''}>Quarantine</option>
                        </select>
                        
                        <select class="form-select" id="filter-gender" style="width: auto;">
                            <option value="">All Genders</option>
                            <option value="Male" ${this.state.filters.gender === 'Male' ? 'selected' : ''}>Male</option>
                            <option value="Female" ${this.state.filters.gender === 'Female' ? 'selected' : ''}>Female</option>
                        </select>
                        
                        <div class="flex items-center gap-2">
                            <button id="view-mode-grid" class="btn-icon ${this.state.viewMode === 'grid' ? 'btn-primary' : 'btn-ghost'}" onclick="AnimalsPage.setViewMode('grid')" title="Grid View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            </button>
                            <button id="view-mode-table" class="btn-icon ${this.state.viewMode === 'table' ? 'btn-primary' : 'btn-ghost'}" onclick="AnimalsPage.setViewMode('table')" title="Table View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Animals Container -->
            <div id="animals-container">
                ${Loading.skeleton('stats', { count: 8 })}
            </div>
            
            <!-- Pagination -->
            <div id="pagination-container" class="mt-6"></div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        // Restore filters from sessionStorage
        const savedFilters = sessionStorage.getItem('animals_filters');
        if (savedFilters) {
            this.state.filters = JSON.parse(savedFilters);
        }
        const savedViewMode = sessionStorage.getItem('animals_viewMode');
        if (savedViewMode) {
            this.state.viewMode = savedViewMode;
        }
        this.state.pagination.page = 1;

        this.setupEventListeners();
        this.syncFilterUI();
        await this.loadAnimals();
    },

    /**
     * Sync filter UI with state
     */
    syncFilterUI() {
        ['type', 'status', 'gender'].forEach(filter => {
            const select = document.getElementById(`filter-${filter}`);
            if (select && this.state.filters[filter]) {
                select.value = this.state.filters[filter];
            }
        });
        const searchInput = document.getElementById('search-input');
        if (searchInput && this.state.filters.search) {
            searchInput.value = this.state.filters.search;
        }
        // Update view mode buttons
        document.getElementById('view-mode-grid')?.classList.toggle('btn-primary', this.state.viewMode === 'grid');
        document.getElementById('view-mode-grid')?.classList.toggle('btn-ghost', this.state.viewMode !== 'grid');
        document.getElementById('view-mode-table')?.classList.toggle('btn-primary', this.state.viewMode === 'table');
        document.getElementById('view-mode-table')?.classList.toggle('btn-ghost', this.state.viewMode !== 'table');
    },

    /**
     * Save filters to sessionStorage
     */
    saveFilters() {
        sessionStorage.setItem('animals_filters', JSON.stringify(this.state.filters));
        sessionStorage.setItem('animals_viewMode', this.state.viewMode);
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Search input with debounce
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.state.filters.search = e.target.value;
                this.state.pagination.page = 1;
                this.saveFilters();
                this.loadAnimals();
            }, 300));
        }

        // Filter selects
        ['type', 'status', 'gender'].forEach(filter => {
            const select = document.getElementById(`filter-${filter}`);
            if (select) {
                select.addEventListener('change', (e) => {
                    this.state.filters[filter] = e.target.value;
                    this.state.pagination.page = 1;
                    this.saveFilters();
                    this.loadAnimals();
                });
            }
        });
    },

    /**
     * Load animals
     */
    async loadAnimals() {
        this.state.loading = true;
        this.renderContainer();

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

            const response = await (Auth.isAdopter()
                ? API.animals.available(params)
                : API.animals.list(params));

            if (response.success) {
                // Handle flat response structure handling ( { data: [], pagination: {} } )
                this.state.animals = Array.isArray(response.data) ? response.data : (response.data.data || []);

                // Handle pagination
                if (response.pagination) {
                    this.state.pagination.total = response.pagination.total_items;
                } else if (response.data && response.data.pagination) {
                    this.state.pagination.total = response.data.pagination.total;
                } else {
                    this.state.pagination.total = this.state.animals.length;
                }

                this.state.loading = false;
                this.renderContainer();
                this.renderPagination();
            }
        } catch (error) {
            console.error('Failed to load animals:', error);
            Toast.error('Failed to load animals');
            this.state.loading = false;
            this.renderContainer();
        }
    },

    /**
     * Render animals container
     */
    renderContainer() {
        const container = document.getElementById('animals-container');
        if (!container) return;

        if (this.state.loading) {
            container.innerHTML = this.state.viewMode === 'grid'
                ? `<div class="animal-grid">${Array(8).fill(Loading.skeleton('card')).join('')}</div>`
                : Loading.skeleton('table', { rows: 8, cols: 6 });
            return;
        }

        if (this.state.animals.length === 0) {
            const hasFilters = this.state.filters.search || this.state.filters.type ||
                this.state.filters.status || this.state.filters.gender;

            container.innerHTML = Card.empty({
                icon: hasFilters ? '🔍' : '🐾',
                title: hasFilters ? 'No matching animals' : 'No animals found',
                description: hasFilters
                    ? 'No animals match your current search or filter criteria.'
                    : 'There are no animals in the shelter yet.',
                hint: hasFilters
                    ? 'Try adjusting your filters or <a href="javascript:AnimalsPage.clearFilters()">clear all filters</a>'
                    : 'Animals added to the shelter will appear here.',
                action: Auth.isStaff() ? {
                    label: 'Add First Animal',
                    onClick: 'AnimalsPage.showAddModal()',
                    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>'
                } : null
            });
            return;
        }

        if (this.state.viewMode === 'grid') {
            container.innerHTML = `
                <div class="animal-grid">
                    ${this.state.animals.map(animal => Card.animal(animal)).join('')}
                </div>
            `;
        } else {
            container.innerHTML = DataTable.render({
                id: 'animals-table',
                columns: [
                    {
                        key: 'Name', label: 'Name', render: (val, row) => {
                            const placeholder = Utils.getAnimalPlaceholder(row.Type);
                            return `
                        <div class="flex items-center gap-3">
                            <img src="${row.Image_URL || placeholder}" 
                                 alt="${val}" 
                                 style="width: 40px; height: 40px; border-radius: var(--radius-md); object-fit: cover;"
                                 onerror="this.src='${placeholder}'">
                            <span class="font-semibold">${val}</span>
                        </div>
                    `}
                    },
                    { key: 'Type', label: 'Type' },
                    { key: 'Breed', label: 'Breed', render: val => val || '-' },
                    { key: 'Gender', label: 'Gender' },
                    { key: 'Age_Group', label: 'Age', render: val => val || '-' },
                    { key: 'Current_Status', label: 'Status', type: 'badge' },
                    { key: 'Intake_Date', label: 'Intake Date', type: 'date' }
                ],
                data: this.state.animals,
                pagination: this.state.pagination,
                actions: {
                    view: true,
                    edit: Auth.isStaff(),
                    delete: Auth.isAdmin()
                },
                onRowClick: (id) => Router.navigate(`/animals/${id}`),
                onAction: (action, id, row) => this.handleAction(action, id, row),
                onPageChange: (page) => {
                    this.state.pagination.page = page;
                    this.loadAnimals();
                },
                sortable: false
            });
        }
    },

    /**
     * Render pagination
     */
    renderPagination() {
        const container = document.getElementById('pagination-container');
        if (!container || this.state.viewMode === 'table') {
            if (container) container.innerHTML = '';
            return;
        }

        const { page, perPage, total } = this.state.pagination;
        const totalPages = Math.ceil(total / perPage);
        const start = ((page - 1) * perPage) + 1;
        const end = Math.min(page * perPage, total);

        if (totalPages <= 1) {
            container.innerHTML = `
                <div class="flex items-center justify-center mt-4 text-secondary" style="font-size: var(--text-sm);">
                    <span>Showing ${total} animal${total !== 1 ? 's' : ''}</span>
                </div>
            `;
            return;
        }

        // Generate page numbers with dots for large sets
        const pages = this.getPageNumbers(page, totalPages);

        container.innerHTML = `
            <div class="flex items-center justify-between mt-4" style="padding: 0 var(--space-2);">
                <div class="text-secondary" style="font-size: var(--text-sm);">
                    Showing ${start} to ${end} of ${total} animals
                </div>
                <div class="pagination">
                    <button class="pagination-btn" onclick="AnimalsPage.goToPage(1)" ${page === 1 ? 'disabled' : ''}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="11 17 6 12 11 7"></polyline><polyline points="18 17 13 12 18 7"></polyline></svg>
                    </button>
                    <button class="pagination-btn" onclick="AnimalsPage.goToPage(${page - 1})" ${page === 1 ? 'disabled' : ''}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    ${pages.map(p => {
            if (p === '...') {
                return '<span class="pagination-btn" style="cursor: default;">...</span>';
            }
            return `
                            <button class="pagination-btn ${p === page ? 'active' : ''}" onclick="AnimalsPage.goToPage(${p})">
                                ${p}
                            </button>
                        `;
        }).join('')}
                    <button class="pagination-btn" onclick="AnimalsPage.goToPage(${page + 1})" ${page === totalPages ? 'disabled' : ''}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                    <button class="pagination-btn" onclick="AnimalsPage.goToPage(${totalPages})" ${page === totalPages ? 'disabled' : ''}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 17 18 12 13 7"></polyline><polyline points="6 17 11 12 6 7"></polyline></svg>
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Get page numbers array with ellipsis for large sets
     * @param {number} current
     * @param {number} total
     * @returns {Array}
     */
    getPageNumbers(current, total) {
        const delta = 2;
        const range = [];
        const rangeWithDots = [];
        let l;

        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                range.push(i);
            }
        }

        for (let i of range) {
            if (l) {
                if (i - l === 2) {
                    rangeWithDots.push(l + 1);
                } else if (i - l !== 1) {
                    rangeWithDots.push('...');
                }
            }
            rangeWithDots.push(i);
            l = i;
        }

        return rangeWithDots;
    },

    /**
     * Go to page
     * @param {number} page
     */
    goToPage(page) {
        this.state.pagination.page = page;
        this.loadAnimals();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    /**
     * Set view mode
     * @param {string} mode
     */
    setViewMode(mode) {
        this.state.viewMode = mode;

        // Update button states
        const gridBtn = document.getElementById('view-mode-grid');
        const tableBtn = document.getElementById('view-mode-table');

        if (gridBtn && tableBtn) {
            if (mode === 'grid') {
                gridBtn.classList.add('btn-primary');
                gridBtn.classList.remove('btn-ghost');
                tableBtn.classList.add('btn-ghost');
                tableBtn.classList.remove('btn-primary');
            } else {
                gridBtn.classList.add('btn-ghost');
                gridBtn.classList.remove('btn-primary');
                tableBtn.classList.add('btn-primary');
                tableBtn.classList.remove('btn-ghost');
            }
        }

        this.renderContainer();
        this.renderPagination();
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
                Router.navigate(`/animals/${id}`);
                break;
            case 'edit':
                this.showEditModal(row);
                break;
            case 'delete':
                await this.deleteAnimal(id, row.Name);
                break;
        }
    },

    /**
     * Show add animal modal
     */
    showAddModal() {
        const fields = this.getAnimalFormFields();

        Modal.open({
            title: 'Add New Animal',
            content: `<form id="add-animal-form">${Form.generate(fields)}</form>`,
            size: 'lg',
            confirmText: 'Add Animal',
            onConfirm: async () => {
                const form = document.getElementById('add-animal-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                // Get the photo file if selected
                const photoInput = form.querySelector('input[name="photo"]');
                const photoFile = photoInput?.files?.[0];

                // Remove photo from data as it's handled separately
                delete data.photo;

                try {
                    const response = await API.animals.create(data);
                    if (response.success) {
                        const animalId = response.data.AnimalID;

                        // Upload photo if provided
                        if (photoFile) {
                            try {
                                await API.animals.uploadImage(animalId, photoFile);
                            } catch (uploadError) {
                                console.error('Photo upload failed:', uploadError);
                                Toast.warning('Animal added but photo upload failed');
                            }
                        }

                        Toast.success('Animal added successfully');
                        this.loadAnimals();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to add animal');
                    return false;
                }
            }
        });
    },

    /**
     * Show edit animal modal
     * @param {Object} animal
     */
    showEditModal(animal) {
        const fields = this.getAnimalFormFields();

        Modal.open({
            title: `Edit ${animal.Name}`,
            content: `<form id="edit-animal-form">${Form.generate(fields, {
                name: animal.Name,
                type: animal.Type,
                breed: animal.Breed,
                gender: animal.Gender,
                age_group: animal.Age_Group,
                weight: animal.Weight,
                current_status: animal.Current_Status
            })}</form>`,
            size: 'lg',
            confirmText: 'Save Changes',
            onConfirm: async () => {
                const form = document.getElementById('edit-animal-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                // Get the photo file if selected
                const photoInput = form.querySelector('input[name="photo"]');
                const photoFile = photoInput?.files?.[0];

                // Remove photo from data as it's handled separately
                delete data.photo;

                try {
                    const response = await API.animals.update(animal.AnimalID, data);
                    if (response.success) {
                        // Upload photo if provided
                        if (photoFile) {
                            try {
                                await API.animals.uploadImage(animal.AnimalID, photoFile);
                            } catch (uploadError) {
                                console.error('Photo upload failed:', uploadError);
                                Toast.warning('Animal updated but photo upload failed');
                            }
                        }

                        Toast.success('Animal updated successfully');
                        this.loadAnimals();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to update animal');
                    return false;
                }
            }
        });
    },


    /**
     * Delete animal
     * @param {number} id
     * @param {string} name
     */
    async deleteAnimal(id, name) {
        const confirmed = await Modal.confirmDelete(name);

        if (confirmed) {
            try {
                const response = await API.animals.delete(id);
                if (response.success) {
                    Toast.success('Animal deleted successfully');
                    this.loadAnimals();
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to delete animal');
            }
        }
    },

    /**
     * Request adoption
     * @param {number} animalId
     */
    async requestAdoption(animalId) {
        if (!Auth.isAuthenticated()) {
            Toast.warning('Please login to request adoption');
            Router.navigate('/login');
            return;
        }

        const confirmed = await Modal.confirm(
            'Would you like to submit an adoption request for this animal?',
            'Request Adoption'
        );

        if (confirmed) {
            try {
                const response = await API.adoptions.create({ animal_id: animalId });
                if (response.success) {
                    Toast.success('Adoption request submitted successfully!');
                    Router.navigate('/adoptions');
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to submit adoption request');
            }
        }
    },

    /**
     * Get animal form fields
     * @returns {Array}
     */
    getAnimalFormFields() {
        return [
            { type: 'text', name: 'name', label: 'Name', required: true, placeholder: 'Enter animal name' },
            { type: 'select', name: 'type', label: 'Type', required: true, options: ['Dog', 'Cat', 'Other'] },
            { type: 'text', name: 'breed', label: 'Breed', placeholder: 'Enter breed' },
            { type: 'select', name: 'gender', label: 'Gender', required: true, options: ['Male', 'Female', 'Unknown'] },
            {
                type: 'select', name: 'age_group', label: 'Age Group', options: [
                    { value: '', label: 'Select age group' },
                    { value: 'Puppy/Kitten', label: 'Puppy/Kitten' },
                    { value: 'Young', label: 'Young' },
                    { value: 'Adult', label: 'Adult' },
                    { value: 'Senior', label: 'Senior' }
                ]
            },
            { type: 'number', name: 'weight', label: 'Weight (kg)', step: '0.1', min: 0 },
            {
                type: 'select', name: 'intake_status', label: 'Intake Status', required: true, options: [
                    { value: 'Stray', label: 'Stray' },
                    { value: 'Surrendered', label: 'Surrendered' },
                    { value: 'Confiscated', label: 'Confiscated' },
                    { value: 'Born in Shelter', label: 'Born in Shelter' },
                    { value: 'Transferred', label: 'Transferred' }
                ]
            },
            {
                type: 'select', name: 'current_status', label: 'Current Status', options: [
                    { value: 'Available', label: 'Available' },
                    { value: 'Reserved', label: 'Reserved' },
                    { value: 'In Treatment', label: 'In Treatment' },
                    { value: 'Quarantine', label: 'Quarantine' }
                ]
            },
            {
                type: 'file',
                name: 'photo',
                label: 'Photo',
                accept: 'image/*',
                hint: 'Upload a photo of the animal (optional)'
            }
        ];
    }
};

// Make AnimalsPage globally available
window.AnimalsPage = AnimalsPage;