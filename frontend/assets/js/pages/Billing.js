/**
 * Billing Page
 * Invoice and payment management
 * 
 * @package AnimalShelter
 */

const BillingPage = {
    /**
     * Page state
     */
    state: {
        invoices: [],
        payments: [],
        pagination: { page: 1, perPage: 20, total: 0 },
        filters: {
            status: '',
            type: '',
            search: ''
        },
        loading: false,
        activeTab: 'invoices' // 'invoices' or 'payments'
    },

    /**
     * Transaction types
     */
    transactionTypes: ['Adoption Fee', 'Reclaim Fee'],

    /**
     * Payment methods
     */
    paymentMethods: ['Cash', 'GCash', 'Bank Transfer'],

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Billing</h1>
                    <p class="page-subtitle">Manage invoices and payments</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" onclick="BillingPage.showReportModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Generate Report
                    </button>
                    <button class="btn btn-primary" onclick="BillingPage.showCreateInvoiceModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Create Invoice
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid mb-6" id="billing-stats">
                ${Loading.skeleton('stats', { count: 4 })}
            </div>
            
            <!-- Tabs -->
            <div class="tabs-pills mb-6" id="billing-tabs">
                <button class="tab active" data-tab="invoices">Invoices</button>
                <button class="tab" data-tab="payments">Payments</button>
            </div>
        
            <!-- Filters -->
            <div class="card mb-6" id="billing-filters-card">
                <div class="card-body">
                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-4" id="filters-container">
                        <div class="flex-1" style="min-width: 200px;">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search..." id="search-input">
                            </div>
                        </div>
                        
                        <div id="invoice-filters">
                            <div class="flex gap-4">
                                <select class="form-select" id="filter-status" style="width: auto;">
                                    <option value="">All Statuses</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                
                                <select class="form-select" id="filter-type" style="width: auto;">
                                    <option value="">All Types</option>
                                    ${this.transactionTypes.map(t => `<option value="${t}">${t}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                        
                        <div id="payment-filters" style="display: none;">
                            <select class="form-select" id="filter-method" style="width: auto;">
                                <option value="">All Methods</option>
                                <option value="All Methods">All Methods</option>
                                ${this.paymentMethods.map(m => `<option value="${m}">${m}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="card">
                <div id="billing-content">
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
            this.loadStats(),
            this.loadInvoices()
        ]);

        this.setupEventListeners();
    },

    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const response = await API.billing.invoiceStats();

            if (response.success) {
                const data = response.data;
                // Map backend keys to frontend expectations if needed
                const stats = {
                    ...data,
                    collected_this_month: data.this_month_collected || 0
                };
                this.renderStats(stats);
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
        const container = document.getElementById('billing-stats');
        if (!container) return;

        container.innerHTML = `
            ${Card.stat({
            title: 'Total Revenue',
            value: Utils.formatCurrency(stats.total_paid || 0),
            iconColor: 'success',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
        })}
            ${Card.stat({
            title: 'This Month',
            value: Utils.formatCurrency(stats.collected_this_month || 0),
            iconColor: 'primary',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
        })}
            ${Card.stat({
            title: 'Outstanding',
            value: Utils.formatCurrency(stats.total_unpaid || 0),
            iconColor: 'warning',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
        })}
            ${Card.stat({
            title: 'Unpaid Invoices',
            value: stats.unpaid_count || 0,
            iconColor: 'danger',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>'
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
                this.loadCurrentTab();
            }, 300));
        }

        // Status filter
        const statusFilter = document.getElementById('filter-status');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.state.filters.status = e.target.value;
                this.state.pagination.page = 1;
                this.loadInvoices();
            });
        }

        // Type filter
        const typeFilter = document.getElementById('filter-type');
        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => {
                this.state.filters.type = e.target.value;
                this.state.pagination.page = 1;
                this.loadInvoices();
            });
        }

        // Method filter
        const methodFilter = document.getElementById('filter-method');
        if (methodFilter) {
            methodFilter.addEventListener('change', (e) => {
                this.state.filters.payment_method = e.target.value;
                this.state.pagination.page = 1;
                this.loadPayments();
            });
        }

        // Tabs
        const tabs = document.getElementById('billing-tabs');
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
        this.state.activeTab = tab;
        this.state.pagination.page = 1;

        const invoiceFilters = document.getElementById('invoice-filters');
        const paymentFilters = document.getElementById('payment-filters');

        if (tab === 'invoices') {
            if (invoiceFilters) invoiceFilters.style.display = 'block';
            if (paymentFilters) paymentFilters.style.display = 'none';
            this.loadInvoices();
        } else {
            if (invoiceFilters) invoiceFilters.style.display = 'none';
            if (paymentFilters) paymentFilters.style.display = 'block';
            this.loadPayments();
        }
    },

    /**
     * Load current tab
     */
    loadCurrentTab() {
        if (this.state.activeTab === 'invoices') {
            this.loadInvoices();
        } else {
            this.loadPayments();
        }
    },

    /**
     * Load invoices
     */
    async loadInvoices() {
        this.state.loading = true;
        const container = document.getElementById('billing-content');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 6 });

        try {
            const params = {
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage,
                ...this.state.filters
            };

            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            const response = await API.billing.invoices(params);

            if (response.success) {
                this.state.invoices = response.data.data || response.data;
                this.state.pagination.total = response.data.pagination?.total || this.state.invoices.length;
                this.renderInvoices();
            }
        } catch (error) {
            console.error('Failed to load invoices:', error);
            Toast.error('Failed to load invoices');
        } finally {
            this.state.loading = false;
        }
    },

    /**
     * Render invoices
     */
    renderInvoices() {
        const container = document.getElementById('billing-content');
        if (!container) return;

        if (this.state.invoices.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">📄</div>
                    <h3 class="empty-state-title">No invoices found</h3>
                    <p class="empty-state-description">Create your first invoice to get started.</p>
                    <button class="btn btn-primary mt-4" onclick="BillingPage.showCreateInvoiceModal()">
                        Create Invoice
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = DataTable.render({
            id: 'invoices-table',
            columns: [
                {
                    key: 'InvoiceID',
                    label: 'Invoice #',
                    render: val => `<span class="font-mono font-semibold">#${String(val).padStart(5, '0')}</span>`
                },
                {
                    key: 'Payer_FirstName',
                    label: 'Customer',
                    render: (val, row) => `
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-sm" style="background: ${Utils.stringToColor(row.Payer_Email || '')}">
                                ${Utils.getInitials(`${val} ${row.Payer_LastName}`)}
                            </div>
                            <div>
                                <p class="font-medium">${val} ${row.Payer_LastName}</p>
                                <p class="text-tertiary text-xs">${row.Payer_Email}</p>
                            </div>
                        </div>
                    `
                },
                {
                    key: 'Transaction_Type',
                    label: 'Type',
                    render: val => `<span class="badge badge-gray">${val}</span>`
                },
                {
                    key: 'Total_Amount',
                    label: 'Amount',
                    render: val => `<span class="font-semibold">${Utils.formatCurrency(val)}</span>`
                },
                {
                    key: 'Balance',
                    label: 'Balance',
                    render: (val, row) => {
                        const balance = row.Total_Amount - (row.Amount_Paid || 0);
                        return balance > 0
                            ? `<span class="text-danger font-semibold">${Utils.formatCurrency(balance)}</span>`
                            : `<span class="text-success">Paid</span>`;
                    }
                },
                {
                    key: 'Status',
                    label: 'Status',
                    type: 'badge'
                },
                {
                    key: 'Created_At',
                    label: 'Date',
                    type: 'date'
                }
            ],
            data: this.state.invoices.map(i => ({ ...i, id: i.InvoiceID })),
            pagination: this.state.pagination,
            actions: {
                view: true,
                custom: [
                    {
                        name: 'payment',
                        label: 'Record Payment',
                        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>'
                    }
                ]
            },
            onAction: (action, id, row) => this.handleInvoiceAction(action, id, row),
            onPageChange: (page) => {
                this.state.pagination.page = page;
                this.loadInvoices();
            },
            sortable: false
        });
    },

    /**
     * Load payments
     */
    async loadPayments() {
        this.state.loading = true;
        const container = document.getElementById('billing-content');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 6 });

        try {
            const params = {
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage,
                search: this.state.filters.search,
                payment_method: this.state.filters.payment_method
            };

            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            const response = await API.billing.payments(params);

            if (response.success) {
                this.state.payments = response.data.data || response.data;
                this.state.pagination.total = response.data.pagination?.total || this.state.payments.length;
                this.renderPayments();
            }
        } catch (error) {
            console.error('Failed to load payments:', error);
            Toast.error('Failed to load payments');
        } finally {
            this.state.loading = false;
        }
    },

    /**
     * Render payments
     */
    renderPayments() {
        const container = document.getElementById('billing-content');
        if (!container) return;

        if (this.state.payments.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">💳</div>
                    <h3 class="empty-state-title">No payments found</h3>
                    <p class="empty-state-description">Payments will appear here once recorded.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = DataTable.render({
            id: 'payments-table',
            columns: [
                {
                    key: 'PaymentID',
                    label: 'Payment #',
                    render: val => `<span class="font-mono font-semibold">#${String(val).padStart(5, '0')}</span>`
                },
                {
                    key: 'InvoiceID',
                    label: 'Invoice',
                    render: val => `<span class="font-mono">#${String(val).padStart(5, '0')}</span>`
                },
                {
                    key: 'Payer_FirstName',
                    label: 'Customer',
                    render: (val, row) => `${val} ${row.Payer_LastName}`
                },
                {
                    key: 'Amount_Paid',
                    label: 'Amount',
                    render: val => `<span class="font-semibold text-success">${Utils.formatCurrency(val)}</span>`
                },
                {
                    key: 'Payment_Method',
                    label: 'Method',
                    render: val => {
                        const colors = {
                            'Cash': 'badge-success',
                            'GCash': 'badge-info',
                            'Bank Transfer': 'badge-primary'
                        };
                        return `<span class="badge ${colors[val] || 'badge-gray'}">${val}</span>`;
                    }
                },
                {
                    key: 'Reference_Number',
                    label: 'Reference',
                    render: val => val || '-'
                },
                {
                    key: 'Payment_Date',
                    label: 'Date',
                    type: 'datetime'
                },
                {
                    key: 'Receiver_FirstName',
                    label: 'Received By',
                    render: (val, row) => `${val} ${row.Receiver_LastName}`
                }
            ],
            data: this.state.payments.map(p => ({ ...p, id: p.PaymentID })),
            pagination: this.state.pagination,
            actions: {
                view: true
            },
            onAction: (action, id, row) => this.handlePaymentAction(action, id, row),
            onPageChange: (page) => {
                this.state.pagination.page = page;
                this.loadPayments();
            },
            sortable: false
        });
    },

    /**
     * Handle invoice actions
     * @param {string} action
     * @param {number} id
     * @param {Object} row
     */
    async handleInvoiceAction(action, id, row) {
        switch (action) {
            case 'view':
                this.showInvoiceDetail(id);
                break;
            case 'payment':
                if (row.Status === 'Paid') {
                    Toast.info('This invoice is already paid');
                    return;
                }
                this.showRecordPaymentModal(row);
                break;
        }
    },

    /**
     * Handle payment actions
     * @param {string} action
     * @param {number} id
     * @param {Object} row
     */
    async handlePaymentAction(action, id, row) {
        switch (action) {
            case 'view':
                this.showPaymentDetail(row);
                break;
        }
    },

    /**
     * Show invoice detail
     * @param {number} id
     */
    async showInvoiceDetail(id) {
        try {
            const response = await API.billing.invoice(id);

            if (response.success) {
                const invoice = response.data;
                const balance = invoice.Total_Amount - (invoice.Amount_Paid || 0);

                Modal.open({
                    title: `Invoice #${String(invoice.InvoiceID).padStart(5, '0')}`,
                    size: 'lg',
                    content: `
                        <div class="space-y-6">
                            <!-- Header -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-lg">${invoice.Payer_FirstName} ${invoice.Payer_LastName}</h3>
                                    <p class="text-secondary">${invoice.Payer_Email}</p>
                                    ${invoice.Payer_Contact ? `<p class="text-secondary">${invoice.Payer_Contact}</p>` : ''}
                                </div>
                                <span class="badge ${Utils.getStatusBadgeClass(invoice.Status)}" style="font-size: var(--text-sm); padding: 8px 16px;">
                                    ${invoice.Status}
                                </span>
                            </div>
                            
                            <!-- Details -->
                            <div class="grid grid-cols-2 gap-4 p-4 bg-secondary rounded-lg">
                                <div>
                                    <p class="text-tertiary text-sm">Transaction Type</p>
                                    <p class="font-medium">${invoice.Transaction_Type}</p>
                                </div>
                                <div>
                                    <p class="text-tertiary text-sm">Date Created</p>
                                    <p class="font-medium">${Utils.formatDate(invoice.Created_At)}</p>
                                </div>
                                ${invoice.Animal_Name ? `
                                    <div class="col-span-2">
                                        <p class="text-tertiary text-sm">Related Animal</p>
                                        <p class="font-medium">${invoice.Animal_Name} (${invoice.Animal_Type})</p>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <!-- Amount -->
                            <div class="border rounded-lg overflow-hidden">
                                <div class="flex justify-between items-center p-4 bg-secondary">
                                    <span>Total Amount</span>
                                    <span class="font-semibold text-lg">${Utils.formatCurrency(invoice.Total_Amount)}</span>
                                </div>
                                <div class="flex justify-between items-center p-4 border-t">
                                    <span>Amount Paid</span>
                                    <span class="text-success">${Utils.formatCurrency(invoice.Amount_Paid || 0)}</span>
                                </div>
                                <div class="flex justify-between items-center p-4 border-t bg-secondary">
                                    <span class="font-semibold">Balance</span>
                                    <span class="font-semibold text-lg ${balance > 0 ? 'text-danger' : 'text-success'}">
                                        ${Utils.formatCurrency(balance)}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Payments -->
                            ${invoice.payments && invoice.payments.length > 0 ? `
                                <div>
                                    <h4 class="font-semibold mb-3">Payment History</h4>
                                    <div class="space-y-2">
                                        ${invoice.payments.map(p => `
                                            <div class="flex justify-between items-center p-3 bg-secondary rounded-lg">
                                                <div>
                                                    <p class="font-medium">${Utils.formatCurrency(p.Amount_Paid)}</p>
                                                    <p class="text-tertiary text-xs">${p.Payment_Method} • ${Utils.formatDateTime(p.Payment_Date)}</p>
                                                </div>
                                                <span class="text-tertiary text-sm">by ${p.FirstName} ${p.LastName}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `,
                    footer: `
                        <button class="btn btn-secondary" data-action="cancel">Close</button>
                        ${invoice.Status === 'Unpaid' ? `
                            <button class="btn btn-primary" onclick="Modal.closeAll(); BillingPage.showRecordPaymentModal(${JSON.stringify(invoice).replace(/"/g, '&quot;')})">
                                Record Payment
                            </button>
                        ` : ''}
                    `
                });
            }
        } catch (error) {
            Toast.error('Failed to load invoice details');
        }
    },

    /**
     * Show payment detail
     * @param {Object} payment
     */
    showPaymentDetail(payment) {
        Modal.open({
            title: `Payment #${String(payment.PaymentID).padStart(5, '0')}`,
            content: `
                <div class="space-y-4">
                    <div class="text-center p-6 bg-success-bg rounded-lg">
                        <p class="text-success text-sm mb-1">Amount Paid</p>
                        <p class="text-3xl font-bold text-success">${Utils.formatCurrency(payment.Amount_Paid)}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-tertiary text-sm">Invoice</p>
                            <p class="font-medium">#${String(payment.InvoiceID).padStart(5, '0')}</p>
                        </div>
                        <div>
                            <p class="text-tertiary text-sm">Customer</p>
                            <p class="font-medium">${payment.Payer_FirstName} ${payment.Payer_LastName}</p>
                        </div>
                        <div>
                            <p class="text-tertiary text-sm">Payment Method</p>
                            <p class="font-medium">${payment.Payment_Method}</p>
                        </div>
                        <div>
                            <p class="text-tertiary text-sm">Date</p>
                            <p class="font-medium">${Utils.formatDateTime(payment.Payment_Date)}</p>
                        </div>
                        ${payment.Reference_Number ? `
                            <div class="col-span-2">
                                <p class="text-tertiary text-sm">Reference Number</p>
                                <p class="font-medium font-mono">${payment.Reference_Number}</p>
                            </div>
                        ` : ''}
                        <div class="col-span-2">
                            <p class="text-tertiary text-sm">Received By</p>
                            <p class="font-medium">${payment.Receiver_FirstName} ${payment.Receiver_LastName}</p>
                        </div>
                    </div>
                </div>
            `,
            footer: '<button class="btn btn-secondary" data-action="cancel">Close</button>'
        });
    },

    /**
     * Show create invoice modal
     */
    async showCreateInvoiceModal() {
        // Load users for dropdown
        let users = [];
        try {
            const response = await API.users.list({ per_page: 1000 });
            if (response.success) {
                users = response.data.data || response.data;
            }
        } catch (error) {
            console.error('Failed to load users:', error);
        }

        Modal.open({
            title: 'Create Invoice',
            content: `
                <form id="create-invoice-form">
                    ${Form.generate([
                {
                    type: 'select',
                    name: 'payer_user_id',
                    label: 'Customer',
                    required: true,
                    options: [
                        { value: '', label: 'Select Customer' },
                        ...users.map(u => ({
                            value: u.id,
                            label: `${u.first_name} ${u.last_name}`
                        }))
                    ]
                },
                {
                    type: 'select',
                    name: 'transaction_type',
                    label: 'Transaction Type',
                    required: true,
                    options: [
                        { value: '', label: 'Select Type' },
                        ...this.transactionTypes.map(t => ({ value: t, label: t }))
                    ]
                },
                {
                    type: 'number',
                    name: 'total_amount',
                    label: 'Amount',
                    required: true,
                    min: 0,
                    step: '0.01',
                    placeholder: '0.00'
                }
            ])}
                </form>
            `,
            confirmText: 'Create Invoice',
            onConfirm: async () => {
                const form = document.getElementById('create-invoice-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);
                data.payer_user_id = parseInt(data.payer_user_id);
                data.issued_by_user_id = Auth.currentUser()?.id;

                try {
                    const response = await API.billing.createInvoice(data);
                    if (response.success) {
                        Toast.success('Invoice created successfully');
                        this.loadInvoices();
                        this.loadStats();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to create invoice');
                    return false;
                }
            }
        });
    },

    /**
     * Show record payment modal
     * @param {Object} invoice
     */
    showRecordPaymentModal(invoice) {
        const balance = invoice.Total_Amount - (invoice.Amount_Paid || 0);

        Modal.open({
            title: 'Record Payment',
            content: `
                <div class="mb-6 p-4 bg-secondary rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-secondary">Invoice</span>
                        <span class="font-mono font-semibold">#${String(invoice.InvoiceID).padStart(5, '0')}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-secondary">Customer</span>
                        <span>${invoice.Payer_FirstName} ${invoice.Payer_LastName}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-secondary">Balance Due</span>
                        <span class="font-semibold text-danger">${Utils.formatCurrency(balance)}</span>
                    </div>
                </div>
                
                <form id="payment-form">
                    ${Form.generate([
                {
                    type: 'number',
                    name: 'amount_paid',
                    label: 'Amount',
                    required: true,
                    min: 0.01,
                    max: balance,
                    step: '0.01',
                    placeholder: balance.toFixed(2)
                },
                {
                    type: 'select',
                    name: 'payment_method',
                    label: 'Payment Method',
                    required: true,
                    options: [
                        { value: '', label: 'Select Method' },
                        ...this.paymentMethods.map(m => ({ value: m, label: m }))
                    ]
                },
                {
                    type: 'text',
                    name: 'reference_number',
                    label: 'Reference Number',
                    placeholder: 'Optional for GCash/Bank Transfer'
                }
            ])}
                </form>
            `,
            confirmText: 'Record Payment',
            onConfirm: async () => {
                const form = document.getElementById('payment-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);
                data.invoice_id = invoice.InvoiceID;
                data.received_by_user_id = Auth.currentUser()?.id;

                try {
                    const response = await API.billing.recordPayment(data);
                    if (response.success) {
                        Toast.success('Payment recorded successfully');
                        this.loadInvoices();
                        this.loadStats();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to record payment');
                    return false;
                }
            }
        });
    },

    /**
     * Show report modal
     */
    showReportModal() {
        Modal.open({
            title: 'Generate Report',
            content: `
                <form id="report-form">
                    ${Form.generate([
                {
                    type: 'select',
                    name: 'report_type',
                    label: 'Report Type',
                    required: true,
                    options: [
                        { value: 'summary', label: 'Summary Report' },
                        { value: 'detailed', label: 'Detailed Report' },
                        { value: 'unpaid', label: 'Unpaid Invoices' }
                    ]
                },
                {
                    type: 'date',
                    name: 'date_from',
                    label: 'From Date',
                    required: true
                },
                {
                    type: 'date',
                    name: 'date_to',
                    label: 'To Date',
                    required: true
                }
            ])}
                </form>
            `,
            confirmText: 'Generate',
            onConfirm: async () => {
                const form = document.getElementById('report-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                Toast.info('Generating report...');

                try {
                    const response = await API.billing.report(data);
                    if (response.success) {
                        // Download or display report
                        Toast.success('Report generated successfully');
                        this.generatePDF(response.data);
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to generate report');
                    return false;
                }
            }
        });
    },

    /**
     * Generate PDF Report
     * @param {Object} data
     */
    generatePDF(data) {
        if (!window.jspdf) {
            Toast.error('PDF library not loaded');
            return;
        }

        const { date_range, invoices, payments, daily_breakdown } = data;

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(22);
            doc.setTextColor(44, 62, 80); // Dark Blue
            doc.text('Catarman Dog Pound', 14, 22);

            doc.setFontSize(14);
            doc.setTextColor(127, 140, 141); // Gray
            doc.text('Financial Report', 14, 32);

            // Date Range & Meta
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Period: ${Utils.formatDate(date_range.from)} - ${Utils.formatDate(date_range.to)}`, 14, 42);
            doc.text(`Generated: ${Utils.formatDateTime(new Date())}`, 14, 48);

            const user = Auth.currentUser();
            if (user) {
                doc.text(`Requested by: ${user.first_name || ''} ${user.last_name || ''} (${user.role || 'User'})`, 14, 54);
            }

            // Summary Section
            let yPos = 65;
            doc.setDrawColor(200);
            doc.line(14, 55, 196, 55);

            doc.setFontSize(12);
            doc.setTextColor(44, 62, 80);
            doc.text('Summary', 14, yPos);
            yPos += 10;

            const summaryData = [
                ['Total Invoices Generated', invoices.invoice_count, 'Total Amount Billed', Utils.formatCurrency(invoices.total_billed || 0)],
                ['Total Amount Collected', Utils.formatCurrency(payments.total_collected || 0), 'Outstanding Balance', Utils.formatCurrency(invoices.total_unpaid_invoices || 0)]
            ];

            doc.autoTable({
                startY: yPos,
                body: summaryData,
                theme: 'plain',
                styles: { fontSize: 10, cellPadding: 2 },
                columnStyles: {
                    0: { fontStyle: 'bold', cellWidth: 50 },
                    2: { fontStyle: 'bold', cellWidth: 50 }
                }
            });

            yPos = doc.lastAutoTable.finalY + 15;

            // Daily Breakdown Table
            doc.text('Daily Breakdown', 14, yPos);
            yPos += 5;

            const tableColumn = ["Date", "Transaction Count", "Collected Amount"];
            const tableRows = [];

            if (daily_breakdown && Array.isArray(daily_breakdown)) {
                daily_breakdown.forEach(row => {
                    const rowData = [
                        Utils.formatDate(row.date),
                        row.payment_count,
                        Utils.formatCurrency(row.total_collected)
                    ];
                    tableRows.push(rowData);
                });
            }

            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: yPos,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [44, 62, 80], textColor: 255 },
                alternateRowStyles: { fillColor: [245, 247, 250] }
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
            const filename = `financial-report-${date_range.from}-to-${date_range.to}.pdf`;
            doc.save(filename);

            Toast.success('Report downloaded');
        } catch (error) {
            console.error('PDF generation failed:', error);
            Toast.error('Failed to generate PDF');
        }
    }
};

// Make BillingPage globally available
window.BillingPage = BillingPage;