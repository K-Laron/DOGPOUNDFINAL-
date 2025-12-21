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
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search animals..." id="search-input" value="${this.state.filters.search}">
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
        this.setupEventListeners();
        await this.loadAnimals();
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
            container.innerHTML = Card.empty({
                icon: '🐾',
                title: 'No animals found',
                description: 'Try adjusting your filters or add a new animal.',
                action: Auth.isStaff() ? { label: 'Add Animal', onClick: 'AnimalsPage.showAddModal()' } : null
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
                        key: 'Name', label: 'Name', render: (val, row) => `
                        <div class="flex items-center gap-3">
                            <img src="${row.Image_URL || 'assets/images/placeholder-animal.svg'}" 
                                 alt="${val}" 
                                 style="width: 40px; height: 40px; border-radius: var(--radius-md); object-fit: cover;"
                                 onerror="this.src='assets/images/placeholder-animal.svg'">
                            <span class="font-semibold">${val}</span>
                        </div>
                    `},
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

        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = `
            <div class="flex items-center justify-center gap-2">
                <button class="btn btn-secondary btn-sm" onclick="AnimalsPage.goToPage(${page - 1})" ${page === 1 ? 'disabled' : ''}>
                    Previous
                </button>
                <span class="text-secondary px-4">Page ${page} of ${totalPages}</span>
                <button class="btn btn-secondary btn-sm" onclick="AnimalsPage.goToPage(${page + 1})" ${page === totalPages ? 'disabled' : ''}>
                    Next
                </button>
            </div>
        `;
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

                try {
                    const response = await API.animals.create(data);
                    if (response.success) {
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

                try {
                    const response = await API.animals.update(animal.AnimalID, data);
                    if (response.success) {
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
                    { value: 'In Treatment', label: 'In Treatment' },
                    { value: 'Quarantine', label: 'Quarantine' }
                ]
            }
        ];
    }
};

// Make AnimalsPage globally available
window.AnimalsPage = AnimalsPage;