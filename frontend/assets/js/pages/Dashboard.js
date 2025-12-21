/**
 * Dashboard Page
 * Main dashboard with statistics and activity feed
 * 
 * @package AnimalShelter
 */

const DashboardPage = {
    /**
     * Dashboard data
     */
    data: {
        stats: null,
        recentAnimals: [],
        recentActivity: [],
        pendingAdoptions: [],
        upcomingTreatments: [],
        lowStockItems: [],
        showAllActivity: false
    },

    /**
     * Icons
     */
    icons: {
        animals: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5"></path><path d="M14.267 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.855-1.45-2.239-2.5"></path><path d="M8 14v.5"></path><path d="M16 14v.5"></path><path d="M11.25 16.25h1.5L12 17l-.75-.75Z"></path><path d="M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444c0-1.061-.162-2.2-.493-3.309m-9.243-6.082A8.801 8.801 0 0 1 12 5c.78 0 1.5.108 2.161.306"></path></svg>',
        adoptions: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
        medical: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
        revenue: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
    },

    /**
     * Render the page
     * @returns {string}
     */
    async render() {
        if (Auth.isAdopter()) {
            // Update header title
            Store.setCurrentPage('dashboard', 'Adoption Hub');
            return this.renderAdopterView();
        }

        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" onclick="DashboardPage.refresh()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        Refresh
                    </button>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div id="stats-container" class="stats-grid">
                ${Loading.skeleton('stats', { count: 4 })}
            </div>
            
            <!-- Main Content Grid -->
            <div class="content-grid mt-8">
                <!-- Left Column -->
                <div class="flex flex-col gap-6">
                    <!-- Charts -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Intake Overview</h3>
                            <div class="tabs-pills" id="chart-tabs">
                                <button class="tab active" data-period="week">Week</button>
                                <button class="tab" data-period="month">Month</button>
                                <button class="tab" data-period="year">Year</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="intake-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Animals -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Animals</h3>
                            <a href="/animals" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body p-0" id="recent-animals">
                            ${Loading.skeleton('list', { items: 5 })}
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="flex flex-col gap-6">
                    <!-- Status Distribution -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Animal Status</h3>
                        </div>
                        <div class="card-body">
                            <div style="height: 200px;">
                                <canvas id="status-chart"></canvas>
                            </div>
                            <div id="status-legend" class="mt-4"></div>
                        </div>
                    </div>
                    
                    <!-- Pending Adoptions -->
                    ${Auth.isStaff() ? `
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Pending Adoptions</h3>
                                <a href="/adoptions" class="btn btn-ghost btn-sm">View All</a>
                            </div>
                            <div class="card-body p-0" id="pending-adoptions">
                                ${Loading.skeleton('list', { items: 3 })}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 gap-3">
                                ${Auth.isStaff() ? `
                                    <button class="btn btn-secondary" onclick="Router.navigate('/animals'); setTimeout(() => AnimalsPage.showAddModal(), 300)">
                                        Add Animal
                                    </button>
                                    <button class="btn btn-secondary" onclick="Router.navigate('/medical'); setTimeout(() => MedicalPage.showAddModal(), 300)">
                                        Medical Record
                                    </button>
                                ` : ''}
                                <button class="btn btn-secondary" onclick="Router.navigate('/animals')">
                                    Browse Animals
                                </button>
                                <button class="btn btn-secondary" onclick="Router.navigate('/adoptions')">
                                    My Adoptions
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Feed -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                        </div>
                        <div class="card-body p-0" id="activity-feed">
                            ${Loading.skeleton('list', { items: 5, hasSubtitle: true })}
                        </div>
                        <div class="card-footer p-2 text-center border-t border-color" id="activity-footer" style="display: none;">
                            <button class="btn btn-ghost btn-sm w-full" onclick="DashboardPage.toggleActivityFeed()">
                                <span id="activity-toggle-text">Show More</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render the page for Adopters
     */
    renderAdopterView() {
        return `
            <div class="page-header">
                <div>
                <h1 class="page-title">Explore!</h1>
                <p class="page-subtitle">Find your new best friend today.</p>
            </div>
            </div>

            <!-- Featured/Recent Animals -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold">Newest Arrivals</h2>
                    <a href="/animals" class="btn btn-ghost btn-sm">Browse All</a>
                </div>
                <div id="adopter-animals-grid" class="grid grid-cols-4 gap-6">
                    ${Loading.skeleton('card', { count: 4 })}
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- My Adoptions -->
                <div class="lg:col-span-3">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">Your Adoption Requests</h3>
                            <a href="/adoptions" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body p-0" id="adopter-adoptions-list">
                            ${Loading.skeleton('list', { items: 3 })}
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
        await this.loadDashboardData();
        this.setupEventListeners();
    },

    /**
     * Load all dashboard data
     */
    async loadDashboardData() {
        try {
            // ADOPTER DATA LOADING
            if (Auth.isAdopter()) {
                const results = await Promise.allSettled([
                    API.animals.available({ per_page: 4, sort: 'date_desc' }),
                    API.adoptions.list({ per_page: 5 })
                ]);

                const [animalsResult, adoptionsResult] = results;

                if (animalsResult.status === 'fulfilled' && animalsResult.value.success) {
                    this.data.recentAnimals = animalsResult.value.data.data || animalsResult.value.data;
                    this.renderAdopterAnimals();
                }

                if (adoptionsResult.status === 'fulfilled' && adoptionsResult.value.success) {
                    // Filter for own adoptions implies backend handles it, but let's be safe
                    this.data.pendingAdoptions = adoptionsResult.value.data.data || adoptionsResult.value.data;
                    this.renderAdopterAdoptions();
                }
                return;
            }

            // STAFF/ADMIN DATA LOADING
            // Load stats
            const results = await Promise.allSettled([
                API.dashboard.stats(),
                API.animals.list({ per_page: 5, sort: 'date_desc' }),
                API.dashboard.activity(10), // Fetch 10 so we can show "Show More"
                API.dashboard.intake('week')
            ]);

            const [statsResult, animalsResult, activityResult, intakeResult] = results;

            // Handle Stats
            if (statsResult.status === 'fulfilled' && statsResult.value.success) {
                this.data.stats = statsResult.value.data;
                this.renderStats();
                // Don't render charts from stats anymore, use explicit intake result
                if (statsResult.value.data.status_distribution) {
                    Charts.statusDistribution('status-chart', statsResult.value.data.status_distribution);
                    this.renderStatusLegend(statsResult.value.data.status_distribution);
                }
            } else if (statsResult.status === 'rejected') {
                console.error('Failed to load stats:', statsResult.reason);
            }

            // Handle Intake Chart
            if (intakeResult.status === 'fulfilled' && intakeResult.value.success) {
                this.renderIntakeChart(intakeResult.value.data);
            }

            // Handle Recent Animals
            if (animalsResult.status === 'fulfilled' && animalsResult.value.success) {
                this.data.recentAnimals = animalsResult.value.data.data || animalsResult.value.data;
                this.renderRecentAnimals();
            } else if (animalsResult.status === 'rejected') {
                console.error('Failed to load recent animals:', animalsResult.reason);
            }

            // Handle Activity
            if (activityResult.status === 'fulfilled' && activityResult.value.success) {
                this.data.recentActivity = activityResult.value.data;
                this.renderActivityFeed();
            } else if (activityResult.status === 'rejected') {
                console.error('Failed to load activity:', activityResult.reason);
            }

            // Load pending adoptions for staff
            if (Auth.isStaff()) {
                try {
                    const adoptionsResponse = await API.adoptions.list({ status: 'Pending', per_page: 5 });
                    if (adoptionsResponse.success) {
                        this.data.pendingAdoptions = adoptionsResponse.data.data || adoptionsResponse.data;
                        this.renderPendingAdoptions();
                    }
                } catch (e) {
                    console.error('Failed to load pending adoptions:', e);
                }
            }

        } catch (error) {
            console.error('Critical error loading dashboard:', error);
            Toast.error('Some dashboard data could not be loaded');
        }
    },

    /**
     * Render animals for Adopter view (Grid)
     */
    renderAdopterAnimals() {
        const container = document.getElementById('adopter-animals-grid');
        if (!container) return;

        if (this.data.recentAnimals.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-secondary">
                    No animals currently available. Check back soon!
                </div>
            `;
            return;
        }

        container.innerHTML = this.data.recentAnimals.map(animal => Card.animal(animal, { square: true })).join('');
    },

    /**
     * Render adoptions for Adopter view (List)
     */
    renderAdopterAdoptions() {
        const container = document.getElementById('adopter-adoptions-list');
        if (!container) return;

        if (this.data.pendingAdoptions.length === 0) {
            container.innerHTML = `
                <div class="empty-state p-6">
                    <p class="text-secondary">You haven't submitted any adoption requests yet.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.data.pendingAdoptions.map(adoption => `
            <div class="flex items-center gap-4 p-4 hover:bg-hover cursor-pointer border-b border-color" onclick="Router.navigate('/adoptions/${adoption.RequestID}')">
                <div class="avatar bg-primary-light text-primary">
                   ${this.icons.adoptions}
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold truncate">${adoption.Animal_Name || 'Unknown Animal'}</h4>
                    <p class="text-secondary text-sm">Status: <span class="${Utils.getStatusBadgeClass(adoption.Status)}">${adoption.Status}</span></p>
                </div>
                <span class="text-tertiary text-xs">${Utils.timeAgo(adoption.Request_Date)}</span>
            </div>
        `).join('');
    },

    /**
     * Render statistics cards
     */
    renderStats() {
        const stats = this.data.stats;
        if (!stats) return;

        const container = document.getElementById('stats-container');
        if (!container) return;

        container.innerHTML = `
            ${Card.stat({
            title: 'Total Animals',
            value: stats.total_animals || 0,
            icon: this.icons.animals,
            iconColor: 'primary',
            trend: stats.animals_trend,
            trendLabel: 'vs last month'
        })}
            ${Card.stat({
            title: 'Available for Adoption',
            value: stats.available_animals || 0,
            icon: this.icons.adoptions,
            iconColor: 'success',
            subtitle: `${stats.adopted_this_month || 0} adopted this month`
        })}
            ${Card.stat({
            title: 'Medical Treatments',
            value: stats.treatments_this_month || 0,
            icon: this.icons.medical,
            iconColor: 'warning',
            subtitle: `${stats.upcoming_treatments || 0} upcoming`
        })}
            ${Auth.isStaff() ? Card.stat({
            title: 'Revenue This Month',
            value: Utils.formatCurrency(stats.revenue_this_month || 0),
            icon: this.icons.revenue,
            iconColor: 'success',
            trend: stats.revenue_trend,
            trendLabel: 'vs last month'
        }) : Card.stat({
            title: 'Pending Requests',
            value: stats.pending_adoptions || 0,
            icon: this.icons.adoptions,
            iconColor: 'warning'
        })}
        `;
    },

    /**
     * Render charts
     */
    renderCharts() {
        const stats = this.data.stats;
        if (!stats) return;

        // Intake chart
        if (stats.monthly_intake) {
            this.renderIntakeChart(stats.monthly_intake);
        }

        // Status distribution chart
        if (stats.status_distribution) {
            Charts.statusDistribution('status-chart', stats.status_distribution);
            this.renderStatusLegend(stats.status_distribution);
        }
    },

    /**
     * Render status legend
     * @param {Object} distribution
     */
    renderStatusLegend(distribution) {
        const container = document.getElementById('status-legend');
        if (!container) return;

        const total = Object.values(distribution).reduce((a, b) => a + b, 0);

        container.innerHTML = Object.entries(distribution).map(([status, count]) => {
            const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
            const badgeClass = Utils.getStatusBadgeClass(status);

            return `
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <span class="badge ${badgeClass}">${status}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold">${count}</span>
                        <span class="text-tertiary text-sm ml-1">(${percentage}%)</span>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Render recent animals list
     */
    renderRecentAnimals() {
        const container = document.getElementById('recent-animals');
        if (!container) return;

        if (this.data.recentAnimals.length === 0) {
            container.innerHTML = `
                <div class="empty-state p-6">
                    <p class="text-secondary">No animals found</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.data.recentAnimals.map(animal => `
            <div class="flex items-center gap-4 p-4 hover:bg-hover cursor-pointer border-b border-color" onclick="Router.navigate('/animals/${animal.AnimalID}')">
                <div class="avatar avatar-lg" style="border-radius: var(--radius-lg); overflow: hidden;">
                    <img src="${animal.Image_URL || 'assets/images/placeholder-animal.svg'}" alt="${animal.Name}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='assets/images/placeholder-animal.svg'">
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold truncate">${animal.Name}</h4>
                    <p class="text-secondary text-sm">${animal.Breed || animal.Type} • ${animal.Gender}</p>
                </div>
                <span class="badge ${Utils.getStatusBadgeClass(animal.Current_Status)}">${animal.Current_Status}</span>
            </div>
        `).join('');
    },

    /**
     * Render pending adoptions
     */
    renderPendingAdoptions() {
        const container = document.getElementById('pending-adoptions');
        if (!container) return;

        if (this.data.pendingAdoptions.length === 0) {
            container.innerHTML = `
                <div class="empty-state p-6">
                    <p class="text-secondary">No pending adoptions</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.data.pendingAdoptions.map(adoption => `
            <div class="flex items-center gap-4 p-4 hover:bg-hover cursor-pointer border-b border-color" onclick="Router.navigate('/adoptions')">
                <div class="avatar" style="background: ${Utils.stringToColor(adoption.Adopter_Email || '')}">
                    ${Utils.getInitials(`${adoption.FirstName || ''} ${adoption.LastName || ''}`)}
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold truncate">${adoption.FirstName} ${adoption.LastName}</h4>
                    <p class="text-secondary text-sm">Wants to adopt ${adoption.Animal_Name}</p>
                </div>
                <span class="text-tertiary text-xs">${Utils.timeAgo(adoption.Request_Date)}</span>
            </div>
        `).join('');
    },

    /**
     * Render activity feed
     */
    renderActivityFeed() {
        const container = document.getElementById('activity-feed');
        const footer = document.getElementById('activity-footer');
        const toggleText = document.getElementById('activity-toggle-text');

        if (!container) return;

        if (this.data.recentActivity.length === 0) {
            container.innerHTML = `
                <div class="empty-state p-6">
                    <p class="text-secondary">No recent activity</p>
                </div>
            `;
            if (footer) footer.style.display = 'none';
            return;
        }

        // Determine which items to show
        const limit = 4;
        const total = this.data.recentActivity.length;
        const showAll = this.data.showAllActivity;

        const itemsToRender = showAll ? this.data.recentActivity : this.data.recentActivity.slice(0, limit);

        container.innerHTML = itemsToRender.map(activity => Card.activity(activity)).join('');

        // Handle footer/button visibility
        if (footer) {
            if (total > limit) {
                footer.style.display = 'block';
                toggleText.textContent = showAll ? 'Show Less' : 'Show More';
            } else {
                footer.style.display = 'none';
            }
        }
    },

    /**
     * Toggle activity feed view
     */
    toggleActivityFeed() {
        this.data.showAllActivity = !this.data.showAllActivity;
        this.renderActivityFeed();
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Chart period tabs
        const chartTabs = document.getElementById('chart-tabs');
        if (chartTabs) {
            chartTabs.addEventListener('click', (e) => {
                const tab = e.target.closest('.tab');
                if (tab) {
                    chartTabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    this.updateChartPeriod(tab.dataset.period);
                }
            });
        }
    },

    /**
     * Update chart period
     * @param {string} period
     */
    async updateChartPeriod(period) {
        try {
            const result = await API.dashboard.intake(period);

            if (result.success) {
                // Update local data
                if (!this.data.stats) this.data.stats = {};
                this.data.stats.monthly_intake = result.data;

                // Re-render chart
                this.renderIntakeChart(result.data);
            }
        } catch (error) {
            console.error('Failed to update chart period:', error);
            Toast.error('Failed to load chart data');
        }
    },

    /**
     * Render intake chart specifically
     * @param {Array} data
     */
    renderIntakeChart(data) {
        if (!data) return;

        Charts.line('intake-chart', {
            labels: data.map(d => d.label || d.month), // Backend returns label or month
            datasets: [
                {
                    label: 'Dogs',
                    data: data.map(d => d.dogs || 0),
                    color: Charts.colors.primary
                },
                {
                    label: 'Cats',
                    data: data.map(d => d.cats || 0),
                    color: Charts.colors.success
                }
            ],
            showLegend: true,
            fill: true
        });
    },

    /**
     * Refresh dashboard data
     */
    async refresh() {
        Toast.info('Refreshing dashboard...');
        await this.loadDashboardData();
        Toast.success('Dashboard refreshed');
    }
};

// Make DashboardPage globally available
window.DashboardPage = DashboardPage;