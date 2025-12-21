/**
 * Charts Component
 * Wrapper for Chart.js with consistent styling
 * 
 * @package AnimalShelter
 */

const Charts = {
    /**
     * Chart instances
     */
    instances: {},

    /**
     * Default options
     */
    defaults: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    family: '-apple-system, BlinkMacSystemFont, "SF Pro Display", "Inter", sans-serif',
                    size: 13,
                    weight: '600'
                },
                bodyFont: {
                    family: '-apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif',
                    size: 12
                },
                padding: 12,
                cornerRadius: 8,
                displayColors: true
            }
        }
    },

    /**
     * Color palette (Apple-inspired)
     */
    colors: {
        primary: '#007AFF',
        secondary: '#5856D6',
        success: '#34C759',
        warning: '#FF9500',
        danger: '#FF3B30',
        info: '#5AC8FA',
        gray: '#8E8E93',

        // Gradient pairs
        primaryGradient: ['#007AFF', '#5856D6'],
        successGradient: ['#34C759', '#30D158'],
        warningGradient: ['#FF9500', '#FFCC00'],
        dangerGradient: ['#FF3B30', '#FF6961'],

        // Chart palette
        palette: [
            '#007AFF',
            '#34C759',
            '#FF9500',
            '#FF3B30',
            '#5856D6',
            '#5AC8FA',
            '#AF52DE',
            '#FF2D55'
        ]
    },

    /**
     * Create chart
     * @param {string} canvasId
     * @param {Object} config
     * @returns {Chart}
     */
    create(canvasId, config) {
        // Destroy existing chart
        if (this.instances[canvasId]) {
            this.instances[canvasId].destroy();
        }

        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas element with id "${canvasId}" not found`);
            return null;
        }

        const ctx = canvas.getContext('2d');

        // Merge with defaults
        const mergedConfig = this.mergeConfig(config);

        // Create chart
        this.instances[canvasId] = new Chart(ctx, mergedConfig);

        return this.instances[canvasId];
    },

    /**
     * Merge config with defaults
     * @param {Object} config
     * @returns {Object}
     */
    mergeConfig(config) {
        return {
            ...config,
            options: {
                ...this.defaults,
                ...config.options,
                plugins: {
                    ...this.defaults.plugins,
                    ...config.options?.plugins
                }
            }
        };
    },

    /**
     * Create gradient
     * @param {CanvasRenderingContext2D} ctx
     * @param {string} startColor
     * @param {string} endColor
     * @param {boolean} vertical
     * @returns {CanvasGradient}
     */
    createGradient(ctx, startColor, endColor, vertical = true) {
        const gradient = vertical
            ? ctx.createLinearGradient(0, 0, 0, ctx.canvas.height)
            : ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);

        gradient.addColorStop(0, startColor);
        gradient.addColorStop(1, endColor);

        return gradient;
    },

    /**
     * ==========================================
     * CHART TYPES
     * ==========================================
     */

    /**
     * Line chart
     * @param {string} canvasId
     * @param {Object} options
     */
    line(canvasId, options) {
        const {
            labels = [],
            datasets = [],
            title = '',
            showLegend = false,
            tension = 0.4,
            fill = false,
            yAxisLabel = '',
            xAxisLabel = ''
        } = options;

        const formattedDatasets = datasets.map((ds, index) => ({
            label: ds.label,
            data: ds.data,
            borderColor: ds.color || this.colors.palette[index % this.colors.palette.length],
            backgroundColor: ds.backgroundColor || (fill
                ? this.hexToRgba(ds.color || this.colors.palette[index % this.colors.palette.length], 0.1)
                : 'transparent'),
            borderWidth: 2,
            tension,
            fill,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2
        }));

        return this.create(canvasId, {
            type: 'line',
            data: { labels, datasets: formattedDatasets },
            options: {
                plugins: {
                    legend: { display: showLegend },
                    title: { display: !!title, text: title }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        title: { display: !!xAxisLabel, text: xAxisLabel }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        title: { display: !!yAxisLabel, text: yAxisLabel }
                    }
                }
            }
        });
    },

    /**
     * Bar chart
     * @param {string} canvasId
     * @param {Object} options
     */
    bar(canvasId, options) {
        const {
            labels = [],
            datasets = [],
            title = '',
            showLegend = false,
            horizontal = false,
            stacked = false,
            borderRadius = 6
        } = options;

        const formattedDatasets = datasets.map((ds, index) => ({
            label: ds.label,
            data: ds.data,
            backgroundColor: ds.color || this.colors.palette[index % this.colors.palette.length],
            borderRadius,
            borderSkipped: false
        }));

        return this.create(canvasId, {
            type: 'bar',
            data: { labels, datasets: formattedDatasets },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: showLegend },
                    title: { display: !!title, text: title }
                },
                scales: {
                    x: {
                        stacked,
                        grid: { display: false }
                    },
                    y: {
                        stacked,
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    }
                }
            }
        });
    },

    /**
     * Doughnut/Pie chart
     * @param {string} canvasId
     * @param {Object} options
     */
    doughnut(canvasId, options) {
        const {
            labels = [],
            data = [],
            colors = null,
            title = '',
            showLegend = true,
            cutout = '70%'
        } = options;

        const backgroundColors = colors || this.colors.palette.slice(0, data.length);

        return this.create(canvasId, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: backgroundColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                cutout,
                plugins: {
                    legend: {
                        display: showLegend,
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    title: { display: !!title, text: title }
                }
            }
        });
    },

    /**
     * Pie chart
     * @param {string} canvasId
     * @param {Object} options
     */
    pie(canvasId, options) {
        return this.doughnut(canvasId, { ...options, cutout: '0%' });
    },

    /**
     * Area chart
     * @param {string} canvasId
     * @param {Object} options
     */
    area(canvasId, options) {
        return this.line(canvasId, { ...options, fill: true });
    },

    /**
     * ==========================================
     * SPECIALIZED CHARTS
     * ==========================================
     */

    /**
     * Stats mini chart (sparkline)
     * @param {string} canvasId
     * @param {Array} data
     * @param {string} color
     */
    sparkline(canvasId, data, color = this.colors.primary) {
        return this.create(canvasId, {
            type: 'line',
            data: {
                labels: data.map((_, i) => i),
                datasets: [{
                    data,
                    borderColor: color,
                    backgroundColor: this.hexToRgba(color, 0.1),
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                elements: {
                    point: { radius: 0 }
                }
            }
        });
    },

    /**
     * Progress ring chart
     * @param {string} canvasId
     * @param {number} value - Percentage (0-100)
     * @param {Object} options
     */
    progressRing(canvasId, value, options = {}) {
        const { color = this.colors.primary, label = '' } = options;

        return this.create(canvasId, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [value, 100 - value],
                    backgroundColor: [color, 'rgba(0, 0, 0, 0.05)'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '80%',
                rotation: -90,
                circumference: 360,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    },

    /**
     * Animal stats chart
     * @param {string} canvasId
     * @param {Object} stats
     */
    animalStats(canvasId, stats) {
        const labels = ['Dogs', 'Cats', 'Others'];
        const data = [stats.dogs || 0, stats.cats || 0, stats.others || 0];

        return this.doughnut(canvasId, {
            labels,
            data,
            colors: [this.colors.primary, this.colors.success, this.colors.warning]
        });
    },

    /**
     * Status distribution chart
     * @param {string} canvasId
     * @param {Object} distribution
     */
    statusDistribution(canvasId, distribution) {
        const statusColors = {
            'Available': this.colors.success,
            'Adopted': this.colors.info,
            'In Treatment': this.colors.warning,
            'Quarantine': this.colors.danger,
            'Deceased': this.colors.gray,
            'Reclaimed': this.colors.secondary
        };

        const labels = Object.keys(distribution);
        const data = Object.values(distribution);
        const colors = labels.map(label => statusColors[label] || this.colors.gray);

        return this.doughnut(canvasId, { labels, data, colors });
    },

    /**
     * Monthly trend chart
     * @param {string} canvasId
     * @param {Array} monthlyData - [{month, value}]
     * @param {Object} options
     */
    monthlyTrend(canvasId, monthlyData, options = {}) {
        const labels = monthlyData.map(d => d.month);
        const data = monthlyData.map(d => d.value);

        return this.line(canvasId, {
            labels,
            datasets: [{
                label: options.label || 'Value',
                data,
                color: options.color || this.colors.primary
            }],
            fill: true,
            tension: 0.4
        });
    },

    /**
     * ==========================================
     * UTILITIES
     * ==========================================
     */

    /**
     * Update chart data
     * @param {string} canvasId
     * @param {Object} newData
     */
    update(canvasId, newData) {
        const chart = this.instances[canvasId];
        if (!chart) return;

        if (newData.labels) {
            chart.data.labels = newData.labels;
        }

        if (newData.datasets) {
            newData.datasets.forEach((ds, index) => {
                if (chart.data.datasets[index]) {
                    Object.assign(chart.data.datasets[index], ds);
                }
            });
        }

        chart.update();
    },

    /**
     * Destroy chart
     * @param {string} canvasId
     */
    destroy(canvasId) {
        if (this.instances[canvasId]) {
            this.instances[canvasId].destroy();
            delete this.instances[canvasId];
        }
    },

    /**
     * Destroy all charts
     */
    destroyAll() {
        Object.keys(this.instances).forEach(id => this.destroy(id));
    },

    /**
     * Convert hex to rgba
     * @param {string} hex
     * @param {number} alpha
     * @returns {string}
     */
    hexToRgba(hex, alpha = 1) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        if (!result) return hex;

        return `rgba(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}, ${alpha})`;
    },

    /**
     * Render chart container
     * @param {string} id
     * @param {Object} options
     * @returns {string}
     */
    container(id, options = {}) {
        const { height = 300, title = '' } = options;

        return `
            <div class="chart-container">
                ${title ? `<h4 class="chart-title">${title}</h4>` : ''}
                <div style="height: ${height}px; position: relative;">
                    <canvas id="${id}"></canvas>
                </div>
            </div>
        `;
    }
};

// Add chart styles
const chartStyles = document.createElement('style');
chartStyles.textContent = `
    .chart-container {
        position: relative;
    }
    
    .chart-title {
        font-size: var(--text-sm);
        font-weight: var(--font-semibold);
        color: var(--text-secondary);
        margin-bottom: var(--space-4);
    }
`;

document.head.appendChild(chartStyles);

// Make Charts globally available
window.Charts = Charts;