<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation History - FAIR Monte Carlo</title>
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
                        <a class="nav-link" href="index.php">New Simulation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="history.php">Simulation History</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="bi bi-clock-history"></i> Simulation History</h2>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search notes or tags...">
                    <button class="btn btn-outline-primary" type="button" id="searchButton">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="compareSection" class="alert alert-warning d-none">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Compare Mode:</strong> <span id="compareCount">0</span> simulations selected
                </div>
                <div>
                    <button class="btn btn-sm btn-primary" id="compareButton" disabled>
                        <i class="bi bi-bar-chart-line"></i> Compare
                    </button>
                    <button class="btn btn-sm btn-secondary" id="clearCompareButton">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div id="loadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div id="historyTable" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Asset Value</th>
                                    <th>Mean Loss</th>
                                    <th>Median Loss</th>
                                    <th>95th %ile</th>
                                    <th>Tags</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="simulationsTableBody">
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center" id="pagination">
                        </ul>
                    </nav>
                </div>

                <div id="emptyState" class="text-center py-5 d-none">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No simulations found</p>
                    <a href="index.php" class="btn btn-primary">Run Your First Simulation</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Simulation Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Simulation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="modalContent" class="d-none">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Input Parameters</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Asset Value:</strong></td>
                                        <td id="detailAssetValue"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>TEF Range:</strong></td>
                                        <td id="detailTEF"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Vulnerability:</strong></td>
                                        <td id="detailVuln"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Primary Loss:</strong></td>
                                        <td id="detailPrimaryLoss"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Secondary Loss:</strong></td>
                                        <td id="detailSecondaryLoss"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Iterations:</strong></td>
                                        <td id="detailIterations"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Statistics</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Mean Loss:</strong></td>
                                        <td id="detailMean"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Median Loss:</strong></td>
                                        <td id="detailMedian"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Min/Max:</strong></td>
                                        <td id="detailMinMax"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>90th Percentile:</strong></td>
                                        <td id="detailP90"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>95th Percentile:</strong></td>
                                        <td id="detailP95"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>99th Percentile:</strong></td>
                                        <td id="detailP99"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="detailHistogram"></canvas>
                        </div>
                        <div class="mt-3" id="detailNotes"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="modalExportLink" class="btn btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Compare Modal -->
    <div class="modal fade" id="compareModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compare Simulations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="chart-container">
                        <canvas id="compareChart"></canvas>
                    </div>
                    <div class="table-responsive mt-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Simulation</th>
                                    <th>Mean Loss</th>
                                    <th>Median Loss</th>
                                    <th>90th %ile</th>
                                    <th>95th %ile</th>
                                    <th>99th %ile</th>
                                </tr>
                            </thead>
                            <tbody id="compareTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    <script src="assets/js/history.js"></script>
</body>
</html>
