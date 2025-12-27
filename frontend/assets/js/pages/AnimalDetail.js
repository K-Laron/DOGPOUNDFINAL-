/**
 * Animal Detail Page
 * Detailed view of a single animal
 * 
 * @package AnimalShelter
 */

const AnimalDetailPage = {
    /**
     * Current animal data
     */
    animal: null,

    /**
     * Related data
     */
    medicalRecords: [],
    feedingRecords: [],
    adoptionHistory: [],

    /**
     * Render the page
     * @param {Object} params
     * @returns {string}
     */
    async render(params) {
        const { id } = params;

        return `
            <div id="animal-detail-container">
                ${Loading.spinner({ size: 'lg', text: 'Loading animal details...' })}
            </div>
        `;
    },

    /**
     * After render callback
     * @param {Object} params
     */
    async afterRender(params) {
        const { id } = params;
        await this.loadAnimalData(id);
    },

    /**
     * Load animal data
     * @param {number} id
     */
    async loadAnimalData(id) {
        try {
            const response = await API.animals.get(id);

            if (response.success) {
                this.animal = response.data;
                this.renderAnimalDetail();

                // Load related data
                await Promise.all([
                    this.loadMedicalRecords(id),
                    this.loadFeedingRecords(id),
                    this.loadAdoptionHistory(id)
                ]);
            } else {
                throw new Error('Animal not found');
            }
        } catch (error) {
            console.error('Failed to load animal:', error);
            document.getElementById('animal-detail-container').innerHTML = Card.empty({
                icon: '🔍',
                title: 'Animal Not Found',
                description: 'The animal you are looking for does not exist.',
                action: { label: 'Back to Animals', onClick: "Router.navigate('/animals')" }
            });
        }
    },

    /**
     * Render animal detail
     */
    renderAnimalDetail() {
        const container = document.getElementById('animal-detail-container');
        if (!container || !this.animal) return;

        const animal = this.animal;
        const statusClass = Utils.getStatusBadgeClass(animal.Current_Status);
        const placeholder = Utils.getAnimalPlaceholder(animal.Type);

        container.innerHTML = `
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <button class="btn-icon btn-ghost" onclick="Router.navigate('/animals')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <div>
                        <h1 class="page-title">${animal.Name}</h1>
                        <p class="text-secondary">${animal.Breed || animal.Type} • ${animal.Gender}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="badge ${statusClass}" style="font-size: var(--text-sm); padding: 8px 16px;">${animal.Current_Status}</span>
                    ${Auth.isStaff() ? `
                        <div class="dropdown" id="animal-actions-dropdown">
                            <button class="btn btn-secondary">
                                Actions
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <div class="dropdown-menu">
                                <button class="dropdown-item" onclick="AnimalDetailPage.editAnimal()">
                                    Edit Animal
                                </button>
                                <button class="dropdown-item" onclick="AnimalDetailPage.updateStatus()">
                                    Update Status
                                </button>
                                <button class="dropdown-item" onclick="AnimalDetailPage.uploadPhoto()">
                                    Upload Photo
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" onclick="AnimalDetailPage.addMedicalRecord()">
                                    Add Medical Record
                                </button>
                                <button class="dropdown-item" onclick="AnimalDetailPage.recordFeeding()">
                                    Record Feeding
                                </button>
                                ${Auth.isAdmin() ? `
                                    <div class="dropdown-divider"></div>
                                    <button class="dropdown-item danger" onclick="AnimalDetailPage.deleteAnimal()">
                                        Delete Animal
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    ` : animal.Current_Status === 'Available' ? `
                        <button class="btn btn-primary" onclick="AnimalDetailPage.requestAdoption()">
                            Request Adoption
                        </button>
                    ` : ''}
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="grid gap-6" style="grid-template-columns: 1fr 2fr;">
                <!-- Left Column - Photo & Basic Info -->
                <div class="flex flex-col gap-6">
                    <!-- Photo -->
                    <div class="card">
                        <div class="card-body p-0">
                            <img 
                                src="${animal.Image_URL || placeholder}" 
                                alt="${animal.Name}"
                                style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: var(--radius-xl);"
                                onerror="this.src='${placeholder}'"
                            >
                        </div>
                    </div>
                    
                    <!-- Basic Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                ${this.renderInfoRow('Type', animal.Type)}
                                ${this.renderInfoRow('Breed', animal.Breed || 'Unknown')}
                                ${this.renderInfoRow('Gender', animal.Gender)}
                                ${this.renderInfoRow('Age Group', animal.Age_Group || 'Unknown')}
                                ${this.renderInfoRow('Weight', animal.Weight ? `${animal.Weight} kg` : 'Unknown')}
                                ${this.renderInfoRow('Intake Date', Utils.formatDate(animal.Intake_Date))}
                                ${this.renderInfoRow('Intake Status', animal.Intake_Status)}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Impound Record -->
                    ${animal.impound_record ? `
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Impound Record</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    ${this.renderInfoRow('Capture Date', Utils.formatDate(animal.impound_record.Capture_Date))}
                                    ${this.renderInfoRow('Location Found', animal.impound_record.Location_Found)}
                                    ${this.renderInfoRow('Officer', animal.impound_record.Impounding_Officer)}
                                    ${animal.impound_record.Condition_On_Arrival ? this.renderInfoRow('Condition', animal.impound_record.Condition_On_Arrival) : ''}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Right Column - Records & History -->
                <div class="flex flex-col gap-6">
                    <!-- Tabs -->
                    <div class="card">
                        <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
                            <div class="tabs" id="detail-tabs">
                                <button class="tab active" data-tab="medical">Medical Records</button>
                                <button class="tab" data-tab="feeding">Feeding Records</button>
                                <button class="tab" data-tab="adoption">Adoption History</button>
                            </div>
                        </div>
                        
                        <div class="card-body" id="tab-content">
                            <!-- Medical Records Tab -->
                            <div id="medical-tab" class="tab-panel">
                                ${Loading.skeleton('list', { items: 3 })}
                            </div>
                            
                            <!-- Feeding Records Tab -->
                            <div id="feeding-tab" class="tab-panel hidden">
                                ${Loading.skeleton('list', { items: 3 })}
                            </div>
                            
                            <!-- Adoption History Tab -->
                            <div id="adoption-tab" class="tab-panel hidden">
                                ${Loading.skeleton('list', { items: 3 })}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.setupEventListeners();
    },

    /**
     * Render info row
     * @param {string} label
     * @param {string} value
     * @returns {string}
     */
    renderInfoRow(label, value) {
        return `
            <div class="flex justify-between items-center py-2 border-b border-color" style="border-color: var(--border-color-light);">
                <span class="text-secondary">${label}</span>
                <span class="font-medium">${value}</span>
            </div>
        `;
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Tabs
        const tabs = document.getElementById('detail-tabs');
        if (tabs) {
            tabs.addEventListener('click', (e) => {
                const tab = e.target.closest('.tab');
                if (tab) {
                    // Update active tab
                    tabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Show corresponding panel
                    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                    document.getElementById(`${tab.dataset.tab}-tab`)?.classList.remove('hidden');
                }
            });
        }

        // Dropdown
        const dropdown = document.getElementById('animal-actions-dropdown');
        if (dropdown) {
            dropdown.querySelector('button').addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('open');
                }
            });
        }
    },

    /**
     * Load medical records
     * @param {number} animalId
     */
    async loadMedicalRecords(animalId) {
        try {
            const response = await API.medical.byAnimal(animalId);

            if (response.success) {
                this.medicalRecords = response.data.data || response.data;
                this.renderMedicalRecords();
            }
        } catch (error) {
            console.error('Failed to load medical records:', error);
        }
    },

    /**
     * Render medical records
     */
    renderMedicalRecords() {
        const container = document.getElementById('medical-tab');
        if (!container) return;

        if (this.medicalRecords.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-8">
                    <div class="text-4xl mb-4">💉</div>
                    <p class="text-secondary">No medical records found</p>
                    ${Auth.isStaff() ? `
                        <button class="btn btn-primary btn-sm mt-4" onclick="AnimalDetailPage.addMedicalRecord()">
                            Add Record
                        </button>
                    ` : ''}
                </div>
            `;
            return;
        }

        container.innerHTML = this.medicalRecords.map(record => `
            <div class="flex items-start gap-4 py-4 border-b border-color">
                <div class="avatar" style="background: ${Utils.stringToColor(record.Diagnosis_Type)}">
                    ${record.Diagnosis_Type.charAt(0)}
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-semibold">${record.Diagnosis_Type}</h4>
                        <span class="text-tertiary text-sm">${Utils.formatDate(record.Date_Performed)}</span>
                    </div>
                    ${record.Vaccine_Name ? `<p class="text-secondary text-sm mb-1">Vaccine: ${record.Vaccine_Name}</p>` : ''}
                    <p class="text-secondary text-sm">${record.Treatment_Notes || 'No notes'}</p>
                    <p class="text-tertiary text-xs mt-2">By Dr. ${record.Vet_FirstName} ${record.Vet_LastName}</p>
                    ${record.Next_Due_Date ? `
                        <div class="mt-2">
                            <span class="badge badge-warning">Next due: ${Utils.formatDate(record.Next_Due_Date)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    },

    /**
     * Load feeding records
     * @param {number} animalId
     */
    async loadFeedingRecords(animalId) {
        try {
            const response = await API.feeding.byAnimal(animalId);

            if (response.success) {
                this.feedingRecords = response.data.data || response.data;
                this.renderFeedingRecords();
            }
        } catch (error) {
            console.error('Failed to load feeding records:', error);
        }
    },

    /**
     * Render feeding records
     */
    renderFeedingRecords() {
        const container = document.getElementById('feeding-tab');
        if (!container) return;

        if (this.feedingRecords.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-8">
                    <div class="text-4xl mb-4">🍖</div>
                    <p class="text-secondary">No feeding records found</p>
                    ${Auth.isStaff() ? `
                        <button class="btn btn-primary btn-sm mt-4" onclick="AnimalDetailPage.recordFeeding()">
                            Record Feeding
                        </button>
                    ` : ''}
                </div>
            `;
            return;
        }

        container.innerHTML = this.feedingRecords.map(record => `
            <div class="flex items-center gap-4 py-3 border-b border-color">
                <div class="text-2xl">🍖</div>
                <div class="flex-1">
                    <p class="font-medium">${record.Food_Type}</p>
                    <p class="text-secondary text-sm">${record.Quantity_Used} units</p>
                </div>
                <div class="text-right">
                    <p class="text-sm">${Utils.formatDateTime(record.Feeding_Time)}</p>
                    <p class="text-tertiary text-xs">By ${record.FirstName} ${record.LastName}</p>
                </div>
            </div>
        `).join('');
    },

    /**
     * Load adoption history
     * @param {number} animalId
     */
    async loadAdoptionHistory(animalId) {
        try {
            const response = await API.adoptions.animalHistory(animalId);

            if (response.success) {
                this.adoptionHistory = response.data || [];
                this.renderAdoptionHistory();
            }
        } catch (error) {
            console.error('Failed to load adoption history:', error);
        }
    },

    /**
     * Render adoption history
     */
    renderAdoptionHistory() {
        const container = document.getElementById('adoption-tab');
        if (!container) return;

        if (this.adoptionHistory.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-8">
                    <div class="text-4xl mb-4">📋</div>
                    <p class="text-secondary">No adoption history</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.adoptionHistory.map(request => `
            <div class="flex items-start gap-4 py-4 border-b border-color">
                <div class="avatar" style="background: ${Utils.stringToColor(request.Adopter_Email || '')}">
                    ${Utils.getInitials(`${request.FirstName || ''} ${request.LastName || ''}`)}
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-semibold">${request.FirstName} ${request.LastName}</h4>
                        <span class="badge ${Utils.getStatusBadgeClass(request.Status)}">${request.Status}</span>
                    </div>
                    <p class="text-secondary text-sm">${request.Email}</p>
                    <p class="text-tertiary text-xs mt-2">Requested ${Utils.timeAgo(request.Request_Date)}</p>
                </div>
            </div>
        `).join('');
    },

    /**
     * Edit animal
     */
    editAnimal() {
        AnimalsPage.showEditModal(this.animal);
    },

    /**
     * Update animal status
     */
    updateStatus() {
        Modal.open({
            title: 'Update Status',
            content: `
                <form id="status-form">
                    ${Form.generate([{
                type: 'select',
                name: 'status',
                label: 'New Status',
                required: true,
                options: [
                    { value: 'Available', label: 'Available' },
                    { value: 'Adopted', label: 'Adopted' },
                    { value: 'In Treatment', label: 'In Treatment' },
                    { value: 'Quarantine', label: 'Quarantine' },
                    { value: 'Deceased', label: 'Deceased' },
                    { value: 'Reclaimed', label: 'Reclaimed' }
                ]
            }], { status: this.animal.Current_Status })}
                </form>
            `,
            size: 'sm',
            confirmText: 'Update',
            onConfirm: async () => {
                const data = Form.getData('#status-form');

                try {
                    const response = await API.animals.updateStatus(this.animal.AnimalID, data.status);
                    if (response.success) {
                        Toast.success('Status updated successfully');
                        this.animal.Current_Status = data.status;
                        this.renderAnimalDetail();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to update status');
                    return false;
                }
            }
        });
    },

    /**
     * Upload photo
     */
    uploadPhoto() {
        Modal.open({
            title: 'Upload Photo',
            content: `
                <form id="photo-form">
                    <div class="form-group">
                        <label class="form-label">Select Image</label>
                        <input type="file" id="photo-input" class="form-input" accept="image/*" required>
                    </div>
                    <div id="photo-preview" class="mt-4" style="display: none;">
                        <img id="preview-img" src="" alt="Preview" style="max-width: 100%; border-radius: var(--radius-lg);">
                    </div>
                </form>
            `,
            confirmText: 'Upload',
            onConfirm: async () => {
                const input = document.getElementById('photo-input');
                if (!input.files[0]) {
                    Toast.error('Please select an image');
                    return false;
                }

                try {
                    const response = await API.animals.uploadImage(this.animal.AnimalID, input.files[0]);
                    if (response.success) {
                        Toast.success('Photo uploaded successfully');
                        this.animal.Image_URL = response.data.image_url;
                        this.renderAnimalDetail();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to upload photo');
                    return false;
                }
            }
        });

        // Setup preview
        setTimeout(() => {
            const input = document.getElementById('photo-input');
            input?.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('photo-preview').style.display = 'block';
                        document.getElementById('preview-img').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }, 100);
    },

    /**
     * Add medical record
     */
    addMedicalRecord() {
        Router.navigate('/medical');
        setTimeout(() => {
            if (typeof MedicalPage !== 'undefined') {
                MedicalPage.showAddModal(this.animal.AnimalID);
            }
        }, 300);
    },

    /**
     * Record feeding
     */
    async recordFeeding() {
        Modal.open({
            title: 'Record Feeding',
            content: `
                <form id="feeding-form">
                    ${Form.generate([
                { type: 'hidden', name: 'animal_id', value: this.animal.AnimalID },
                {
                    type: 'select', name: 'food_type', label: 'Food Type', required: true, options: [
                        'Dry Food', 'Wet Food', 'Mixed', 'Special Diet'
                    ]
                },
                { type: 'number', name: 'quantity_used', label: 'Quantity', required: true, min: 1 },
                { type: 'datetime-local', name: 'feeding_time', label: 'Feeding Time' }
            ])}
                </form>
            `,
            confirmText: 'Record',
            onConfirm: async () => {
                const form = document.getElementById('feeding-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);
                data.user_id = Auth.currentUser()?.id;

                try {
                    const response = await API.feeding.record(data);
                    if (response.success) {
                        Toast.success('Feeding recorded successfully');
                        this.loadFeedingRecords(this.animal.AnimalID);
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to record feeding');
                    return false;
                }
            }
        });
    },

    /**
     * Request adoption
     */
    requestAdoption() {
        AnimalsPage.requestAdoption(this.animal.AnimalID);
    },

    /**
     * Delete animal
     */
    async deleteAnimal() {
        const confirmed = await Modal.confirmDelete(this.animal.Name);

        if (confirmed) {
            try {
                const response = await API.animals.delete(this.animal.AnimalID);
                if (response.success) {
                    Toast.success('Animal deleted successfully');
                    Router.navigate('/animals');
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to delete animal');
            }
        }
    }
};

// Make AnimalDetailPage globally available
window.AnimalDetailPage = AnimalDetailPage;