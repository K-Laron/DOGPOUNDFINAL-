/**
 * Users Page
 * User management (Admin only)
 * 
 * @package AnimalShelter
 */

const UsersPage = {
    /**
     * Page state
     */
    state: {
        users: [],
        roles: [],
        pagination: { page: 1, perPage: 20, total: 0 },
        filters: {
            role: '',
            status: '',
            search: ''
        },
        sort: {
            key: 'created_at',
            direction: 'desc'
        },
        loading: false
    },

    /**
     * Account statuses
     */
    statuses: ['Active', 'Inactive', 'Banned'],

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">User Management</h1>
                    <p class="page-subtitle">Manage system users and their access</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="UsersPage.showAddModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add User
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid mb-6" id="users-stats">
                ${Loading.skeleton('stats', { count: 4 })}
            </div>
            
            <!-- Filters -->
            <div class="card mb-6">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1" style="min-width: 200px;">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search by name or email..." id="search-input">
                            </div>
                        </div>
                        
                        <select class="form-select" id="filter-role" style="width: auto;">
                            <option value="">All Roles</option>
                        </select>
                        
                        <select class="form-select" id="filter-status" style="width: auto;">
                            <option value="">All Statuses</option>
                            ${this.statuses.map(s => `<option value="${s}">${s}</option>`).join('')}
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div id="users-container">
                    ${Loading.skeleton('table', { rows: 5, cols: 6 })}
                </div>
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        await Promise.all([
            this.loadRoles(),
            this.loadStats(),
            this.loadUsers()
        ]);

        this.setupEventListeners();
    },

    /**
     * Load roles
     */
    async loadRoles() {
        try {
            const response = await API.users.roles();

            if (response.success) {
                this.state.roles = response.data;
                this.populateRoleDropdown();
            }
        } catch (error) {
            console.error('Failed to load roles:', error);
        }
    },

    /**
     * Populate role dropdown
     */
    populateRoleDropdown() {
        const select = document.getElementById('filter-role');
        if (!select) return;

        select.innerHTML = `
            <option value="">All Roles</option>
            ${this.state.roles.map(r => `
                <option value="${r.id || r.RoleID}">${r.name || r.Role_Name}</option>
            `).join('')}
        `;
    },

    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const response = await API.get('/users/stats/summary');

            if (response.success) {
                this.renderStats(response.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
            // Render default stats
            this.renderStats({});
        }
    },

    // ... (keeping intermediate code unchanged if possible, but replace_file_content needs contiguous block)
    // Actually, since the edits are far apart (line 121 and line 713), I should use multi_replace_file_content.


    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const response = await API.get('/users/stats/summary');

            if (response.success) {
                this.renderStats(response.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
            // Render default stats
            this.renderStats({});
        }
    },

    /**
     * Render statistics
     * @param {Object} stats
     */
    renderStats(stats) {
        const container = document.getElementById('users-stats');
        if (!container) return;

        container.innerHTML = `
            ${Card.stat({
            title: 'Total Users',
            value: stats.total || 0,
            iconColor: 'primary',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'
        })}
            ${Card.stat({
            title: 'Active Users',
            value: stats.active || 0,
            iconColor: 'success',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
        })}
            ${Card.stat({
            title: 'New This Month',
            value: stats.created_this_month || 0,
            iconColor: 'info',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>'
        })}
            ${Card.stat({
            title: 'Inactive/Banned',
            value: (stats.inactive || 0) + (stats.banned || 0),
            iconColor: 'warning',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>'
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
                this.loadUsers();
            }, 300));
        }

        // Role filter
        const roleFilter = document.getElementById('filter-role');
        if (roleFilter) {
            roleFilter.addEventListener('change', (e) => {
                this.state.filters.role_id = e.target.value;
                this.state.pagination.page = 1;
                this.loadUsers();
            });
        }

        // Status filter
        const statusFilter = document.getElementById('filter-status');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.state.filters.status = e.target.value;
                this.state.pagination.page = 1;
                this.loadUsers();
            });
        }
    },

    /**
     * Load users
     */
    async loadUsers() {
        this.state.loading = true;
        const container = document.getElementById('users-container');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 6 });

        try {
            const params = {
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage,
                sort_by: this.state.sort.key,
                sort_order: this.state.sort.direction,
                ...this.state.filters
            };

            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            const response = await API.users.list(params);

            if (response.success) {
                this.state.users = response.data.data || response.data;
                this.state.pagination.total = response.data.pagination?.total || this.state.users.length;
                this.renderUsers();
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            Toast.error('Failed to load users');
        } finally {
            this.state.loading = false;
        }
    },

    /**
     * Render users table
     */
    renderUsers() {
        const container = document.getElementById('users-container');
        if (!container) return;

        if (this.state.users.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">👥</div>
                    <h3 class="empty-state-title">No users found</h3>
                    <p class="empty-state-description">Try adjusting your filters or add a new user.</p>
                    <button class="btn btn-primary mt-4" onclick="UsersPage.showAddModal()">
                        Add User
                    </button>
                </div>
            `;
            return;
        }

        const currentUserId = Auth.currentUser()?.id;

        container.innerHTML = DataTable.render({
            id: 'users-table',
            columns: [
                {
                    key: 'first_name',
                    label: 'User',
                    render: (val, row) => {
                        const fullName = `${val} ${row.last_name}`;
                        return `
                            <div class="flex items-center gap-3">
                                <div class="avatar" style="background: ${Utils.stringToColor(row.email)}">
                                    ${Utils.getInitials(fullName)}
                                </div>
                                <div>
                                    <p class="font-semibold">${fullName}</p>
                                    <p class="text-tertiary text-xs">${row.email}</p>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    key: 'role_name',
                    label: 'Role',
                    render: val => {
                        const colors = {
                            'Admin': 'badge-danger',
                            'Staff': 'badge-primary',
                            'Veterinarian': 'badge-success',
                            'Adopter': 'badge-info'
                        };
                        return `<span class="badge ${colors[val] || 'badge-gray'}">${val}</span>`;
                    }
                },
                {
                    key: 'contact_number',
                    label: 'Contact',
                    render: val => val || '-'
                },
                {
                    key: 'account_status',
                    label: 'Status',
                    type: 'badge'
                },
                {
                    key: 'created_at',
                    label: 'Joined',
                    type: 'date'
                }
            ],
            data: this.state.users,
            pagination: this.state.pagination,
            actions: {
                view: true,
                edit: true,
                delete: true,
                custom: [
                    {
                        name: 'status',
                        label: 'Change Status',
                        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
                    }
                ]
            },
            onAction: (action, id, row) => this.handleAction(action, id, row),
            onPageChange: (page) => {
                this.state.pagination.page = page;
                this.loadUsers();
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
        const currentUserId = Auth.currentUser()?.id;

        // Prevent actions on self for certain operations
        if (id === currentUserId && ['delete', 'status'].includes(action)) {
            Toast.warning('You cannot perform this action on your own account');
            return;
        }

        switch (action) {
            case 'view':
                this.showDetail(id);
                break;
            case 'edit':
                this.showEditModal(row);
                break;
            case 'delete':
                await this.deleteUser(id, `${row.first_name} ${row.last_name}`);
                break;
            case 'status':
                this.showStatusModal(row);
                break;
        }
    },

    /**
     * Show user detail
     * @param {number} id
     */
    async showDetail(id) {
        try {
            const response = await API.users.get(id);

            if (response.success) {
                const user = response.data;
                const fullName = `${user.first_name} ${user.last_name}`;

                Modal.open({
                    title: 'User Details',
                    size: 'lg',
                    content: `
                        <div class="space-y-6">
                            <!-- Header -->
                            <div class="flex items-center gap-4">
                                <div class="avatar avatar-xl" style="background: ${Utils.stringToColor(user.email)}">
                                    ${Utils.getInitials(fullName)}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-xl">${fullName}</h3>
                                    <p class="text-secondary">${user.email}</p>
                                    <div class="flex gap-2 mt-2">
                                        <span class="badge ${this.getRoleBadgeClass(user.role_name)}">${user.role_name}</span>
                                        <span class="badge ${Utils.getStatusBadgeClass(user.account_status)}">${user.account_status}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Details -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-tertiary text-sm">User ID</p>
                                    <p class="font-medium">#${user.id}</p>
                                </div>
                                <div>
                                    <p class="text-tertiary text-sm">Contact Number</p>
                                    <p class="font-medium">${user.contact_number || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-tertiary text-sm">Joined</p>
                                    <p class="font-medium">${Utils.formatDate(user.created_at)}</p>
                                </div>
                                <div>
                                    <p class="text-tertiary text-sm">Last Updated</p>
                                    <p class="font-medium">${Utils.formatDate(user.updated_at)}</p>
                                </div>
                            </div>
                            
                            <!-- Activity Summary -->
                            ${user.role_name === 'Adopter' ? `
                                <div class="p-4 bg-secondary rounded-lg">
                                    <h4 class="font-semibold mb-3">Adoption History</h4>
                                    <div class="flex gap-6">
                                        <div>
                                            <p class="text-2xl font-bold">${user.adoption_requests || 0}</p>
                                            <p class="text-tertiary text-sm">Total Requests</p>
                                        </div>
                                        <div>
                                            <p class="text-2xl font-bold text-success">${user.completed_adoptions || 0}</p>
                                            <p class="text-tertiary text-sm">Completed</p>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${user.role_name === 'Veterinarian' && user.veterinarian ? `
                                <div class="p-4 bg-secondary rounded-lg">
                                    <h4 class="font-semibold mb-3">Veterinarian Info</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-tertiary text-sm">License Number</p>
                                            <p class="font-medium">${user.veterinarian.License_Number}</p>
                                        </div>
                                        <div>
                                            <p class="text-tertiary text-sm">Specialization</p>
                                            <p class="font-medium">${user.veterinarian.Specialization || '-'}</p>
                                        </div>
                                        <div>
                                            <p class="text-tertiary text-sm">Years Experience</p>
                                            <p class="font-medium">${user.veterinarian.Years_Experience || 0}</p>
                                        </div>
                                        <div>
                                            <p class="text-tertiary text-sm">Medical Records</p>
                                            <p class="font-medium">${user.veterinarian.record_count || 0}</p>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `,
                    footer: `
                        <button class="btn btn-secondary" data-action="cancel">Close</button>
                        <button class="btn btn-primary" onclick="Modal.closeAll(); UsersPage.showEditModal(${JSON.stringify(user).replace(/"/g, '&quot;')})">
                            Edit User
                        </button>
                    `
                });
            }
        } catch (error) {
            Toast.error('Failed to load user details');
        }
    },

    /**
     * Show add user modal
     */
    showAddModal() {
        const fields = this.getFormFields();

        Modal.open({
            title: 'Add New User',
            content: `<form id="add-user-form">${Form.generate(fields)}</form>`,
            size: 'lg',
            confirmText: 'Add User',
            onConfirm: async () => {
                const form = document.getElementById('add-user-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                // Validate password match
                if (data.password !== data.password_confirmation) {
                    Toast.error('Passwords do not match');
                    return false;
                }

                delete data.password_confirmation;

                try {
                    const response = await API.users.create(data);
                    if (response.success) {
                        Toast.success('User added successfully');
                        this.loadUsers();
                        this.loadStats();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to add user');
                    return false;
                }
            }
        });
    },

    /**
     * Show edit user modal
     * @param {Object} user
     */
    showEditModal(user) {
        const fields = this.getFormFields(true);

        Modal.open({
            title: `Edit ${user.first_name} ${user.last_name}`,
            content: `<form id="edit-user-form">${Form.generate(fields, {
                first_name: user.first_name,
                last_name: user.last_name,
                email: user.email,
                contact_number: user.contact_number,
                role_id: user.role_id,
                account_status: user.account_status
            })}</form>`,
            size: 'lg',
            confirmText: 'Save Changes',
            onConfirm: async () => {
                const form = document.getElementById('edit-user-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                // Remove empty password fields
                if (!data.password) {
                    delete data.password;
                    delete data.password_confirmation;
                } else if (data.password !== data.password_confirmation) {
                    Toast.error('Passwords do not match');
                    return false;
                } else {
                    delete data.password_confirmation;
                }

                try {
                    const response = await API.users.update(user.id, data);
                    if (response.success) {
                        Toast.success('User updated successfully');
                        // Auto-refresh page to ensure consistency
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to update user');
                    return false;
                }
            }
        });
    },

    /**
     * Show status change modal
     * @param {Object} user
     */
    showStatusModal(user) {
        Modal.open({
            title: 'Change Account Status',
            content: `
                <div class="mb-4 p-4 bg-secondary rounded-lg">
                    <p class="text-sm">Changing status for <strong>${user.first_name} ${user.last_name}</strong></p>
                    <p class="text-sm text-secondary">Current status: <span class="badge ${Utils.getStatusBadgeClass(user.account_status)}">${user.account_status}</span></p>
                </div>
                <form id="status-form">
                    ${Form.generate([
                {
                    type: 'select',
                    name: 'account_status',
                    label: 'New Status',
                    required: true,
                    options: this.statuses.map(s => ({
                        value: s,
                        label: s,
                        selected: s === user.account_status
                    }))
                }
            ])}
                </form>
            `,
            size: 'sm',
            confirmText: 'Update Status',
            onConfirm: async () => {
                const data = Form.getData('#status-form');

                try {
                    const response = await API.users.update(user.id, {
                        account_status: data.account_status
                    });

                    if (response.success) {
                        Toast.success('Status updated successfully');
                        // Auto-refresh page as requested
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
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
     * Delete user
     * @param {number} id
     * @param {string} name
     */
    async deleteUser(id, name) {
        const confirmed = await Modal.confirmDelete(name);

        if (confirmed) {
            try {
                const response = await API.users.delete(id);
                if (response.success) {
                    Toast.success('User deleted successfully');
                    this.loadUsers();
                    this.loadStats();
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to delete user');
            }
        }
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
    },

    /**
     * Get form fields
     * @param {boolean} isEdit
     * @returns {Array}
     */
    getFormFields(isEdit = false) {
        const fields = [
            {
                type: 'text',
                name: 'first_name',
                label: 'First Name',
                required: true,
                placeholder: 'Enter first name'
            },
            {
                type: 'text',
                name: 'last_name',
                label: 'Last Name',
                required: true,
                placeholder: 'Enter last name'
            },
            {
                type: 'email',
                name: 'email',
                label: 'Email Address',
                required: true,
                placeholder: 'user@example.com'
            },
            {
                type: 'tel',
                name: 'contact_number',
                label: 'Contact Number',
                placeholder: '09171234567'
            },
            {
                type: 'select',
                name: 'role_id',
                label: 'Role',
                required: true,
                options: [
                    { value: '', label: 'Select Role' },
                    ...this.state.roles.map(r => ({
                        value: r.id || r.RoleID,
                        label: r.name || r.Role_Name
                    }))
                ]
            }
        ];

        if (isEdit) {
            fields.push({
                type: 'select',
                name: 'account_status',
                label: 'Account Status',
                required: true,
                options: this.statuses.map(s => ({ value: s, label: s }))
            });

            fields.push({
                type: 'divider'
            });

            fields.push({
                type: 'heading',
                label: 'Change Password (Optional)'
            });
        }

        fields.push({
            type: 'password',
            name: 'password',
            label: isEdit ? 'New Password' : 'Password',
            required: !isEdit,
            placeholder: isEdit ? 'Leave blank to keep current' : 'Enter password',
            hint: 'Minimum 8 characters'
        });

        fields.push({
            type: 'password',
            name: 'password_confirmation',
            label: 'Confirm Password',
            required: !isEdit,
            placeholder: 'Confirm password'
        });

        return fields;
    }
};

// Make UsersPage globally available
window.UsersPage = UsersPage;