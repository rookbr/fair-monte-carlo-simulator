/**
 * Monte Carlo Simulation JavaScript
 * Handles form submission, API calls, and chart rendering
 */

let currentChart = null;
let currentSimulationId = null;

// Form submission handler
document.getElementById('simulationForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate inputs
    if (!validateForm()) {
        return;
    }
    
    // Get form data
    const formData = {
        asset_value: parseFloat(document.getElementById('assetValue').value),
        tef_min: parseFloat(document.getElementById('tefMin').value),
        tef_max: parseFloat(document.getElementById('tefMax').value),
        vulnerability_percent: parseFloat(document.getElementById('vulnerability').value),
        primary_loss_min: parseFloat(document.getElementById('primaryLossMin').value),
        primary_loss_max: parseFloat(document.getElementById('primaryLossMax').value),
        secondary_loss_min: parseFloat(document.getElementById('secondaryLossMin').value),
        secondary_loss_max: parseFloat(document.getElementById('secondaryLossMax').value),
        num_iterations: parseInt(document.getElementById('numIterations').value),
        notes: document.getElementById('notes').value,
        tags: document.getElementById('tags').value
    };
    
    // Show loading state
    showLoading();
    
    try {
        const response = await fetch('api/run_simulation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentSimulationId = data.simulation_id;
            displayResults(data);
            showNotification('Simulation completed successfully!', 'success');
        } else {
            showNotification('Error: ' + data.error, 'danger');
            hideLoading();
        }
    } catch (error) {
        showNotification('Network error: ' + error.message, 'danger');
        hideLoading();
    }
});

/**
 * Validate form inputs
 */
function validateForm() {
    const tefMin = parseFloat(document.getElementById('tefMin').value);
    const tefMax = parseFloat(document.getElementById('tefMax').value);
    const primaryMin = parseFloat(document.getElementById('primaryLossMin').value);
    const primaryMax = parseFloat(document.getElementById('primaryLossMax').value);
    const secondaryMin = parseFloat(document.getElementById('secondaryLossMin').value);
    const secondaryMax = parseFloat(document.getElementById('secondaryLossMax').value);
    
    if (tefMin > tefMax) {
        showNotification('TEF Min cannot be greater than TEF Max', 'warning');
        return false;
    }
    
    if (primaryMin > primaryMax) {
        showNotification('Primary Loss Min cannot be greater than Primary Loss Max', 'warning');
        return false;
    }
    
    if (secondaryMin > secondaryMax) {
        showNotification('Secondary Loss Min cannot be greater than Secondary Loss Max', 'warning');
        return false;
    }
    
    return true;
}

/**
 * Show loading state
 */
function showLoading() {
    document.getElementById('emptyState').classList.add('d-none');
    document.getElementById('resultsSection').classList.add('d-none');
    document.getElementById('loadingState').classList.remove('d-none');
    document.getElementById('runButton').disabled = true;
}

/**
 * Hide loading state
 */
function hideLoading() {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('runButton').disabled = false;
}

/**
 * Display simulation results
 */
function displayResults(data) {
    hideLoading();
    
    // Update simulation info
    document.getElementById('simulationId').textContent = data.simulation_id;
    document.getElementById('iterationCount').textContent = data.num_iterations.toLocaleString();
    
    // Update statistics
    document.getElementById('meanLoss').textContent = formatCurrency(data.statistics.mean);
    document.getElementById('medianLoss').textContent = formatCurrency(data.statistics.median);
    document.getElementById('minLoss').textContent = formatCurrency(data.statistics.min);
    document.getElementById('maxLoss').textContent = formatCurrency(data.statistics.max);
    document.getElementById('percentile90').textContent = formatCurrency(data.statistics.percentile_90);
    document.getElementById('percentile95').textContent = formatCurrency(data.statistics.percentile_95);
    document.getElementById('percentile99').textContent = formatCurrency(data.statistics.percentile_99);
    
    // Update export link
    document.getElementById('exportLink').href = `api/export_csv.php?id=${data.simulation_id}`;
    
    // Display results section
    document.getElementById('resultsSection').classList.remove('d-none');
    
    // Render chart
    renderHistogram(data.histogram);
}

/**
 * Render histogram chart
 */
function renderHistogram(histogramData) {
    const ctx = document.getElementById('lossHistogram');
    
    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }
    
    currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: histogramData.labels,
            datasets: [{
                label: 'Frequency',
                data: histogramData.data,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
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
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            const nextIndex = index + 1;
                            if (nextIndex < histogramData.labels.length) {
                                return formatCurrency(histogramData.labels[index]) + ' - ' + 
                                       formatCurrency(histogramData.labels[nextIndex]);
                            }
                            return formatCurrency(histogramData.labels[index]) + '+';
                        },
                        label: function(context) {
                            return 'Occurrences: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Annual Loss ($)'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        callback: function(value, index) {
                            // Show every 5th label to avoid crowding
                            return index % 5 === 0 ? formatCurrencyShort(this.getLabelForValue(value)) : '';
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Frequency'
                    },
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Format number as currency
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

/**
 * Format currency with abbreviations (K, M, B)
 */
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

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
