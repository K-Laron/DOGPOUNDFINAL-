/**
 * Profile Page
 * User profile management
 * 
 * @package AnimalShelter
 */

const ProfilePage = {
    /**
     * User data
     */
    user: null,

    /**
     * Activity log
     */
    activityLog: [],

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">Manage your account settings and preferences</p>
                </div>
            </div>
            
            <div class="grid gap-6" style="grid-template-columns: 300px 1fr;">
                <!-- Left Sidebar -->
                <div class="flex flex-col gap-6">
                    <!-- Profile Card -->
                    <div class="card" id="profile-card">
                        ${Loading.skeleton('card', { hasImage: false, lines: 4 })}
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="card" id="quick-stats">
                        ${Loading.skeleton('list', { items: 3, hasAvatar: false })}
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="flex flex-col gap-6">
                    <!-- Tabs Card -->
                    <div class="card">
                        <div class="card-header" style="border-bottom: none;">
                            <div class="tabs" id="profile-tabs">
                                <button class="tab active" data-tab="personal">Personal Info</button>
                                <button class="tab" data-tab="security">Security</button>
                                ${!Auth.isAdopter() ? '<button class="tab" data-tab="activity">Activity</button>' : ''}
                                <button class="tab" data-tab="preferences">Preferences</button>
                                ${Auth.currentUser().role === 'Veterinarian' ? '<button class="tab" data-tab="veterinarian">Vet Profile</button>' : ''}
                            </div>
                        </div>
                        
                        <div class="card-body" id="tab-content">
                            ${Loading.skeleton('text', { lines: 5 })}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        await this.loadProfile();
        this.setupEventListeners();
    },

    /**
     * Load user profile
     */
    async loadProfile() {
        try {
            const response = await API.users.profile();

            if (response.success) {
                this.user = response.data;

                // Update global auth state to sync header/sidebar
                try {
                    Auth.updateUserProfile(this.user);
                } catch (e) {
                    console.warn('Failed to sync auth state:', e);
                }

                // DEBUG: Check if vet details exist
                if (this.user.Role_Name === 'Veterinarian') {
                    if (this.user.veterinarian_details) {
                        // console.log('Vet details loaded:', this.user.veterinarian_details);
                    } else {
                        Toast.warning('Debug: No veterinarian details found in profile response');
                    }
                }

                this.renderProfileCard();
                this.renderQuickStats();
                this.renderPersonalInfoTab();
            }
        } catch (error) {
            console.error('Failed to load profile:', error);

            // If user is not found (404), likely deleted account
            if (error.status === 404) {
                Toast.error('Account not found. Logging out...');
                setTimeout(() => Auth.logout(), 1500);
                return;
            }

            Toast.error('Failed to load profile');
        }
    },

    /**
     * Render profile card
     */
    renderProfileCard() {
        const container = document.getElementById('profile-card');
        if (!container || !this.user) return;

        const fullName = `${this.user.first_name} ${this.user.last_name}`;

        container.innerHTML = `
            <div class="card-body text-center">
                <!-- Avatar with upload option -->
                <div class="relative inline-block mb-4">
                    <div class="avatar avatar-xl mx-auto" style="background: ${Utils.stringToColor(this.user.email)}; width: 100px; height: 100px; font-size: 36px;">
                        ${this.user.avatar_url
                ? `<img src="${this.user.avatar_url}" alt="${fullName}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`
                : Utils.getInitials(fullName)
            }
                    </div>
                    <button 
                        class="btn-icon" 
                        style="position: absolute; bottom: 0; right: 0; background: var(--bg-primary); border: 2px solid var(--bg-primary); box-shadow: var(--shadow-md); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;"
                        onclick="ProfilePage.showAvatarUpload()"
                        title="Change photo"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </button>
                </div>
                
                <h3 class="font-semibold text-xl mb-1">${fullName}</h3>
                <p class="text-secondary mb-3">${this.user.email}</p>
                <span class="badge ${this.getRoleBadgeClass(this.user.role)}">${this.user.role}</span>
                
                <div class="mt-6 pt-6 border-t">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary">Status</span>
                        <span class="badge ${Utils.getStatusBadgeClass(this.user.account_status)}">${this.user.account_status}</span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary">Member since</span>
                        <span>${Utils.formatDate(this.user.created_at, 'short')}</span>
                    </div>
                    ${this.user.last_login ? `
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary">Last login</span>
                            <span>${Utils.timeAgo(this.user.last_login)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    },

    /**
     * Render quick stats
     */
    renderQuickStats() {
        const container = document.getElementById('quick-stats');
        if (!container || !this.user) return;

        let statsHtml = '';

        if (this.user.role === 'Adopter') {
            statsHtml = `
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Adoption Requests</span>
                    <span class="font-semibold">${this.user.stats?.adoption_requests || 0}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Completed Adoptions</span>
                    <span class="font-semibold text-success">${this.user.stats?.completed_adoptions || 0}</span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-secondary">Pending Requests</span>
                    <span class="font-semibold text-warning">${this.user.stats?.pending_requests || 0}</span>
                </div>
            `;
        } else if (this.user.role === 'Veterinarian') {
            statsHtml = `
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Medical Records</span>
                    <span class="font-semibold">${this.user.stats?.medical_records || 0}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Animals Treated</span>
                    <span class="font-semibold">${this.user.stats?.animals_treated || 0}</span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-secondary">This Month</span>
                    <span class="font-semibold text-primary">${this.user.stats?.records_this_month || 0}</span>
                </div>
            `;
        } else if (this.user.role === 'Staff' || this.user.role === 'Admin') {
            statsHtml = `
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Animals Registered</span>
                    <span class="font-semibold">${this.user.stats?.animals_registered || 0}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Adoptions Processed</span>
                    <span class="font-semibold">${this.user.stats?.adoptions_processed || 0}</span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-secondary">Invoices Created</span>
                    <span class="font-semibold">${this.user.stats?.invoices_created || 0}</span>
                </div>
            `;
        } else {
            statsHtml = `
                <div class="flex justify-between items-center py-3 border-b">
                    <span class="text-secondary">Account Age</span>
                    <span class="font-semibold">${Utils.timeAgo(this.user.created_at)}</span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-secondary">Profile Completion</span>
                    <span class="font-semibold">${this.calculateProfileCompletion()}%</span>
                </div>
            `;
        }

        container.innerHTML = `
            <div class="card-header">
                <h3 class="card-title">Quick Stats</h3>
            </div>
            <div class="card-body">
                ${statsHtml}
            </div>
        `;
    },

    /**
     * Calculate profile completion percentage
     * @returns {number}
     */
    calculateProfileCompletion() {
        const fields = ['first_name', 'last_name', 'email', 'contact_number', 'avatar_url'];
        const filled = fields.filter(f => this.user[f]).length;
        return Math.round((filled / fields.length) * 100);
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        const tabs = document.getElementById('profile-tabs');
        if (tabs) {
            tabs.addEventListener('click', (e) => {
                const tab = e.target.closest('.tab');
                if (tab) {
                    tabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    this.switchTab(tab.dataset.tab);
                }
            });
        }
    },

    /**
     * Switch tab
     * @param {string} tab
     */
    switchTab(tab) {
        switch (tab) {
            case 'personal':
                this.renderPersonalInfoTab();
                break;
            case 'security':
                this.renderSecurityTab();
                break;
            case 'activity':
                this.renderActivityTab();
                break;
            case 'preferences':
                this.renderPreferencesTab();
                break;
            case 'veterinarian':
                this.renderVeterinarianTab();
                break;
        }
    },

    /**
     * Render personal info tab
     */
    renderPersonalInfoTab() {
        const container = document.getElementById('tab-content');
        if (!container || !this.user) return;

        container.innerHTML = `
            <form id="personal-info-form">
                <h4 class="font-semibold mb-4">Basic Information</h4>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label required" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" 
                               value="${this.user.first_name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" 
                               value="${this.user.last_name || ''}" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="${this.user.username || ''}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="${this.user.email || ''}" required>
                    <p class="form-hint">Changing your email will require re-verification</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-input" 
                           value="${this.user.contact_number || ''}" placeholder="09171234567">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" name="address" class="form-textarea" rows="2" 
                              placeholder="Enter your address">${this.user.address || ''}</textarea>
                </div>
                
                <div class="flex justify-end mt-6 pt-6 border-t">
                    <button type="button" class="btn btn-ghost mr-3" onclick="ProfilePage.loadProfile()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        `;

        // Setup form handler
        const form = document.getElementById('personal-info-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.updatePersonalInfo();
            });
        }
    },

    /**
     * Render security tab
     */
    renderSecurityTab() {
        const container = document.getElementById('tab-content');
        if (!container) return;

        container.innerHTML = `
            <!-- Change Password Section -->
            <div class="mb-8">
                <h4 class="font-semibold mb-4">Change Password</h4>
                <form id="password-form">
                    <div class="form-group">
                        <label class="form-label required" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-input" required placeholder="Enter current password">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-input" required minlength="8" placeholder="Enter new password">
                        <p class="form-hint">Minimum 8 characters with a mix of letters and numbers</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-input" required placeholder="Confirm new password">
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="mb-4" id="password-strength" style="display: none;">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-secondary">Password Strength</span>
                            <span class="text-sm font-medium" id="strength-label">Weak</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar" id="strength-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
            

            
            <!-- Active Sessions -->
            <div class="mb-8 pt-8 border-t">
                <h4 class="font-semibold mb-4">Active Sessions</h4>
                <div class="space-y-3">
                    <div class="p-4 bg-secondary rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="avatar" style="background: var(--color-success);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                </div>
                                <div>
                                    <p class="font-medium">Current Session</p>
                                    <p class="text-tertiary text-sm">This device • Active now</p>
                                </div>
                            </div>
                            <span class="badge badge-success">Active</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-ghost btn-sm mt-4 text-danger" onclick="ProfilePage.logoutAllSessions()">
                    Log out of all other sessions
                </button>
            </div>
            
            <!-- Danger Zone -->
            <div class="pt-8 border-t">
                <h4 class="font-semibold mb-2 text-danger">Danger Zone</h4>
                <p class="text-secondary text-sm mb-4">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                <button class="btn btn-outline" style="border-color: var(--color-danger); color: var(--color-danger);" 
                        onclick="ProfilePage.showDeleteAccountModal()">
                    Delete Account
                </button>
            </div>
        `;

        // Setup password form handler
        const form = document.getElementById('password-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.updatePassword();
            });

            // Password strength indicator
            const newPasswordInput = document.getElementById('new_password');
            newPasswordInput?.addEventListener('input', (e) => {
                this.updatePasswordStrength(e.target.value);
            });
        }


    },

    /**
     * Update password strength indicator
     * @param {string} password
     */
    updatePasswordStrength(password) {
        const container = document.getElementById('password-strength');
        const bar = document.getElementById('strength-bar');
        const label = document.getElementById('strength-label');

        if (!password) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';

        const validation = Utils.validatePassword(password);
        const strength = validation.strength;

        const strengthConfig = {
            1: { width: '20%', color: 'danger', label: 'Very Weak' },
            2: { width: '40%', color: 'danger', label: 'Weak' },
            3: { width: '60%', color: 'warning', label: 'Fair' },
            4: { width: '80%', color: 'success', label: 'Strong' },
            5: { width: '100%', color: 'success', label: 'Very Strong' }
        };

        const config = strengthConfig[strength] || strengthConfig[1];

        bar.style.width = config.width;
        bar.className = `progress-bar ${config.color}`;
        label.textContent = config.label;
        label.style.color = `var(--color-${config.color})`;
    },

    /**
     * Render activity tab
     */
    async renderActivityTab() {
        const container = document.getElementById('tab-content');
        if (!container) return;

        container.innerHTML = `
            <div class="flex items-center justify-between mb-6">
                <h4 class="font-semibold">Recent Activity</h4>
                <select class="form-select" id="activity-filter" style="width: auto;">
                    <option value="all">All Activity</option>
                    <option value="login">Logins</option>
                    <option value="update">Updates</option>
                    <option value="action">Actions</option>
                </select>
            </div>
            <div id="activity-list">
                ${Loading.skeleton('list', { items: 5 })}
            </div>
        `;

        await this.loadActivityLog();

        // Setup filter
        const filter = document.getElementById('activity-filter');
        if (filter) {
            filter.addEventListener('change', () => this.loadActivityLog(filter.value));
        }
    },

    /**
     * Load activity log
     * @param {string} filter
     */
    async loadActivityLog(filter = 'all') {
        const container = document.getElementById('activity-list');
        if (!container) return;

        try {
            const params = { limit: 30 };
            if (filter !== 'all') {
                params.action_pattern = filter.toUpperCase();
            }

            const response = await API.system.userLogs(this.user.id, params);

            if (response.success) {
                // Backend returns { user, logs } in paginated response data
                this.activityLog = response.data.logs || [];

                if (this.activityLog.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state py-8">
                            <div class="text-4xl mb-4">📋</div>
                            <p class="text-secondary">No activity recorded yet</p>
                        </div>
                    `;
                    return;
                }

                // Group by date
                const grouped = Utils.groupBy(this.activityLog, (item) => {
                    return Utils.formatDate(item.Log_Date, 'medium');
                });

                container.innerHTML = Object.entries(grouped).map(([date, logs]) => `
                    <div class="mb-6">
                        <p class="text-sm font-medium text-secondary mb-3">${date}</p>
                        <div class="space-y-1">
                            ${logs.map(log => `
                                <div class="flex items-start gap-4 p-3 rounded-lg hover:bg-hover transition-colors">
                                    <div class="avatar avatar-sm" style="background: var(--bg-tertiary); flex-shrink: 0;">
                                        ${this.getActivityIcon(log.Action_Type)}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm">
                                            <span class="font-medium">${this.formatActionType(log.Action_Type)}</span>
                                        </p>
                                        ${log.Description ? `<p class="text-tertiary text-xs truncate">${log.Description}</p>` : ''}
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-tertiary text-xs">${Utils.formatTime(log.Log_Date)}</p>
                                        ${log.IP_Address ? `<p class="text-tertiary text-xs font-mono">${log.IP_Address}</p>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Load activity log error:', error);
            container.innerHTML = `
                <div class="empty-state py-8">
                    <p class="text-secondary text-danger">Failed to load: ${error.message}</p>
                    <button class="btn btn-ghost btn-sm mt-2" onclick="ProfilePage.loadActivityLog()">Try Again</button>
                </div>
            `;
        }
    },

    /**
     * Render preferences tab
     */
    renderPreferencesTab() {
        const container = document.getElementById('tab-content');
        if (!container) return;

        const preferences = this.user.preferences || {};

        container.innerHTML = `
            <form id="preferences-form">
                <!-- Appearance -->
                <div class="mb-8">
                    <h4 class="font-semibold mb-4">Appearance</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Theme</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="theme-option ${Store.get('theme') === 'light' ? 'active' : ''}">
                                <input type="radio" name="theme" value="light" ${Store.get('theme') === 'light' ? 'checked' : ''}>
                                <div class="theme-preview light">
                                    <div class="theme-preview-sidebar"></div>
                                    <div class="theme-preview-content"></div>
                                </div>
                                <span>Light</span>
                            </label>
                            <label class="theme-option ${Store.get('theme') === 'dark' ? 'active' : ''}">
                                <input type="radio" name="theme" value="dark" ${Store.get('theme') === 'dark' ? 'checked' : ''}>
                                <div class="theme-preview dark">
                                    <div class="theme-preview-sidebar"></div>
                                    <div class="theme-preview-content"></div>
                                </div>
                                <span>Dark</span>
                            </label>
                            <label class="theme-option ${Store.get('theme') === 'auto' ? 'active' : ''}">
                                <input type="radio" name="theme" value="auto" ${Store.get('theme') === 'auto' ? 'checked' : ''}>
                                <div class="theme-preview auto">
                                    <div class="theme-preview-sidebar"></div>
                                    <div class="theme-preview-content"></div>
                                </div>
                                <span>System</span>
                            </label>
                        </div>
                    </div>
                </div>
                

                
                <!-- Regional Settings -->
                <div class="mb-8 pt-8 border-t">
                    <h4 class="font-semibold mb-4">Regional Settings</h4>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Language</label>
                            <select name="language" class="form-select">
                                <option value="en" ${preferences.language === 'en' ? 'selected' : ''}>English</option>
                                <option value="fil" ${preferences.language === 'fil' ? 'selected' : ''}>Filipino</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Date Format</label>
                            <select name="date_format" class="form-select">
                                <option value="MMM DD, YYYY" ${preferences.date_format === 'MMM DD, YYYY' ? 'selected' : ''}>MMM DD, YYYY (Dec 21, 2025)</option>
                                <option value="MMMM DD, YYYY" ${preferences.date_format === 'MMMM DD, YYYY' ? 'selected' : ''}>MMMM DD, YYYY (December 21, 2025)</option>
                                <option value="MM/DD/YYYY" ${preferences.date_format === 'MM/DD/YYYY' ? 'selected' : ''}>MM/DD/YYYY (12/21/2025)</option>
                                <option value="DD/MM/YYYY" ${preferences.date_format === 'DD/MM/YYYY' ? 'selected' : ''}>DD/MM/YYYY (21/12/2025)</option>
                                <option value="YYYY-MM-DD" ${preferences.date_format === 'YYYY-MM-DD' ? 'selected' : ''}>YYYY-MM-DD (2025-12-21)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end pt-6 border-t">
                    <button type="submit" class="btn btn-primary">
                        Save Preferences
                    </button>
                </div>
            </form>
        `;

        // Add theme preview styles
        this.addThemePreviewStyles();

        // Setup form handler
        const form = document.getElementById('preferences-form');
        if (form) {
            // Populate form data
            try {
                const safePreferences = {
                    language: preferences.language || 'en',
                    date_format: preferences.date_format || 'MMM DD, YYYY'
                };
                Form.setData(form, safePreferences);
            } catch (e) {
                console.warn('Failed to populate preferences form:', e);
            }

            // Theme change instant preview
            form.querySelectorAll('input[name="theme"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    Store.setTheme(e.target.value);
                    form.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
                    e.target.closest('.theme-option').classList.add('active');
                });
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.updatePreferences();
            });
        }
    },

    /**
     * Add theme preview styles
     */
    addThemePreviewStyles() {
        if (document.getElementById('theme-preview-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'theme-preview-styles';
        styles.textContent = `
            .theme-option {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }
            
            .theme-option input {
                display: none;
            }
            
            .theme-preview {
                width: 100%;
                aspect-ratio: 16/10;
                border-radius: var(--radius-md);
                border: 2px solid var(--border-color);
                display: flex;
                overflow: hidden;
                transition: border-color 0.2s;
            }
            
            .theme-option.active .theme-preview {
                border-color: var(--color-primary);
            }
            
            .theme-preview-sidebar {
                width: 30%;
                height: 100%;
            }
            
            .theme-preview-content {
                flex: 1;
                height: 100%;
            }
            
            .theme-preview.light .theme-preview-sidebar { background: #f5f5f7; }
            .theme-preview.light .theme-preview-content { background: #ffffff; }
            
            .theme-preview.dark .theme-preview-sidebar { background: #1c1c1e; }
            .theme-preview.dark .theme-preview-content { background: #000000; }
            
            .theme-preview.auto .theme-preview-sidebar { background: linear-gradient(to right, #f5f5f7 50%, #1c1c1e 50%); }
            .theme-preview.auto .theme-preview-content { background: linear-gradient(to right, #ffffff 50%, #000000 50%); }
            
            .theme-option span {
                font-size: var(--text-sm);
                color: var(--text-secondary);
            }
            
            .theme-option.active span {
                color: var(--color-primary);
                font-weight: var(--font-medium);
            }
        `;
        document.head.appendChild(styles);
    },

    /**
     * Render veterinarian tab
     */
    renderVeterinarianTab() {
        const container = document.getElementById('tab-content');
        if (!container || !this.user) return;

        // Backend returns 'veterinarian_details', but we fallback to empty object
        const vetInfo = this.user.veterinarian_details || {};

        container.innerHTML = `
            <div class="mb-6 p-4 bg-secondary rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="avatar" style="background: var(--color-success);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div>
                        <p class="font-medium">Verified Veterinarian</p>
                        <p class="text-secondary text-sm">Your professional credentials have been verified</p>
                    </div>
                </div>
            </div>
            
            <form id="vet-info-form">
                <h4 class="font-semibold mb-4">Professional Information</h4>
                
                <div class="form-group">
                    <label class="form-label required" for="license_number">License Number</label>
                    <input type="text" id="license_number" name="license_number" class="form-input" 
                           value="${vetInfo.license_number || ''}" required placeholder="Enter your license number">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="specialization">Specialization</label>
                    <input type="text" id="specialization" name="specialization" class="form-input" 
                           value="${vetInfo.specialization || ''}" placeholder="e.g., Surgery, Internal Medicine, Dermatology">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="years_experience">Years of Experience</label>
                    <input type="number" id="years_experience" name="years_experience" class="form-input" 
                           value="${vetInfo.years_experience || ''}" min="0" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="clinic_name">Clinic/Hospital Name</label>
                    <input type="text" id="clinic_name" name="clinic_name" class="form-input" 
                           value="${vetInfo.clinic_name || ''}" placeholder="Enter clinic or hospital name">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bio">Professional Bio</label>
                    <textarea id="bio" name="bio" class="form-textarea" rows="4" 
                              placeholder="Brief description of your experience and expertise">${vetInfo.bio || ''}</textarea>
                </div>
                
                <div class="flex justify-end mt-6 pt-6 border-t">
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        `;

        // Setup form handler
        const form = document.getElementById('vet-info-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.updateVetInfo();
            });
        }
    },

    /**
     * Update veterinarian info
     */
    async updateVetInfo() {
        const form = document.getElementById('vet-info-form');
        if (!form || !Form.validate(form)) return;

        const data = Form.getData(form);
        const submitBtn = form.querySelector('button[type="submit"]');

        try {
            Loading.setButtonLoading(submitBtn, true, 'Saving...');

            // API endpoint to update profile (includes vet details logic in backend)
            // Was incorrect: API.put('/profile/veterinarian', data);
            const response = await API.users.updateProfile(data);

            if (response.success) {
                Toast.success('Veterinarian profile updated successfully');

                // Update local user data - merging correctly
                // Ensure we initialize veterinarian_details if it doesn't exist
                this.user.veterinarian_details = {
                    ...(this.user.veterinarian_details || {}),
                    ...data
                };

                // Also update Auth store if needed
                Auth.updateUserProfile({ veterinarian_details: this.user.veterinarian_details });

                // Refresh page after delay to ensure data consistency
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to update veterinarian profile');
        } finally {
            Loading.setButtonLoading(submitBtn, false);
        }
    },

    /**
     * Update personal info
     */
    async updatePersonalInfo() {
        const form = document.getElementById('personal-info-form');
        if (!form || !Form.validate(form)) return;

        const data = Form.getData(form);
        const submitBtn = form.querySelector('button[type="submit"]');

        try {
            Loading.setButtonLoading(submitBtn, true, 'Saving...');

            const response = await API.users.updateProfile(data);

            if (response.success) {
                Toast.success('Profile updated successfully');

                // Update local user data
                this.user = { ...this.user, ...data };
                Auth.updateUserProfile(data);

                this.renderProfileCard();
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to update profile');
        } finally {
            Loading.setButtonLoading(submitBtn, false);
        }
    },

    /**
     * Update password
     */
    async updatePassword() {
        const form = document.getElementById('password-form');
        if (!form || !Form.validate(form)) return;

        const data = Form.getData(form);

        // Validate password match
        if (data.new_password !== data.confirm_password) {
            Toast.error('New passwords do not match');
            return;
        }

        // Validate password strength
        const validation = Utils.validatePassword(data.new_password);
        if (!validation.isValid) {
            Toast.error('Please choose a stronger password');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');

        try {
            Loading.setButtonLoading(submitBtn, true, 'Updating...');

            const response = await Auth.changePassword(data.current_password, data.new_password);

            if (response.success) {
                Toast.success('Password updated successfully');
                Form.reset(form);
                document.getElementById('password-strength').style.display = 'none';
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to update password');
        } finally {
            Loading.setButtonLoading(submitBtn, false);
        }
    },

    /**
     * Update preferences
     */
    async updatePreferences() {
        const form = document.getElementById('preferences-form');
        if (!form) return;

        const data = Form.getData(form);
        const submitBtn = form.querySelector('button[type="submit"]');

        try {
            Loading.setButtonLoading(submitBtn, true, 'Saving...');

            // Save theme locally
            Store.setTheme(data.theme);

            // Save other preferences to server
            const preferences = {
                email_notifications: data.email_notifications,
                adoption_notifications: data.adoption_notifications,
                new_animal_notifications: data.new_animal_notifications,
                language: data.language,
                date_format: data.date_format
            };

            const response = await API.users.updateProfile({ preferences });

            if (response.success) {
                Toast.success('Preferences saved successfully');
                this.user.preferences = preferences;
                Auth.updateUserProfile({ preferences });
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to save preferences');
        } finally {
            Loading.setButtonLoading(submitBtn, false);
        }
    },



    /**
     * Show avatar upload modal
     */
    showAvatarUpload() {
        Modal.open({
            title: 'Change Profile Photo',
            content: `
                <div class="text-center">
                    <div id="avatar-preview" class="mb-6">
                        <div class="avatar avatar-xl mx-auto" style="background: ${Utils.stringToColor(this.user.email)}; width: 120px; height: 120px; font-size: 42px;">
                            ${this.user.avatar_url
                    ? `<img src="${this.user.avatar_url}" alt="Avatar" id="preview-img" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`
                    : Utils.getInitials(`${this.user.first_name} ${this.user.last_name}`)
                }
                        </div>
                    </div>
                    
                    <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                    
                    <div class="flex justify-center gap-3">
                        <button class="btn btn-secondary" onclick="document.getElementById('avatar-input').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            Upload Photo
                        </button>
                        ${this.user.avatar_url ? `
                            <button class="btn btn-ghost text-danger" onclick="ProfilePage.removeAvatar()">
                                Remove
                            </button>
                        ` : ''}
                    </div>
                    
                    <p class="text-tertiary text-xs mt-4">JPG, PNG or GIF. Max 2MB.</p>
                </div>
            `,
            footer: `
                <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                <button class="btn btn-primary" id="save-avatar-btn" disabled onclick="ProfilePage.saveAvatar()">Save</button>
            `
        });

        // Setup file input handler
        setTimeout(() => {
            const input = document.getElementById('avatar-input');
            if (input) {
                input.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        // Validate file
                        if (file.size > 2 * 1024 * 1024) {
                            Toast.error('File size must be less than 2MB');
                            return;
                        }

                        if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                            Toast.error('Only JPG, PNG and GIF files are allowed');
                            return;
                        }

                        // Preview
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const preview = document.getElementById('avatar-preview');
                            preview.innerHTML = `
                                <div class="avatar avatar-xl mx-auto" style="width: 120px; height: 120px; overflow: hidden;">
                                    <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                </div>
                            `;
                            document.getElementById('save-avatar-btn').disabled = false;
                        };
                        reader.readAsDataURL(file);

                        // Store file for upload
                        this.pendingAvatar = file;
                    }
                });
            }
        }, 100);
    },

    /**
     * Save avatar
     */
    async saveAvatar() {
        if (!this.pendingAvatar) return;

        const btn = document.getElementById('save-avatar-btn');

        try {
            Loading.setButtonLoading(btn, true, 'Uploading...');

            const response = await API.upload('/profile/avatar', this.pendingAvatar, 'avatar');

            if (response.success) {
                Toast.success('Profile photo updated');
                this.user.avatar_url = response.data.avatar_url;
                Auth.updateUserProfile({ avatar_url: response.data.avatar_url });
                this.renderProfileCard();
                if (window.Sidebar && typeof Sidebar.updateProfile === 'function') {
                    Sidebar.updateProfile();
                }
                Modal.closeAll();
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to upload photo');
        } finally {
            Loading.setButtonLoading(btn, false);
            this.pendingAvatar = null;
        }
    },

    /**
     * Remove avatar
     */
    async removeAvatar() {
        try {
            const response = await API.delete('/profile/avatar');

            if (response.success) {
                Toast.success('Profile photo removed');
                this.user.avatar_url = null;
                Auth.updateUserProfile({ avatar_url: null });
                this.renderProfileCard();
                if (window.Sidebar && typeof Sidebar.updateProfile === 'function') {
                    Sidebar.updateProfile();
                }
                Modal.closeAll();
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to remove photo');
        }
    },

    /**
     * Toggle two-factor authentication
     * @param {boolean} enabled
     */
    async toggleTwoFactor(enabled) {
        if (enabled) {
            // Show setup modal
            Toast.info('Two-factor authentication setup is not yet implemented');
            document.getElementById('two-factor').checked = false;
        } else {
            // Confirm disable
            const confirmed = await Modal.confirm(
                'Are you sure you want to disable two-factor authentication? This will make your account less secure.',
                'Disable 2FA'
            );

            if (!confirmed) {
                document.getElementById('two-factor').checked = true;
            }
        }
    },

    /**
     * Logout all other sessions
     */
    async logoutAllSessions() {
        const confirmed = await Modal.confirm(
            'This will log you out of all other devices and browsers. You will need to log in again on those devices.',
            'Log Out All Sessions'
        );

        if (confirmed) {
            try {
                await API.post('/auth/logout-all');
                Toast.success('Logged out of all other sessions');
            } catch (error) {
                Toast.error(error.message || 'Failed to logout other sessions');
            }
        }
    },

    /**
     * Show delete account modal
     */
    showDeleteAccountModal() {
        Modal.open({
            title: 'Delete Account',
            content: `
                <div class="text-center mb-6">
                    <div class="avatar avatar-lg mx-auto mb-4" style="background: var(--color-danger);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Are you absolutely sure?</h3>
                    <p class="text-secondary">This action cannot be undone. This will permanently delete your account and remove all your data from our servers.</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type <strong>DELETE</strong> to confirm</label>
                    <input type="text" id="delete-confirm" class="form-input" placeholder="DELETE">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Enter your password</label>
                    <input type="password" id="delete-password" class="form-input" placeholder="Your password">
                </div>
            `,
            footer: `
                <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                <button class="btn btn-danger" onclick="ProfilePage.deleteAccount()">Delete Account</button>
            `
        });
    },

    /**
     * Delete account
     */
    async deleteAccount() {
        const confirmText = document.getElementById('delete-confirm')?.value;
        const password = document.getElementById('delete-password')?.value;

        if (confirmText !== 'DELETE') {
            Toast.error('Please type DELETE to confirm');
            return;
        }

        if (!password) {
            Toast.error('Please enter your password');
            return;
        }

        try {
            const response = await API.delete('/profile', { password });

            if (response.success) {
                Toast.success('Account deleted successfully');
                Auth.logout();
            }
        } catch (error) {
            Toast.error(error.message || 'Failed to delete account');
        }
    },

    /**
     * Get activity icon based on action type
     * @param {string} actionType
     * @returns {string}
     */
    getActivityIcon(actionType) {
        const icons = {
            'LOGIN': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>',
            'LOGOUT': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>',
            'UPDATE_PROFILE': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
            'CHANGE_PASSWORD': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
        };

        return icons[actionType] || '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle></svg>';
    },

    /**
     * Format action type for display
     * @param {string} actionType
     * @returns {string}
     */
    formatActionType(actionType) {
        const labels = {
            'LOGIN': 'Signed in',
            'LOGOUT': 'Signed out',
            'LOGIN_FAILED': 'Failed login attempt',
            'UPDATE_PROFILE': 'Updated profile',
            'CHANGE_PASSWORD': 'Changed password',
            'CREATE_ADOPTION': 'Submitted adoption request',
            'CREATE_ANIMAL': 'Added an animal',
            'CREATE_MEDICAL': 'Added medical record'
        };

        return labels[actionType] || actionType.replace(/_/g, ' ').toLowerCase();
    },

    /**
     * Get role badge class
     * @param {string} role
     * @returns {string}
     */
    getRoleBadgeClass(role) {
        const colors = {
            'Admin': 'badge-danger',
            'Staff': 'badge-primary',
            'Veterinarian': 'badge-success',
            'Adopter': 'badge-info'
        };
        return colors[role] || 'badge-gray';
    }
};

// Make ProfilePage globally available
window.ProfilePage = ProfilePage;