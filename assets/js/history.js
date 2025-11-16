/**
 * Simulation History JavaScript
 * Handles simulation list, details, comparison, and deletion
 */

let currentPage = 1;
let currentSearch = '';
let selectedSimulations = new Set();
let detailChart = null;
let compareChart = null;

// Load simulations on page load
document.addEventListener('DOMContentLoaded', () => {
    loadSimulations();
    setupEventListeners();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
    document.getElementById('searchButton').addEventListener('click', () => {
        currentSearch = document.getElementById('searchInput').value;
        currentPage = 1;
        loadSimulations();
    });
    
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            currentSearch = document.getElementById('searchInput').value;
            currentPage = 1;
            loadSimulations();
        }
    });
    
    document.getElementById('selectAll').addEventListener('change', (e) => {
        const checkboxes = document.querySelectorAll('.sim-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = e.target.checked;
            if (e.target.checked) {
                selectedSimulations.add(parseInt(cb.value));
            } else {
                selectedSimulations.delete(parseInt(cb.value));
            }
        });
        updateCompareSection();
    });
    
    document.getElementById('compareButton').addEventListener('click', () => {
        compareSimulations();
    });
    
    document.getElementById('clearCompareButton').addEventListener('click', () => {
        selectedSimulations.clear();
        document.querySelectorAll('.sim-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateCompareSection();
    });
}

/**
 * Load simulations from API
 */
async function loadSimulations() {
    showLoadingSpinner();
    
    try {
        // Use relative path from current page location
        const url = new URL('api/get_simulations.php', window.location.href);
        url.searchParams.append('page', currentPage);
        if (currentSearch) {
            url.searchParams.append('search', currentSearch);
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            if (data.simulations.length === 0) {
                showEmptyState();
            } else {
                displaySimulations(data.simulations);
                displayPagination(data.pagination);
            }
        } else {
            showNotification('Error loading simulations: ' + data.error, 'danger');
        }
    } catch (error) {
        showNotification('Network error: ' + error.message, 'danger');
    }
    
    hideLoadingSpinner();
}

/**
 * Display simulations in table
 */
function displaySimulations(simulations) {
    const tbody = document.getElementById('simulationsTableBody');
    tbody.innerHTML = '';
    
    simulations.forEach(sim => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input sim-checkbox" 
                       value="${sim.id}" ${selectedSimulations.has(sim.id) ? 'checked' : ''}>
            </td>
            <td>${sim.id}</td>
            <td>${formatDate(sim.created_at)}</td>
            <td>${formatCurrency(sim.asset_value)}</td>
            <td>${formatCurrency(sim.mean_loss)}</td>
            <td>${formatCurrency(sim.median_loss)}</td>
            <td>${formatCurrency(sim.percentile_95)}</td>
            <td>
                ${sim.tags ? `<span class="badge bg-secondary">${sim.tags}</span>` : ''}
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewDetails(${sim.id})">
                    <i class="bi bi-eye"></i>
                </button>
                <a href="api/export_csv.php?id=${sim.id}" class="btn btn-sm btn-success">
                    <i class="bi bi-download"></i>
                </a>
                <button class="btn btn-sm btn-danger" onclick="deleteSimulation(${sim.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Add event listeners to checkboxes
    document.querySelectorAll('.sim-checkbox').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const id = parseInt(e.target.value);
            if (e.target.checked) {
                selectedSimulations.add(id);
            } else {
                selectedSimulations.delete(id);
            }
            updateCompareSection();
        });
    });
    
    document.getElementById('historyTable').classList.remove('d-none');
    document.getElementById('emptyState').classList.add('d-none');
}

/**
 * Display pagination
 */
