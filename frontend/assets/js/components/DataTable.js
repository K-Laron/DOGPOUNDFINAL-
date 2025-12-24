/**
 * DataTable Component
 * Reusable data table with sorting, filtering, and pagination
 * 
 * @package AnimalShelter
 */

const DataTable = {
    /**
     * Active tables
     */
    tables: {},

    /**
     * Icons
     */
    icons: {
        sortAsc: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>',
        sortDesc: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>',
        sort: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5h10M11 9h7M11 13h4M3 17l3 3 3-3M6 18V4"></path></svg>',
        chevronLeft: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>',
        chevronRight: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>',
        chevronsLeft: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="11 17 6 12 11 7"></polyline><polyline points="18 17 13 12 18 7"></polyline></svg>',
        chevronsRight: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 17 18 12 13 7"></polyline><polyline points="6 17 11 12 6 7"></polyline></svg>'
    },

    /**
     * Render table
     * @param {Object} options
     * @returns {string}
     */
    render(options) {
        const {
            id,
            columns = [],
            data = [],
            pagination = null,
            sortable = true,
            selectable = false,
            actions = null,
            emptyMessage = 'No data available',
            loading = false,
            onRowClick = null
        } = options;

        // Store table config
        this.tables[id] = { ...options };
        console.log(`DataTable: Registered table ${id}`, Object.keys(options));

        if (loading) {
            return `
                <div id="${id}-container">
                    ${Loading.skeleton('table', { rows: 5, cols: columns.length })}
                </div>
            `;
        }

        if (data.length === 0) {
            return `
                <div id="${id}-container">
                    <div class="card">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <p class="empty-state-description">${emptyMessage}</p>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div id="${id}-container">
                <div class="table-container">
                    <table class="table" id="${id}">
                        <thead>
                            <tr>
                                ${selectable ? '<th style="width: 40px;"><input type="checkbox" id="${id}-select-all" onchange="DataTable.toggleSelectAll(\'${id}\')"></th>' : ''}
                                ${columns.map(col => this.renderHeaderCell(id, col, sortable)).join('')}
                                ${actions ? '<th style="width: 100px;">Actions</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map((row, index) => this.renderRow(id, row, index, columns, selectable, actions, onRowClick)).join('')}
                        </tbody>
                    </table>
                </div>
                ${pagination ? this.renderPagination(id, pagination) : ''}
            </div>
        `;
    },

    /**
     * Render header cell
     * @param {string} tableId
     * @param {Object} column
     * @param {boolean} sortable
     * @returns {string}
     */
    renderHeaderCell(tableId, column, sortable) {
        const isSortable = sortable && column.sortable !== false;
        const sortClass = isSortable ? 'sortable' : '';
        const currentSort = this.tables[tableId]?.currentSort;
        const isActive = currentSort?.key === column.key;
        const sortDirection = isActive ? currentSort.direction : null;

        const sortIcon = isActive
            ? (sortDirection === 'asc' ? this.icons.sortAsc : this.icons.sortDesc)
            : this.icons.sort;

        return `
            <th 
                class="${sortClass} ${isActive ? sortDirection : ''}"
                style="${column.width ? `width: ${column.width};` : ''}"
                ${isSortable ? `onclick="DataTable.sort('${tableId}', '${column.key}')"` : ''}
            >
                <div class="flex items-center gap-1">
                    <span>${column.label}</span>
                    ${isSortable ? `<span class="sort-icon">${sortIcon}</span>` : ''}
                </div>
            </th>
        `;
    },

    /**
     * Render table row
     * @param {string} tableId
     * @param {Object} row
     * @param {number} index
     * @param {Array} columns
     * @param {boolean} selectable
     * @param {Object} actions
     * @param {Function} onRowClick
     * @returns {string}
     */
    renderRow(tableId, row, index, columns, selectable, actions, onRowClick) {
        const rowId = row.id || row.ID || row[Object.keys(row)[0]] || index;
        const clickable = onRowClick ? 'style="cursor: pointer;"' : '';
        const clickHandler = onRowClick ? `onclick="DataTable.handleRowClick('${tableId}', ${rowId})"` : '';

        return `
            <tr data-id="${rowId}" ${clickable} ${clickHandler}>
                ${selectable ? `
                    <td onclick="event.stopPropagation();" data-label="Select">
                        <input type="checkbox" class="row-select" data-id="${rowId}" onchange="DataTable.toggleRowSelect('${tableId}', ${rowId})">
                    </td>
                ` : ''}
                ${columns.map(col => this.renderCell(row, col)).join('')}
                ${actions ? `<td onclick="event.stopPropagation();" data-label="Actions">${this.renderActions(tableId, row, actions)}</td>` : ''}
            </tr>
        `;
    },

    /**
     * Render cell
     * @param {Object} row
     * @param {Object} column
     * @returns {string}
     */
    renderCell(row, column) {
        let value = Utils.get(row, column.key, '');

        // Custom renderer
        if (column.render) {
            value = column.render(value, row);
        } else {
            // Default formatters based on type
            switch (column.type) {
                case 'date':
                    value = value ? Utils.formatDate(value) : '-';
                    break;
                case 'datetime':
                    value = value ? Utils.formatDateTime(value) : '-';
                    break;
                case 'currency':
                    value = Utils.formatCurrency(value);
                    break;
                case 'number':
                    value = Utils.formatNumber(value);
                    break;
                case 'badge':
                    const badgeClass = Utils.getStatusBadgeClass(value);
                    value = `<span class="badge ${badgeClass}">${value}</span>`;
                    break;
                case 'avatar':
                    const name = row[column.nameKey] || value;
                    value = `
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-sm" style="background: ${Utils.stringToColor(name)}">
                                ${Utils.getInitials(name)}
                            </div>
                            <span>${name}</span>
                        </div>
                    `;
                    break;
                case 'boolean':
                    value = value
                        ? '<span class="badge badge-success">Yes</span>'
                        : '<span class="badge badge-gray">No</span>';
                    break;
            }
        }

        const align = column.align ? `text-align: ${column.align};` : '';
        const className = column.className || '';

        return `<td style="${align}" class="${className}" data-label="${column.label}">${value ?? '-'}</td>`;
    },

    /**
     * Render actions
     * @param {string} tableId
     * @param {Object} row
     * @param {Object} actions
     * @returns {string}
     */
    renderActions(tableId, row, actions) {
        const rowId = row.id || row.ID || row[Object.keys(row)[0]];

        if (typeof actions === 'function') {
            return actions(row);
        }

        const buttons = [];

        if (actions.view) {
            buttons.push(`
                <button class="btn-icon btn-ghost btn-sm" onclick="DataTable.action('${tableId}', 'view', '${rowId}')" title="View">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            `);
        }

        if (actions.edit) {
            buttons.push(`
                <button class="btn-icon btn-ghost btn-sm" onclick="DataTable.action('${tableId}', 'edit', '${rowId}')" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
            `);
        }

        if (actions.delete) {
            buttons.push(`
                <button class="btn-icon btn-ghost btn-sm text-danger" onclick="DataTable.action('${tableId}', 'delete', '${rowId}')" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            `);
        }

        // Custom actions
        if (actions.custom) {
            actions.custom.forEach(action => {
                buttons.push(`
                    <button class="btn-icon btn-ghost btn-sm ${action.className || ''}" onclick="DataTable.action('${tableId}', '${action.name}', '${rowId}')" title="${action.label}">
                        ${action.icon}
                    </button>
                `);
            });
        }

        return `<div class="flex items-center gap-1">${buttons.join('')}</div>`;
    },

    /**
     * Render pagination
     * @param {string} tableId
     * @param {Object} pagination
     * @returns {string}
     */
    renderPagination(tableId, pagination) {
        const { page, perPage, total } = pagination;
        const totalPages = Math.ceil(total / perPage);
        const start = ((page - 1) * perPage) + 1;
        const end = Math.min(page * perPage, total);

        if (totalPages <= 1) {
            return `
                <div class="flex items-center justify-between mt-4 text-secondary" style="font-size: var(--text-sm); padding: 0 var(--space-5);">
                    <span>Showing ${total} item${total !== 1 ? 's' : ''}</span>
                </div>
            `;
        }

        // Generate page numbers
        const pages = this.getPageNumbers(page, totalPages);

        return `
            <div class="flex items-center justify-between mt-4" style="padding: 0 var(--space-5);">
                <div class="text-secondary" style="font-size: var(--text-sm);">
                    Showing ${start} to ${end} of ${total} results
                </div>
                <div class="pagination">
                    <button class="pagination-btn" onclick="DataTable.goToPage('${tableId}', 1)" ${page === 1 ? 'disabled' : ''}>
                        ${this.icons.chevronsLeft}
                    </button>
                    <button class="pagination-btn" onclick="DataTable.goToPage('${tableId}', ${page - 1})" ${page === 1 ? 'disabled' : ''}>
                        ${this.icons.chevronLeft}
                    </button>
                    ${pages.map(p => {
            if (p === '...') {
                return '<span class="pagination-btn" style="cursor: default;">...</span>';
            }
            return `
                            <button class="pagination-btn ${p === page ? 'active' : ''}" onclick="DataTable.goToPage('${tableId}', ${p})">
                                ${p}
                            </button>
                        `;
        }).join('')}
                    <button class="pagination-btn" onclick="DataTable.goToPage('${tableId}', ${page + 1})" ${page === totalPages ? 'disabled' : ''}>
                        ${this.icons.chevronRight}
                    </button>
                    <button class="pagination-btn" onclick="DataTable.goToPage('${tableId}', ${totalPages})" ${page === totalPages ? 'disabled' : ''}>
                        ${this.icons.chevronsRight}
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Get page numbers array
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
     * Handle sort
     * @param {string} tableId
     * @param {string} key
     */
    sort(tableId, key) {
        console.log(`DataTable: Sort ${key} for table ${tableId}`);
        const table = this.tables[tableId];
        if (!table) return;
        if (!table.onSort) {
            console.warn(`DataTable: No onSort handler for table ${tableId}`);
            return;
        }

        let direction = 'asc';
        if (table.currentSort?.key === key && table.currentSort.direction === 'asc') {
            direction = 'desc';
        }

        table.currentSort = { key, direction };
        table.onSort(key, direction);
    },

    /**
     * Go to page
     * @param {string} tableId
     * @param {number} page
     */
    goToPage(tableId, page) {
        const table = this.tables[tableId];
        if (!table || !table.onPageChange) return;

        table.onPageChange(page);
    },

    /**
     * Handle action
     * @param {string} tableId
     * @param {string} action
     * @param {*} rowId
     */
    action(tableId, action, rowId) {
        console.log(`DataTable: Action ${action} on row ${rowId} for table ${tableId}`);
        const table = this.tables[tableId];
        if (!table) {
            console.error(`DataTable: Table ${tableId} not found in registry`, this.tables);
            return;
        }

        const row = table.data?.find(r => {
            const id = r.id || r.ID || r[Object.keys(r)[0]];
            return id == rowId;
        });

        if (table.onAction) {
            table.onAction(action, rowId, row);
        }
    },

    /**
     * Handle row click
     * @param {string} tableId
     * @param {*} rowId
     */
    handleRowClick(tableId, rowId) {
        const table = this.tables[tableId];
        if (!table || !table.onRowClick) return;

        const row = table.data?.find(r => {
            const id = r.id || r.ID || r[Object.keys(r)[0]];
            return id == rowId;
        });

        table.onRowClick(rowId, row);
    },

    /**
     * Toggle select all
     * @param {string} tableId
     */
    toggleSelectAll(tableId) {
        const selectAll = document.getElementById(`${tableId}-select-all`);
        const checkboxes = document.querySelectorAll(`#${tableId} .row-select`);

        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });

        this.updateSelection(tableId);
    },

    /**
     * Toggle row select
     * @param {string} tableId
     * @param {*} rowId
     */
    toggleRowSelect(tableId, rowId) {
        this.updateSelection(tableId);
    },

    /**
     * Update selection
     * @param {string} tableId
     */
    updateSelection(tableId) {
        const table = this.tables[tableId];
        if (!table) return;

        const checkboxes = document.querySelectorAll(`#${tableId} .row-select:checked`);
        const selectedIds = Array.from(checkboxes).map(cb => cb.dataset.id);

        if (table.onSelectionChange) {
            table.onSelectionChange(selectedIds);
        }
    },

    /**
     * Get selected rows
     * @param {string} tableId
     * @returns {Array}
     */
    getSelected(tableId) {
        const checkboxes = document.querySelectorAll(`#${tableId} .row-select:checked`);
        return Array.from(checkboxes).map(cb => cb.dataset.id);
    },

    /**
     * Clear selection
     * @param {string} tableId
     */
    clearSelection(tableId) {
        const selectAll = document.getElementById(`${tableId}-select-all`);
        const checkboxes = document.querySelectorAll(`#${tableId} .row-select`);

        if (selectAll) selectAll.checked = false;
        checkboxes.forEach(cb => cb.checked = false);
    },

    /**
     * Refresh table
     * @param {string} tableId
     * @param {Object} newOptions
     */
    refresh(tableId, newOptions = {}) {
        const table = this.tables[tableId];
        if (!table) return;

        Object.assign(table, newOptions);

        const container = document.getElementById(`${tableId}-container`);
        if (container) {
            container.outerHTML = this.render(table);
        }
    }
};

// Make DataTable globally available
window.DataTable = DataTable;