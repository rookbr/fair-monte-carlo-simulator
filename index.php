<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAIR Risk Analysis Monte Carlo Simulator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-bar-chart-line-fill"></i> FAIR Monte Carlo Simulator
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">New Simulation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">Simulation History</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-sliders"></i> Simulation Parameters</h5>
                    </div>
                    <div class="card-body">
                        <form id="simulationForm">
                            <div class="mb-3">
                                <label for="assetValue" class="form-label">
                                    Asset Value ($)
                                    <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" 
                                       title="Total value of the asset at risk"></i>
                                </label>
                                <input type="number" class="form-control" id="assetValue" 
                                       min="0" step="0.01" value="1000000" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tefMin" class="form-label">
                                        TEF Min (events/year)
                                        <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" 
                                           title="Minimum Threat Event Frequency - how often threats occur per year"></i>
                                    </label>
                                    <input type="number" class="form-control" id="tefMin" 
                                           min="0" step="0.01" value="0.1" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tefMax" class="form-label">
                                        TEF Max (events/year)
                                    </label>
                                    <input type="number" class="form-control" id="tefMax" 
                                           min="0" step="0.01" value="5" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="vulnerability" class="form-label">
                                    Vulnerability (%)
                                    <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" 
                                       title="Probability that a threat will successfully exploit a vulnerability"></i>
                                </label>
                                <input type="number" class="form-control" id="vulnerability" 
                                       min="0" max="100" step="0.1" value="25" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="primaryLossMin" class="form-label">
                                        Primary Loss Min ($)
                                        <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" 
                                           title="Minimum direct loss from an event (e.g., response costs, productivity loss)"></i>
                                    </label>
                                    <input type="number" class="form-control" id="primaryLossMin" 
                                           min="0" step="0.01" value="10000" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="primaryLossMax" class="form-label">
                                        Primary Loss Max ($)
                                    </label>
                                    <input type="number" class="form-control" id="primaryLossMax" 
                                           min="0" step="0.01" value="500000" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="secondaryLossMin" class="form-label">
                                        Secondary Loss Min ($)
                                        <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" 
                                           title="Minimum indirect loss (e.g., reputation damage, regulatory fines)"></i>
                                    </label>
                                    <input type="number" class="form-control" id="secondaryLossMin" 
                                           min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="secondaryLossMax" class="form-label">
                                        Secondary Loss Max ($)
                                    </label>
                                    <input type="number" class="form-control" id="secondaryLossMax" 
                                           min="0" step="0.01" value="100000">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="numIterations" class="form-label">
                                    Number of Simulations
                                    <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" 
                                       title="More iterations provide more accurate results (1,000 - 100,000)"></i>
                                </label>
                                <input type="number" class="form-control" id="numIterations" 
                                       min="1000" max="100000" step="1000" value="10000" required>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (optional)</label>
                                <textarea class="form-control" id="notes" rows="2" 
                                          placeholder="Add notes about this simulation..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="tags" class="form-label">Tags (optional)</label>
                                <input type="text" class="form-control" id="tags" 
                                       placeholder="e.g., ransomware, data-breach">
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100" id="runButton">
                                <i class="bi bi-play-circle-fill"></i> Run Simulation
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Simulation Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="loadingState" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Running simulation...</span>
                            </div>
                            <p class="mt-3 text-muted">Running Monte Carlo simulation...</p>
                        </div>

                        <div id="resultsSection" class="d-none">
                            <div class="alert alert-info mb-4">
                                <strong>Simulation ID:</strong> <span id="simulationId"></span> | 
                                <strong>Iterations:</strong> <span id="iterationCount"></span> |
                                <a href="#" id="exportLink" class="alert-link">Export to CSV</a>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-label">Mean Annual Loss</div>
                                        <div class="stat-value text-primary" id="meanLoss">$0</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-label">Median Annual Loss</div>
                                        <div class="stat-value text-success" id="medianLoss">$0</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="stat-card-sm">
                                        <div class="stat-label-sm">90th Percentile</div>
                                        <div class="stat-value-sm" id="percentile90">$0</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="stat-card-sm">
                                        <div class="stat-label-sm">95th Percentile</div>
                                        <div class="stat-value-sm" id="percentile95">$0</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="stat-card-sm">
                                        <div class="stat-label-sm">99th Percentile</div>
                                        <div class="stat-value-sm" id="percentile99">$0</div>
                                    </div>
                                </div>
                            </div>

                            <div class="chart-container">
                                <canvas id="lossHistogram"></canvas>
                            </div>

                            <div class="mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Statistic</th>
                                            <th class="text-end">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Minimum Loss</td>
                                            <td class="text-end" id="minLoss">$0</td>
                                        </tr>
                                        <tr>
                                            <td>Maximum Loss</td>
                                            <td class="text-end" id="maxLoss">$0</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="emptyState" class="text-center py-5">
                            <i class="bi bi-bar-chart" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Run a simulation to see results</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center text-muted">
            <small>FAIR Monte Carlo Simulator - Risk Analysis Tool</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    <script src="assets/js/monte-carlo.js"></script>

    <script>
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    </script>
</body>
</html>