function displayPagination(pagination) {
    const paginationEl = document.getElementById('pagination');
    paginationEl.innerHTML = '';
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${pagination.page === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#">Previous</a>`;
    prevLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (pagination.page > 1) {
            currentPage = pagination.page - 1;
            loadSimulations();
        }
    });
    paginationEl.appendChild(prevLi);
    
    // Page numbers
    for (let i = 1; i <= pagination.pages; i++) {
        // Show first, last, current, and adjacent pages
        if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
            const li = document.createElement('li');
            li.className = `page-item ${i === pagination.page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = i;
                loadSimulations();
            });
            paginationEl.appendChild(li);
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = `<span class="page-link">...</span>`;
            paginationEl.appendChild(li);
        }
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${pagination.page === pagination.pages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#">Next</a>`;
    nextLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (pagination.page < pagination.pages) {
            currentPage = pagination.page + 1;
            loadSimulations();
        }
    });
    paginationEl.appendChild(nextLi);
}

/**
 * View simulation details
 */
async function viewDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    
    document.getElementById('modalLoading').classList.remove('d-none');
    document.getElementById('modalContent').classList.add('d-none');
    
    try {
        const response = await fetch(`api/get_simulation_detail.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            displaySimulationDetail(data.simulation, data.histogram);
            document.getElementById('modalExportLink').href = `api/export_csv.php?id=${id}`;
        } else {
            showNotification('Error loading simulation: ' + data.error, 'danger');
            modal.hide();
        }
    } catch (error) {
        showNotification('Network error: ' + error.message, 'danger');
        modal.hide();
    }
    
    document.getElementById('modalLoading').classList.add('d-none');
    document.getElementById('modalContent').classList.remove('d-none');
}

/**
 * Display simulation detail in modal
 */
function displaySimulationDetail(sim, histogram) {
    // Input parameters
    document.getElementById('detailAssetValue').textContent = formatCurrency(sim.asset_value);
    document.getElementById('detailTEF').textContent = `${sim.tef_min} - ${sim.tef_max}`;
    document.getElementById('detailVuln').textContent = `${sim.vulnerability_percent}%`;
    document.getElementById('detailPrimaryLoss').textContent = 
        `${formatCurrency(sim.primary_loss_min)} - ${formatCurrency(sim.primary_loss_max)}`;
    document.getElementById('detailSecondaryLoss').textContent = 
        `${formatCurrency(sim.secondary_loss_min)} - ${formatCurrency(sim.secondary_loss_max)}`;
    document.getElementById('detailIterations').textContent = sim.num_iterations.toLocaleString();
    
    // Statistics
    document.getElementById('detailMean').textContent = formatCurrency(sim.mean_loss);
    document.getElementById('detailMedian').textContent = formatCurrency(sim.median_loss);
    document.getElementById('detailMinMax').textContent = 
        `${formatCurrency(sim.min_loss)} - ${formatCurrency(sim.max_loss)}`;
    document.getElementById('detailP90').textContent = formatCurrency(sim.percentile_90);
    document.getElementById('detailP95').textContent = formatCurrency(sim.percentile_95);
    document.getElementById('detailP99').textContent = formatCurrency(sim.percentile_99);
    
    // Notes
    const notesEl = document.getElementById('detailNotes');
    if (sim.notes) {
        notesEl.innerHTML = `<strong>Notes:</strong> ${sim.notes}`;
    } else {
        notesEl.innerHTML = '';
    }
    
    // Render chart
    renderDetailHistogram(histogram);
}

/**
 * Render detail histogram
 */
function renderDetailHistogram(histogramData) {
    const ctx = document.getElementById('detailHistogram');
    
    if (detailChart) {
        detailChart.destroy();
    }
    
    detailChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: histogramData.labels,
            datasets: [{
                label: 'Frequency',
                data: histogramData.data,
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Annual Loss Distribution',
                    font: { size: 16, weight: 'bold' }
                },
                legend: { display: false }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Annual Loss ($)' },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        callback: function(value, index) {
                            return index % 5 === 0 ? formatCurrencyShort(this.getLabelForValue(value)) : '';
                        }
                    }
                },
                y: {
                    title: { display: true, text: 'Frequency' },
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Compare selected simulations
 */
async function compareSimulations() {
    if (selectedSimulations.size < 2) {
        showNotification('Please select at least 2 simulations to compare', 'warning');
        return;
    }
    
    if (selectedSimulations.size > 3) {
        showNotification('You can compare up to 3 simulations at a time', 'warning');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('compareModal'));
    modal.show();
    
    try {
        const simulations = [];
        for (const id of selectedSimulations) {
            const response = await fetch(`api/get_simulation_detail.php?id=${id}`);
            const data = await response.json();
            if (data.success) {
                simulations.push(data);
            }
        }
        
        displayComparison(simulations);
    } catch (error) {
        showNotification('Error loading simulations: ' + error.message, 'danger');
        modal.hide();
    }
}

/**
 * Display comparison chart and table
 */
function displayComparison(simulations) {
    const colors = [
        'rgba(13, 110, 253, 0.7)',
        'rgba(25, 135, 84, 0.7)',
        'rgba(220, 53, 69, 0.7)'
    ];
    
    const datasets = simulations.map((sim, index) => ({
        label: `Simulation ${sim.simulation.id}`,
        data: sim.histogram.data,
        backgroundColor: colors[index],
        borderColor: colors[index].replace('0.7', '1'),
        borderWidth: 1
    }));
    
    const ctx = document.getElementById('compareChart');
    
    if (compareChart) {
        compareChart.destroy();
    }
    
    compareChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: simulations[0].histogram.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Simulation Comparison',
                    font: { size: 16, weight: 'bold' }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Annual Loss ($)' },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        callback: function(value, index) {
                            return index % 5 === 0 ? formatCurrencyShort(this.getLabelForValue(value)) : '';
                        }
                    }
                },
                y: {
                    title: { display: true, text: 'Frequency' },
                    beginAtZero: true
                }
            }
        }
    });
    
    // Display comparison table
    const tbody = document.getElementById('compareTableBody');
    tbody.innerHTML = '';
    
    simulations.forEach(sim => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>Simulation ${sim.simulation.id}</td>
            <td>${formatCurrency(sim.simulation.mean_loss)}</td>
            <td>${formatCurrency(sim.simulation.median_loss)}</td>
            <td>${formatCurrency(sim.simulation.percentile_90)}</td>
            <td>${formatCurrency(sim.simulation.percentile_95)}</td>
            <td>${formatCurrency(sim.simulation.percentile_99)}</td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Delete simulation
 */
async function deleteSimulation(id) {
    if (!confirm('Are you sure you want to delete this simulation?')) {
        return;
    }
    
    try {
        const response = await fetch('api/delete_simulation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Simulation deleted successfully', 'success');
            loadSimulations();
        } else {
            showNotification('Error deleting simulation: ' + data.error, 'danger');
        }
    } catch (error) {
        showNotification('Network error: ' + error.message, 'danger');
    }
}

/**
 * Update compare section
 */
function updateCompareSection() {
    const count = selectedSimulations.size;
    document.getElementById('compareCount').textContent = count;
    document.getElementById('compareButton').disabled = count < 2;
    
    if (count > 0) {
        document.getElementById('compareSection').classList.remove('d-none');
    } else {
        document.getElementById('compareSection').classList.add('d-none');
    }
}

/**
 * Show/hide UI elements
 */
function showLoadingSpinner() {
    document.getElementById('loadingSpinner').classList.remove('d-none');
    document.getElementById('historyTable').classList.add('d-none');
    document.getElementById('emptyState').classList.add('d-none');
}

function hideLoadingSpinner() {
    document.getElementById('loadingSpinner').classList.add('d-none');
}

function showEmptyState() {
    document.getElementById('emptyState').classList.remove('d-none');
    document.getElementById('historyTable').classList.add('d-none');
}

/**
 * Utility functions
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

function formatCurrencyShort(value) {
    const num = parseFloat(value.replace(/[^0-9.-]+/g, ''));
    if (num >= 1000000000) {
        return '$' + (num / 1000000000).toFixed(1) + 'B';
    } else if (num >= 1000000) {
        return '$' + (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return '$' + (num / 1000).toFixed(0) + 'K';
    }
    return '$' + num.toFixed(0);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
