/**
 * Inventory Page
 * Inventory and supplies management
 * 
 * @package AnimalShelter
 */

const InventoryPage = {
    /**
     * Page state
     */
    state: {
        items: [],
        pagination: { page: 1, perPage: 20, total: 0 },
        filters: {
            category: '',
            search: ''
        },
        loading: false,
        activeTab: 'all', // 'all', 'low-stock', 'expiring'
        alerts: null
    },

    /**
     * Categories
     */
    categories: ['Medical', 'Food', 'Cleaning', 'Supplies'],

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Inventory</h1>
                    <p class="page-subtitle">Manage shelter supplies and stock levels</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" onclick="InventoryPage.exportInventory()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="InventoryPage.showAddModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Item
                    </button>
                </div>
            </div>
            
            <!-- Alerts Banner -->
            <div id="alerts-banner" class="mb-6"></div>
            
            <!-- Stats -->
            <div class="stats-grid mb-6" id="inventory-stats">
                ${Loading.skeleton('stats', { count: 4 })}
            </div>
            
            <!-- Tabs -->
            <div class="tabs-pills mb-6" id="inventory-tabs">
                <button class="tab active" data-tab="all">All Items</button>

                <button class="tab" data-tab="low-stock">
                    Stock Alerts
                    <span class="badge badge-danger ml-2" id="low-stock-count" style="display: none;"></span>
                </button>
                <button class="tab" data-tab="expiring">
                    Expiring Soon
                    <span class="badge badge-warning ml-2" id="expiring-count" style="display: none;"></span>
                </button>
            </div>
            
            <!-- Filters -->
            <div class="card mb-6" id="inventory-filters-card">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4" id="filters-container">
                        <div class="flex-1" style="min-width: 200px;">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="form-input search-input" placeholder="Search items..." id="search-input">
                            </div>
                        </div>
                        
                        <select class="form-select" id="filter-category" style="width: auto;">
                            <option value="">All Categories</option>
                            ${this.categories.map(c => `<option value="${c}">${c}</option>`).join('')}
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Table -->
            <div id="inventory-container">
                ${Loading.skeleton('table', { rows: 5, cols: 6 })}
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        await Promise.all([
            this.loadStats(),
            this.loadAlerts(),
            this.loadItems()
        ]);

        this.setupEventListeners();
    },

    /**
     * Load statistics
     */
    async loadStats() {
        try {
            const response = await API.inventory.statistics();

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
        const container = document.getElementById('inventory-stats');
        if (!container) return;

        container.innerHTML = `
            ${Card.stat({
            title: 'Total Items',
            value: stats.total_items || 0,
            iconColor: 'primary',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>'
        })}
            ${Card.stat({
            title: 'Low Stock Items',
            value: stats.low_stock_count || 0,
            iconColor: 'danger',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
        })}
            ${Card.stat({
            title: 'Expiring Soon',
            value: stats.expiring_soon_count || 0,
            iconColor: 'warning',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
        })}
            ${Card.stat({
            title: 'Out of Stock',
            value: stats.out_of_stock_count || 0,
            iconColor: 'danger',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
        })}
        `;
    },

    /**
     * Load alerts
     */
    async loadAlerts() {
        try {
            const response = await API.inventory.alerts();

            if (response.success) {
                this.state.alerts = response.data;
                this.renderAlertsBanner();
                this.updateBadgeCounts();
            }
        } catch (error) {
            console.error('Failed to load alerts:', error);
        }
    },

    /**
     * Render alerts banner
     */
    renderAlertsBanner() {
        const container = document.getElementById('alerts-banner');
        if (!container || !this.state.alerts) return;

        const { out_of_stock, low_stock, expiring_soon, expired } = this.state.alerts;
        const hasAlerts = (out_of_stock?.length > 0) || (low_stock?.length > 0) || (expiring_soon?.length > 0) || (expired?.length > 0);

        if (!hasAlerts) {
            container.innerHTML = '';
            return;
        }

        const alerts = [];



        if (expired?.length > 0) {
            alerts.push(`
                <div class="flex items-center gap-3 p-4 bg-danger-bg rounded-lg mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <div class="flex-1">
                        <p class="font-semibold text-danger">${expired.length} item(s) have expired</p>
                        <p class="text-sm text-secondary">${expired.slice(0, 3).map(i => i.Item_Name).join(', ')}${expired.length > 3 ? ` and ${expired.length - 3} more` : ''}</p>
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="InventoryPage.showExpiredItems()">View</button>
                </div>
            `);
        }

        if (low_stock?.length > 0) {
            const outOfStockCount = low_stock.filter(i => i.Quantity_On_Hand <= 0).length;
            const hasOutOfStock = outOfStockCount > 0;

            const title = hasOutOfStock
                ? `${low_stock.length} item(s) need attention (${outOfStockCount} out of stock)`
                : `${low_stock.length} item(s) are running low`;

            const bgColor = hasOutOfStock ? 'bg-danger-bg' : 'rgba(255, 59, 48, 0.08)';
            const iconColor = hasOutOfStock ? 'var(--color-danger)' : 'var(--color-warning)';

            alerts.push(`
                <div class="flex items-center gap-3 p-4 rounded-lg mb-3" style="background: ${bgColor};">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <div class="flex-1">
                        <p class="font-semibold" style="color: ${iconColor}">${title}</p>
                        <p class="text-sm text-secondary">${low_stock.slice(0, 3).map(i => i.Item_Name).join(', ')}${low_stock.length > 3 ? ` and ${low_stock.length - 3} more` : ''}</p>
                    </div>
                    <button class="btn btn-sm btn-outline" onclick="InventoryPage.switchTab('low-stock')">View All</button>
                </div>
            `);
        }

        container.innerHTML = alerts.join('');
    },

    /**
     * Update badge counts
     */
    updateBadgeCounts() {
        const lowStockBadge = document.getElementById('low-stock-count');
        const expiringBadge = document.getElementById('expiring-count');

        if (lowStockBadge && this.state.alerts?.low_stock?.length > 0) {
            lowStockBadge.textContent = this.state.alerts.low_stock.length;
            lowStockBadge.style.display = 'inline-flex';
        }

        if (expiringBadge && this.state.alerts?.expiring_soon?.length > 0) {
            expiringBadge.textContent = this.state.alerts.expiring_soon.length;
            expiringBadge.style.display = 'inline-flex';
        }
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
                this.loadItems();
            }, 300));
        }

        // Category filter
        const categoryFilter = document.getElementById('filter-category');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.state.filters.category = e.target.value;
                this.state.pagination.page = 1;
                this.loadItems();
            });
        }

        // Tabs
        const tabs = document.getElementById('inventory-tabs');
        if (tabs) {
            tabs.addEventListener('click', (e) => {
                const tab = e.target.closest('.tab');
                if (tab) {
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
        const tabs = document.getElementById('inventory-tabs');
        if (tabs) {
            tabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tabs.querySelector(`[data-tab="${tab}"]`)?.classList.add('active');
        }

        const filtersCard = document.getElementById('inventory-filters-card');

        this.state.activeTab = tab;

        switch (tab) {
            case 'all':
                if (filtersCard) filtersCard.style.display = 'block';
                this.loadItems();
                break;
            case 'low-stock':
                if (filtersCard) filtersCard.style.display = 'none';
                this.renderLowStockItems();
                break;

            case 'expiring':
                if (filtersCard) filtersCard.style.display = 'none';
                this.renderExpiringItems();
                break;
        }
    },

    /**
     * Load inventory items
     */
    async loadItems() {
        if (this.state.activeTab !== 'all') return;

        this.state.loading = true;
        const container = document.getElementById('inventory-container');
        if (container) container.innerHTML = Loading.skeleton('table', { rows: 5, cols: 7 });

        try {
            const params = {
                page: this.state.pagination.page,
                per_page: this.state.pagination.perPage,
                ...this.state.filters
            };

            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            const response = await API.inventory.list(params);

            if (response.success) {
                this.state.items = Array.isArray(response.data) ? response.data : (response.data.data || []);

                // Handle pagination
                if (response.pagination) {
                    this.state.pagination.total = response.pagination.total_items;
                } else if (response.data && response.data.pagination) {
                    this.state.pagination.total = response.data.pagination.total;
                } else {
                    this.state.pagination.total = this.state.items.length;
                }

                this.state.loading = false;
                this.renderItems();
            }
        } catch (error) {
            console.error('Failed to load items:', error);
            Toast.error('Failed to load inventory');
            this.state.loading = false;
            this.renderItems();
        }
    },

    /**
     * Render inventory items
     */
    renderItems() {
        const container = document.getElementById('inventory-container');
        if (!container) return;

        if (this.state.items.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">📦</div>
                    <h3 class="empty-state-title">No items found</h3>
                    <p class="empty-state-description">Add items to start tracking your inventory.</p>
                    <button class="btn btn-primary mt-4" onclick="InventoryPage.showAddModal()">
                        Add Item
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = DataTable.render({
            id: 'inventory-table',
            columns: [
                {
                    key: 'Item_Name',
                    label: 'Item Name',
                    render: (val, row) => `
                        <div>
                            <p class="font-semibold">${val}</p>
                            <span class="badge badge-gray text-xs">${row.Category}</span>
                        </div>
                    `
                },
                {
                    key: 'Quantity_On_Hand',
                    label: 'Quantity',
                    render: (val, row) => {
                        const isLow = val <= row.Reorder_Level;
                        return `<span class="${isLow ? 'text-danger font-semibold' : ''}">${val}</span>`;
                    }
                },
                {
                    key: 'Reorder_Level',
                    label: 'Reorder Level'
                },
                {
                    key: 'Stock_Status',
                    label: 'Status',
                    render: (val, row) => {
                        const qty = row.Quantity_On_Hand;
                        const reorder = row.Reorder_Level;

                        if (qty === 0) {
                            return '<span class="badge badge-danger">Out of Stock</span>';
                        } else if (qty <= reorder) {
                            return '<span class="badge badge-warning">Low Stock</span>';
                        }
                        return '<span class="badge badge-success">In Stock</span>';
                    }
                },
                {
                    key: 'Expiration_Date',
                    label: 'Expires',
                    render: val => {
                        if (!val) return '-';
                        const date = new Date(val);
                        const today = new Date();
                        const daysUntil = Math.ceil((date - today) / (1000 * 60 * 60 * 24));

                        if (daysUntil < 0) {
                            return `<span class="badge badge-danger">Expired</span>`;
                        } else if (daysUntil <= 30) {
                            return `<span class="badge badge-warning">${daysUntil} days</span>`;
                        }
                        return Utils.formatDate(val, 'short');
                    }
                },
                {
                    key: 'Supplier_Name',
                    label: 'Supplier',
                    render: val => val || '-'
                },
                {
                    key: 'Last_Updated',
                    label: 'Last Updated',
                    render: val => val ? Utils.timeAgo(val) : '-'
                }
            ],
            data: this.state.items.map(i => ({ ...i, id: i.ItemID })),
            pagination: this.state.pagination,
            actions: {
                custom: [
                    {
                        name: 'adjust',
                        label: 'Adjust Stock',
                        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>'
                    }
                ],
                edit: true,
                delete: Auth.isAdmin()
            },
            onAction: (action, id, row) => this.handleAction(action, id, row),
            onPageChange: (page) => {
                this.state.pagination.page = page;
                this.loadItems();
            },
            sortable: false
        });
    },

    /**
     * Render low stock items
     */
    renderLowStockItems() {
        const container = document.getElementById('inventory-container');
        if (!container) return;

        const items = this.state.alerts?.low_stock || [];

        if (items.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">✅</div>
                    <h3 class="empty-state-title">All items are stocked</h3>
                    <p class="empty-state-description">No items are running low.</p>
                </div>
            `;
            return;
        }

        const outOfStock = items.filter(i => i.Quantity_On_Hand <= 0);
        const lowStock = items.filter(i => i.Quantity_On_Hand > 0);

        let html = '<div class="p-4">';

        if (outOfStock.length > 0) {
            html += `
                <div class="mb-6">
                    <h3 class="text-danger font-bold mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        Out of Stock (${outOfStock.length})
                    </h3>
                    ${outOfStock.map(item => `
                        <div class="flex items-center gap-4 p-4 mb-3 bg-danger-bg rounded-lg border border-danger-subtle">
                            <div class="avatar bg-danger text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-semibold text-danger">${item.Item_Name}</h4>
                                    <span class="badge badge-gray">${item.Category}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <p class="text-secondary text-sm">
                                        Current: <span class="text-danger font-bold">0</span> / Reorder: ${item.Reorder_Level}
                                    </p>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="InventoryPage.showAdjustModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                                Restock
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        if (lowStock.length > 0) {
            html += `
                <div>
                     ${outOfStock.length > 0 ? `
                    <h3 class="text-warning font-bold mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Low Stock (${lowStock.length})
                    </h3>` : ''}
                    ${lowStock.map(item => `
                        <div class="flex items-center gap-4 p-4 mb-3 bg-warning-bg rounded-lg" style="background: rgba(255, 59, 48, 0.05);">
                            <div class="avatar" style="background: var(--color-warning);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-semibold">${item.Item_Name}</h4>
                                    <span class="badge badge-gray">${item.Category}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <p class="text-secondary text-sm">
                                        Current: <span class="text-danger font-semibold">${item.Quantity_On_Hand}</span> / Reorder: ${item.Reorder_Level}
                                    </p>
                                    <p class="text-secondary text-sm">Shortage: ${item.Shortage || (item.Reorder_Level - item.Quantity_On_Hand)}</p>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="InventoryPage.showAdjustModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                                Restock
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        html += '</div>';
        container.innerHTML = html;
    },



    /**
     * Render expiring items
     */
    renderExpiringItems() {
        const container = document.getElementById('inventory-container');
        if (!container) return;

        const items = this.state.alerts?.expiring_soon || [];

        if (items.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-12">
                    <div class="text-5xl mb-4">✅</div>
                    <h3 class="empty-state-title">No items expiring soon</h3>
                    <p class="empty-state-description">All items have sufficient shelf life.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="p-4">
                ${items.map(item => `
                    <div class="flex items-center gap-4 p-4 mb-3 bg-warning-bg rounded-lg">
                        <div class="avatar" style="background: var(--color-warning);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-semibold">${item.Item_Name}</h4>
                                <span class="badge badge-warning">${item.Days_Until_Expiry} days left</span>
                            </div>
                            <p class="text-secondary text-sm">
                                Expires: ${Utils.formatDate(item.Expiration_Date)} • Quantity: ${item.Quantity_On_Hand}
                            </p>
                        </div>
                        <button class="btn btn-sm btn-ghost" onclick="InventoryPage.showEditModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                            Edit
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
            case 'adjust':
                this.showAdjustModal(row);
                break;
            case 'edit':
                this.showEditModal(row);
                break;
            case 'delete':
                await this.deleteItem(id, row.Item_Name);
                break;
        }
    },

    /**
     * Show add item modal
     */
    showAddModal() {
        const fields = this.getFormFields();

        Modal.open({
            title: 'Add Inventory Item',
            content: `<form id="add-item-form">${Form.generate(fields)}</form>`,
            size: 'lg',
            confirmText: 'Add Item',
            onConfirm: async () => {
                const form = document.getElementById('add-item-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                try {
                    const response = await API.inventory.create(data);
                    if (response.success) {
                        Toast.success('Item added successfully');
                        this.loadItems();
                        this.loadStats();
                        this.loadAlerts();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to add item');
                    return false;
                }
            }
        });
    },

    /**
     * Show edit item modal
     * @param {Object} item
     */
    showEditModal(item) {
        const fields = this.getFormFields();

        Modal.open({
            title: `Edit ${item.Item_Name}`,
            content: `<form id="edit-item-form">${Form.generate(fields, {
                item_name: item.Item_Name,
                category: item.Category,
                quantity_on_hand: item.Quantity_On_Hand,
                reorder_level: item.Reorder_Level,
                expiration_date: item.Expiration_Date ? Utils.toInputDate(item.Expiration_Date) : '',
                supplier_name: item.Supplier_Name
            })}</form>`,
            size: 'lg',
            confirmText: 'Save Changes',
            onConfirm: async () => {
                const form = document.getElementById('edit-item-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                try {
                    const response = await API.inventory.update(item.ItemID, data);
                    if (response.success) {
                        Toast.success('Item updated successfully');
                        this.loadItems();
                        this.loadStats();
                        this.loadAlerts();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to update item');
                    return false;
                }
            }
        });
    },

    /**
     * Show adjust stock modal
     * @param {Object} item
     */
    showAdjustModal(item) {
        Modal.open({
            title: `Adjust Stock - ${item.Item_Name}`,
            content: `
                <div class="mb-6 p-4 bg-secondary rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-secondary">Current Stock</span>
                        <span class="text-xl font-bold">${item.Quantity_On_Hand}</span>
                    </div>
                </div>
                <form id="adjust-form">
                    ${Form.generate([
                {
                    type: 'select',
                    name: 'operation',
                    label: 'Operation',
                    required: true,
                    options: [
                        { value: 'add', label: 'Add Stock' },
                        { value: 'subtract', label: 'Remove Stock' },
                        { value: 'set', label: 'Set Quantity' }
                    ]
                },
                {
                    type: 'number',
                    name: 'amount',
                    label: 'Amount',
                    required: true,
                    min: 1,
                    placeholder: 'Enter amount'
                }
            ])}
                </form>
            `,
            size: 'sm',
            confirmText: 'Apply',
            onConfirm: async () => {
                const form = document.getElementById('adjust-form');
                if (!Form.validate(form)) return false;

                const data = Form.getData(form);

                try {
                    const response = await API.inventory.adjustStock(item.ItemID, {
                        operation: data.operation,
                        amount: parseInt(data.amount)
                    });

                    if (response.success) {
                        Toast.success('Stock adjusted successfully');
                        this.loadItems();
                        this.loadStats();
                        this.loadAlerts();
                        return true;
                    }
                } catch (error) {
                    Toast.error(error.message || 'Failed to adjust stock');
                    return false;
                }
            }
        });
    },

    /**
     * Delete item
     * @param {number} id
     * @param {string} name
     */
    async deleteItem(id, name) {
        const confirmed = await Modal.confirmDelete(name);

        if (confirmed) {
            try {
                const response = await API.inventory.delete(id);
                if (response.success) {
                    Toast.success('Item deleted successfully');
                    this.loadItems();
                    this.loadStats();
                    this.loadAlerts();
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to delete item');
            }
        }
    },

    /**
     * Show expired items
     */
    showExpiredItems() {
        const items = this.state.alerts?.expired || [];

        Modal.open({
            title: 'Expired Items',
            content: items.length === 0 ? '<p>No expired items.</p>' : `
                <div class="space-y-3">
                    ${items.map(item => `
                        <div class="flex items-center justify-between p-3 bg-danger-bg rounded-lg">
                            <div>
                                <p class="font-semibold">${item.Item_Name}</p>
                                <p class="text-sm text-secondary">Expired ${item.Days_Expired} days ago</p>
                            </div>
                            <span class="badge badge-danger">Qty: ${item.Quantity_On_Hand}</span>
                        </div>
                    `).join('')}
                </div>
            `,
            footer: '<button class="btn btn-secondary" data-action="cancel">Close</button>'
        });
    },

    /**
     * Export inventory
     */
    async exportInventory() {
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
            doc.text('Inventory Report', 14, 32);

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
            doc.text(`Total Items: ${this.state.items.length}`, 14, 46);

            const user = Auth.currentUser();
            if (user) {
                doc.text(`Requested by: ${user.first_name || ''} ${user.last_name || ''} (${user.role || 'User'})`, 14, 52);
            }

            // Table Data
            const tableColumn = ["Item Name", "Category", "Qty", "Reorder", "Expiration", "Supplier", "Status"];
            const tableRows = [];

            this.state.items.forEach(item => {
                let status = 'In Stock';
                if (item.Quantity_On_Hand <= 0) status = 'Out of Stock';
                else if (item.Quantity_On_Hand <= item.Reorder_Level) status = 'Low Stock';

                const itemData = [
                    item.Item_Name,
                    item.Category,
                    item.Quantity_On_Hand,
                    item.Reorder_Level,
                    item.Expiration_Date ? Utils.formatDate(item.Expiration_Date) : '-',
                    item.Supplier_Name || '-',
                    status
                ];
                tableRows.push(itemData);
            });

            // Generate Table
            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 60,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [66, 66, 66], textColor: 255 },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                didParseCell: function (data) {
                    // Color code status column
                    if (data.section === 'body' && data.column.index === 6) {
                        if (data.cell.raw === 'Out of Stock') {
                            data.cell.styles.textColor = [220, 53, 69]; // Red
                        } else if (data.cell.raw === 'Low Stock') {
                            data.cell.styles.textColor = [255, 193, 7]; // Yellow/Orange
                        } else {
                            data.cell.styles.textColor = [40, 167, 69]; // Green
                        }
                    }
                }
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
            const filename = `inventory-report-${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);

            Toast.success('Export downloaded');
        } catch (error) {
            console.error('Export failed:', error);
            Toast.error('Failed to generate PDF');
        }
    },

    /**
     * Get form fields
     * @returns {Array}
     */
    getFormFields() {
        return [
            {
                type: 'text',
                name: 'item_name',
                label: 'Item Name',
                required: true,
                placeholder: 'Enter item name'
            },
            {
                type: 'select',
                name: 'category',
                label: 'Category',
                required: true,
                options: [
                    { value: '', label: 'Select Category' },
                    ...this.categories.map(c => ({ value: c, label: c }))
                ]
            },
            {
                type: 'number',
                name: 'quantity_on_hand',
                label: 'Initial Quantity',
                required: true,
                min: 0,
                placeholder: '0'
            },
            {
                type: 'number',
                name: 'reorder_level',
                label: 'Reorder Level',
                required: true,
                min: 0,
                placeholder: '10',
                hint: 'Alert will show when stock falls below this level'
            },
            {
                type: 'date',
                name: 'expiration_date',
                label: 'Expiration Date',
                hint: 'Leave blank if item does not expire'
            },
            {
                type: 'text',
                name: 'supplier_name',
                label: 'Supplier Name',
                placeholder: 'Enter supplier name'
            }
        ];
    }
};

// Make InventoryPage globally available
window.InventoryPage = InventoryPage;