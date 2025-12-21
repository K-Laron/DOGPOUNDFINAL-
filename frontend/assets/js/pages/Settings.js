/**
 * Settings Page
 * System settings and configuration (Admin only)
 * 
 * @package AnimalShelter
 */

const SettingsPage = {
    /**
     * Settings data
     */
    settings: {},
    
    /**
     * Current tab
     */
    currentTab: 'general',
    
    /**
     * Activity logs
     */
    activityLogs: [],
    
    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        // Check admin access
        if (!Auth.isAdmin()) {
            return `
                <div class="empty-state py-16">
                    <div class="text-5xl mb-4">🔒</div>
                    <h3 class="empty-state-title">Access Denied</h3>
                    <p class="empty-state-description">You don't have permission to access this page.</p>
                    <button class="btn btn-primary mt-4" onclick="Router.navigate('/dashboard')">
                        Go to Dashboard
                    </button>
                </div>
            `;
        }
        
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Settings</h1>
                    <p class="page-subtitle">Configure system settings and preferences</p>
                </div>
            </div>
            
            <div class="grid gap-6" style="grid-template-columns: 240px 1fr;">
                <!-- Settings Navigation -->
                <div class="card" style="height: fit-content; position: sticky; top: 80px;">
                    <div class="card-body p-2">
                        <nav class="settings-nav" id="settings-nav">
                            <button class="settings-nav-item active" data-tab="general">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                General
                            </button>
                            <button class="settings-nav-item" data-tab="shelter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                Shelter Info
                            </button>
                            <button class="settings-nav-item" data-tab="adoption">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                Adoption
                            </button>
                            <button class="settings-nav-item" data-tab="fees">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                Fees & Pricing
                            </button>
                            <button class="settings-nav-item" data-tab="notifications">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                Notifications
                            </button>
                            <button class="settings-nav-item" data-tab="email">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                Email
                            </button>
                            <button class="settings-nav-item" data-tab="backup">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                Backup & Data
                            </button>
                            <button class="settings-nav-item" data-tab="logs">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                Activity Logs
                            </button>
                            <button class="settings-nav-item" data-tab="system">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                System Info
                            </button>
                        </nav>
                    </div>
                </div>
                
                <!-- Settings Content -->
                <div class="card">
                    <div class="card-body" id="settings-content">
                        ${Loading.skeleton('text', { lines: 8 })}
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * After render callback
     */
    async afterRender() {
        if (!Auth.isAdmin()) return;
        
        await this.loadSettings();
        this.setupEventListeners();
        this.renderCurrentTab();
        this.addStyles();
    },
    
    /**
     * Load settings
     */
    async loadSettings() {
        try {
            const response = await API.get('/settings');
            
            if (response.success) {
                this.settings = response.data || {};
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
            this.settings = this.getDefaultSettings();
        }
    },
    
    /**
     * Get default settings
     * @returns {Object}
     */
    getDefaultSettings() {
        return {
            general: {
                site_name: 'Catarman Dog Pound',
                site_description: 'Animal shelter management system',
                timezone: 'Asia/Manila',
                date_format: 'MM/DD/YYYY',
                currency: 'PHP'
            },
            shelter: {
                name: 'Catarman Dog Pound',
                address: 'Catarman, Northern Samar',
                phone: '',
                email: '',
                operating_hours: '8:00 AM - 5:00 PM'
            },
            adoption: {
                require_approval: true,
                require_interview: true,
                minimum_age: 18,
                adoption_fee_enabled: true,
                default_adoption_fee: 500
            },
            fees: {
                adoption_fee_dog: 500,
                adoption_fee_cat: 300,
                adoption_fee_other: 200,
                reclaim_fee_base: 300,
                reclaim_fee_per_day: 50
            },
            notifications: {
                email_enabled: true,
                sms_enabled: false,
                notify_new_adoption: true,
                notify_animal_status: true,
                notify_low_inventory: true
            },
            email: {
                smtp_host: '',
                smtp_port: 587,
                smtp_username: '',
                smtp_password: '',
                from_email: '',
                from_name: 'Catarman Dog Pound'
            }
        };
    },
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        const nav = document.getElementById('settings-nav');
        if (nav) {
            nav.addEventListener('click', (e) => {
                const item = e.target.closest('.settings-nav-item');
                if (item) {
                    nav.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
                    this.currentTab = item.dataset.tab;
                    this.renderCurrentTab();
                }
            });
        }
    },
    
    /**
     * Render current tab
     */
    renderCurrentTab() {
        switch (this.currentTab) {
            case 'general':
                this.renderGeneralSettings();
                break;
            case 'shelter':
                this.renderShelterSettings();
                break;
            case 'adoption':
                this.renderAdoptionSettings();
                break;
            case 'fees':
                this.renderFeesSettings();
                break;
            case 'notifications':
                this.renderNotificationSettings();
                break;
            case 'email':
                this.renderEmailSettings();
                break;
            case 'backup':
                this.renderBackupSettings();
                break;
            case 'logs':
                this.renderActivityLogs();
                break;
            case 'system':
                this.renderSystemInfo();
                break;
        }
    },
    
    /**
     * Render general settings
     */
    renderGeneralSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        const settings = this.settings.general || {};
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">General Settings</h3>
            
            <form id="general-settings-form">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-input" 
                           value="${settings.site_name || 'Catarman Dog Pound'}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Site Description</label>
                    <textarea name="site_description" class="form-textarea" rows="2">${settings.site_description || ''}</textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-select">
                            <option value="Asia/Manila" ${settings.timezone === 'Asia/Manila' ? 'selected' : ''}>Asia/Manila (PHT)</option>
                            <option value="UTC" ${settings.timezone === 'UTC' ? 'selected' : ''}>UTC</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date Format</label>
                        <select name="date_format" class="form-select">
                            <option value="MM/DD/YYYY" ${settings.date_format === 'MM/DD/YYYY' ? 'selected' : ''}>MM/DD/YYYY</option>
                            <option value="DD/MM/YYYY" ${settings.date_format === 'DD/MM/YYYY' ? 'selected' : ''}>DD/MM/YYYY</option>
                            <option value="YYYY-MM-DD" ${settings.date_format === 'YYYY-MM-DD' ? 'selected' : ''}>YYYY-MM-DD</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select" style="width: 200px;">
                        <option value="PHP" ${settings.currency === 'PHP' ? 'selected' : ''}>PHP - Philippine Peso</option>
                        <option value="USD" ${settings.currency === 'USD' ? 'selected' : ''}>USD - US Dollar</option>
                    </select>
                </div>
                
                ${this.renderSaveButton('general')}
            </form>
        `;
        
        this.setupFormHandler('general-settings-form', 'general');
    },
    
    /**
     * Render shelter settings
     */
    renderShelterSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        const settings = this.settings.shelter || {};
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">Shelter Information</h3>
            
            <form id="shelter-settings-form">
                <div class="form-group">
                    <label class="form-label">Shelter Name</label>
                    <input type="text" name="name" class="form-input" 
                           value="${settings.name || ''}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-textarea" rows="2">${settings.address || ''}</textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-input" 
                               value="${settings.phone || ''}" placeholder="(055) 123-4567">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" 
                               value="${settings.email || ''}" placeholder="contact@shelter.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Operating Hours</label>
                    <input type="text" name="operating_hours" class="form-input" 
                           value="${settings.operating_hours || ''}" placeholder="8:00 AM - 5:00 PM">
                </div>
                
                <div class="form-group">
                    <label class="form-label">About/Description</label>
                    <textarea name="about" class="form-textarea" rows="4">${settings.about || ''}</textarea>
                </div>
                
                ${this.renderSaveButton('shelter')}
            </form>
        `;
        
        this.setupFormHandler('shelter-settings-form', 'shelter');
    },
    
    /**
     * Render adoption settings
     */
    renderAdoptionSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        const settings = this.settings.adoption || {};
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">Adoption Settings</h3>
            
            <form id="adoption-settings-form">
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 bg-secondary rounded-lg">
                        <div>
                            <p class="font-medium">Require Admin Approval</p>
                            <p class="text-secondary text-sm">Adoption requests need to be approved by staff</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="require_approval" ${settings.require_approval !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-secondary rounded-lg">
                        <div>
                            <p class="font-medium">Require Interview</p>
                            <p class="text-secondary text-sm">Schedule interviews with potential adopters</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="require_interview" ${settings.require_interview ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Minimum Adopter Age</label>
                        <input type="number" name="minimum_age" class="form-input" style="width: 120px;"
                               value="${settings.minimum_age || 18}" min="18" max="100">
                        <p class="form-hint">Minimum age required to adopt an animal</p>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-secondary rounded-lg">
                        <div>
                            <p class="font-medium">Enable Adoption Fees</p>
                            <p class="text-secondary text-sm">Charge fees for animal adoptions</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="adoption_fee_enabled" ${settings.adoption_fee_enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Adoption Cooldown Period (Days)</label>
                        <input type="number" name="cooldown_days" class="form-input" style="width: 120px;"
                               value="${settings.cooldown_days || 30}" min="0" max="365">
                        <p class="form-hint">Days before a rejected applicant can apply again</p>
                    </div>
                </div>
                
                ${this.renderSaveButton('adoption')}
            </form>
        `;
        
        this.setupFormHandler('adoption-settings-form', 'adoption');
    },
    
    /**
     * Render fees settings
     */
    renderFeesSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        const settings = this.settings.fees || {};
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">Fees & Pricing</h3>
            
            <form id="fees-settings-form">
                <h4 class="font-medium mb-4">Adoption Fees</h4>
                
                <div class="grid grid-cols-3 gap-4 mb-8">
                    <div class="form-group">
                        <label class="form-label">Dogs</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="adoption_fee_dog" class="form-input" style="padding-left: 32px;"
                                   value="${settings.adoption_fee_dog || 500}" min="0" step="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Cats</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="adoption_fee_cat" class="form-input" style="padding-left: 32px;"
                                   value="${settings.adoption_fee_cat || 300}" min="0" step="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Other Animals</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="adoption_fee_other" class="form-input" style="padding-left: 32px;"
                                   value="${settings.adoption_fee_other || 200}" min="0" step="50">
                        </div>
                    </div>
                </div>
                
                <h4 class="font-medium mb-4">Reclaim Fees</h4>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="form-group">
                        <label class="form-label">Base Fee</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="reclaim_fee_base" class="form-input" style="padding-left: 32px;"
                                   value="${settings.reclaim_fee_base || 300}" min="0" step="50">
                        </div>
                        <p class="form-hint">Initial fee for reclaiming an animal</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Daily Fee</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="reclaim_fee_per_day" class="form-input" style="padding-left: 32px;"
                                   value="${settings.reclaim_fee_per_day || 50}" min="0" step="10">
                        </div>
                        <p class="form-hint">Additional fee per day in shelter</p>
                    </div>
                </div>
                
                <h4 class="font-medium mb-4">Other Fees</h4>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Veterinary Services</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="vet_service_fee" class="form-input" style="padding-left: 32px;"
                                   value="${settings.vet_service_fee || 0}" min="0" step="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Surrender Fee</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="font-weight: 600;">₱</span>
                            <input type="number" name="surrender_fee" class="form-input" style="padding-left: 32px;"
                                   value="${settings.surrender_fee || 0}" min="0" step="50">
                        </div>
                    </div>
                </div>
                
                ${this.renderSaveButton('fees')}
            </form>
        `;
        
        this.setupFormHandler('fees-settings-form', 'fees');
    },
    
    /**
     * Render notification settings
     */
    renderNotificationSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        const settings = this.settings.notifications || {};
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">Notification Settings</h3>
            
            <form id="notification-settings-form">
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 bg-secondary rounded-lg">
                        <div>
                            <p class="font-medium">Email Notifications</p>
                            <p class="text-secondary text-sm">Send email notifications to users</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="email_enabled" ${settings.email_enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-secondary rounded-lg">
                        <div>
                            <p class="font-medium">SMS Notifications</p>
                            <p class="text-secondary text-sm">Send SMS notifications (requires SMS gateway)</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="sms_enabled" ${settings.sms_enabled ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <hr class="my-6">
                    
                    <h4 class="font-medium mb-4">Notification Events</h4>
                    
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="font-medium">New Adoption Request</p>
                            <p class="text-secondary text-sm">Notify staff when new adoption requests are submitted</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="notify_new_adoption" ${settings.notify_new_adoption !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="font-medium">Animal Status Changes</p>
                            <p class="text-secondary text-sm">Notify interested adopters when animal status changes</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="notify_animal_status" ${settings.notify_animal_status !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="font-medium">Low Inventory Alerts</p>
                            <p class="text-secondary text-sm">Notify staff when inventory items are running low</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="notify_low_inventory" ${settings.notify_low_inventory !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="font-medium">Medical Reminders</p>
                            <p class="text-secondary text-sm">Send reminders for upcoming vaccinations and treatments</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="notify_medical_reminders" ${settings.notify_medical_reminders !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium">Daily Summary</p>
                            <p class="text-secondary text-sm">Send daily activity summary to administrators</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="notify_daily_summary" ${settings.notify_daily_summary ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                ${this.renderSaveButton('notifications')}
            </form>
        `;
        
        this.setupFormHandler('notification-settings-form', 'notifications');
    },
    
    /**
     * Render email settings
     */
    renderEmailSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        const settings = this.settings.email || {};
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">Email Configuration</h3>
            
            <div class="p-4 bg-warning-bg rounded-lg mb-6">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <p class="text-sm">Email settings contain sensitive information. Only modify if you know what you're doing.</p>
                </div>
            </div>
            
            <form id="email-settings-form">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-input" 
                               value="${settings.smtp_host || ''}" placeholder="smtp.example.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-input" 
                               value="${settings.smtp_port || 587}" placeholder="587">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-input" 
                               value="${settings.smtp_username || ''}" placeholder="username">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-input" 
                               value="" placeholder="••••••••">
                        <p class="form-hint">Leave blank to keep current password</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Encryption</label>
                    <select name="smtp_encryption" class="form-select" style="width: 200px;">
                        <option value="tls" ${settings.smtp_encryption === 'tls' ? 'selected' : ''}>TLS</option>
                        <option value="ssl" ${settings.smtp_encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                        <option value="none" ${settings.smtp_encryption === 'none' ? 'selected' : ''}>None</option>
                    </select>
                </div>
                
                <hr class="my-6">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-input" 
                               value="${settings.from_email || ''}" placeholder="noreply@shelter.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-input" 
                               value="${settings.from_name || ''}" placeholder="Catarman Dog Pound">
                    </div>
                </div>
                
                <div class="flex items-center gap-4 mt-6 pt-6 border-t">
                    <button type="button" class="btn btn-secondary" onclick="SettingsPage.testEmail()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Test Connection
                    </button>
                    <div class="flex-1"></div>
                    <button type="submit" class="btn btn-primary">
                        Save Settings
                    </button>
                </div>
            </form>
        `;
        
        this.setupFormHandler('email-settings-form', 'email');
    },
    
    /**
     * Render backup settings
     */
    renderBackupSettings() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">Backup & Data Management</h3>
            
            <!-- Database Backup -->
            <div class="mb-8">
                <h4 class="font-medium mb-4">Database Backup</h4>
                <p class="text-secondary mb-4">Create a backup of your database. This includes all animals, adoptions, users, and other data.</p>
                
                <div class="flex items-center gap-4">
                    <button class="btn btn-primary" onclick="SettingsPage.createBackup()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Download Backup
                    </button>
                    <span class="text-secondary text-sm">Last backup: ${this.settings.last_backup ? Utils.formatDateTime(this.settings.last_backup) : 'Never'}</span>
                </div>
            </div>
            
            <!-- Restore Backup -->
            <div class="mb-8 pt-8 border-t">
                <h4 class="font-medium mb-4">Restore Backup</h4>
                <p class="text-secondary mb-4">Restore your database from a previous backup file. This will replace all current data.</p>
                
                <div class="p-4 bg-danger-bg rounded-lg mb-4">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <p class="text-sm text-danger">Warning: Restoring a backup will permanently replace all current data. This action cannot be undone.</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <input type="file" id="backup-file" accept=".sql,.zip" style="display: none;">
                    <button class="btn btn-secondary" onclick="document.getElementById('backup-file').click()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Select Backup File
                    </button>
                    <span id="selected-file" class="text-secondary text-sm">No file selected</span>
                </div>
            </div>
            
            <!-- Export Data -->
            <div class="mb-8 pt-8 border-t">
                <h4 class="font-medium mb-4">Export Data</h4>
                <p class="text-secondary mb-4">Export specific data as CSV files for reporting or migration purposes.</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <button class="btn btn-ghost justify-start" onclick="SettingsPage.exportData('animals')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Export Animals
                    </button>
                    <button class="btn btn-ghost justify-start" onclick="SettingsPage.exportData('adoptions')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Export Adoptions
                    </button>
                    <button class="btn btn-ghost justify-start" onclick="SettingsPage.exportData('users')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Export Users
                    </button>
                    <button class="btn btn-ghost justify-start" onclick="SettingsPage.exportData('medical')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Export Medical Records
                    </button>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="pt-8 border-t">
                <h4 class="font-medium mb-2 text-danger">Danger Zone</h4>
                <p class="text-secondary text-sm mb-4">These actions are irreversible. Please proceed with caution.</p>
                
                <div class="space-y-3">
                    <button class="btn btn-outline w-full justify-start" style="border-color: var(--color-danger); color: var(--color-danger);" onclick="SettingsPage.clearLogs()">
                        Clear Activity Logs
                    </button>
                    <button class="btn btn-outline w-full justify-start" style="border-color: var(--color-danger); color: var(--color-danger);" onclick="SettingsPage.resetSystem()">
                        Reset System to Defaults
                    </button>
                </div>
            </div>
        `;
        
        // Setup file input handler
        const fileInput = document.getElementById('backup-file');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    document.getElementById('selected-file').textContent = file.name;
                    this.confirmRestore(file);
                }
            });
        }
    },
    
    /**
     * Render activity logs
     */
    async renderActivityLogs() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        container.innerHTML = `
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-lg">Activity Logs</h3>
                <div class="flex items-center gap-3">
                    <select class="form-select" id="log-filter" style="width: auto;">
                        <option value="">All Actions</option>
                        <option value="LOGIN">Logins</option>
                        <option value="CREATE">Creates</option>
                        <option value="UPDATE">Updates</option>
                        <option value="DELETE">Deletes</option>
                    </select>
                    <button class="btn btn-ghost btn-sm" onclick="SettingsPage.exportLogs()">
                        Export
                    </button>
                </div>
            </div>
            
            <div id="logs-container">
                ${Loading.skeleton('table', { rows: 10, cols: 5 })}
            </div>
            
            <div id="logs-pagination" class="mt-6"></div>
        `;
        
        await this.loadActivityLogs();
        
        // Setup filter
        const filter = document.getElementById('log-filter');
        if (filter) {
            filter.addEventListener('change', () => this.loadActivityLogs(1, filter.value));
        }
    },
    
    /**
     * Load activity logs
     * @param {number} page
     * @param {string} filter
     */
    async loadActivityLogs(page = 1, filter = '') {
        const container = document.getElementById('logs-container');
        if (!container) return;
        
        try {
            const params = { page, per_page: 20 };
            if (filter) params.action_pattern = filter;
            
            const response = await API.system.logs(params);
            
            if (response.success) {
                this.activityLogs = response.data.data || response.data;
                const pagination = response.data.pagination || { page: 1, total: this.activityLogs.length, per_page: 20 };
                
                if (this.activityLogs.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state py-8">
                            <div class="text-4xl mb-4">📋</div>
                            <p class="text-secondary">No activity logs found</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = `
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${this.activityLogs.map(log => `
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="avatar avatar-sm" style="background: ${Utils.stringToColor(log.Email || '')}">
                                                    ${Utils.getInitials(`${log.FirstName || 'S'} ${log.LastName || ''}`)}
                                                </div>
                                                <span>${log.FirstName || 'System'} ${log.LastName || ''}</span>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-gray">${log.Action_Type}</span></td>
                                        <td class="truncate" style="max-width: 300px;">${log.Description || '-'}</td>
                                        <td class="font-mono text-tertiary text-sm">${log.IP_Address || '-'}</td>
                                        <td class="text-tertiary">${Utils.formatDateTime(log.Log_Date)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Render pagination
                this.renderLogsPagination(pagination, filter);
            }
        } catch (error) {
            container.innerHTML = `
                <div class="empty-state py-8">
                    <p class="text-secondary">Failed to load activity logs</p>
                </div>
            `;
        }
    },
    
    /**
     * Render logs pagination
     * @param {Object} pagination
     * @param {string} filter
     */
    renderLogsPagination(pagination, filter) {
        const container = document.getElementById('logs-pagination');
        if (!container) return;
        
        const { page, total, per_page } = pagination;
        const totalPages = Math.ceil(total / per_page);
        
        if (totalPages <= 1) {
            container.innerHTML = `<p class="text-secondary text-sm">Showing ${total} log${total !== 1 ? 's' : ''}</p>`;
            return;
        }
        
        container.innerHTML = `
            <div class="flex items-center justify-between">
                <p class="text-secondary text-sm">Showing ${(page - 1) * per_page + 1} to ${Math.min(page * per_page, total)} of ${total} logs</p>
                <div class="pagination">
                    <button class="pagination-btn" onclick="SettingsPage.loadActivityLogs(${page - 1}, '${filter}')" ${page === 1 ? 'disabled' : ''}>
                        Previous
                    </button>
                    <span class="pagination-btn" style="cursor: default;">${page} / ${totalPages}</span>
                    <button class="pagination-btn" onclick="SettingsPage.loadActivityLogs(${page + 1}, '${filter}')" ${page === totalPages ? 'disabled' : ''}>
                        Next
                    </button>
                </div>
            </div>
        `;
    },
    
    /**
     * Render system info
     */
    async renderSystemInfo() {
        const container = document.getElementById('settings-content');
        if (!container) return;
        
        container.innerHTML = `
            <h3 class="font-semibold text-lg mb-6">System Information</h3>
            <div id="system-info-content">
                ${Loading.skeleton('list', { items: 8, hasAvatar: false })}
            </div>
        `;
        
        try {
            const response = await API.system.info();
            const info = response.success ? response.data : {};
            
            document.getElementById('system-info-content').innerHTML = `
                <div class="space-y-6">
                    <!-- Application Info -->
                    <div>
                        <h4 class="font-medium mb-4">Application</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-secondary rounded-lg">
                                <p class="text-tertiary text-sm mb-1">Version</p>
                                <p class="font-semibold">${info.app_version || '1.0.0'}</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg">
                                <p class="text-tertiary text-sm mb-1">Environment</p>
                                <p class="font-semibold">${info.environment || 'Production'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Server Info -->
                    <div>
                        <h4 class="font-medium mb-4">Server</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-secondary rounded-lg">
                                <p class="text-tertiary text-sm mb-1">PHP Version</p>
                                <p class="font-semibold">${info.php_version || 'N/A'}</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg">
                                <p class="text-tertiary text-sm mb-1">MySQL Version</p>
                                <p class="font-semibold">${info.mysql_version || 'N/A'}</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg">
                                <p class="text-tertiary text-sm mb-1">Server Software</p>
                                <p class="font-semibold">${info.server_software || 'N/A'}</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg">
                                <p class="text-tertiary text-sm mb-1">Server Time</p>
                                <p class="font-semibold">${Utils.formatDateTime(new Date())}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Database Stats -->
                    <div>
                        <h4 class="font-medium mb-4">Database Statistics</h4>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="p-4 bg-secondary rounded-lg text-center">
                                <p class="text-2xl font-bold text-primary">${info.total_animals || 0}</p>
                                <p class="text-tertiary text-sm">Animals</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg text-center">
                                <p class="text-2xl font-bold text-success">${info.total_adoptions || 0}</p>
                                <p class="text-tertiary text-sm">Adoptions</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg text-center">
                                <p class="text-2xl font-bold text-info">${info.total_users || 0}</p>
                                <p class="text-tertiary text-sm">Users</p>
                            </div>
                            <div class="p-4 bg-secondary rounded-lg text-center">
                                <p class="text-2xl font-bold text-warning">${info.total_medical || 0}</p>
                                <p class="text-tertiary text-sm">Medical Records</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Health Check -->
                    <div>
                        <h4 class="font-medium mb-4">System Health</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-secondary rounded-lg">
                                <span>Database Connection</span>
                                <span class="badge ${info.db_connected ? 'badge-success' : 'badge-danger'}">
                                    ${info.db_connected ? 'Connected' : 'Disconnected'}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-secondary rounded-lg">
                                <span>Storage</span>
                                <span class="badge badge-success">OK</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-secondary rounded-lg">
                                <span>Cache</span>
                                <span class="badge ${info.cache_enabled ? 'badge-success' : 'badge-warning'}">
                                    ${info.cache_enabled ? 'Enabled' : 'Disabled'}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="pt-6 border-t">
                        <div class="flex items-center gap-4">
                            <button class="btn btn-secondary" onclick="SettingsPage.clearCache()">
                                Clear Cache
                            </button>
                            <button class="btn btn-ghost" onclick="SettingsPage.renderSystemInfo()">
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            document.getElementById('system-info-content').innerHTML = `
                <div class="empty-state py-8">
                    <p class="text-secondary">Failed to load system information</p>
                </div>
            `;
        }
    },
    
    /**
     * Render save button
     * @param {string} section
     * @param {boolean} inline
     * @returns {string}
     */
    renderSaveButton(section, inline = false) {
        if (inline) {
            return `<button type="submit" class="btn btn-primary">Save Settings</button>`;
        }
        
        return `
            <div class="flex justify-end mt-6 pt-6 border-t">
                <button type="submit" class="btn btn-primary">
                    Save Settings
                </button>
            </div>
        `;
    },
    
    /**
     * Setup form handler
     * @param {string} formId
     * @param {string} section
     */
    setupFormHandler(formId, section) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.saveSettings(section, Form.getData(form));
        });
    },
    
    /**
     * Save settings
     * @param {string} section
     * @param {Object} data
     */
    async saveSettings(section, data) {
        const form = document.getElementById(`${section}-settings-form`);
        const submitBtn = form?.querySelector('button[type="submit"]');
        
        try {
            if (submitBtn) Loading.setButtonLoading(submitBtn, true, 'Saving...');
            
            const response = await API.put(`/settings/${section}`, data);
            
            if (response.success) {
                Toast.success('Settings saved successfully');
                this.settings[section] = { ...this.settings[section], ...data };
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to save settings');
        } finally {
            if (submitBtn) Loading.setButtonLoading(submitBtn, false);
        }
    },
    
    /**
     * Test email configuration
     */
    async testEmail() {
        const loadingModal = Modal.loading('Testing email connection...');
        
        try {
            const form = document.getElementById('email-settings-form');
            const data = Form.getData(form);
            
            const response = await API.post('/settings/email/test', data);
            
            loadingModal.close();
            
            if (response.success) {
                Toast.success('Email test successful! Check your inbox.');
            } else {
                Toast.error(response.message || 'Email test failed');
            }
        } catch (error) {
            loadingModal.close();
            Toast.error(error.message || 'Failed to test email');
        }
    },
    
    /**
     * Create backup
     */
    async createBackup() {
        const confirmed = await Modal.confirm(
            'This will create a backup of your entire database. The download will start automatically.',
            'Create Backup'
        );
        
        if (!confirmed) return;
        
        const loadingModal = Modal.loading('Creating backup...');
        
        try {
            Toast.info('Preparing backup file...');
            
            // This would typically trigger a download
            const response = await API.get('/backup/create');
            
            loadingModal.close();
            
            if (response.success && response.data.download_url) {
                window.location.href = response.data.download_url;
                Toast.success('Backup created successfully');
            }
        } catch (error) {
            loadingModal.close();
            Toast.error(error.message || 'Failed to create backup');
        }
    },
    
    /**
     * Confirm restore
     * @param {File} file
     */
    async confirmRestore(file) {
        const confirmed = await Modal.confirm(
            `Are you sure you want to restore from "${file.name}"? This will replace ALL current data and cannot be undone.`,
            'Restore Backup'
        );
        
        if (!confirmed) {
            document.getElementById('backup-file').value = '';
            document.getElementById('selected-file').textContent = 'No file selected';
            return;
        }
        
        // Double confirmation for safety
        const doubleConfirmed = await Modal.confirm(
            'This is your final warning. All current data will be permanently deleted. Are you absolutely sure?',
            'Final Confirmation'
        );
        
        if (!doubleConfirmed) return;
        
        const loadingModal = Modal.loading('Restoring backup...');
        
        try {
            const response = await API.upload('/backup/restore', file, 'backup');
            
            loadingModal.close();
            
            if (response.success) {
                Toast.success('Backup restored successfully. Reloading...');
                setTimeout(() => window.location.reload(), 2000);
            }
        } catch (error) {
            loadingModal.close();
            Toast.error(error.message || 'Failed to restore backup');
        }
    },
    
    /**
     * Export data
     * @param {string} type
     */
    async exportData(type) {
        Toast.info(`Exporting ${type}...`);
        
        try {
            const response = await API.get(`/export/${type}`);
            
            if (response.success && response.data.download_url) {
                window.location.href = response.data.download_url;
                Toast.success(`${Utils.capitalize(type)} exported successfully`);
            } else if (response.success && response.data) {
                // If data is returned directly, convert to CSV
                const csv = this.convertToCSV(response.data);
                Utils.downloadFile(csv, `${type}-${Utils.toInputDate(new Date())}.csv`, 'text/csv');
                Toast.success(`${Utils.capitalize(type)} exported successfully`);
            }
        } catch (error) {
            Toast.error(error.message || `Failed to export ${type}`);
        }
    },
    
    /**
     * Convert array to CSV
     * @param {Array} data
     * @returns {string}
     */
    convertToCSV(data) {
        if (!data || data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const rows = data.map(row => 
            headers.map(header => {
                const value = row[header];
                // Escape quotes and wrap in quotes if contains comma
                if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                    return `"${value.replace(/"/g, '""')}"`;
                }
                return value ?? '';
            }).join(',')
        );
        
        return [headers.join(','), ...rows].join('\n');
    },
    
    /**
     * Export logs
     */
    async exportLogs() {
        Toast.info('Exporting activity logs...');
        
        try {
            const response = await API.system.logs({ per_page: 10000 });
            
            if (response.success) {
                const logs = response.data.data || response.data;
                const csv = this.convertToCSV(logs);
                Utils.downloadFile(csv, `activity-logs-${Utils.toInputDate(new Date())}.csv`, 'text/csv');
                Toast.success('Logs exported successfully');
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to export logs');
        }
    },
    
    /**
     * Clear logs
     */
    async clearLogs() {
        const confirmed = await Modal.confirm(
            'This will permanently delete all activity logs. This action cannot be undone.',
            'Clear Activity Logs'
        );
        
        if (!confirmed) return;
        
        try {
            const response = await API.delete('/logs/clear');
            
            if (response.success) {
                Toast.success('Activity logs cleared');
                this.renderActivityLogs();
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to clear logs');
        }
    },
    
    /**
     * Clear cache
     */
    async clearCache() {
        try {
            const response = await API.post('/system/clear-cache');
            
            if (response.success) {
                Toast.success('Cache cleared successfully');
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to clear cache');
        }
    },
    
    /**
     * Reset system
     */
    async resetSystem() {
        const confirmed = await Modal.confirm(
            'This will reset ALL settings to their default values. Your data will NOT be affected.',
            'Reset System'
        );
        
        if (!confirmed) return;
        
        try {
            const response = await API.post('/settings/reset');
            
            if (response.success) {
                Toast.success('Settings reset to defaults');
                await this.loadSettings();
                this.renderCurrentTab();
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to reset settings');
        }
    },
    
    /**
     * Add component styles
     */
    addStyles() {
        if (document.getElementById('settings-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'settings-styles';
        styles.textContent = `
            .settings-nav {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            
            .settings-nav-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                border-radius: var(--radius-md);
                font-size: var(--text-sm);
                font-weight: var(--font-medium);
                color: var(--text-secondary);
                text-align: left;
                transition: all var(--transition-fast);
                cursor: pointer;
                border: none;
                background: none;
                width: 100%;
            }
            
            .settings-nav-item:hover {
                background-color: var(--bg-hover);
                color: var(--text-primary);
            }
            
            .settings-nav-item.active {
                background-color: rgba(var(--color-primary-rgb), 0.1);
                color: var(--color-primary);
            }
            
            .settings-nav-item svg {
                flex-shrink: 0;
            }
        `;
        document.head.appendChild(styles);
    }
};

// Make SettingsPage globally available
window.SettingsPage = SettingsPage;